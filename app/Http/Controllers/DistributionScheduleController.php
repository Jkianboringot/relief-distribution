<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\DistributionScheduleRequest;
use App\Models\Barangay;
use App\Models\DistributionSchedule;
use App\Models\ReliefPack;
use App\Services\DistributionStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DistributionScheduleController extends Controller
{
    public function __construct(protected DistributionStockService $distribution_stock)
    {
    }

    public function index(): Response
    {
        $schedules = DistributionSchedule::with([
                'barangay:id,name',
                'reliefStock.reliefPack:id,name',
            ])
            ->withSum('reliefStock as total_quantity', 'quantity')
            ->withCount([
                'transactions as claimed_count' => function ($query) {
                    $query->where('status', 'claimed');
                }
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($schedule) => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date?->format('Y-m-d'),
                'location' => $schedule->location,
                'barangay' => $schedule->barangay?->name,
                'status' => $schedule->status,
                'claimed_count' => (int) $schedule->claimed_count,
                'total_quantity' => (int) $schedule->total_quantity,
                'items' => $schedule->reliefStock->map(fn($stock) => [
                    'id' => $stock->id,
                    'name' => $stock->reliefPack?->name ?? 'Unknown pack',
                    'quantity' => (int) $stock->quantity,
                ])->values(),
            ]);

        return Inertia::render('Distribution/Index', [
            'schedules' => $schedules,
        ]);
    }

public function create(): Response
{
    return Inertia::render('Distribution/Create', [
        'barangays' => Barangay::select('id', 'name')->orderBy('name')->get(),
    ]);
}

public function store(DistributionScheduleRequest $request): RedirectResponse
{
    $data = $request->validated();

    try {
        $this->distribution_stock->distributionStore([
            'title' => $data['title'],
            'date' => $data['date'],
            'location' => $data['location'] ?? null,
            'barangay_id' => $data['barangay_id'],
        ]);
    } catch (\Throwable $th) {
        Log::error($th);
        return back()->withInput()->with('error', 'Failed to create distribution.');
    }

    return redirect()->route('distribution.index')->with('message', 'Distribution created.');
}

public function edit(DistributionSchedule $schedule): Response
{
    return Inertia::render('Distribution/Edit', [
        'schedule' => [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'date' => $schedule->date?->format('Y-m-d'),
            'location' => $schedule->location,
            'barangay_id' => $schedule->barangay_id,
        ],
        'barangays' => Barangay::select('id', 'name')->orderBy('name')->get(),
    ]);
}

public function update(DistributionScheduleRequest $request, DistributionSchedule $schedule): RedirectResponse
{
    $data = $request->validated();

    try {
        $this->distribution_stock->distributionUpdate($schedule, [
            'title' => $data['title'],
            'date' => $data['date'],
            'location' => $data['location'] ?? null,
            'barangay_id' => $data['barangay_id'],
        ]);
    } catch (\Throwable $th) {
        Log::error($th);
        return back()->with('error', 'Failed to update distribution.');
    }

    return redirect()->route('distribution.index')->with('message', 'Distribution updated.');
}

    public function destroy(DistributionSchedule $schedule): RedirectResponse
    {
        try {
            $this->distribution_stock->distributionDelete($schedule);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to delete distribution.');
        }

        return redirect()
            ->route('distribution.index')
            ->with('message', 'Distribution deleted.');
    }

    public function updateStatus(Request $request, DistributionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:ongoing,completed',
        ]);

        try {
            $this->distribution_stock->distributionSetStatus($schedule, $data['status']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update distribution status.');
        }

        return back()->with('message', $data['status'] === 'ongoing'
            ? 'Distribution started. Claims are now open.'
            : 'Distribution completed.');
    }


    
public function show(DistributionSchedule $schedule): Response
{
    $schedule->load([
        'barangay:id,name',
        'reliefStock.reliefPack:id,name',
        'transactions.beneficiary.barangay:id,name',
        'transactions.verifiedBy:id,name',
    ]);

    return Inertia::render('Distribution/Show', [
        'schedule' => [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'date' => $schedule->date?->format('Y-m-d'),
            'location' => $schedule->location,
            'barangay' => $schedule->barangay?->name,
            'status' => $schedule->status,
            'relief_stock' => $schedule->reliefStock->map(fn ($stock) => [
                'id' => $stock->id,
                'relief_pack_id' => $stock->relief_pack_id,
                'quantity' => (int) $stock->quantity,
                'relief_pack' => $stock->reliefPack ? [
                    'id' => $stock->reliefPack->id,
                    'name' => $stock->reliefPack->name,
                ] : null,
            ])->values(),
            'transactions' => $schedule->transactions->map(fn ($tx) => [
                'id' => $tx->id,
                'quantity_boxes' => $tx->quantity_boxes,
                'verification_timestamp' => $tx->verification_timestamp,
                'status' => $tx->status,
                'beneficiary' => $tx->beneficiary ? [
                    'id' => $tx->beneficiary->id,
                    'family_head_name' => $tx->beneficiary->full_name,
                    'barangay' => $tx->beneficiary->barangay?->name,
                    'family_size' => $tx->beneficiary->household_members,
                ] : null,
                'verified_by' => $tx->verifiedBy ? [
                    'id' => $tx->verifiedBy->id,
                    'name' => $tx->verifiedBy->name,
                ] : null,
            ])->values(),
        ],
        'remainingAllocation' => $schedule->remainingAllocation(),
    ]);
}

}