<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Benificiary;
use App\Models\DistributionSchedule;
use App\Models\DistributionTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Claims against ONE distribution schedule's own stock.
 * Remaining = SUM(distribution_relief_stocks) - COUNT(claimed transactions).
 *
 * Methods do not catch exceptions. The transaction rolls back on throw
 * and the controller decides what to show.
 */
class DistributionTransactionService
{
    /**
     * @throws \DomainException            not eligible / wrong barangay / already claimed / schedule not ongoing
     * @throws InsufficientStockException  schedule stock is already zero
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

            $stock = (int) $schedule->reliefStock()->sum('quantity');
            $used  = $schedule->transactions()->where('status', 'claimed')->count();

            if ($stock - $used < 1) {
                throw new InsufficientStockException('Stock is already zero for this distribution.');
            }

            return $schedule->transactions()->create([
                'beneficiary_id'         => $beneficiary->id,
                'quantity_boxes'         => 1,
                'verified_by'            => $verifiedBy,
                'verification_timestamp' => now(),
                'status'                 => 'claimed',
            ])->load('beneficiary.barangay:id,name', 'beneficiary:id,first_name,middle_name,last_name,barangay_id,household_members');
        });
    } catch (QueryException $e) {
        if ($e->getCode() === '23000') {
            throw new \DomainException('This family head has already claimed.');
        }
        throw $e;
    }
}

    /**
     * Reverse a wrongly recorded claim. Stock frees up automatically
     * because remaining is computed from the transaction count.
     */
    public function reverse(DistributionTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->delete();
        });
    }
}