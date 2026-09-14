import { useRef } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CircleAlert, QrCode, Undo2 } from 'lucide-react';
import { claim } from '@/routes/distribution';
import { destroy } from '@/routes/distribution-transactions';
import FlashAlerts from '@/components/flash-alerts';

interface Beneficiary {
    id: number;
    family_head_name: string;
    barangay: string;
    family_size: number;
}

interface Transaction {
    id: number;
    quantity_boxes: number;
    verification_timestamp: string;
    status: 'claimed' | 'pending';
    beneficiary: Beneficiary;
    verified_by: { id: number; name: string } | null;
}

interface Schedule {
    id: number;
    title: string;
    date: string;
    location: string | null;
    barangay: string;
    planned_quantity: number;
    status: string;
    relief_pack: { id: number; name: string; current_stock: number };
    transactions: Transaction[];
}

interface PageProps {
    schedule: Schedule;
    remainingAllocation: number;
    flash: {
        message?: string;
        error?: string;
    };
}

export default function Show() {
    const { flash, schedule, remainingAllocation } = usePage<PageProps & Record<string, unknown>>()
        .props as unknown as PageProps;

    const { data, setData, post, processing, errors, reset } = useForm({ qr_code: '' });
    const qrInputRef = useRef<HTMLInputElement>(null);

    const handleClaim = (e: React.FormEvent) => {
        e.preventDefault();
        post(claim(schedule.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                reset('qr_code');
                qrInputRef.current?.focus();
            },
        });
    };

    const handleReverse = (transactionId: number, familyHeadName: string) => {
        if (confirm(`Reverse the claim for "${familyHeadName}"? This restores 1 box to stock.`)) {
            router.delete(destroy(transactionId).url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={schedule.title} />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        {schedule.title}
                    </h1>
                    <p className="mt-1 text-sm text-subtle">
                        {schedule.date} · {schedule.barangay}
                        {schedule.location ? ` · ${schedule.location}` : ''} · Box: {schedule.relief_pack.name}
                    </p>
                </div>

                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Planned</p>
                        <p className="text-2xl font-extrabold text-ink">{schedule.planned_quantity}</p>
                    </div>
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Claimed</p>
                        <p className="text-2xl font-extrabold text-ink">{schedule.transactions.length}</p>
                    </div>
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Remaining Allocation</p>
                        <p className="text-2xl font-extrabold text-brand-orange">{remainingAllocation}</p>
                    </div>
                </div>

                <div className="mb-6 rounded-xl border border-[#d1d5db] bg-white p-5">
                    <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-ink">
                        <QrCode className="h-5 w-5 text-brand-orange" />
                        Scan / Enter QR to Release Box
                    </h2>
                    <p className="mb-3 text-sm text-subtle">
                        1 box is released per family head. A family head can only claim once for this schedule.
                    </p>

                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive" className="mb-3">
                            <CircleAlert />
                            <AlertTitle>Cannot release box</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    <form onSubmit={handleClaim} className="flex items-end gap-3">
                        <div className="flex-1 space-y-1">
                            <Label htmlFor="qr_code" className="font-semibold text-ink">
                                Beneficiary QR Code
                            </Label>
                            <Input
                                id="qr_code"
                                ref={qrInputRef}
                                type="text"
                                autoFocus
                                placeholder="Scan or type QR code"
                                value={data.qr_code}
                                onChange={(e) => setData('qr_code', e.target.value)}
                                className="border-[#e0d0c0] font-mono"
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={processing || remainingAllocation < 1}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Release Box
                        </Button>
                    </form>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-white">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Family Head</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Barangay</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Family Size</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Verified By</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Time</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {schedule.transactions.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No boxes released yet for this schedule.
                                    </TableCell>
                                </TableRow>
                            )}
                            {schedule.transactions.map((tx) => (
                                <TableRow
                                    key={tx.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {tx.beneficiary.family_head_name}
                                    </TableCell>
                                    <TableCell className="text-ink">{tx.beneficiary.barangay}</TableCell>
                                    <TableCell className="text-ink">{tx.beneficiary.family_size}</TableCell>
                                    <TableCell className="text-ink">{tx.verified_by?.name ?? '—'}</TableCell>
                                    <TableCell className="text-ink">
                                        {new Date(tx.verification_timestamp).toLocaleString()}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => handleReverse(tx.id, tx.beneficiary.family_head_name)}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger"
                                            >
                                                <Undo2 className="h-4 w-4" />
                                                Reverse
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

Show.layout = {
    breadcrumbs: [
        { title: 'Distribution', href: '/distribution' },
        { title: 'Schedule' },
    ],
};