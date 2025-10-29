<?php

return [
    'code_length' => env('OTP_CODE_LENGTH', 6),
    'ttl_minutes' => env('OTP_TTL_MINUTES', 5),
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
    'rate_limit' => [
        'per_phone_attempts' => env('OTP_RATE_LIMIT_ATTEMPTS', 3),
        'per_phone_decay_seconds' => env('OTP_RATE_LIMIT_DECAY', 60),
    ],
    'registration_token_ttl_minutes' => env('OTP_REGISTRATION_TOKEN_TTL_MINUTES', 15),
];
