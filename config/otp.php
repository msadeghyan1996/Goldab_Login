<?php

return [
    'length' => (int) env('OTP_LENGTH', 6),
    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 2),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'pepper' => (string) env('OTP_PEPPER', env('APP_KEY')),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 120),
    'max_sends_per_hour' => (int) env('OTP_MAX_SENDS_PER_HOUR', 5),
    'max_sends_per_ip_in_cooldown' => (int) env('OTP_MAX_SENDS_PER_IP_IN_COOLDOWN', 3),
    'cleanup_days' => (int) env('OTP_CLEANUP_DAYS', 2),
];
