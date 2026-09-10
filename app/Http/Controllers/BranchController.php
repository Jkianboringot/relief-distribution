<?php

namespace App\Http\Controllers;

use App\Enums\barangayType;
use App\Http\Requests\barangayRequest;
use App\Models\barangay;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Str;

class barangayController extends Controller
{

    //     public function delete(Request $request)
    // {
    //     barangay::findOrFail($request->id)->deleteOrFail();
    //     return redirect()->route('barangays.index')->with('message', 'barangay Delete Successfully');
    // }
    public function delete(barangay $barangay)
    {
        try {


            $barangay->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this barangay — it still has associated inventory or sales records.');
        }

        return redirect()->route('barangays.index')->with('message', 'barangay deleted successfully.');
    }



    public function create()
    {
        return Inertia::render('barangays/Create', [
            'barangay_types' => collect(barangayType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

        ]);
    }

    public function store(barangayRequest $request)
    {

        try {

            barangay::create($request->validated());

        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to create barangay.');
        }
        // dd($product,$request);
        return redirect()->route('barangays.index')->with('message', 'barangay Created Successfully');

    }



    public function edit(barangay $barangay)
    {
        // $p = barangay::findOrFail($barangay->id);
        // dd($barangay);
        return Inertia::render('barangays/Edit', [
            'barangays' => $barangay,
            'barangay_types' => collect(barangayType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
        ]);

    }

    // public function update(barangayRequest $request)
    // {

    //     $p = barangay::findOrFail($request->id);

    //     $request->validated();


    //     $p->update($request->all());

    //     return redirect()->route('barangays.index')->with('message', 'barangay Update Successfully');

    // }

    public function update(barangayRequest $request, barangay $barangay)
    {


        try {
            // this is the summary of how it work:
            // $barangay is model binding, it auto findorFail and it already has error handling too 
            //the is request is what takes data from body and we update what even barangay has with what 
            // we parse from body, with request form, also its the one that validated shit


            $barangay->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update barangay.');
        }
        // dd($product,$request);
        return redirect()->route('barangays.index')->with('message', 'barangay Updated Successfully');


    }


    // ASK-YOURSELF - ask about which of this two is better the top update or this below one
    //   public function update(Request $request,barangay $barangay)
    // {


    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     $barangay->update([
    //         'name'=>$barangay->input('name'),
    //         'price'=>$barangay->input('price'),
    //     ]);

    //     return redirect()->route('barangays.index')->with('message', 'barangay Delete Successfully');

    // }
// barangayController.php
    public function products(barangay $barangay)
    {
        $products = $barangay->products()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('barangays/barangayProducts', [
            'barangay' => $barangay->only('id', 'location'),
            'products' => $products,
        ]);
    }

    public function index(Request $request)
    {
        $barangays = barangay::query()
            ->withCount('products')
            ->selectSub(
              Sale::selectRaw('COALESCE(SUM(net_cash), 0)')
                    ->whereColumn('barangay_id', 'barangays.id'),
                'total_sales'
            )
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('barangays/Index', [
            'barangays' => $barangays,
            'filters' => $request->only(['search']),
        ]);
    }
}
