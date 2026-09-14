<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\DistributionSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistributionTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $verifier = User::role('lgustaff')->inRandomOrder()->first() ?? User::first();

        DistributionSchedule::where('status', 'completed')
            ->with('reliefPack')
            ->get()
            ->each(function (DistributionSchedule $schedule) use ($verifier) {
                $barangay = Barangay::where('name', $schedule->barangay)->first();

                if (! $barangay) {
                    return;
                }

                $candidates = Beneficiary::where('barangay_id', $barangay->id)
                    ->where('status', 'unclaimed')
                    ->inRandomOrder()
                    ->get();

                $claimCount = min(
                    $schedule->planned_quantity,
                    $schedule->reliefPack->current_stock,
                    $candidates->count()
                );

                $candidates->take($claimCount)->each(function (Beneficiary $beneficiary) use ($schedule, $verifier) {
                    DB::transaction(function () use ($beneficiary, $schedule, $verifier) {
                        $reliefPack = $schedule->reliefPack;

                        // if (method_exists($reliefPack, 'decrementStock')) {
                        //     $reliefPack->decrementStock(1);
                        // } else {
                        //     $reliefPack->decrement('current_stock', 1);
                        // }

                        $schedule->transactions()->create([
                            'beneficiary_id' => $beneficiary->id,
                            'quantity_boxes' => 1,
                            'verified_by' => $verifier->id,
                            'verification_timestamp' => $schedule->date,
                            'status' => 'claimed',
                        ]);

                        $beneficiary->update(['status' => 'claimed']);
                    });
                });
            });
    }
}