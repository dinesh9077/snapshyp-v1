<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverheadCharge extends Model
{
    use HasFactory;

    protected $table = 'overhead_charges';

    protected $fillable = [
        'shipping_company_id',
        'flat_mile_per_kg',
        'flat_mile_min',
        'rov_owner_percent',
        'rov_owner_min',
        'rov_carrier_percent',
        'rov_carrier_min',
        'green_per_kg',
        'green_min',
        'oda_per_kg',
        'oda_min',
        'cod_percent',
        'cod_min',
        'divisor',
        'minimum_charge',
        'gst_percent',
    ];
}
