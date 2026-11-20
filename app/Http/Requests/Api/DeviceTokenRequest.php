<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'in:android,ios'],
        ];
    }
}
