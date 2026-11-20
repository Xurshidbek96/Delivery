<?php

namespace App\Services\Push;

use App\Contracts\PushNotifier;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/** Test rejimi: push yuborilmaydi, logga yoziladi. FCM ulanganda FcmPushNotifier bilan almashtiriladi. */
class LogPushNotifier implements PushNotifier
{
    public function send(User $user, string $title, string $body, array $data = []): void
    {
        Log::info("[PUSH] user#{$user->id}: {$title} — {$body}", $data);
    }
}
