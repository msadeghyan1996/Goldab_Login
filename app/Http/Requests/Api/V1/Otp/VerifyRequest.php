<?php

namespace App\Http\Requests\Api\V1\Otp;

use App\Rules\Otp\ValidateOtpCode;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'phone:mobile'],
            'code' => [
                'bail',
                'required',
                'digits:' . config('otp.length', 6),
                new ValidateOtpCode(phone: (string) $this->input('phone')),
            ],
        ];
    }
}
