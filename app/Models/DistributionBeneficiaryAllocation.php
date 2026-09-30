<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionBeneficiaryAllocation extends Model
{
    protected $fillable = [
        'distribution_schedule_id',
        'beneficiary_id',
        'relief_pack_id',
        'quantity',
        'assigned_by',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DistributionSchedule::class, 'distribution_schedule_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Benificiary::class);
    }

    public function reliefPack(): BelongsTo
    {
        return $this->belongsTo(ReliefPack::class);
    }
}