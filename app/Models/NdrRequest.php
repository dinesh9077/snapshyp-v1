<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 

class NdrRequest extends Authenticatable
{
    use HasFactory, Notifiable; 
   
    protected $fillable = [
        'order_id',
        'shipping_company_id',
        'reattemptdate',
        'action',
        'remarks',
        'awb_number',
        'address_1',
        'phone',
    ];
    
}
