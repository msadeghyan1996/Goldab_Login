<?php

namespace App\Exceptions\Otp;

class OtpTooManyAttemptsException extends OtpException
{
    public function __construct()
    {
        parent::__construct(
            'Too many invalid OTP attempts. Please request a new code.',
            429,
            'too_many_attempts'
        );
    }
}
