<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for testing
        $this->artisan('migrate:fresh');
    }

    /**
     * Generate test JWT token for authenticated requests
     */
    protected function generateTestToken($userId = 1, $mobile = '9145813194'): string
    {
        $payload = [
            'iss' => env('APP_URL', 'http://localhost'),
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600,
            'mobile' => $mobile,
        ];

        return \Firebase\JWT\JWT::encode($payload, env('JWT_SECRET', 'test_jwt_secret_key'), 'HS256');
    }

    /**
     * Helper to make authenticated JSON request
     */
    protected function authenticatedJson($method, $uri, array $data = [], $userId = 1)
    {
        $token = $this->generateTestToken($userId);
        
        return $this->json($method, $uri, $data, [
            'Authorization' => 'Bearer ' . $token
        ]);
    }
}

