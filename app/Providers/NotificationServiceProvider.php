<?php
namespace App\Providers;
use App\Channels\impl\SmsChannel;
use App\Providers\Sms\SmsProviderInterface;
use App\Providers\Sms\impl\SmsIrProvider;
use Illuminate\Support\ServiceProvider;
class NotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SmsProviderInterface::class, function ($app) {
            return $this->createSmsProvider();
        });
        $this->app->singleton(SmsChannel::class, function ($app) {
            return new SmsChannel(
                $app->make(SmsProviderInterface::class)
            );
        });
        $this->app->singleton(\App\Services\impl\NotificationService::class, function ($app) {
            return new \App\Services\impl\NotificationService();
        });
    }
    protected function createSmsProvider(): SmsProviderInterface
    {
        $provider = env('SMS_PROVIDER', 'sms.ir');
        return match ($provider) {
            'sms.ir' => new SmsIrProvider(),
            default => throw new \RuntimeException("Unsupported provider: $provider"),
        };
    }
}