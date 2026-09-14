<?php

namespace Database\Seeders;

use App\Models\PackReceipt;
use App\Models\ReliefPack;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackReceiptSeeder extends Seeder
{
    /**
     * Mirrors ReliefPackController::receiveStock — every receipt row is
     * paired with a call to ReliefPack::incrementStock() so current_stock
     * always agrees with the sum of its receipts (minus anything claimed
     * later by DistributionTransactionSeeder).
     */
    public function run(): void
    {
        $sources = ['DSWD', 'Provincial Office', 'Municipal LGU', 'Donation'];
        // ->role() is Spatie's HasRoles scope, not a `role` column.
        $receiver = User::role('lgustaff')->inRandomOrder()->first()
            ?? User::first();

        ReliefPack::all()->each(function (ReliefPack $reliefPack) use ($sources, $receiver) {
            // 2–3 receipts per pack type, received over the last couple of months.
            $receiptCount = fake()->numberBetween(2, 3);

            for ($i = 0; $i < $receiptCount; $i++) {
                $quantity = fake()->numberBetween(50, 300);
                $dateReceived = fake()->dateTimeBetween('-2 months', '-1 week');

                DB::transaction(function () use ($reliefPack, $sources, $receiver, $quantity, $dateReceived) {
                    PackReceipt::create([
                        'relief_pack_id' => $reliefPack->id,
                        'source_name' => fake()->randomElement($sources),
                        'quantity_received' => $quantity,
                        'date_received' => $dateReceived,
                        'received_by' => $receiver->id,
                    ]);

                    if (method_exists($reliefPack, 'incrementStock')) {
                        $reliefPack->incrementStock($quantity);
                    } else {
                        $reliefPack->increment('current_stock', $quantity);
                    }
                });
            }
        });
    }
}
