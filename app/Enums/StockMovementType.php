<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Waste = 'waste';
    case Opname = 'opname';
    case VoidReturn = 'void_return';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Pembelian',
            self::Sale => 'Penjualan',
            self::Waste => 'Terbuang',
            self::Opname => 'Stok Opname',
            self::VoidReturn => 'Pengembalian Void',
            self::Manual => 'Penyesuaian Manual',
        };
    }
}
