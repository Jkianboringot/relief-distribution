import { Head, usePage } from '@inertiajs/react';
import FlashAlerts from '@/components/flash-alerts';
import { update } from '@/routes/distribution';
import DistributionForm from './DistributionForm';
import type { Barangay, ReliefPack } from './DistributionForm';

type Schedule = {
    id: number;
    title: string;
    date: string;
    location: string | null;
    barangay_id: number | null;
    reliefList: { relief_pack_id: number; quantity: number }[];
};

type Props = {
    schedule: Schedule;
    reliefPacks: ReliefPack[];
    barangays: Barangay[];
};

export default function Edit({ schedule, reliefPacks, barangays }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;

    return (
        <>
            <Head title="Edit Distribution" />

            <div className="mx-auto w-full max-w-3xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Distribution</h1>
                    <p className="mt-0.5 text-sm text-subtle">{schedule.title}</p>
                </div>

                <DistributionForm
                    reliefPacks={reliefPacks}
                    barangays={barangays}
                    method="put"
                    url={update(schedule.id).url}
                    submitLabel="Save Changes"
                    initial={{
                        title: schedule.title,
                        date: schedule.date,
                        location: schedule.location ?? '',
                        barangay_id: schedule.barangay_id ? String(schedule.barangay_id) : '',
                        reliefList: schedule.reliefList.map((l) => ({
                            relief_pack_id: String(l.relief_pack_id),
                            quantity: String(l.quantity),
                        })),
                    }}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Distribution', href: '/distribution' },
        { title: 'Edit Distribution', href: '#' },
    ],
};