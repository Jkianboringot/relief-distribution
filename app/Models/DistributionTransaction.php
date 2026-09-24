<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_schedule_id',
        'beneficiary_id',
        'quantity_boxes',
        'verified_by',
        'verification_timestamp',
        'status',
    ];

    protected $casts = [
        'verification_timestamp' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DistributionSchedule::class, 'distribution_schedule_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Benificiary::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}