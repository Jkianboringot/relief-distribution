<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Distribution counts per status: ['pending' => 3, 'ongoing' => 1, 'completed' => 8]
        $statusCounts = DB::table('distribution_schedules')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Counted by when it was marked completed (updated_at), not by the
        // scheduled `date`, so a schedule dated in another month still counts.
        $completedThisMonth = DB::table('distribution_schedules')
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->count();

        $beneficiariesPerBarangay = DB::table('beneficiaries')
            ->join('barangays', 'barangays.id', '=', 'beneficiaries.barangay_id')
            ->select('barangays.name', DB::raw('COUNT(*) as total'))
            ->groupBy('barangays.id', 'barangays.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total' => (int) $row->total,
            ]);

        $recentActivities = DB::table('distribution_schedules as ds')
            ->join('barangays', 'barangays.id', '=', 'ds.barangay_id')
            ->leftJoin('users', 'users.id', '=', 'ds.created_by')
            ->select([
                'ds.id',
                'ds.title as activity',
                'ds.date',
                'ds.status',
                'barangays.name as barangay',
                'users.name as officer',
            ])
            ->orderByDesc('ds.created_at')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'date' => $row->date,
                'activity' => $row->activity,
                'barangay' => $row->barangay,
                'status' => $row->status,
                'officer' => $row->officer ?? '—',
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'totalBeneficiaries' => DB::table('beneficiaries')->count(),
                'scheduledDistributions' => (int) $statusCounts->sum(), // every distribution schedule made
                'completedOperations' => $completedThisMonth,
            ],
            'statusBreakdown' => [
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'ongoing' => (int) ($statusCounts['ongoing'] ?? 0),
            ],
            'beneficiariesPerBarangay' => $beneficiariesPerBarangay,
            'recentActivities' => $recentActivities,
        ]);
    }
}