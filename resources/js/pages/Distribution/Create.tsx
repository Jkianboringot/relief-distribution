import { Head, usePage } from '@inertiajs/react';
import FlashAlerts from '@/components/flash-alerts';
import { store } from '@/routes/distribution';
import DistributionForm from './DistributionForm';
import type { Barangay, ReliefPack } from './DistributionForm';

type Props = {
    reliefPacks: ReliefPack[];
    barangays: Barangay[];
};

export default function Create({ reliefPacks, barangays }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;

    return (
        <>
            <Head title="New Distribution" />

            <div className="mx-auto w-full max-w-3xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Distribution</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Record the relief packs going out to a barangay.
                    </p>
                </div>

                <DistributionForm
                    reliefPacks={reliefPacks}
                    barangays={barangays}
                    method="post"
                    url={store().url}
                    submitLabel="Create Distribution"
                    initial={{
                        title: '',
                        date: '',
                        location: '',
                        barangay_id: '',
                        reliefList: [{ relief_pack_id: '', quantity: '' }],
                    }}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Distribution', href: '/distribution' },
        { title: 'New Distribution', href: '/distribution/create' },
    ],
};