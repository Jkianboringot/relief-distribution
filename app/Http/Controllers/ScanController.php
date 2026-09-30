<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Benificiary;
use App\Models\DistributionSchedule;
use App\Services\DistributionTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function __construct(protected DistributionTransactionService $transactions)
    {
    }

    /**
     * Entry point for the physical QR scan. Finds the ONE ongoing schedule for
     * this beneficiary's barangay and releases automatically. If there's more
     * than one ongoing schedule for that barangay at once, staff picks which.
     */
    public function show(string $qr_code): Response
    {
        $beneficiary = Benificiary::with('barangay:id,name')->where('qr_code', $qr_code)->first();

        if (! $beneficiary) {
            return $this->result('error', 'QR code not recognized.');
        }

        $schedules = DistributionSchedule::where('barangay_id', $beneficiary->barangay_id)
            ->where('status', 'ongoing')
            ->orderBy('date')
            ->get(['id', 'title', 'date']);

        if ($schedules->isEmpty()) {
            return $this->result(
                'error',
                "No active distribution is open for {$beneficiary->barangay->name} right now."
            );
        }

        if ($schedules->count() > 1) {
            return Inertia::render('Scan/Choose', [
                'qr_code' => $qr_code,
                'beneficiary_name' => $beneficiary->full_name,
                'schedules' => $schedules->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'date' => $s->date?->format('Y-m-d'),
                ]),
            ]);
        }

        return $this->release($schedules->first(), $qr_code);
    }

    /**
     * Staff's choice when more than one ongoing schedule matched.
     */
    public function confirm(Request $request, string $qr_code): Response
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'exists:distribution_schedules,id'],
        ]);

        $schedule = DistributionSchedule::findOrFail($data['schedule_id']);

        return $this->release($schedule, $qr_code);
    }

    private function release(DistributionSchedule $schedule, string $qr_code): Response
    {
        try {
            $transaction = $this->transactions->claim($schedule, $qr_code, auth()->id());
        } catch (InsufficientStockException|\DomainException $e) {
            return $this->result('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->result('error', 'Something went wrong recording this claim.');
        }

        $packSummary = $transaction->items
            ->map(fn ($i) => "{$i->reliefPack?->name} × {$i->quantity}")
            ->implode(', ');

        return $this->result(
            'success',
            "Released to {$transaction->beneficiary->full_name}: {$packSummary}",
            $schedule->title
        );
    }

    private function result(string $outcome, string $message, ?string $scheduleTitle = null): Response
    {
        return Inertia::render('Scan/Result', [
            'outcome' => $outcome,
            'message' => $message,
            'schedule_title' => $scheduleTitle,
        ]);
    }

    public function live(): Response
{
    return Inertia::render('Scan/Live');
}
}