<?php

namespace App\Http\Requests\Api\Client;

use App\Data\PlaceOrderData;
use App\Enums\ItemUnit;
use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Address;
use App\Models\Product;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Narx maydonlari qabul qilinmaydi: yetkazish narxi serverda, mahsulot narxini kuryer xariddan keyin kiritadi.
 * Manzil: saqlangan manzil (address_id, faqat o'ziniki) yoki to'g'ridan-to'g'ri maydonlar.
 * Mahsulot: erkin matn (name + unit) yoki katalogdan (product_id — nom va birlik standart mahsulotdan olinadi).
 */
class StoreOrderRequest extends FormRequest
{
    use NormalizesPhone;

    /** @var Collection<int, Product>|null */
    private ?Collection $catalog = null;

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
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name' => ['required_without:items.*.product_id', 'nullable', 'string', 'min:2', 'max:150'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'items.*.unit' => ['required_without:items.*.product_id', 'nullable', Rule::enum(ItemUnit::class)],
            'items.*.note' => ['nullable', 'string', 'max:255'],

            'payment_method' => ['required', Rule::in($enabledMethods)],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', $this->phoneRule()],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Katalog mahsuloti mavjud va mijozga ko'rinadigan (faol, do'koni faol) bo'lishi shart. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $products = $this->catalogProducts();
            foreach ((array) $this->input('items', []) as $i => $item) {
                $id = $item['product_id'] ?? null;
                if ($id !== null && ! $products->has((int) $id)) {
                    $validator->errors()->add("items.{$i}.product_id", 'Bu mahsulot hozir mavjud emas.');
                }
            }
        }];
    }

    /** @return Collection<int, Product> */
    private function catalogProducts(): Collection
    {
        return $this->catalog ??= Product::query()
            ->visible()
            ->with('shop')
            ->whereKey(collect((array) $this->input('items', []))->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique()->all())
            ->get()
            ->keyBy('id');
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
            items: array_map(function (array $i) {
                $product = isset($i['product_id']) ? $this->catalogProducts()->get((int) $i['product_id']) : null;

                return [
                    'product_id' => $product?->id,
                    'shop_name' => $product?->shop->name,
                    'name' => trim($i['name'] ?? '') ?: $product->name,
                    'quantity' => (float) $i['quantity'],
                    'unit' => $i['unit'] ?? $product->unit->value,
                    'note' => $i['note'] ?? null,
                ];
            }, $this->validated('items')),
            paymentMethod: PaymentMethod::from($this->validated('payment_method')),
            contactName: $this->validated('contact_name'),
            contactPhone: $this->validated('contact_phone'),
            note: $this->validated('note'),
        );
    }
}
