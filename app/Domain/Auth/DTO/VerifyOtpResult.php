<?php

namespace App\Domain\Auth\DTO;

class VerifyOtpResult
{
    public function __construct(
        public readonly OtpVerificationResult $verification,
        public readonly ?AuthTokenResult $token = null,
    ) {}

    public function tokenIssued(): bool
    {
        return $this->token !== null
            && $this->verification->isSuccessful();
    }
}
