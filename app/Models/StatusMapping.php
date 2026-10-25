<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_status',
        'internal_status'
    ];

    protected $casts = [
        'courier_status' => 'array',
    ];

    /**
     * Get all unique internal statuses (default + custom)
     */
    public static function getInternalStatuses()
    {
        $defaultStatuses = [
            'New',
            'Ready To Ship',
            'Ready to Pickup',
            'Pending Pickup',
            'In Transit',
            'Out For Delivery',
            'Delivered',
            'Cancelled',
            'Lost',
            'Damaged',
            'RTO All',
            'RTO In Transit',
            'RTO Delivered',
            'RTO Lost',
            'RTO Damaged',
        ];

        // Get custom internal statuses from database
        $customStatuses = self::select('internal_status')
                             ->distinct()
                             ->whereNotIn('internal_status', $defaultStatuses)
                             ->pluck('internal_status')
                             ->toArray();

        return array_merge($defaultStatuses, $customStatuses);
    }
}
