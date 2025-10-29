<?php

namespace App\Http\Controllers;

use App\Exceptions\Otp\OtpExpiredException;
use App\Exceptions\Otp\OtpInvalidCodeException;
use App\Exceptions\Otp\OtpRateLimitException;
use App\Exceptions\Otp\OtpTooManyAttemptsException;
use App\Exceptions\ProjectLogicException;
use App\Exceptions\Registration\InvalidRegistrationTokenException;
use App\Http\Requests\AuthAttemptRequest;
use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\LoginWithPasswordRequest;
use App\Http\Requests\RegisterOtpRequest;
use App\Http\Requests\VerifyRegisterOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function lookup(AuthAttemptRequest $request)
    {
        $data = $request->validated();
        $phoneNumber = $data['phone_number'];
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            return apiResponse()->success([
                'is_new_user' => true,
                'authentication_type' => 'otp',
            ]);
        }

        return apiResponse()->success([
            'is_new_user' => false,
            'authentication_type' => 'password',
        ]);
    }

    public function loginWithPassword(LoginWithPasswordRequest $request)
    {
        $data = $request->validated();

        $user = User::where('phone_number', $data['phone_number'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return apiResponse()->error('The provided credentials are incorrect.', 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return apiResponse()->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @throws ProjectLogicException
     * @throws OtpRateLimitException
     */
    public function requestRegistrationOtp(RegisterOtpRequest $request)
    {
        $phoneNumber = $request->input('phone_number');

        $this->validateUserDoesNotExist($phoneNumber);

        otpService()->request($phoneNumber);

        return apiResponse()->success(
            message: 'OTP has been sent to your phone.'
        );
    }

    /**
     * @throws OtpTooManyAttemptsException
     * @throws OtpExpiredException
     * @throws ProjectLogicException
     * @throws OtpInvalidCodeException
     */
    public function verifyRegistrationOtp(VerifyRegisterOtpRequest $request)
    {
        $phoneNumber = $request->input('phone_number');

        $this->validateUserDoesNotExist($phoneNumber);
        otpService()->verify($phoneNumber, $request->input('code'));

        $registrationToken = otpService()->createPendingRegistrationToken($phoneNumber);

        return apiResponse()->success(
            data: [
                'registration_token' => $registrationToken,
            ],
            message: 'Phone number verified.'
        );
    }

    /**
     * @throws ProjectLogicException
     * @throws InvalidRegistrationTokenException
     */
    public function completeRegistration(CompleteRegistrationRequest $request)
    {
        $pending = otpService()->getPendingRegistration($request->input('registration_token'));

        $phoneNumber = $pending['phone_number'];

        $this->validateUserDoesNotExist($phoneNumber);

        $user = User::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'national_id' => $request->input('national_id'),
            'phone_number' => $phoneNumber,
            'password' => $request->input('password'),
        ]);

        otpService()->forgetPendingRegistration($request->input('registration_token'));

        $token = $user->createToken('mobile')->plainTextToken;

        return apiResponse()->success(
            data: [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            code: 201
        );
    }

    public function me(Request $request)
    {
        return apiResponse()->success(UserResource::make(auth()->user()));
    }

    public function logout(Request $request)
    {
        auth()->user()->currentAccessToken()?->delete();

        return apiResponse()->success(message: 'Logged out successfully.');
    }

    /**
     * @throws ProjectLogicException
     */
    private function validateUserDoesNotExist(string $phoneNumber): void
    {
        if(!User::where('phone_number', $phoneNumber)->exists()) return;

        throw new ProjectLogicException(
            'Password authentication is required for this phone number.',
            403,
            'password_authentication_required'
        );
    }
}
