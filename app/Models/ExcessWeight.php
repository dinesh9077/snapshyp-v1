<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcessWeight extends Model
{
    use HasFactory;
		
	// Allow mass assignment
    protected $fillable = [
        'user_id',
        'order_id',
        'chargeable_weight',
        'excess_weight',
        'excess_charge',
        'product_images',
        'status',
    ];

    // Ensure timestamps are managed
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = ['product_images' => 'array'];
}
