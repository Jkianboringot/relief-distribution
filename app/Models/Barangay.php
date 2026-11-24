<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barangay extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code', 'municipality_id'];

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Benificiary::class);
    }
    public function distributionSchedule(): HasMany
    {
        return $this->hasMany(DistributionSchedule::class);
    }


}
