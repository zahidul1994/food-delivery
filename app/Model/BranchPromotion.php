<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BranchPromotion extends Model
{
    protected $casts = [
        'branch_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

}
