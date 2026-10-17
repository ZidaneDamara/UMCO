<?php

namespace App\Enums;

enum DiscountKind: string
{
    case FixedPerItem = 'fixed_per_item';
    case PercentPerItem = 'percent_per_item';
    case FixedPerOrder = 'fixed_per_order';
    case Bundle = 'bundle';
    case Promo = 'promo';

    public function label(): string
    {
        return match ($this) {
            self::FixedPerItem => 'Potongan Tetap per Item',
            self::PercentPerItem => 'Persentase per Item',
            self::FixedPerOrder => 'Potongan Tetap per Transaksi',
            self::Bundle => 'Bundling',
            self::Promo => 'Promo',
        };
    }
}
