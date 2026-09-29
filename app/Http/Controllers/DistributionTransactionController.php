<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\DistributionSchedule;
use App\Models\DistributionTransaction;
use App\Services\DistributionTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DistributionTransactionController extends Controller
{
    public function __construct(protected DistributionTransactionService $transactions)
    {
    }

    public function store(Request $request, DistributionSchedule $schedule)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
        ]);

        try {
            $transaction = $this->transactions->claim(
                $schedule,
                $validated['qr_code'],
                $request->user()->id
            );
        } catch (InsufficientStockException | \DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to record the claim.');
        }

        return back()->with('message', "Box released to {$transaction->beneficiary->family_head_name}.");
    }

    public function destroy(DistributionTransaction $transaction)
    {
        try {
            $this->transactions->reverse($transaction);
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to reverse the claim.');
        }

        return back()->with('message', 'Claim reversed.');
    }
}