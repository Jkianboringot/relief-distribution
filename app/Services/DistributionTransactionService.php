<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Benificiary;
use App\Models\DistributionSchedule;
use App\Models\DistributionTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DistributionTransactionService
{
    /**
     * @throws \DomainException            not eligible / wrong barangay / already claimed / schedule not ongoing / no packs configured
     * @throws InsufficientStockException  any pack on the schedule is out of stock
     */
    public function claim(DistributionSchedule $schedule, string $qrCode, int $verifiedBy): DistributionTransaction
    {
        try {
            return DB::transaction(function () use ($schedule, $qrCode, $verifiedBy) {
                // Lock the schedule so two simultaneous scans can't both take the last boxes.
                $schedule = DistributionSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

                if ($schedule->status !== 'ongoing') {
                    throw new \DomainException('This distribution is not open for claiming.');
                }

                $beneficiary = Benificiary::where('qr_code', $qrCode)->first();

                if (! $beneficiary) {
                    throw new \DomainException('QR code not recognized.');
                }

                if ((int) $beneficiary->barangay_id !== (int) $schedule->barangay_id) {
                    throw new \DomainException('This family head is not registered in this barangay.');
                }

                $alreadyClaimed = $schedule->transactions()
                    ->where('beneficiary_id', $beneficiary->id)
                    ->exists();

                if ($alreadyClaimed) {
                    throw new \DomainException("{$beneficiary->full_name} has already claimed.");
                }

                // Every pack type on this schedule, and how many boxes were planned for it in total.
                $planned = $schedule->reliefStock()
                    ->get(['relief_pack_id', 'quantity'])
                    ->groupBy('relief_pack_id')
                    ->map(fn ($rows) => (int) $rows->sum('quantity'));

                if ($planned->isEmpty()) {
                    throw new \DomainException('No relief packs configured for this distribution.');
                }

                // How many boxes of each pack type have already been given out (claimed only).
                $given = $schedule->transactionItems()
                    ->whereIn('relief_pack_id', $planned->keys())
                    ->whereHas('transaction', fn ($q) => $q->where('status',ClaimStatus::Claim->value))
                    ->selectRaw('relief_pack_id, SUM(quantity) as total')
                    ->groupBy('relief_pack_id')
                    ->pluck('total', 'relief_pack_id');

                // A claim is 1 box of EVERY pack type on the schedule (a bundle).
                // If any single pack is out of stock, the whole bundle can't be released.
                foreach ($planned as $packId => $totalPlanned) {
                    $remaining = $totalPlanned - (int) ($given[$packId] ?? 0);

                    if ($remaining < 1) {
                        $packName = $schedule->reliefStock()
                            ->with('reliefPack:id,name')
                            ->where('relief_pack_id', $packId)
                            ->first()?->reliefPack?->name ?? "Pack #{$packId}";

                        throw new InsufficientStockException("Stock is already zero for {$packName}.");
                    }
                }

                $transaction = $schedule->transactions()->create([
                    'beneficiary_id'         => $beneficiary->id,
                    'quantity_boxes'         => $planned->count(),
                    'verified_by'            => $verifiedBy,
                    'verification_timestamp' => now(),
                    'status'                 => ClaimStatus::Claim->value,
                ]);

                foreach ($planned->keys() as $packId) {
                    $transaction->items()->create([
                        'relief_pack_id' => $packId,
                        'quantity' => 1,
                    ]);
                }

                return $transaction->load(
                    'beneficiary.barangay:id,name',
                    'beneficiary:id,first_name,middle_name,last_name,barangay_id,household_members',
                    'items.reliefPack:id,name',
                );
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new \DomainException('This family head has already claimed.');
            }
            throw $e;
        }
    }

    public function reverse(DistributionTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Items cascade-delete via the FK, freeing all pack stock automatically.
            $transaction->delete();
        });
    }
}