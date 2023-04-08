<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PointTransitions extends Model
{
    protected $table = "point_transitions";

    protected $fillable = [
        'user_id',
        'transaction_id',
        'reference',
        'type',
        'debit',
        'credit',
        'amount',
        'created_at',
        'updated_at',
    ];


    protected $casts = [
        'amount' => 'float',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'credit' => 'float',
        'debit' => 'float',
        'reference'=>'string',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
