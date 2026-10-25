<?php

namespace App\Models;

use App\Services\ShopifyTokenService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_domain',
        'shopify_token',
        'shop_name',
        'shop_email',
        'shop_owner',
        'is_active',
        'is_charge',
        'host',
        'access_token_expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'scopes',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    public function getValidShopifyToken(): string
    {
        return app(ShopifyTokenService::class)->getValidAccessToken($this);
    }
    
    public function shopUser()
    {
        return $this->belongsTo(User::class, 'shopify_domain', 'shop');
    }
}
