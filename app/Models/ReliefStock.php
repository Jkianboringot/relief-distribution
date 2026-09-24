<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefStock extends Model
{
    public $guarded = ['id'];

    
    public function reliefPack()
    {
        return $this->belongsTo(ReliefPack::class);
    }

    public function packReceipt()
    {
        return $this->hasMany(PackReceipt::class);
    }

}
