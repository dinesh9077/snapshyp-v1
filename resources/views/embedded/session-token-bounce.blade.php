<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ config('setting.company_name') }}</title>
    {{-- Shown by ShopifyEmbeddedAuth when a request carries no proof it came from Shopify admin:
         ask App Bridge for a session token and reload this same URL with it --}}
    <meta name="shopify-api-key" content="{{ config('services.shopify_api_key') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
</head>

<body>
    <p style="font-family: sans-serif; text-align: center; margin-top: 40px;">Loading...</p>
    <script>
        shopify.idToken().then(function (token) {
            var url = new URL(window.location.href);
            url.searchParams.set('id_token', token);
            window.location.replace(url.toString());
        });
    </script>
</body>

</html>
