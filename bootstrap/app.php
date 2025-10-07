<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->configure('database');
$app->configure('cache');
$app->configure('queue');
$app->configure('view');
$app->configure('swagger-lume');

$app->alias('cache', \Illuminate\Cache\CacheManager::class);

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'throttle' => App\Http\Middleware\ThrottleRequests::class,
]);

$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(App\Providers\NotificationServiceProvider::class);
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\RepositoryServiceProvider::class);
$app->register(\SwaggerLume\ServiceProvider::class);

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/api.php';
});

return $app;

