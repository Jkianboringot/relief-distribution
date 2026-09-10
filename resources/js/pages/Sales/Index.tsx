import { useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Megaphone, Search, X } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { index } from '@/routes/sales';

interface Sale {
    id: number;
    inventory: string | null;
    branch: string;
    shift: string;
    cash_advance: number | null;
    cash_shortage: number | null;
    remitted_expenses: number | null;
    cash_amount: number | null;
    gcash_amount: number | null;
    net_cash: number;
    created_at: string;
}

interface PaginatedSales {
    data: Sale[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    sales: PaginatedSales;
    filters: { search?: string };
    flash?: { success?: string; error?: string };
}

function SaleBranchBadge({ type }: { type: string }) {
    return (
        <span className="inline-flex items-center rounded-full border border-brand-orange/40 bg-[#fbead9] px-3 py-0.5 text-xs font-medium capitalize text-[#7a3b12]">
            {type}
        </span>
    );
}

function InOutTypeBadge({ type }: { type: string }) {
    return (
        <span className="inline-flex items-center rounded-full border border-red-300 bg-red-100 px-3 py-0.5 text-xs font-medium capitalize text-red-700">
            {type}
        </span>
    );
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { sales, filters, flash } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const [search, setSearch] = useState(filters?.search ?? '');

    // Debounced: typing only updates local state immediately. The actual
    // request waits until 400ms after the user stops typing. Guarded
    // against firing when `search` already matches the server-confirmed
    // filters.search — prevents a stray/duplicate effect fire (e.g. React
    // StrictMode's double-invoke) from silently resetting pagination.
    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    // Bypasses the debounce entirely for an instant clear — safe because
    // the effect's guard above no-ops once filters.search catches up.
    function clearSearch() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    }

    return (
        <div className="p-4">
            {flash?.success && (
                <div className="mb-4">
                    <Alert>
                        <Megaphone />
                        <AlertTitle>Notification</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                </div>
            )}
            {flash?.error && (
                <div className="mb-4">
                    <Alert variant="destructive">
                        <Megaphone />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                </div>
            )}
            <Head title="Sales" />

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">Sale</h1>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#f0ddc8] bg-[#fdf8f2]">
                    <div className="flex items-center justify-end gap-3 border-b border-[#f0ddc8] px-5 py-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search"
                                className="w-56 border-brand-orange/40 bg-white pl-9 pr-8 text-sm"
                            />
                              {search.length >= 100 && (

                                <p className="absolute left-0 top-full mb-10 text-xs text-danger">
                                    Search can't be longer than 100 characters.
                                </p>
                            )}
                            {search && (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    aria-label="Clear search"
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-subtle hover:text-brand-orange"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#f0ddc8] bg-[#fbead9] hover:bg-[#fbead9]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Inventory#</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Branch</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Shift</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Cash on Hand</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Gcash Amount</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Cash Advance</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Cash Shortage</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Remitted</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Total Cash</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sales.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={10} className="py-10 text-center text-sm text-subtle">
                                        No sales found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {sales.data.map((sale) => (
                                <TableRow key={sale.id} className="border-b border-[#f0ddc8] last:border-0 hover:bg-[#fbf3e8]">
                                    <TableCell className="font-medium text-[#7a3b12]">{sale.inventory}</TableCell>
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        <SaleBranchBadge type={sale.branch} />
                                    </TableCell>
                                    <TableCell>
                                        <InOutTypeBadge type={sale.shift} />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.cash_amount !== null ? `₱${sale.cash_amount}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.gcash_amount !== null ? `₱${sale.gcash_amount}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.cash_advance !== null ? `₱${sale.cash_advance}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.cash_shortage !== null ? `₱${sale.cash_shortage}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.remitted_expenses !== null ? `₱${sale.remitted_expenses}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {sale.net_cash !== null ? `₱${sale.net_cash}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-subtle">{sale.created_at}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {sales.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#f0ddc8] px-5 py-3">
                            {sales.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${link.active
                                            ? 'bg-brand-orange text-white'
                                            : 'text-brand-orange-hover hover:bg-[#fbead9]'
                                        } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Sales',
            href: '/sales',
        },
    ],
};