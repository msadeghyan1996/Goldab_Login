<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(now());

        // Ensure JWT can sign tokens during tests
        Config::set('auth.defaults.guard', 'api');
        Config::set('jwt.secret', 'testing-secret');
    }

    public function test_is_verification_completed_true_only_when_national_id_set(): void
    {
        $service = new UserService();
        $phone = '+989145509000';

        // No user yet
        $this->assertFalse($service->setPhone($phone)->isVerificationCompleted());

        // User without national_id -> false
        User::create(['phone' => $phone]);
        $this->assertFalse($service->setPhone($phone)->isVerificationCompleted());

        // User with national_id -> true
        $user = User::where('phone', $phone)->first();
        $user->update(['national_id' => 'ABC123']);
        $this->assertTrue($service->setPhone($phone)->isVerificationCompleted());
    }

    public function test_get_token_returns_expected_shape_and_values(): void
    {
        $service = new UserService();
        $phone = '+989145509000';

        // Make TTL deterministic
        Config::set('jwt.ttl', 5); // minutes

        $payload = $service->setPhone($phone)->getToken();

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('access_token', $payload);
        $this->assertArrayHasKey('token_type', $payload);
        $this->assertArrayHasKey('expires_in', $payload);

        $this->assertIsString($payload['access_token']);
        $this->assertNotEmpty($payload['access_token']);
        $this->assertSame('bearer', $payload['token_type']);
        $this->assertSame(5 * 60, $payload['expires_in']);

        // User should be created and currently authenticated via the api guard
        $this->assertDatabaseHas('users', ['phone' => $phone]);
        $this->assertTrue(Auth::check());
        $this->assertSame(User::where('phone', $phone)->first()->id, Auth::id());
    }
}


