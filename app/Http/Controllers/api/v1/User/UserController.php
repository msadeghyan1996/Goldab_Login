<?php

namespace App\Http\Controllers\api\v1\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorType;
use Src\User\Contracts\UserContract;

final class UserController {
    public function __construct(
        private readonly UserContract $userContract
    ) {}

    public function completeProfile(Request $request) : JsonResponse{
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'توکن معتبر نیست یا کاربر پیدا نشد.',
                'status'  => 'unauthenticated'
            ], 401);
        }

        $validated = $this->validateUpdateProfile($request);

        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors(),
            ], 422);
        }

        $result = $this->userContract->completeProfile($request->user()->id, $request->all());

        return response()->json($result, 200);

    }

    private function validateUpdateProfile($request): ValidatorType {
        return Validator::make($request->all(), [
            'user_id'     => 'required|integer|exists:users,id',
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'national_id' => 'required|string|max:10',
            'password'    => 'required|string|min:6',
        ]);
    }
}
