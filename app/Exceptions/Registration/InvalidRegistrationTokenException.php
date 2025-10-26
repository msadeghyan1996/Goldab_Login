<?php

namespace App\Exceptions\Registration;

use App\Exceptions\ProjectLogicException;

class InvalidRegistrationTokenException extends ProjectLogicException
{
    public function __construct()
    {
        parent::__construct(
            'The registration token is invalid or has expired.',
            422,
            'invalid_registration_token'
        );
    }
}
