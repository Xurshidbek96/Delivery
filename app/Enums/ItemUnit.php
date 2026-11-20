<?php

namespace App\Enums;

enum ItemUnit: string
{
    case Piece = 'pcs';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Pack = 'pack';
    case Bunch = 'bunch';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'dona',
            self::Kilogram => 'kg',
            self::Gram => 'gramm',
            self::Liter => 'litr',
            self::Pack => 'qadoq',
            self::Bunch => 'bog\'',
        };
    }
}
