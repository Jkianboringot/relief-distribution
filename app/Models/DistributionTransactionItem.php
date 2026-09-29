<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionTransactionItem extends Model
{
    protected $fillable = [
        'distribution_transaction_id',
        'relief_pack_id',
        'quantity',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(DistributionTransaction::class, 'distribution_transaction_id');
    }

    public function reliefPack(): BelongsTo
    {
        return $this->belongsTo(ReliefPack::class);
    }
}