<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCourierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'courier_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Courier->value)->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return ['courier_id.required' => 'Kuryerni tanlang.', 'courier_id.exists' => 'Tanlangan kuryer topilmadi yoki faol emas.'];
    }
}
