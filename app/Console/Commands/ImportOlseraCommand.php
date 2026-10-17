<?php

namespace App\Console\Commands;

use App\Enums\MenuType;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOlseraCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'umco:import-olsera {file : Path to CSV file} {--dry-run : Run in simulation mode without writing to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import menu, categories, and initial inventory from Olsera CSV export (KF-12)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = (bool) $this->option('dry-run');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return Command::FAILURE;
        }

        $this->info($isDryRun ? '=== SIMULASI IMPOR DATA OLSERA (DRY-RUN) ===' : '=== MEMULAI IMPOR DATA OLSERA ===');

        $rows = array_map('str_getcsv', file($filePath));
        if (empty($rows)) {
            $this->error('File CSV kosong.');
            return Command::FAILURE;
        }

        $header = array_shift($rows);
        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        $categoriesCount = 0;
        $menuItemsCount = 0;
        $ingredientsCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $lineNum = $index + 2;
                if (count($row) < 3) {
                    continue;
                }

                $data = array_combine(array_slice($header, 0, count($row)), $row);

                $categoryName = trim($data['kategori'] ?? 'Umum');
                $menuName = trim($data['nama_menu'] ?? $data['menu'] ?? '');
                $type = strtolower(trim($data['tipe'] ?? 'drink')) === 'food' ? MenuType::Food : MenuType::Drink;
                $price = (int) preg_replace('/[^0-9]/', '', $data['harga'] ?? '0');
                $ingredientName = trim($data['bahan_nama'] ?? '');
                $stock = (float) ($data['stok_awal'] ?? 0);
                $unit = trim($data['unit'] ?? 'porsi');
                $minStock = (float) ($data['batas_min'] ?? 10);

                if (empty($menuName)) {
                    $errors[] = "Baris {$lineNum}: Nama menu kosong.";
                    continue;
                }

                // 1. Process Category
                $category = Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['type' => $type->value, 'sort_order' => 1]
                );
                if ($category->wasRecentlyCreated) {
                    $categoriesCount++;
                }

                // 2. Process MenuItem
                $menuItem = MenuItem::updateOrCreate(
                    ['name' => $menuName],
                    [
                        'category_id' => $category->id,
                        'type' => $type,
                        'price' => $price,
                        'is_available' => true,
                        'is_active' => true,
                    ]
                );
                if ($menuItem->wasRecentlyCreated) {
                    $menuItemsCount++;
                }

                // 3. Process Optional Ingredient
                if (! empty($ingredientName)) {
                    $ingredient = Ingredient::firstOrCreate(
                        ['name' => $ingredientName],
                        [
                            'unit' => $unit,
                            'category' => $type === MenuType::Drink ? 'bar' : 'kitchen',
                            'stock' => $stock,
                            'min_stock' => $minStock,
                            'is_active' => true,
                        ]
                    );
                    if ($ingredient->wasRecentlyCreated) {
                        $ingredientsCount++;
                    }
                }
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->warn('Mode Dry-run: Seluruh perubahan dibatalkan (tidak disimpan ke database).');
            } else {
                DB::commit();
                $this->info('Impor berhasil disimpan ke database.');
            }

            $this->table(
                ['Entitas', 'Jumlah Diproses'],
                [
                    ['Kategori Baru', $categoriesCount],
                    ['Menu Baru/Diperbarui', $menuItemsCount],
                    ['Bahan Baku Terdaftar', $ingredientsCount],
                    ['Baris Bermasalah', count($errors)],
                ]
            );

            if (! empty($errors)) {
                $this->warn('Catatan Kesalahan:');
                foreach (array_slice($errors, 0, 5) as $err) {
                    $this->line("- {$err}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal memproses impor: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
