import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface BranchSale {
    id: number;
    location: string;
    total_sale: number;
    sale_count: number;
}

interface Props {
    overallTotal: number;
    branchSales: BranchSale[];
}

export default function Dashboard({ overallTotal, branchSales }: Props) {
    const topBranch = branchSales[0];
    const totalTransactions = branchSales.reduce((sum, b) => sum + b.sale_count, 0);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <SummaryCard label="Overall Total Sales" value={`₱${overallTotal.toFixed(2)}`} />
                    <SummaryCard
                        label="Top Branch"
                        value={topBranch ? topBranch.location : '—'}
                        sub={topBranch ? `₱${topBranch.total_sale.toFixed(2)}` : undefined}
                    />
                    <SummaryCard label="Total Transactions" value={String(totalTransactions)} />
                </div>

                <div className="relative flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="p-6">
                        <h2 className="mb-4 text-sm font-semibold text-ink">Sales by Branch</h2>
                        <div className="space-y-3">
                            {branchSales.map((branch) => {
                                const pct = overallTotal > 0 ? (branch.total_sale / overallTotal) * 100 : 0;
                                return (
                                    <div key={branch.id} className="rounded-lg border border-[#f0ddc8] bg-white/60 p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <span className="text-sm font-medium text-ink">{branch.location}</span>
                                            <span className="text-sm font-semibold text-ink">₱{branch.total_sale.toFixed(2)}</span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-[#f0ddc8]">
                                            <div className="h-full rounded-full bg-brand-orange" style={{ width: `${pct}%` }} />
                                        </div>
                                        <div className="mt-1 text-xs text-subtle">
                                            {branch.sale_count} sale{branch.sale_count === 1 ? '' : 's'} · {pct.toFixed(1)}% of total
                                        </div>
                                    </div>
                                );
                            })}
                            {branchSales.length === 0 && <p className="text-sm text-subtle">No sales recorded yet.</p>}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function SummaryCard({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-[#fdf8f2] p-5 dark:border-sidebar-border">
            <p className="text-xs font-medium text-subtle">{label}</p>
            <p className="mt-1 text-2xl font-extrabold tracking-tight text-ink">{value}</p>
            {sub && <p className="mt-0.5 text-sm text-subtle">{sub}</p>}
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};