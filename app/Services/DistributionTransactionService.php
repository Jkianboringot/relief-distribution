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
     * @throws \DomainException            not eligible / wrong barangay / already claimed / not on the
     *                                      list for this schedule / schedule not ongoing
     * @throws InsufficientStockException  any allocated pack can't fulfil what this beneficiary is owed
     */
    public function claim(DistributionSchedule $schedule, string $qrCode, int $verifiedBy): DistributionTransaction
    {
        try {
            return DB::transaction(function () use ($schedule, $qrCode, $verifiedBy) {
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

                // Guest-list only: no allocation rows for this beneficiary = cannot claim, period.
                $allocations = $schedule->allocations()
                    ->where('beneficiary_id', $beneficiary->id)
                    ->with('reliefPack:id,name')
                    ->get();

                if ($allocations->isEmpty()) {
                    throw new \DomainException("{$beneficiary->full_name} is not on the distribution list for this schedule.");
                }

                $given = $schedule->transactionItems()
                    ->whereIn('relief_pack_id', $allocations->pluck('relief_pack_id'))
                    ->whereHas('transaction', fn ($q) => $q->where('status', ClaimStatus::Claim->value))
                    ->selectRaw('relief_pack_id, SUM(quantity) as total')
                    ->groupBy('relief_pack_id')
                    ->pluck('total', 'relief_pack_id');

                $stock = $schedule->reliefStock()->get(['relief_pack_id', 'quantity'])->keyBy('relief_pack_id');

                foreach ($allocations as $alloc) {
                    $planned = (int) ($stock[$alloc->relief_pack_id]->quantity ?? 0);
                    $remaining = $planned - (int) ($given[$alloc->relief_pack_id] ?? 0);

                    if ($remaining < $alloc->quantity) {
                        $packName = $alloc->reliefPack?->name ?? "Pack #{$alloc->relief_pack_id}";
                        throw new InsufficientStockException(
                            "Not enough {$packName} left: {$beneficiary->full_name} is allocated {$alloc->quantity}, only {$remaining} remain."
                        );
                    }
                }

                $transaction = $schedule->transactions()->create([
                    'beneficiary_id'         => $beneficiary->id,
                    'quantity_boxes'         => (int) $allocations->sum('quantity'),
                    'verified_by'            => $verifiedBy,
                    'verification_timestamp' => now(),
                    'status'                 => ClaimStatus::Claim->value,
                ]);

                foreach ($allocations as $alloc) {
                    $transaction->items()->create([
                        'relief_pack_id' => $alloc->relief_pack_id,
                        'quantity' => $alloc->quantity,
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
            $transaction->delete();
        });
    }
}