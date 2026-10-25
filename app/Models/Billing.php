<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;
	
	protected $fillable = [
		'user_id',
		'billing_type',
		'billing_type_id',
		'transaction_type',
		'amount',
		'note',
		'created_at',
		'updated_at',
	];
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	} 
	public function order()
	{
		return $this->belongsTo(Order::class, 'billing_type_id');
	}

	public function userWallet()
	{
		return $this->belongsTo(UserWallet::class, 'billing_type_id');
	}
}
