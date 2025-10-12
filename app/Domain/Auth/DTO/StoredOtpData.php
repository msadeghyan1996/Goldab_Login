<?php

namespace App\Domain\Auth\DTO;

use Carbon\CarbonImmutable;

class StoredOtpData
{
    public function __construct(
        public readonly string $mobile,
        public readonly string $codeHash,
        public readonly CarbonImmutable $expiresAt,
        public readonly int $attempts,
        public readonly ?CarbonImmutable $lockedUntil,
    ) {}

    public function isExpired(?CarbonImmutable $reference = null): bool
    {
        $reference ??= CarbonImmutable::now();

        return $this->expiresAt->lessThanOrEqualTo($reference);
    }

    public function isLocked(?CarbonImmutable $reference = null): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }

        $reference ??= CarbonImmutable::now();

        return $this->lockedUntil->greaterThan($reference);
    }
}
