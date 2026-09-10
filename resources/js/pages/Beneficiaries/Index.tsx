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
import { deleteMethod, index as beneficiariesIndex, edit, index } from '@/routes/beneficiaries';
import FlashAlerts from '@/components/flash-alerts';

interface barangay {
    id: number;
    name: string;
}

interface beneficiary {
    id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    gender: string;
    household_members: number;
    qr_code: string;
    status: 'unclaimed' | 'claimed';
    barangay: barangay;
}

interface PaginatedBeneficiaries {
    data: beneficiary[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    beneficiaries: PaginatedBeneficiaries;
    filters: { search?: string; barangay_id?: string };
    flash: {
        message?: string;
        error?: string;
    };
}

function StatusBadge({ status }: { status: string }) {
    const isClaimed = status === 'claimed';

    return (
        <span
            className={`inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium capitalize ${
                isClaimed
                    ? 'border-emerald-400/40 bg-emerald-100 text-emerald-800'
                    : 'border-brand-orange/40 bg-[#d1d5db] text-[#7a3b12]'
            }`}
        >
            {status}
        </span>
    );
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { flash, beneficiaries, filters } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');

    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                beneficiariesIndex().url,
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

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <>
            <Head title="Beneficiaries" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Beneficiaries
                    </h1>
                    <Link href={'/beneficiaries/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New Beneficiary
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
                                placeholder="Search by name"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
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
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Name</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Barangay</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Gender</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Household</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">QR Code</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Status</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {beneficiaries.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-10 text-center text-sm text-subtle">
                                        No beneficiaries found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {beneficiaries.data.map((beneficiary) => (
                                <TableRow
                                    key={beneficiary.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {[beneficiary.first_name, beneficiary.middle_name, beneficiary.last_name]
                                            .filter(Boolean)
                                            .join(' ')}
                                    </TableCell>
                                    <TableCell className="text-ink">{beneficiary.barangay.name}</TableCell>
                                    <TableCell className="capitalize text-ink">{beneficiary.gender}</TableCell>
                                    <TableCell className="text-ink">{beneficiary.household_members}</TableCell>
                                    <TableCell className="font-mono text-xs text-subtle">{beneficiary.qr_code}</TableCell>
                                    <TableCell>
                                        <StatusBadge status={beneficiary.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(beneficiary.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() =>
                                                    handleDelete(
                                                        beneficiary.id,
                                                        [beneficiary.first_name, beneficiary.last_name].join(' '),
                                                    )
                                                }
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

                    {beneficiaries.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#d1d5db] px-5 py-3">
                            {beneficiaries.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${
                                        link.active
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
            title: 'Beneficiaries',
            href: '/beneficiaries',
        },
    ],
};