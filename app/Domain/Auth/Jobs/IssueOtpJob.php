<?php

namespace App\Domain\Auth\Jobs;

use App\Domain\Auth\Contracts\OtpSender;
use App\Domain\Auth\DTO\OtpContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IssueOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly OtpContext $context,
        public readonly string $code,
    ) {
        $this->onQueue('otp');
    }

    public function handle(OtpSender $sender): void
    {
        $sender->send($this->context, $this->code);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return [
            'auth',
            'otp',
            'mobile:'.$this->context->mobile,
        ];
    }
}
