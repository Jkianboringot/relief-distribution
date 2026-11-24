<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;

/**
 * The 11 municipalities of Catanduanes.
 */
class MunicipalitySeeder extends Seeder
{
    public const MUNICIPALITIES = [
        'Bagamanoc',
        'Baras',
        'Bato',
        'Caramoran',
        'Gigmoto',
        'Pandan',
        'Panganiban',
        'San Andres',
        'San Miguel',
        'Viga',
        'Virac',
    ];

    public function run(): void
    {
        foreach (self::MUNICIPALITIES as $name) {
            Municipality::firstOrCreate(['name' => $name]);
        }
    }
}