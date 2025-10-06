<?php

namespace Database\Factories;

use App\Models\OTP;
use App\Models\User;
use App\Enums\OTP\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OTP>
 */
class OTPFactory extends Factory {
    protected $model = OTP::class;

    public function definition () : array {
        return [
            'user_id'    => User::factory(),
            'code'       => (string) $this->faker->numberBetween(1000, 9999),
            'type'       => Type::Login->value,
            'expires_at' => Carbon::now()->addMinutes(5),
        ];
    }

    public function expired () : static {
        return $this->state(fn() => [
            'expires_at' => Carbon::now()->subMinutes(5),
        ]);
    }

    public function withCode (string $code) : static {
        return $this->state(fn() => [
            'code' => $code,
        ]);
    }
}
