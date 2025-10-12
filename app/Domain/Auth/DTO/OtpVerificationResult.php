<?php

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\OtpVerificationStatus;
use Carbon\CarbonImmutable;

class OtpVerificationResult
{
    public function __construct(
        public readonly OtpVerificationStatus $status,
        public readonly ?int $attempts = null,
        public readonly ?int $remainingAttempts = null,
        public readonly ?CarbonImmutable $lockedUntil = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === OtpVerificationStatus::Success;
    }

    public static function success(): self
    {
        return new self(OtpVerificationStatus::Success);
    }

    public static function invalid(int $attempts, int $remainingAttempts): self
    {
        return new self(OtpVerificationStatus::Invalid, $attempts, $remainingAttempts);
    }

    public static function expired(): self
    {
        return new self(OtpVerificationStatus::Expired);
    }

    public static function locked(CarbonImmutable $lockedUntil, ?int $attempts = null): self
    {
        return new self(OtpVerificationStatus::Locked, $attempts, remainingAttempts: null, lockedUntil: $lockedUntil);
    }

    public static function missing(): self
    {
        return new self(OtpVerificationStatus::Missing);
    }
}
