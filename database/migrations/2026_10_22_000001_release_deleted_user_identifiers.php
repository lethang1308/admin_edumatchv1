<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Repair accounts deleted by the legacy admin, without deleting FK history.
        DB::table('users')->whereNotNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('email')->orWhere('email', 'not like', '%@deleted.invalid')->orWhereNotNull('phone'))
            ->orderBy('id')->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::transaction(function () use ($user) {
                        $user = DB::table('users')->where('id', $user->id)->whereNotNull('deleted_at')->lockForUpdate()->first();
                        if (! $user) {
                            return;
                        }
                        DB::table('personal_access_tokens')->where('tokenable_type', (new \App\Models\User)->getMorphClass())->where('tokenable_id', $user->id)->delete();
                        DB::table('auth_verification_tokens')->where('user_id', $user->id)
                            ->when($user->email, fn ($query) => $query->orWhere('destination', $user->email))->delete();
                        if ($user->email) {
                            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                        }
                        DB::table('users')->where('id', $user->id)->update([
                            'email' => 'deleted-'.$user->id.'-'.Str::uuid().'@deleted.invalid',
                            'phone' => null, 'password' => Hash::make(Str::random(64)),
                            'remember_token' => null, 'email_verified_at' => null, 'phone_verified_at' => null,
                            'status' => 'blocked', 'is_active' => false, 'updated_at' => now(),
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Deleted login identifiers and credentials are intentionally not restored.
    }
};
