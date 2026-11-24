<?php

namespace App\Http\Controllers;

use App\Http\Requests\MunicipalityRequest;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MunicipalityController extends Controller
{
    public function index(Request $request)
    {
        $municipalities = Municipality::query()
            ->withCount('barangays')
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Municipalities/Index', [
            'municipalities' => $municipalities,
            'filters' => $request->only(['search']),
        ]);
    }

    // Create and edit share one form page.
    public function create()
    {
        return Inertia::render('Municipalities/Form', [
            'municipality' => null,
        ]);
    }

    public function store(MunicipalityRequest $request)
    {
        try {
            Municipality::create($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to create municipality.');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality created successfully');
    }

    public function edit(Municipality $municipality)
    {
        return Inertia::render('Municipalities/Form', [
            'municipality' => $municipality,
        ]);
    }

    public function update(MunicipalityRequest $request, Municipality $municipality)
    {
        try {
            $municipality->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update municipality.');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality updated successfully');
    }

    public function delete(Municipality $municipality)
    {
        try {
            $municipality->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this municipality because it has associated barangays');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality deleted');
    }
}
