<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Tranzaksiya commit bo'lgandan keyin chiqadi — bildirishnoma, keyinchalik websocket/analitika. */
class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly ?User $actor = null,
    ) {}
}
