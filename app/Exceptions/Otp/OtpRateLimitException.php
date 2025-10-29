<?php

namespace App\Exceptions\Otp;

class OtpRateLimitException extends OtpException
{
    private int $retryAfterSeconds;

    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct(
            'Too many OTP requests. Please try again later.',
            429,
            'too_many_requests'
        );

        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
