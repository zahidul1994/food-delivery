<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProductByBranch extends Model
{
    protected $table = 'product_by_branches';

    protected $fillable = [
        'product_id',
        'price',
        'discount_type',
        'discount',
        'branch_id',
        'is_available',
        'variations'
    ];

    protected $casts = [
        'id'=>'integer',
        'product_id'=>'integer',
        'discount_type'=>'string',
        'discount'=>'float',
        'price'=>'float',
        'branch_id'=>'integer',
        'is_available'=>'integer',
        'variations'=>'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
