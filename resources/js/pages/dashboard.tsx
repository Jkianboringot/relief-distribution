import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface barangaySale {
    id: number;
    location: string;
    total_sale: number;
    sale_count: number;
}

interface Props {
    overallTotal: number;
    barangaySales: barangaySale[];
}

export default function Dashboard({ overallTotal, barangaySales }: Props) {
    const topbarangay = barangaySales[0];
    const totalTransactions = barangaySales.reduce((sum, b) => sum + b.sale_count, 0);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <SummaryCard label="Overall Total Sales" value={`₱${overallTotal.toFixed(2)}`} />
                    <SummaryCard
                        label="Top barangay"
                        value={topbarangay ? topbarangay.location : '—'}
                        sub={topbarangay ? `₱${topbarangay.total_sale.toFixed(2)}` : undefined}
                    />
                    <SummaryCard label="Total Transactions" value={String(totalTransactions)} />
                </div>

                <div className="relative flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="p-6">
                        <h2 className="mb-4 text-sm font-semibold text-ink">Sales by barangay</h2>
                        <div className="space-y-3">
                            {barangaySales.map((barangay) => {
                                const pct = overallTotal > 0 ? (barangay.total_sale / overallTotal) * 100 : 0;
                                return (
                                    <div key={barangay.id} className="rounded-lg border border-[#d1d5db] bg-white/60 p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <span className="text-sm font-medium text-ink">{barangay.location}</span>
                                            <span className="text-sm font-semibold text-ink">₱{barangay.total_sale.toFixed(2)}</span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-[#f0ddc8]">
                                            <div className="h-full rounded-full bg-brand-orange" style={{ width: `${pct}%` }} />
                                        </div>
                                        <div className="mt-1 text-xs text-subtle">
                                            {barangay.sale_count} sale{barangay.sale_count === 1 ? '' : 's'} · {pct.toFixed(1)}% of total
                                        </div>
                                    </div>
                                );
                            })}
                            {barangaySales.length === 0 && <p className="text-sm text-subtle">No sales recorded yet.</p>}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function SummaryCard({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-[#ffffff] p-5 dark:border-sidebar-border">
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