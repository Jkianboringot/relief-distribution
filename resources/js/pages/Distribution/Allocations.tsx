import { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { CircleAlert, Plus, Search, Trash2 } from 'lucide-react';
import FlashAlerts from '@/components/flash-alerts';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface ReliefPack {
    id: number;
    name: string;
    default_entitlement: number;
}

interface Allocation {
    id: number;
    beneficiary_id: number;
    beneficiary_name: string;
    household_members: number | null;
    relief_pack_id: number;
    relief_pack_name: string;
    quantity: number;
}
interface ReliefPack {
    id: number;
    name: string;
    available: number;
}

interface BeneficiaryResult {
    id: number;
    name: string;
    household_members: number;
}

interface PageProps {
    schedule: { id: number; title: string; barangay: string };
    reliefPacks: ReliefPack[];
    allocations: Allocation[];
    flash: { message?: string; error?: string };
}

// One row waiting to be saved — not yet sent to the server.
interface PendingRow {
    beneficiary_id: number;
    beneficiary_name: string;
    relief_pack_id: string;
    quantity: string;
}

export default function Allocations() {
    const { schedule, reliefPacks, allocations, flash } = usePage<PageProps & Record<string, unknown>>()
        .props as unknown as PageProps;

    const [search, setSearch] = useState('');
    const [results, setResults] = useState<BeneficiaryResult[]>([]);
    const [searching, setSearching] = useState(false);
    const [selected, setSelected] = useState<BeneficiaryResult | null>(null);
    const [packId, setPackId] = useState('');
    const [quantity, setQuantity] = useState('2');
    const [pending, setPending] = useState<PendingRow[]>([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (search.trim().length < 2) {
            setResults([]);
            return;
        }

        setSearching(true);
        const timeout = setTimeout(async () => {
            const res = await fetch(
                `/distribution/${schedule.id}/allocations/search?q=${encodeURIComponent(search)}`,
            );
            const data: BeneficiaryResult[] = await res.json();
            setResults(data);
            setSearching(false);
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, schedule.id]);

    function pickBeneficiary(b: BeneficiaryResult) {
        setSelected(b);
        setSearch(b.name);
        setResults([]);
    }

    function addPendingRow() {
        if (!selected || !packId || !quantity) return;

        setPending((prev) => [
            ...prev.filter(
                (r) => !(r.beneficiary_id === selected.id && r.relief_pack_id === packId),
            ),
            {
                beneficiary_id: selected.id,
                beneficiary_name: selected.name,
                relief_pack_id: packId,
                quantity,
            },
        ]);

        setSelected(null);
        setSearch('');
        setPackId('');
        setQuantity('2');
    }

    function removePendingRow(index: number) {
        setPending((prev) => prev.filter((_, i) => i !== index));
    }

    function saveAll() {
        if (pending.length === 0) return;

        setSaving(true);
        router.post(
            `/distribution/${schedule.id}/allocations`,
            {
                allocations: pending.map((r) => ({
                    beneficiary_id: r.beneficiary_id,
                    relief_pack_id: Number(r.relief_pack_id),
                    quantity: Number(r.quantity),
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => setPending([]),
                onFinish: () => setSaving(false),
            },
        );
    }

    function deleteSaved(allocationId: number, name: string, packName: string) {
        if (confirm(`Remove the ${packName} override for ${name}? They'll fall back to the default amount.`)) {
            router.delete(`/allocations/${allocationId}`, { preserveScroll: true });
        }
    }

    const packName = (id: string) => reliefPacks.find((p) => String(p.id) === id)?.name ?? '';

    return (
        <>
            <Head title={`Allocations — ${schedule.title}`} />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">{schedule.title}</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        {schedule.barangay} · Pre-assign a different amount for specific families before the event.
                        Anyone not listed here still gets the schedule's normal amount.
                    </p>
                </div>

                <div className="mb-6 rounded-xl border border-[#d1d5db] bg-white p-5">
                    <h2 className="mb-3 text-lg font-bold text-ink">Add an override</h2>

                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_10rem_8rem_auto]">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setSelected(null);
                                }}
                                placeholder="Search family head by name…"
                                className="border-[#e0d0c0] pl-9"
                            />
                            {results.length > 0 && (
                                <div className="absolute z-10 mt-1 w-full rounded-md border border-[#d1d5db] bg-white shadow-md">
                                    {results.map((b) => (
                                        <button
                                            key={b.id}
                                            type="button"
                                            onClick={() => pickBeneficiary(b)}
                                            className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-[#f3f4f6]"
                                        >
                                            <span>{b.name}</span>
                                            <span className="text-xs text-subtle">{b.household_members} members</span>
                                        </button>
                                    ))}
                                </div>
                            )}
                            {searching && (
                                <p className="mt-1 text-xs text-subtle">Searching…</p>
                            )}
                        </div>

                        <Select value={packId || undefined} onValueChange={setPackId}>
                            <SelectTrigger className="w-full bg-white">
                                <SelectValue placeholder="Pack…" />
                            </SelectTrigger>
                            <SelectContent>
                                {reliefPacks.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.name} ({p.available} available)
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Input
                            type="number"
                            min={0}
                            value={quantity}
                            onChange={(e) => setQuantity(e.target.value)}
                            placeholder="Qty"
                            className="border-[#e0d0c0]"
                        />

                        <Button
                            type="button"
                            onClick={addPendingRow}
                            disabled={!selected || !packId || quantity === ''}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                        >
                            <Plus className="mr-1 size-4" />
                            Add
                        </Button>
                    </div>

                    {selected && (
                        <p className="mt-2 text-xs text-subtle">
                            Selected: {selected.name} ({selected.household_members} members)
                        </p>
                    )}
                </div>

                {pending.length > 0 && (
                    <div className="mb-6 rounded-xl border border-brand-orange/40 bg-white p-5">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-lg font-bold text-ink">Not yet saved</h2>
                            <Button
                                type="button"
                                onClick={saveAll}
                                disabled={saving}
                                className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                            >
                                Save {pending.length} override{pending.length === 1 ? '' : 's'}
                            </Button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Family Head</TableHead>
                                    <TableHead>Pack</TableHead>
                                    <TableHead>Quantity</TableHead>
                                    <TableHead className="text-right">Remove</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pending.map((row, i) => (
                                    <TableRow key={i}>
                                        <TableCell>{row.beneficiary_name}</TableCell>
                                        <TableCell>{packName(row.relief_pack_id)}</TableCell>
                                        <TableCell>{row.quantity}</TableCell>
                                        <TableCell className="text-right">
                                            <button
                                                type="button"
                                                onClick={() => removePendingRow(i)}
                                                className="text-subtle hover:text-danger"
                                            >
                                                <Trash2 className="size-4" />
                                            </button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-white">
                    <div className="border-b border-[#d1d5db] px-5 py-3">
                        <h2 className="text-lg font-bold text-ink">Saved overrides</h2>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold text-brand-orange-hover">Family Head</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Household</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Pack</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Quantity</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {allocations.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-10 text-center text-sm text-subtle">
                                        No overrides yet. Everyone claims at the default amount.
                                    </TableCell>
                                </TableRow>
                            )}
                            {allocations.map((a) => (
                                <TableRow key={a.id} className="border-b border-[#d1d5db] last:border-0">
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {a.beneficiary_name}
                                    </TableCell>
                                    <TableCell className="text-ink">{a.household_members ?? '—'}</TableCell>
                                    <TableCell className="text-ink">{a.relief_pack_name}</TableCell>
                                    <TableCell className="text-ink">{a.quantity}</TableCell>
                                    <TableCell className="text-right">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                deleteSaved(a.id, a.beneficiary_name, a.relief_pack_name)
                                            }
                                            className="flex items-center justify-end gap-1 text-sm font-medium text-ink hover:text-danger"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Remove
                                        </button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Alert className="mt-6">
                    <CircleAlert />
                    <AlertTitle>How this works</AlertTitle>
                    <AlertDescription>
                        A saved override only changes the amount for that one family head, for that one pack,
                        on this schedule. Every other pack, and every other family head, still uses the
                        schedule's normal amount automatically.
                    </AlertDescription>
                </Alert>
            </div>
        </>
    );
}

Allocations.layout = {
    breadcrumbs: [
        { title: 'Distribution', href: '/distribution' },
        { title: 'Allocations' },
    ],
};