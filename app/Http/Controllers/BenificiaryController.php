<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Http\Requests\BeneficiaryRequest;
use App\Models\Barangay;
use App\Models\Benificiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Http\Response as HttpResponse;
class BenificiaryController extends Controller
{
    public function delete(Benificiary $Benificiary)
    {
        try {
            $Benificiary->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this Benificiary because it has associated records');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Benificiary deleted');
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
            $validated = $request->validated();

            Benificiary::create([
                ...$validated,
                'qr_code' => $this->generateQrCode($validated['barangay_id']),
                'status' => 'unclaimed',
                'registered_by' => $request->user()->id,
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to register Benificiary.');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Benificiary Registered Successfully');
    }

    public function edit(Benificiary $Benificiary)
    {
        return Inertia::render('Beneficiaries/Edit', [
            'Benificiary' => $Benificiary,
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'genders' => $this->genderOptions(),
        ]);
    }

    public function update(BeneficiaryRequest $request, Benificiary $Benificiary)
    {
        try {
            $Benificiary->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update Benificiary.');
        }

        return redirect()->route('beneficiaries.index')->with('message', 'Benificiary Updated Successfully');
    }

    public function index(Request $request)
    {
        $beneficiaries = Benificiary::query()
            ->with('barangay:id,name')
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "{$search}%")
                        ->orWhere('last_name', 'like', "{$search}%");
                });
            })
            ->when($request->integer('barangay_id'), fn($query, $barangayId) => $query->where('barangay_id', $barangayId))
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
            ['value' => Gender::Male->value, 'label' => 'Male'],
            ['value' => Gender::Female->value, 'label' => 'Female'],
        ];
    }

    protected function generateQrCode($id): string
    {
        // NOTE:no need to validated here since it is validated already when it was givin, and since its protected
        // nothing can call it outside
        $b = Barangay::findOrFail($id)->code;
        do {
            $beneficiaryCode = strtoupper(Str::random(12));
            $qrCode = $b . '-' . $beneficiaryCode;
        } while (Benificiary::where('qr_code', $qrCode)->exists());

        return $qrCode;

    }

   public function qr(Benificiary $beneficiary): HttpResponse
    {
        return response($beneficiary->generateQrCode(), 200)
            ->header('Content-Type', 'image/png');
    }
 
    // This is what the QR code points to. For now it just answers.
    public function scan(string $qrCode): HttpResponse
    {
        return response('scaned');
    }
}