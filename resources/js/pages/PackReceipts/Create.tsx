import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert, Plus, Trash2 } from 'lucide-react';
import { store } from '@/routes/pack-receipts';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashAlerts from '@/components/flash-alerts';

interface ReliefPack {
    id: number;
    name: string;
}

interface ReliefListItem {
    relief_pack_id: string;
    quantity: string;
}

interface ReceiptForm {
    source_name: string;
    date_received: string;
    reliefList: ReliefListItem[];
}

interface Props {
    reliefPacks: ReliefPack[];
}

export default function Create({ reliefPacks }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, post, processing, errors } = useForm<ReceiptForm>({
        source_name: '',
        date_received: '',
        reliefList: [{ relief_pack_id: '', quantity: '' }],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    const addRow = () => {
        setData('reliefList', [...data.reliefList, { relief_pack_id: '', quantity: '' }]);
    };

    const removeRow = (index: number) => {
        setData(
            'reliefList',
            data.reliefList.filter((_, i) => i !== index),
        );
    };

    const updateRow = (index: number, field: keyof ReliefListItem, value: string) => {
        const updated = [...data.reliefList];
        updated[index] = { ...updated[index], [field]: value };
        setData('reliefList', updated);
    };

    return (
        <>
            <Head title="New Pack Receipt" />

            <div className="mx-auto w-full max-w-3xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Pack Receipt</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Record relief goods received into stock.
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

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="source_name" className="font-semibold text-ink">
                                Source Name
                            </Label>
                            <Input
                                id="source_name"
                                type="text"
                                placeholder="e.g. DSWD Regional Office"
                                value={data.source_name}
                                onChange={(e) => setData('source_name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.source_name && (
                                <p className="mt-1.5 text-sm text-danger">{errors.source_name}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="date_received" className="font-semibold text-ink">
                                Date Received
                            </Label>
                            <Input
                                id="date_received"
                                type="date"
                                value={data.date_received}
                                onChange={(e) => setData('date_received', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.date_received && (
                                <p className="mt-1.5 text-sm text-danger">{errors.date_received}</p>
                            )}
                        </div>
                    </div>

                    <div className="border-t border-[#d1d5db] pt-4">
                        <div className="mb-2 flex items-center justify-between">
                            <Label className="font-semibold text-ink">Relief Packs</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addRow}
                                className="gap-1"
                            >
                                <Plus className="h-4 w-4" />
                                Add Item
                            </Button>
                        </div>

                        <div className="space-y-2">
                            {data.reliefList.map((row, index) => {
                                const rowErrors = errors as Record<string, string>;
                                const packError = rowErrors[`reliefList.${index}.relief_pack_id`];
                                const qtyError = rowErrors[`reliefList.${index}.quantity`];

                                return (
                                    <div key={index} className="flex items-start gap-2">
                                        <div className="flex-1">
                                            <Select
                                                value={row.relief_pack_id || undefined}
                                                onValueChange={(value) => updateRow(index, 'relief_pack_id', value)}
                                            >
                                                <SelectTrigger className="w-full bg-white">
                                                    <SelectValue placeholder="Select relief pack…" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {reliefPacks.map((pack) => (
                                                        <SelectItem key={pack.id} value={String(pack.id)}>
                                                            {pack.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {packError && <p className="mt-1.5 text-sm text-danger">{packError}</p>}
                                        </div>

                                        <div className="w-32">
                                            <Input
                                                type="number"
                                                min={1}
                                                placeholder="Qty"
                                                value={row.quantity}
                                                onChange={(e) => updateRow(index, 'quantity', e.target.value)}
                                                className="border-[#e0d0c0]"
                                            />
                                            {qtyError && <p className="mt-1.5 text-sm text-danger">{qtyError}</p>}
                                        </div>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            disabled={data.reliefList.length === 1}
                                            onClick={() => removeRow(index)}
                                            className="mt-0.5 text-danger hover:bg-transparent hover:text-danger/80"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                        {errors.reliefList && (
                            <p className="mt-1.5 text-sm text-danger">{errors.reliefList}</p>
                        )}
                    </div>

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Save Receipt
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Pack Receipts', href: '/pack-receipts' },
        { title: 'New Receipt', href: '/pack-receipts/create' },
    ],
};