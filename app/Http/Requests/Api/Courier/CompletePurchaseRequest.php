<?php

namespace App\Http\Requests\Api\Courier;

use Illuminate\Foundation\Http\FormRequest;

class CompletePurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        return ['receipt' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']];
    }
}
