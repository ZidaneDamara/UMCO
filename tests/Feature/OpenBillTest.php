<?php

use App\Actions\CreateOrder;
use App\Actions\OpenShift;
use App\Actions\PayOrder;
use App\Enums\MenuType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('cashier can create an open bill', function () {
    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'customer_name' => 'Budi Santoso',
        'is_open_bill' => true,
        'open_bill_note' => 'Meja 04 - Tunggu kawan',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 2,
                'unit_price' => 18000,
                'notes' => 'Less ice',
            ],
        ],
    ]);

    expect($order->is_open_bill)->toBeTrue()
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->customer_name)->toBe('Budi Santoso')
        ->and($order->open_bill_note)->toBe('Meja 04 - Tunggu kawan')
        ->and($order->total)->toBe(36000);
});

test('open bill can be settled with cash payment', function () {
    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'customer_name' => 'Ahmad Dahlan',
        'is_open_bill' => true,
        'open_bill_note' => 'Rapat BUMK',
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

    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $payOrder = app(PayOrder::class);
    $payment = $payOrder->execute(
        order: $order,
        method: PaymentMethod::Cash,
        cashReceived: 50000,
    );

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($payment->amount)->toBe(18000)
        ->and($payment->cash_received)->toBe(50000)
        ->and($payment->change)->toBe(32000);
});
