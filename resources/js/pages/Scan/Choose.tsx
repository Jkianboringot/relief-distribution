import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface ScheduleOption {
    id: number;
    title: string;
    date: string | null;
}

interface PageProps {
    qr_code: string;
    beneficiary_name: string;
    schedules: ScheduleOption[];
}

export default function Choose({ qr_code, beneficiary_name, schedules }: PageProps) {
    function pick(scheduleId: number) {
        router.post(`/scan/${qr_code}/confirm`, { schedule_id: scheduleId });
    }

    return (
        <>
            <Head title="Choose Distribution" />

            <div className="mx-auto max-w-md p-8">
                <h1 className="text-xl font-bold text-ink">More than one distribution is open</h1>
                <p className="mt-1 text-sm text-subtle">
                    {beneficiary_name} is eligible in more than one active schedule right now. Pick which one.
                </p>

                <div className="mt-6 space-y-3">
                    {schedules.map((s) => (
                        <button
                            key={s.id}
                            type="button"
                            onClick={() => pick(s.id)}
                            className="w-full rounded-xl border border-[#d1d5db] bg-white p-4 text-left hover:bg-[#f3f4f6]"
                        >
                            <p className="font-semibold text-ink">{s.title}</p>
                            <p className="text-sm text-subtle">{s.date ?? 'No date'}</p>
                        </button>
                    ))}
                </div>
            </div>
        </>
    );
}