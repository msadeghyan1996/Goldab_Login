<?php

namespace App\Services;

use App\Models\Enum\OtpPurpose;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * send sms to specific phone number
     *
     * @param string $phone
     * @param OtpPurpose $purpose
     * @param string $code
     *
     * @return void
     */
    public function send(string $phone, OtpPurpose $purpose, string $code): void
    {
        Log::info("DEV OTP {$purpose->label()} for {$phone} by code: {$code}");
    }
}
