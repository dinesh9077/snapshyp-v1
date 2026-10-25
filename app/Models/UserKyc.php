<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKyc extends Model
{
	use HasFactory;
	// Fillable fields
	protected $fillable = [
		'user_id',
		'pancard',
		'pancard_status',
		'pancard_text',
		'pan_full_name',
		'pancard_category',
		'pan_reason',
		'aadhar',
		'aadhar_front',
		'aadhar_status',
		'aadhar_text',
		'aadhar_full_name',
		'aadhar_address',
		'aadhar_dob',
		'aadhar_gender',
		'aadhar_zip',
		'aadhar_reason',
		'aadhar_role',
		'pan_role',
		'gst',
		'gst_business_name',
		'gst_address',
		'gst_mobile_no',
		'gst_email',
		'gstin_status',
		'gst_status',
		'gst_text',
		'gst_reason',
		'gst_role',
        'udyam_number',
        'udyam_enterprise_name',
        'udyam_status',
        'udyam_text',
        'udyam_role',
        'udyam_reason',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'bank_status',
        'bank_role',
        'bank_reason',
        'bank_text',
        'amount_deposited',
		'pancard_image',
		'aadhar_back',
		'gst_image',
		'bank_passbook',
        'udyam_image',
	];

	// Cast boolean fields properly
	protected $casts = [
		'pancard_text' => 'array',
		'aadhar_text' => 'array',
		'gst_text' => 'array',
        'udyam_text' => 'array',
        'bank_text' => 'array',
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
