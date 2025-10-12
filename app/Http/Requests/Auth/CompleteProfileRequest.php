<?php

namespace App\Http\Requests\Auth;

use App\Rules\NationalId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompleteProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'first_name'  => ['required', 'string', 'max:120'],
            'last_name'   => ['required', 'string', 'max:120'],
            'national_id' => [
                'required',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('users', 'national_id')->ignore($user?->id),
                new NationalId,
            ],
            'password'    => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->when(
                        app()->isProduction(),
                        fn ($password) => $password->uncompromised()
                    )

            ],
        ];
    }
}
