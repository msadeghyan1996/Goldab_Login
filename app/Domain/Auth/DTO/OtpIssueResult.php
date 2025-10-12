<?php

namespace App\Domain\Auth\DTO;

use Carbon\CarbonImmutable;

class OtpIssueResult
{
    public function __construct(
        public readonly bool $issued,
        public readonly ?CarbonImmutable $expiresAt = null,
        public readonly ?CarbonImmutable $lockedUntil = null,
    ) {}

    public static function issued(CarbonImmutable $expiresAt): self
    {
        return new self(true, $expiresAt);
    }

    public static function locked(CarbonImmutable $lockedUntil): self
    {
        return new self(false, null, $lockedUntil);
    }
}
