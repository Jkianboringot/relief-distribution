<?php

namespace Database\Seeders;

use App\Models\DistributionSchedule;
use App\Models\ReliefPack;
use App\Models\User;
use Illuminate\Database\Seeder;

class DistributionScheduleSeeder extends Seeder
{
    private const BARANGAYS = [
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
        // No separate "admin" role — lgustaff covers both admin and staff.
        $admin = User::role('lgustaff')->first() ?? User::first();
        $reliefPacks = ReliefPack::all();

        foreach (self::BARANGAYS as $barangay) {
            // One completed schedule in the past, one upcoming/pending schedule.
            $this->makeSchedule($barangay, $reliefPacks, $admin, 'completed', fake()->dateTimeBetween('-6 weeks', '-1 week'));
            $this->makeSchedule($barangay, $reliefPacks, $admin, 'pending', fake()->dateTimeBetween('+1 week', '+5 weeks'));
        }
    }

    private function makeSchedule(string $barangay, $reliefPacks, User $admin, string $status, \DateTime $date): void
    {
        $reliefPack = $reliefPacks->random();

        // Store::validate() requires planned_quantity <= current_stock at
        // creation time, so cap the allocation to what's actually on hand.
        $maxAllocatable = max(10, (int) floor($reliefPack->current_stock * 0.3));
        $plannedQuantity = fake()->numberBetween(10, max(10, $maxAllocatable));

        DistributionSchedule::create([
            'title' => "Relief Distribution — Brgy. {$barangay}",
            'date' => $date,
            'location' => "Barangay {$barangay} Covered Court",
            'barangay' => $barangay,
            'relief_pack_id' => $reliefPack->id,
            'planned_quantity' => $plannedQuantity,
            'status' => $status,
            'created_by' => $admin->id,
        ]);
    }
}
