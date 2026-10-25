<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'otp',
        'remember_token',
        'mobile',
        'profile_image',
        'address',
        'state',
        'city',
        'country',
        'role',
        'staff_id',
        'company_name',
        'zip_code',
        'gender',
        'charge_type',
        'charge',
        'b2c_charge_type',
        'b2c_charge',
        'b2b_charge_type',
        'b2b_charge',
        'franchise_charge_type',
        'franchise_charge',
        'status',
        'xpass',
        'kyc_status',
        'wallet_amount',
        'sms_date',
        'shopify_grandfathered',
        'shopify_freemium',
        'plan_id',
        'cod_plan_id',
        'cod_plan_active',
        'shopify_namespace',
        'password_updated_at',
        'shop',
        'api_token',
        'shopify_session_token',
        'shopify_sync_mode',
        'cod_remittance_cycle_days',
        'payout_days',
        'remittance_service_fee_type',
        'remittance_service_fee_value',
        'cod_wallet_transfer_value',
        'rate_card_id',
        'b2c_rate_card_id',
        'b2b_rate_card_id',
        'seller_id',
        'shipping_api_provider',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = ['deleted_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermissionTo($permission)
    {
        // Check if the user has the given permission
        return $this->permissions()->where('slug', $permission)->exists();
    }

    public function permissions()
    {
        // Define the relationship with the permissions table
        return $this->hasMany(Permission::class, 'user_id', 'id');
    }

    public function userKyc()
    {
        // Define the relationship with the permissions table
        return $this->hasOne(UserKyc::class, 'user_id');
    }

    public function createdBy()
    {
        // Define the relationship with the permissions table
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function shopDetail()
    {
        return $this->belongsTo(Shop::class, 'shop', 'shopify_domain');
    }

    public function codPlan()
    {
        return $this->belongsTo(CodSetting::class, 'cod_plan_id');
    }

    public function rateCard()
    {
        return $this->belongsTo(RateCard::class, 'rate_card_id');
    }

    public function b2cRateCard()
    {
        return $this->belongsTo(RateCard::class, 'b2c_rate_card_id');
    }

    public function b2bRateCard()
    {
        return $this->belongsTo(RateCard::class, 'b2b_rate_card_id');
    }

    private function normalizeRateCardId($value): int
    {
        if ($value === null) {
            return 0;
        }
        if (is_string($value) && trim($value) === '') {
            return 0;
        }
        return (int) $value;
    }

    public function getFranchiseOwner(): ?self
    {
        if (!in_array(($this->role ?? null), ['user', 'staff'], true) || empty($this->staff_id)) {
            return null;
        }

        $parent = $this->relationLoaded('createdBy')
            ? $this->createdBy
            : $this->createdBy()->first(['id', 'role', 'staff_id', 'rate_card_id', 'b2c_rate_card_id', 'b2b_rate_card_id', 'shipping_api_provider']);

        if (!$parent) {
            return null;
        }

        if (($parent->role ?? null) === 'franchise') {
            return $parent;
        }

        if (($parent->role ?? null) !== 'staff' || empty($parent->staff_id)) {
            return null;
        }

        $franchiseParent = $parent->relationLoaded('createdBy')
            ? $parent->createdBy
            : $parent->createdBy()->first(['id', 'role', 'rate_card_id', 'b2c_rate_card_id', 'b2b_rate_card_id', 'shipping_api_provider']);

        if (!$franchiseParent || ($franchiseParent->role ?? null) !== 'franchise') {
            return null;
        }

        return $franchiseParent;
    }

    public function isFranchiseSeller(): bool
    {
        return ($this->role ?? null) === 'user' && $this->getFranchiseOwner() !== null;
    }

    public function getFranchiseCommissionRate(): float
    {
        return $this->isFranchiseSeller() ? 1.0 : 0.0;
    }

    public function hasShippingApiProvider(?string $provider = null): bool
    {
        $assignedProvider = strtolower(trim((string) ($this->shipping_api_provider ?? '')));
        if ($assignedProvider === '') {
            return false;
        }

        if ($provider === null) {
            return true;
        }

        return $assignedProvider === strtolower(trim((string) $provider));
    }

    public function canAccessShadowfax(): bool
    {
        if (($this->role ?? null) === 'franchise') {
            return $this->hasShippingApiProvider('shadowfax') || $this->hasShippingApiProvider('routxmitra');
        }

        if (($this->role ?? null) === 'staff') {
            $creator = $this->relationLoaded('createdBy')
                ? $this->createdBy
                : $this->createdBy()->first(['id', 'role', 'shipping_api_provider']);

            if ($creator && ($creator->role ?? null) === 'franchise') {
                return $creator->hasShippingApiProvider('shadowfax') || $creator->hasShippingApiProvider('routxmitra');
            }
        }

        if (($this->role ?? null) === 'user') {
            $franchiseOwner = $this->getFranchiseOwner();
            if ($franchiseOwner) {
                return $franchiseOwner->hasShippingApiProvider('shadowfax') || $franchiseOwner->hasShippingApiProvider('routxmitra');
            }
        }

        return true;
    }

    private function getInheritedRateCardIdFromParent(int $packageType = 1): int
    {
        $parent = $this->getFranchiseOwner();
        if (!$parent) {
            return 0;
        }

        $b2c = $this->normalizeRateCardId($parent->b2c_rate_card_id ?? $parent->rate_card_id ?? 0);
        $b2b = $this->normalizeRateCardId($parent->b2b_rate_card_id ?? $parent->rate_card_id ?? 0);

        return $packageType === 2 ? $b2b : $b2c;
    }

    public function getAssignedRateCardId(int $packageType = 1)
    {
        $selfB2c = $this->normalizeRateCardId($this->b2c_rate_card_id);
        $selfB2b = $this->normalizeRateCardId($this->b2b_rate_card_id);
        $selfDefault = $this->normalizeRateCardId($this->rate_card_id);

        if ($packageType === 2) {
            if ($selfB2b > 0) {
                return $selfB2b;
            }
            if ($selfDefault > 0) {
                return $selfDefault;
            }
            return $this->getInheritedRateCardIdFromParent(2);
        }

        if ($selfB2c > 0) {
            return $selfB2c;
        }
        if ($selfDefault > 0) {
            return $selfDefault;
        }
        return $this->getInheritedRateCardIdFromParent(1);
    }

    public function getAssignedRateCardIdByType(string $rateType = 'b2c')
    {
        return $this->getAssignedRateCardId(strtolower($rateType) === 'b2b' ? 2 : 1);
    }

    public function isMasterAdmin(): bool
    {
        return $this->role === 'master_admin';
    }

    public function isAdminLevel(): bool
    {
        return in_array($this->role, ['admin', 'master_admin'], true);
    }

    /**
     * Get COD remittance cycle days - user-specific or global config
     */
    public function getCodRemittanceCycleDays()
    {
        return $this->cod_remittance_cycle_days ?? config('setting.cod_remittance_cycle_days', 3);
    }

    /**
     * Get payout days - user-specific or global config
     */
    public function getPayoutDays()
    {
        return !empty($this->payout_days)
            ? explode(',', $this->payout_days)
            : explode(',', config('setting.payout_days', 'Monday,Thursday'));
    }

    /**
     * Get remittance service fee type - user-specific or global config
     */
    public function getRemittanceServiceFeeType()
    {
        return $this->remittance_service_fee_type ?? config('setting.remittance_service_fee_type', 'percentage');
    }

    /**
     * Get remittance service fee value - user-specific or global config
     */
    public function getRemittanceServiceFeeValue()
    {
        return $this->remittance_service_fee_value ?? config('setting.remittance_service_fee_value', 0);
    }

    /**
     * Get COD to wallet transfer value - user-specific or global config
     */
    public function getCodWalletTransferValue()
    {
        return $this->cod_wallet_transfer_value ?? config('setting.cod_wallet_transfer_value', 0);
    }

    /**
     * Get all COD remittance settings - user-specific or global config
     */
    public function getCodRemittanceSettings()
    {
        return [
            'cod_remittance_cycle_days' => $this->getCodRemittanceCycleDays(),
            'payout_days' => $this->getPayoutDays(),
            'remittance_service_fee_type' => $this->getRemittanceServiceFeeType(),
            'remittance_service_fee_value' => $this->getRemittanceServiceFeeValue(),
            'cod_wallet_transfer_value' => $this->getCodWalletTransferValue(),
        ];
    }

    public function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function isFranchise(): bool
    {
        return in_array($this->normalizedRole(), ['franchise', 'frenchise'], true);
    }

    public function isDataScopedRole(): bool
    {
        return $this->normalizedRole() === 'user';
    }

    public function getDataOwnerId(): ?int
    {
        return $this->isDataScopedRole() ? (int) $this->id : null;
    }
}
