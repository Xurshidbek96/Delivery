<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:255']];
    }

    public function messages(): array
    {
        return ['reason.required' => 'Bekor qilish sababini yozing — mijozga yuboriladi.'];
    }
}
