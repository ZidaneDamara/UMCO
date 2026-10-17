<?php

namespace App\Actions;

use App\Models\Shift;
use App\Models\User;
use App\Services\AuditService;
use InvalidArgumentException;

class OpenShift
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function execute(User $user, int $openingCash = 0): Shift
    {
        if ($user->activeShift()) {
            throw new InvalidArgumentException('Anda masih memiliki shift yang belum ditutup.');
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
        ]);

        $this->auditService->log(
            action: 'shifts.open',
            subject: $shift,
            oldValues: null,
            newValues: [
                'user_id' => $user->id,
                'opening_cash' => $openingCash,
            ]
        );

        return $shift;
    }
}
