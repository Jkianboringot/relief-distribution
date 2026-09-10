<?php

namespace App\Http\Controllers;

use App\Enums\InOutType;
use App\Enums\Shift;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreInInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\StoreOutInventoryRequest;
use App\Models\barangay;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use Str;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    /**
     * List all inventory records (both IN and OUT), same data the
     * Filament table showed.
     */
  public function index(Request $request): Response
{
    $search = $request->string('search')->trim();

    $inventories = Inventory::with(['barangays', 'encoder', 'sales'])
        ->when($search->isNotEmpty(), function ($query) use ($search) {
            $query->whereHas('barangays', function ($q) use ($search) {
                $q->where('location', 'like', "{$search}%")
                  ->orWhere('name', 'like', "{$search}%");
            });
        })
        ->latest()
        ->paginate(15)
        ->withQueryString()
        ->through(fn(Inventory $inv) => [
            'id' => $inv->id,
            'type' => $inv->type,
            'inventory_type' => $inv->inventory_type,
            'stock_movement_type' => $inv->stock_movement_type,
            'barangay' => $inv->barangays?->name,
            'encoder' => $inv->encoder?->name,
            'cash_amount' => $inv->sales?->cash_amount,
            'net_cash' => $inv->sales?->net_cash,
            'created_at' => $inv->created_at->format('M d, Y g:i A'),
        ]);

    return Inertia::render('Inventories/Index', [
        'inventories' => $inventories,
        'filters' => $request->only(['search']),
    ]);
}

    // i think its better to get the model here
    // public function edit(Inventory $inventory): Response
    // {

    //     return Inertia::render('Inventories/CreateIn', [

    //         'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
    //         'barangays' => barangay::select('id', 'location', 'barangay_type')->get(),
    //         'products' => Product::select('id', 'name', 'price')->get(),
    //     ]);
    // }

    public function editIn(Inventory $inventory): Response
    {
        // dd($inventory);
        $inventory->load('items');

        return Inertia::render('Inventories/EditIn', [
            'inventory' => [
                'id' => $inventory->id,
                'barangay_id' => $inventory->barangay_id,
                'stock_movement_type' => $inventory->stock_movement_type,
                'productList' => $inventory->items->map(fn($i) => [
                    'product_id' => $i->product_id,
                    'quantity' => $i->quantity,
                ]),
            ],
            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($c) => ['value' => $c->value, 'label' => Str::headline($c->name)]),
            'barangays' => barangay::select('id', 'location', 'barangay_type')->get(),
            'products' => Product::select('id', 'name', 'price')->get(),
        ]);
    }

    public function inUpdate( Inventory $inventory,StoreInInventoryRequest $request): RedirectResponse
    {
// dd('hisd');

        $data = $request->validated();
        $inv = $this->inventoryService->inventoryUpdateIn($inventory, [
            'barangay_id' => $data['barangay_id'],
            'inventory' => ['stock_movement_type' => $data['stock_movement_type']],
            'productList' => $data['productList'],
        ]);

        if (!$inv) {
            return back()->with('error', 'Failed to update stock in. Check the logs.');
        }

        return redirect()->route('inventories.index')->with('success', 'Stock in updated.');
    }

    public function editOut(Inventory $inventory): Response
    {
        $inventory->load(['items', 'sales']);

        $barangays = barangay::with('products')->get()->map(fn(barangay $barangay) => [
            'id' => $barangay->id,
            'location' => $barangay->location,
            'products' => $barangay->products->map(fn(Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->pivot->quantity,
            ]),
        ]);

        return Inertia::render('Inventories/EditOut', [
            'inventory' => [
                'id' => $inventory->id,
                'barangay_id' => $inventory->barangay_id,
                'stock_movement_type' => $inventory->stock_movement_type??'',
                'productList' => $inventory->items->map(fn($i) => [
                    'product_id' => $i->product_id,
                    'quantity' => $i->quantity,
                ]),
                'shift' => $inventory->sales->shift??null,
                'cash_amount' => $inventory->sales->cash_amount,
                'cash_shortage' => $inventory->sales->cash_shortage??null,
                'gcash_amount' => $inventory->sales->gcash_amount??null,
                'cash_advance' => $inventory->sales->cash_advance??null,
                'remitted_expenses' => $inventory->sales->remitted_expenses??null,
            ],
            'shifts' => collect(Shift::cases())->map(fn($c) => ['value' => $c->value, 'label' => Str::headline($c->name)]),
            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($c) => ['value' => $c->value, 'label' => Str::headline($c->name)]),
            'barangays' => $barangays,
        ]);
    }

    public function updateOut(StoreOutInventoryRequest $request, Inventory $inventory)
    {
        dd($request,$inventory);
        $data = $request->validated();

        $inv = $this->inventoryService->inventoryUpdateOut($inventory, [
            'barangay_id' => $data['barangay_id'],
            'inventory' => ['stock_movement_type' => $data['stock_movement_type']],
            'productList' => $data['productList'],
            'sale' => [
                'shift' => $data['shift'],
                'cash_amount' => $data['cash_amount'],
                'gcash_amount' => $data['gcash_amount'],
                'cash_advance' => $data['cash_advance'],
                'remitted_expenses' => $data['remitted_expenses'],
                'cash_shortage' => $data['cash_shortage'] ?? 0,
                'net_cash' => $data['net_cash'],
            ],
        ]);

        if (!$inv) {
            return back()->with('error', 'Failed to update sale. Check the logs.');
        }

        return redirect()->route('inventories.index')->with('success', 'Sale updated.');
    }



    public function createIn(): Response
    {
        return Inertia::render('Inventories/CreateIn', [

            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
            'barangays' => barangay::select('id', 'location', 'barangay_type')->get(),
            'products' => Product::select('id', 'name', 'price')->get(),
        ]);
    }

    public function storeIn(StoreInInventoryRequest $request): RedirectResponse
    {

        $data = $request->validated();

        $inv = $this->inventoryService->inventoryIn([
            'barangay_id' => $data['barangay_id'],
            'inventory' => [
                'stock_movement_type' => $data['stock_movement_type'],

            ],
            'productList' => $data['productList'],
        ]);

        if (!$inv) {
            return back()->with('message', 'Failed to record stock in. Check the logs.');
        }

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Stock in recorded.');
    }

    /**
     * Show the "Stock Out" form. Sends every barangay along with the
     * products it already carries (with current quantity), so the
     * frontend can filter/display stock without extra requests.
     */
    public function createOut(): Response
    {
        $barangays = barangay::with('products')->get()->map(fn(barangay $barangay) => [
            'id' => $barangay->id,
            'location' => $barangay->location,
            'products' => $barangay->products->map(fn(Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $product->pivot->quantity,
            ]),
        ]);

        return Inertia::render('Inventories/CreateOut', [
            'shifts' => collect(Shift::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

            'barangays' => $barangays,
        ]);
    }

    public function storeOut(StoreOutInventoryRequest $request): RedirectResponse
    {
        // dd($request);   
        $data = $request->validated();

        $inv = $this->inventoryService->inventoryOut([
            'barangay_id' => $data['barangay_id'],
            'inventory' => [
                'stock_movement_type' => $data['stock_movement_type'],

            ],
            'productList' => $data['productList'],
            'sale' => [
                'shift' => $data['shift'],
                'cash_amount' => $data['cash_amount'],
                'gcash_amount' => $data['gcash_amount'],
                'cash_advance' => $data['cash_advance'],
                'remitted_expenses' => $data['remitted_expenses'],
                'cash_shortage' => $data['cash_shortage'] ?? 0,
                'net_cash' => $data['net_cash'],
            ],
        ]);

        if (!$inv) {
            return back()->with('error', 'Failed to record sale. Check the logs.');
        }

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Sale recorded.');
    }

    public function delete(Inventory $inventory): RedirectResponse
    {
        $deleted = $this->inventoryService->inventoryDelete($inventory);

        if (!$deleted) {
            return back()->with('error', 'Failed to delete record. Check the logs.');
        }

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Record deleted.');
    }
}