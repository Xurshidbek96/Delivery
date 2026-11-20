<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'delivery_fee' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
