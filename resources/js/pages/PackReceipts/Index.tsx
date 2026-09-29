import { useEffect, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Pencil, Search, Trash2, X } from 'lucide-react';
import { deleteMethod, edit, index } from '@/routes/pack-receipts';
import FlashAlerts from '@/components/flash-alerts';

interface ReceiptItem {
    id: number;
    name: string;
    quantity: number;
}

interface PackReceipt {
    id: number;
    source_name: string;
    date_received: string;
    total_quantity: number;
    items: ReceiptItem[];
}

interface PaginatedPackReceipts {
    data: PackReceipt[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    packReceipts: PaginatedPackReceipts;
    filters: { search?: string };
    flash: {
        message?: string;
        error?: string;
    };
}

const MAX_VISIBLE = 3;

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// "2026-09-16T00:00:00.000000Z" -> "Sep 16, 2026"
// Reads the date part straight from the string so timezones can't shift the day.
const formatDate = (value: string | null): string => {
    if (!value) return '—';
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);
    if (!year || !month || !day) return value;
    return `${MONTHS[month - 1]} ${day}, ${year}`;
};

// Laravel's paginator labels are always one of these three shapes —
// render icons for prev/next instead of trusting raw HTML entities.
function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { flash, packReceipts, filters } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');
    const [expanded, setExpanded] = useState<Set<number>>(new Set());

    // Debounced: typing only updates local state immediately. The request
    // waits until 400ms after the user stops typing. The guard stops it from
    // firing when `search` already matches what the server returned, which
    // keeps StrictMode's duplicate effect call from resetting pagination.
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

    function clearSearch() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    }

    const toggleExpanded = (id: number) => {
        setExpanded((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const handleDelete = (id: number, sourceName: string) => {
        if (confirm(`Delete receipt from "${sourceName}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <>
            <Head title="Pack Receipts" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Pack Receipts
                    </h1>
                    <Link href={'/pack-receipts/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New Receipt
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <div className="flex items-center justify-end gap-3 border-b border-[#d1d5db] px-5 py-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by source name"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
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
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Source Name</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Date Received</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Relief Packs</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Total Quantity</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {packReceipts.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-10 text-center text-sm text-subtle">
                                        No pack receipts found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {packReceipts.data.map((receipt) => {
                                const isExpanded = expanded.has(receipt.id);
                                const visibleItems = isExpanded
                                    ? receipt.items
                                    : receipt.items.slice(0, MAX_VISIBLE);
                                const hiddenCount = receipt.items.length - MAX_VISIBLE;

                                return (
                                    <TableRow
                                        key={receipt.id}
                                        className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                    >
                                        <TableCell className="align-top font-medium text-[#7a3b12]">
                                            {receipt.source_name}
                                        </TableCell>
                                        <TableCell className="align-top text-ink">
                                            {formatDate(receipt.date_received)}
                                        </TableCell>
                                        <TableCell className="align-top text-ink">
                                            {receipt.items.length === 0 ? (
                                                <span className="text-sm text-subtle">No items</span>
                                            ) : (
                                                <ul className="space-y-1">
                                                    {visibleItems.map((item) => (
                                                        <li key={item.id} className="flex items-center gap-2 text-sm">
                                                            <span>{item.name}</span>
                                                            <span className="font-semibold text-brand-orange-hover">
                                                                × {item.quantity}
                                                            </span>
                                                        </li>
                                                    ))}
                                                    {hiddenCount > 0 && (
                                                        <li>
                                                            <button
                                                                type="button"
                                                                onClick={() => toggleExpanded(receipt.id)}
                                                                className="text-xs font-semibold text-brand-orange hover:text-brand-orange-hover"
                                                            >
                                                                {isExpanded ? 'Show less' : `Show ${hiddenCount} more`}
                                                            </button>
                                                        </li>
                                                    )}
                                                </ul>
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top font-semibold text-ink">
                                            {receipt.total_quantity}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <div className="flex items-center justify-end gap-4">
                                                <Link
                                                    href={edit(receipt.id).url}
                                                    className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    Edit
                                                </Link>

                                                <button
                                                    type="button"
                                                    disabled={processing}
                                                    onClick={() => handleDelete(receipt.id, receipt.source_name)}
                                                    className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger disabled:opacity-50"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    Delete
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    {packReceipts.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#d1d5db] px-5 py-3">
                            {packReceipts.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${link.active
                                        ? 'bg-brand-orange text-white'
                                        : 'text-brand-orange-hover hover:bg-[#d1d5db]'
                                        } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Pack Receipts',
            href: '/pack-receipts',
        },
    ],
};