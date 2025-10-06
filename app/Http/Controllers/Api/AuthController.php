<?php

namespace App\Http\Controllers\Api;

use App\Enums\OTP\Type;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\OTP;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller {

    /**
     * Login
     *
     * @group    auth
     * @response 422  {
     *        "success": false,
     *        "statusType": "error",
     *        "title": "خطا",
     *        "message": "Validation failed.",
     *        "errors": {
     *            "mobile": [
     *                "فیلد تلفن همراه الزامی است."
     *            ]
     *        },
     *        "notify": true
     *    }
     * @response 200  {
     *        "success": true,
     *        "statusType": "success",
     *        "title": "موفق",
     *        "message": "کد ارسال شده رو وارد فرمایید 8159",
     *        "data": {
     *            "page": "validation_code"
     *        },
     *        "notify": true
     *    }
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Random\RandomException
     */
    public function login (Request $request) : \Illuminate\Http\JsonResponse {

        $validator = Validator::make($request->all(), [
            'mobile' => 'required|max:15'
        ]);

        if ( $validator->fails() ) {
            return self::validationResponse($validator);
        }

        $mobile = $validator->validated()['mobile'];
        $mobile = Helper::normalizeMobile($mobile);
        if ( !$mobile ) {
            return self::warning([
                'message' => 'فیلد ها رو به صورت صحیح وارد فرمایید',
                'errors'  => [
                    'mobile' => 'شماره همراه صحیح نمی باشد'
                ]
            ], 422);
        }
        $number = Helper::generateCode(4);
        $user   = User::whereMobile($mobile)->first();
        if ( !$user ) {
            try {
                DB::beginTransaction();

                $createUser = User::create([
                    'mobile' => $mobile,
                ]);
                $createUser->otp()->create([
                    'code'       => $number,
                    'type'       => Type::Login->value,
                    'expires_at' => now()->addMinutes(3),
                ]);

                DB::commit();

                return self::success([
                    'message' => 'کد ارسال شده رو وارد فرمایید ' . $number,
                    'data'    => [
                        'page' => 'validation_code'
                    ],
                ]);

            } catch ( \Throwable $e ) {
                DB::rollBack();
                Log::channel('login')->info('Create user get failed', [
                    'mobile' => $mobile,
                    'msg'    => $e->getMessage(),
                    'line'   => $e->getLine(),
                ]);

                return self::error('لطفا دوباره تلاش فرمایید');
            }

        } elseif ( !$user->last_name ) {
            // if not verify mobile
            if ( !$user->hasVerifiedMobile() ) {
                /**
                 * @var OTP $otp
                 */
                $otp = $user->otp;
                if ( !$otp ) {
                    $otp = $user->otp()->create([
                        'code'       => $number,
                        'type'       => Type::Login->value,
                        'expires_at' => now()->addMinutes(3),
                    ]);
                    if ( $otp ) {
                        return self::success([
                            'message' => 'کد ارسال شده رو وارد فرمایید ' . $number,
                            'data'    => [
                                'page' => 'validation_code'
                            ],
                        ]);
                    }

                    return self::error('لطفا دوباره تلاش فرمایید');
                }

                if ( !$otp->isExpired(Type::Login) ) {
                    $secondsLeft = now()->diffInSeconds($otp->expires_at);

                    return self::warning([
                        'message' => 'منتظر کد باشیدلطفا ',
                        'errors'  => [
                            'secondsLeft' => (int) abs($secondsLeft),
                        ]
                    ]);
                }
                $otp = $user->otp()->update([
                    'code'       => $number,
                    'type'       => Type::Login->value,
                    'expires_at' => now()->addMinutes(3),
                ]);
                if ( $otp ) {
                    return self::success([
                        'message' => 'کد ارسال شده رو وارد فرمایید ' . $number,
                        'data'    => [
                            'page' => 'validation_code'
                        ],
                    ]);
                }

                return self::error('لطفا دوباره تلاش فرمایید');
            }

            return self::success([
                'message' => 'لطفا اطلاعات را کامل فرمایید',
                'data'    => [
                    'page' => 'register'
                ],
            ]);
        }

        return self::success([
            'message' => 'با موفقیت وارد شدید',
            'data'    => [
                'token' => $user->createToken('auth_token')->plainTextToken,
                'page'  => 'home'
            ]
        ]);

    }

    public function otpVerify (Request $request) {

        $validator = Validator::make($request->all(), [
            'mobile' => 'required|max:15',
            'code'   => 'required|digits:4'
        ]);

        if ( $validator->fails() ) {
            return self::validationResponse($validator);
        }
        $data   = $validator->validated();
        $mobile = $data['mobile'];
        $code   = $data['code'];
        $mobile = Helper::normalizeMobile($mobile);
        if ( !$mobile ) {
            return self::warning([
                'message' => 'فیلدها را به صورت صحیح وارد فرمایید',
                'errors'  => [
                    'mobile' => 'شماره همراه معتبر نیست'
                ]
            ], 422);
        }
        /**
         * @var User $user
         */
        $user = User::whereMobile($mobile)->first();
        if ( !$user ) {
            return self::warning('شماره تلفن صحیح نمی باشد', 404);
        }
        /** @var OTP|null $otp */
        $otp = $user->otp;
        if ( !$otp || $otp->isExpired(Type::Login) || !$otp->isValid($code) ) {
            return self::warning('کد منقضی شده است');
        }

        if ( !$user->hasVerifiedMobile() ) {
            $user->forceFill([
                'mobile_verified_at' => now(),
            ])->save();
        }
        // expire code
        $otp->setExpire();
        $otp->save();

        if ( blank($user->last_name) ) {
            return self::success([
                'message' => 'لطفا اطلاعات خود را کامل فرمایید',
                'data'    => [
                    'page' => 'register'
                ],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return self::success([
            'message' => 'با موفقیت وارد شدید',
            'data'    => [
                'token' => $token,
                'page'  => 'home',
            ],
        ]);


    }

}
