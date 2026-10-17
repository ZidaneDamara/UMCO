<?php

namespace App\Livewire\Schedule;

use App\Actions\CreateSchedule;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ScheduleIndex extends Component
{
    public string $weekStart; // Y-m-d (Monday)
    public bool $showAssignModal = false;
    public ?int $selectedUserId = null;
    public string $selectedDate = '';
    public string $selectedSlot = 'shift1';
    public string $scheduleNotes = '';

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->selectedDate = now()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function openAssignModal(string $date, string $slot): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = $slot;
        $this->selectedUserId = User::where('is_active', true)->first()?->id;
        $this->scheduleNotes = '';
        $this->resetErrorBag();
        $this->showAssignModal = true;
    }

    public function assignSchedule(CreateSchedule $createSchedule): void
    {
        $this->validate([
            'selectedUserId' => ['required', 'exists:users,id'],
            'selectedDate' => ['required', 'date'],
            'selectedSlot' => ['required', 'in:shift1,shift2,kitchen'],
        ]);

        $user = User::findOrFail($this->selectedUserId);

        $createSchedule->execute(
            user: $user,
            workDate: $this->selectedDate,
            slot: $this->selectedSlot,
            notes: $this->scheduleNotes,
        );

        $this->showAssignModal = false;
        session()->flash('success', "Jadwal untuk {$user->name} berhasil ditambahkan.");
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $name = $schedule->user->name;
        $schedule->delete();

        session()->flash('success', "Penugasan jadwal untuk {$name} telah dihapus.");
    }

    public function render()
    {
        $start = Carbon::parse($this->weekStart);
        $days = [];
        for ($i = 0; $i < 6; $i++) { // Senin - Sabtu (Minggu tutup)
            $dayDate = $start->copy()->addDays($i);
            $days[] = [
                'date' => $dayDate->toDateString(),
                'day_name' => match ($dayDate->dayOfWeek) {
                    1 => 'Senin',
                    2 => 'Selasa',
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    6 => 'Sabtu',
                    default => $dayDate->format('l'),
                },
                'formatted' => $dayDate->format('d M'),
                'is_today' => $dayDate->isToday(),
            ];
        }

        $endDate = $start->copy()->addDays(5)->toDateString();

        $schedules = Schedule::with('user')
            ->whereBetween('work_date', [$this->weekStart, $endDate])
            ->get();

        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('livewire.schedule.schedule-index', [
            'days' => $days,
            'schedules' => $schedules,
            'users' => $users,
        ])->title('Jadwal Kerja Karyawan');
    }
}
