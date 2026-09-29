import { useForm } from '@inertiajs/react';
import { CircleAlert, Plus, Trash2 } from 'lucide-react';
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

export type ReliefPack = {
    id: number;
    name: string;
    current_stock: number;
};

export type Barangay = {
    id: number;
    name: string;
};

export type ReliefLine = {
    relief_pack_id: string;
    quantity: string;
};

export type DistributionFormData = {
    title: string;
    date: string;
    location: string;
    barangay_id: string;
    reliefList: ReliefLine[];
};

type Props = {
    reliefPacks: ReliefPack[];
    barangays: Barangay[];
    initial: DistributionFormData;
    method: 'post' | 'put';
    url: string;
    submitLabel: string;
};

export default function DistributionForm({
    reliefPacks,
    barangays,
    initial,
    method,
    url,
    submitLabel,
}: Props) {
    const { data, setData, post, put, processing, errors } =
        useForm<DistributionFormData>(initial);

    const err = errors as Record<string, string | undefined>;

    // When editing, this schedule's own saved quantities count as free stock again.
    // `initial` is only the saved lines on the edit page, and empty on create.
    const originalQty = new Map<string, number>();
    initial.reliefList.forEach((l) => {
        if (l.relief_pack_id) {
            originalQty.set(
                l.relief_pack_id,
                (originalQty.get(l.relief_pack_id) ?? 0) + (Number(l.quantity) || 0),
            );
        }
    });

    const availableFor = (packId: string): number | undefined => {
        const pack = reliefPacks.find((p) => String(p.id) === packId);
        if (!pack) return undefined;
        return pack.current_stock + (originalQty.get(packId) ?? 0);
    };

    const chosenIds = data.reliefList.map((l) => l.relief_pack_id);

    const setLine = (index: number, patch: Partial<ReliefLine>) =>
        setData(
            'reliefList',
            data.reliefList.map((line, i) => (i === index ? { ...line, ...patch } : line)),
        );

    const addLine = () =>
        setData('reliefList', [...data.reliefList, { relief_pack_id: '', quantity: '' }]);

    const removeLine = (index: number) =>
        setData(
            'reliefList',
            data.reliefList.filter((_, i) => i !== index),
        );

    const totalBoxes = data.reliefList.reduce((sum, l) => sum + (Number(l.quantity) || 0), 0);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (method === 'post') {
            post(url);
        } else {
            put(url);
        }
    };

    const errorMessages = Object.values(errors).filter(Boolean) as string[];

    return (
        <form
            onSubmit={handleSubmit}
            className="space-y-4 rounded-xl border border-[#d1d5db] bg-white p-5"
        >
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
                {err.title && <p className="mt-1.5 text-sm text-danger">{err.title}</p>}
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
                    {err.date && <p className="mt-1.5 text-sm text-danger">{err.date}</p>}
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
                    {err.location && <p className="mt-1.5 text-sm text-danger">{err.location}</p>}
                </div>
            </div>

            <div className="space-y-1">
                <Label htmlFor="barangay_id" className="font-semibold text-ink">
                    Barangay
                </Label>
                <Select
                    value={data.barangay_id || undefined}
                    onValueChange={(value) => setData('barangay_id', value)}
                >
                    <SelectTrigger id="barangay_id" className="w-full border-[#e0d0c0] bg-white">
                        <SelectValue placeholder="Select barangay…" />
                    </SelectTrigger>
                    <SelectContent>
                        {barangays.map((b) => (
                            <SelectItem key={b.id} value={String(b.id)}>
                                {b.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {err.barangay_id && (
                    <p className="mt-1.5 text-sm text-danger">{err.barangay_id}</p>
                )}
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label className="font-semibold text-ink">Relief Packs</Label>
                    <span className="text-xs text-subtle">Total: {totalBoxes} boxes</span>
                </div>

                {data.reliefList.map((line, index) => {
                    const available = availableFor(line.relief_pack_id);

                    return (
                        <div key={index} className="space-y-1 rounded-lg border border-[#e5e7eb] p-3">
                            <div className="grid grid-cols-[1fr_9rem_auto] items-start gap-3">
                                <div>
                                    <Select
                                        value={line.relief_pack_id || undefined}
                                        onValueChange={(value) =>
                                            setLine(index, { relief_pack_id: value })
                                        }
                                    >
                                        <SelectTrigger className="w-full bg-white">
                                            <SelectValue placeholder="Select box type…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {reliefPacks
                                                .filter(
                                                    (p) =>
                                                        String(p.id) === line.relief_pack_id ||
                                                        !chosenIds.includes(String(p.id)),
                                                )
                                                .map((pack) => (
                                                    <SelectItem key={pack.id} value={String(pack.id)}>
                                                        {pack.name} (
                                                        {pack.current_stock +
                                                            (originalQty.get(String(pack.id)) ?? 0)}{' '}
                                                        in stock)
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Input
                                    type="number"
                                    min={1}
                                    max={available}
                                    placeholder="Qty"
                                    value={line.quantity}
                                    onChange={(e) => setLine(index, { quantity: e.target.value })}
                                    className="border-[#e0d0c0]"
                                />

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeLine(index)}
                                    disabled={data.reliefList.length === 1}
                                    aria-label="Remove line"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>

                            {available !== undefined && (
                                <p className="text-xs text-subtle">{available} boxes available.</p>
                            )}
                            {err[`reliefList.${index}.relief_pack_id`] && (
                                <p className="text-sm text-danger">
                                    {err[`reliefList.${index}.relief_pack_id`]}
                                </p>
                            )}
                            {err[`reliefList.${index}.quantity`] && (
                                <p className="text-sm text-danger">
                                    {err[`reliefList.${index}.quantity`]}
                                </p>
                            )}
                        </div>
                    );
                })}

                {err.reliefList && <p className="text-sm text-danger">{err.reliefList}</p>}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addLine}
                    disabled={data.reliefList.length >= reliefPacks.length}
                >
                    <Plus className="mr-1 size-4" />
                    Add another pack
                </Button>
            </div>

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