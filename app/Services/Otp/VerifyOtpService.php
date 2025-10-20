<?php

namespace App\Services\Otp;

use App\Models\OtpToken;

class VerifyOtpService
{
    private string $phone;
    private ?OtpToken $otpToken;

    public function __construct()
    {
    }

    /**
     * Set the phone number and load the latest active OTP for it.
     *
     * @param string $phone E.164 phone number including leading '+'.
     *
     * @return VerifyOtpService Fluent interface for chaining.
     */
    public function setPhone(string $phone): VerifyOtpService
    {
        $this->phone = $phone;

        $this->findToken();

        return $this;
    }

    /**
     * Mark the currently loaded OTP token as consumed.
     *
     * Sets `consumed_at` to now. No-op if no token is loaded.
     *
     * @return VerifyOtpService Fluent interface for chaining.
     */
    public function consume(): VerifyOtpService
    {
        $this->otpToken->update([
            'consumed_at' => now(),
        ]);

        OtpCache::forget($this->otpToken->phone);

        return $this;
    }

    /**
     * Increment the attempts counter on the current OTP token.
     *
     * @return VerifyOtpService Fluent interface for chaining.
     */
    public function attempt(): VerifyOtpService
    {
        $this->otpToken->increment('attempts_count');

        return $this;
    }

    /**
     * Get the currently loaded OTP token if one exists.
     *
     * @return OtpToken|null The active OTP token or null if none found.
     */
    public function getOtpToken(): ?OtpToken
    {
        return $this->otpToken;
    }

    /**
     * Load the latest active OTP token for the configured phone.
     *
     * Finds the most recent token that is unconsumed and unexpired.
     *
     * @return OtpToken|null The active OTP token or null if none found.
     */
    private function findToken(): ?OtpToken
    {
        $this->otpToken = OtpToken::where('phone', $this->phone)->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')->first();

        return $this->otpToken;
    }

    /**
     * Check whether a candidate code matches an OTP token.
     *
     * Computes SHA-256 over pepper + salt + candidate and compares it to the stored
     * hash using a timing-attack safe comparison.
     *
     * @param string $candidate The plain code provided by the user.
     *
     * @return bool True if the candidate matches; false otherwise.
     */
    public function codeIsValid(string $candidate): bool
    {
        $pepper = config('otp.pepper');

        $hash = hash('sha256', $pepper . $this->otpToken->salt . $candidate);

        return hash_equals($this->otpToken->code_hash, $hash);
    }
}
