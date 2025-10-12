<?php

namespace App\Domain\Auth\Senders;

use App\Domain\Auth\Contracts\OtpSender;
use App\Domain\Auth\DTO\OtpContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;

class LogOtpSender implements OtpSender
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Application $app,
        private readonly ?string $channel = null,
    ) {}

    public function send(OtpContext $context, string $code): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        $logger = $this->channel !== null
            ? $this->log->channel($this->channel)
            : $this->log;

        $logger->info('OTP issued', [
            'mobile' => $context->mobile,
            'code' => $code,
            'channel' => $context->channel,
            'ip' => $context->ip,
        ]);
    }
}
