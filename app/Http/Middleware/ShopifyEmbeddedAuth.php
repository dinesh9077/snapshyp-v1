<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Models\User;

/**
 * Logs the shop's seller in for the embedded Shopify app, but only after proving the
 * request really comes from that shop's Shopify admin. The ?shop= value alone is public
 * and must never be enough. Accepted proofs:
 *
 * - a Shopify session token (JWT signed with the app secret): ?id_token= or Authorization: Bearer
 * - Shopify's signed launch parameters (?hmac=... on the app URL, fresh timestamp)
 * - an existing Laravel session already logged in as this shop's seller
 *
 * A page request with none of these gets a small App Bridge page that fetches a fresh
 * session token and reloads the same URL with ?id_token=, so in-app links keep working.
 */
class ShopifyEmbeddedAuth
{
    // Shopify launch HMACs older than this are not accepted
    private const LAUNCH_MAX_AGE_SECONDS = 3600;

    public function handle(Request $request, Closure $next)
    {
        $shop = $request->query('shop') ?? $request->input('shop');
        $host = $request->query('host') ?? $request->input('host');

        // If no shop parameter, redirect to error
        if (!$shop) {
            return response('Missing shop parameter', 400);
        }

        // Check if shop exists in database
        $registeredShop = Shop::where('shopify_domain', $shop)->first();
        if (!$registeredShop) {
            return response('Shop not registered. Please reinstall the app.', 400);
        }

        // Check if there's a user registered for this shop with valid session token
        $shopUser = User::where('shop', $shop)
            ->where('status', 1)
            ->whereNotNull('shopify_session_token')
            ->first();

        if (!$shopUser) {
            // No user registered for this shop or no valid session token
            return redirect()->route('shopify.embedded.login', ['shop' => $shop, 'host' => $host]);
        }

        $alreadyLoggedIn = Auth::check() && Auth::id() === $shopUser->id;

        if (!$alreadyLoggedIn && !$this->hasValidSessionToken($request, $shop) && !$this->hasValidLaunchHmac($request, $shop)) {
            if ($request->expectsJson() || $request->ajax() || !$request->isMethod('GET')) {
                return response()->json(['status' => 'error', 'msg' => 'Shopify session expired. Please reload the app.'], 401);
            }
            return response()->view('embedded.session-token-bounce');
        }

        if (!$alreadyLoggedIn) {
            Auth::login($shopUser);
        }

        return $next($request);
    }

    // Shopify session token: HS256 JWT signed with the app secret, issued for this app and this shop
    private function hasValidSessionToken(Request $request, string $shop): bool
    {
        $token = $request->query('id_token') ?: $request->bearerToken();
        $secret = (string) config('services.shopify_api_secret');
        if (!$token || $secret === '') {
            return false;
        }

        try {
            JWT::$leeway = 10;
            $payload = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable $e) {
            Log::info('Shopify session token rejected: ' . $e->getMessage(), ['shop' => $shop]);
            return false;
        }

        $tokenShop = parse_url((string) ($payload->dest ?? ''), PHP_URL_HOST);
        return ($payload->aud ?? null) === config('services.shopify_api_key') && $tokenShop === $shop;
    }

    // Shopify's signed app-launch query (?shop=&timestamp=&hmac=...)
    private function hasValidLaunchHmac(Request $request, string $shop): bool
    {
        $query = $request->query();
        $hmac = $query['hmac'] ?? null;
        if (!$hmac || ($query['shop'] ?? null) !== $shop) {
            return false;
        }

        if (abs(time() - (int) ($query['timestamp'] ?? 0)) > self::LAUNCH_MAX_AGE_SECONDS) {
            return false;
        }

        unset($query['hmac']);
        ksort($query);
        $computed = hash_hmac('sha256', http_build_query($query), (string) config('services.shopify_api_secret'));

        return hash_equals($computed, (string) $hmac);
    }
}
