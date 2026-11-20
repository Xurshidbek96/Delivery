<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

/** Kuryer mahsulotlarni topshirdi (naqdda — pulni oldi). */
class DeliverOrder
{
    use LocksOrder;

    public function __construct(
        private readonly OrderTransitioner $transitioner,
        private readonly PaymentGatewayManager $payments,
    ) {}

    public function handle(Order $order, User $courier): Order
    {
        return DB::transaction(function () use ($order, $courier) {
            $order = $this->lock($order);
            $this->ensureAssignedTo($order, $courier);

            $this->payments->for($order->payment_method)->onDelivered($order);
            $this->transitioner->transition($order, OrderStatus::Delivered, $courier);

            return $order;
        });
    }
}
