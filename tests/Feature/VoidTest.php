<?php

use App\Actions\CreateOrder;
use App\Actions\OpenShift;
use App\Actions\VerifyManagerPinAndVoid;
use App\Enums\MenuType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderVoid;
use App\Models\Recipe;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->cashier = User::where('username', 'kasir')->first();
    $this->manager = User::where('username', 'manager')->first();
    $this->actingAs($this->cashier);

    app(OpenShift::class)->execute($this->cashier, 100000);
    RateLimiter::clear('void_pin_attempts|127.0.0.1');

    $this->category = Category::create(['name' => 'Minuman', 'type' => 'drink', 'sort_order' => 1]);
    $this->beans = Ingredient::create([
        'name' => 'Biji Kopi Arabika',
        'unit' => 'gram',
        'category' => 'bar',
        'stock' => 1000,
        'min_stock' => 200,
        'is_active' => true,
    ]);

    $this->menuItem = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Espresso Double',
        'type' => MenuType::Drink,
        'price' => 15000,
        'is_available' => true,
        'is_active' => true,
    ]);

    Recipe::create([
        'menu_item_id' => $this->menuItem->id,
        'ingredient_id' => $this->beans->id,
        'qty_per_item' => 18,
    ]);
});

test('order can be voided with valid manager pin and stock is returned', function () {
    $createOrder = app(CreateOrder::class);

    // Create order -> stock deducted by 18g
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

    $this->beans->refresh();
    expect((int) $this->beans->stock)->toBe(982);

    // Void the order with valid manager pin (123456)
    $verifier = app(VerifyManagerPinAndVoid::class);
    $orderVoid = $verifier->execute(
        order: $order,
        requester: $this->cashier,
        pin: '123456',
        reason: 'input_error',
        reasonDetail: 'Salah pilih pesanan',
    );

    $order->refresh();
    $this->beans->refresh();

    expect($order->status)->toBe(OrderStatus::Void)
        ->and($order->payment_status)->toBe(PaymentStatus::Void)
        ->and((int) $this->beans->stock)->toBe(1000) // Stock returned!
        ->and(OrderVoid::count())->toBe(1)
        ->and($orderVoid->approved_by)->toBe($this->manager->id);

    // Verify stock movement of type void_return exists
    $movement = StockMovement::where('type', StockMovementType::VoidReturn)->latest()->first();
    expect($movement)->not->toBeNull()
        ->and((int) $movement->qty_change)->toBe(18);
});

test('void with invalid pin is rejected', function () {
    $createOrder = app(CreateOrder::class);
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

    $verifier = app(VerifyManagerPinAndVoid::class);

    expect(fn () => $verifier->execute(
        order: $order,
        requester: $this->cashier,
        pin: '999999', // Wrong PIN
        reason: 'input_error',
    ))->toThrow(InvalidArgumentException::class, 'PIN Manajer tidak valid');

    $order->refresh();
    expect($order->status)->not->toBe(OrderStatus::Void);
});

test('rate limiter locks after 5 wrong pin attempts', function () {
    $createOrder = app(CreateOrder::class);
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

    $verifier = app(VerifyManagerPinAndVoid::class);

    // Attempt 5 wrong pins
    for ($i = 0; $i < 5; $i++) {
        try {
            $verifier->execute(
                order: $order,
                requester: $this->cashier,
                pin: '000000',
                reason: 'input_error',
            );
        } catch (\InvalidArgumentException $e) {
            // expected
        }
    }

    // 6th attempt should be blocked by rate limiter
    expect(fn () => $verifier->execute(
        order: $order,
        requester: $this->cashier,
        pin: '123456', // Even with correct PIN, system is locked!
        reason: 'input_error',
    ))->toThrow(InvalidArgumentException::class, 'Terlalu banyak percobaan PIN salah');
});
