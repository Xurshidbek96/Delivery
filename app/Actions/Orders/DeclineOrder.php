<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use Illuminate\Support\Facades\DB;

/** Kuryer biriktirilgan buyurtmadan voz kechadi — buyurtma moderatorga qaytadi (boshqa kuryer tanlanadi). */
class DeclineOrder
{
    use LocksOrder;

    public function __construct(private readonly OrderTransitioner $transitioner) {}

    public function handle(Order $order, User $courier, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $courier, $reason) {
            $order = $this->lock($order);
            $this->ensureAssignedTo($order, $courier);

            $order->courier_id = null;
            $order->assigned_at = null;
            $this->transitioner->transition($order, OrderStatus::Confirmed, $courier, $reason ? "Kuryer rad etdi: {$reason}" : 'Kuryer rad etdi');

            return $order;
        });
    }
}
