<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'rate_type',
        'is_rto_charge',
    ];

    public function rateCardMatrices()
    {
        return $this->hasMany(RateCardMatrix::class);
    }

    public function rateCardDetails()
    {
        return $this->hasMany(RateCardDetail::class);
    }
}
