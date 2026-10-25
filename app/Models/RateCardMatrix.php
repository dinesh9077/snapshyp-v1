<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCardMatrix extends Model
{
    use HasFactory;

    protected $table = 'rate_card_matrices';

    protected $fillable = [
        'rate_card_id',
        'shipping_company_id',
        'from_zone_id',
        'to_zone_id',
        'rate_per_kg',
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

    public function fromZone()
    {
        return $this->belongsTo(ZoneSetting::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(ZoneSetting::class, 'to_zone_id');
    }
}
