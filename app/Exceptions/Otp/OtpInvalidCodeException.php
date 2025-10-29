<?php

namespace App\Exceptions\Otp;

class OtpInvalidCodeException extends OtpException
{
    public function __construct()
    {
        parent::__construct(
            'The provided OTP code is invalid.',
            422,
            'invalid_code'
        );
    }
}
