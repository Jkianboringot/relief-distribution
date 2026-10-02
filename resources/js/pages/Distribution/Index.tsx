import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Pencil, Trash2 } from 'lucide-react';
import FlashAlerts from '@/components/flash-alerts';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import { destroy, edit, show } from '@/routes/distribution';

interface ScheduleItem {
    id: number;
    name: string;
    quantity: number;
}

interface Schedule {
    id: number;
    title: string;
    date: string | null;
    location: string | null;
    barangay: string;
    status: 'pending' | 'ongoing' | 'completed';
    claimed_count: number;
    total_quantity: number;
    items: ScheduleItem[];
}

interface PageProps {
    schedules: Schedule[];
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

function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        pending: 'border-[#d1d5db] bg-[#e5e7eb] text-[#374151]',
        ongoing: 'border-brand-orange/40 bg-[#fde8d7] text-[#7a3b12]',
        completed: 'border-emerald-400/40 bg-emerald-100 text-emerald-800',
    };

    return (
        <span
            className={`inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium capitalize ${styles[status] ?? styles.pending}`}
        >
            {status}
        </span>
    );
}

export default function Index() {
    const can = useCan();
    const { flash, schedules = [] } = usePage<PageProps & Record<string, unknown>>()
        .props as unknown as PageProps;

    const handleDelete = (schedule: Schedule) => {
        if (!window.confirm(`Delete "${schedule.title}"? This cannot be undone.`)) {
            return;
        }

        router.delete(destroy(schedule.id).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Distribution Schedules" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Distribution Schedules
                    </h1>
                    {can('distributions.create') && (
                        <Link href="/distribution/create">
                            <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                                New Schedule
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Title</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Barangay</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Box Type</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Status</TableHead>

                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Date</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Claimed / Planned</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {schedules.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-10 text-center text-sm text-subtle">
                                        No distribution schedules yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {schedules.map((schedule) => (
                                <TableRow
                                    key={schedule.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        <div className="flex items-center gap-2">
                                            <CalendarDays className="h-4 w-4 text-brand-orange" />
                                            {schedule.title}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-ink">{formatDate(schedule.date)}</TableCell>
                                    <TableCell className="text-ink">{schedule.barangay}</TableCell>
                                    <TableCell className="text-ink">
                                        {(schedule.items ?? []).length === 0 ? (
                                            '—'
                                        ) : (
                                            <ul className="space-y-0.5 text-sm">
                                                {schedule.items.map((item) => (
                                                    <li key={item.id}>
                                                        {item.name} × {item.quantity}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-ink">
                                        {schedule.claimed_count} / {schedule.total_quantity}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge status={schedule.status} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={show(schedule.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                View / Claim
                                            </Link>
                                            {can('distributions.update') && (
                                                <Link
                                                    href={edit(schedule.id).url}
                                                    className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                    Edit
                                                </Link>
                                            )}
                                            {can('distributions.delete') && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(schedule)}
                                                    className="flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                    Delete
                                                </button>
                                            )}
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
            title: 'Distribution',
            href: '/distribution',
        },
    ],
};