<?php
    namespace App\Http\Middleware;
    
    use Closure;
    
    class ShopifyCsp
    {
        public function handle($request, Closure $next)
        {
            $response = $next($request);

            $shop = $request->get('shop') ?? $request->query('shop') ?? '*';
            
            $response->headers->set(
                'Content-Security-Policy',
                "frame-ancestors https://*.myshopify.com https://admin.shopify.com;"
            );
    
            return $response;
        }
    }
