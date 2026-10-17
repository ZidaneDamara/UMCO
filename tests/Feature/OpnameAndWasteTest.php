<?php

use App\Actions\RecordOpname;
use App\Actions\RecordWaste;
use App\Enums\StockMovementType;
use App\Livewire\Inventory\InventoryIndex;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->manager = User::where('username', 'manager')->first();
    $this->barista = User::where('username', 'barista')->first();
    $this->actingAs($this->manager);

    $this->milk = Ingredient::create([
        'name' => 'Susu UHT Fresh Milk',
        'unit' => 'ml',
        'category' => 'bar',
        'stock' => 5000,
        'min_stock' => 1000,
        'is_active' => true,
    ]);

    $this->syrup = Ingredient::create([
        'name' => 'Sirup Karamel',
        'unit' => 'ml',
        'category' => 'bar',
        'stock' => 1000,
        'min_stock' => 300,
        'is_active' => true,
    ]);
});

test('record waste deducts stock and records waste movement with mandatory reason', function () {
    $recordWaste = app(RecordWaste::class);

    $movement = $recordWaste->execute(
        ingredient: $this->milk,
        qty: 500,
        reason: 'Basi / Kedaluwarsa',
        user: $this->barista,
        note: 'Susu berbau asam saat dibuka pagi',
    );

    $this->milk->refresh();

    expect((int) $this->milk->stock)->toBe(4500)
        ->and($movement->type)->toBe(StockMovementType::Waste)
        ->and((int) $movement->qty_change)->toBe(-500)
        ->and((int) $movement->qty_after)->toBe(4500)
        ->and($movement->note)->toContain('Basi / Kedaluwarsa')
        ->and($movement->note)->toContain('Susu berbau asam');
});

test('record waste fails with zero or negative quantity', function () {
    $recordWaste = app(RecordWaste::class);

    expect(fn () => $recordWaste->execute(
        ingredient: $this->milk,
        qty: 0,
        reason: 'Tumpah',
        user: $this->barista,
    ))->toThrow(InvalidArgumentException::class);
});

test('record batch opname reconciles physical stock and records differences without google form', function () {
    $recordOpname = app(RecordOpname::class);

    // Initial stocks:
    // Milk: 5000ml in system -> Physical count: 4800ml (Diff: -200ml)
    // Syrup: 1000ml in system -> Physical count: 1200ml (Diff: +200ml)
    $opname = $recordOpname->execute(
        user: $this->manager,
        items: [
            ['ingredient_id' => $this->milk->id, 'counted_qty' => 4800],
            ['ingredient_id' => $this->syrup->id, 'counted_qty' => 1200],
        ],
        note: 'Opname Mingguan Barista',
    );

    $this->milk->refresh();
    $this->syrup->refresh();

    expect((int) $this->milk->stock)->toBe(4800)
        ->and((int) $this->syrup->stock)->toBe(1200)
        ->and($opname->items()->count())->toBe(2);

    $milkItem = $opname->items()->where('ingredient_id', $this->milk->id)->first();
    expect((int) $milkItem->system_qty)->toBe(5000)
        ->and((int) $milkItem->counted_qty)->toBe(4800)
        ->and((int) $milkItem->diff)->toBe(-200);

    // Check stock movements created
    $movements = StockMovement::where('type', StockMovementType::Opname)->get();
    expect($movements->count())->toBe(2);
});

test('inventory livewire component renders and handles waste and opname operations', function () {
    Livewire::test(InventoryIndex::class)
        ->assertSee('Susu UHT Fresh Milk')
        ->assertSee('Daftar Stok')
        ->assertSee('Perlu Dibeli')
        ->assertSee('Stok Opname')
        // Test waste modal and save
        ->call('openWasteModal', $this->milk->id)
        ->assertSet('showWasteModal', true)
        ->set('wasteQty', 200)
        ->set('wasteReason', 'Tumpah / Terjatuh')
        ->set('wasteNote', 'Tumpah di meja racik')
        ->call('recordWaste')
        ->assertSet('showWasteModal', false)
        ->assertHasNoErrors();

    $this->milk->refresh();
    expect((int) $this->milk->stock)->toBe(4800);

    // Test opname batch save
    Livewire::test(InventoryIndex::class)
        ->set('countedQuantities.' . $this->milk->id, 4500)
        ->set('opnameNote', 'Opname Sore')
        ->call('saveOpname')
        ->assertHasNoErrors();

    $this->milk->refresh();
    expect((int) $this->milk->stock)->toBe(4500);
});
