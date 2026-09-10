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

class BarangayController extends Controller
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
            return back()->with('error', 'Cannot delete this barangay because it has associated records');
        }

        return redirect()->route('barangays.index')->with('message', 'Barangay deleted');
    }



    public function create()
    {
        return Inertia::render('Barangays/Create', [
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
        return redirect()->route('barangays.index')->with('message', 'Barangay Created Successfully');

    }



    public function edit(barangay $barangay)
    {
        // $p = barangay::findOrFail($barangay->id);
        // dd($barangay);
        return Inertia::render('Barangays/Edit', [
            'barangays' => $barangay,
            'barangay_types' => collect(barangayType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
        ]);

    }



    public function update(barangayRequest $request, barangay $barangay)
    {


        try {


            $barangay->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update Barangay.');
        }
        // dd($product,$request);
        return redirect()->route('barangays.index')->with('message', 'Barangay Updated Successfully');


    }


    public function index(Request $request)
    {
        $barangays = barangay::query()

            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Barangays/Index', [
            'barangays' => $barangays,
            'filters' => $request->only(['search']),
        ]);
    }
}
