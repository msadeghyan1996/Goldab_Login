<?php

namespace App\Services\Otp;

use App\Models\OtpToken;
use Illuminate\Support\Facades\Cache;

class OtpCache
{
    /**
     * Store the latest OTP token for a phone in cache until it expires.
     *
     * @param OtpToken $otpToken OTP token instance to cache.
     *
     * @return bool True on success, false otherwise.
     */
    public static function set(OtpToken $otpToken): bool
    {
        return Cache::put(self::key($otpToken->phone), $otpToken, $otpToken->expires_at);
    }

    /**
     * Retrieve the cached OTP token for the given phone number.
     *
     * @param string $phone Normalized E.164 phone number including leading '+'.
     *
     * @return OtpToken|null The cached token or null if not found/expired.
     */
    public static function get(string $phone) : mixed
    {
        return Cache::get(self::key($phone));
    }

    /**
     * Delete the cached OTP token for the given phone number.
     *
     * @param string $phone Normalized E.164 phone number including leading '+'.
     *
     * @return bool True if the key existed and was removed; false otherwise.
     */
    public static function forget(string $phone): bool
    {
        return Cache::forget(self::key($phone));
    }

    /**
     * Build the cache key for the given phone number.
     *
     * @param string $phone Normalized E.164 phone number including leading '+'.
     *
     * @return string Cache key used for storing the token.
     */
    private static function key(string $phone): string
    {
        return 'otp-token:' . $phone;
    }
}
