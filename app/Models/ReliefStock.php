<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefStock extends Model
{
    public $guarded = ['id'];

    
    public function reliefPack()
    {
        return $this->belongsTo(ReliefPack::class,'relief_pack_id');
    }

    public function packReceipt()
    {
        return $this->belongsTo(PackReceipt::class,'pack_receipt_id');
    }

}
