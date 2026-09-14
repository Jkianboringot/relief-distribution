<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        $registrar = User::role('lgustaff')->inRandomOrder()->first() ?? User::first();

        Barangay::all()->each(function (Barangay $barangay) use ($registrar) {
            for ($i = 0; $i < 15; $i++) {
                Beneficiary::create([
                    'barangay_id' => $barangay->id,
                    'first_name' => fake()->firstName(),
                    'middle_name' => fake()->boolean(70) ? fake()->lastName() : null,
                    'last_name' => fake()->lastName(),
                    'birthdate' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                    'gender' => fake()->randomElement(['male', 'female']),
                    'address' => fake()->streetAddress(),
                    'household_members' => fake()->numberBetween(1, 8),
                    'qr_code' => (string) Str::uuid(),
                    'status' => 'unclaimed',
                    'registered_by' => $registrar->id,
                ]);
            }
        });
    }
}