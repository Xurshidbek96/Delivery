<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhones(['phone']);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', $this->phoneRule(), Rule::unique('users', 'phone')->ignore($staff?->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::staff()))],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['phone.unique' => 'Bu raqam bilan foydalanuvchi allaqachon bor.', 'phone.regex' => 'Telefon raqamni to\'liq kiriting.'];
    }
}
