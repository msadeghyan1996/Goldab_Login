<?php

namespace App\Services\User;

use App\Models\User\User;

class UserRegisterService
{
    private User $user;

    public function __construct(private readonly string $phoneNumber)
    {
    }

    public function handle(): self
    {
        $this->findOrCreateUser();
        cache()?->forget(User::getOtpCodeCacheKeyByPhoneNumber($this->phoneNumber));
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
