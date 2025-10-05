<?php

namespace App\Services\User;

use App\Models\User\User;

class UserRegisterService
{
    private User $user;

    public function __construct(private string $phoneNumber)
    {
    }

    public function handle(): self
    {
        $this->findOrCreateUser();
        return $this;
    }

    public function needsCompletion(): bool
    {
        return !$this->user->isProfileCompleted();
    }

    private function findOrCreateUser(): void
    {
        $this->user = User::query()->firstOrCreate(['phone_number' => $this->phoneNumber]);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
