<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodVoucher extends Model
{
    use HasFactory;

	protected $fillable = [
		'id',
		'voucher_no',
		'user_id',
		'remarks',
		'amount',
		'voucher_date',
		'voucher_status',
		'reference_no',
		'payout_date',
		'created_at',
		'updated_at',
		'cod_charges',
		'gst_amount',
		'total_charges',
		'net_amount',
		'cod_plan_id',
		'plan_percentage',
		'cod_balance_expense'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}


	public function codVoucherOrders()
	{
		return $this->hasMany(Order::class, 'cod_voucher_id');
	}

	public function codPlan()
	{
		return $this->belongsTo(CodSetting::class, 'cod_plan_id');
	}
}
