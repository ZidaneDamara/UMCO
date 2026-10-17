<?php

namespace App\Enums;

enum MenuType: string
{
    case Drink = 'drink';
    case Food = 'food';

    public function label(): string
    {
        return match ($this) {
            self::Drink => 'Minuman',
            self::Food => 'Makanan',
        };
    }

    public function station(): StationType
    {
        return match ($this) {
            self::Drink => StationType::Bar,
            self::Food => StationType::Kitchen,
        };
    }
}
