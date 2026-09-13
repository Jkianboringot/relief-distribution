import { useEffect, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Pencil, Search, Trash2, X } from 'lucide-react';
import { deleteMethod, index as usersIndex, edit, index } from '@/routes/users';
import FlashAlerts from '@/components/flash-alerts';

interface barangay {
    id: number;
    name: string;
}

interface role {
    id: number;
    name: string;
}

interface appUser {
    id: number;
    name: string;
    email: string;
    barangay: barangay | null;
    roles: role[];
}

interface PaginatedUsers {
    data: appUser[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    users: PaginatedUsers;
    filters: { search?: string; role?: string };
    flash: {
        message?: string;
        error?: string;
    };
}

function RoleBadge({ name }: { name: string }) {
    const isStaff = name === 'lgustaff';

    return (
        <span
            className={`inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-medium capitalize ${
                isStaff
                    ? 'border-brand-orange/40 bg-[#d1d5db] text-[#7a3b12]'
                    : 'border-slate-400/40 bg-slate-100 text-slate-700'
            }`}
        >
            {isStaff ? 'LGU Staff' : 'Barangay Official'}
        </span>
    );
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { flash, users, filters } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');

    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                usersIndex().url,
                { search, role: filters?.role },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    function clearSearch() {
        setSearch('');
        router.get(index().url, { role: filters?.role }, { preserveState: true, replace: true });
    }

    function filterByRole(role: string) {
        router.get(
            usersIndex().url,
            { search, role: role === 'all' ? undefined : role },
            { preserveState: true, replace: true },
        );
    }

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <>
            <Head title="Users" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Users
                    </h1>
                    <Link href={'/users/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New User
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <div className="flex items-center justify-end gap-3 border-b border-[#d1d5db] px-5 py-3">
                        <Select value={filters?.role ?? 'all'} onValueChange={filterByRole}>
                            <SelectTrigger className="w-44 bg-white text-sm">
                                <SelectValue placeholder="All roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All roles</SelectItem>
                                <SelectItem value="lgustaff">LGU Staff</SelectItem>
                                <SelectItem value="barangayofficial">Barangay Official</SelectItem>
                            </SelectContent>
                        </Select>

                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search name or email"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    aria-label="Clear search"
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-subtle hover:text-brand-orange"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Name</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Email</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Role</TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">Barangay</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-10 text-center text-sm text-subtle">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.data.map((user) => (
                                <TableRow
                                    key={user.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">{user.name}</TableCell>
                                    <TableCell className="text-ink">{user.email}</TableCell>
                                    <TableCell>
                                        {user.roles[0] && <RoleBadge name={user.roles[0].name} />}
                                    </TableCell>
                                    <TableCell className="text-ink">{user.barangay?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(user.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(user.id, user.name)}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger disabled:opacity-50"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                                Delete
                                            </button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {users.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#d1d5db] px-5 py-3">
                            {users.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${
                                        link.active
                                            ? 'bg-brand-orange text-white'
                                            : 'text-brand-orange-hover hover:bg-[#d1d5db]'
                                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: '/users',
        },
    ],
};