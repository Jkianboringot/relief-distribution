<?php

namespace App\Http\Controllers;

use App\Models\barangay;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // total_sale = cash_amount + gcash_amount, aggregated in SQL so we
        // never have to load every Sale row into PHP just to sum them.
        $perbarangay = barangay::query()
            ->select('barangays.id', 'barangays.location')
            ->selectRaw('COALESCE(SUM(sales.cash_amount + sales.gcash_amount), 0) as total_sale')
            ->selectRaw('COUNT(sales.id) as sale_count')
            ->leftJoin('sales', 'sales.barangay_id', '=', 'barangays.id')
            ->groupBy('barangays.id', 'barangays.location')
            ->orderByDesc('total_sale')
            ->get()
            ->map(fn ($barangay) => [
                'id' => $barangay->id,
                'location' => $barangay->location,
                'total_sale' => (float) $barangay->total_sale,
                'sale_count' => (int) $barangay->sale_count,
            ]);

        return Inertia::render('dashboard', [
            'overallTotal' => (float) $perbarangay->sum('total_sale'),
            'barangaySales' => $perbarangay,
        ]);
    }
}