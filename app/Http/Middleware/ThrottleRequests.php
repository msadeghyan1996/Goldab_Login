<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
class ThrottleRequests
{
    protected $limiter;
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }
        $this->limiter->hit($key, $decayMinutes * 60);
        $response = $next($request);
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1($request->method() . '|' . $request->server('SERVER_NAME') . '|' . $request->path() . '|' . $request->ip());
    }
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }
    protected function addHeaders($response, int $maxAttempts, int $remainingAttempts)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
        return $response;
    }
}