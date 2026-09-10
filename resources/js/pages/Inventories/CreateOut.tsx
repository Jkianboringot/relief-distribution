import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Trash2 } from 'lucide-react';
import { storeOut } from '@/routes/inventories';
import FlashAlerts from '@/components/flash-alerts';

interface Product {
    id: number;
    name: string;
    quantity: number; // stock on hand at this branch
    price: number;    // NEW — needed to auto-calculate expected sales; backend must include this
}

interface Branch {
    id: number;
    location: string;
    products: Product[];
}

interface ProductRow {
    product_id: number | '';
    quantity: number;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    branches: Branch[];
    stockMovementTypes: SelectOption[];
    shifts: SelectOption[];
}

export default function CreateOut({ branches, stockMovementTypes, shifts }: Props) {
    const { flash } = usePage<{ flash: { message?: string; error?: string } }>().props;

    const { data, setData, post, processing, errors } = useForm<{
        branch_id: number | '';
        productList: ProductRow[];
        shift: string;
        stock_movement_type: string | null;
        cash_amount: number|'';
        cash_shortage: number | '';
        gcash_amount: number | '';
        cash_advance: number | '';
        remitted_expenses: number | '';
        net_cash: number|'';
    }>({
        branch_id: '',
        productList: [{ product_id: '', quantity: 1 }],
        shift: '',
        stock_movement_type: '',
        cash_amount: '',
        cash_shortage: '',
        gcash_amount: '',
        cash_advance: '',
        remitted_expenses: '',
        net_cash: '',
    });

    const selectedBranch = useMemo(
        () => branches.find((b) => b.id === data.branch_id),
        [branches, data.branch_id],
    );

    // Expected sales — sum of (product price × quantity sold) across every
    // row. This is "what you should have on hand" based on what left stock.
    const expectedTotal = useMemo(() => {
        if (!selectedBranch) return 0;

        return data.productList.reduce((sum, row) => {
            if (row.product_id === '') return sum;
            const product = selectedBranch.products.find((p) => p.id === row.product_id);
            if (!product) return sum;
            return sum + product.price * Number(row.quantity || 0);
        }, 0);
    }, [data.productList, selectedBranch]);

    // Total / net_cash — plain sum of everything actually collected.
    // No shortage folded in here; this reacts to every field as expected.
    const total_cash = useMemo(() => {
        return (
            Number(data.cash_amount || 0) +
            Number(data.gcash_amount || 0) +
            Number(data.cash_advance || 0) +
            Number(data.remitted_expenses || 0)
        );
    }, [data.cash_amount, data.gcash_amount, data.cash_advance, data.remitted_expenses]);

    // Cash shortage — the gap between what was actually collected (Total)
    // and what should have been collected based on what was sold (Expected).
    // Positive = over, negative = short.
    const cashShortage = useMemo(
        () => Math.abs(Number((expectedTotal - total_cash).toFixed(2))),
        [expectedTotal, total_cash],
    );

    function handleBranchChange(branchId: number) {
        setData((prev) => ({
            ...prev,
            branch_id: branchId,
            productList: [{ product_id: '', quantity: 1 }],
        }));
    }

    function addRow() {
        setData('productList', [...data.productList, { product_id: '', quantity: 1 }]);
    }

    function removeRow(index: number) {
        setData(
            'productList',
            data.productList.filter((_, i) => i !== index),
        );
    }

    function updateRow(index: number, key: keyof ProductRow, value: string | number) {
        const rows = [...data.productList];
        rows[index] = { ...rows[index], [key]: value };
        setData('productList', rows);
    }

    function stockFor(productId: number | ''): number | null {
        if (productId === '' || !selectedBranch) return null;
        return selectedBranch.products.find((p) => p.id === productId)?.quantity ?? 0;
    }

    function priceFor(productId: number | ''): number | null {
        if (productId === '' || !selectedBranch) return null;
        return selectedBranch.products.find((p) => p.id === productId)?.price ?? null;
    }

    // ids already picked in other rows, so the same product can't be
    // sold twice in one submission
    function pickedElsewhere(index: number): number[] {
        return data.productList
            .filter((_, i) => i !== index)
            .map((r) => r.product_id)
            .filter((id): id is number => id !== '');
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        // Computed inline rather than via setData() beforehand — setData
        // is async, so a setData(...) immediately followed by post(...)
        // would send the PREVIOUS render's value, not the one just set.
        setData('net_cash', total_cash);
        setData('cash_shortage', cashShortage);

        post(storeOut().url, {
            data: {
                ...data

            },
        });
    }

    return (
        <div className="p-6">
            <FlashAlerts flash={flash} />

            <div className="mb-6">
                <h1 className="text-3xl font-extrabold tracking-tight text-ink">Inventory Out</h1>
            </div>

            <form onSubmit={submit} className="w-full">
                <div className="w-full overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    {/* Branch */}
                    <div className="border-b border-[#d1d5db] p-6">
                        <h2 className="mb-4 text-sm font-semibold text-ink">Branch</h2>
                        <Select
                            value={data.branch_id ? String(data.branch_id) : undefined}
                            onValueChange={(value) => handleBranchChange(Number(value))}
                        >
                            <SelectTrigger className="w-full bg-white">
                                <SelectValue placeholder="Select a branch…" />
                            </SelectTrigger>
                            <SelectContent>
                                {branches.map((b) => (
                                    <SelectItem key={b.id} value={String(b.id)}>
                                        {b.location}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.branch_id && <p className="mt-1.5 text-sm text-danger">{errors.branch_id}</p>}
                    </div>

                    {/* Products sold */}
                    <div className="border-b border-[#d1d5db] p-6">
                        <div className="mb-1 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-ink">Products Sold</h2>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={addRow}
                                disabled={!selectedBranch}
                                className="text-brand-orange hover:text-brand-orange-hover disabled:opacity-40"
                            >
                                <Plus className="h-4 w-4" />
                                Add Product
                            </Button>
                        </div>
                        <p className="mb-4 text-xs text-subtle">Only products already stocked at the selected branch can be sold.</p>

                        <div className="space-y-3">
                            {data.productList.map((row, i) => {
                                const excluded = pickedElsewhere(i);
                                const stock = stockFor(row.product_id);
                                const price = priceFor(row.product_id);
                                const rowTotal = price !== null ? price * Number(row.quantity || 0) : null;

                                return (
                                    <div
                                        key={i}
                                        className="flex flex-col gap-3 rounded-lg border border-[#d1d5db] bg-white/60 p-3 sm:flex-row sm:items-start"
                                    >
                                        <Select
                                            value={row.product_id ? String(row.product_id) : undefined}
                                            disabled={!selectedBranch}
                                            onValueChange={(value) => updateRow(i, 'product_id', Number(value))}
                                        >
                                            <SelectTrigger className="flex-1 bg-white">
                                                <SelectValue
                                                    placeholder={selectedBranch ? 'Select product…' : 'Select a branch first…'}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {selectedBranch?.products
                                                    .filter((p) => !excluded.includes(p.id))
                                                    .map((p) => (
                                                        <SelectItem key={p.id} value={String(p.id)}>
                                                            {p.name} — ₱{p.price} ({p.quantity} in stock)
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>

                                        <Input
                                            type="number"
                                            disabled
                                            value={stock ?? ''}
                                            placeholder="In stock"
                                            className="bg-white sm:w-24"
                                        />

                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={stock ?? undefined}
                                            className="bg-white sm:w-24"
                                            placeholder="Qty sold"
                                            value={row.quantity}
                                            onChange={(e) => updateRow(i, 'quantity', Number(e.target.value))}
                                        />

                                        <div className="flex min-w-24 items-center text-sm font-medium text-ink sm:justify-end sm:pt-2">
                                            {rowTotal !== null ? `₱${rowTotal.toFixed(2)}` : '—'}
                                        </div>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeRow(i)}
                                            disabled={data.productList.length === 1}
                                            className="shrink-0 text-danger hover:bg-danger/10 hover:text-danger disabled:opacity-40"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Remove
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                        {errors.productList && <p className="mt-2 text-sm text-danger">{errors.productList}</p>}

                        <div className="mt-4 flex items-center justify-between rounded-lg border border-[#d1d5db] bg-white/60 px-4 py-3">
                            <span className="text-sm font-medium text-ink">Expected Sales</span>
                            <span className="text-base font-semibold text-ink">₱{expectedTotal.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Cash summary */}
                    <div className="p-6">
                        <h2 className="mb-4 text-sm font-semibold text-ink">Cash Summary</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="shift">Shift</Label>
                                <Select value={data.shift || undefined} onValueChange={(value) => setData('shift', value)}>
                                    <SelectTrigger id="shift" className="mt-1.5 w-full bg-white sm:max-w-xs">
                                        <SelectValue placeholder="Select shift…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {shifts.map((v) => (
                                            <SelectItem key={v.value} value={v.value}>
                                                {v.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.shift && <p className="mt-1.5 text-sm text-danger">{errors.shift}</p>}
                            </div>

                            <div>
                                <Label htmlFor="stock_movement_type">Stock Type</Label>
                                <Select
                                    value={data.stock_movement_type || undefined}
                                    onValueChange={(value) => setData('stock_movement_type', value)}
                                >
                                    <SelectTrigger id="stock_movement_type" className="mt-1.5 w-full bg-white sm:max-w-xs">
                                        <SelectValue placeholder="Select stock movement…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {stockMovementTypes.map((t) => (
                                            <SelectItem key={t.value} value={t.value}>
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.stock_movement_type && (
                                    <p className="mt-1.5 text-sm text-danger">{errors.stock_movement_type}</p>
                                )}
                            </div>

                            <MoneyField label="Cash on Hand" value={data.cash_amount} onChange={(v) => setData('cash_amount', v)} error={errors.cash_amount} />
                            <MoneyField label="Gcash" value={data.gcash_amount ?? 0} onChange={(v) => setData('gcash_amount', v)} error={errors.gcash_amount} />
                            <MoneyField label="Cash Advance" value={data.cash_advance ?? 0} onChange={(v) => setData('cash_advance', v)} error={errors.cash_advance} />
                            <MoneyField label="Remitted Expenses" value={data.remitted_expenses ?? 0} onChange={(v) => setData('remitted_expenses', v)} error={errors.remitted_expenses} />

                            {/* Cash Shortage — auto-calculated, no longer a manual input */}
                            <div>
                                <Label>Cash Shortage</Label>
                                <div className="relative mt-1.5">
                                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-subtle">₱</span>
                                    <div
                                        className={'flex h-9 items-center rounded-md border bg-[#f5ede0] pl-7 pr-3 text-sm font-medium border-danger/40 text-danger'}
                                    >
                                        {cashShortage.toFixed(2)}
                                    </div>
                                </div>
                                <p className="mt-1.5 text-xs text-subtle">
                                    {cashShortage < 0
                                        ? 'Short — collected less than expected sales.'
                                        : cashShortage > 0
                                            ? 'Over — collected more than expected sales.'
                                            : 'Balanced.'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-4 flex items-center justify-between rounded-lg border border-[#d1d5db] bg-white/60 px-4 py-3">
                            <span className="text-sm font-medium text-ink">Total</span>
                            <span className="text-base font-semibold text-ink">₱{total_cash.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end border-t border-[#d1d5db] bg-white/60 px-6 py-4">
                        <Button type="submit" disabled={processing} className="bg-green-600 font-bold text-white hover:bg-green-700">
                            {processing ? 'Saving…' : 'Save Sale'}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}

function MoneyField({
    label,
    value,
    onChange,
    error,
}: {
    label: string;
    value: number;
    onChange: (v: number) => void;
    error?: string;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <div className="relative mt-1.5">
                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-subtle">₱</span>
                <Input type="number" step="0.01" className="bg-white pl-7" value={value} onChange={(e) => onChange(Number(e.target.value))} />
            </div>
            {error && <p className="mt-1.5 text-sm text-danger">{error}</p>}
        </div>
    );
}