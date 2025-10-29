<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string', 'size:40'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_id' => ['required', 'string', 'regex:/^\d{6,20}$/'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.regex' => 'The :attribute must contain only digits.',
        ];
    }

    public function attributes(): array
    {
        return [
            'registration_token' => 'registration token',
            'national_id' => 'national ID',
        ];
    }
}
