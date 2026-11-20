<?php

namespace App\Support;

final class Money
{
    public static function format(?int $amount, bool $withCurrency = true): string
    {
        $formatted = number_format((int) $amount, 0, '.', ' ');

        return $withCurrency ? "{$formatted} so'm" : $formatted;
    }
}
