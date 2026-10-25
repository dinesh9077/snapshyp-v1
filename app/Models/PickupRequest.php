<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    use HasFactory;
	
	protected $fillable = [
        'user_id',
        'shipping_company_id',
        'pickup_id',
        'warehouse_id',
        'pickup_date',
        'pickup_start_time',
        'pickup_end_time', 
        'expected_package_count',
        'status',
    ];
	
	public function warehouse()
	{
		return $this->belongsTo(CourierWarehouse::class, 'warehouse_id');
	}
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	public function shippingCompany()
	{
		return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
	}
}
