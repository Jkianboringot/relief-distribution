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
        'barangay',
        'relief_pack_id',
        'planned_quantity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function reliefPack(): BelongsTo
    {
        return $this->belongsTo(ReliefPack::class);
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

    public function remainingAllocation(): int
    {
        return max(0, $this->planned_quantity - $this->claimedCount());
    }
}