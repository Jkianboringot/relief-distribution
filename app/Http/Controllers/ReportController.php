<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TYPES = [
        'distribution'  => 'Distribution / Claims',
        'beneficiaries' => 'Beneficiaries per Barangay',
        'inventory'     => 'Inventory (Received / Released / Remaining)',
        'schedules'     => 'Schedule Summary',
    ];

    public function index(Request $request): Response
    {
        [$type, $filters] = $this->resolve($request);
        $report = $this->build($type, $filters);

        return Inertia::render('Reports/Index', [
            'types' => collect(self::TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'type'        => $type,
            'filters'     => $filters,
            'columns'     => $report['columns'],
            'rows'        => $report['rows'],
            'summary'     => $report['summary'],
            'barangays'   => DB::table('barangays')->orderBy('name')->get(['id', 'name']),
            'schedules'   => DB::table('distribution_schedules')->orderByDesc('date')->get(['id', 'title']),
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$type, $filters] = $this->resolve($request);
        $report = $this->build($type, $filters);
        $filename = "report-{$type}-" . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 properly

            fputcsv($out, array_column($report['columns'], 'label'), ',', '"', '\\');
            foreach ($report['rows'] as $row) {
                fputcsv(
                    $out,
                    array_map(fn ($c) => $row[$c['key']] ?? '', $report['columns']),
                    ',', '"', '\\'
                );
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    private function resolve(Request $request): array
    {
        $type = $request->query('type');
        if (! in_array($type, array_keys(self::TYPES), true)) {
            $type = 'distribution';
        }

        $date = fn (string $key) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query($key))
            ? $request->query($key)
            : null;
        $int = fn (string $key) => $request->query($key) ? (int) $request->query($key) : null;

        return [$type, [
            'from'        => $date('from'),
            'to'          => $date('to'),
            'barangay_id' => $int('barangay_id'),
            'schedule_id' => $int('schedule_id'),
        ]];
    }

    private function build(string $type, array $f): array
    {
        return match ($type) {
            'beneficiaries' => $this->beneficiaries($f),
            'inventory'     => $this->inventory($f),
            'schedules'     => $this->schedules($f),
            default         => $this->distribution($f),
        };
    }

    /** Filters that work on a distribution_schedules alias. */
    private function scheduleFilters($query, array $f, string $alias = 'ds')
    {
        return $query
            ->when($f['from'], fn ($q, $v) => $q->whereDate("$alias.date", '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate("$alias.date", '<=', $v))
            ->when($f['barangay_id'], fn ($q, $v) => $q->where("$alias.barangay_id", $v))
            ->when($f['schedule_id'], fn ($q, $v) => $q->where("$alias.id", $v));
    }

    // ---------------------------------------------------------------------
    // reports
    // ---------------------------------------------------------------------

   private function distribution(array $f): array
{
    $claim = ClaimStatus::Claim->value;

    $rows = $this->scheduleFilters(
        DB::table('distribution_transactions as dt')
            ->join('distribution_schedules as ds', 'ds.id', '=', 'dt.distribution_schedule_id')
            ->join('barangays as br', 'br.id', '=', 'ds.barangay_id')
            ->join('beneficiaries as b', 'b.id', '=', 'dt.beneficiary_id')
            ->leftJoin('users as u', 'u.id', '=', 'dt.verified_by'),
        $f
    )
        ->selectRaw("
            dt.id,
            dt.quantity_boxes,
            dt.status,
            dt.verification_timestamp,
            dt.created_at,
            COALESCE(dt.verification_timestamp, dt.created_at) as claimed_at,
            ds.title as schedule,
            br.name as barangay,
            CONCAT(b.last_name, ', ', b.first_name) as beneficiary,
            u.name as verified_by,
            (SELECT GROUP_CONCAT(CONCAT(rp.name, ' x ', dti.quantity) SEPARATOR ', ')
               FROM distribution_transaction_items dti
               JOIN relief_packs rp ON rp.id = dti.relief_pack_id
              WHERE dti.distribution_transaction_id = dt.id) as items
        ")
        ->orderByDesc('claimed_at')
        ->get();

    $claimed = $rows->where('status', $claim);
    return [
        'columns' => [
            ['key' => 'claimed_at',  'label' => 'Date Claimed'],
            ['key' => 'schedule',    'label' => 'Schedule'],
            ['key' => 'barangay',    'label' => 'Barangay'],
            ['key' => 'beneficiary', 'label' => 'Beneficiary'],
            ['key' => 'items',       'label' => 'Items Released'],
            ['key' => 'boxes',       'label' => 'Boxes', 'align' => 'right'],
            ['key' => 'status',      'label' => 'Status'],
            ['key' => 'verified_by', 'label' => 'Verified By'],
        ],
        'rows' => $rows->map(fn ($r) => [
            'claimed_at'  => Carbon::parse($r->claimed_at)->format('M j, Y g:i A'),
            'schedule'    => $r->schedule,
            'barangay'    => $r->barangay,
            'beneficiary' => $r->beneficiary,
            'items'       => $r->items ?? '—',
            'boxes'       => (int) $r->quantity_boxes,
            'status'      => ucfirst($r->status),
            'verified_by' => $r->verified_by ?? '—',
        ])->values(),
        'summary' => [
            ['label' => 'Total Records',   'value' => $rows->count()],
            ['label' => 'Total Claims',    'value' => $claimed->count()],
            ['label' => 'Boxes Released',  'value' => (int) $claimed->sum('quantity_boxes')],
        ],
    ];
}

    private function beneficiaries(array $f): array
    {
        $rows = DB::table('beneficiaries as b')
            ->join('barangays as br', 'br.id', '=', 'b.barangay_id')
            ->when($f['barangay_id'], fn ($q, $v) => $q->where('b.barangay_id', $v))
            ->when($f['from'], fn ($q, $v) => $q->whereDate('b.created_at', '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate('b.created_at', '<=', $v))
            ->selectRaw('br.name as barangay, COUNT(*) as beneficiaries, SUM(b.household_members) as members')
            ->groupBy('br.id', 'br.name')
            ->orderByDesc('beneficiaries')
            ->get();

        return [
            'columns' => [
                ['key' => 'barangay',      'label' => 'Barangay'],
                ['key' => 'beneficiaries', 'label' => 'Registered Beneficiaries', 'align' => 'right'],
                ['key' => 'members',       'label' => 'Total Household Members',  'align' => 'right'],
            ],
            'rows' => $rows->map(fn ($r) => [
                'barangay'      => $r->barangay,
                'beneficiaries' => (int) $r->beneficiaries,
                'members'       => (int) $r->members,
            ])->values(),
            'summary' => [
                ['label' => 'Barangays',     'value' => $rows->count()],
                ['label' => 'Beneficiaries', 'value' => (int) $rows->sum('beneficiaries')],
                ['label' => 'Household Members', 'value' => (int) $rows->sum('members')],
            ],
        ];
    }

    private function inventory(array $f): array
    {
        // Received: date filter uses pack_receipts.date_received (barangay/schedule filters don't apply)
        $received = DB::table('relief_stocks as rs')
            ->join('pack_receipts as pr', 'pr.id', '=', 'rs.pack_receipt_id')
            ->when($f['from'], fn ($q, $v) => $q->whereDate('pr.date_received', '>=', $v))
            ->when($f['to'], fn ($q, $v) => $q->whereDate('pr.date_received', '<=', $v))
            ->groupBy('rs.relief_pack_id')
            ->selectRaw('rs.relief_pack_id, SUM(rs.quantity) as total')
            ->pluck('total', 'relief_pack_id');

        // Allocated to schedules (planned)
        $allocated = $this->scheduleFilters(
            DB::table('distribution_relief_stocks as dsr')
                ->join('distribution_schedules as ds', 'ds.id', '=', 'dsr.distribution_sched_id'),
            $f
        )
            ->groupBy('dsr.relief_pack_id')
            ->selectRaw('dsr.relief_pack_id, SUM(dsr.quantity) as total')
            ->pluck('total', 'relief_pack_id');

        // Released (actually claimed)
        $released = $this->scheduleFilters(
            DB::table('distribution_transaction_items as dti')
                ->join('distribution_transactions as dt', 'dt.id', '=', 'dti.distribution_transaction_id')
                ->join('distribution_schedules as ds', 'ds.id', '=', 'dt.distribution_schedule_id')
                ->where('dt.status', ClaimStatus::Claim->value),
            $f
        )
            ->groupBy('dti.relief_pack_id')
            ->selectRaw('dti.relief_pack_id, SUM(dti.quantity) as total')
            ->pluck('total', 'relief_pack_id');

        $rows = DB::table('relief_packs')->orderBy('name')->get(['id', 'name'])->map(function ($p) use ($received, $allocated, $released) {
            $rec = (float) ($received[$p->id] ?? 0);
            $all = (float) ($allocated[$p->id] ?? 0);
            $rel = (float) ($released[$p->id] ?? 0);

            return [
                'pack'      => $p->name,
                'received'  => round($rec, 2),
                'allocated' => round($all, 2),
                'released'  => round($rel, 2),
                'remaining' => round($rec - $rel, 2), // accurate when no filters are applied
            ];
        });

        return [
            'columns' => [
                ['key' => 'pack',      'label' => 'Relief Pack'],
                ['key' => 'received',  'label' => 'Received',            'align' => 'right'],
                ['key' => 'allocated', 'label' => 'Allocated (Planned)', 'align' => 'right'],
                ['key' => 'released',  'label' => 'Released (Claimed)',  'align' => 'right'],
                ['key' => 'remaining', 'label' => 'Remaining',           'align' => 'right'],
            ],
            'rows' => $rows->values(),
            'summary' => [
                ['label' => 'Total Received',  'value' => round($rows->sum('received'), 2)],
                ['label' => 'Total Released',  'value' => round($rows->sum('released'), 2)],
                ['label' => 'Total Remaining', 'value' => round($rows->sum('remaining'), 2)],
            ],
        ];
    }

    private function schedules(array $f): array
    {
        $claim = ClaimStatus::Claim->value;

        $rows = $this->scheduleFilters(
            DB::table('distribution_schedules as ds')
                ->join('barangays as br', 'br.id', '=', 'ds.barangay_id'),
            $f
        )
            ->selectRaw("
                ds.id, ds.title, ds.date, ds.status,
                br.name as barangay,
                (SELECT COALESCE(SUM(dsr.quantity), 0) FROM distribution_relief_stocks dsr
                  WHERE dsr.distribution_sched_id = ds.id) as planned,
                (SELECT COUNT(*) FROM distribution_transactions dt
                  WHERE dt.distribution_schedule_id = ds.id AND dt.status = ?) as claimed
            ", [$claim])
            ->orderByDesc('ds.date')
            ->get();

        return [
            'columns' => [
                ['key' => 'title',    'label' => 'Schedule'],
                ['key' => 'date',     'label' => 'Date'],
                ['key' => 'barangay', 'label' => 'Barangay'],
                ['key' => 'status',   'label' => 'Status'],
                ['key' => 'planned',  'label' => 'Planned',  'align' => 'right'],
                ['key' => 'claimed',  'label' => 'Claimed',  'align' => 'right'],
            ],
            'rows' => $rows->map(fn ($r) => [
                'title'    => $r->title,
                'date'     => Carbon::parse($r->date)->format('M j, Y'),
                'barangay' => $r->barangay,
                'status'   => ucfirst($r->status),
                'planned'  => round((float) $r->planned, 2),
                'claimed'  => (int) $r->claimed,
            ])->values(),
            'summary' => [
                ['label' => 'Schedules', 'value' => $rows->count()],
                ['label' => 'Completed', 'value' => $rows->where('status', 'completed')->count()],
                ['label' => 'Total Claimed', 'value' => (int) $rows->sum('claimed')],
            ],
        ];
    }
}