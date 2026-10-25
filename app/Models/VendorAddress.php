<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorAddress extends Model
{
    use HasFactory;
	
	protected $fillable = [
        'vendor_id',
        'address',
        'country',
        'state',
        'city',
        'zip_code',
        'status', // 0 - Inactive, 1 - Active
        'shipping_id',
        'warehouse_name',
        'warehouse_status', // 0 - Inactive, 1 - Active
        'created_at',
        'updated_at'
    ];
}
