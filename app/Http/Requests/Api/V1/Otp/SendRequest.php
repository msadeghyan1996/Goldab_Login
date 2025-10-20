<?php

namespace App\Http\Requests\Api\V1\Otp;

use Illuminate\Foundation\Http\FormRequest;

class SendRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'phone:mobile']
        ];
    }
}
