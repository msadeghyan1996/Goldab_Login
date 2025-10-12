<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\CompleteProfile;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\OtpChannel;
use App\Domain\Auth\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CompleteProfileController extends Controller
{
    public function __construct(private readonly CompleteProfile $action) {}

    public function __invoke(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $context = new OtpContext(
            mobile: (string) $user->mobile,
            ip: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            channel: OtpChannel::Api,
            userId: $user->id,
        );

        try {
            $result = $this->action->handle(
                $user,
                $context,
                (string) $request->input('first_name'),
                (string) $request->input('last_name'),
                (string) $request->input('national_id'),
                $request->input('password'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'The provided details could not be saved.',
            ], 422);
        }

        $ability = $result->ability;
        $status = $ability === TokenAbility::AccessApi ? 'ok' : 'pending_profile';

        return response()->json([
            'token' => $result->plainTextToken,
            'status' => $status,
            'user' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'national_id' => $user->national_id,
                'mobile' => $user->mobile,
            ],
        ]);
    }
}
