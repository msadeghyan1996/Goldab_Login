<?php

namespace App\Http\Controllers\Api\V1\Verification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Verification\VerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class VerificationController extends Controller
{
    /**
     * Complete user verification by updating profile fields.
     *
     * Applies validated `national_id`, `first_name`, and `last_name` to the
     * authenticated user, then returns a JSON response indicating the next step.
     *
     * @param VerificationRequest $request Validated request payload.
     *
     * @return JsonResponse JSON with `next_step` and a localized success message.
     */
    public function verification(VerificationRequest $request): JsonResponse
    {
        Auth::user()->update($request->validated());

        return Response::json([
            'next_step' => 'panel',
            'message' => trans('otp.verification_completed')
        ]);
    }
}
