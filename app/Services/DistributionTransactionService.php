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
     * @throws \DomainException            not eligible / wrong barangay / already claimed / schedule not ongoing / not entitled to anything
     * @throws InsufficientStockException  any entitled pack can't fulfil this beneficiary's full entitlement
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

                // Every pack line on the schedule, with its default per-beneficiary entitlement.
                $lines = $schedule->reliefStock()
                    ->with('reliefPack:id,name')
                    ->get(['id', 'relief_pack_id', 'quantity', 'entitlement_per_beneficiary']);

                if ($lines->isEmpty()) {
                    throw new \DomainException('No relief packs configured for this distribution.');
                }

                // This beneficiary's pre-assigned overrides for THIS schedule, keyed by pack.
                // A pack with no override here just uses the pack line's default entitlement.
                $overrides = $schedule->allocations()
                    ->where('beneficiary_id', $beneficiary->id)
                    ->pluck('quantity', 'relief_pack_id');

                // How much of each pack has already been handed out (claimed only).
                $given = $schedule->transactionItems()
                    ->whereIn('relief_pack_id', $lines->pluck('relief_pack_id'))
                    ->whereHas('transaction', fn ($q) => $q->where('status', ClaimStatus::Claim->value))
                    ->selectRaw('relief_pack_id, SUM(quantity) as total')
                    ->groupBy('relief_pack_id')
                    ->pluck('total', 'relief_pack_id');

                // Resolve THIS beneficiary's real entitlement per pack: override wins,
                // the pack line's default is the fallback. A resolved entitlement of 0
                // means this beneficiary simply isn't owed that pack at all.
                $entitlements = $lines->mapWithKeys(function ($line) use ($overrides) {
                    $qty = $overrides->has($line->relief_pack_id)
                        ? (int) $overrides[$line->relief_pack_id]
                        : (int) $line->entitlement_per_beneficiary;

                    return [$line->relief_pack_id => $qty];
                })->filter(fn ($qty) => $qty > 0);

                if ($entitlements->isEmpty()) {
                    throw new \DomainException('This family head is not entitled to any pack in this distribution.');
                }

                // Check EVERY entitled pack can be fully covered BEFORE releasing anything.
                foreach ($entitlements as $packId => $entitlement) {
                    $line = $lines->firstWhere('relief_pack_id', $packId);
                    $remaining = (int) $line->quantity - (int) ($given[$packId] ?? 0);

                    if ($remaining < $entitlement) {
                        $packName = $line->reliefPack?->name ?? "Pack #{$packId}";
                        throw new InsufficientStockException(
                            "Not enough {$packName} left: this family head is entitled to {$entitlement}, only {$remaining} remain."
                        );
                    }
                }

                $transaction = $schedule->transactions()->create([
                    'beneficiary_id'         => $beneficiary->id,
                    'quantity_boxes'         => (int) $entitlements->sum(),
                    'verified_by'            => $verifiedBy,
                    'verification_timestamp' => now(),
                    'status'                 => ClaimStatus::Claim->value,
                ]);

                foreach ($entitlements as $packId => $qty) {
                    $transaction->items()->create([
                        'relief_pack_id' => $packId,
                        'quantity' => $qty,
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