<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Generate order number with format UMC-YYMMDD-0001 (daily reset).
     * Uses DB transaction with lock to prevent duplicates.
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $today = now()->format('ymd');
            $prefix = "UMC-{$today}-";

            // Lock the orders table to prevent race conditions
            $lastOrder = DB::table('orders')
                ->where('number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            if ($lastOrder) {
                $lastSequence = (int) substr($lastOrder, -4);
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
