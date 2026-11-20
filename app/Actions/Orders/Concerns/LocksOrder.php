<?php

namespace App\Actions\Orders\Concerns;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;

trait LocksOrder
{
    /** Parallel so'rovlar (ikki moderator, kuryer + moderator) bir-birini buzmasligi uchun. */
    protected function lock(Order $order): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($order->getKey());
    }

    protected function ensureAssignedTo(Order $order, User $courier): void
    {
        if (! $order->isAssignedTo($courier)) {
            throw new BusinessRuleException('Bu buyurtma sizga biriktirilmagan.');
        }
    }
}
