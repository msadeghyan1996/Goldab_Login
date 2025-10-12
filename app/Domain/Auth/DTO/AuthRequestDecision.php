<?php

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthNextStep;
use Carbon\CarbonImmutable;

class AuthRequestDecision
{
    public function __construct(
        public readonly AuthNextStep $next,
        public readonly ?OtpIssueResult $otpResult = null,
    ) {}

    public static function password(): self
    {
        return new self(AuthNextStep::Password);
    }

    public static function otp(OtpIssueResult $result): self
    {
        return new self(AuthNextStep::Otp, $result);
    }

    public function isLocked(): bool
    {
        return $this->otpResult !== null
            && ! $this->otpResult->issued
            && $this->otpResult->lockedUntil instanceof CarbonImmutable;
    }
}
