<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Order;

/**
 * To'lov usuli drayveri. Naqd to'lov — CashPaymentGateway.
 * Onlayn to'lov (Click/Payme) qo'shilganda: checkoutUrl() to'lov sahifasini qaytaradi,
 * webhook alohida controllerda qabul qilinib, buyurtma payment_status'i yangilanadi.
 */
interface PaymentGateway
{
    public function method(): PaymentMethod;

    /** Mijoz to'lovga yo'naltiriladigan URL (naqd to'lovda null) */
    public function checkoutUrl(Order $order): ?string;

    /** Buyurtma yetkazilganda chaqiriladi (naqdda — "to'landi" deb belgilash) */
    public function onDelivered(Order $order): void;
}
