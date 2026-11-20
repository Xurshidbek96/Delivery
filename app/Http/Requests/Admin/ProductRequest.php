<?php

namespace App\Http\Requests\Admin;

use App\Enums\ItemUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'sort_order' => $this->input('sort_order') ?? 0,
            // "12 000" ko'rinishida kiritilsa ham
            'price' => filled($this->input('price')) ? preg_replace('/\D+/', '', (string) $this->input('price')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit' => ['required', Rule::enum(ItemUnit::class)],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function productData(): array
    {
        return $this->safe()->except(['image', 'remove_image']);
    }
}
