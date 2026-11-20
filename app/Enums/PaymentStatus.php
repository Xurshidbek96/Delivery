<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'To\'lanmagan',
            self::Paid => 'To\'langan',
            self::Refunded => 'Qaytarilgan',
        };
    }
}
