<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            Table::firstOrCreate(
                ['label' => "Meja {$num}"],
                [
                    'token' => 'tbl-' . Str::lower($num) . '-' . Str::random(6),
                    'is_active' => true,
                ]
            );
        }

        Table::firstOrCreate(
            ['token' => 'antar'],
            [
                'label' => 'Pesanan Antar (Dosen/Rektorat)',
                'is_active' => true,
            ]
        );
    }
}
