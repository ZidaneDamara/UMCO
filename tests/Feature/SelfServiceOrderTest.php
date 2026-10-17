<?php

use App\Actions\CreateOrder;
use App\Enums\MenuType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderConfirmed;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Livewire\Customer\CustomerDisplay;
use App\Livewire\Customer\CustomerMenu;
use App\Livewire\Customer\OrderStatusScreen;
use App\Livewire\Pos\PosIndex;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\Table;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Setup roles & permissions if not exists
    $role = Role::firstOrCreate(['name' => 'cashier']);
    $permission = Permission::firstOrCreate(['name' => 'pos.access']);
    $role->givePermissionTo($permission);

    $this->cashier = User::factory()->create(['name' => 'Kasir UMCO']);
    $this->cashier->assignRole('cashier');

    // Create Table
    $this->table = Table::create([
        'label' => 'Meja 05',
        'token' => 'tbl-test-token-05',
        'is_active' => true,
    ]);

    // Create Antar Table
    $this->antarTable = Table::create([
        'label' => 'Pesanan Antar (Dosen/Rektorat)',
        'token' => 'antar',
        'is_active' => true,
    ]);

    $this->category = Category::create(['name' => 'Kopi', 'type' => 'drink', 'sort_order' => 1]);

    $this->ingredient = Ingredient::create([
        'name' => 'Biji Kopi Gayo',
        'unit' => 'gram',
        'category' => 'bar',
        'stock' => 1000,
        'min_stock' => 100,
        'is_active' => true,
    ]);

    $this->menuItem = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Kopi Susu Gula Aren',
        'type' => MenuType::Drink,
        'price' => 18000,
        'is_available' => true,
        'is_active' => true,
    ]);

    Recipe::create([
        'menu_item_id' => $this->menuItem->id,
        'ingredient_id' => $this->ingredient->id,
        'qty_per_item' => 18,
    ]);
});

test('customer can view self service menu with valid table token', function () {
    $this->get(route('customer.menu', ['token' => $this->table->token]))
        ->assertOk()
        ->assertSee('Meja 05')
        ->assertSee('Kopi Susu Gula Aren')
        ->assertSee('Rp18.000');
});

test('invalid or inactive table token displays friendly error screen', function () {
    $this->get(route('customer.menu', ['token' => 'token-salah-123']))
        ->assertOk()
        ->assertSee('Kode QR Meja Tidak Valid');
});

test('customer can add items with note chips and submit order', function () {
    Event::fake([OrderCreated::class]);

    Livewire::test(CustomerMenu::class, ['token' => $this->table->token])
        ->call('selectItem', $this->menuItem->id)
        ->call('toggleChip', 'Less Ice')
        ->call('toggleChip', 'Less Sugar')
        ->set('itemNote', 'Gula aren asli')
        ->call('addActiveItemToCart')
        ->set('customerName', 'Budi Santoso')
        ->call('submitOrder');

    $order = Order::where('customer_name', 'Budi Santoso')->first();

    expect($order)->not->toBeNull();
    expect($order->source)->toBe(OrderSource::SelfService);
    expect($order->status)->toBe(OrderStatus::PendingConfirmation);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
    expect($order->table_id)->toBe($this->table->id);
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->note)->toContain('Less Ice');
    expect($order->items->first()->note)->toContain('Less Sugar');
    expect($order->items->first()->note)->toContain('Gula aren asli');

    // Stock should NOT be deducted while still pending confirmation
    expect((float) $this->ingredient->fresh()->stock)->toBe(1000.0);

    Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
        return $event->order->id === $order->id;
    });
});

test('delivery order requires delivery location and sets delivery source', function () {
    Livewire::test(CustomerMenu::class, ['token' => $this->antarTable->token])
        ->call('selectItem', $this->menuItem->id)
        ->call('addActiveItemToCart')
        ->set('customerName', 'Dr. Hendri')
        ->set('deliveryLocation', 'Gedung Rektorat Lt 2, Ruang WR I')
        ->call('submitOrder');

    $order = Order::where('customer_name', 'Dr. Hendri')->first();

    expect($order)->not->toBeNull();
    expect($order->source)->toBe(OrderSource::Delivery);
    expect($order->open_bill_note)->toBe('Gedung Rektorat Lt 2, Ruang WR I');
    expect($order->status)->toBe(OrderStatus::PendingConfirmation);
});

test('honeypot field prevents bot form submission', function () {
    Livewire::test(CustomerMenu::class, ['token' => $this->table->token])
        ->call('selectItem', $this->menuItem->id)
        ->call('addActiveItemToCart')
        ->set('customerName', 'Bot Spammer')
        ->set('honeypot', 'i_am_a_bot')
        ->call('submitOrder');

    $order = Order::where('customer_name', 'Bot Spammer')->first();
    expect($order)->toBeNull();
});

test('cashier can accept pending order which deducts stock and broadcasts OrderConfirmed', function () {
    Event::fake([OrderConfirmed::class, OrderStatusUpdated::class]);

    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'source' => OrderSource::SelfService,
        'table_id' => $this->table->id,
        'customer_name' => 'Rina',
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

    expect($order->status)->toBe(OrderStatus::PendingConfirmation);
    expect((float) $this->ingredient->fresh()->stock)->toBe(1000.0);

    $this->actingAs($this->cashier);

    Livewire::test(PosIndex::class)
        ->call('acceptPendingOrder', $order->id);

    $freshOrder = $order->fresh();
    expect($freshOrder->status)->toBe(OrderStatus::Confirmed);

    // Stock should now be deducted: 1000 - (18 * 2) = 964
    expect((float) $this->ingredient->fresh()->stock)->toBe(964.0);

    Event::assertDispatched(OrderConfirmed::class, function ($event) use ($order) {
        return $event->order->id === $order->id && $event->station === 'bar';
    });

    Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
        return $event->order->id === $order->id && $event->status === OrderStatus::Confirmed;
    });
});

test('cashier can reject pending order', function () {
    Event::fake([OrderStatusUpdated::class]);

    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'source' => OrderSource::SelfService,
        'table_id' => $this->table->id,
        'customer_name' => 'Fulan',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => $this->menuItem->price,
            ],
        ],
    ]);

    $this->actingAs($this->cashier);

    Livewire::test(PosIndex::class)
        ->set('rejectReason', 'Bahan kopi habis')
        ->call('rejectPendingOrder', $order->id);

    $freshOrder = $order->fresh();
    expect($freshOrder->status)->toBe(OrderStatus::Void);
    expect($freshOrder->payment_status)->toBe(PaymentStatus::Void);
    expect($freshOrder->void)->not->toBeNull();
    expect($freshOrder->void->reason)->toBe('Bahan kopi habis');
});

test('customer order status tracking screen displays order progress', function () {
    $createOrder = app(CreateOrder::class);
    $order = $createOrder->execute([
        'source' => OrderSource::SelfService,
        'table_id' => $this->table->id,
        'customer_name' => 'Fajar',
        'items' => [
            [
                'menu_item_id' => $this->menuItem->id,
                'name' => $this->menuItem->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => $this->menuItem->price,
            ],
        ],
    ]);

    $this->get(route('customer.order.status', ['orderNumber' => $order->number]))
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('Meja 05')
        ->assertSee('Fajar')
        ->assertSee('Menunggu Konfirmasi Kasir');
});

test('customer facing counter display loads successfully', function () {
    $this->get(route('customer.display'))
        ->assertOk()
        ->assertSee('UMCO HOUSE')
        ->assertSee('Kopi Pilihan');
});
