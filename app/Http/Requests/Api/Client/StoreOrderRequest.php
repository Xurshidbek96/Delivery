<?php

namespace App\Http\Requests\Api\Client;

use App\Data\PlaceOrderData;
use App\Enums\ItemUnit;
use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Address;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Narx maydonlari qabul qilinmaydi: yetkazish narxi serverda, mahsulot narxini kuryer xariddan keyin kiritadi.
 * Manzil: saqlangan manzil (address_id, faqat o'ziniki) yoki to'g'ridan-to'g'ri maydonlar.
 */
class StoreOrderRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhones(['contact_phone']);
        $this->mergeIfMissing(['payment_method' => PaymentMethod::Cash->value]);
    }

    public function rules(): array
    {
        $maxItems = app(SettingsRepository::class)->maxItemsPerOrder();
        $enabledMethods = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::enabled());

        return [
            'address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $this->user()?->id)],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],

            'items' => ['required', 'array', 'min:1', "max:{$maxItems}"],
            'items.*.name' => ['required', 'string', 'min:2', 'max:150'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'items.*.unit' => ['required', Rule::enum(ItemUnit::class)],
            'items.*.note' => ['nullable', 'string', 'max:255'],

            'payment_method' => ['required', Rule::in($enabledMethods)],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', $this->phoneRule()],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Kamida bitta mahsulot qo\'shing.',
            'items.max' => 'Mahsulotlar soni juda ko\'p.',
            'address.required_without' => 'Manzilni kiriting yoki saqlangan manzilni tanlang.',
            'payment_method.in' => 'Bu to\'lov usuli hozircha ishlamaydi.',
        ];
    }

    public function toData(): PlaceOrderData
    {
        $address = $this->filled('address_id')
            ? Address::query()->findOrFail($this->integer('address_id'))->toOrderAttributes()
            : $this->safe()->only(['address', 'entrance', 'floor', 'apartment', 'landmark', 'lat', 'lng']);

        return new PlaceOrderData(
            address: $address,
            items: array_map(fn (array $i) => [
                'name' => trim($i['name']),
                'quantity' => (float) $i['quantity'],
                'unit' => $i['unit'],
                'note' => $i['note'] ?? null,
            ], $this->validated('items')),
            paymentMethod: PaymentMethod::from($this->validated('payment_method')),
            contactName: $this->validated('contact_name'),
            contactPhone: $this->validated('contact_phone'),
            note: $this->validated('note'),
        );
    }
}
