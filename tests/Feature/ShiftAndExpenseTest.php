<?php

use App\Actions\CloseShift;
use App\Actions\CreateOrder;
use App\Actions\OpenShift;
use App\Actions\PayOrder;
use App\Actions\RecordExpense;
use App\Enums\MenuType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Expense;
use App\Models\MenuItem;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->cashier = User::where('username', 'kasir')->first();
    $this->actingAs($this->cashier);
});

test('shift lifecycle calculates expected cash correctly with sales and expenses', function () {
    // 1. Open shift with Rp100.000 modal awal
    $openShift = app(OpenShift::class);
    $shift = $openShift->execute($this->cashier, 100000);

    expect($shift->isOpen())->toBeTrue()
        ->and($shift->opening_cash)->toBe(100000);

    // 2. Perform Cash Sale: 2x Americano = Rp36.000
    $category = Category::create(['name' => 'Minuman', 'type' => 'drink', 'sort_order' => 1]);
    $item = MenuItem::create([
        'category_id' => $category->id,
        'name' => 'Americano',
        'type' => MenuType::Drink,
        'price' => 18000,
        'is_available' => true,
        'is_active' => true,
    ]);

    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'items' => [
            [
                'menu_item_id' => $item->id,
                'name' => $item->name,
                'type' => MenuType::Drink,
                'qty' => 2,
                'unit_price' => 18000,
            ],
        ],
    ]);

    $payOrder = app(PayOrder::class);
    $payOrder->execute(
        order: $order,
        method: PaymentMethod::Cash,
        cashReceived: 50000,
    );

    // 3. Record Operational Expense: Beli Es Batu Rp10.000
    $recordExpense = app(RecordExpense::class);
    $expense = $recordExpense->execute(
        shift: $shift,
        user: $this->cashier,
        category: 'bahan_baku',
        amount: 10000,
        note: 'Beli es batu kristal darurat',
    );

    expect($expense->amount)->toBe(10000)
        ->and(Expense::count())->toBe(1);

    // Expected cash should be:
    // opening_cash (100.000) + cash_sales (36.000) - expenses (10.000) = 126.000
    // Suppose physical counted cash is Rp125.000 (Selisih -Rp1.000)
    // and kas esok hari is Rp100.000
    $closeShift = app(CloseShift::class);
    $closedShift = $closeShift->execute(
        shift: $shift,
        countedCash: 125000,
        nextDayCash: 100000,
    );

    $closedShift->refresh();

    expect($closedShift->isOpen())->toBeFalse()
        ->and($closedShift->expected_cash)->toBe(126000)
        ->and($closedShift->counted_cash)->toBe(125000)
        ->and($closedShift->difference)->toBe(-1000)
        ->and($closedShift->next_day_cash)->toBe(100000);
});

test('cashier cannot have two open shifts simultaneously', function () {
    $openShift = app(OpenShift::class);
    $openShift->execute($this->cashier, 100000);

    expect(fn () => $openShift->execute($this->cashier, 50000))
        ->toThrow(InvalidArgumentException::class, 'Anda masih memiliki shift yang belum ditutup.');
});
