<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'ok',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'national_id' => $user->national_id,
                    'mobile' => $user->mobile,
                    'email' => $user->email,
                    'has_completed_profile' => $user->hasCompletedProfile(),
                ],
            ],
        ]);
    }
}
