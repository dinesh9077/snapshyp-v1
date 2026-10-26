<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // null = global slab, else that seller's own slab
        'slab',
        'length',
        'width',
        'height',
        'status'
    ];

    /**
     * Package size for a weight: the smallest active slab that fits it. A seller who has set up
     * their own slabs gets only those (no match = no dimensions); a seller with none uses the
     * global slabs. $exact matches the slab value itself (order form auto-fill).
     */
    public static function forWeight(?int $userId, $weight, bool $exact = false): ?self
    {
        $weight = (float) $weight;
        if ($weight <= 0) {
            return null;
        }

        $owner = ($userId && static::where('user_id', $userId)->where('status', 1)->exists()) ? $userId : null;

        $query = static::where('status', 1)
            ->when($owner, fn ($q) => $q->where('user_id', $owner), fn ($q) => $q->whereNull('user_id'));
        $exact ? $query->where('slab', $weight) : $query->where('slab', '>=', $weight)->orderBy('slab', 'asc');

        return $query->first();
    }
}
