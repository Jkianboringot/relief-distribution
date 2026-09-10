<?php

namespace App\Http\Controllers;

use App\Enums\BranchType;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Str;

class BranchController extends Controller
{

    //     public function delete(Request $request)
    // {
    //     Branch::findOrFail($request->id)->deleteOrFail();
    //     return redirect()->route('branches.index')->with('message', 'Branch Delete Successfully');
    // }
    public function delete(Branch $branch)
    {
        try {


            $branch->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this branch — it still has associated inventory or sales records.');
        }

        return redirect()->route('branches.index')->with('message', 'Branch deleted successfully.');
    }



    public function create()
    {
        return Inertia::render('Branches/Create', [
            'branch_types' => collect(BranchType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

        ]);
    }

    public function store(BranchRequest $request)
    {

        try {

            Branch::create($request->validated());

        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to create branch.');
        }
        // dd($product,$request);
        return redirect()->route('branches.index')->with('message', 'Branch Created Successfully');

    }



    public function edit(Branch $branch)
    {
        // $p = Branch::findOrFail($branch->id);
        // dd($branch);
        return Inertia::render('Branches/Edit', [
            'branches' => $branch,
            'branch_types' => collect(BranchType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
        ]);

    }

    // public function update(BranchRequest $request)
    // {

    //     $p = Branch::findOrFail($request->id);

    //     $request->validated();


    //     $p->update($request->all());

    //     return redirect()->route('branches.index')->with('message', 'Branch Update Successfully');

    // }

    public function update(BranchRequest $request, Branch $branch)
    {


        try {
            // this is the summary of how it work:
            // $branch is model binding, it auto findorFail and it already has error handling too 
            //the is request is what takes data from body and we update what even branch has with what 
            // we parse from body, with request form, also its the one that validated shit


            $branch->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update branch.');
        }
        // dd($product,$request);
        return redirect()->route('branches.index')->with('message', 'Branch Updated Successfully');


    }


    // ASK-YOURSELF - ask about which of this two is better the top update or this below one
    //   public function update(Request $request,Branch $branch)
    // {


    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     $branch->update([
    //         'name'=>$branch->input('name'),
    //         'price'=>$branch->input('price'),
    //     ]);

    //     return redirect()->route('branchs.index')->with('message', 'Branch Delete Successfully');

    // }
// BranchController.php
    public function products(Branch $branch)
    {
        $products = $branch->products()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/BranchProducts', [
            'branch' => $branch->only('id', 'location'),
            'products' => $products,
        ]);
    }

    public function index(Request $request)
    {
        $branches = Branch::query()
            ->withCount('products')
            ->selectSub(
              Sale::selectRaw('COALESCE(SUM(net_cash), 0)')
                    ->whereColumn('branch_id', 'branches.id'),
                'total_sales'
            )
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters' => $request->only(['search']),
        ]);
    }
}
