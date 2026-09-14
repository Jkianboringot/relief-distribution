import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { store } from '@/routes/distribution';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashAlerts from '@/components/flash-alerts';

interface ReliefPack {
    id: number;
    name: string;
    current_stock: number;
}

interface ScheduleForm {
    title: string;
    date: string;
    location: string;
    barangay: string;
    relief_pack_id: string;
    planned_quantity: string;
}

interface Props {
    reliefPacks: ReliefPack[];
}

export default function Create({ reliefPacks }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, post, processing, errors } = useForm<ScheduleForm>({
        title: '',
        date: '',
        location: '',
        barangay: '',
        relief_pack_id: '',
        planned_quantity: '',
    });

    const selectedPack = reliefPacks.find((p) => String(p.id) === data.relief_pack_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="New Distribution Schedule" />

            <div className="mx-auto w-full max-w-3xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Distribution Schedule</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Plan a relief distribution event for a barangay.
                    </p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-[#d1d5db] bg-white p-5"
                >
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="space-y-1">
                        <Label htmlFor="title" className="font-semibold text-ink">
                            Title
                        </Label>
                        <Input
                            id="title"
                            type="text"
                            placeholder="e.g. Typhoon Response – Barangay Distribution"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.title && <p className="mt-1.5 text-sm text-danger">{errors.title}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="date" className="font-semibold text-ink">
                                Date
                            </Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) => setData('date', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.date && <p className="mt-1.5 text-sm text-danger">{errors.date}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="location" className="font-semibold text-ink">
                                Location
                            </Label>
                            <Input
                                id="location"
                                type="text"
                                placeholder="e.g. Barangay Hall"
                                value={data.location}
                                onChange={(e) => setData('location', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.location && <p className="mt-1.5 text-sm text-danger">{errors.location}</p>}
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="barangay" className="font-semibold text-ink">
                            Barangay
                        </Label>
                        <Input
                            id="barangay"
                            type="text"
                            placeholder="Target barangay"
                            value={data.barangay}
                            onChange={(e) => setData('barangay', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.barangay && <p className="mt-1.5 text-sm text-danger">{errors.barangay}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="relief_pack_id">Box Type</Label>
                            <Select
                                value={data.relief_pack_id || undefined}
                                onValueChange={(value) => setData('relief_pack_id', value)}
                            >
                                <SelectTrigger id="relief_pack_id" className="mt-1.5 w-full bg-white">
                                    <SelectValue placeholder="Select box type…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {reliefPacks.map((pack) => (
                                        <SelectItem key={pack.id} value={String(pack.id)}>
                                            {pack.name} ({pack.current_stock} in stock)
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.relief_pack_id && (
                                <p className="mt-1.5 text-sm text-danger">{errors.relief_pack_id}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="planned_quantity" className="font-semibold text-ink">
                                Planned Quantity (boxes)
                            </Label>
                            <Input
                                id="planned_quantity"
                                type="number"
                                min={1}
                                max={selectedPack?.current_stock ?? undefined}
                                placeholder="No. of boxes to allocate"
                                value={data.planned_quantity}
                                onChange={(e) => setData('planned_quantity', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {selectedPack && (
                                <p className="mt-1 text-xs text-subtle">
                                    {selectedPack.current_stock} boxes currently in stock.
                                </p>
                            )}
                            {errors.planned_quantity && (
                                <p className="mt-1.5 text-sm text-danger">{errors.planned_quantity}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Create Schedule
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Distribution', href: '/distribution' },
        { title: 'New Schedule', href: '/distribution/create' },
    ],
};  