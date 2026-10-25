<?php
    namespace App\Http\Middleware;
    
    use Closure;
    use Illuminate\Http\Request;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    
    class VerifyShopifySession
    {
        public function handle(Request $request, Closure $next)
        {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !preg_match('/Bearer\s(.+)/', $authHeader, $matches)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
    
            $token = $matches[1];
            try {
                $payload = JWT::decode($token, new Key(config('services.shopify_api_secret'), 'HS256'));
    
                // Optional: Validate exp, aud, iss etc.
                if (time() > $payload->exp) {
                    return response()->json(['error' => 'Token expired'], 401);
                }
    
                // Set CSP headers for iframe
                $shop = parse_url($payload->dest ?? '', PHP_URL_HOST);
               
                $response = $next($request);
                $response->headers->set('Content-Security-Policy', 'frame-ancestors https://*.myshopify.com https://admin.shopify.com;');
                $response->headers->set('X-Frame-Options', 'ALLOWALL');  
                $request->merge(['shop' => $shop ?? null, 'payload' => $payload]); 
               
                return $next($request);
    
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Invalid token'], 401);
            }
        }
    }
