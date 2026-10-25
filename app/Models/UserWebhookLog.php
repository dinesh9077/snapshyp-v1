<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWebhookLog extends Model
{
    protected $table = 'user_webhook_logs';

    protected $fillable = [
        'webhook_id',
        'user_id',
        'event',
        'url',
        'payload',
        'status',
        'attempt',
        'max_attempts',
        'http_status',
        'duration_ms',
        'response_body',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'max_attempts' => 'integer',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function webhook()
    {
        return $this->belongsTo(UserWebhook::class, 'webhook_id');
    }
}
