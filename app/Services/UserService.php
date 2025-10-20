<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService
{
    private string $phone;

    /**
     * Retrieve an existing user by phone or create one if it does not exist.
     *
     * Uses the internally set phone number provided via setPhone().
     *
     * @return User The existing or newly created user model.
     */
    private function firstOrCreate(): User
    {
        return User::firstOrCreate([
            'phone' => $this->phone,
        ]);
    }

    /**
     * Set the phone number to operate on and enable method chaining.
     *
     * @param string $phone E.164 phone number including leading '+'.
     *
     * @return $this
     */
    public function setPhone(string $phone): UserService
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Determine whether the user's verification is complete for the set phone.
     *
     * Considers verification complete if a user exists with a non-null `national_id`.
     *
     * @return bool True if the user has a `national_id`; false otherwise.
     */
    public function isVerificationCompleted(): bool
    {
        return (bool) User::where('phone', $this->phone)->first()?->is_verification_completed;
    }

    /**
     * Ensure the user exists for the configured phone and issue a JWT.
     *
     * Creates or fetches the user, authenticates them, and returns the token payload.
     *
     * @return array{access_token:string, token_type:string, expires_in:int}
     */
    public function getToken(): array
    {
        return [
            'access_token' => Auth::login($this->firstOrCreate()),
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ];
    }
}
