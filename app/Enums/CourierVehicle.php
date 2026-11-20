<?php

namespace App\Enums;

enum CourierVehicle: string
{
    case Foot = 'foot';
    case Bicycle = 'bicycle';
    case Scooter = 'scooter';
    case Car = 'car';

    public function label(): string
    {
        return match ($this) {
            self::Foot => 'Piyoda',
            self::Bicycle => 'Velosiped',
            self::Scooter => 'Skuter / moto',
            self::Car => 'Avtomobil',
        };
    }
}
