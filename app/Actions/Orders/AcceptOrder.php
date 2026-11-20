<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use Illuminate\Support\Facades\DB;

/** Kuryer biriktirilgan buyurtmani qabul qiladi va bozorlikni boshlaydi. */
class AcceptOrder
{
    use LocksOrder;

    public function __construct(private readonly OrderTransitioner $transitioner) {}

    public function handle(Order $order, User $courier): Order
    {
        return DB::transaction(function () use ($order, $courier) {
            $order = $this->lock($order);
            $this->ensureAssignedTo($order, $courier);

            $this->transitioner->transition($order, OrderStatus::Shopping, $courier);

            return $order;
        });
    }
}
