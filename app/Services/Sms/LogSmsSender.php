<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/** Test rejimi: SMS yuborilmaydi, storage/logs/laravel.log ga yoziladi. */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('[SMS] '.$phone.': '.$message);
    }
}
