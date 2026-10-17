<?php

namespace App\Enums;

enum OrderSource: string
{
    case Pos = 'pos';
    case SelfService = 'self_service';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pos => 'POS',
            self::SelfService => 'Self-Service',
            self::Delivery => 'Pesan Antar',
        };
    }
}
