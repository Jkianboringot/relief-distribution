<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionReliefStock extends Model
{
    protected $guarded = ['id'];

    public function reliefPack(): BelongsTo
    {
        return $this->belongsTo(ReliefPack::class, 'relief_pack_id');
    }

    public function distributionSched(): BelongsTo
    {
        return $this->belongsTo(DistributionSchedule::class, 'distribution_sched_id');
    }
}