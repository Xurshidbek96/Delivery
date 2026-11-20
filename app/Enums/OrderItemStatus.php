<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Pending = 'pending';         // Hali olinmagan
    case Purchased = 'purchased';     // Sotib olindi (narxi kiritilgan)
    case Unavailable = 'unavailable'; // Topilmadi

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Kutilmoqda',
            self::Purchased => 'Olindi',
            self::Unavailable => 'Topilmadi',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Purchased => 'success',
            self::Unavailable => 'danger',
        };
    }
}
