<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponse;

class CheckApiToken
{
    use ApiResponse;
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        // Fallbacks for environments where Authorization header is stripped.
        if (empty($token)) {
            $authHeader = $request->header('Authorization') ?: $request->server('HTTP_AUTHORIZATION');
            if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
                $token = $matches[1];
            } elseif ($authHeader && preg_match('/Token\s+(\S+)/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (empty($token)) {
            $token = $request->header('X-API-Token')
                ?: $request->header('api-token')
                ?: $request->input('api_token');
        }

        if (empty($token)) {
            return $this->errorResponse('Authorization token missing', 401);
        }

        // Example: if token is stored in `api_token` column in `users` table
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return $this->errorResponse('Invalid API token', 401);
        }

        // Set the authenticated user for later use
        $request->merge(['user_id' => $user->id]);

        return $next($request);
    }
}
