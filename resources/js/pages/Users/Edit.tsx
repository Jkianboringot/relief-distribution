import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/barangays';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashAlerts from '@/components/flash-alerts';

interface barangay {
    id: number;
    name: string;
    code: string;

}

interface SelectOption {
    value: string;
    label: string;
}


interface Props {
    barangays: barangay;
}




export default function Edit({ barangays }: Props) {
    // HACK - useForm should have type
    const { data, setData, put, processing, errors } = useForm({
        name: barangays.name,
        code: barangays.code,
    });
      const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(barangays.id).url);
    };

    return (
        <>
            <Head title="Edit barangay" />

            <div className="mx-auto w-full  max-w-4xl p-6">
                 <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Product</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update details for "{barangays.name}".
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
                            <Label htmlFor="name" className="font-semibold text-ink">
                                Barangay Name
                            </Label>
                            <Input
                                id="name"
                                type='text'
                                placeholder="Barangay name"
                                value={data.name}
                                minLength={3}
                                maxLength={75}
                                onChange={(e) => setData('name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}

                        </div>
                         <div className="space-y-1">
                            <Label htmlFor="code" className="font-semibold text-ink">
                                Barangay Code
                            </Label>
                            <Input
                                id="code"
                                type='text'
                                placeholder="Code"
                                value={data.code}
                                minLength={3}
                                maxLength={75}
                                onChange={(e) => setData('code', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.code && <p className="mt-1.5 text-sm text-danger">{errors.code}</p>}

                        </div>
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

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Edit  barangay',
        },
    ],
};