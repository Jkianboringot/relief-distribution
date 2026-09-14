<?php

namespace App\Http\Controllers;

use App\Models\PackReceipt;
use App\Models\ReliefPack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReliefPackController extends Controller
{
    public function index(): Response
    {
        $reliefPacks = ReliefPack::withCount('receipts')
            ->orderBy('name')
            ->get();

        return Inertia::render('ReliefPacks/Index', [
            'reliefPacks' => $reliefPacks,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ReliefPacks/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ReliefPack::create($validated);

        return redirect()->route('relief-packs.index')->with('success', 'Box type created.');
    }

    public function edit(ReliefPack $reliefPack): Response
    {
        return Inertia::render('ReliefPacks/Edit', [
            'reliefPack' => $reliefPack->only(['id', 'name', 'description']),
        ]);
    }

    public function update(Request $request, ReliefPack $reliefPack)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $reliefPack->update($validated);

        return redirect()->route('relief-packs.index')->with('success', 'Box type updated.');
    }

    /**
     * Record a receipt of boxes (e.g. from DSWD, Provincial Office).
     * This is the only way current_stock goes up.
     */
    public function receiveStock(Request $request, ReliefPack $reliefPack)
    {
        $validated = $request->validate([
            'source_name' => 'required|string|max:255',
            'quantity_received' => 'required|integer|min:1',
            'date_received' => 'required|date',
        ]);

        DB::transaction(function () use ($reliefPack, $validated, $request) {
            PackReceipt::create([
                'relief_pack_id' => $reliefPack->id,
                'source_name' => $validated['source_name'],
                'quantity_received' => $validated['quantity_received'],
                'date_received' => $validated['date_received'],
                'received_by' => $request->user()->id,
            ]);

            $reliefPack->incrementStock($validated['quantity_received']);
        });

        return redirect()->back()->with('success', 'Boxes received and added to stock.');
    }

    public function receipts(ReliefPack $reliefPack): Response
    {
        $receipts = $reliefPack->receipts()
            ->with('receivedBy:id,name')
            ->latest('date_received')
            ->get();

        return Inertia::render('ReliefPacks/Receipts', [
            'reliefPack' => $reliefPack,
            'receipts' => $receipts,
        ]);
    }

    public function delete(ReliefPack $reliefPack)
    {
        try {
            $reliefPack->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this box type because it has associated receipts.');
        }

        return redirect()->route('relief-packs.index')->with('success', 'Box type deleted.');
    }
}