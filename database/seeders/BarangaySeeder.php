<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * All 315 barangays of Catanduanes (11 municipalities), per the 2020 PSGC
 * list. Many barangay names repeat across municipalities (e.g. "San Roque",
 * "Salvacion"), so `code` is built from municipality + barangay to stay
 * unique, e.g. "virac-san-roque".
 */
class BarangaySeeder extends Seeder
{
    public const BARANGAYS = [
     
        'Baras' => [
            'Abihao',
            'Agban',
            'Bagong Sirang',
            'Batolinao',
            'Benticayan',
            'Buenavista',
            'Caragumihan',
            'Danao',
            'Eastern Poblacion',
            'Ginitligan',
            'Guinsaanan',
            'J. M. Alberto',
            'Macutal',
            'Moning',
            'Nagbarorong',
            'Osmeña',
            'P. Teston',
            'Paniquihan',
            'Puraran',
            'Putsan',
            'Quezon',
            'Rizal',
            'Sagrada',
            'Salvacion',
            'San Lorenzo',
            'San Miguel',
            'Santa Maria',
            'Tilod',
            'Western Poblacion',
        ],
        'Virac' => [
            'Antipolo del Norte',
            'Antipolo del Sur',
            'Balite',
            'Batag',
            'Bigaa',
            'Buenavista',
            'Buyo',
            'Cabihian',
            'Calabnigan',
            'Calampong',
            'Calatagan Proper',
            'Calatagan Tibang',
            'Capilihan',
            'Casoocan',
            'Cavinitan',
            'Concepcion',
            'Constantino',
            'Danicop',
            'Dugui San Isidro',
            'Dugui San Vicente',
            'Dugui Too',
            'F. Tacorda Village',
            'Francia',
            'Gogon Centro',
            'Gogon Sirangan',
            'Hawan Grande',
            'Hawan Ilaya',
            'Hicming',
            'Ibong Sapa',
            'Igang',
            'Juan M. Alberto',
            'Lanao',
            'Magnesia del Norte',
            'Magnesia del Sur',
            'Marcelo Alberto',
            'Marilima',
            'Pajo Baguio',
            'Pajo San Isidro',
            'Palnab del Norte',
            'Palnab del Sur',
            'Palta Big',
            'Palta Salvacion',
            'Palta Small',
            'Rawis',
            'Salvacion',
            'San Isidro Village',
            'San Jose',
            'San Juan',
            'San Pablo',
            'San Pedro',
            'San Roque',
            'San Vicente',
            'Santa Cruz',
            'Santa Elena',
            'Santo Cristo',
            'Santo Domingo',
            'Santo Niño',
            'Simamla',
            'Sogod-Simamla',
            'Sogod-Tibgao',
            'Talisoy',
            'Tubaon',
            'Valencia',
        ],
    ];

    public function run(): void
    {
        foreach (self::BARANGAYS as $municipality => $names) {
            foreach ($names as $name) {
                Barangay::firstOrCreate(
                    ['code' => Str::slug($name)],
                    ['name' => $name],
                );
            }
        }
    }
}