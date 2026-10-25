<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    use HasFactory;
	
	protected $fillable = [
		'id',
		'user_id',
		'amount',
		'status',
		'order_id',
		'transaction_type',
		'pg_name',
		'utr_no',
		'amount_type',
		'txn_number',
		'payable_response',
		'payment_receipt',
		'note',
		'transaction_status',
		'reject_note',
		'created_at',
		'updated_at'  
	];


	protected $casts = [
		'payable_response' => 'array',
	];
	
	protected $hidden = [
		'payable_response',
	];
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	} 
}
