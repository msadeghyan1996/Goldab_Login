<?php

namespace App\Domain\Auth\Events;

use App\Domain\Auth\DTO\OtpContext;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OtpContext $context,
        public readonly string $reason,
    ) {}
}
