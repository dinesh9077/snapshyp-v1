<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'events',
        'secret_key',
        'status',
    ];

    protected $casts = [
        'events' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
