<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReliefPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        
    ];

   public function receipts(): HasMany
{
    return $this->hasMany(PackReceipt::class);
}

    public function reliefStock()
    {
        return $this->hasMany(ReliefStock::class);
    }


       public function distributionReliefStock(): HasMany
    {
        return $this->hasMany(DistributionReliefStock::class);
    }
 
    // current stock = total received - total distributed
    protected function currentStock(): Attribute
    {
        return Attribute::make(
            get: function () {
                $in = $this->relationLoaded('reliefStock')
                    ? $this->reliefStock->sum('quantity')
                    : $this->reliefStock()->sum('quantity');
 
                $out = $this->relationLoaded('distributionReliefStock')
                    ? $this->distributionReliefStock->sum('quantity')
                    : $this->distributionReliefStock()->sum('quantity');
 
                return (int) $in - (int) $out;
            },
        );
    }
    
}