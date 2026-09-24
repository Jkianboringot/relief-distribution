import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CalendarDays } from 'lucide-react';
import { show } from '@/routes/distribution';
import FlashAlerts from '@/components/flash-alerts';

interface Schedule {
    id: number;
    title: string;
    date: string;
    barangay: string;
    planned_quantity: number;
    claimed_count: number;
    status: 'pending' | 'ongoing' | 'completed';
    relief_pack: { id: number; name: string; current_stock: number };
}

interface PageProps {
    schedules: Schedule[];
    flash: {
        message?: string;
        error?: string;
    };
}

function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        pending: 'border-[#d1d5db] bg-[#e5e7eb] text-[#374151]',
        ongoing: 'border-brand-orange/40 bg-[#fde8d7] text-[#7a3b12]',
        completed: 'border-emerald-400/40 bg-emerald-100 text-emerald-800',
    };

    return (
        <span className={`inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium capitalize ${styles[status]}`}>
            {status}
        </span>
    );
}

export default function Index() {
    const { flash, schedules } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;

    return (
        <>
            <Head title="Distribution Schedules" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Distribution Schedules
                    </h1>
                    <Link href={'/distribution/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New Schedule
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Title</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Date</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Barangay</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Box Type</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Claimed / Planned</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Status</TableHead>
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
                                    <TableCell className="flex items-center gap-2 font-medium text-[#7a3b12]">
                                        <CalendarDays className="h-4 w-4 text-brand-orange" />
                                        {schedule.title}
                                    </TableCell>
                                    <TableCell className="text-ink">{schedule.date}</TableCell>
                                    <TableCell className="text-ink">{schedule.barangay}</TableCell>
                                    <TableCell className="text-ink">{schedule.relief_pack.name}</TableCell>
                                    <TableCell className="text-ink">
                                        {schedule.claimed_count} / {schedule.planned_quantity}
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