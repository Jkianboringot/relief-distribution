import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { store } from '@/routes/beneficiaries';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import FlashAlerts from '@/components/flash-alerts';

interface beneficiaryForm {
    barangay_id: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    birthdate: string;
    gender: string;
    address: string;
    household_members: string;
}

interface SelectOption {
    value: string;
    label: string;
}

interface barangay {
    id: number;
    name: string;
}

interface Props {
    barangays: barangay[];
    genders: SelectOption[];
}

export default function Create({ barangays, genders }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, post, processing, errors } = useForm<beneficiaryForm>({
        barangay_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        birthdate: '',
        gender: '',
        address: '',
        household_members: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Register New Beneficiary" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New Beneficiary</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Register a beneficiary under a barangay.
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

                    <div>
                        <Label htmlFor="barangay_id">Barangay</Label>
                        <Select
                            value={data.barangay_id || undefined}
                            onValueChange={(value) => setData('barangay_id', value)}
                        >
                            <SelectTrigger id="barangay_id" className="mt-1.5 w-full bg-white sm:max-w-xs">
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
                        {errors.barangay_id && <p className="mt-1.5 text-sm text-danger">{errors.barangay_id}</p>}
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="first_name" className="font-semibold text-ink">
                                First Name
                            </Label>
                            <Input
                                id="first_name"
                                type="text"
                                placeholder="First name"
                                value={data.first_name}
                                maxLength={75}
                                onChange={(e) => setData('first_name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.first_name && <p className="mt-1.5 text-sm text-danger">{errors.first_name}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="middle_name" className="font-semibold text-ink">
                                Middle Name
                            </Label>
                            <Input
                                id="middle_name"
                                type="text"
                                placeholder="Middle name (optional)"
                                value={data.middle_name}
                                maxLength={75}
                                onChange={(e) => setData('middle_name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.middle_name && <p className="mt-1.5 text-sm text-danger">{errors.middle_name}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="last_name" className="font-semibold text-ink">
                                Last Name
                            </Label>
                            <Input
                                id="last_name"
                                type="text"
                                placeholder="Last name"
                                value={data.last_name}
                                maxLength={75}
                                onChange={(e) => setData('last_name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.last_name && <p className="mt-1.5 text-sm text-danger">{errors.last_name}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="birthdate" className="font-semibold text-ink">
                                Birthdate
                            </Label>
                            <Input
                                id="birthdate"
                                type="date"
                                value={data.birthdate}
                                onChange={(e) => setData('birthdate', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.birthdate && <p className="mt-1.5 text-sm text-danger">{errors.birthdate}</p>}
                        </div>

                        <div>
                            <Label htmlFor="gender">Gender</Label>
                            <Select
                                value={data.gender || undefined}
                                onValueChange={(value) => setData('gender', value)}
                            >
                                <SelectTrigger id="gender" className="mt-1.5 w-full bg-white">
                                    <SelectValue placeholder="Select gender…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {genders.map((g) => (
                                        <SelectItem key={g.value} value={g.value}>
                                            {g.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.gender && <p className="mt-1.5 text-sm text-danger">{errors.gender}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="address" className="font-semibold text-ink">
                                Address
                            </Label>
                            <Input
                                id="address"
                                type="text"
                                placeholder="Complete address"
                                value={data.address}
                                maxLength={150}
                                onChange={(e) => setData('address', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.address && <p className="mt-1.5 text-sm text-danger">{errors.address}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="household_members" className="font-semibold text-ink">
                                Household Members
                            </Label>
                            <Input
                                id="household_members"
                                type="number"
                                min={1}
                                max={50}
                                placeholder="Number of household members"
                                value={data.household_members}
                                onChange={(e) => setData('household_members', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.household_members && (
                                <p className="mt-1.5 text-sm text-danger">{errors.household_members}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Register Beneficiary
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
            title: 'Register New Beneficiary',
            href: '/beneficiaries/create',
        },
    ],
};