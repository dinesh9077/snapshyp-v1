<?php

namespace App\Services;

use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ShopifyTokenService
{
    /**
     * Return a valid access token for the given shop, refreshing if needed.
     * Throws if we can't refresh.
     */
    public function getValidAccessToken(Shop $shop): string
    {
        // If access token is still valid, just return
        if (
            $shop->shopify_token && $shop->access_token_expires_at &&
            $shop->access_token_expires_at->isFuture()
        ) {
            return $shop->shopify_token;
        }

        // Otherwise, refresh using refresh_token
        return $this->refreshAccessToken($shop);
    }

    /**
     * Use refresh_token to get new access token.
     */
    protected function refreshAccessToken(Shop $shop): string
    {
        if (
            !$shop->refresh_token ||
            !$shop->refresh_token_expires_at ||
            $shop->refresh_token_expires_at->isPast()
        ) {
            throw new \RuntimeException('Refresh token missing or expired for shop ' . $shop->shopify_domain);
        }

        $apiKey = config('services.shopify_api_key');
        $apiSecret = config('services.shopify_api_secret');

        // See: https://shopify.dev/docs/apps/build/authentication-authorization/access-tokens/offline-access-tokens
        // The exact refresh endpoint may differ based on your flow/version.
        // Here we assume POST to /admin/oauth/access_token with grant_type=refresh_token etc.
        $response = Http::asForm()
            ->acceptJson()
            ->post("https://{$shop->shopify_domain}/admin/oauth/access_token", [
                'client_id' => $apiKey,
                'client_secret' => $apiSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $shop->refresh_token,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to refresh access token: ' . $response->body());
        }

        $data = $response->json();

        // Expect new access/refresh + expiries back
        $newAccessToken = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;
        $newRefreshToken = $data['refresh_token'] ?? null;
        $refreshTokenExpiresIn = $data['refresh_token_expires_in'] ?? null;

        if (!$newAccessToken || !$newRefreshToken || !$expiresIn || !$refreshTokenExpiresIn) {
            throw new \RuntimeException('Unexpected refresh response: missing fields.');
        }

        $accessTokenExpiresAt = Carbon::now()->addSeconds($expiresIn);
        $refreshTokenExpiresAt = Carbon::now()->addSeconds($refreshTokenExpiresIn);

        $shop->update([
            'shopify_token' => $newAccessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $newRefreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
        ]);

        return $newAccessToken;
    }
}
