<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhones(['phone']);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', $this->phoneRule()],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Telefon raqamni to\'liq kiriting.'];
    }
}
