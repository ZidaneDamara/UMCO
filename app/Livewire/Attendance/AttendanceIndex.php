<?php

namespace App\Livewire\Attendance;

use App\Actions\CheckInAttendance;
use App\Actions\CheckOutAttendance;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AttendanceIndex extends Component
{
    public string $viewTab = 'self'; // self | recap (for manager)
    public string $selectedMonth; // Y-m
    public string $selectedDateFilter = '';

    // Selfie modal state
    public bool $showCameraModal = false;
    public string $cameraAction = 'in'; // in | out
    public ?string $capturedPhotoBase64 = null;
    public ?float $userLat = null;
    public ?float $userLng = null;
    public string $attendanceNote = '';

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedDateFilter = now()->toDateString();
    }

    public function openCheckInModal(): void
    {
        $this->cameraAction = 'in';
        $this->capturedPhotoBase64 = null;
        $this->attendanceNote = '';
        $this->resetErrorBag();
        $this->showCameraModal = true;
    }

    public function openCheckOutModal(): void
    {
        $this->cameraAction = 'out';
        $this->capturedPhotoBase64 = null;
        $this->attendanceNote = '';
        $this->resetErrorBag();
        $this->showCameraModal = true;
    }

    public function processCheckIn(CheckInAttendance $checkIn): void
    {
        $user = auth()->user();

        try {
            $checkIn->execute(
                user: $user,
                photoBase64: $this->capturedPhotoBase64,
                lat: $this->userLat,
                lng: $this->userLng,
                note: $this->attendanceNote,
            );

            $this->showCameraModal = false;
            session()->flash('success', 'Absensi masuk berhasil tercatat.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('checkInError', $e->getMessage());
        }
    }

    public function processCheckOut(CheckOutAttendance $checkOut): void
    {
        $user = auth()->user();

        try {
            $checkOut->execute(
                user: $user,
                photoBase64: $this->capturedPhotoBase64,
                note: $this->attendanceNote,
            );

            $this->showCameraModal = false;
            session()->flash('success', 'Absensi pulang berhasil tercatat. Terima kasih atas kerja keras Anda hari ini!');
        } catch (\InvalidArgumentException $e) {
            $this->addError('checkInError', $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // 1. User's today schedule & attendance
        $todaySchedule = Schedule::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        // 2. User's recent attendance history (last 14 days)
        $myHistory = Attendance::with('schedule')
            ->where('user_id', $user->id)
            ->latest('work_date')
            ->limit(14)
            ->get();

        // 3. Manager Recap query (if user has permission to manage attendance)
        $recapAttendances = collect();
        if ($user->can('attendance.manage') && $this->viewTab === 'recap') {
            $query = Attendance::with(['user', 'schedule'])->latest('work_date');

            if ($this->selectedDateFilter) {
                $query->where('work_date', $this->selectedDateFilter);
            } else {
                $monthStart = Carbon::parse($this->selectedMonth . '-01')->startOfMonth()->toDateString();
                $monthEnd = Carbon::parse($this->selectedMonth . '-01')->endOfMonth()->toDateString();
                $query->whereBetween('work_date', [$monthStart, $monthEnd]);
            }

            $recapAttendances = $query->get();
        }

        return view('livewire.attendance.attendance-index', [
            'todaySchedule' => $todaySchedule,
            'todayAttendance' => $todayAttendance,
            'myHistory' => $myHistory,
            'recapAttendances' => $recapAttendances,
        ])->title('Absensi Karyawan & Selfie');
    }
}
