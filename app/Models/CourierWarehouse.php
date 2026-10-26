<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierWarehouse extends Model
{
    use HasFactory;
	
	protected $fillable = [
        'user_id',
        'warehouse_name',
        'shipping_id',
        'warehouse_status',
        'company_name',
        'contact_name',
        'contact_number',
        'address', 
        'api_response',
        'contact_email',
        'created',
        'city',
        'state',
        'country',
        'zip_code',
        'label_options'
    ];
	
	protected $casts = [
        'api_response' => 'array',
        'created' => 'array',
        'label_options' => 'array',
    ];
	
	protected $hidden = [
        'api_response'
    ];
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
