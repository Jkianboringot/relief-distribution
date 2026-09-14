<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_head_name',
        'family_size',
        'address',
        'barangay',
        'contact_number',
        'qr_code',
        'eligibility_status',
    ];

    public function distributionTransactions(): HasMany
    {
        return $this->hasMany(DistributionTransaction::class);
    }

    public function hasClaimedFor(int $scheduleId): bool
    {
        return $this->distributionTransactions()
            ->where('distribution_schedule_id', $scheduleId)
            ->exists();
    }

    public function barangay(){
        return $this->belongsTo(Barangay::class);
    }
}