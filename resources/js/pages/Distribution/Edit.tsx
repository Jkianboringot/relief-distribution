import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/relief-packs';
import FlashAlerts from '@/components/flash-alerts';

interface ReliefPack {
    id: number;
    name: string;
    description: string | null;
    current_stock: number;
}

interface Props {
    reliefPack: ReliefPack;
}

export default function Edit({ reliefPack }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, put, processing, errors } = useForm({
        name: reliefPack.name,
        description: reliefPack.description ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(reliefPack.id).url);
    };

    return (
        <>
            <Head title="Edit Box Type" />

            <div className="mx-auto w-full max-w-2xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Box Type</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update details for "{reliefPack.name}". Current stock: {reliefPack.current_stock} boxes.
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
                        <Label htmlFor="name" className="font-semibold text-ink">
                            Box Name
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            placeholder="e.g. Family Food Pack"
                            value={data.name}
                            maxLength={255}
                            onChange={(e) => setData('name', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="description" className="font-semibold text-ink">
                            Description
                        </Label>
                        <Input
                            id="description"
                            type="text"
                            placeholder="What's inside this box (optional)"
                            value={data.description}
                            maxLength={500}
                            onChange={(e) => setData('description', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.description && <p className="mt-1.5 text-sm text-danger">{errors.description}</p>}
                    </div>

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Edit Box Type',
        },
    ],
};