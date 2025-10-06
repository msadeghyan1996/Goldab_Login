<?php

namespace Database\Factories\User;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => '09' . fake()->numerify('#########'),
            'name' => fake()->name(),
            'national_id' => fake()->numerify('##########'),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Indicate that the user profile is incomplete (no name, national_id, or password).
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'national_id' => null,
            'password' => null,
        ]);
    }

    /**
     * Create a user with only phone number (minimal registration via OTP).
     */
    public function phoneOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'national_id' => null,
            'password' => null,
        ]);
    }

    /**
     * Create a user with a specific phone number.
     */
    public function withPhone(string $phoneNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * Create a user with a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Create a completed profile user (all fields filled).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['name'] ?? fake()->name(),
            'national_id' => $attributes['national_id'] ?? fake()->numerify('##########'),
            'password' => $attributes['password'] ?? Hash::make('password'),
        ]);
    }
}
