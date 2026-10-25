<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
	
	protected $fillable = [
		'user_id',
		'shipping_company_id',
		'invoice_number',
		'invoice_state',
		'month_start',
		'month_end',
		'invoice_date',
		'invoice_period',
		'base_amount',
		'cgst_amount',
		'sgst_amount',
		'igst_amount',
		'total_amount',
	];
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	public function orders()
	{
		return $this->hasMany(Order::class, 'invoice_id');
	}

	public function shippingCompany()
	{
		return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
	}
}
