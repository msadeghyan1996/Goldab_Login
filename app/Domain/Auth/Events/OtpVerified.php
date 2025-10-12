<?php

namespace App\Domain\Auth\Events;

use App\Domain\Auth\DTO\AuthTokenResult;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\OtpVerificationResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpVerified
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OtpContext $context,
        public readonly OtpVerificationResult $verification,
        public readonly ?AuthTokenResult $token,
    ) {}
}
