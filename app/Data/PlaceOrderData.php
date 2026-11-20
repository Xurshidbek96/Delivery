<?php

namespace App\Data;

use App\Enums\PaymentMethod;

final readonly class PlaceOrderData
{
    /**
     * @param  array{address: string, entrance?: ?string, floor?: ?string, apartment?: ?string, landmark?: ?string, lat?: ?float, lng?: ?float}  $address
     * @param  list<array{name: string, quantity: float|int, unit: string, note?: ?string}>  $items
     */
    public function __construct(
        public array $address,
        public array $items,
        public PaymentMethod $paymentMethod,
        public ?string $contactName = null,
        public ?string $contactPhone = null,
        public ?string $note = null,
    ) {}
}
