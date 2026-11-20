<?php

namespace App\Http\Requests\Api\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnlineRequest extends FormRequest
{
    public function rules(): array
    {
        return ['is_online' => ['required', 'boolean']];
    }
}
