<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Channels\PushChannel;
use Illuminate\Notifications\Notification;

/**
 * Mijoz va kuryerga buyurtma holati haqida xabar: ilova ichida (database) va push.
 * FCM ulanganda `implements ShouldQueue` qo'shing (navbat ishchisi kerak bo'ladi).
 */
class OrderStatusNotification extends Notification
{
    public function __construct(
        public readonly Order $order,
        public readonly string $title,
        public readonly string $body,
    ) {}

    /** @return list<string|class-string> */
    public function via(User $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'status' => $this->order->status->value,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    /** @return array{title: string, body: string, data: array<string, scalar|null>} */
    public function toPush(User $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => ['order_id' => $this->order->id, 'status' => $this->order->status->value],
        ];
    }

    public static function forClient(Order $order): ?self
    {
        $n = $order->number;

        return match ($order->status) {
            OrderStatus::Confirmed => new self($order, 'Buyurtma tasdiqlandi', "{$n} buyurtmangiz qabul qilindi. Tez orada kuryer biriktiriladi."),
            OrderStatus::Shopping => new self($order, 'Kuryer bozorlikda', "{$n}: kuryer mahsulotlaringizni xarid qilmoqda."),
            OrderStatus::Delivering => new self($order, 'Buyurtma yo\'lda', "{$n}: kuryer sizga yo'l oldi. Jami: ".number_format($order->total, 0, '.', ' ').' so\'m.'),
            OrderStatus::Delivered => new self($order, 'Yetkazildi', "{$n} buyurtmangiz yetkazildi. Xaridingiz uchun rahmat!"),
            OrderStatus::Cancelled => new self($order, 'Buyurtma bekor qilindi', "{$n}: ".($order->cancel_reason ?: 'bekor qilindi.')),
            default => null,
        };
    }

    public static function forCourier(Order $order, ?OrderStatus $from): ?self
    {
        $n = $order->number;

        return match (true) {
            $order->status === OrderStatus::Assigned => new self($order, 'Yangi buyurtma', "{$n} sizga biriktirildi: {$order->address}"),
            $order->status === OrderStatus::Cancelled && $from?->isActiveForCourier() => new self($order, 'Buyurtma bekor qilindi', "{$n} bekor qilindi."),
            default => null,
        };
    }
}
