<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function claimedCount(): int
    {
        return $this->transactions()->where('status', 'claimed')->count();
    }

       // NOTE: this is the total across ALL pack types on the schedule.
    // Adjust if a claim should count against one specific pack.
    public function plannedQuantity(): int
    {
        return (int) $this->reliefStock()->sum('quantity');
    }


    public function remainingAllocation(): int
    {
        return max(0, $this->plannedQuantity() - $this->claimedCount());
    }
}