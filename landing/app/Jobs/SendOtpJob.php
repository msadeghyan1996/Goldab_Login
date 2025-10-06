<?php

namespace App\Jobs;

use App\Enums\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $phoneNumber, protected string $otp)
    {
        $this->onQueue(QueueName::Otp->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger()?->info("OTP is sent to the phone number '$this->phoneNumber'. OTP value is $this->otp!");
    }
}
