<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['inventory_id', 'product_id', 'quantity'];
    // Inventory model
    public function invnetoryItem()
    {
        return $this->belongsTo(Inventory::class);
    }
}
