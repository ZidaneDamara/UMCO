<?php

namespace App\Actions;

use App\Models\Schedule;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use InvalidArgumentException;

class CreateSchedule
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Create or update employee shift schedule (KF-08, KF-09).
     */
    public function execute(
        User $user,
        string $workDate,
        string $slot,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null,
    ): Schedule {
        $allowedSlots = ['shift1', 'shift2', 'kitchen'];
        if (! in_array($slot, $allowedSlots)) {
            throw new InvalidArgumentException("Slot shift '{$slot}' tidak valid. Pilihan: shift1, shift2, kitchen.");
        }

        // Default standard slot times per DESIGN.md Section 1
        if (! $startTime || ! $endTime) {
            [$startTime, $endTime] = match ($slot) {
                'shift1' => ['08:00:00', '15:00:00'],
                'shift2' => ['15:00:00', '22:00:00'],
                'kitchen' => ['09:00:00', '17:00:00'],
            };
        }

        $date = Carbon::parse($workDate)->toDateString();

        $schedule = Schedule::updateOrCreate(
            [
                'user_id' => $user->id,
                'work_date' => $date,
                'slot' => $slot,
            ],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $notes,
            ]
        );

        $this->auditService->log(
            action: 'schedules.create',
            subject: $schedule,
            oldValues: null,
            newValues: [
                'user_id' => $user->id,
                'work_date' => $date,
                'slot' => $slot,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]
        );

        return $schedule;
    }
}
