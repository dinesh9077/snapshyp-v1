<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
	protected $fillable = [
        'user_id',
        'awb_number',
        'contact_name',
        'contact_phone',
        'ticket_no',
        'text',
        'revert',
        'status',
    ];
	
	public function remarks()
	{
		return $this->hasMany(TicketRemark::class, 'ticket_id');
	}

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
