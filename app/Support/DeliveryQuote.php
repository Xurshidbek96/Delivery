<?php

namespace App\Support;

final readonly class DeliveryQuote
{
    public function __construct(
        public int $deliveryFee,
        public int $serviceFee,
    ) {}
}
