<?php

namespace App\Http\Requests\Api\Auth;

class VerifyOtpRequest extends SendOtpRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'code' => ['required', 'string', 'digits_between:4,6'],
            'name' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
