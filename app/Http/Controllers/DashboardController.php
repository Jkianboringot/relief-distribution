<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // total_sale = cash_amount + gcash_amount, aggregated in SQL so we
        // never have to load every Sale row into PHP just to sum them.
        $perBranch = Branch::query()
            ->select('branches.id', 'branches.location')
            ->selectRaw('COALESCE(SUM(sales.cash_amount + sales.gcash_amount), 0) as total_sale')
            ->selectRaw('COUNT(sales.id) as sale_count')
            ->leftJoin('sales', 'sales.branch_id', '=', 'branches.id')
            ->groupBy('branches.id', 'branches.location')
            ->orderByDesc('total_sale')
            ->get()
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'location' => $branch->location,
                'total_sale' => (float) $branch->total_sale,
                'sale_count' => (int) $branch->sale_count,
            ]);

        return Inertia::render('dashboard', [
            'overallTotal' => (float) $perBranch->sum('total_sale'),
            'branchSales' => $perBranch,
        ]);
    }
}