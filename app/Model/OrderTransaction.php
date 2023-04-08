<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderTransaction extends Model
{
    protected $table = 'order_transactions';

    protected $casts = [
        'delivery_man_id' => 'integer',
        'order_id' => 'integer',
        'order_amount' => 'float',
        'received_by' => 'string',
        'delivery_charge' => 'float',
        'original_delivery_charge' => 'float',
        'tax' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
