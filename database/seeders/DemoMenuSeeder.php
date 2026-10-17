<?php

namespace Database\Seeders;

use App\Enums\DiscountKind;
use App\Enums\MenuType;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class DemoMenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $catKopi = Category::firstOrCreate(['name' => 'Kopi'], ['type' => 'drink', 'sort_order' => 1]);
        $catNonKopi = Category::firstOrCreate(['name' => 'Non-Kopi'], ['type' => 'drink', 'sort_order' => 2]);
        $catMakanan = Category::firstOrCreate(['name' => 'Makanan Utama'], ['type' => 'food', 'sort_order' => 3]);
        $catSnack = Category::firstOrCreate(['name' => 'Camilan & Roti'], ['type' => 'food', 'sort_order' => 4]);

        // 2. Ingredients
        $ingredients = [
            'kopi_arabika' => Ingredient::firstOrCreate(
                ['name' => 'Biji Kopi Arabika'],
                ['unit' => 'gram', 'category' => 'bar', 'stock' => 5000, 'min_stock' => 1000, 'is_active' => true]
            ),
            'susu_fresh' => Ingredient::firstOrCreate(
                ['name' => 'Susu Fresh Milk'],
                ['unit' => 'ml', 'category' => 'bar', 'stock' => 10000, 'min_stock' => 2000, 'is_active' => true]
            ),
            'gula_aren' => Ingredient::firstOrCreate(
                ['name' => 'Sirup Gula Aren'],
                ['unit' => 'ml', 'category' => 'bar', 'stock' => 5000, 'min_stock' => 1000, 'is_active' => true]
            ),
            'bubuk_matcha' => Ingredient::firstOrCreate(
                ['name' => 'Bubuk Matcha Premium'],
                ['unit' => 'gram', 'category' => 'bar', 'stock' => 2000, 'min_stock' => 500, 'is_active' => true]
            ),
            'bubuk_cokelat' => Ingredient::firstOrCreate(
                ['name' => 'Bubuk Dark Chocolate'],
                ['unit' => 'gram', 'category' => 'bar', 'stock' => 3000, 'min_stock' => 500, 'is_active' => true]
            ),
            'teh_melati' => Ingredient::firstOrCreate(
                ['name' => 'Daun Teh Melati'],
                ['unit' => 'gram', 'category' => 'bar', 'stock' => 2000, 'min_stock' => 400, 'is_active' => true]
            ),
            'beras' => Ingredient::firstOrCreate(
                ['name' => 'Beras Organik'],
                ['unit' => 'gram', 'category' => 'kitchen', 'stock' => 15000, 'min_stock' => 3000, 'is_active' => true]
            ),
            'daging_ayam' => Ingredient::firstOrCreate(
                ['name' => 'Daging Ayam Fillet'],
                ['unit' => 'gram', 'category' => 'kitchen', 'stock' => 5000, 'min_stock' => 1000, 'is_active' => true]
            ),
            'telur' => Ingredient::firstOrCreate(
                ['name' => 'Telur Ayam'],
                ['unit' => 'butir', 'category' => 'kitchen', 'stock' => 100, 'min_stock' => 20, 'is_active' => true]
            ),
            'roti_tawar' => Ingredient::firstOrCreate(
                ['name' => 'Roti Tawar Gandum'],
                ['unit' => 'lembar', 'category' => 'kitchen', 'stock' => 60, 'min_stock' => 15, 'is_active' => true]
            ),
            'kentang_beku' => Ingredient::firstOrCreate(
                ['name' => 'Kentang French Fries'],
                ['unit' => 'gram', 'category' => 'kitchen', 'stock' => 4000, 'min_stock' => 1000, 'is_active' => true]
            ),
        ];

        // 3. Menu Items
        $menuData = [
            // Kopi
            [
                'name' => 'Kopi Susu Aren UMCO',
                'category_id' => $catKopi->id,
                'type' => MenuType::Drink,
                'price' => 18000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kopi_arabika', 'qty' => 18],
                    ['ingredient' => 'susu_fresh', 'qty' => 120],
                    ['ingredient' => 'gula_aren', 'qty' => 25],
                ],
            ],
            [
                'name' => 'Americano Dingin',
                'category_id' => $catKopi->id,
                'type' => MenuType::Drink,
                'price' => 15000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kopi_arabika', 'qty' => 18],
                ],
            ],
            [
                'name' => 'Caffe Latte',
                'category_id' => $catKopi->id,
                'type' => MenuType::Drink,
                'price' => 20000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kopi_arabika', 'qty' => 18],
                    ['ingredient' => 'susu_fresh', 'qty' => 150],
                ],
            ],
            [
                'name' => 'Caramel Macchiato',
                'category_id' => $catKopi->id,
                'type' => MenuType::Drink,
                'price' => 22000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kopi_arabika', 'qty' => 18],
                    ['ingredient' => 'susu_fresh', 'qty' => 130],
                    ['ingredient' => 'gula_aren', 'qty' => 20],
                ],
            ],
            [
                'name' => 'Espresso Single',
                'category_id' => $catKopi->id,
                'type' => MenuType::Drink,
                'price' => 12000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kopi_arabika', 'qty' => 10],
                ],
            ],

            // Non-Kopi
            [
                'name' => 'Matcha Latte',
                'category_id' => $catNonKopi->id,
                'type' => MenuType::Drink,
                'price' => 22000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'bubuk_matcha', 'qty' => 20],
                    ['ingredient' => 'susu_fresh', 'qty' => 150],
                ],
            ],
            [
                'name' => 'Signature Chocolate Ice',
                'category_id' => $catNonKopi->id,
                'type' => MenuType::Drink,
                'price' => 20000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'bubuk_cokelat', 'qty' => 25],
                    ['ingredient' => 'susu_fresh', 'qty' => 140],
                ],
            ],
            [
                'name' => 'Es Teh Melati UMRI',
                'category_id' => $catNonKopi->id,
                'type' => MenuType::Drink,
                'price' => 8000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'teh_melati', 'qty' => 10],
                ],
            ],
            [
                'name' => 'Lemon Tea Segar',
                'category_id' => $catNonKopi->id,
                'type' => MenuType::Drink,
                'price' => 14000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'teh_melati', 'qty' => 10],
                ],
            ],

            // Makanan Utama
            [
                'name' => 'Nasi Goreng Spesial UMCO',
                'category_id' => $catMakanan->id,
                'type' => MenuType::Food,
                'price' => 24000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'beras', 'qty' => 150],
                    ['ingredient' => 'telur', 'qty' => 1],
                    ['ingredient' => 'daging_ayam', 'qty' => 50],
                ],
            ],
            [
                'name' => 'Mie Goreng Telur',
                'category_id' => $catMakanan->id,
                'type' => MenuType::Food,
                'price' => 18000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'telur', 'qty' => 1],
                ],
            ],
            [
                'name' => 'Rice Bowl Ayam Teriyaki',
                'category_id' => $catMakanan->id,
                'type' => MenuType::Food,
                'price' => 26000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'beras', 'qty' => 150],
                    ['ingredient' => 'daging_ayam', 'qty' => 80],
                ],
            ],
            [
                'name' => 'Rice Bowl Ayam Sambal Matah',
                'category_id' => $catMakanan->id,
                'type' => MenuType::Food,
                'price' => 26000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'beras', 'qty' => 150],
                    ['ingredient' => 'daging_ayam', 'qty' => 80],
                ],
            ],

            // Camilan & Roti
            [
                'name' => 'Kentang Goreng Krispi',
                'category_id' => $catSnack->id,
                'type' => MenuType::Food,
                'price' => 16000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'kentang_beku', 'qty' => 150],
                ],
            ],
            [
                'name' => 'Roti Bakar Coklat Keju',
                'category_id' => $catSnack->id,
                'type' => MenuType::Food,
                'price' => 18000,
                'is_available' => true,
                'recipe' => [
                    ['ingredient' => 'roti_tawar', 'qty' => 2],
                    ['ingredient' => 'bubuk_cokelat', 'qty' => 15],
                ],
            ],
            [
                'name' => 'Cireng Crispy Bumbu Rujak',
                'category_id' => $catSnack->id,
                'type' => MenuType::Food,
                'price' => 15000,
                'is_available' => true,
                'recipe' => [],
            ],
        ];

        foreach ($menuData as $item) {
            $menu = MenuItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $item['category_id'],
                    'type' => $item['type'],
                    'price' => $item['price'],
                    'is_available' => $item['is_available'],
                    'is_active' => true,
                ]
            );

            // Sync recipes
            Recipe::where('menu_item_id', $menu->id)->delete();
            foreach ($item['recipe'] as $rec) {
                if (isset($ingredients[$rec['ingredient']])) {
                    Recipe::create([
                        'menu_item_id' => $menu->id,
                        'ingredient_id' => $ingredients[$rec['ingredient']]->id,
                        'qty_per_item' => $rec['qty'],
                    ]);
                }
            }
        }

        // 4. Default Standard Discounts (per DESIGN.md Section 8.1)
        Discount::updateOrCreate(
            ['name' => 'Mahasiswa UMRI'],
            [
                'kind' => DiscountKind::FixedPerItem,
                'value' => 5000,
                'applies_to' => 'drink',
                'conditions' => [
                    'description' => 'Potongan Rp5.000 per minuman dengan syarat KTM & follow IG UMCO House.',
                    'ktm_required' => true,
                    'instagram_follow_required' => true,
                ],
                'is_active' => true,
            ]
        );

        Discount::updateOrCreate(
            ['name' => 'Dosen & Karyawan UMRI'],
            [
                'kind' => DiscountKind::PercentPerItem,
                'value' => 15,
                'applies_to' => 'drink',
                'conditions' => [
                    'description' => 'Diskon 15% per minuman untuk dosen dan staf kampus.',
                    'staff_id_required' => true,
                ],
                'is_active' => true,
            ]
        );

        Discount::updateOrCreate(
            ['name' => 'Minimal 3 Produk'],
            [
                'kind' => DiscountKind::FixedPerOrder,
                'value' => 5000,
                'applies_to' => 'all',
                'conditions' => [
                    'description' => 'Potongan Rp5.000 per transaksi jika pembelian minimal 3 item.',
                    'min_qty' => 3,
                ],
                'is_active' => true,
            ]
        );
    }
}
