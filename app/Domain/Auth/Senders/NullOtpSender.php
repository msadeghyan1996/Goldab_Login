<?php

namespace App\Domain\Auth\Senders;

use App\Domain\Auth\Contracts\OtpSender;
use App\Domain\Auth\DTO\OtpContext;

class NullOtpSender implements OtpSender
{
    public function send(OtpContext $context, string $code): void
    {
        // Intentionally left blank.
    }
}
