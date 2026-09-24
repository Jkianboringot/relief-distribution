<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'relief_pack_id',
        'source_name',
        'quantity_received',
        'date_received',
        'received_by',
    ];

    protected $casts = [
        'date_received' => 'date',
    ];

    public function reliefStock()
    {
        return $this->hasMany(ReliefStock::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}