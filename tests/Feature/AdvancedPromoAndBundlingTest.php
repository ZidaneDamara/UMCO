<?php

use App\Enums\DiscountKind;
use App\Enums\MenuType;
use App\Models\Category;
use App\Models\Discount;
use App\Models\MenuItem;
use App\Services\DiscountEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create(['name' => 'Kopi', 'type' => 'drink', 'sort_order' => 1]);
    $this->latte = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Caffe Latte',
        'type' => MenuType::Drink,
        'price' => 22000,
        'is_available' => true,
        'is_active' => true,
    ]);
    $this->croissant = MenuItem::create([
        'category_id' => $this->category->id,
        'name' => 'Butter Croissant',
        'type' => MenuType::Food,
        'price' => 15000,
        'is_available' => true,
        'is_active' => true,
    ]);
});

test('scheduled promo only applies on designated days of week', function () {
    // Promo: 'Promo Jumat Berkah' Rp5.000 off, only on Fridays (day 5)
    $promoJumat = Discount::create([
        'name' => 'Promo Jumat Berkah',
        'kind' => DiscountKind::Promo,
        'value' => 5000,
        'applies_to' => 'drink',
        'conditions' => [
            'days_of_week' => [5], // Friday
            'type' => 'fixed',
        ],
        'is_active' => true,
    ]);

    $engine = app(DiscountEngine::class);

    // Test on Friday: 2026-10-16 is Friday
    Carbon::setTestNow(Carbon::create(2026, 10, 16, 10, 0, 0));
    expect(now()->dayOfWeekIso)->toBe(5);

    $cart = collect([
        ['menu_item_id' => $this->latte->id, 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 22000],
    ]);

    $resultFriday = $engine->calculate($cart, $promoJumat->id);
    expect($resultFriday['discount_total'])->toBe(5000);

    // Test on Wednesday: 2026-10-14 is Wednesday (day 3)
    Carbon::setTestNow(Carbon::create(2026, 10, 14, 10, 0, 0));
    expect(now()->dayOfWeekIso)->toBe(3);

    $resultWednesday = $engine->calculate($cart, $promoJumat->id);
    expect($resultWednesday['discount_total'])->toBe(0); // Inactive on Wednesday!

    Carbon::setTestNow();
});

test('item-specific promo applies discount only to allowed menu items', function () {
    // Promo specifically for Latte: 50% discount
    $lattePromo = Discount::create([
        'name' => 'Promo Diskon 50% Latte',
        'kind' => DiscountKind::Promo,
        'value' => 50,
        'applies_to' => 'all',
        'conditions' => [
            'type' => 'percent',
            'menu_item_ids' => [$this->latte->id],
        ],
        'is_active' => true,
    ]);

    $engine = app(DiscountEngine::class);
    $cart = collect([
        ['menu_item_id' => $this->latte->id, 'type' => MenuType::Drink, 'qty' => 1, 'unit_price' => 22000],
        ['menu_item_id' => $this->croissant->id, 'type' => MenuType::Food, 'qty' => 1, 'unit_price' => 15000],
    ]);

    $result = $engine->calculate($cart, $lattePromo->id);

    // Latte gets 50% off (11.000), Croissant gets 0 discount
    expect($result['discount_total'])->toBe(11000)
        ->and($result['items'][0]['discount_amount'])->toBe(11000)
        ->and($result['items'][1]['discount_amount'])->toBe(0);
});
