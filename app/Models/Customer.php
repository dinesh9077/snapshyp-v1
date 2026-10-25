<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'alternate_mobile',
        'mobile',
        'status',
        'created_at',
        'updated_at',
        'gst_number',
        'aadhar_front',
        'aadhar_back',
        'pancard',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customerAddresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function customerOrder()
    {
        return $this->hasOne(Order::class, 'customer_id');
    }

    public function latestCustomerAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->orderByDesc('created_at');
    }
}
