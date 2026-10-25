<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NdrDetail extends Model
{
    use HasFactory;
	protected $fillable = [ 
        'shipping_id',
        'order_id',
        'awb_number',
        'ndr_date',
        'remarks',
        'total_attempts',
        'upl_id',
        'status',
        'action',
        'response',
    ];
	
	protected $casts = [
        'response' => 'array'
    ];
	
	public function orderAwb()
	{
		return $this->belongsTo(Order::class, 'order_id', 'id');
	}
	
	public function ndrRequests()
	{
		return $this->hasMany(NdrRequest::class, 'order_id', 'order_id');
	}
}
