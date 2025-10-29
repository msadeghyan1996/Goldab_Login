<?php

namespace App\Exceptions\Otp;

use App\Exceptions\ProjectLogicException;

abstract class OtpException extends ProjectLogicException
{
    public function __construct(string $message, int $statusCode, string $errorType)
    {
        parent::__construct($message, $statusCode, $errorType);
    }
}
