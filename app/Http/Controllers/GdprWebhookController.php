<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GdprWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Optional: Verify signature
        if (!$this->verifyShopifySignature($request)) {
            Log::warning("GDPR webhook signature mismatch");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Shopify topic header
        $topic = $request->header('X-Shopify-Topic');

        Log::info("GDPR webhook received", [
            'topic' => $topic,
            'payload' => $request->all(),
        ]);

        // Route based on webhook topic
        switch ($topic) {
            case 'customers/data_request':
                $this->handleCustomerDataRequest($request->all());
                break;

            case 'customers/redact':
                $this->handleCustomerRedact($request->all());
                break;

            case 'shop/redact':
                $this->handleShopRedact($request->all());
                break;

            default:
                Log::notice("Unhandled GDPR webhook: $topic");
        }

        // Must return 200 OK for Shopify
        return response()->json(['status' => 'ok'], 200);
    }

    protected function handleCustomerDataRequest(array $data)
    {
        // Shopify wants ALL customer data they have
        Log::info("Customer data request", $data);

        // Example: return data from your DB or CRM
        // $customerId = $data['customer']['id'] ?? null;
        // $customer = Customer::find($customerId);

        // Format and send the data back if required
    }

    protected function handleCustomerRedact(array $data)
    {
        // Shopify says: delete or anonymize customer data
        Log::info("Customer redact request", $data);

        // Example:
        // $customerId = $data['customer']['id'];
        // Customer::where('shopify_id',$customerId)->delete();
    }

    protected function handleShopRedact(array $data)
    {
        // Shopify says: delete data related to this shop
        Log::info("Shop redact request", $data);

        // Example:
        // $shopDomain = $data['shop_domain'];
        // Shop::where('domain', $shopDomain)->delete();
    }

    protected function verifyShopifySignature(Request $request): bool
    {
        // 1. Get HMAC header from Shopify
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        if (empty($hmacHeader)) {
        // No HMAC header => definitely invalid request
        return false;
        }

        // 2. Get your app's shared secret (NOT the API key)
        $secret = config('services.shopify_api_secret'); // typically from config/services.php

        if (empty($secret)) {
        // Misconfiguration — better to fail closed
        throw new HttpException(500, 'Shopify client secret not configured.');
        }

        // 3. Get the raw request body exactly as Shopify sent it
        $data = $request->getContent(); // same as file_get_contents('php://input');

        // 4. Compute HMAC using SHA256, binary output, then Base64-encode
        $calculatedHmac = base64_encode(
        hash_hmac('sha256', $data, $secret, true)
        );

        // 5. Timing-safe comparison
        return hash_equals($hmacHeader, $calculatedHmac);
    }
}
