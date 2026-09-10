<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,

            barangaySeeder::class,
            // EmployeeSeeder::class,
            UserSeeder::class,
            InventorySeeder::class,
            // SaleSeeder::class,
            ]
        );
    
    }
}
