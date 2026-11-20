<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['accepting_orders' => $this->boolean('accepting_orders')]);
    }

    public function rules(): array
    {
        return [
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'service_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'courier_max_active_orders' => ['required', 'integer', 'min:1', 'max:20'],
            'max_items_per_order' => ['required', 'integer', 'min:1', 'max:200'],
            'support_phone' => ['required', 'string', 'max:30'],
            'accepting_orders' => ['boolean'],
        ];
    }
}
