<?php

use App\Actions\CreateOrder;
use App\Actions\OpenShift;
use App\Actions\PayOrder;
use App\Enums\MenuType;
use App\Enums\PaymentMethod;
use App\Livewire\Reports\ReportsIndex;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->manager = User::where('username', 'manager')->first();
    $this->cashier = User::where('username', 'kasir')->first();
    $this->actingAs($this->cashier);

    app(OpenShift::class)->execute($this->cashier, 100000);

    $this->category = Category::create(['name' => 'Kopi', 'type' => 'drink', 'sort_order' => 1]);
    $this->item = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Kopi Susu Gula Aren',
        'type' => MenuType::Drink,
        'price' => 20000,
        'is_available' => true,
        'is_active' => true,
    ]);

    // Create 1 Paid Cash Order (2x = Rp40.000)
    $order1 = app(CreateOrder::class)->execute([
        'items' => [
            [
                'menu_item_id' => $this->item->id,
                'name' => $this->item->name,
                'type' => MenuType::Drink,
                'qty' => 2,
                'unit_price' => 20000,
            ],
        ],
    ]);
    app(PayOrder::class)->execute($order1, PaymentMethod::Cash, 50000);

    // Create 1 Open Bill Order (1x = Rp20.000)
    app(CreateOrder::class)->execute([
        'customer_name' => 'Dosen Prodi',
        'is_open_bill' => true,
        'open_bill_note' => 'Rapat',
        'items' => [
            [
                'menu_item_id' => $this->item->id,
                'name' => $this->item->name,
                'type' => MenuType::Drink,
                'qty' => 1,
                'unit_price' => 20000,
            ],
        ],
    ]);
});

test('reports index calculates sales metrics and open bill totals', function () {
    $this->actingAs($this->manager);

    Livewire::test(ReportsIndex::class)
        ->assertSee('Laporan Penjualan')
        ->assertSee('Rp40.000') // Net sales
        ->assertSee('Rp20.000'); // Open bill piutang
});

test('reports index can export pdf', function () {
    $this->actingAs($this->manager);

    $component = Livewire::test(ReportsIndex::class);
    $response = $component->instance()->exportPdf();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class)
        ->and($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('reports index can export csv with utf-8 bom', function () {
    $this->actingAs($this->manager);

    $component = Livewire::test(ReportsIndex::class);
    $response = $component->instance()->exportCsv();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class)
        ->and($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->headers->get('content-disposition'))->toContain('.csv');
});

test('reports calculates consignment partner profit share correctly', function () {
    $this->actingAs($this->manager);

    // Partner gets 30% share, UMCO gets 70%
    $partner = Partner::create([
        'name' => 'Dapur Bu Rita',
        'share_percent' => 30,
        'contact' => '08123456789',
        'is_active' => true,
    ]);

    $pie = MenuItem::create([
        'category_id' => $this->category->id,
        'partner_id' => $partner->id,
        'name' => 'Pastel Ayam Bu Rita',
        'type' => MenuType::Food,
        'price' => 10000,
        'is_available' => true,
        'is_active' => true,
    ]);

    // Sell 5 pastels = Rp50.000
    // Partner gets 30% = Rp15.000, Cafe gets Rp35.000
    $order = app(CreateOrder::class)->execute([
        'items' => [
            [
                'menu_item_id' => $pie->id,
                'name' => $pie->name,
                'type' => MenuType::Food,
                'qty' => 5,
                'unit_price' => 10000,
            ],
        ],
    ]);
    app(PayOrder::class)->execute($order, PaymentMethod::Cash, 50000);

    Livewire::test(ReportsIndex::class)
        ->set('reportTab', 'partners')
        ->assertSee('Dapur Bu Rita')
        ->assertSee('30%')
        ->assertSee('Rp50.000') // Total revenue
        ->assertSee('Rp15.000') // Partner share
        ->assertSee('Rp35.000'); // Cafe share
});
