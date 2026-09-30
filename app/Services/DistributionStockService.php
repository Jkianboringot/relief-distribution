<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Benificiary;
use App\Models\DistributionBeneficiaryAllocation;
use App\Models\DistributionReliefStock;
use App\Models\DistributionSchedule;
use App\Models\ReliefPack;
use App\Models\ReliefStock;
use Illuminate\Support\Facades\DB;

/**
 * OUT side of the ledger.
 *
 * A schedule no longer states its own pack quantities up front. Instead:
 *   distribution_beneficiary_allocations (who gets what, how much)
 *     -> summed per pack ->
 *   distribution_relief_stocks.quantity (kept in sync, read-only from the UI's point of view)
 *
 * Stock per pack, globally = SUM(relief_stocks) - SUM(distribution_relief_stocks) across ALL schedules.
 * That global check still happens here, now triggered by allocation changes instead of schedule creation.
 */
class DistributionStockService
{
    /**
     * Schedule header only — no pack lines. Packs are attached later via allocations.
     */
    public function distributionStore(array $schedule): DistributionSchedule
    {
        return DB::transaction(function () use ($schedule) {
            $model = new DistributionSchedule();
            $model->fill($schedule);
            $model->created_by = auth()->id();
            $model->save();

            return $model;
        });
    }

