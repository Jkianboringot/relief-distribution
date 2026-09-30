<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DistributionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'date',
        'location',
        'barangay_id',
        'relief_pack_id',
        'planned_quantity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function reliefStock(): HasMany
    {
        return $this->hasMany(DistributionReliefStock::class, 'distribution_sched_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DistributionTransaction::class);
    }

    public function transactionItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            DistributionTransactionItem::class,
            DistributionTransaction::class,
            'distribution_schedule_id', // FK on distribution_transactions
            'distribution_transaction_id', // FK on distribution_transaction_items
        );
    }

    public function claimedCount(): int
    {
        // Family heads served, not boxes.
        return $this->transactions()->where('status', 'claimed')->count();
    }

    // Total boxes across ALL pack types, as configured on this schedule.
    public function plannedQuantity(): int
    {
        return (int) $this->reliefStock()->sum('quantity');
    }

    // Total boxes actually handed out across every pack type.
    public function distributedQuantity(): int
    {
        return (int) $this->transactionItems()
            ->whereHas('transaction', fn ($q) => $q->where('status', 'claimed'))
            ->sum('quantity');
    }

    // Boxes left for one specific pack type on this schedule.
    public function remainingForPack(int $reliefPackId): int
    {
        $planned = (int) $this->reliefStock()
            ->where('relief_pack_id', $reliefPackId)
            ->sum('quantity');

        $given = (int) $this->transactionItems()
            ->where('relief_pack_id', $reliefPackId)
            ->whereHas('transaction', fn ($q) => $q->where('status', 'claimed'))
            ->sum('quantity');

        return max(0, $planned - $given);
    }

    public function remainingAllocation(): int
    {
        return max(0, $this->plannedQuantity() - $this->distributedQuantity());
    }

    public function allocations(): HasMany
{
    return $this->hasMany(DistributionBeneficiaryAllocation::class);
}
}