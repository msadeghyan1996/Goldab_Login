<?php

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\OtpChannel;

class OtpContext
{
    public function __construct(
        public readonly string $mobile,
        public readonly string $ip,
        public readonly string $userAgent,
        public readonly OtpChannel $channel = OtpChannel::Api,
        public readonly ?int $userId = null,
    ) {}

    public function withUserId(?int $userId): self
    {
        if ($userId === null || $this->userId === $userId) {
            return $this;
        }

        return new self(
            mobile: $this->mobile,
            ip: $this->ip,
            userAgent: $this->userAgent,
            channel: $this->channel,
            userId: $userId,
        );
    }
}
