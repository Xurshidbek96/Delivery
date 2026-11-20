<?php

namespace App\Notifications\Channels;

use App\Contracts\PushNotifier;
use App\Models\User;
use Illuminate\Notifications\Notification;

/** Laravel notification kanali → PushNotifier (hozir log, keyin FCM). */
class PushChannel
{
    public function __construct(private readonly PushNotifier $push) {}

    public function send(User $notifiable, Notification $notification): void
    {
        /** @var array{title: string, body: string, data?: array<string, scalar|null>} $message */
        $message = $notification->toPush($notifiable);

        $this->push->send($notifiable, $message['title'], $message['body'], $message['data'] ?? []);
    }
}
