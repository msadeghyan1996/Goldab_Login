<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResCode;

class EnsureUserVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_verification_completed) {
            return Response::json([
                'message' => trans('otp.complete_verification_first'),
                'next_step' => 'verification',
            ], ResCode::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}


