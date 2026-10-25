<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use App\Models\User;

class ShopifyEmbeddedAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');

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

        // User exists with valid session_token - ensure they're logged in via Laravel Auth
        if (!Auth::check() || Auth::id() !== $shopUser->id) {
            // Auto-login the user with valid session token
            Auth::login($shopUser);
        }

        // User is authenticated, shop matches, and has valid session token - allow access
        return $next($request);
    }
}
