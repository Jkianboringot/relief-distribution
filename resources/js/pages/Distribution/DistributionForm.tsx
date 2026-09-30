// DistributionForm.tsx — full file
import { useForm } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type Barangay = {
    id: number;
    name: string;
};

export type DistributionFormData = {
    title: string;
    date: string;
    location: string;
    barangay_id: string;
};

type Props = {
    barangays: Barangay[];
    initial: DistributionFormData;
    method: 'post' | 'put';
    url: string;
    submitLabel: string;
};

export default function DistributionForm({ barangays, initial, method, url, submitLabel }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<DistributionFormData>(initial);
    const err = errors as Record<string, string | undefined>;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (method === 'post') post(url);
        else put(url);
    };

    const errorMessages = Object.values(errors).filter(Boolean) as string[];

    return (
        <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border border-[#d1d5db] bg-white p-5">
            {errorMessages.length > 0 && (
                <Alert variant="destructive">
                    <CircleAlert />
                    <AlertTitle>Something's not right</AlertTitle>
                    <AlertDescription>
                        <ul className="list-inside list-disc text-sm">
                            {errorMessages.map((message, i) => (
                                <li key={i}>{message}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            )}

            <div className="space-y-1">
                <Label htmlFor="title" className="font-semibold text-ink">Title</Label>
                <Input
                    id="title"
                    type="text"
                    placeholder="e.g. Typhoon Response – Barangay Distribution"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="border-[#e0d0c0]"
                />
                {err.title && <p className="mt-1.5 text-sm text-danger">{err.title}</p>}
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                    <Label htmlFor="date" className="font-semibold text-ink">Date</Label>
                    <Input
                        id="date"
                        type="date"
                        value={data.date}
                        onChange={(e) => setData('date', e.target.value)}
                        className="border-[#e0d0c0]"
                    />
                    {err.date && <p className="mt-1.5 text-sm text-danger">{err.date}</p>}
                </div>

                <div className="space-y-1">
                    <Label htmlFor="location" className="font-semibold text-ink">Location</Label>
                    <Input
                        id="location"
                        type="text"
                        placeholder="e.g. Barangay Hall"
                        value={data.location}
                        onChange={(e) => setData('location', e.target.value)}
                        className="border-[#e0d0c0]"
                    />
                    {err.location && <p className="mt-1.5 text-sm text-danger">{err.location}</p>}
                </div>
            </div>

            <div className="space-y-1">
                <Label htmlFor="barangay_id" className="font-semibold text-ink">Barangay</Label>
                <Select value={data.barangay_id || undefined} onValueChange={(value) => setData('barangay_id', value)}>
                    <SelectTrigger id="barangay_id" className="w-full border-[#e0d0c0] bg-white">
                        <SelectValue placeholder="Select barangay…" />
                    </SelectTrigger>
                    <SelectContent>
                        {barangays.map((b) => (
                            <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {err.barangay_id && <p className="mt-1.5 text-sm text-danger">{err.barangay_id}</p>}
            </div>

            <Alert>
                <CircleAlert />
                <AlertTitle>No pack quantities here</AlertTitle>
                <AlertDescription>
                    Relief packs and quantities are set on the Allocations page, per beneficiary, after
                    creating this schedule.
                </AlertDescription>
            </Alert>

            <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                <Button
                    type="submit"
                    disabled={processing}
                    className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                >
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}