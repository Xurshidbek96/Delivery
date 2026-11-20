<?php

namespace App\Contracts;

use App\Models\User;

/** Mobil ilovaga push. Hozir: LogPushNotifier. Keyin: FCM (device_tokens jadvalidan). */
interface PushNotifier
{
    /** @param array<string, scalar|null> $data */
    public function send(User $user, string $title, string $body, array $data = []): void;
}
