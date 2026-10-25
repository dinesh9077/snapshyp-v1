<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class weightDescrepencyHistory extends Model
{
    use HasFactory;
	// Allow mass assignment
    protected $fillable = [
        'order_id',
        'status_descrepency',
        'action_by',
        'remarks',
    ];

    // Ensure timestamps are managed
    protected $dates = ['created_at', 'updated_at'];
}
