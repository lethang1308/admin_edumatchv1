<?php

namespace Tests\Concerns;

use App\Models\AuthVerificationToken;
use Illuminate\Support\Facades\Hash;

trait SeedsRegistrationOtp
{
    protected function seedRegistrationOtp(string $email, string $otp = '123456', array $attributes = []): AuthVerificationToken
    {
        return AuthVerificationToken::create(array_replace([
            'destination' => mb_strtolower(trim($email)), 'channel' => 'email', 'purpose' => 'registration',
            'token_hash' => Hash::make($otp), 'expires_at' => now()->addMinutes(10), 'attempt_count' => 0,
        ], $attributes));
    }
}