    public function distributionUpdate(DistributionSchedule $schedule, array $data): DistributionSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            return $schedule;
        });
    }

    /**
     * Deleting a schedule frees everything: its allocations and its relief stock lines.
     *
     * @throws \DomainException if beneficiaries already have transactions on this schedule
     */
    public function distributionDelete(DistributionSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            if ($schedule->transactions()->exists()) {
                throw new \DomainException('Cannot delete a schedule that already has beneficiary transactions.');
            }

            $schedule->allocations()->delete();
            $schedule->reliefStock()->delete();
            $schedule->delete();
        });
    }

    public function distributionSetStatus(DistributionSchedule $schedule, string $status): DistributionSchedule
    {
        return DB::transaction(function () use ($schedule, $status) {
            $schedule = DistributionSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            $allowed = [
                'pending' => ['ongoing'],
                'ongoing' => ['completed'],
            ];

            if (! in_array($status, $allowed[$schedule->status] ?? [], true)) {
                throw new \DomainException("Cannot change a {$schedule->status} distribution to {$status}.");
            }

            if ($status === 'ongoing' && $schedule->reliefStock()->sum('quantity') < 1) {
                throw new \DomainException('Allocate at least one beneficiary before starting this distribution.');
            }

            $schedule->status = $status;
            $schedule->save();

            return $schedule;
        });
    }

    /**
     * $allocations = [
     *   ['beneficiary_id' => 900, 'relief_pack_id' => 1, 'quantity' => 2],
     *   ...
     * ]
     *
     * @throws \DomainException            beneficiary not in this schedule's barangay, or would reduce
     *                                      a pack below what's already been claimed
     * @throws InsufficientStockException  the new total for a pack exceeds what's globally available
     */
    public function setBeneficiaryAllocations(DistributionSchedule $schedule, array $allocations, int $assignedBy): void
    {
        DB::transaction(function () use ($schedule, $allocations, $assignedBy) {
            $schedule = DistributionSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            // Eligibility, as confirmed: barangay residency only.
            $beneficiaryIds = collect($allocations)->pluck('beneficiary_id')->unique();
            $ineligible = Benificiary::whereIn('id', $beneficiaryIds)
                ->where('barangay_id', '!=', $schedule->barangay_id)
                ->pluck('id');

            if ($ineligible->isNotEmpty()) {
                throw new \DomainException('One or more beneficiaries are not registered in this barangay.');
            }

            // Full picture per (beneficiary, pack): existing rows, with the incoming
            // batch overriding matching pairs (this is how an edit to an existing
            // allocation is represented — same beneficiary+pack, new quantity).
            $existingRows = $schedule->allocations()->get(['beneficiary_id', 'relief_pack_id', 'quantity']);
            $merged = $existingRows->keyBy(fn ($r) => "{$r->beneficiary_id}:{$r->relief_pack_id}");

            foreach ($allocations as $row) {
                $merged->put("{$row['beneficiary_id']}:{$row['relief_pack_id']}", (object) $row);
            }

            // New total per pack, across the WHOLE schedule, after this batch applies.
            $newTotalsByPack = $merged->groupBy('relief_pack_id')
                ->map(fn ($rows) => (int) collect($rows)->sum('quantity'));

            // Never let an edit shrink a pack's total below what's already been claimed
            // against it on this schedule — that stock is already physically gone.
            $alreadyGiven = $schedule->transactionItems()
                ->whereIn('relief_pack_id', $newTotalsByPack->keys())
                ->whereHas('transaction', fn ($q) => $q->where('status', 'claimed'))
                ->selectRaw('relief_pack_id, SUM(quantity) as total')
                ->groupBy('relief_pack_id')
                ->pluck('total', 'relief_pack_id');

            foreach ($newTotalsByPack as $packId => $newTotal) {
                $given = (int) ($alreadyGiven[$packId] ?? 0);

                if ($newTotal < $given) {
                    $name = ReliefPack::find($packId)?->name ?? "Pack #{$packId}";
                    throw new \DomainException(
                        "Can't reduce {$name} below {$given}, since that many have already been claimed."
                    );
                }
            }

            // Reuses the same global-stock guard used when schedule pack lines were edited
            // directly. Passing $schedule as $existing frees this schedule's OWN previously
            // committed amount before checking the new total against what's really available.
            $lines = $newTotalsByPack->map(fn ($qty, $packId) => [
                'relief_pack_id' => $packId,
                'quantity' => $qty,
            ])->values()->all();

            $this->guardStock($lines, $schedule);

            // Safe to write.
            foreach ($allocations as $row) {
                $schedule->allocations()->updateOrCreate(
                    ['beneficiary_id' => $row['beneficiary_id'], 'relief_pack_id' => $row['relief_pack_id']],
                    ['quantity' => $row['quantity'], 'assigned_by' => $assignedBy]
                );
            }

            // Sync distribution_relief_stocks.quantity to match — this is what makes
            // "stock = how many beneficiaries were added" actually true everywhere else
            // in the app (Show.tsx's Planned/Remaining, remainingForPack(), etc.).
            foreach ($newTotalsByPack as $packId => $qty) {
                $schedule->reliefStock()->updateOrCreate(
                    ['relief_pack_id' => $packId],
                    ['quantity' => $qty]
                );
            }
        });
    }

    public function removeBeneficiaryAllocation(DistributionBeneficiaryAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            $schedule = DistributionSchedule::whereKey($allocation->distribution_schedule_id)
                ->lockForUpdate()->firstOrFail();

            $packId = $allocation->relief_pack_id;
            $allocation->delete();

            $newTotal = (int) $schedule->allocations()->where('relief_pack_id', $packId)->sum('quantity');

            $given = (int) $schedule->transactionItems()
                ->where('relief_pack_id', $packId)
                ->whereHas('transaction', fn ($q) => $q->where('status', 'claimed'))
                ->sum('quantity');

            // Never let the line drop below what's already been physically given out.
            $floor = max($newTotal, $given);

            if ($floor > 0) {
                $schedule->reliefStock()->where('relief_pack_id', $packId)->update(['quantity' => $floor]);
            } else {
                // Nobody left allocated to this pack, and nothing claimed against it yet — drop the line.
                $schedule->reliefStock()->where('relief_pack_id', $packId)->delete();
            }
        });
    }

    /**
     * Checks a set of per-pack totals against real global availability.
     * When $existing is passed, that schedule's OWN current committed amount is
     * added back as "free" before comparing — i.e. this schedule's prior claim on
     * stock doesn't count against itself while being resized.
     *
     * @throws InsufficientStockException
     */
    private function guardStock(array $lines, ?DistributionSchedule $existing = null): void
    {
        $requested = collect($lines)
            ->groupBy('relief_pack_id')
            ->map(fn ($rows) => (int) collect($rows)->sum('quantity'));

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
                    "Not enough stock for {$name}: allocating {$want} in total, only {$available} available."
                );
            }
        }
    }
}