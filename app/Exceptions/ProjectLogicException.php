<?php

namespace App\Exceptions;

use Exception;

class ProjectLogicException extends Exception
{
    protected int $httpStatusCode;
    protected ?string $errorType;

    public function __construct(
        string $message = "",
        int $httpStatusCode = 400,
        ?string $errorType = null,
        int $code = 0,
        ?\Throwable $previous = null
    )
    {
        $this->httpStatusCode = $httpStatusCode;
        $this->errorType = $errorType;
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }
}
