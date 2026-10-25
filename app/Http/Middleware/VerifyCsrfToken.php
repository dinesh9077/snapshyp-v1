<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/api/update-sync-setting',
        'recharge/wallet/response',
        'shopify-webhook/orders-create',
        'shopify-webhook/orders-cancelled',
        'webhooks/*',
        'webhook/app-uninstalled',
        'shopify/bulk-shipment/now',
        '/delhivery/webhook',
        // '/zoho/payment/webhook',
        '/urbanebolt/webhook',
        '/shipmozo/webhook',
        '/xpressbees/webhook',
        '/shadowfax/webhook',
        'woocommerce/order/create',
        'shopify/login',
        'shopify/register',
        'webhook/receive',
    ];
}
