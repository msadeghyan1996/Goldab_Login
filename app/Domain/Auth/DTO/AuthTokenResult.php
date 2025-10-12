<?php

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\TokenAbility;
use App\Models\User;

class AuthTokenResult
{
    public function __construct(
        public readonly User $user,
        public readonly string $plainTextToken,
        public readonly TokenAbility $ability,
    ) {}
}
