<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StaffNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return ['staff_note' => ['nullable', 'string', 'max:1000']];
    }
}
