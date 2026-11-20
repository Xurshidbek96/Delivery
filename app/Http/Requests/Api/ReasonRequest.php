<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReasonRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:255']];
    }
}
