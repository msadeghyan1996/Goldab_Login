<?php

namespace App\Listeners;

use App\Events\OtpCodeGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class OTPSmsProvider implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OtpCodeGenerated $event): void
    {
        Log::debug('OTP code generated for phone number ' . $event->phoneNumber . ' | Code: ' . $event->code);
    }
}
