<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'notification_type',
        'phone_number',
        'message',
        'charge',
        'status',
        'response',
    ];

    protected $casts = [
        'charge' => 'decimal:2',
    ];

    /**
     * Get the order associated with this log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user associated with this log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
