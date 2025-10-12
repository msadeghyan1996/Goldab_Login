<?php

namespace App\Domain\Auth\Events;

use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\OtpIssueResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OtpContext $context,
        public readonly OtpIssueResult $result,
    ) {}
}
