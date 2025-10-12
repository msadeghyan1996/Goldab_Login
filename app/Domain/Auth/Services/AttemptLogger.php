<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\AttemptMethod;
use App\Domain\Auth\Enums\AttemptResult;
use App\Domain\Auth\Models\LoginAttempt;
use Carbon\CarbonImmutable;

class AttemptLogger
{
    public function log(
        AttemptMethod $method,
        AttemptResult $result,
        OtpContext $context,
        array $meta = [],
        ?int $userId = null,
    ): LoginAttempt {
        return LoginAttempt::query()->create([
            'user_id' => $userId ?? $context->userId,
            'mobile' => $context->mobile,
            'ip' => $context->ip,
            'user_agent' => $context->userAgent,
            'channel' => $context->channel,
            'method' => $method,
            'result' => $result,
            'context' => $meta,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }
}
