<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateCardOtherCharge extends Model
{
    protected $table = 'rate_card_other_charges';

    protected $fillable = [
        'rate_card_id',
        'shipping_company_id',
        'other_charge_master_id',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function master()
    {
        return $this->belongsTo(OtherChargeMaster::class, 'other_charge_master_id');
    }
}
