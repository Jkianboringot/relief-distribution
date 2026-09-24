<?php

namespace App\Http\Controllers;

use App\Models\Benificiary;
use App\Models\DistributionSchedule;
use App\Models\DistributionTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributionTransactionController extends Controller
{
    /**
     * Claim a box for a family head under a specific distribution schedule.
     * Triggered after the staff scans the beneficiary's QR code.
     *
     * Rules:
     * - 1 family head = 1 box, regardless of family size.
     * - A family head can only claim once per schedule.
     * - Stock must be available before the claim is recorded.
     */
    public function store(Request $request, DistributionSchedule $schedule)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string|exists:beneficiaries,qr_code',
        ]);

        $beneficiary = Benificiary::where('qr_code', $validated['qr_code'])->firstOrFail();

        if ($beneficiary->eligibility_status !== 'eligible') {
            throw ValidationException::withMessages([
                'qr_code' => 'This family head is not marked as eligible.',
            ]);
        }

        if ($beneficiary->hasClaimedFor($schedule->id)) {
            throw ValidationException::withMessages([
                'qr_code' => 'This family head has already claimed a box for this schedule.',
            ]);
        }

        if ($schedule->remainingAllocation() < 1) {
            throw ValidationException::withMessages([
                'qr_code' => 'No remaining box allocation for this schedule.',
            ]);
        }

        $reliefPack = $schedule->reliefPack;

        $transaction = DB::transaction(function () use ($schedule, $beneficiary, $reliefPack, $request) {
            $reliefPack->decrementStock(1); // always 1 box per family head

            return DistributionTransaction::create([
                'distribution_schedule_id' => $schedule->id,
                'beneficiary_id' => $beneficiary->id,
                'quantity_boxes' => 1,
                'verified_by' => $request->user()->id,
                'verification_timestamp' => now(),
                'status' => 'claimed',
            ]);
        });

        return redirect()->back()->with('success', "Box released to {$beneficiary->family_head_name}.");
    }

    public function destroy(DistributionTransaction $transaction)
    {
        // Reverses a wrongly-recorded claim: restores stock, deletes the record.
        DB::transaction(function () use ($transaction) {
            $transaction->schedule->reliefPack->incrementStock($transaction->quantity_boxes);
            $transaction->delete();
        });

        return redirect()->back()->with('success', 'Transaction reversed and stock restored.');
    }
}