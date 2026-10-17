<?php

use App\Actions\CreateOrder;
use App\Actions\PayOrder;
use App\Enums\MenuType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\User;

beforeEach(function () {
    $this->cashier = User::factory()->create(['name' => 'Kasir Test']);
    $this->actingAs($this->cashier);

    $this->category = Category::create(['name' => 'Kopi', 'type' => 'drink', 'sort_order' => 1]);

    $this->ingredient = Ingredient::create([
        'name' => 'Biji Kopi Arabika Test',
        'unit' => 'gram',
        'category' => 'bar',
        'stock' => 1000,
        'min_stock' => 200,
        'is_active' => true,
    ]);

    $this->menuItem = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Espresso Test',
        'type' => MenuType::Drink,
        'price' => 15000,
        'is_available' => true,
        'is_active' => true,
    ]);

    Recipe::create([
        'menu_item_id' => $this->menuItem->id,
        'ingredient_id' => $this->ingredient->id,
        'qty_per_item' => 18,
    ]);
});

test('create order generates valid daily order number and deducts stock', function () {
    $createOrder = app(CreateOrder::class);

    $order = $createOrder->execute([
        'customer_name' => 'Budi',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 2,
                'unit_price' => $this->menuItem->price,
            ],
        ],
    ]);

    $today = now()->format('ymd');
    expect($order->number)->toStartWith("UMC-{$today}-")
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->total)->toBe(30000);

    // Stock was 1000, 2 cups x 18g = 36g deducted -> 964g remaining
    $this->ingredient->refresh();
    expect((int) $this->ingredient->stock)->toBe(964);
});

test('cash payment calculates correct change and sets order as paid', function () {
    $createOrder = app(CreateOrder::class);
    $payOrder = app(PayOrder::class);

    $order = $createOrder->execute([
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => 15000,
            ],
        ],
    ]);

    $payment = $payOrder->execute(
        order: $order,
        method: PaymentMethod::Cash,
        cashReceived: 50000,
    );

    $order->refresh();

    expect($payment->cash_received)->toBe(50000)
        ->and($payment->change)->toBe(35000)
        ->and($order->isPaid())->toBeTrue()
        ->and($order->paid_at)->not->toBeNull();
});

test('cash payment fails when cash received is less than total', function () {
    $createOrder = app(CreateOrder::class);
    $payOrder = app(PayOrder::class);

    $order = $createOrder->execute([
        'items' => [
            ['name' => 'Kopi', 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 20000],
        ],
    ]);

    expect(fn () => $payOrder->execute(
        order: $order,
        method: PaymentMethod::Cash,
        cashReceived: 15000,
    ))->toThrow(InvalidArgumentException::class);
});

test('open bill creates unpaid order with note', function () {
    $createOrder = app(CreateOrder::class);

    $order = $createOrder->execute([
        'customer_name' => 'Dosen FT',
        'is_open_bill' => true,
        'open_bill_note' => 'Konsumsi Rapat Fakultas',
        'items' => [
            ['name' => 'Kopi Susu', 'type' => MenuType::Drink, 'qty' => 3, 'unit_price' => 18000],
        ],
    ]);

    expect($order->is_open_bill)->toBeTrue()
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->open_bill_note)->toBe('Konsumsi Rapat Fakultas');
});
