<?php

use Illuminate\Support\Facades\RateLimiter;

test('auth lookup route throttles after five attempts per minute', function () {
    $phoneNumber = fake()->unique()->e164PhoneNumber();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/auth/lookup', [
            'phone_number' => $phoneNumber,
        ])->assertSuccessful();
    }

    $this->postJson('/api/auth/lookup', [
        'phone_number' => $phoneNumber,
    ])->assertTooManyRequests();

    RateLimiter::clear(md5('auth-mobile'.trim($phoneNumber)));
});
