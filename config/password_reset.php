<?php

return [
    'ttl_minutes' => 15,
    'max_attempts' => 5,
    // This flag is effective ONLY in local/testing environments.
    'debug' => env('PASSWORD_RESET_DEBUG', true),
    // Optional application-owned SMS gateway accepting {to, message} JSON.
    'sms_url' => env('PASSWORD_RESET_SMS_URL'),
    'sms_token' => env('PASSWORD_RESET_SMS_TOKEN'),
];
