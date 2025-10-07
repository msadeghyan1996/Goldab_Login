<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(\App\Services\impl\RedisCacheService::class, function ($app) {
            return new \App\Services\impl\RedisCacheService();
        });
        $this->app->singleton(\App\Services\OtpServiceInterface::class, function ($app) {
            return new \App\Services\impl\RedisOtpService(
                $app->make(\App\Services\impl\RedisCacheService::class),
                $app->make(\App\Services\impl\NotificationService::class)
            );
        });
        $this->app->singleton(\App\Services\impl\AuthService::class, function ($app) {
            return new \App\Services\impl\AuthService(
                $app->make(\App\Repositories\UserRepositoryInterface::class),
                $app->make(\App\Services\OtpServiceInterface::class),
                $app->make(\App\Repositories\LoginAttemptRepositoryInterface::class)
            );
        });
    }
}