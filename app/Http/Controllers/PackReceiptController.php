<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackReceiptRequest;
use App\Models\PackReceipt;
use App\Models\ReliefPack;
use App\Services\ReliefStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PackReceiptController extends Controller
{
    
    public function __construct(protected ReliefStockService $relief_stock)
    {
        // throw new \Exception('Not implemented');
    }

    public function index(): Response
    {
        $packReceipts = PackReceipt::withCount('reliefStock')
            ->orderBy('source_name')
            ->get();

        return Inertia::render('PackReceipts/Index', [
            'packReceipts' => $packReceipts,
        ]);
    }

    public function create(): Response
    {

        return Inertia::render('PackReceipts/Create', [
            'reliefPacks' => ReliefPack::select('id', 'name')
                ->get(),
        ]);
    }

    public function store(PackReceiptRequest $request)
    {
        $data = $request->validated();
        // dd($request,$data);

        $rl=$this->relief_stock->reliefStockStore([
            'receipt'=>[
                'source_name'=>$data['source_name'],
                'date_received'=>$data['date_received']
            ],
            'reliefList'=>$data['reliefList']
        ]);

        if(!$rl){
            return back()->with('error','Failed to record Relief Stock');
        }

        return redirect()->route('pack-receipts.index')->with('message', 'Successfully record Relief Stock');
    }

    public function edit(PackReceipt $packReceipt): Response
    {
     $data=$packReceipt->with('reliefStock.reliefPack')
            ->only(['id', 'name', 'description']);
        return Inertia::render('PackReceipts/Edit', [
            'packReceipts' =>$data ,
            // 'relief_packs'=>$data->reliefStock()
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