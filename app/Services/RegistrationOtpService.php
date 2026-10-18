<?php

namespace App\Services;

use App\Mail\RegistrationOtpCode;
use App\Models\AuthVerificationToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegistrationOtpService
{
    public function issue(string $email, string $name): string
    {
        abort_if(! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true),
            503, 'Chức năng gửi email chưa được cấu hình. Vui lòng liên hệ hỗ trợ.');
        $otp = (string) random_int(100000, 999999);
        try {
            DB::transaction(function () use ($email, $name, $otp) {
                AuthVerificationToken::where('destination', $email)->where('channel', 'email')
                    ->where('purpose', 'registration')->whereNull('used_at')->update(['used_at' => now()]);
                AuthVerificationToken::create([
                    'destination' => $email, 'channel' => 'email', 'purpose' => 'registration',
                    'token_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(config('registration_otp.ttl_minutes')),
                    'attempt_count' => 0,
                ]);
                // Deliver in local too; a failed delivery rolls back the new token and invalidation.
                Mail::to($email)->send(new RegistrationOtpCode($otp, $name));
            });
        } catch (\Throwable $exception) {
            report($exception);
            abort(503, 'Chưa thể gửi email xác thực. Vui lòng thử lại sau.');
        }

        return $otp;
    }

    // Called within the account-creation transaction. Return errors so failed attempts commit.
    public function consume(string $email, string $otp): ?string
    {
        $record = AuthVerificationToken::where('destination', $email)->where('channel', 'email')
            ->where('purpose', 'registration')->latest('id')->lockForUpdate()->first();
        if (! $record || $record->used_at || $record->expires_at->lessThanOrEqualTo(now())
            || $record->attempt_count >= config('registration_otp.max_attempts')) {
            return 'Mã xác nhận không tồn tại, đã hết hạn hoặc đã được sử dụng. Vui lòng lấy mã mới.';
        }
        if (! Hash::check($otp, $record->token_hash)) {
            $record->attempt_count++;
            if ($record->attempt_count >= config('registration_otp.max_attempts')) {
                $record->used_at = now();
            }
            $record->save();

            return $record->used_at
                ? 'Bạn đã nhập sai mã 5 lần. Vui lòng lấy mã mới.'
                : 'Mã xác nhận OTP không chính xác.';
        }
        $record->update(['used_at' => now()]);

        return null;
    }
}
