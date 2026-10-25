<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCompanyPincode extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_company_id',
        'pincode_id',
        'direction',
        'status',
        'service_mode',
        'oda_category',
        'oda_charge_id',
        'opa_category',
        'opa_charge_id',
        'cod_enabled',
    ];

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }

    public function zipcode()
    {
        return $this->belongsTo(Zipcode::class, 'pincode_id');
    }
}
