<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReliefPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'current_stock',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(PackReceipt::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DistributionSchedule::class);
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('current_stock', $quantity);
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->current_stock < $quantity) {
            throw new \RuntimeException('Not enough boxes in stock.');
        }

        $this->decrement('current_stock', $quantity);
    }
}