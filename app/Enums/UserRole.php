<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Courier = 'courier';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Mijoz',
            self::Courier => 'Kuryer',
            self::Moderator => 'Moderator',
            self::Admin => 'Administrator',
        };
    }

    /** Admin panelga kira oladigan rollar */
    public function isStaff(): bool
    {
        return in_array($this, [self::Moderator, self::Admin], true);
    }

    /** @return list<self> */
    public static function staff(): array
    {
        return [self::Moderator, self::Admin];
    }
}
