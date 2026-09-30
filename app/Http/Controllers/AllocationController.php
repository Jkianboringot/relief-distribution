<?php

namespace App\Http\Controllers;

use App\Models\Benificiary;
use App\Models\DistributionBeneficiaryAllocation;
use App\Models\DistributionSchedule;
use App\Services\DistributionStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllocationController extends Controller
{
    public function __construct(protected DistributionStockService $stock)
    {
    }

    public function edit(DistributionSchedule $schedule): Response
    {
        $schedule->load([
            'barangay:id,name',
            'reliefStock.reliefPack:id,name',
            'allocations.beneficiary:id,first_name,middle_name,last_name,household_members',
            'allocations.reliefPack:id,name',
        ]);

        return Inertia::render('Distribution/Allocations', [
            'schedule' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'barangay' => $schedule->barangay?->name,
            ],
            'reliefPacks' => $schedule->reliefStock->map(fn ($stock) => [
                'id' => $stock->relief_pack_id,
                'name' => $stock->reliefPack?->name ?? 'Unknown pack',
                'default_entitlement' => (int) $stock->entitlement_per_beneficiary,
            ])->values(),
            'allocations' => $schedule->allocations->map(fn ($a) => [
                'id' => $a->id,
                'beneficiary_id' => $a->beneficiary_id,
                'beneficiary_name' => $a->beneficiary?->full_name ?? 'Unknown',
                'household_members' => $a->beneficiary?->household_members,
                'relief_pack_id' => $a->relief_pack_id,
                'relief_pack_name' => $a->reliefPack?->name ?? 'Unknown pack',
                'quantity' => (int) $a->quantity,
            ])->values(),
        ]);
    }

    public function search(Request $request, DistributionSchedule $schedule): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $beneficiaries = Benificiary::query()
            ->where('barangay_id', $schedule->barangay_id)
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "{$q}%")
                    ->orWhere('last_name', 'like', "{$q}%");
            })
            ->limit(10)
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'household_members'])
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->full_name,
                'household_members' => $b->household_members,
            ]);
        return response()->json($beneficiaries);
    }

    public function store(Request $request, DistributionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'allocations.*.relief_pack_id' => ['required', 'exists:relief_packs,id'],
            'allocations.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->stock->setBeneficiaryAllocations($schedule, $data['allocations'], $request->user()->id);

        return back()->with('message', 'Allocations saved.');
    }

    public function destroy(DistributionBeneficiaryAllocation $allocation): RedirectResponse
    {
        $this->stock->removeBeneficiaryAllocation($allocation);

        return back()->with('message', 'Allocation removed.');
    }
}