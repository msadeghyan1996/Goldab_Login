<?php

namespace App\Http\Controllers\api\v1\Verification;

use App\Http\Controllers\Controller;
use App\Rules\ConvertFaNumToEn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Src\Verification\Contracts\VerificationContract;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class VerificationController extends Controller{
    public function __construct(
        public VerificationContract $verificationContract
    ){}

    public function create(Request $request): JsonResponse {

        $validated = $this->validateCreate($request);

        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors(),
            ], ResponseAlias::HTTP_PRECONDITION_FAILED);
        }

        $result = $this->verificationContract->create(
            $request->input('phone'),
            $request->input('password')
        );

        if (in_array($result['status'], ['error','need_password'])) {
            return response()->json($result, ResponseAlias::HTTP_PRECONDITION_FAILED);
        }

        return response()->json($result, ResponseAlias::HTTP_CREATED);
    }

    public function verify(Request $request): JsonResponse {

        $validated = $this->validateVerify($request);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()]);
        }

        $result = $this->verificationContract->verify(
            $request->input('code'),
            $request->input('phone')
        );

        if ($result['status'] === 'error') {
            return response()->json($result, ResponseAlias::HTTP_PRECONDITION_FAILED);
        }

        return response()->json($result, ResponseAlias::HTTP_OK);
    }

    private function validateCreate($request) : \Illuminate\Validation\Validator {
        return Validator::make($request->all(), [
            'phone' => [
                'required',
                'regex:/^(?:09|9)[0-9]{9}$/',
                'min:10',
                'max:11',
                new ConvertFaNumToEn
            ],
            'password' => ['nullable', 'string', 'min:4']
        ]);
    }

    private function validateVerify($request) : \Illuminate\Validation\Validator {
        return Validator::make($request->all(), [
            'phone' => ['required', 'min:11', 'max:11', new ConvertFaNumToEn],
            'code'  => ['required', 'int', new ConvertFaNumToEn()],
        ]);
    }
}
