<?php

namespace App\Http\Requests\Api\Auth;

class CourierLoginRequest extends SendOtpRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'password' => ['required', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
