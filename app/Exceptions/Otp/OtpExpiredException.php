<?php

namespace App\Exceptions\Otp;

class OtpExpiredException extends OtpException
{
    public function __construct()
    {
        parent::__construct(
            'The OTP has expired. Please request a new code.',
            410,
            'otp_expired'
        );
    }
}
