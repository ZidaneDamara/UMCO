<?php

use App\Actions\CreateOrder;
use App\Actions\PayOrder;
use App\Enums\MenuType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderConfirmed;
use App\Events\OrderPaid;
use App\Events\OrderStatusUpdated;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Event::fake([
        OrderConfirmed::class,
        OrderPaid::class,
        OrderStatusUpdated::class,
    ]);

    $this->cashier = User::factory()->create();
    $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
    $this->cashier->assignRole($cashierRole);
    $this->actingAs($this->cashier);

    $this->drinkCategory = Category::create(['name' => 'Kopi', 'type' => 'drink', 'sort_order' => 1]);
    $this->foodCategory = Category::create(['name' => 'Makanan', 'type' => 'food', 'sort_order' => 2]);

    $this->drinkItem = MenuItem::create([
        'category_id' => $this->drinkCategory->id,
        'name' => 'Kopi Susu Broadcast',
        'type' => MenuType::Drink,
        'price' => 18000,
        'is_available' => true,
        'is_active' => true,
    ]);

    $this->foodItem = MenuItem::create([
        'category_id' => $this->foodCategory->id,
        'name' => 'Nasi Goreng Broadcast',
        'type' => MenuType::Food,
        'price' => 25000,
        'is_available' => true,
        'is_active' => true,
    ]);
});

test('order confirmation dispatches OrderConfirmed events to bar and kitchen', function () {
    $createOrder = app(CreateOrder::class);

    $order = $createOrder->execute([
        'items' => [
            [
                'menu_item_id' => $this->drinkItem->id,
                'name' => $this->drinkItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => $this->drinkItem->price,
            ],
            [
                'menu_item_id' => $this->foodItem->id,
                'name' => $this->foodItem->name,
                'type' => MenuType::Food,
                'qty' => 1,
                'unit_price' => $this->foodItem->price,
            ],
        ],
    ]);

    Event::assertDispatched(OrderConfirmed::class, function ($event) use ($order) {
        return $event->order->id === $order->id && $event->station === 'bar';
    });

    Event::assertDispatched(OrderConfirmed::class, function ($event) use ($order) {
        return $event->order->id === $order->id && $event->station === 'kitchen';
    });
});

test('paying order dispatches OrderPaid event to cashier channel', function () {
    $createOrder = app(CreateOrder::class);
    $payOrder = app(PayOrder::class);

    $order = $createOrder->execute([
        'items' => [
            ['name' => 'Kopi', 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 15000],
        ],
    ]);

    $payOrder->execute(
        order: $order,
        method: PaymentMethod::Cash,
        cashReceived: 20000,
    );

    Event::assertDispatched(OrderPaid::class, function ($event) use ($order) {
        return $event->order->id === $order->id;
    });
});
