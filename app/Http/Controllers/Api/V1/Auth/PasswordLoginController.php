<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\PasswordLogin;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\OtpChannel;
use App\Domain\Auth\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordLoginRequest;
use Illuminate\Http\JsonResponse;

class PasswordLoginController extends Controller
{
    public function __construct(private readonly PasswordLogin $action) {}

    public function __invoke(PasswordLoginRequest $request): JsonResponse
    {
        $context = new OtpContext(
            mobile: (string) $request->input('mobile'),
            ip: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            channel: OtpChannel::Api,
        );

        $result = $this->action->handle($context, (string) $request->input('password'));

        if (! $result->successful || $result->token === null) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $ability = $result->token->ability;
        $status = $ability === TokenAbility::AccessApi ? 'ok' : 'pending_profile';

        return response()->json([
            'token' => $result->token->plainTextToken,
            'status' => $status,
        ]);
    }
}
