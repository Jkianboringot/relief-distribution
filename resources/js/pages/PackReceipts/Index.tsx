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
import { Search, Trash2, X } from 'lucide-react';
import { deleteMethod } from '@/routes/pack-receipts';
import FlashAlerts from '@/components/flash-alerts';

interface PackReceipt {
    id: number;
    source_name: string;
    date_received: string;
    relief_stock_count: number;
}

interface PageProps {
    packReceipts: PackReceipt[];
    flash: {
        message?: string;
        error?: string;
    };
}

export default function Index() {
    const { flash, packReceipts } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const [search, setSearch] = useState('');
    const { processing, delete: destroyForm } = useForm();

    const filteredReceipts = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return packReceipts;
        return packReceipts.filter((receipt) => receipt.source_name.toLowerCase().includes(query));
    }, [packReceipts, search]);

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
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Source Name</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Date Received</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Items</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredReceipts.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="py-10 text-center text-sm text-subtle">
                                        No pack receipts found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {filteredReceipts.map((receipt) => (
                                <TableRow
                                    key={receipt.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">{receipt.source_name}</TableCell>
                                    <TableCell className="text-ink">{receipt.date_received}</TableCell>
                                    <TableCell className="text-ink">{receipt.relief_stock_count}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
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
            title: 'Pack Receipts',
            href: '/pack-receipts',
        },
    ],
};