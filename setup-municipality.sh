#!/usr/bin/env bash
# Adds the Municipality module (model, request, controller, routes, React pages).
# Run from the Laravel project root (where `artisan` is).
# Does NOT touch any Barangay file.
set -e

if [ ! -f artisan ]; then
    echo "Run this from your Laravel project root (the folder with artisan)."
    exit 1
fi

mkdir -p app/Models app/Http/Requests app/Http/Controllers resources/js/pages/Municipalities

# ---------------------------------------------------------------- Model (replaces the empty one)
cat > app/Models/Municipality.php <<'PHP_EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}
PHP_EOF

# ---------------------------------------------------------------- Form request
cat > app/Http/Requests/MunicipalityRequest.php <<'PHP_EOF'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MunicipalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $municipality = $this->route('municipality');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:75',
                Rule::unique('municipalities', 'name')->ignore($municipality?->id),
            ],
        ];
    }
}
PHP_EOF

# ---------------------------------------------------------------- Controller
cat > app/Http/Controllers/MunicipalityController.php <<'PHP_EOF'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\MunicipalityRequest;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MunicipalityController extends Controller
{
    public function index(Request $request)
    {
        $municipalities = Municipality::query()
            ->withCount('barangays')
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Municipalities/Index', [
            'municipalities' => $municipalities,
            'filters' => $request->only(['search']),
        ]);
    }

    // Create and edit share one form page.
    public function create()
    {
        return Inertia::render('Municipalities/Form', [
            'municipality' => null,
        ]);
    }

    public function store(MunicipalityRequest $request)
    {
        try {
            Municipality::create($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to create municipality.');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality created successfully');
    }

    public function edit(Municipality $municipality)
    {
        return Inertia::render('Municipalities/Form', [
            'municipality' => $municipality,
        ]);
    }

    public function update(MunicipalityRequest $request, Municipality $municipality)
    {
        try {
            $municipality->update($request->validated());
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update municipality.');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality updated successfully');
    }

    public function delete(Municipality $municipality)
    {
        try {
            $municipality->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this municipality because it has associated barangays');
        }

        return redirect()->route('municipalities.index')->with('message', 'Municipality deleted');
    }
}
PHP_EOF

# ---------------------------------------------------------------- Routes (appended once)
# NOTE: only the 'auth' middleware is added here. Match this to however your
# barangay routes are protected (permission middleware, etc.).
if ! grep -q "MunicipalityController" routes/web.php; then
cat >> routes/web.php <<'PHP_EOF'

// Municipalities
Route::middleware(['auth'])->group(function () {
    Route::get('/municipalities', [\App\Http\Controllers\MunicipalityController::class, 'index'])->name('municipalities.index');
    Route::get('/municipalities/create', [\App\Http\Controllers\MunicipalityController::class, 'create'])->name('municipalities.create');
    Route::post('/municipalities', [\App\Http\Controllers\MunicipalityController::class, 'store'])->name('municipalities.store');
    Route::get('/municipalities/{municipality}/edit', [\App\Http\Controllers\MunicipalityController::class, 'edit'])->name('municipalities.edit');
    Route::put('/municipalities/{municipality}', [\App\Http\Controllers\MunicipalityController::class, 'update'])->name('municipalities.update');
    Route::delete('/municipalities/{municipality}', [\App\Http\Controllers\MunicipalityController::class, 'delete'])->name('municipalities.delete');
});
PHP_EOF
else
    echo "routes/web.php already has municipality routes, skipping."
fi

# ---------------------------------------------------------------- Index page
cat > resources/js/pages/Municipalities/Index.tsx <<'TSX_EOF'
import { useEffect, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Pencil, Search, Trash2, X } from 'lucide-react';
import FlashAlerts from '@/components/flash-alerts';

interface Municipality {
    id: number;
    name: string;
    barangays_count: number;
}

interface PaginatedMunicipalities {
    data: Municipality[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    municipalities: PaginatedMunicipalities;
    filters: { search?: string };
    flash: {
        message?: string;
        error?: string;
    };
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { flash, municipalities, filters } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');

    // Debounced search; guarded so it doesn't re-fire when `search` already matches the server.
    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                '/municipalities',
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    function clearSearch() {
        setSearch('');
        router.get('/municipalities', {}, { preserveState: true, replace: true });
    }

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(`/municipalities/${id}`);
        }
    };

    return (
        <>
            <Head title="Municipalities" />

            <div className="p-6">
                <FlashAlerts flash={flash} />

                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Municipalities
                    </h1>
                    <Link href="/municipalities/create">
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New Municipality
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <div className="flex items-center justify-end gap-3 border-b border-[#d1d5db] px-5 py-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                            {search.length >= 100 && (
                                <p className="absolute left-0 top-full mb-10 text-xs text-danger">
                                    Search can't be longer than 100 characters.
                                </p>
                            )}
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
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    Municipality Name
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    Barangays
                                </TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {municipalities.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={3} className="py-10 text-center text-sm text-subtle">
                                        No Municipalities found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {municipalities.data.map((municipality) => (
                                <TableRow
                                    key={municipality.id}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {municipality.name}
                                    </TableCell>
                                    <TableCell className="text-ink">
                                        {municipality.barangays_count}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={`/municipalities/${municipality.id}/edit`}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(municipality.id, municipality.name)}
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

                    {municipalities.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#d1d5db] px-5 py-3">
                            {municipalities.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${link.active
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
            title: 'Municipalities',
            href: '/municipalities',
        },
    ],
};
TSX_EOF

# ---------------------------------------------------------------- Combined create/edit form page
cat > resources/js/pages/Municipalities/Form.tsx <<'TSX_EOF'
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
TSX_EOF

echo ""
echo "Done. Created/updated:"
echo "  app/Models/Municipality.php"
echo "  app/Http/Requests/MunicipalityRequest.php"
echo "  app/Http/Controllers/MunicipalityController.php"
echo "  routes/web.php (municipality routes appended)"
echo "  resources/js/pages/Municipalities/Index.tsx"
echo "  resources/js/pages/Municipalities/Form.tsx"
echo ""
echo "Next: php artisan route:list --name=municipalities"