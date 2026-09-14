import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/users';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import FlashAlerts from '@/components/flash-alerts';

interface userForm {
    name: string;
    email: string;
    role: string;
    barangay_id: string;
    password: string;
    password_confirmation: string;
}

interface SelectOption {
    value: string;
    label: string;
}

interface barangay {
    id: number;
    name: string;
}

interface user {
    id: number;
    name: string;
    email: string;
    role?: string;
    barangay_id: number | null;
}

interface Props {
    user: user;
    barangays: barangay[];
    roles: SelectOption[];
}

export default function Edit({ user, barangays, roles }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;
    const { data, setData, put, processing, errors } = useForm<userForm>({
        name: user.name ?? '',
        email: user.email ?? '',
        role: user.role ?? '',
        barangay_id: user.barangay_id ? String(user.barangay_id) : '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(user.id).url);
    };

    const needsBarangay = data.role === 'barangayofficial';

    return (
        <>
            <Head title="Edit User" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <FlashAlerts flash={flash} />
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit User</h1>
                    <p className="mt-0.5 text-sm text-subtle">
                        Update this account's details and role.
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
                                Name
                            </Label>
                            <Input
                                id="name"
                                type="text"
                                placeholder="Full name"
                                value={data.name}
                                maxLength={75}
                                onChange={(e) => setData('name', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.name && <p className="mt-1.5 text-sm text-danger">{errors.name}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="email" className="font-semibold text-ink">
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="name@example.com"
                                value={data.email}
                                maxLength={150}
                                onChange={(e) => setData('email', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.email && <p className="mt-1.5 text-sm text-danger">{errors.email}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="role">Role</Label>
                            <Select
                                value={data.role || undefined}
                                onValueChange={(value) => {
                                    setData('role', value);
                                    if (value !== 'barangayofficial') setData('barangay_id', '');
                                }}
                            >
                                <SelectTrigger id="role" className="mt-1.5 w-full bg-white">
                                    <SelectValue placeholder="Select role…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((r) => (
                                        <SelectItem key={r.value} value={r.value}>
                                            {r.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.role && <p className="mt-1.5 text-sm text-danger">{errors.role}</p>}
                        </div>

                        {needsBarangay && (
                            <div>
                                <Label htmlFor="barangay_id">Barangay</Label>
                                <Select
                                    value={data.barangay_id || undefined}
                                    onValueChange={(value) => setData('barangay_id', value)}
                                >
                                    <SelectTrigger id="barangay_id" className="mt-1.5 w-full bg-white">
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
                                {errors.barangay_id && (
                                    <p className="mt-1.5 text-sm text-danger">{errors.barangay_id}</p>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="password" className="font-semibold text-ink">
                                New Password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="Leave blank to keep current password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.password && <p className="mt-1.5 text-sm text-danger">{errors.password}</p>}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="password_confirmation" className="font-semibold text-ink">
                                Confirm New Password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                        </div>
                    </div>

                    <div className="flex justify-end border-t border-[#d1d5db] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Update User
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
            title: 'Edit User',
            href: '/users/edit',
        },
    ],
};