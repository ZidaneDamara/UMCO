<?php

use App\Actions\CreateOrder;
use App\Actions\OpenShift;
use App\Enums\MenuType;
use App\Enums\PaymentStatus;
use App\Livewire\Pos\OpenBillsIndex;
use App\Livewire\Shifts\ShiftManager;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->cashier = User::where('username', 'kasir')->first();
    $this->actingAs($this->cashier);

    app(OpenShift::class)->execute($this->cashier, 100000);

    $this->category = Category::create(['name' => 'Minuman', 'type' => 'drink', 'sort_order' => 1]);
    $this->menuItem = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Americano',
        'type' => MenuType::Drink,
        'price' => 18000,
        'is_available' => true,
        'is_active' => true,
    ]);
});

test('open bills component renders and can settle open bill', function () {
    $order = app(CreateOrder::class)->execute([
        'customer_name' => 'Pak Dekan',
        'is_open_bill' => true,
        'open_bill_note' => 'Meja 05 - Tamu',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => 18000,
            ],
        ],
    ]);

    Livewire::test(OpenBillsIndex::class)
        ->assertSee('Pak Dekan')
        ->assertSee('Meja 05 - Tamu')
        ->assertSee('Rp18.000')
        ->call('openPayModal', $order->id)
        ->assertSet('showPayModal', true)
        ->assertSet('selectedOrderId', $order->id)
        ->assertSet('cashReceived', 18000)
        ->call('settlePayment')
        ->assertSet('showPayModal', false)
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('open bills component can void order with manager pin', function () {
    $order = app(CreateOrder::class)->execute([
        'customer_name' => 'Bu Wadek',
        'is_open_bill' => true,
        'open_bill_note' => 'Salah catat',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => 18000,
            ],
        ],
    ]);

    Livewire::test(OpenBillsIndex::class)
        ->call('openVoidModal', $order->id)
        ->assertSet('showVoidModal', true)
        ->set('voidReason', 'input_error')
        ->set('managerPin', '123456') // Manager pin
        ->call('confirmVoid')
        ->assertSet('showVoidModal', false)
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->isVoid())->toBeTrue();
});

test('shift manager component renders and can record expense and close shift', function () {
    Livewire::test(ShiftManager::class)
        ->assertSee('Modal Awal Kas')
        ->assertSee('Rp100.000')
        ->set('expenseCategory', 'operasional')
        ->set('expenseAmount', 15000)
        ->set('expenseNote', 'Beli Es Batu')
        ->call('recordExpense')
        ->assertHasNoErrors()
        ->assertSee('Beli Es Batu')
        ->assertSee('Rp15.000')
        ->call('openCloseShiftModal')
        ->assertSet('showCloseModal', true)
        ->set('countedCash', 85000)
        ->set('nextDayCash', 100000)
        ->call('closeCurrentShift')
        ->assertHasNoErrors();

    $shift = $this->cashier->shifts()->latest()->first();
    expect($shift->isOpen())->toBeFalse()
        ->and($shift->expected_cash)->toBe(85000) // 100k - 15k
        ->and($shift->counted_cash)->toBe(85000)
        ->and($shift->difference)->toBe(0)
        ->and($shift->next_day_cash)->toBe(100000);
});
