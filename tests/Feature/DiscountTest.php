<?php

use App\Enums\DiscountKind;
use App\Enums\MenuType;
use App\Models\Discount;
use App\Services\DiscountEngine;

beforeEach(function () {
    // Seed test discounts
    Discount::create([
        'name' => 'Mahasiswa UMRI',
        'kind' => DiscountKind::FixedPerItem,
        'value' => 5000,
        'applies_to' => 'drink',
        'is_active' => true,
    ]);

    Discount::create([
        'name' => 'Dosen & Karyawan',
        'kind' => DiscountKind::PercentPerItem,
        'value' => 15,
        'applies_to' => 'drink',
        'is_active' => true,
    ]);

    Discount::create([
        'name' => 'Minimal 3 Produk',
        'kind' => DiscountKind::FixedPerOrder,
        'value' => 5000,
        'applies_to' => 'all',
        'conditions' => ['min_qty' => 3],
        'is_active' => true,
    ]);
});

test('mahasiswa discount deducts Rp5.000 per drink item', function () {
    $engine = app(DiscountEngine::class);
    $discount = Discount::where('name', 'Mahasiswa UMRI')->first();

    $cart = collect([
        ['menu_item_id' => 1, 'name' => 'Kopi Susu Aren', 'type' => MenuType::Drink, 'qty' => 2, 'unit_price' => 18000],
        ['menu_item_id' => 2, 'name' => 'Nasi Goreng', 'type' => MenuType::Food, 'qty' => 1, 'unit_price' => 24000],
    ]);

    $result = $engine->calculate($cart, $discount->id);

    // Kopi Susu Aren: Rp5.000 * 2 = Rp10.000 discount
    // Nasi Goreng: Food, so Rp0 identity discount
    // Total qty is 3 (2 drinks + 1 food), which also qualifies for Minimal 3 Produk (+Rp5.000)
    expect($result['discount_total'])->toBe(15000);
});

test('dosen discount deducts 15% per drink item', function () {
    $engine = app(DiscountEngine::class);
    $discount = Discount::where('name', 'Dosen & Karyawan')->first();

    $cart = collect([
        ['menu_item_id' => 1, 'name' => 'Kopi Susu Aren', 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 20000],
    ]);

    $result = $engine->calculate($cart, $discount->id);

    // 15% of 20.000 = 3.000
    expect($result['discount_total'])->toBe(3000);
});

test('minimal 3 products discount applies when total quantity reaches 3', function () {
    $engine = app(DiscountEngine::class);

    $cart = collect([
        ['menu_item_id' => 1, 'name' => 'Americano', 'type' => MenuType::Drink, 'qty' => 3, 'unit_price' => 15000],
    ]);

    // No identity discount selected
    $result = $engine->calculate($cart, null);

    expect($result['order_discount'])->toBe(5000)
        ->and($result['discount_total'])->toBe(5000);
});

test('discount never exceeds unit price', function () {
    $engine = app(DiscountEngine::class);
    $discount = Discount::where('name', 'Mahasiswa UMRI')->first(); // 5000 off

    $cart = collect([
        ['menu_item_id' => 1, 'name' => 'Air Mineral', 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 3000],
    ]);

    $result = $engine->calculate($cart, $discount->id);

    // Unit price is 3000, discount should cap at 3000, not 5000
    expect($result['items'][0]['discount_amount'])->toBe(3000);
});
