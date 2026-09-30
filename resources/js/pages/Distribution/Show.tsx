import { useCallback, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Camera, CameraOff, CircleAlert, QrCode, Undo2 } from 'lucide-react';
import FlashAlerts from '@/components/flash-alerts';
import QrScanner from '@/components/qr-scanner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { claim, status as updateStatus } from '@/routes/distribution';
import { edit as editAllocations } from '@/routes/distribution/allocations';
import { destroy } from '@/routes/distribution-transactions';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
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
    beneficiary: Beneficiary | null;
    verified_by: { id: number; name: string } | null;
}

interface ReliefStock {
    id: number;
    relief_pack_id: number;
    quantity: number;
    relief_pack: { id: number; name: string } | null;
}

interface Schedule {
    id: number;
    title: string;
    date: string | null;
    location: string | null;
    barangay: string;
    status: 'pending' | 'ongoing' | 'completed';
    relief_stock: ReliefStock[];
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

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' });
    return `${month} ${date.getDate()}, ${date.getFullYear()}`;
}

export default function Show() {
    const { flash, schedule, remainingAllocation } = usePage<PageProps & Record<string, unknown>>()
        .props as unknown as PageProps;

    const reliefStock = schedule.relief_stock ?? [];
    const transactions = schedule.transactions ?? [];

    const plannedQuantity = reliefStock.reduce((sum, s) => sum + (Number(s.quantity) || 0), 0);
    const claimedCount = transactions.filter((t) => t.status === 'claimed').length;
    const remaining = remainingAllocation ?? 0;

    const isOngoing = schedule.status === 'ongoing';
    const outOfStock = remaining < 1;
    const canClaim = isOngoing && !outOfStock;

    const packSummary =
        reliefStock.length === 0
            ? '—'
            : reliefStock
                  .map((s) => `${s.relief_pack?.name ?? 'Unknown pack'} × ${s.quantity}`)
                  .join(', ');

    const { data, setData, post, processing, errors, reset, transform, setError, clearErrors } =
        useForm({ qr_code: '' });
    const qrInputRef = useRef<HTMLInputElement>(null);
    const [cameraOn, setCameraOn] = useState(false);
    // Camera fires repeatedly while a code stays in frame; this blocks duplicate submits.
    const submittingRef = useRef(false);

    // Hardware scanners can append whitespace/newlines.
    transform((d) => ({ ...d, qr_code: d.qr_code.trim() }));

    const handleClaim = (e: React.FormEvent) => {
        e.preventDefault();
        if (!canClaim || processing || data.qr_code.trim() === '') return;

        post(claim(schedule.id).url, {
            preserveScroll: true,
            // Runs on success AND on error, so the field is always ready for the next scan.
            onFinish: () => {
                reset('qr_code');
                qrInputRef.current?.focus();
            },
        });
    };

    const submitClaim = useCallback(
        (code: string) => {
            if (!canClaim || processing || submittingRef.current) return;

            submittingRef.current = true;
            clearErrors();

            router.post(
                claim(schedule.id).url,
                { qr_code: code },
                {
                    preserveScroll: true,
                    // router.post doesn't feed useForm's errors, so pass them along manually.
                    onError: (errs) => {
                        if (errs.qr_code) setError('qr_code', errs.qr_code);
                    },
                    onFinish: () => {
                        submittingRef.current = false;
                        reset('qr_code');
                        qrInputRef.current?.focus();
                    },
                },
            );
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [canClaim, processing, schedule.id],
    );

    // Camera decodes the same printed QR that encodes the full /scan/{qr_code}
    // URL, so pull the code back out the same way the dedicated scan page does.
    const handleCameraScan = useCallback(
        (decodedText: string) => {
            let code = decodedText.trim();
            try {
                const url = new URL(code);
                const parts = url.pathname.split('/').filter(Boolean);
                code = parts[parts.length - 1] ?? code;
            } catch {
                // not a URL — assume it's the raw code
            }
            submitClaim(code);
        },
        [submitClaim],
    );

    const handleReverse = (transactionId: number, familyHeadName: string) => {
        if (
            confirm(
                `Reverse the claim for "${familyHeadName}"? This frees 1 box and lets them claim again.`,
            )
        ) {
            router.delete(destroy(transactionId).url, { preserveScroll: true });
        }
    };

    const handleStatusChange = (next: 'ongoing' | 'completed') => {
        const message =
            next === 'ongoing'
                ? 'Start this distribution? Claims will open.'
                : 'Complete this distribution? Claims will close and cannot be reopened.';

        if (confirm(message)) {
            router.patch(updateStatus(schedule.id).url, { status: next }, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={schedule.title} />

            <div className="p-6">
                {/* Shows service messages: already claimed, stock zero, not eligible, wrong barangay, etc. */}
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                                {schedule.title}
                            </h1>

                            {schedule.status === 'pending' && (
                                <Button
                                    type="button"
                                    onClick={() => handleStatusChange('ongoing')}
                                    className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                                >
                                    Start Distribution
                                </Button>
                            )}
                            {schedule.status === 'ongoing' && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => handleStatusChange('completed')}
                                >
                                    Complete Distribution
                                </Button>
                            )}

                            <Link href={editAllocations(schedule.id).url}>
                                <Button type="button" variant="outline">
                                    Manage Allocations
                                </Button>
                            </Link>

                            <span
                                className={
                                    'rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide ' +
                                    (schedule.status === 'ongoing'
                                        ? 'bg-green-100 text-green-700'
                                        : schedule.status === 'completed'
                                          ? 'bg-gray-200 text-gray-700'
                                          : 'bg-orange-100 text-orange-700')
                                }
                            >
                                {schedule.status}
                            </span>
                        </div>
                        <p className="mt-1 text-sm text-subtle">
                            {formatDate(schedule.date)} · {schedule.barangay}
                            {schedule.location ? ` · ${schedule.location}` : ''} · Boxes: {packSummary}
                        </p>
                    </div>
                </div>

                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Planned</p>
                        <p className="text-2xl font-extrabold text-ink">{plannedQuantity}</p>
                    </div>
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Claimed</p>
                        <p className="text-2xl font-extrabold text-ink">{claimedCount}</p>
                    </div>
                    <div className="rounded-xl border border-[#d1d5db] bg-white p-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-subtle">Remaining Allocation</p>
                        <p className="text-2xl font-extrabold text-brand-orange">{remaining}</p>
                    </div>
                </div>

                <div className="mb-6 rounded-xl border border-[#d1d5db] bg-white p-5">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="flex items-center gap-2 text-lg font-bold text-ink">
                            <QrCode className="h-5 w-5 text-brand-orange" />
                            Scan / Enter QR to Release Box
                        </h2>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!canClaim}
                            onClick={() => setCameraOn((v) => !v)}
                        >
                            {cameraOn ? (
                                <>
                                    <CameraOff className="mr-1.5 h-4 w-4" />
                                    Stop Camera
                                </>
                            ) : (
                                <>
                                    <Camera className="mr-1.5 h-4 w-4" />
                                    Use Camera
                                </>
                            )}
                        </Button>
                    </div>

                    <p className="mb-3 text-sm text-subtle">
                        Each family head receives their assigned entitlement per pack. A family head can only
                        claim once for this schedule.
                    </p>

                    {cameraOn && canClaim && (
                        <QrScanner active={cameraOn} onScan={handleCameraScan} className="mb-4" />
                    )}

                    {!isOngoing && (
                        <Alert className="mb-3">
                            <CircleAlert />
                            <AlertTitle>Distribution is {schedule.status}</AlertTitle>
                            <AlertDescription>
                                Boxes can only be released while the distribution is ongoing.
                            </AlertDescription>
                        </Alert>
                    )}

                    {isOngoing && outOfStock && (
                        <Alert variant="destructive" className="mb-3">
                            <CircleAlert />
                            <AlertTitle>Stock is zero</AlertTitle>
                            <AlertDescription>
                                All boxes for this distribution have been released.
                            </AlertDescription>
                        </Alert>
                    )}

                    {errors.qr_code && (
                        <Alert variant="destructive" className="mb-3">
                            <CircleAlert />
                            <AlertTitle>Cannot release box</AlertTitle>
                            <AlertDescription>{errors.qr_code}</AlertDescription>
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
                                autoComplete="off"
                                disabled={!canClaim}
                                placeholder="Scan or type QR code"
                                value={data.qr_code}
                                onChange={(e) => setData('qr_code', e.target.value)}
                                className="border-[#e0d0c0] font-mono"
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={processing || !canClaim || data.qr_code.trim() === ''}
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
                            {transactions.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No boxes released yet for this schedule.
                                    </TableCell>
                                </TableRow>
                            )}
                            {transactions.map((tx) => (
                                <TableRow
                                    key={tx.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {tx.beneficiary?.family_head_name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-ink">{tx.beneficiary?.barangay ?? '—'}</TableCell>
                                    <TableCell className="text-ink">{tx.beneficiary?.family_size ?? '—'}</TableCell>
                                    <TableCell className="text-ink">{tx.verified_by?.name ?? '—'}</TableCell>
                                    <TableCell className="text-ink">
                                        {tx.verification_timestamp
                                            ? new Date(tx.verification_timestamp).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleReverse(
                                                        tx.id,
                                                        tx.beneficiary?.family_head_name ?? 'this beneficiary',
                                                    )
                                                }
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