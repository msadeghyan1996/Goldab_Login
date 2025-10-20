<?php

namespace App\Helper\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResCode;

class CustomResponse
{
    /**
     * Build a standardized JSON response for rate-limited requests.
     *
     * Expects limiter-provided headers array and extracts Retry-After seconds.
     *
     * @param array $headers Rate limit headers (expects 'Retry-After').
     *
     * @return JsonResponse JSON with message and retry_after seconds.
     */
    public static function rateLimit(array $headers): JsonResponse
    {
        return Response::json([
            'message' => trans('otp.limit.send'),
            'retry_after' => $headers['Retry-After'],
        ], ResCode::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Build a standardized JSON response for unexpected internal errors.
     *
     * @return JsonResponse JSON with a localized unexpected error message.
     */
    public static function internalError(): JsonResponse
    {
        return Response::json([
            'message' => trans('msg.unexpected_error'),
        ], ResCode::HTTP_INTERNAL_SERVER_ERROR);
    }
}
