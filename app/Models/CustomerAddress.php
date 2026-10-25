<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;
	protected $fillable = [
        'customer_id',
        'address',
        'country',
        'state',
        'city',
        'zip_code',
        'status', // 0 = Inactive, 1 = Active
        'created_at',
        'updated_at',
    ];

    // Define Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
