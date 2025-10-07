<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \App\Repositories\UserRepositoryInterface::class,
            function ($app) {
                return new \App\Repositories\impl\UserRepository(
                    new \App\Models\User()
                );
            }
        );
        $this->app->bind(
            \App\Repositories\OtpRepositoryInterface::class,
            function ($app) {
                return new \App\Repositories\impl\OtpRepository(
                    new \App\Models\OtpCode()
                );
            }
        );
        $this->app->bind(
            \App\Repositories\LoginAttemptRepositoryInterface::class,
            function ($app) {
                return new \App\Repositories\impl\LoginAttemptRepository(
                    new \App\Models\LoginAttempt()
                );
            }
        );
    }
}