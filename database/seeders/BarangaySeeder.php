<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    // Assumes a `barangays` table with `id` + `name` — nothing you've
    // shared creates one yet, but barangay_id needs it to exist.
    public const NAMES = [
        'San Roque',
        'Santa Elena',
        'Concepcion',
        'Francia',
        'Cavinitan',
        'San Isidro',
        'Marilima',
        'Bigaa',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Barangay::firstOrCreate(['name' => $name,'code'=>$name]);
        }
    }
}