<?php

namespace App\Services\Pricing;

use App\Contracts\DeliveryPricing;
use App\Services\Settings\SettingsRepository;
use App\Support\DeliveryQuote;

/** Qat'iy narx: admin "Sozlamalar"da belgilagan yetkazish va xizmat haqi. */
class FlatDeliveryPricing implements DeliveryPricing
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function quote(?float $lat, ?float $lng, int $itemsCount): DeliveryQuote
    {
        return new DeliveryQuote(
            deliveryFee: $this->settings->deliveryFee(),
            serviceFee: $this->settings->serviceFee(),
        );
    }
}
