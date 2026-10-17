<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CheckInAttendance
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Process employee check-in with selfie and schedule validation (KF-02).
     */
    public function execute(
        User $user,
        ?string $photoBase64 = null,
        ?float $lat = null,
        ?float $lng = null,
        ?string $note = null,
        bool $bypassSchedule = false,
    ): Attendance {
        $today = now()->toDateString();

        // 1. Check if already checked in today
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($existing && $existing->isCheckedIn()) {
            throw new InvalidArgumentException('Anda sudah melakukan absensi masuk hari ini.');
        }

        // 2. Find schedule for today
        $schedule = Schedule::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (! $schedule && ! $bypassSchedule && ! $user->hasRole(['manager', 'bumk_staff'])) {
            throw new InvalidArgumentException('Anda tidak memiliki jadwal kerja hari ini.');
        }

        // 3. Validate schedule time window (tolerance ±30 mins before shift)
        $status = 'present';
        if ($schedule && ! $bypassSchedule) {
            $dateStr = $schedule->work_date instanceof \Carbon\Carbon ? $schedule->work_date->toDateString() : (string) $schedule->work_date;
            $shiftStart = Carbon::parse("{$dateStr} {$schedule->start_time}");
            $shiftEnd = Carbon::parse("{$dateStr} {$schedule->end_time}");

            // Open 30 minutes before shift
            $earlyLimit = $shiftStart->copy()->subMinutes(30);

            if (now()->lt($earlyLimit)) {
                $minsLeft = now()->diffInMinutes($earlyLimit);
                throw new InvalidArgumentException("Belum masuk rentang waktu shift. Absensi dibuka 30 menit sebelum jadwal (mulai pukul {$earlyLimit->format('H:i')}).");
            }

            if (now()->gt($shiftEnd)) {
                throw new InvalidArgumentException("Jadwal shift Anda untuk hari ini telah berakhir pada pukul {$shiftEnd->format('H:i')}.");
            }

            // Late threshold: 15 minutes after start_time
            if (now()->gt($shiftStart->copy()->addMinutes(15))) {
                $status = 'late';
            }
        }

        // 4. Save selfie photo to private storage
        $photoPath = null;
        if ($photoBase64) {
            $photoPath = $this->storeSelfie($user, $photoBase64, 'in');
        }

        // 5. Create or update attendance
        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'work_date' => $today,
            ],
            [
                'schedule_id' => $schedule?->id,
                'check_in_at' => now(),
                'status' => $status,
                'photo_in_path' => $photoPath,
                'lat' => $lat,
                'lng' => $lng,
                'note' => $note,
            ]
        );

        $this->auditService->log(
            action: 'attendance.check_in',
            subject: $attendance,
            oldValues: null,
            newValues: [
                'user_id' => $user->id,
                'status' => $status,
                'check_in_at' => now()->toDateTimeString(),
                'schedule_id' => $schedule?->id,
            ]
        );

        return $attendance;
    }

    protected function storeSelfie(User $user, string $base64, string $type): string
    {
        // Strip data:image/...;base64, if present
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }

        $imageData = base64_decode($base64);
        $filename = "attendances/{$user->id}/" . now()->format('Ymd_His') . "_{$type}.jpg";

        Storage::disk('local')->put($filename, $imageData);

        return $filename;
    }
}
