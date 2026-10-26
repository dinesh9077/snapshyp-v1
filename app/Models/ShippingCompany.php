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
        'provider',
        'is_default',
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
        'is_default' => 'boolean',
    ];

    public const PROVIDER_SHADOWFAX = 'shadowfax';

    // Integrated couriers and the credential fields each one needs (field => form label)
    public const PROVIDERS = [
        self::PROVIDER_SHADOWFAX => [
            'label' => 'Shadowfax',
            'fields' => ['api_key' => 'Api Key/Token', 'url' => 'Url'],
            'default_url' => 'https://dale.shadowfax.in/api',
        ],
    ];

    // Every credential field any provider uses, for building the account form
    public static function credentialFields(): array
    {
        $fields = [];
        foreach (self::PROVIDERS as $provider) {
            $fields += $provider['fields'];
        }
        return $fields;
    }

    public function providerLabel(): string
    {
        return self::PROVIDERS[$this->provider]['label'] ?? 'Other';
    }

    // Every account of the Shadowfax integration (not just id 1) uses ShadowfaxService
    public function isShadowfax(): bool
    {
        return $this->provider === self::PROVIDER_SHADOWFAX;
    }

    public static function isShadowfaxId($id): bool
    {
        return $id !== null && static::whereKey($id)->where('provider', self::PROVIDER_SHADOWFAX)->exists();
    }

    /**
     * Courier accounts a seller may quote and ship with: per provider, the account assigned
     * to the seller if it is active, otherwise that provider's default account.
     */
    public static function availableFor($user)
    {
        $active = static::where('status', 1)->where('provider', '!=', 'other')->orderBy('id')->get();

        return $active->groupBy('provider')->map(function ($accounts) use ($user) {
            $assigned = $user ? $accounts->firstWhere('id', (int) $user->assigned_shipping_company_id) : null;
            return $assigned ?? $accounts->firstWhere('is_default', true) ?? $accounts->first();
        })->filter()->values();
    }

    public static function isAvailableFor($user, $shippingCompanyId): bool
    {
        return static::availableFor($user)->contains('id', (int) $shippingCompanyId);
    }

    public function hasExpired()
    {
        if (!$this->expired_at) {
            return true;
        }
        return Carbon::now()->addMinutes(30)->greaterThanOrEqualTo($this->expired_at);
    }

    public function scopeVisibleInRateCards($query)
    {
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
