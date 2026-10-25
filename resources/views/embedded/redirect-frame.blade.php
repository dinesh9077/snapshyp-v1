<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting…</title>

    <!-- ✅ App Bridge v2 UMD (OAuth safe) -->
    <script src="https://unpkg.com/@shopify/app-bridge@2.0.32/umd/index.min.js"></script>
    <script src="https://unpkg.com/@shopify/app-bridge/actions@2.0.32/umd/index.min.js"></script>
</head>
<body>

<script>
    // App Bridge v2 exposes default
    var createApp = window['app-bridge'];

    var actions = window['app-bridge-actions'];
    var Redirect = actions.Redirect;

    var app = createApp({
        apiKey: "{{ config('services.shopify_api_key') }}",
        host: "{{ request('host') }}",
        forceRedirect: true
    });

    Redirect.create(app).dispatch(
        Redirect.Action.REMOTE,
        "{{ $redirectUrl }}"
    );
</script>

Redirecting to Shopify…
</body>
</html>
