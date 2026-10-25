<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\OAuthServerException;
use Illuminate\Auth\AuthenticationException;
use App\Traits\ApiResponse;
use Throwable;
use Illuminate\Support\Facades\Auth;

class CheckPassportToken
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        try {
            // 🔹 Check if token is missing (no Authorization header)
            if (!$request->bearerToken()) {
                return $this->errorReponse('Unauthorized: Token missing', 401);
            }

            // 🔹 Check if user is authenticated via token
            if (!Auth::guard('api')->check()) {
                return $this->error('Unauthorized: Invalid token', 401);
            }

            return $next($request);

        } catch (OAuthServerException $e) {
            // Expired / revoked / malformed
            return $this->error('Unauthorized: ' . $e->getMessage(), 401);

        } catch (AuthenticationException $e) {
            // Authentication failed
            return $this->error('Unauthorized: Authentication required', 401);

        } catch (Throwable $e) {
            // Fallback error
            return $this->error('Something went wrong', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
