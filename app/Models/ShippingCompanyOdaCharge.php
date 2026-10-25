<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCompanyOdaCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_company_id',
        'name',
        'code',
        'per_kg',
        'min_charge',
        'sort_order',
    ];

    protected $casts = [
        'per_kg' => 'float',
        'min_charge' => 'float',
    ];

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }
}
