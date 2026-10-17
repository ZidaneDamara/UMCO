<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Shift;
use App\Services\AuditService;
use InvalidArgumentException;

class CloseShift
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function execute(Shift $shift, int $countedCash, int $nextDayCash = 0): Shift
    {
        if (! $shift->isOpen()) {
            throw new InvalidArgumentException('Shift ini sudah ditutup.');
        }

        $orderIds = $shift->orders()->pluck('id');
        $cashSales = (int) Payment::whereIn('order_id', $orderIds)
            ->where('method', PaymentMethod::Cash)
            ->sum('amount');

        $totalExpenses = (int) $shift->expenses()->sum('amount');
        $expectedCash = $shift->opening_cash + $cashSales - $totalExpenses;
        $difference = $countedCash - $expectedCash;

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expectedCash,
            'counted_cash' => $countedCash,
            'difference' => $difference,
            'next_day_cash' => $nextDayCash,
        ]);

        $this->auditService->log(
            action: 'shifts.close',
            subject: $shift,
            oldValues: null,
            newValues: [
                'expected_cash' => $expectedCash,
                'counted_cash' => $countedCash,
                'difference' => $difference,
                'next_day_cash' => $nextDayCash,
            ]
        );

        return $shift;
    }
}
