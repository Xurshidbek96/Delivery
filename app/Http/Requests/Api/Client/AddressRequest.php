<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ];
    }
}
