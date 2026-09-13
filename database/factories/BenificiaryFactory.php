<?php

namespace Database\Factories;

use App\Models\Benificiary;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Benificiary>
 */
class BenificiaryFactory extends Factory
{
    protected $model = Benificiary::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'barangay_id' => Barangay::factory(),

            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),

            'birthdate' => fake()->dateTimeBetween(
                '-80 years',
                '-18 years'
            )->format('Y-m-d'),

            'gender' => fake()->randomElement([
                'male',
                'female',
            ]),

            'address' => fake()->address(),

            'household_members' => fake()->numberBetween(1, 10),

            'qr_code' => fake()->unique()->uuid(),

            'status' => 'unclaimed',

            'registered_by' => User::factory(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}