<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Menunggu Konfirmasi',
            self::Confirmed => 'Dikonfirmasi',
            self::Preparing => 'Diproses',
            self::Ready => 'Siap',
            self::Completed => 'Selesai',
            self::Void => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'warning',
            self::Confirmed => 'info',
            self::Preparing => 'info',
            self::Ready => 'success',
            self::Completed => 'muted',
            self::Void => 'danger',
        };
    }
}
