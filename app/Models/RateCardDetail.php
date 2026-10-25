<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCardDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_card_id',
        'shipping_company_id',
        'zone_setting_id',
        'weight',
        'zone_slug',
        'rate',
        'extra_rate',
        'cod_charges',
        'cod_percentage',
        'status',
    ];

    public function rateCard()
    {
        return $this->belongsTo(RateCard::class);
    }

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class);
    }
    public function zoneSetting()
    {
        return $this->belongsTo(ZoneSetting::class);
    }
}
