<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\DistributionBeneficiaryAllocation;
use App\Models\DistributionReliefStock;
use App\Models\DistributionSchedule;
use App\Models\ReliefPack;
use App\Models\ReliefStock;
use Illuminate\Support\Facades\DB;

/**
 * OUT side of the ledger:
 *   distribution_schedules (header) -> distribution_relief_stocks (one row per pack line)
 * Stock per pack = SUM(relief_stocks) - SUM(distribution_relief_stocks).
 *
 * Methods do not catch exceptions. DB::transaction() rolls back on throw,
 * and the controller decides what to show the user.
 */
class DistributionStockService
{
    /**
     * $data = [
     *   'schedule'   => ['title' => '...', 'date' => 'Y-m-d', 'location' => '...', 'barangay_id' => 1],
     *   'reliefList' => [['relief_pack_id' => 1, 'quantity' => 50, 'entitlement_per_beneficiary' => 2], ...],
     * ]
     *
     * @throws InsufficientStockException
     */
    public function distributionStore(array $data): DistributionSchedule
    {
        return DB::transaction(function () use ($data) {
            $lines = $this->lines($data['reliefList']);

            $this->guardStock($lines);

            $schedule = new DistributionSchedule();
            $schedule->fill($data['schedule']);
            $schedule->created_by = auth()->id();
            $schedule->save();

            $schedule->reliefStock()->createMany($lines);

            return $schedule;
        });
    }

    /**
     * Same $data shape as distributionStore. created_by and status are untouched.
     *
     * @throws InsufficientStockException
     */
    public function distributionUpdate(DistributionSchedule $schedule, array $data): DistributionSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $lines = $this->lines($data['reliefList']);

            $this->guardStock($lines, $schedule);

            $schedule->update($data['schedule']);
            $schedule->reliefStock()->delete();
            $schedule->reliefStock()->createMany($lines);

