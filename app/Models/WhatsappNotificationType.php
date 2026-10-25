<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'message_template',
        'price',
        'is_free',
        'is_enabled',
        'is_recommended',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'is_enabled' => 'boolean',
        'is_recommended' => 'boolean',
    ];

    /**
     * Get notification type by type key.
     *
     * @param string $type
     * @return WhatsappNotificationType|null
     */
    public static function getByType($type)
    {
        return self::where('type', $type)->first();
    }

    /**
     * Get all enabled notification types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEnabled()
    {
        return self::where('is_enabled', 1)->get();
    }
}
