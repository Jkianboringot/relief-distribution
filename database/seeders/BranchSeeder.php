<?php

namespace Database\Seeders;

use App\Models\barangay;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class barangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        barangay::insert([
            ['name' => 'mb1', 'location' => 'Calatagan', 'barangay_type' => 'barangay'],
            ['name' => 'mb2', 'location' => 'San Andres', 'barangay_type' => 'franchise'],
            ['name' => 'mb3', 'location' => 'San Juan', 'barangay_type' => 'center']
        ])
        ;
        $barangays = barangay::all();
        $products = Product::all();

        // if ($barangays->isEmpty() || $products->isEmpty()) {
        //     $this->command->warn('No barangays or products found — seed those tables first.');

        // return;
        // }

        foreach ($barangays as $barangay) {
            $pivotData = [];

            foreach ($products as $product) {
                $pivotData[$product->id] = [
                    'quantity' => rand(10, 100), // starting stock — adjust as needed
                ];
            }

            $barangay->products()->syncWithoutDetaching($pivotData);

            $this->command->info("Seeded {$products->count()} products for barangay: {$barangay->location}");
        }
    }
}
