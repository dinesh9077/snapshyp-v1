<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key',
        'api_url',
        'api_phone_number',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the singleton WhatsApp setting instance.
     *
     * @return WhatsappSetting
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'api_key' => null,
            'api_url' => null,
            'api_phone_number' => null,
            'is_enabled' => false,
        ]);
    }
}
