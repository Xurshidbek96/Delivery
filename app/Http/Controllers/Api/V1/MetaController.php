<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemUnit;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\JsonResponse;

/** Ilovalar ishga tushganda oladigan sozlamalar va lug'atlar (avtorizatsiyasiz). */
class MetaController extends ApiController
{
    public function __invoke(SettingsRepository $settings): JsonResponse
    {
        $options = fn (array $cases) => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);

        return response()->json([
            'accepting_orders' => $settings->acceptingOrders(),
            'delivery_fee' => $settings->deliveryFee(),
            'service_fee' => $settings->serviceFee(),
            'max_items_per_order' => $settings->maxItemsPerOrder(),
            'support_phone' => $settings->supportPhone(),
            'units' => $options(ItemUnit::cases()),
            'payment_methods' => $options(PaymentMethod::enabled()),
            'order_statuses' => $options(OrderStatus::cases()),
        ]);
    }
}
