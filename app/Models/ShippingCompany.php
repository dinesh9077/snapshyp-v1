<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShippingCompany extends Model
{
    use HasFactory;   
	
	// Specify the fields that are allowed for mass assignment
    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'secret_key',
        'email',
        'password',
        'url',
        'mode',
        'freight_mode',
        'carrier_type',
        'selection_mode',
        'status',
        'tax',
        'logo',
        'created_at',
        'updated_at', 
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];
	
    public function hasExpired()
    {
        if (!$this->expired_at) {
            return true;
        }
        return Carbon::now()->addMinutes(30)->greaterThanOrEqualTo($this->expired_at);
    }

    public function scopeVisibleInRateCards($query)
    {
        return $query
            ->where(function ($query) {
                $query->whereNull('name')
                    ->orWhereRaw('LOWER(name) NOT LIKE ?', ['%routx%']);
            })
            ->where(function ($query) {
                $query->whereNull('url')
                    ->orWhereRaw('LOWER(url) NOT LIKE ?', ['%routxmitra%']);
            });
    }

    public function scopeWithoutRoutx($query)
    {
        return $query
            ->where('id', '!=', 1)
            ->where(function ($query) {
                $query->whereNull('name')
                    ->orWhereRaw('LOWER(name) NOT LIKE ?', ['%routx%']);
            })
            ->where(function ($query) {
                $query->whereNull('url')
                    ->orWhereRaw('LOWER(url) NOT LIKE ?', ['%routxmitra%']);
            });
    }

    public function scopeVisibleForUser($query, $user)
    {
        if ($user && method_exists($user, 'canAccessRoutx') && !$user->canAccessRoutx()) {
            return $query->withoutRoutx();
        }

        return $query;
    }

    public static function filterVisibleRateCardCompanyIds(array $companyIds): array
    {
        $normalizedIds = collect($companyIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($normalizedIds)) {
            return [];
        }

        return static::query()
            ->visibleInRateCards()
            ->whereIn('id', $normalizedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
