<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusNotification;

class SendOrderStatusNotifications
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing(['client', 'courier']);

        // O'zi qilgan amal haqida o'ziga xabar yubormaymiz
        if ($order->client && $event->actor?->id !== $order->client_id && ($n = OrderStatusNotification::forClient($order))) {
            $order->client->notify($n);
        }

        if ($order->courier && $event->actor?->id !== $order->courier_id && ($n = OrderStatusNotification::forCourier($order, $event->from))) {
            $order->courier->notify($n);
        }
    }
}
