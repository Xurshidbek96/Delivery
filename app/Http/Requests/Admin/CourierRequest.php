<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourierVehicle;
use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourierRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhones(['phone']);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $courier = $this->route('courier');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', $this->phoneRule(), Rule::unique('users', 'phone')->ignore($courier?->id)],
            'password' => [$courier ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'vehicle' => ['required', Rule::enum(CourierVehicle::class)],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['phone.unique' => 'Bu raqam bilan foydalanuvchi allaqachon bor.', 'phone.regex' => 'Telefon raqamni to\'liq kiriting.'];
    }
}
