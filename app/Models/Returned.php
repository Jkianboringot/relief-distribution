<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Returned extends Model
{
    protected $fillable = ['order_id', 'barangay_id'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_returns')
            ->withPivot(['price', 'quantity']);
    }


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
         
    }

         public function barangay(): BelongsTo
    {
        return $this->belongsTo(barangay::class);
         
    }
}
