<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketRemark extends Model
{
    use HasFactory;
	
	// Allow mass assignment
    protected $fillable = [
        'ticket_id',
        'user_id',
        'remark',
        'images',
        'role',
        'created_at',
        'updated_at',
    ]; 
    // Ensure timestamps are managed
    protected $dates = ['created_at', 'updated_at'];
	
	protected $casts = [
        'images' => 'array', // Store images as JSON array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
