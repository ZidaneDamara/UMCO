<?php

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tempCsv = tempnam(sys_get_temp_dir(), 'olsera_') . '.csv';
    $csvContent = implode("\n", [
        'kategori,nama_menu,tipe,harga,bahan_nama,stok_awal,unit,batas_min',
        'Minuman Kopi,Caramel Macchiato,drink,25000,Sirup Karamel Olsera,2000,ml,500',
        'Makanan Berat,Nasi Goreng UMCO,food,18000,Beras Pulen,5000,gram,1000',
    ]);
    file_put_contents($this->tempCsv, $csvContent);
});

afterEach(function () {
    if (file_exists($this->tempCsv)) {
        @unlink($this->tempCsv);
    }
});

test('olsera import dry-run simulates without writing to database', function () {
    $this->artisan('umco:import-olsera', [
        'file' => $this->tempCsv,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('SIMULASI IMPOR DATA OLSERA (DRY-RUN)')
        ->expectsOutputToContain('Mode Dry-run: Seluruh perubahan dibatalkan')
        ->assertSuccessful();

    expect(MenuItem::count())->toBe(0)
        ->and(Category::count())->toBe(0);
});

test('olsera import persists categories, menus, and ingredients', function () {
    $this->artisan('umco:import-olsera', [
        'file' => $this->tempCsv,
    ])
        ->expectsOutputToContain('Impor berhasil disimpan ke database')
        ->assertSuccessful();

    expect(MenuItem::count())->toBe(2)
        ->and(Category::count())->toBe(2)
        ->and(MenuItem::where('name', 'Caramel Macchiato')->first()->price)->toBe(25000);
});

test('backup command runs and creates backup snapshot', function () {
    $this->artisan('umco:backup')
        ->assertSuccessful();

    $backupDir = storage_path('app/backups');
    expect(File::exists($backupDir))->toBeTrue();
});
