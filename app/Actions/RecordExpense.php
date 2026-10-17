<?php

namespace App\Actions;

use App\Models\Expense;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditService;
use InvalidArgumentException;

class RecordExpense
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function execute(
        Shift $shift,
        User $user,
        string $category,
        int $amount,
        ?string $note = null,
    ): Expense {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pengeluaran harus lebih besar dari 0.');
        }

        $expense = Expense::create([
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'category' => $category,
            'amount' => $amount,
            'note' => $note,
        ]);

        $this->auditService->log(
            action: 'expenses.create',
            subject: $expense,
            oldValues: null,
            newValues: [
                'shift_id' => $shift->id,
                'category' => $category,
                'amount' => $amount,
                'note' => $note,
            ]
        );

        return $expense;
    }
}
