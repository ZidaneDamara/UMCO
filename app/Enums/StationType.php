<?php

namespace App\Enums;

enum StationType: string
{
    case Bar = 'bar';
    case Kitchen = 'kitchen';

    public function label(): string
    {
        return match ($this) {
            self::Bar => 'Bar',
            self::Kitchen => 'Dapur',
        };
    }
}
