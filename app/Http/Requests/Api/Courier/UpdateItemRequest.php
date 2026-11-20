<?php

namespace App\Http\Requests\Api\Courier;

use App\Enums\OrderItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([OrderItemStatus::Purchased->value, OrderItemStatus::Unavailable->value])],
            'price' => ['required_if:status,purchased', 'nullable', 'integer', 'min:1', 'max:100000000'],
            'courier_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['price.required_if' => 'Olingan mahsulot narxini kiriting.'];
    }
}
