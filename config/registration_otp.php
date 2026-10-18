<?php

return [
    'ttl_minutes' => 10,
    'max_attempts' => 5,
    'resend_after_seconds' => 60,
    // Never returned in production. Email is still delivered when this is enabled.
    'debug' => env('REGISTRATION_OTP_DEBUG', false),
];
