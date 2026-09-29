<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\PackReceipt;
use App\Models\ReliefPack;
use App\Models\ReliefStock;
use Illuminate\Support\Facades\DB;

/**
 * Relief stock is an append-only ledger:
 *   pack_receipts (header) -> relief_stocks (one row per pack line)
 * Current stock per pack is derived: SUM(relief_stocks.quantity) - distributed.
 *
 * Methods do not catch exceptions. DB::transaction() rolls back on throw,
 * and the controller decides what to show the user.
 */
class ReliefStockService
{
    /**
     * $data = [
     *   'receipt'    => ['source_name' => '...', 'date_received' => 'Y-m-d'],
     *   'reliefList' => [['relief_pack_id' => 1, 'quantity' => 10], ...],
     * ]
     *
     * Rows with the same relief_pack_id are combined into one line
     * (A:23 + A:43 => A:66) before anything is saved.
     */
    public function reliefStockStore(array $data): PackReceipt
    {
        return DB::transaction(function () use ($data) {
            $receipt = new PackReceipt();
            $receipt->fill($data['receipt']);
            $receipt->received_by = auth()->id();
            $receipt->save();

            $receipt->reliefStock()->createMany($this->lines($data['reliefList']));

            return $receipt;
        });
    }

    /**
     * Same $data shape as reliefStockStore. received_by is left untouched.
     *
     * @throws InsufficientStockException if the edit would push any pack below 0
     */
    public function reliefStockUpdate(PackReceipt $receipt, array $data): PackReceipt
    {
        return DB::transaction(function () use ($receipt, $data) {
            $lines = $this->lines($data['reliefList']);

            $this->guardStock($receipt, $lines);

            $receipt->update($data['receipt']);
            $receipt->reliefStock()->delete();
            $receipt->reliefStock()->createMany($lines);

            return $receipt;
        });
    }

    /**
     * @throws InsufficientStockException if removing this receipt would push any pack below 0
     */
    public function reliefStockDelete(PackReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $this->guardStock($receipt, []);

            $receipt->reliefStock()->delete();
            $receipt->delete();
        });
    }

    /**
     * Checks that replacing this receipt's lines with $newLines
     * (empty array = deleting the receipt) leaves every affected pack at >= 0.
     * Must be called inside a transaction.
     */
    private function guardStock(PackReceipt $receipt, array $newLines): void
    {
        $old = $receipt->reliefStock()
            ->get(['relief_pack_id', 'quantity'])
            ->groupBy('relief_pack_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $new = collect($newLines)
            ->groupBy('relief_pack_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $packIds = $old->keys()->merge($new->keys())->unique()->values();

        if ($packIds->isEmpty()) {
            return;
        }

        // Lock the pack rows so two edits touching the same pack run one after the other.
        $packs = ReliefPack::whereIn('id', $packIds)->lockForUpdate()->get()->keyBy('id');

        $received = ReliefStock::whereIn('relief_pack_id', $packIds)
            ->selectRaw('relief_pack_id, SUM(quantity) as total')
            ->groupBy('relief_pack_id')
            ->pluck('total', 'relief_pack_id');

        $distributed = $this->distributedByPack($packIds->all());

        foreach ($packIds as $id) {
            $after = (int) ($received[$id] ?? 0)
                - (int) ($old[$id] ?? 0)
                + (int) ($new[$id] ?? 0)
                - (int) ($distributed[$id] ?? 0);

            if ($after < 0) {
                $name = $packs[$id]->name ?? "Pack #{$id}";
                throw new InsufficientStockException(
                    "Cannot save: {$name} would be short by " . abs($after) . ' after this change, because those packs were already distributed.'
                );
            }
        }
    }

    /**
     * Total quantity already sent out, per pack: [pack_id => qty].
     *
     * TODO: there is no OUT table yet, so nothing has been distributed.
     * When you add distribution lines, replace this body, e.g.:
     *
     *   return DistributionLine::whereIn('relief_pack_id', $packIds)
     *       ->selectRaw('relief_pack_id, SUM(quantity) as total')
     *       ->groupBy('relief_pack_id')
     *       ->pluck('total', 'relief_pack_id')
     *       ->all();
     */
    private function distributedByPack(array $packIds): array
    {
        return [];
    }

    /**
     * Keep only the columns we allow, and combine rows that use the same
     * relief pack into a single line with the quantities added together.
     *
     * [A:23, B:23, A:43] => [A:66, B:23]
     */
    private function lines(array $reliefList): array
    {
        return collect($reliefList)
            ->groupBy('relief_pack_id')
            ->map(fn ($rows, $packId) => [
                'relief_pack_id' => (int) $packId,
                'quantity' => (int) $rows->sum(fn ($row) => (int) $row['quantity']),
            ])
            ->values()
            ->all();
    }
}