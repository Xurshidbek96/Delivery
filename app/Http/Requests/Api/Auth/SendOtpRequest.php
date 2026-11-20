<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhones(['phone']);
    }

    public function rules(): array
    {
        return ['phone' => ['required', 'string', $this->phoneRule()]];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Telefon raqam +998XXXXXXXXX ko\'rinishida bo\'lsin.'];
    }
}
