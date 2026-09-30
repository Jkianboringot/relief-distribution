<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\PackReceiptRequest;
use App\Models\PackReceipt;
use App\Models\ReliefPack;
use App\Services\ReliefStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PackReceiptController extends Controller
{
    public function __construct(protected ReliefStockService $relief_stock)
    {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $packReceipts = PackReceipt::with('reliefStock.reliefPack:id,name')
            ->withSum('reliefStock as total_quantity', 'quantity')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('source_name', 'like', "{$search}%");
            })
            ->orderByDesc('created_at')

            ->paginate(15)
            ->withQueryString()
            ->through(fn($receipt) => [
                'id' => $receipt->id,
                'source_name' => $receipt->source_name,
                'date_received' => $receipt->date_received?->format('Y-m-d'),
                'total_quantity' => (int) $receipt->total_quantity,
                'items' => $receipt->reliefStock->map(fn($stock) => [
                    'id' => $stock->id,
                    'name' => $stock->reliefPack?->name ?? 'Unknown pack',
                    'quantity' => (int) $stock->quantity,
                ])->values(),
            ]);

        return Inertia::render('PackReceipts/Index', [
            'packReceipts' => $packReceipts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PackReceipts/Create', [
            'reliefPacks' => ReliefPack::select('id', 'name')->get(),
        ]);
    }

    public function store(PackReceiptRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $receipt = $this->relief_stock->reliefStockStore([
                'receipt' => [
                    'source_name' => $data['source_name'],
                    'date_received' => $data['date_received'],
                ],
                'reliefList' => $data['reliefList'],
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to record relief stock.');
        }

        // your current service returns null on failure instead of throwing;
        // keep this until the service is changed to let exceptions bubble
        if (!$receipt) {
            return back()->with('error', 'Failed to record relief stock.');
        }

        return redirect()
            ->route('pack-receipts.index')
            ->with('message', 'Successfully recorded relief stock.');
    }

    public function edit(PackReceipt $packReceipt): Response
    {
        $packReceipt->load('reliefStock.reliefPack:id,name');

        return Inertia::render('PackReceipts/Edit', [
            'packReceipt' => [
                'id' => $packReceipt->id,
                'source_name' => $packReceipt->source_name,
                'date_received' => $packReceipt->date_received?->format('Y-m-d'),
                'reliefList' => $packReceipt->reliefStock->map(fn($stock) => [
                    'relief_pack_id' => $stock->relief_pack_id,
                    'quantity' => (int) $stock->quantity,
                ])->values(),
            ],
            'reliefPacks' => ReliefPack::select('id', 'name')->get(),
        ]);
    }

    public function update(PackReceiptRequest $request, PackReceipt $packReceipt): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->relief_stock->reliefStockUpdate($packReceipt, [
                'receipt' => [
                    'source_name' => $data['source_name'],
                    'date_received' => $data['date_received'],
                ],
                'reliefList' => $data['reliefList'],
            ]);
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update relief stock.');
        }

        return redirect()
            ->route('pack-receipts.index')
            ->with('message', 'Relief stock receipt updated.');
    }

    public function destroy(PackReceipt $packReceipt): RedirectResponse
    {
        try {
            $this->relief_stock->reliefStockDelete($packReceipt);
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to delete this receipt.');
        }

        return redirect()
            ->route('pack-receipts.index')
            ->with('message', 'Relief stock receipt deleted.');
    }
}