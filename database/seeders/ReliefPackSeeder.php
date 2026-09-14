<?php

namespace Database\Seeders;

use App\Models\ReliefPack;
use Illuminate\Database\Seeder;

class ReliefPackSeeder extends Seeder
{
    /**
     * current_stock always starts at 0 here — per the process doc, the
     * ONLY way stock goes up is through a recorded receipt
     * (ReliefPackController::receiveStock / PackReceipt). PackReceiptSeeder
     * runs right after this one and brings stock to a realistic level.
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Family Food Pack',
                'description' => 'Rice, canned goods, noodles, and other food staples for one family for about a week.',
            ],
            [
                'name' => 'Hygiene Kit',
                'description' => 'Soap, toothpaste, toothbrush, sanitary items, and other personal hygiene supplies.',
            ],
            [
                'name' => 'Family Kitchen Kit',
                'description' => 'Basic cooking and eating utensils for households that lost kitchenware during a disaster.',
            ],
            [
                'name' => 'Emergency Shelter Kit',
                'description' => 'Tarpaulin, rope, and basic repair materials for temporary shelter repair.',
            ],
            [
                'name' => 'Family Water Kit',
                'description' => 'Water containers and water purification tablets for a family.',
            ],
        ];

        foreach ($packs as $pack) {
            ReliefPack::firstOrCreate(
                ['name' => $pack['name']],
                [
                    'description' => $pack['description'],
                ]
            );
        }
    }
}
