<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetCode extends Mailable
{
    public function __construct(public string $otp) {}

    public function build(): static
    {
        return $this->subject('Mã đặt lại mật khẩu EduMatch')->view('emails.password-reset-code');
    }
}
