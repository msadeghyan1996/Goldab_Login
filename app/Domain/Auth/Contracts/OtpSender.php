<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTO\OtpContext;

interface OtpSender
{
    public function send(OtpContext $context, string $code): void;
}
