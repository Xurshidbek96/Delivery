<?php

namespace App\Services\Orders;

use App\Enums\OrderItemStatus;
use App\Models\Order;

/** Buyurtma summalarini qayta hisoblash. Narxlar faqat serverda hisoblanadi. */
class OrderTotals
{
    public function recalculate(Order $order): void
    {
        $order->items_total = (int) $order->items()
            ->where('status', OrderItemStatus::Purchased->value)
            ->sum('price');

        $order->total = $order->items_total + $order->delivery_fee + $order->service_fee;
    }
}
