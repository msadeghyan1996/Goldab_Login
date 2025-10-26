<?php

namespace App\Services;

use App\Events\OtpCodeGenerated;
use App\Exceptions\Otp\OtpExpiredException;
use App\Exceptions\Otp\OtpInvalidCodeException;
use App\Exceptions\Otp\OtpRateLimitException;
use App\Exceptions\Otp\OtpTooManyAttemptsException;
use App\Exceptions\Registration\InvalidRegistrationTokenException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * @throws OtpRateLimitException
     */
    public function request(string $phoneNumber): void
    {
        $this->enforceRateLimit($phoneNumber);

        $code = $this->generateCode((int) config('otp.code_length', 6));

        $expiresAt = Carbon::now()->addMinutes((int) config('otp.ttl_minutes', 5));

        $payload = [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ];

        Cache::put(
            $this->otpKey($phoneNumber),
            $payload,
            $this->secondsUntil($expiresAt)
        );

        event(new OtpCodeGenerated($phoneNumber, $code));
    }

    /**
     * @throws OtpExpiredException
     * @throws OtpInvalidCodeException
     * @throws OtpTooManyAttemptsException
     */
    public function verify(string $phoneNumber, string $code): void
    {
        $key = $this->otpKey($phoneNumber);

        $payload = Cache::get($key);

        if (!$payload) {
            throw new OtpExpiredException();
        }

        $expiresAt = $this->normalizeCarbon($payload['expires_at'] ?? null);

        if (Carbon::now()->greaterThanOrEqualTo($expiresAt)) {
            Cache::forget($key);

            throw new OtpExpiredException();
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        $maxAttempts = (int) config('otp.max_attempts', 5);

        if ($attempts >= $maxAttempts) {
            Cache::forget($key);

            throw new OtpTooManyAttemptsException();
        }

        if (!Hash::check($code, $payload['hash'])) {
            $attempts++;

            if ($attempts >= $maxAttempts) {
                Cache::forget($key);

                throw new OtpTooManyAttemptsException();
            }

            $payload['attempts'] = $attempts;

            Cache::put(
                $key,
                $payload,
                $this->secondsUntil($expiresAt)
            );

            throw new OtpInvalidCodeException();
        }

        Cache::forget($key);
    }

    public function createPendingRegistrationToken(string $phoneNumber): string
    {
        $token = Str::random(40);

        $expiresAt = Carbon::now()->addMinutes(
            (int) config('otp.registration_token_ttl_minutes', 15)
        );

        $payload = [
            'phone_number' => $phoneNumber,
            'verified_at' => Carbon::now(),
        ];

        Cache::put(
            $this->pendingRegistrationKey($token),
            $payload,
            $this->secondsUntil($expiresAt)
        );

        return $token;
    }

    /**
     * @throws InvalidRegistrationTokenException
     */
    public function getPendingRegistration(string $token): array
    {
        $payload = Cache::get($this->pendingRegistrationKey($token));

        if (!$payload) {
            throw new InvalidRegistrationTokenException();
        }

        return $payload;
    }

    public function forgetPendingRegistration(string $token): void
    {
        Cache::forget($this->pendingRegistrationKey($token));
    }

    /**
     * @throws OtpRateLimitException
     */
    private function enforceRateLimit(string $phoneNumber): void
    {
        $limitKey = $this->rateLimitKey($phoneNumber);
        $maxAttempts = (int) config('otp.rate_limit.per_phone_attempts', 3);
        $decaySeconds = (int) config('otp.rate_limit.per_phone_decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
            throw new OtpRateLimitException(
                RateLimiter::availableIn($limitKey)
            );
        }

        RateLimiter::hit($limitKey, $decaySeconds);
    }

    private function generateCode(int $length): string
    {
        $length = max(1, $length);

        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function secondsUntil(Carbon $expiresAt): int
    {
        return max(1, Carbon::now()->diffInSeconds($expiresAt, false));
    }

    private function normalizeCarbon(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return Carbon::now();
    }

    private function otpKey(string $phoneNumber): string
    {
        return "otp:{$phoneNumber}";
    }

    private function pendingRegistrationKey(string $token): string
    {
        return "pending-registration:{$token}";
    }

    private function rateLimitKey(string $phoneNumber): string
    {
        return sprintf('otp:request:%s', $phoneNumber);
    }
}
