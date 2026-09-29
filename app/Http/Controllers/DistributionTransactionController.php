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

    $packNames = $transaction->items->pluck('reliefPack.name')->filter()->implode(', ');

    return back()->with(
        'message',
        "Released {$transaction->quantity_boxes} box(es) to {$transaction->beneficiary->full_name}: {$packNames}."
    );
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