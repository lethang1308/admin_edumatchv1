<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RegistrationOtpCode extends Mailable
{
    public function __construct(public string $otp, public string $name = '') {}

    public function build(): static
    {
        return $this->subject('Mã xác thực đăng ký tài khoản EduMatch')
            ->view('emails.registration-otp');
    }
}
