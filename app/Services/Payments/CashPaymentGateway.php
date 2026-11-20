<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;

/** Naqd: kuryer yetkazganda mijozdan pulni oladi — buyurtma "to'langan" bo'ladi. */
class CashPaymentGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Cash;
    }

    public function checkoutUrl(Order $order): ?string
    {
        return null;
    }

    public function onDelivered(Order $order): void
    {
        $order->payment_status = PaymentStatus::Paid;
    }
}
