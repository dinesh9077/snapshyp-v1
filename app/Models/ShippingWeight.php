<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingWeight extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipping_company_id',
        'weight',
        'status',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Get the shipping company that owns the weight.
     */
    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }
}
