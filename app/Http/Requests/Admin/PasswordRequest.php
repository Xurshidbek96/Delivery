<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return ['current_password.current_password' => 'Joriy parol noto\'g\'ri.'];
    }
}
