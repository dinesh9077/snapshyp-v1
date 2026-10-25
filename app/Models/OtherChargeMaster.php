<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCompany;

class OtherChargeMaster extends Model
{
    protected $table = 'other_charge_masters';

    protected $fillable = [
        'shipping_company_id',
        'name',
        'code',
        'calc_type',
        'default_config',
        'zip_codes',
        'is_active',
    ];

    protected $casts = [
        'default_config' => 'array',
        'zip_codes' => 'array',
        'is_active' => 'boolean',
    ];

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }
}
