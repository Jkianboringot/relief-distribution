import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert, PackagePlus } from 'lucide-react';
import { receive } from '@/routes/relief-packs';
import FlashAlerts from '@/components/flash-alerts';

interface ReliefPack {
    id: number;
    name: string;
    current_stock: number;
}

interface Props {
    reliefPack: ReliefPack;
}

export default function Receive({ reliefPack }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        source_name: '',
        quantity_received: '',
        date_received: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(receive(reliefPack.id).url, {
            onSuccess: () => reset('source_name', 'quantity_received', 'date_received'),
        });
    };

    return (
        <>
            <Head title={`Receive Boxes – ${reliefPack.name}`} />

            <div className="mx-auto w-full max-w-2xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="flex items-center gap-2 text-2xl font-bold text-ink">
                        <PackagePlus className="h-6 w-6 text-brand-orange" />
                        Receive Boxes
                    </h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        {reliefPack.name} — currently {reliefPack.current_stock} boxes in stock.
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
                        <Label htmlFor="source_name" className="font-semibold text-ink">
                            Source
                        </Label>
                        <Input
                            id="source_name"
                            type="text"
                            placeholder="DSWD, Provincial Office, Donation…"
                            value={data.source_name}
                            onChange={(e) => setData('source_name', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.source_name && <p className="mt-1.5 text-sm text-danger">{errors.source_name}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="quantity_received" className="font-semibold text-ink">
                                Quantity
                            </Label>
                            <Input
                                id="quantity_received"
                                type="number"
                                min={1}
                                placeholder="No. of boxes"
                                value={data.quantity_received}
                                onChange={(e) => setData('quantity_received', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.quantity_received && (
                                <p className="mt-1.5 text-sm text-danger">{errors.quantity_received}</p>
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

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Add to Stock
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Receive.layout = {
    breadcrumbs: [
        { title: 'Relief Packs', href: '/relief-packs' },
        { title: 'Receive Boxes' },
    ],
};