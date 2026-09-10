<?php

namespace Database\Factories;

use App\Enums\InOutType;
use App\Enums\StockMovementType;
use App\Models\barangay;
use App\Models\Barangay as ModelsBarangay;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarangayFactory extends Factory
{
    protected $model = ModelsBarangay::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'code' => $this->faker->randomFloat(2, 50, 200),

        ];
    }
}