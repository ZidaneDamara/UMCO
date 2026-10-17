<?php

use App\Actions\CheckInAttendance;
use App\Actions\CheckOutAttendance;
use App\Actions\CreateSchedule;
use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Schedule\ScheduleIndex;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('local');

    $this->manager = User::where('username', 'manager')->first();
    $this->barista = User::where('username', 'barista')->first();
    $this->kitchen = User::where('username', 'kitchen')->first();
});

test('manager can assign weekly schedule for employees', function () {
    $this->actingAs($this->manager);
    $createSchedule = app(CreateSchedule::class);

    $schedule = $createSchedule->execute(
        user: $this->barista,
        workDate: now()->toDateString(),
        slot: 'shift1',
        notes: 'Barista pembuka',
    );

    expect($schedule->user_id)->toBe($this->barista->id)
        ->and($schedule->slot)->toBe('shift1')
        ->and($schedule->start_time)->toBe('08:00:00')
        ->and($schedule->end_time)->toBe('15:00:00')
        ->and(Schedule::count())->toBe(1);
});

test('employee check-in succeeds with valid schedule and selfie', function () {
    $this->actingAs($this->barista);

    // Create schedule for barista starting now - 5 mins, ending in 6 hours
    $now = Carbon::create(2026, 10, 14, 8, 5, 0);
    Carbon::setTestNow($now);

    Schedule::create([
        'user_id' => $this->barista->id,
        'work_date' => $now->toDateString(),
        'slot' => 'shift1',
        'start_time' => '08:00:00',
        'end_time' => '15:00:00',
    ]);

    $checkIn = app(CheckInAttendance::class);
    $mockSelfie = 'data:image/jpeg;base64,' . base64_encode('fake-selfie-data');

    $attendance = $checkIn->execute(
        user: $this->barista,
        photoBase64: $mockSelfie,
        lat: 0.5070677,
        lng: 101.447779,
        note: 'Masuk tepat waktu',
    );

    expect($attendance->isCheckedIn())->toBeTrue()
        ->and($attendance->status)->toBe('present') // 08:05 is within 15 min tolerance
        ->and($attendance->photo_in_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($attendance->photo_in_path))->toBeTrue();

    Carbon::setTestNow();
});

test('employee check-in marks as late when arriving past 15 minutes after shift start', function () {
    $this->actingAs($this->barista);

    // Shift starts at 08:00, arrival at 08:25
    $now = Carbon::create(2026, 10, 14, 8, 25, 0);
    Carbon::setTestNow($now);

    Schedule::create([
        'user_id' => $this->barista->id,
        'work_date' => $now->toDateString(),
        'slot' => 'shift1',
        'start_time' => '08:00:00',
        'end_time' => '15:00:00',
    ]);

    $checkIn = app(CheckInAttendance::class);
    $attendance = $checkIn->execute(
        user: $this->barista,
        note: 'Terlambat karena macet',
    );

    expect($attendance->status)->toBe('late');

    Carbon::setTestNow();
});

test('check-in is rejected if employee has no schedule today', function () {
    $this->actingAs($this->kitchen);

    $now = Carbon::create(2026, 10, 14, 9, 0, 0);
    Carbon::setTestNow($now);

    // No schedule created for kitchen
    $checkIn = app(CheckInAttendance::class);

    expect(fn () => $checkIn->execute(user: $this->kitchen))
        ->toThrow(InvalidArgumentException::class, 'Anda tidak memiliki jadwal kerja hari ini.');

    Carbon::setTestNow();
});

test('check-out marks attendance as completed', function () {
    $this->actingAs($this->barista);

    $now = Carbon::create(2026, 10, 14, 8, 0, 0);
    Carbon::setTestNow($now);

    $schedule = Schedule::create([
        'user_id' => $this->barista->id,
        'work_date' => $now->toDateString(),
        'slot' => 'shift1',
        'start_time' => '08:00:00',
        'end_time' => '15:00:00',
    ]);

    $checkIn = app(CheckInAttendance::class);
    $checkIn->execute(user: $this->barista);

    // Time is 15:05 (Shift ends, checking out)
    Carbon::setTestNow($now->copy()->setTime(15, 5, 0));

    $checkOut = app(CheckOutAttendance::class);
    $attendance = $checkOut->execute(user: $this->barista);

    expect($attendance->isCheckedOut())->toBeTrue()
        ->and($attendance->check_out_at)->not->toBeNull();

    Carbon::setTestNow();
});

test('schedule and attendance livewire components render properly', function () {
    $this->actingAs($this->manager);

    Livewire::test(ScheduleIndex::class)
        ->assertSee('Jadwal Shift Karyawan')
        ->assertSee('Shift 1')
        ->assertSee('Shift 2')
        ->assertSee('Kitchen');

    Livewire::test(AttendanceIndex::class)
        ->assertSee('Absensi Karyawan')
        ->assertSee('Jam Masuk')
        ->assertSee('Jam Pulang');
});
