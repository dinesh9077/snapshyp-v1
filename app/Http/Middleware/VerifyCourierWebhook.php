<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Courier status webhooks change order status and can trigger RTO charges, so they must
 * come from the courier. Couriers can't sign requests, so the webhook URL registered in
 * each courier's dashboard carries a shared secret: ?token=<COURIER_WEBHOOK_SECRET>
 * (or an X-Webhook-Token header).
 *
 * Until COURIER_WEBHOOK_SECRET is set, requests are let through and logged, so live
 * webhooks keep working while the courier dashboards are updated.
 */
class VerifyCourierWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('services.courier_webhook_secret');

        if ($secret === '') {
            Log::warning('Courier webhook accepted without a secret; set COURIER_WEBHOOK_SECRET.', ['path' => $request->path()]);
            return $next($request);
        }

        $token = (string) ($request->query('token') ?? $request->header('X-Webhook-Token') ?? '');
        if (!hash_equals($secret, $token)) {
            Log::warning('Courier webhook rejected: bad or missing token.', ['path' => $request->path(), 'ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'msg' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
