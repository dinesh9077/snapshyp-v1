<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'quick_kyc' => [
        'key' => env('QUICK_KYC_KEY'),
        'base_url' => env('QUICK_KYC_URL'),
    ],
    "shopify_api_key" => env('SHOPIFY_API_KEY'),
    "shopify_api_secret" => env('SHOPIFY_API_SECRET'),
    "shopify_api_scopes" => env('SHOPIFY_API_SCOPES', 'read_orders,write_orders,read_fulfillments,write_fulfillments,read_assigned_fulfillment_orders,write_assigned_fulfillment_orders,read_locations,read_merchant_managed_fulfillment_orders,write_merchant_managed_fulfillment_orders,write_third_party_fulfillment_orders,read_products'),
    "shopify_api_version" => env('SHOPIFY_API_VERSION', '2025-10'),

    // Shared secret for courier status webhooks (?token=... in the URL registered with each courier)
    "courier_webhook_secret" => env('COURIER_WEBHOOK_SECRET'),

    // Wallet recharge payment gateway (MasterService)
    'recharge_gateway' => [
        'url' => env('RECHARGE_GATEWAY_URL', 'https://api.recharge.kashishindiapvtltd.com/payments'),
        'secret' => env('RECHARGE_GATEWAY_SECRET'),
        'user' => env('RECHARGE_GATEWAY_USER'),
    ],

];
