<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Inventory;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Barangay::factory()->count(1)->create();
    }
}