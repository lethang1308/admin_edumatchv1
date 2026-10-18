<?php

namespace App\Services;

use App\Mail\PasswordResetCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class PasswordResetDelivery
{
    public function isDebug(): bool
    {
        return app()->environment(['local', 'testing']) && (bool) config('password_reset.debug');
    }

    public function ensureAvailable(string $channel): void
    {
        if ($this->isDebug()) {
            return;
        }
        if ($channel === 'sms') {
            abort_unless(config('password_reset.sms_url'), 503, 'Chức năng gửi SMS chưa được cấu hình. Vui lòng sử dụng email hoặc liên hệ hỗ trợ.');
        } else {
            // A log/array mailer cannot deliver a production password-reset code.
            abort_if(in_array(config('mail.default'), ['log', 'array'], true), 503, 'Chức năng gửi email chưa được cấu hình. Vui lòng liên hệ hỗ trợ.');
        }
    }

    public function send(string $destination, string $channel, string $otp): void
    {
        if ($this->isDebug()) {
            return;
        }
        if ($channel === 'email') {
            Mail::to($destination)->send(new PasswordResetCode($otp));
        } else {
            Http::timeout(10)->withToken((string) config('password_reset.sms_token'))
                ->post(config('password_reset.sms_url'), [
                    'to' => $destination,
                    'message' => "Ma dat lai mat khau EduMatch: {$otp}. Hieu luc 15 phut. Khong chia se ma nay.",
                ])->throw();
        }
    }
}
