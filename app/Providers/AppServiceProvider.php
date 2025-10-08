<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Src\User\Contracts\UserContract;
use Src\User\Repositories\UserRepository;
use Src\Verification\Contracts\VerificationContract;
use Src\Verification\Repositories\VerificationRepository;

class AppServiceProvider extends ServiceProvider {
    public function register(): void{}

    public function boot(): void{

        Schema::defaultStringLength(191);

        $services = [
            VerificationContract::class => VerificationRepository::class,
            UserContract::class         => UserRepository::class,
        ];

        foreach ($services as $interface => $repository) {
            $this->app->bind($interface, $repository);
        }
    }
}
