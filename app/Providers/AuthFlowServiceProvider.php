<?php

namespace App\Providers;

use App\Domain\Auth\Contracts\OtpSender;
use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Senders\LogOtpSender;
use App\Domain\Auth\Senders\NullOtpSender;
use App\Domain\Auth\Services\OtpManager;
use App\Domain\Auth\Stores\RedisOtpStore;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AuthFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OtpStore::class, function (Application $app): OtpStore {
            $config = $app->make(Repository::class)->get('otp', []);
            $store = Arr::get($config, 'store', 'redis');

            return match ($store) {
                'redis' => new RedisOtpStore(
                    $app->make(RedisFactory::class),
                    Arr::get($config, 'stores.redis.connection', 'default'),
                    Arr::get($config, 'stores.redis.key_prefix', 'otp:'),
                ),
                default => throw new InvalidArgumentException("Unsupported OTP store driver [{$store}]."),
            };
        });

        $this->app->singleton(OtpSender::class, function (Application $app): OtpSender {
            $config = $app->make(Repository::class)->get('otp', []);
            $driver = Arr::get($config, 'sender', 'log');

            return match ($driver) {
                'log' => new LogOtpSender(
                    $app->make(LogManager::class),
                    $app,
                    Arr::get($config, 'senders.log.channel'),
                ),
                'null' => new NullOtpSender,
                default => throw new InvalidArgumentException("Unsupported OTP sender driver [{$driver}]."),
            };
        });

        $this->app->singleton(OtpManager::class, function (Application $app): OtpManager {
            return new OtpManager(
                $app->make(OtpStore::class),
                $app->make(Dispatcher::class),
                $app->make(Repository::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('otp-request', function (Request $request) {
            $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile', ''));
            $key = sha1($request->ip().'|'.$mobile);

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile', ''));
            $key = sha1($request->ip().'|'.$mobile);

            return [
                Limit::perMinute(10)->by($key),
                Limit::perMinute(60)->by($request->ip()),
            ];
        });
    }
}
