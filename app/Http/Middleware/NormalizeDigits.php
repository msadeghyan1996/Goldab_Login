<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Services\DigitNormalizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeDigits
{
    /**
     * @var list<string>
     */
    private const FIELDS = ['mobile', 'national_id', 'otp', 'code'];

    public function __construct(private readonly DigitNormalizer $normalizer) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::FIELDS as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (is_string($value)) {
                $request->merge([
                    $field => $this->normalizer->normalize($value),
                ]);
            }
        }

        return $next($request);
    }
}
