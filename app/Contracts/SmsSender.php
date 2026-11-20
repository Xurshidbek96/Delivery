<?php

namespace App\Contracts;

/** SMS yuborish. Hozir: LogSmsSender (test rejimi). Keyin: Eskiz/PlayMobile drayveri. */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
