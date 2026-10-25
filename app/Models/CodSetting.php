<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'days',
        'percentage',
        'gst_percentage',
        'description',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the default COD setting
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Get active COD settings
     */
    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('days')->get();
    }

    /**
     * Set as default (unset others first)
     */
    public function setAsDefault()
    {
        // Unset all other defaults
        self::where('is_default', true)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }
}
