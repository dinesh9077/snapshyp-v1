<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWhatsappService extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type_id',
        'is_active',
        'purchased_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    /**
     * Get the user that owns the service.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification type.
     */
    public function notificationType()
    {
        return $this->belongsTo(WhatsappNotificationType::class, 'notification_type_id');
    }

    /**
     * Check if user has purchased a specific service.
     *
     * @param int $userId
     * @param int $notificationTypeId
     * @return bool
     */
    public static function hasPurchased($userId, $notificationTypeId)
    {
        return self::where('user_id', $userId)
            ->where('notification_type_id', $notificationTypeId)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get all purchased services for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserServices($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', 1)
            ->with('notificationType')
            ->get();
    }
}
