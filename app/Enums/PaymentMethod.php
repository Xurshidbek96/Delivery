<?php

namespace App\Enums;

/**
 * To'lov usullari. Yangi usul (click, payme, ...) qo'shish:
 *  1) shu yerga case qo'shing, 2) App\Contracts\PaymentGateway ni implement qiling,
 *  3) PaymentGatewayManager::$gateways ga ro'yxatdan o'tkazing, 4) isEnabled() ni yangilang.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Click = 'click';
    case Payme = 'payme';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Naqd (kuryerga)',
            self::Click => 'Click',
            self::Payme => 'Payme',
        };
    }

    /** Hozircha faqat naqd to'lov ishlaydi */
    public function isEnabled(): bool
    {
        return $this === self::Cash;
    }

    /** @return list<self> */
    public static function enabled(): array
    {
        return array_values(array_filter(self::cases(), fn (self $m) => $m->isEnabled()));
    }
}
