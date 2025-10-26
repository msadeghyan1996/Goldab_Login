<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRegisterOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'The :attribute must be a valid E.164 number.',
            'code.regex' => 'The :attribute must be a 6-digit numeric code.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone_number' => 'phone number',
            'code' => 'verification code',
        ];
    }
}
