import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { store } from '@/routes/barangays';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import FlashAlerts from '@/components/flash-alerts';

interface barangayForm {
    name: string;
    municipality_id: string;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Municipality {
    id: number;
    name: string;
}






export default function Create({ municipalities }: { municipalities: Municipality[] }) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, post, processing, errors } = useForm<barangayForm>({
        name: '',
        municipality_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Create New barangay" />

            <div className="mx-auto w-full  max-w-4xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className=" text-2xl font-bold text-ink">New barangay</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Add a barangay to your catalog.
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
                            <Label htmlFor="municipality_id" className="font-semibold text-ink">
                                Municipality
                            </Label>
                            <Select
                                value={data.municipality_id}
                                onValueChange={(value) => setData('municipality_id', value)}
                            >
                                <SelectTrigger id="municipality_id" className="border-[#e0d0c0]">
                                    <SelectValue placeholder="Select municipality" />
                                </SelectTrigger>
                                <SelectContent>
                                    {municipalities.map((m) => (
                                        <SelectItem key={m.id} value={String(m.id)}>
                                            {m.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.municipality_id && (
                                <p className="mt-1.5 text-sm text-danger">{errors.municipality_id}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="name" className="font-semibold text-ink">
                                Barangay Name
                            </Label>
                            <Input
                                id="name"
                                type='text'
                                placeholder="barangay name"
                                value={data.name}
                                minLength={3}
                                maxLength={75}
                                onChange={(e) => setData('name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}

                        </div>







                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Save barangay
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Create New barangay',
            href: '/barangays/create',
        },
    ],
};