<?php

namespace App\Http\Controllers;

use App\Models\PackReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PackReceiptController extends Controller
{
    public function index(): Response
    {
        $packReceipts = PackReceipt::withCount('reliefStock')
            ->orderBy('name')
            ->get();

        return Inertia::render('PackReceipts/Index', [
            'packReceipts' => $packReceipts,
        ]);
    }

    public function create(): Response
    {
        
        return Inertia::render('PackReceipts/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        PackReceipt::create($validated);

        return redirect()->route('relief-packs.index')->with('success', 'Box type created.');
    }

    public function edit(PackReceipt $packReceipt): Response
    {
        return Inertia::render('PackReceipts/Edit', [
            'packReceipt' => $packReceipt->only(['id', 'name', 'description']),
        ]);
    }

    public function update(Request $request, PackReceipt $packReceipt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $packReceipt->update($validated);

        return redirect()->route('relief-packs.index')->with('success', 'Box type updated.');
    }

    /**
     * Record a receipt of boxes (e.g. from DSWD, Provincial Office).
     * This is the only way current_stock goes up.
     */
    public function receiveStock(Request $request, PackReceipt $packReceipt)
    {
        $validated = $request->validate([
            'source_name' => 'required|string|max:255',
            'quantity_received' => 'required|integer|min:1',
            'date_received' => 'required|date',
        ]);

        DB::transaction(function () use ($packReceipt, $validated, $request) {
            PackReceipt::create([
                'relief_pack_id' => $packReceipt->id,
                'source_name' => $validated['source_name'],
                'quantity_received' => $validated['quantity_received'],
                'date_received' => $validated['date_received'],
                'received_by' => $request->user()->id,
            ]);

            $packReceipt->incrementStock($validated['quantity_received']);
        });

        return redirect()->back()->with('success', 'Boxes received and added to stock.');
    }

    public function receipts(PackReceipt $packReceipt): Response
    {
        $receipts = $packReceipt->receipts()
            ->with('receivedBy:id,name')
            ->latest('date_received')
            ->get();

        return Inertia::render('PackReceipts/Receipts', [
            'packReceipt' => $packReceipt,
            'receipts' => $receipts,
        ]);
    }

    public function delete(PackReceipt $packReceipt)
    {
        try {
            $packReceipt->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this box type because it has associated receipts.');
        }

        return redirect()->route('relief-packs.index')->with('success', 'Box type deleted.');
    }
}