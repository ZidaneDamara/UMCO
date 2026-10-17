<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Customer\CustomerDisplay;
use App\Livewire\Customer\CustomerMenu;
use App\Livewire\Customer\OrderStatusScreen;
use App\Livewire\Inventory\InventoryIndex;
use App\Livewire\Menu\MenuIndex;
use App\Livewire\Pos\OpenBillsIndex;
use App\Livewire\Pos\PosIndex;
use App\Livewire\Reports\ReportsIndex;
use App\Livewire\Schedule\ScheduleIndex;
use App\Livewire\Shifts\ShiftManager;
use App\Livewire\Station\StationIndex;
use Illuminate\Support\Facades\Route;

// Guest Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/switch-user', [AuthController::class, 'switchUser'])->name('switch-user');

    // Root redirect
    Route::get('/', function () {
        return redirect()->route('pos.index');
    });

    // POS / Kasir
    Route::get('/pos', PosIndex::class)
        ->name('pos.index')
        ->middleware('permission:pos.access');

    // Open Bills (Tagihan Belum Dibayar / Dine-in Tab)
    Route::get('/open-bills', OpenBillsIndex::class)
        ->name('open-bills.index')
        ->middleware('permission:orders.open_bill');

    // Kelola Shift & Kas Operasional
    Route::get('/shifts', ShiftManager::class)
        ->name('shifts.index')
        ->middleware('permission:shifts.manage');

    // Layar Produksi Barista & Dapur
    Route::get('/station/bar', StationIndex::class)
        ->name('station.bar')
        ->middleware('permission:production.bar')
        ->defaults('station', 'bar');

    Route::get('/station/kitchen', StationIndex::class)
        ->name('station.kitchen')
        ->middleware('permission:production.kitchen')
        ->defaults('station', 'kitchen');

    // Menu & Ketersediaan
    Route::get('/menu', MenuIndex::class)
        ->name('menu.index')
        ->middleware('permission:menu.view');

    // Stok & Bahan Baku
    Route::get('/inventory', InventoryIndex::class)
        ->name('inventory.index')
        ->middleware('permission:inventory.view');

    // Laporan Transaksi
    Route::get('/reports', ReportsIndex::class)
        ->name('reports.index')
        ->middleware('permission:reports.view');

    // Jadwal Shift Karyawan (KF-08, KF-09)
    Route::get('/schedule', ScheduleIndex::class)
        ->name('schedule.index')
        ->middleware('permission:schedule.view');

    // Absensi & Selfie Kehadiran (KF-02)
    Route::get('/attendance', AttendanceIndex::class)
        ->name('attendance.index')
        ->middleware('permission:attendance.self');

    // Realtime Fallback Polling (DESIGN.md Section 5.3)
    Route::get('/api/orders/pending', function () {
        $user = auth()->user();
        $query = \App\Models\Order::with(['items', 'table'])
            ->whereIn('status', [
                \App\Enums\OrderStatus::Confirmed,
                \App\Enums\OrderStatus::Preparing,
                \App\Enums\OrderStatus::Ready,
            ])
            ->where('created_at', '>=', now()->subHours(2))
            ->latest();

        if ($user->hasRole('barista')) {
            $query->whereHas('items', fn ($q) => $q->where('station', \App\Enums\StationType::Bar));
        } elseif ($user->hasRole('kitchen')) {
            $query->whereHas('items', fn ($q) => $q->where('station', \App\Enums\StationType::Kitchen));
        }

        $orders = $query->limit(20)->get()->map(fn ($o) => [
            'id' => $o->id,
            'number' => $o->number,
            'status' => $o->status->value,
            'table' => $o->table_label,
            'customer' => $o->customer_name,
            'created_at' => $o->created_at->format('H:i'),
        ]);

        return response()->json(['orders' => $orders]);
    });
});

// Self-Service QR & Layar Pelanggan (KF-33, KF-34)
Route::get('/m/{token}', CustomerMenu::class)
    ->name('customer.menu')
    ->middleware('throttle:30,1');

Route::get('/order/{orderNumber}/status', OrderStatusScreen::class)
    ->name('customer.order.status');

Route::get('/customer/display', CustomerDisplay::class)
    ->name('customer.display');

