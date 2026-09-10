<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeneficiaryRequest;
use App\Models\Barangay;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BeneficiaryController extends Controller
{
    public function delete(Beneficiary $beneficiary)
    {
        try {
            $beneficiary->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this beneficiary because it has associated records');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Beneficiary deleted');
    }

    public function create()
    {
        return Inertia::render('Beneficiaries/Create', [
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'genders' => $this->genderOptions(),
        ]);
    }

    public function store(BeneficiaryRequest $request)
    {
        try {
            Beneficiary::create([
                ...$request->validated(),
                'qr_code' => $this->generateQrCode(),
                'status' => 'unclaimed',
                'registered_by' => $request->user()->id,
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to register beneficiary.');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Beneficiary Registered Successfully');
    }

    public function edit(Beneficiary $beneficiary)
    {
        return Inertia::render('Beneficiaries/Edit', [
            'beneficiary' => $beneficiary,
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'genders' => $this->genderOptions(),
        ]);
    }

    public function update(BeneficiaryRequest $request, Beneficiary $beneficiary)
    {
        try {
            $beneficiary->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update Beneficiary.');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Beneficiary Updated Successfully');
    }

    public function index(Request $request)
    {
        $beneficiaries = Beneficiary::query()
            ->with('barangay:id,name')
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "{$search}%")
                        ->orWhere('last_name', 'like', "{$search}%");
                });
            })
            ->when($request->integer('barangay_id'), fn ($query, $barangayId) => $query->where('barangay_id', $barangayId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Beneficiaries/Index', [
            'beneficiaries' => $beneficiaries,
            'filters' => $request->only(['search', 'barangay_id']),
        ]);
    }

    protected function genderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'Male'],
            ['value' => 'female', 'label' => 'Female'],
        ];
    }

    protected function generateQrCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (Beneficiary::where('qr_code', $code)->exists());

        return $code;
    }
}