import { Head, useForm, usePage } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FlashAlerts from '@/components/flash-alerts';

interface Municipality {
    id: number;
    name: string;
}

interface MunicipalityForm {
    name: string;
}

export default function Form({ municipality }: { municipality: Municipality | null }) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const isEdit = municipality !== null;

    const { data, setData, post, put, processing, errors } = useForm<MunicipalityForm>({
        name: municipality?.name ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/municipalities/${municipality.id}`);
        } else {
            post('/municipalities');
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit Municipality' : 'Create New Municipality'} />

            <div className="mx-auto w-full max-w-4xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">
                        {isEdit ? 'Edit Municipality' : 'New Municipality'}
                    </h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        {isEdit ? 'Update the municipality name.' : 'Add a municipality to your catalog.'}
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
                            Municipality Name
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            placeholder="Municipality name"
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
                            {isEdit ? 'Update Municipality' : 'Save Municipality'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Form.layout = {
    breadcrumbs: [
        {
            title: 'Municipalities',
            href: '/municipalities',
        },
    ],
};
