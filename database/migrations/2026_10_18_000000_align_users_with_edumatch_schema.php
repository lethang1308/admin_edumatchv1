<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Validate legacy data before adding stricter constraints. Never truncate it.
        if (DB::table('users')->whereRaw('CHAR_LENGTH(email) > 191 OR CHAR_LENGTH(phone) > 20 OR CHAR_LENGTH(name) > 150 OR CHAR_LENGTH(role) > 20')->exists()
            || DB::table('users')->whereNull('password')->exists()
            || DB::table('users')->whereNull('email')->whereNull('phone')->exists()
            || DB::table('users')->select('phone')->whereNotNull('phone')->groupBy('phone')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot upgrade users: fix oversized name/email/phone/role, missing password/contact, or duplicate phones first.');
        }

        DB::table('users')->whereNull('name')->update([
            'name' => DB::raw("LEFT(COALESCE(full_name, email, phone, CONCAT('User ', id)), 150)"),
        ]);
        DB::table('users')->where('role', 'user')->update(['role' => 'student']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('users')->whereNull('created_at')->update(['created_at' => now()]);
        DB::table('users')->whereNull('updated_at')->update(['updated_at' => now()]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
            $table->string('name', 150)->nullable(false)->change();
            $table->string('email', 191)->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->string('password', 255)->nullable(false)->change();
            $table->dateTime('email_verified_at')->nullable()->change();
            $table->dateTime('created_at')->nullable(false)->change();
            $table->dateTime('updated_at')->nullable(false)->change();
            $table->string('avatar_path', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->dateTime('phone_verified_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->dateTime('blocked_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unique('phone');
            $table->index(['role', 'status', 'created_at']);
        });

        DB::table('users')->whereNotNull('avatar')->update(['avatar_path' => DB::raw('avatar')]);
        DB::table('users')->where('is_active', false)->update(['status' => 'blocked']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['role', 'status', 'created_at']);
            $table->dropColumn(['avatar_path', 'status', 'phone_verified_at', 'last_login_at', 'blocked_reason', 'blocked_at', 'deleted_at']);
            $table->string('role')->default('user')->change();
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->timestamp('email_verified_at')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        DB::table('users')->where('role', 'student')->update(['role' => 'user']);
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
    }
};
