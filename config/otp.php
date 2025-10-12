<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    | Core parameters governing OTP generation, verification, and expiry.
    |
    */

    'length' => (int) env('OTP_LENGTH', 6),

    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 300),

    'attempt_limit' => (int) env('OTP_ATTEMPT_LIMIT', 5),

    'lock_seconds' => (int) env('OTP_LOCK_SECONDS', 900),

    'store' => env('OTP_STORE', 'redis'),

    'sender' => env('OTP_SENDER', 'log'),

    'hmac_key' => env('OTP_HMAC_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Store Drivers
    |--------------------------------------------------------------------------
    |
    | Configure the available OTP storage mechanisms. You may add additional
    | stores and resolve them via the AuthFlow service provider.
    |
    */

    'stores' => [
        'redis' => [
            'connection' => env('OTP_REDIS_CONNECTION', 'default'),
            'key_prefix' => env('OTP_REDIS_PREFIX', 'otp:'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sender Drivers
    |--------------------------------------------------------------------------
    |
    | Configure the available OTP delivery mechanisms. Vendor integrations
    | can be layered on top by adding new configuration blocks.
    |
    */

    'senders' => [
        'log' => [
            'channel' => env('OTP_LOG_CHANNEL'),
        ],

        'null' => [],
    ],

];
