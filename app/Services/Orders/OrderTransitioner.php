<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;

/**
 * Buyurtma holatini o'zgartirishning YAGONA joyi: graf tekshiruvi, vaqt belgilari,
 * tarix yozuvi va event. Faqat tranzaksiya ichida, lockForUpdate qilingan buyurtma bilan chaqiring.
 */
class OrderTransitioner
{
    private const TIMESTAMPS = [
        'confirmed' => 'confirmed_at',
        'assigned' => 'assigned_at',
        'shopping' => 'shopping_started_at',
        'delivering' => 'delivering_at',
        'delivered' => 'delivered_at',
        'cancelled' => 'cancelled_at',
    ];

    public function transition(Order $order, OrderStatus $to, ?User $actor, ?string $comment = null): void
    {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw new BusinessRuleException(
                "Buyurtmani «{$from->label()}» holatidan «{$to->label()}» holatiga o'tkazib bo'lmaydi."
            );
        }

        $order->status = $to;
        if ($column = self::TIMESTAMPS[$to->value] ?? null) {
            $order->{$column} = now();
        }
        $order->save();

        $this->record($order, $from, $to, $actor, $comment);

        OrderStatusChanged::dispatch($order, $from, $to, $actor);
    }

    /** Holat o'zgarmaydigan, lekin tarixda ko'rinishi kerak bo'lgan voqealar (yaratish, kuryer almashtirish). */
    public function record(Order $order, ?OrderStatus $from, OrderStatus $to, ?User $actor, ?string $comment = null): void
    {
        $order->histories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'comment' => $comment,
        ]);
    }
}