            return $schedule;
        });
    }

    /**
     * Deleting an OUT record only returns stock, so no stock check is needed.
     *
     * @throws \DomainException if the schedule is completed,
     *                          or if beneficiaries already have transactions on it
     */
    public function distributionDelete(DistributionSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            // Lock so a status change can't slip in while we are deleting.
            $schedule = DistributionSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            if ($schedule->status === 'completed') {
                throw new \DomainException('Cannot delete a completed distribution.');
            }

            if ($schedule->transactions()->exists()) {
                throw new \DomainException('Cannot delete a schedule that already has beneficiary transactions.');
            }

            $schedule->reliefStock()->delete();
            $schedule->delete();
        });
    }

    /**
     * Allowed flow: pending -> ongoing -> completed.
     *
     * @throws \DomainException
     */
    public function distributionSetStatus(DistributionSchedule $schedule, string $status): DistributionSchedule
    {
        return DB::transaction(function () use ($schedule, $status) {
            // Lock so a claim in progress can't overlap with a status change.
            $schedule = DistributionSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            $allowed = [
                'pending' => ['ongoing'],
                'ongoing' => ['completed'],
            ];

            if (! in_array($status, $allowed[$schedule->status] ?? [], true)) {
                throw new \DomainException("Cannot change a {$schedule->status} distribution to {$status}.");
            }

            if ($status === 'ongoing' && $schedule->reliefStock()->sum('quantity') < 1) {
                throw new \DomainException('Add relief stock to this distribution before starting it.');
            }

            if ($status === 'completed') {
                $this->guardFullyGivenOut($schedule);
            }

            $schedule->status = $status;
            $schedule->save();

            return $schedule;
        });
    }

    /**
     * A distribution can only be completed when every relief pack assigned to it
     * has been fully given out (nothing left over).
     *
     * Given out per pack = (number of beneficiary transactions) x entitlement_per_beneficiary.
     * Must be called inside a transaction.
     *
     * @throws \DomainException listing the packs that still have items left
     */
    private function guardFullyGivenOut(DistributionSchedule $schedule): void
    {
        $lines = $schedule->reliefStock()
            ->get(['relief_pack_id', 'quantity', 'entitlement_per_beneficiary']);

        if ($lines->isEmpty()) {
            throw new \DomainException('This distribution has no relief stock, so it cannot be completed.');
        }

        // ASSUMPTION: every row in transactions() is one beneficiary who already received
        // their entitlement. If transactions have a status column (e.g. pending/claimed),
        // filter it here, for example: ->where('status', 'claimed')
        $beneficiariesServed = $schedule->transactions()->count();

        $names = ReliefPack::whereIn('id', $lines->pluck('relief_pack_id'))->pluck('name', 'id');

        $leftovers = [];

        foreach ($lines as $line) {
            $givenOut = $beneficiariesServed * (int) $line->entitlement_per_beneficiary;
            $remaining = (int) $line->quantity - $givenOut;

            if ($remaining > 0) {
                $name = $names[$line->relief_pack_id] ?? "Pack #{$line->relief_pack_id}";
                $leftovers[] = "{$name} ({$remaining} left)";
            }
        }

        if ($leftovers !== []) {
            throw new \DomainException(
                'Cannot complete this distribution until all relief packs are given out. Remaining: '
                . implode(', ', $leftovers) . '.'
            );
        }
    }

    /**
     * Checks every requested pack has enough stock.
     * When editing, pass the existing schedule: its old lines count as free stock again.
     * Must be called inside a transaction.
     *
     * @throws InsufficientStockException
     */
    private function guardStock(array $lines, ?DistributionSchedule $existing = null): void
    {
        $requested = collect($lines)
            ->groupBy('relief_pack_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $old = $existing
            ? $existing->reliefStock()
                ->get(['relief_pack_id', 'quantity'])
                ->groupBy('relief_pack_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'))
            : collect();

        $packIds = $requested->keys()->merge($old->keys())->unique()->values();

        if ($packIds->isEmpty()) {
            return;
        }

        // Lock the pack rows so two requests for the same pack run one after the other.
        $packs = ReliefPack::whereIn('id', $packIds)->lockForUpdate()->get()->keyBy('id');

        $received = ReliefStock::whereIn('relief_pack_id', $packIds)
            ->selectRaw('relief_pack_id, SUM(quantity) as total')
            ->groupBy('relief_pack_id')
            ->pluck('total', 'relief_pack_id');

        $distributed = DistributionReliefStock::whereIn('relief_pack_id', $packIds)
            ->selectRaw('relief_pack_id, SUM(quantity) as total')
            ->groupBy('relief_pack_id')
            ->pluck('total', 'relief_pack_id');

        foreach ($packIds as $id) {
            $available = (int) ($received[$id] ?? 0)
                - (int) ($distributed[$id] ?? 0)
                + (int) ($old[$id] ?? 0);

            $want = (int) ($requested[$id] ?? 0);

            if ($want > $available) {
                $name = $packs[$id]->name ?? "Pack #{$id}";
                throw new InsufficientStockException(
                    "Not enough stock for {$name}: requested {$want}, only {$available} available."
                );
            }
        }
    }

    private function lines(array $reliefList): array
    {
        return collect($reliefList)
            ->map(fn (array $item) => [
                'relief_pack_id' => $item['relief_pack_id'],
                'quantity' => $item['quantity'],
                'entitlement_per_beneficiary' => $item['entitlement_per_beneficiary'] ?? 1,
            ])
            ->all();
    }

public function setBeneficiaryAllocations(DistributionSchedule $schedule, array $allocations, int $assignedBy): void
{
    DB::transaction(function () use ($schedule, $allocations, $assignedBy) {
        foreach ($allocations as $row) {
            $schedule->allocations()->updateOrCreate(
                [
                    'beneficiary_id' => $row['beneficiary_id'],
                    'relief_pack_id' => $row['relief_pack_id'],
                ],
                [
                    'quantity' => $row['quantity'],
                    'assigned_by' => $assignedBy,
                ]
            );
        }
    });
}

public function removeBeneficiaryAllocation(DistributionBeneficiaryAllocation $allocation): void
{
    $allocation->delete();
}
}