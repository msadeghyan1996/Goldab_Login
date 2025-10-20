<?php

namespace App\Helper\Log;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log as LogFacade;

/**
 * @method static \Psr\Log\LoggerInterface build(array $config)
 * @method static \Psr\Log\LoggerInterface stack(array $channels, string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface channel(string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface driver(string|null $driver = null)
 * @method static \Illuminate\Log\LogManager shareContext(array $context)
 * @method static array sharedContext()
 * @method static \Illuminate\Log\LogManager withoutContext()
 * @method static \Illuminate\Log\LogManager flushSharedContext()
 * @method static string|null getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \Illuminate\Log\LogManager extend(string $driver, \Closure $callback)
 * @method static void forgetChannel(string|null $driver = null)
 * @method static array getChannels()
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log(mixed $level, string|\Stringable $message, array $context = [])
 * @method static \Illuminate\Log\LogManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static void write(string $level, \Illuminate\Contracts\Support\Arrayable|\Illuminate\Contracts\Support\Jsonable|\Illuminate\Support\Stringable|array|string $message, array $context = [])
 * @method static \Illuminate\Log\Logger withContext(array $context = [])
 * @method static void listen(\Closure $callback)
 * @method static \Psr\Log\LoggerInterface getLogger()
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher()
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
 * @method static \Illuminate\Log\Logger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Illuminate\Log\Logger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Illuminate\Log\LogManager
 */
class Log extends Facade
{
    /**
     *  Add exception to log context
     *
     * @param \Throwable $throwable
     *
     * @return Logger
     */
    public static function withException(\Throwable $throwable): Logger
    {
        return LogFacade::withContext([
            '_exception' => [
                'message' => $throwable->getMessage(),
                'class' => get_class($throwable),
                'code' => $throwable->getCode(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
                'file' => $throwable->getFile(),
            ]
        ]);
    }

    /**
     *  get standard message for log
     *
     * @param int $layer
     *
     * @return string
     */
    public static function standardMessage(int $layer = 1): string
    {
        $file = debug_backtrace(limit: $layer + 1);
        $class = $file[$layer]['class'] ?? 'Class';
        $method = $file[$layer]['function'] ?? 'method';

        return "$class-$method";
    }


    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'log';
    }
}