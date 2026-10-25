<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
	
	protected $fillable = [
		'user_id',
		'company_name',
		'first_name',
		'last_name',
		'email',
		'mobile',
		'status', // 0 = In-Active, 1 = Active
		'created_at',
		'updated_at',
	];

	public function vendorAddresses()
	{
		return $this->hasMany(VendorAddress::class, 'vendor_id');
	}
}
