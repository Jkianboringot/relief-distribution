<?php

namespace App\Http\Controllers;

use App\Models\DistributionSchedule;
use App\Models\ReliefPack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DistributionScheduleController extends Controller
{
    public function index(): Response
    {
        $schedules = DistributionSchedule::with('reliefPack:id,name,current_stock')
            ->withCount(['transactions as claimed_count' => function ($query) {
                $query->where('status', 'claimed');
            }])
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Distribution/Index', [
            'schedules' => $schedules,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Distribution/Create', [
            'reliefPacks' => ReliefPack::select('id', 'name', 'current_stock')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'barangay' => 'required|string|max:255',
            'relief_pack_id' => 'required|exists:relief_packs,id',
            'planned_quantity' => 'required|integer|min:1',
        ]);

        $reliefPack = ReliefPack::findOrFail($validated['relief_pack_id']);

        if ($reliefPack->current_stock < $validated['planned_quantity']) {
            return redirect()->back()->withErrors([
                'planned_quantity' => 'Not enough boxes in stock for this allocation.',
            ]);
        }

        DistributionSchedule::create([
            ...$validated,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('distribution.index')->with('success', 'Distribution schedule created.');
    }

    public function show(DistributionSchedule $schedule): Response
    {
        $schedule->load([
            'reliefPack:id,name,current_stock',
            'transactions.beneficiary:id,family_head_name,barangay,family_size',
            'transactions.verifiedBy:id,name',
        ]);

        return Inertia::render('Distribution/Show', [
            'schedule' => $schedule,
            'remainingAllocation' => $schedule->remainingAllocation(),
        ]);
    }
}