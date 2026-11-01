<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['slug' => 'vmbo', 'name' => 'VMBO'],
            ['slug' => 'mavo', 'name' => 'MAVO'],
            ['slug' => 'havo', 'name' => 'HAVO'],
            ['slug' => 'vwo', 'name' => 'VWO'],
            ['slug' => 'university', 'name' => 'Universiteit'],
        ];

        foreach ($levels as $index => $level) {
            Level::updateOrCreate(['slug' => $level['slug']], $level + ['sort_order' => $index + 1]);
        }
    }
}
