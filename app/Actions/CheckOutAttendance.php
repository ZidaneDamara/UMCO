<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CheckOutAttendance
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Process employee check-out with optional photo (KF-02).
     */
    public function execute(
        User $user,
        ?string $photoBase64 = null,
        ?string $note = null,
    ): Attendance {
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (! $attendance || ! $attendance->isCheckedIn()) {
            throw new InvalidArgumentException('Anda belum melakukan absensi masuk hari ini.');
        }

        if ($attendance->isCheckedOut()) {
            throw new InvalidArgumentException('Anda sudah melakukan absensi pulang hari ini.');
        }

        $photoPath = null;
        if ($photoBase64) {
            if (str_contains($photoBase64, ',')) {
                $photoBase64 = explode(',', $photoBase64)[1];
            }
            $imageData = base64_decode($photoBase64);
            $photoPath = "attendances/{$user->id}/" . now()->format('Ymd_His') . '_out.jpg';
            Storage::disk('local')->put($photoPath, $imageData);
        }

        $attendance->update([
            'check_out_at' => now(),
            'photo_out_path' => $photoPath,
            'note' => $note ?: $attendance->note,
        ]);

        $this->auditService->log(
            action: 'attendance.check_out',
            subject: $attendance,
            oldValues: null,
            newValues: [
                'user_id' => $user->id,
                'check_out_at' => now()->toDateTimeString(),
            ]
        );

        return $attendance;
    }
}
