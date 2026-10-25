<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneSetting extends Model
{
    use HasFactory;
    protected $table = 'zone_settings';

    protected $fillable = ['heading', 'zone_code', 'zone_name', 'zone', 'shipping_company_id', 'rate_type', 'is_default', 'sort_order', 'slug'];

    protected $casts = [
        'zone' => 'array', // cast JSON to array automatically
    ];

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class);
    }
}
