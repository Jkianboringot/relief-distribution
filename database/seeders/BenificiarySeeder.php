<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Benificiary;
use App\Models\Inventory;
use Illuminate\Database\Seeder;

class BenificiarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Benificiary::factory()->count(10)->create();
    }
}