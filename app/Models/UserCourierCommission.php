<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCourierCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'courier_commission_id',
        'type',
        'value'
    ];

    public function courierCommission()
    {
        return $this->belongsTo(CourierCommission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
