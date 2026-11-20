<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTotals;
use App\Services\Orders\OrderTransitioner;
use Illuminate\Support\Facades\DB;

/** Moderator buyurtmani tekshirib tasdiqlaydi (kerak bo'lsa yetkazish narxini to'g'rilaydi). */
class ConfirmOrder
{
    use LocksOrder;

    public function __construct(
        private readonly OrderTransitioner $transitioner,
        private readonly OrderTotals $totals,
    ) {}

    public function handle(Order $order, User $moderator, ?int $deliveryFee = null, ?string $staffNote = null): Order
    {
        return DB::transaction(function () use ($order, $moderator, $deliveryFee, $staffNote) {
            $order = $this->lock($order);

            if ($deliveryFee !== null) {
                $order->delivery_fee = $deliveryFee;
                $this->totals->recalculate($order);
            }
            if ($staffNote !== null) {
                $order->staff_note = $staffNote;
            }
            $order->confirmed_by = $moderator->id;

            $this->transitioner->transition($order, OrderStatus::Confirmed, $moderator);

            return $order;
        });
    }
}
