import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ArrowDownCircle, ArrowUpCircle, ChevronLeft, ChevronRight, Pencil, Search, Trash2, X } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { createIn, createOut, deleteMethod, editIn, editOut, index as inventoriesIndex } from '@/routes/inventories';
import FlashAlerts from '@/components/flash-alerts';

interface InventoryRow {
    id: number;
    type: string;
    inventory_type: string | null;
    stock_movement_type: string | null;
    barangay: string | null;
    encoder: string | null;
    cash_amount: number | null;
    net_cash: number | null;
    created_at: string;
}

interface PaginatedInventories {
    data: InventoryRow[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    inventories: PaginatedInventories;
    filters: { search?: string };
    flash?: { message?: string; error?: string };
}

// Laravel's paginator labels are always one of these three shapes —
// render icons for prev/next instead of trusting raw HTML entities.
function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { inventories, filters, flash } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const [search, setSearch] = useState(filters?.search ?? '');
    const { processing, delete: destroyForm } = useForm();

    // Debounced: typing only updates local state immediately. The actual
    // request waits until 400ms after the user stops typing. Guarded
    // against firing when `search` already matches the server-confirmed
    // filters.search — prevents a stray/duplicate effect fire (e.g. React
    // StrictMode's double-invoke) from silently resetting pagination.
    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                inventoriesIndex().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    // Bypasses the debounce for an instant clear.
    function clearSearch() {
        setSearch('');
        router.get(inventoriesIndex().url, {}, { preserveState: true, replace: true });
    }

    const handleDelete = (id: number, label: string) => {
        if (confirm(`Delete "${label}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <div className="p-6">
            <FlashAlerts flash={flash} />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">Inventories</h1>
                </div>
                <div className="flex gap-2">
                    <Link href={createIn().url}>
                        <Button className="bg-green-600 font-bold text-white hover:bg-green-700">
                            <ArrowDownCircle className="h-4 w-4" />
                            IN
                        </Button>
                    </Link>
                    <Link href={createOut().url}>
                        <Button className="bg-red-600 font-bold text-white hover:bg-red-700">
                            <ArrowUpCircle className="h-4 w-4" />
                            OUT
                        </Button>
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                <div className="flex items-center justify-end gap-3 border-b border-[#d1d5db] px-5 py-3">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search"
                            className="w-56 border-brand-orange/40 bg-white pl-9 pr-8 text-sm"
                        />
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
                        <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db]  hover:bg-[#d1d5db] ">
                            <TableHead>#</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>barangay</TableHead>
                            <TableHead>Inventory Type</TableHead>
                            <TableHead>Encoder</TableHead>
                            <TableHead className="text-right">Cash on Hand</TableHead>
                            <TableHead className="text-right">Total Cash</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {inventories.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={9} className="py-10 text-center text-sm text-subtle">
                                    No inventory records yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {inventories.data.map((inv) => (
                            <TableRow
                                key={inv.id}
                                className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9] "
                            >
                                <TableCell className="text-subtle">{inv.id}</TableCell>
                                <TableCell>
                                    <span
                                        className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                            inv.inventory_type === 'IN'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        }`}
                                    >
                                        {inv.inventory_type}
                                    </span>
                                </TableCell>
                                <TableCell className="font-medium text-[#7a3b12]">{inv.barangay ?? '—'}</TableCell>
                                <TableCell>{inv.stock_movement_type ?? '—'}</TableCell>
                                <TableCell>{inv.encoder ?? '—'}</TableCell>
                                <TableCell className="text-right">
                                    {inv.cash_amount !== null ? `₱${inv.cash_amount}` : '—'}
                                </TableCell>
                                <TableCell className="text-right font-medium text-[#7a3b12]">
                                    {inv.net_cash !== null ? `₱${inv.net_cash}` : '—'}
                                </TableCell>
                                <TableCell className="text-subtle">{inv.created_at}</TableCell>
                                <TableCell>
                                    <div className="flex items-center justify-end gap-4">
                                        <Link
                                            href={inv.inventory_type === 'in' ? editIn(inv.id).url : editOut(inv.id).url}
                                            className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                        >
                                            <Pencil className="h-4 w-4" />
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            disabled={processing}
                                            onClick={() => handleDelete(inv.id, inv.barangay ?? `#${inv.id}`)}
                                            className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger disabled:opacity-50"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Delete
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {inventories.links.length > 3 && (
                    <div className="flex gap-1 border-t border-[#d1d5db] px-5 py-3">
                        {inventories.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                className={`flex items-center rounded-md px-3 py-1 text-sm ${
                                    link.active
                                        ? 'bg-brand-orange text-white'
                                        : 'text-brand-orange-hover hover:bg-[#d1d5db] '
                                } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                            >
                                {paginationLabel(link.label)}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Inventories',
            href: '/inventories',
        },
    ],
};