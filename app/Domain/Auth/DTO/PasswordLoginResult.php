<?php

namespace App\Domain\Auth\DTO;

class PasswordLoginResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?AuthTokenResult $token = null,
        public readonly ?string $message = null,
    ) {}

    public static function success(AuthTokenResult $token): self
    {
        return new self(true, $token);
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }
}
