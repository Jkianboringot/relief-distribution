import { useMemo, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
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
import { Pencil, Search, Trash2, X } from 'lucide-react';
import { deleteMethod, edit } from '@/routes/relief-packs';
import FlashAlerts from '@/components/flash-alerts';

interface ReliefPack {
    id: number;
    name: string;
    description: string | null;
    current_stock: number;
    receipts_count: number;
}

interface PageProps {
    reliefPacks: ReliefPack[];
    flash: {
        message?: string;
        error?: string;
    };
}

function StockBadge({ stock }: { stock: number }) {
    const isEmpty = stock === 0;
    const isLow = stock > 0 && stock <= 10;

    return (
        <span
            className={`inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium ${
                isEmpty
                    ? 'border-danger/40 bg-red-100 text-red-800'
                    : isLow
                      ? 'border-brand-orange/40 bg-[#fde8d7] text-[#7a3b12]'
                      : 'border-emerald-400/40 bg-emerald-100 text-emerald-800'
            }`}
        >
            {stock} {stock === 1 ? 'box' : 'boxes'}
        </span>
    );
}

export default function Index() {
    const { flash, reliefPacks } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const [search, setSearch] = useState('');
    const { processing, delete: destroyForm } = useForm();

    const filteredPacks = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return reliefPacks;
        return reliefPacks.filter((pack) => pack.name.toLowerCase().includes(query));
    }, [reliefPacks, search]);

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <>
            <Head title="Relief Packs" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Relief Packs (Boxes)
                    </h1>
                    <Link href={'/relief-packs/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New Box Type
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
                                placeholder="Search by box name"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
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
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Box Name</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Description</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Current Stock</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Receipts</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredPacks.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-10 text-center text-sm text-subtle">
                                        No box types found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {filteredPacks.map((pack) => (
                                <TableRow
                                    key={pack.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">{pack.name}</TableCell>
                                    <TableCell className="text-ink">{pack.description ?? '—'}</TableCell>
                                    <TableCell>
                                        <StockBadge stock={pack.current_stock} />
                                    </TableCell>
                                    <TableCell className="text-ink">{pack.receipts_count}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(pack.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(pack.id, pack.name)}
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
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Relief Packs',
            href: '/relief-packs',
        },
    ],
};