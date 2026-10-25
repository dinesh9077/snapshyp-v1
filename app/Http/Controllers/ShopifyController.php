<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Shop;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\CourierWarehouse;
use App\Models\ShippingCompany;
use App\Models\WeightDimension;
use App\Models\WhatsappSetting;
use App\Models\CodSetting;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Str;
use App\Models\Notification;
use App\Models\LabelPreference;
use App\Services\{
    DelhiveryB2CService,
    DelhiveryService,
    MasterService
};
use App\Jobs\ProcessBulkShipment;
use App\Jobs\AppUninstalledJob;
use App\Mail\OtpMail;

class ShopifyController extends Controller
{
    protected DelhiveryB2CService $delhiveryB2CService;
    protected DelhiveryService $delhiveryService;
    protected MasterService $masterService;
    protected $settings;
    public function __construct()
    {
        error_reporting(0);
        $this->delhiveryB2CService = new DelhiveryB2CService();
        $this->delhiveryService = new DelhiveryService();
        $this->masterService = new MasterService();
        $this->settings = WhatsappSetting::getSettings();
    }

    public function editAddress(Request $request)
    {
        try {
            $encoded = $request->query('data');
            $data = json_decode(base64_decode(urldecode($encoded)), true);

            if (!$data || empty($data['customer_address_id'])) {
                return response("Invalid link or expired.", 400);
            }

            $address = CustomerAddress::find($data['customer_address_id']);

            if (!$address) {
                return response("Address not found.", 404);
            }

            $customer_name = $data['customer_name'];
            $fromNumber = $data['fromNumber'];

            return view('edit_address', [
                'address' => $address,
                'customer_name' => $customer_name,
                'fromNumber' => $fromNumber,
            ]);
        } catch (\Exception $e) {
            return response("Error loading address: " . $e->getMessage(), 500);
        }
    }

    public function updateAddress(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:customer_addresses,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'from_number' => 'nullable|string',
            'customer_name' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 Fetch and update address
            $address = CustomerAddress::findOrFail($request->id);
            $address->update([
                'address' => $request->address,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
            ]);

            DB::commit();

            // 🔹 After successful update, send WhatsApp message (non-critical)
            if (!empty($request->from_number)) {
                try {
                    $data = [
                        "phone" => $request->from_number,
                        "template_name" => "address_confirmation_thankyou",
                        "template_language" => "en",
                        "components" => [
                            [
                                "type" => "BODY",
                                "parameters" => [
                                    ["type" => "text", "text" => $request->customer_name ?? 'Customer'],
                                    ["type" => "text", "text" => $request->address],
                                ],
                            ],
                        ],
                    ];

                    $this->sendTemplateMessage($data);
                } catch (\Throwable $e) {
                    Log::error("WhatsApp sendTemplateMessage failed: " . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', '✅ Address updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ Address update failed: " . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Something went wrong while updating the address. Please try again later.'
            ]);
        }
    }


    public function sendTemplateMessage($data)
    {
        $payload = array_merge([
            "token" => $this->settings->api_key
        ], $data);

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->post($this->settings->api_url . '/api/wpbox/sendtemplatemessage', $payload);

            if ($response->successful()) {
                Log::info("✅ Message sent successfully to unregistered user", [
                    'payload' => $data,
                    'response' => $response->json(),
                ]);
                return 1;
            } else {
                Log::warning("⚠️ SendMessage API call returned error", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return 0;
            }
        } catch (\Exception $e) {
            Log::error("❗ Exception while sending unregistered message", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function index(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');

        // Middleware has already verified authentication and shop match
        // Show order listing dashboard
        return view('embedded.shopify-orders', compact('shop', 'host'));
    }

    public function home(Request $request)
    {
        return view('embedded.dashboard');
    }

    public function install(Request $request)
    {
        $shop = $request->query('shop');

        if (!$shop) {
            abort(400, 'Missing shop parameter');
        }

        $host = $request->query('host'); // Important for embedded app

        $apiKey = config('services.shopify_api_key');
        $scopes = config('services.shopify_api_scopes'); // adjust scopes as needed
        $redirectUri = urlencode(route('shopify.callback'));

        $state = Str::random(32);

        $installUrl = "https://{$shop}/admin/oauth/authorize"
            . "?client_id={$apiKey}"
            . "&scope={$scopes}"
            . "&redirect_uri={$redirectUri}"
            . "&state=nonce123";

        if (request('session') && request('id_token')) {
            // Step 2: Redirect via Shopify's /exitiframe logic
            $exitIframeRedirect = urlencode($installUrl);

            $shopRecord = Shop::with('shopUser')->where('shopify_domain', $shop)->first(); 
            if($shopRecord && ($shopRecord->shopUser && $shopRecord->shopUser->shopify_session_token)){
                return view('embedded.exitiframe', [
                    'redirectUrl' => $exitIframeRedirect,
                    'host' => $host,
                    'shop' => $shop
                ]);
            }
            else{   
                return view('embedded.login',  [
                    'host' => $host,
                    'shop' => $shop
                ]);
            }
        } else {
            return redirect()->away($installUrl);
        }
    }


    public function callback(Request $request)
    {
        $hmac = $request->query('hmac');
        $shop = $request->query('shop');
        $code = $request->query('code');
        $apiVersion = config('services.shopify_api_version');

        $query = $request->query();
        unset($query['hmac']);
        ksort($query);


        $computedHmac = hash_hmac('sha256', http_build_query($query), config('services.shopify_api_secret'));

        if (!hash_equals($hmac, $computedHmac)) {
            abort(400, 'HMAC verification failed');
        }

        $response = Http::withOptions([
            'verify' => true
        ])->asForm()->post("https://{$shop}/admin/oauth/access_token", [
                    'client_id' => config('services.shopify_api_key'),
                    'client_secret' => config('services.shopify_api_secret'),
                    'code' => $code,
                    'expiring' => 1
                ]);

        if (!$response->successful()) {
            abort(400, 'Failed to get access token from Shopify');
        }

        //$accessToken = $response->json()['access_token'];

        $data = $response->json();

        // access_token, scope, expires_in, refresh_token, refresh_token_expires_in
        $accessToken = $data['access_token'] ?? null;
        $scopesString = $data['scope'] ?? '';
        $expiresIn = $data['expires_in'] ?? null;
        $refreshToken = $data['refresh_token'] ?? null;
        $refreshTokenExpiresIn = $data['refresh_token_expires_in'] ?? null;

        if (!$accessToken || !$refreshToken || !$expiresIn || !$refreshTokenExpiresIn) {
            abort(500, 'Unexpected token response: missing fields.');
        }

        $scopes = $scopesString
            ? explode(',', $scopesString)
            : [];

        // Calculate expiry times
        $accessTokenExpiresAt = \Carbon\Carbon::now()->addSeconds($expiresIn);
        $refreshTokenExpiresAt = \Carbon\Carbon::now()->addSeconds($refreshTokenExpiresIn);
        $shopResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shop}/admin/api/{$apiVersion}/shop.json");

        if (!$shopResponse->successful()) {
            abort(400, 'Failed to fetch shop details from Shopify');
        }

        $shopData = $shopResponse->json()['shop'];

        $shopRecord = [
            'shopify_domain' => $shop,
            'shopify_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'scopes' => $scopes,
            'shop_name' => $shopData['name'] ?? null,
            'shop_email' => $shopData['email'] ?? null,
            'shop_owner' => $shopData['shop_owner'] ?? null,
            'is_active' => 1,
            'host' => $request->query('host'),
            'created_at' => now(),
            'updated_at' => now()
        ];

        Shop::updateOrInsert(
            ['shopify_domain' => $shop],  // unique key
            $shopRecord
        );

        $webhooks = [
            [
                'topic' => 'orders/create',
                'address' => url('/shopify-webhook/orders-create'),
                'format' => 'json',
            ],
            [
                'topic' => 'orders/cancelled',
                'address' => url('shopify-webhook/orders-cancelled'),
                'format' => 'json'
            ],
            [
                'topic' => 'orders/updated',
                'address' => url('shopify-webhook/orders-updated'),
                'format' => 'json'
            ],
            [
                'topic' => 'orders/fulfilled',
                'address' => url('shopify-webhook/orders-updated'),
                'format' => 'json'
            ],
            [
                'topic' => 'app/uninstalled',
                'address' => url('/webhook/app-uninstalled'),
                'format' => 'json',
            ]
        ];

        foreach ($webhooks as $webhook) {
            $response = Http::withOptions([
                'verify' => true
            ])->withHeaders([
                        'X-Shopify-Access-Token' => $accessToken,
                    ])->post("https://{$shop}/admin/api/{$apiVersion}/webhooks.json", [
                        'webhook' => $webhook,
                    ]);

            if ($response->successful()) {
            } else {
                Log::info($response->body());
            }
        }

        $shopModel = Shop::where('shopify_domain', $shop)->first();

        if ($shopModel->is_charge == 1) {
            return redirect('/shopify-billing/callback?shop=' . $shop . '&host=' . $request->query('host'));
        }

        $shopify_domain = $shop;
        $shopify_host = $request->query('host');
        return redirect()->route('shopify.embedded.login', ['shop' => $shopify_domain, 'host' => $shopify_host]);
    }

    public function customersDataRequest(Request $request)
    {
        Log::info('GDPR customers/data_request', $request->all());
        $this->verifyWebhook($request);
        return response('OK', 200);
    }

    public function customersRedact(Request $request)
    {
        Log::info('GDPR customers/redact', $request->all());
        $this->verifyWebhook($request);
        return response('OK', 200);
    }

    public function shopRedact(Request $request)
    {
        Log::info('GDPR shop/redact', $request->all());
        $this->verifyWebhook($request);
        return response('OK', 200);
    }

    protected function verifyWebhook(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $data, config('services.shopify_api_secret'), true)
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            abort(401, 'Invalid webhook signature');
        }
    }

    public function shopUninstalled(Request $request)
    {
        \Log::info('GDPR shop/Uninstall', $request->all());
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        AppUninstalledJob::dispatch($shopDomain);
        return response('OK', 200);
    }

    public function orderCreate(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), config('services.shopify_api_secret'), true)
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('Invalid HMAC for orders/create webhook');
            return response('Unauthorized', 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        if (!$shopDomain) {
            Log::warning('Webhook missing X-Shopify-Shop-Domain header');
            return response('Bad Request', 400);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop) {
            Log::warning("Received webhook for unknown shop: $shopDomain");
            return response('Shop not found', 404);
        }

        $user = User::where('shop', $shopDomain)->first();

        if ($user && ($user->status == 0 || $user->kyc_status == 0)) {
            return response('User is inactive', 200);
        }

        if (($user->shopify_sync_mode ?? 'automatic') === "manual") {
            return redirect('/apps/home?shop=' . $request->input('shop') . '&host=' . $request->query('host'));
        }
        $orderData = $request->all();
        Log::info("Order Created for {$shopDomain}", $orderData);

        $shopifyOrderId = $orderData['id'];
        $shopifyOrderNumber = $orderData['order_number'] ?? $orderData['id'];

        if (Order::where('shopify_order_number', $shopifyOrderId)->exists()) {
            return response('Duplicate shopify order id', 404);
        }

        DB::beginTransaction();
        try {
            $user_id = $user->id;

            // Step 1: Get phone from either place
            $customerMobile = '';
            if (isset($orderData['customer']['phone']) && !empty($orderData['customer']['phone']) && $orderData['customer']['phone'] != "null") {
                $customerMobile = $orderData['customer']['phone'];
            } elseif (isset($orderData['customer']['default_address']['phone']) && !empty($orderData['customer']['default_address']['phone']) && $orderData['customer']['default_address']['phone'] != "null") {
                $customerMobile = $orderData['customer']['default_address']['phone'];
            } elseif (isset($orderData['billing_address']['phone']) && !empty($orderData['billing_address']['phone']) && $orderData['billing_address']['phone'] != "null") {
                $customerMobile = $orderData['billing_address']['phone'];
            } elseif (isset($orderData['shipping_address']['phone']) && !empty($orderData['shipping_address']['phone']) && $orderData['shipping_address']['phone'] != "null") {
                $customerMobile = $orderData['shipping_address']['phone'];
            }

            // Step 2: Normalize
            $customerMobile = preg_replace('/^\+?91/', '', $customerMobile); // remove +91 or 91 from start
            $customerMobile = preg_replace('/\D/', '', $customerMobile);     // keep only digits

            // Step 3: Validate length (India mobile numbers should be 10 digits)
            if (strlen($customerMobile) !== 10) {
                $customerMobile = '';
            }

            $customer = Customer::create([
                'user_id' => $user_id,
                'first_name' => $orderData['customer']['first_name'] ?? '',
                'last_name' => $orderData['customer']['last_name'] ?? '',
                'email' => $orderData['customer']['email'] ?? '',
                'mobile' => $customerMobile,
                'status' => 1,
            ]);

            $addressData = $orderData['customer']['default_address'] ?? null;
            $customerAddress = null;
            if ($addressData) {
                $address1 = $addressData['address1'] ?? '';
                $address2 = $addressData['address2'] ?? '';
                $fullAddress = trim("$address1 $address2");

                $customerAddress = CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address' => $fullAddress,
                    'country' => $addressData['country_name'] ?? 'India',
                    'state' => $addressData['province'] ?? '',
                    'city' => $addressData['city'] ?? '',
                    'zip_code' => $addressData['zip'] ?? '',
                    'status' => 1,
                ]);
            }

            $gateways = array_map('strtolower', $orderData['payment_gateway_names'] ?? []);
            $isCod = false;
            foreach ($gateways as $gateway) {
                if (strpos($gateway, 'cash on delivery') !== false) {
                    $isCod = true;
                    break;
                }
            }

            if (!$isCod && empty($gateways)) {
                if ($orderData['financial_status'] === "pending") {
                    $isCod = true;
                }
            }

            $totalAmount = $orderData['total_price'] ?? 0;
            $codAmount = $orderData['total_outstanding'] ?? 0;
            $totalWeightInGrams = $orderData['total_weight'] ?? 0;
            $totalShippingCost = $orderData['total_shipping_price_set']['shop_money']['amount'] ?? 0;
            $tags = array_values(array_filter(array_map('trim', explode(',', $orderData['tags'] ?? ''))));

            $totalWeight = $totalWeightInGrams > 0 ? ($totalWeightInGrams / 1000) : 0;
            $warehouse = CourierWarehouse::where('user_id', $user_id)->where('warehouse_status', 1)->first();
            $weightDimension = null;
            if ($totalWeightInGrams > 0) {
                $weightDimension = WeightDimension::where('slab', '>=', $totalWeight)->orderBy('slab', 'asc')->first();
            }

            // Prepare Order Data
            $requestData = [];
            $requestData['user_id'] = $user_id;
            $requestData['customer_id'] = $customer->id ?? null;
            $requestData['customer_address_id'] = $customerAddress->id ?? null;
            $requestData['warehouse_id'] = $warehouse->id ?? null;
            $requestData['order_prefix'] = Order::generateOrderNumber($user_id);

            // Old Shopify columns (for backward compatibility)
            $requestData['shopify_order_number'] = $shopifyOrderId;
            $requestData['shopify_order_id'] = $shopifyOrderNumber;
            $requestData['shopify_order_name'] = $orderData['name'] ?? null;
            $requestData['is_shopify_order'] = 1;
            $requestData['shopify_tags'] = $tags;

            // New platform columns (for future compatibility)
            $requestData['platform_source'] = 'shopify';
            $requestData['is_platform_order'] = 1;
            $requestData['platform_order_number'] = $shopifyOrderId;
            $requestData['platform_order_id'] = $shopifyOrderNumber;
            $requestData['platform_order_name'] = $orderData['name'] ?? null;
            $requestData['platform_tags'] = $tags;

            $requestData['order_type'] = $isCod ? 'cod' : 'prepaid';
            $requestData['cod_amount'] = $isCod ? $codAmount : 0;
            $requestData['weight_order'] = 1;
            $requestData['shipping_mode'] = 'Surface';
            $requestData['order_date'] = now()->toDateString();
            $requestData['status_courier'] = 'New';
            $requestData['status'] = 1;
            $requestData['total_amount'] = $totalAmount;
            $requestData['invoice_amount'] = $totalAmount;
            $requestData['total_shipping_cost'] = $totalShippingCost;
            $requestData['length'] = $weightDimension ? $weightDimension->length : 0;
            $requestData['width'] = $weightDimension ? $weightDimension->width : 0;
            $requestData['height'] = $weightDimension ? $weightDimension->height : 0;
            $requestData['weight'] = $weightDimension ? $weightDimension->slab : $totalWeight;

            // Create Order
            $order = Order::create($requestData);

            // Add Order Items
            $itemDatas = $orderData['line_items'] ?? [];

            if (count($itemDatas) > 0) {
                $gstAmount = 0;
                foreach ($itemDatas as $key => $itemData) {
                    $gstData = [];
                    foreach ($itemData['tax_lines'] as $txLine) {
                        $gstData[] = [
                            'price' => $txLine['price'],
                            'rate' => $txLine['rate'],
                            'title' => $txLine['title']
                        ];
                        $gstAmount += $txLine['price'];
                    }
                    $quantity = $itemData['quantity'] ?? 0;

                    $productImage = $this->getProductByShopify($itemData, $shop);

                    $orderItems = [
                        'order_id' => $order->id,
                        'product_category' => $itemData['name'] ?? null,
                        'product_name' => $itemData['name'] ?? null,
                        'sku_number' => $itemData['sku'] ?? null,
                        'hsn_number' => null,
                        'amount' => $itemData['price'] ?? 0,
                        'ewaybillno' => null,
                        'product_image' => $productImage,
                        'quantity' => $quantity,
                        'discount' => $itemData['total_discount'] ?? 0,
                        'gst' => $gstData ?? [],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    OrderItem::create($orderItems);
                }

                $order->update(['total_gst_amount' => $gstAmount]);
            }

            // Create Order Status
            OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => 'New',
            ]);

            // Track Order Activity
            Helper::orderActivity($order->id, 'Order created from Shopify.');

            DB::commit();

            if (!empty($customerMobile)) {
                $this->sendWhatsAppMessage($order, $customerMobile);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Exception when storing order from Shopify", [
                'error' => $e->getMessage()
            ]);
        }

        return response('OK', 200);
    }

    public function sendWhatsAppMessage($order, $customerMobile)
    {
        if (!$order) {
            return;
        }
        $customer = $order->customer ?? null;
        $shop = $order->user->shopDetail ?? null;
        $customer_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Valued Customer';
        $productName = $order->orderItems->pluck('product_name')->implode(', ');

        $productImage = $order->orderItems->whereNotNull('product_image')->first();
        $productImage = $productImage ? $productImage->product_image : null;
        if (!$productImage) {
            return;
        }

        $data = [
            "phone" => "91" . $customerMobile,
            "template_name" => "order_confirmation_summary",
            "template_language" => "en",
            "components" => [
                [
                    "type" => "header",
                    "parameters" => [
                        [
                            "type" => "image",
                            "image" => [
                                "link" => $productImage
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "BODY",
                    "parameters" => [
                        ["type" => "text", "text" => $customer_name],
                        ["type" => "text", "text" => $shop->shop_name ?? 'N/A'],
                        ["type" => "text", "text" => $order->shopify_order_id],
                        ["type" => "text", "text" => $productName],
                    ]
                ]
            ]
        ];

        $payload = array_merge([
            "token" => $this->settings->api_key
        ], $data);

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->post($this->settings->api_url . '/api/wpbox/sendtemplatemessage', $payload);

            if ($response->successful()) {
                // Log::info("✅ Message sent successfully to unregistered user", [
                // 	'payload' => $data,
                // 	'response' => $response->json(),
                // ]);
                return 1;
            } else {
                Log::warning("⚠️ SendMessage API call returned error", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return 0;
            }
        } catch (\Exception $e) {
            Log::error("❗ Exception while sending unregistered message", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function getProductByShopify($itemData, $shop)
    {
        try {
            $productId = $itemData['product_id'] ?? null;

            if (!$productId || !$shop?->shopify_domain) {
                return null; // invalid data
            }

            $shopifyToken = $shop->getValidShopifyToken();

            $apiVersion = config('services.shopify_api_version');

            $url = "https://{$shop->shopify_domain}/admin/api/{$apiVersion}/products/{$productId}.json";

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shopifyToken,
            ])->timeout(15)->get($url);

            // check for HTTP success
            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return $data['product']['image']['src'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function orderCancelled(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), config('services.shopify_api_secret'), true)
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('❌ Invalid Shopify webhook signature', ['header' => $hmacHeader]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), config('services.shopify_api_secret'), true)
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('Invalid HMAC for orders/create webhook');
            return response('Unauthorized', 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        if (!$shopDomain) {
            Log::warning('Webhook missing X-Shopify-Shop-Domain header');
            return response('Bad Request', 400);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop) {
            Log::warning("Received webhook for unknown shop: $shopDomain");
            return response('Shop not found', 404);
        }

        $payload = $request->all();
        $shopifyOrderId = $payload['id'] ?? null;

        // Update your DB
        if (!$shopifyOrderId) {
            return response('ok', 200);
        }

        $order = Order::where('shopify_order_number', $shopifyOrderId)->first();

        if (!$order) {
            return response('ok', 200);
        }

        // case 1: order not shipped yet
        if (empty($order->shipping_company_id)) {
            $order->update([
                'status_courier' => 'cancelled',
                'reason_cancel' => $payload['cancel_reason'] ?? 'Shopify through cancelled order',
                'order_cancel_date' => now()->toDateString(),
            ]);

            OrderStatus::insert(['order_id' => $order->id, 'order_status' => 'cancelled', 'created_at' => now(), 'updated_at' => now()]);

            Helper::orderActivity($order->id, 'Order cancelled.');
            return response('ok', 200);
        }

        // // case 2: order has shipping company but still pending
        // if ($order->status_courier !== "manifested") {
        //     return response('ok', 200);
        // }

        // // already cancelled
        // if (in_array($order->status_courier, ["cancelled", "seller cancelled"])) {
        //     return response('ok', 200);
        // }

        // $shippingCompany = $order->shippingCompany;
        // $user = $order->user;
        // if (!$shippingCompany || !$user) {
        //     return response('ok', 200);
        // }

        // // cancel with courier
        // $orderCancelledData = $this->cancelCourierOrder($order, $shippingCompany);

        // if (!$orderCancelledData) {
        //     return response()->json(['status' => 'error', 'msg' => 'Courier cancel failed']);
        // }

        // // update order
        // $order->update($orderCancelledData);

        // // refund wallet if normal user
        // if ($user->role === 'user') {
        //     $user->increment('wallet_amount', $order->shipping_charge);

        //     Billing::create([
        //         'user_id' => $order->user_id,
        //         'billing_type' => 'Order',
        //         'billing_type_id' => $order->id,
        //         'transaction_type' => 'credit',
        //         'amount' => $order->shipping_charge,
        //         'note' => 'Order cancelled (AWB: ' . $order->awb_number . ')',
        //     ]);
        // }


        // OrderStatus::insert(['order_id' => $order->id, 'order_status' => 'cancelled', 'created_at' => now(), 'updated_at' => now()]);

        // Helper::orderActivity($order->id, 'Order cancelled with AWB: ' . $order->awb_number);

        return response('ok', 200);
    }

    public function orderUpdated(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), config('services.shopify_api_secret'), true)
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('Invalid HMAC for orders/updated webhook');
            return response('Unauthorized', 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $payload = $request->all();
        $shopifyOrderId = $payload['id'] ?? null;
        $fulfillmentStatus = $payload['fulfillment_status'] ?? null;
        Log::info('Order fulfilled webhook received', $payload);

        if (!$shopifyOrderId) {
            return response('ok', 200);
        }

        $order = Order::where('shopify_order_number', $shopifyOrderId)->first();

        if ($order) {
            if ($fulfillmentStatus === 'fulfilled' && strtolower($order->status_courier) === "new") {
                $order->delete();
                $order->orderItems()->delete();
                $order->orderStatus()->delete();
                $order->orderActivities()->delete();
            }
        }

        return response('ok', 200);
    }

    private function cancelCourierOrder($order, $shippingCompany)
    {
        $date = now()->toDateString();

        switch ($shippingCompany->id) {
            case 1:
                $response = $this->delhiveryB2CService->cancelShipmentByAwbNumber($order->awb_number, $shippingCompany);
                if (!($response['success'] ?? false))
                    return null;
                break;

            default:
                return null;
        }

        return [
            'status_courier' => 'cancelled',
            'order_cancel_date' => $date,
            'reason_cancel' => "AWB {$order->awb_number} cancelled by courier",
        ];
    }


    public function billingCallback(Request $request)
    {
        $shop = $request->get('shop');

        $shopDetail = Shop::where('shopify_domain', $shop)->first();

        $shopDetail->update([
            'is_charge' => 1
        ]);

        $host = $shopDetail->host;

        return redirect()->to('apps/home?shop=' . $shop . '&host=' . $host);
    }

    public function orderManualFulfill(Request $request)
    {
        // Shopify Admin Link / Bulk Action passes selected order IDs
        $orderIds = $request->input('ids', []); // array of Shopify order IDs
        $orderId = $request->input('id', '');
        $shopDomain = $request->input('shop'); // e.g., myshop.myshopify.com

        if (!$shopDomain) {
            return response('Bad Request', 400);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop) {
            return response('Shop not found', 404);
        }

        $user = User::where('shop', $shopDomain)->first();
        if (empty($orderIds) && !empty($orderId) && !empty($shopDomain)) {
            $orderIds[] = $orderId;
        }

        if (!$user || empty($orderIds)) {
            return response('No orders selected or user not found', 400);
        }

        if ($user && ($user->status == 0 || $user->kyc_status == 0)) {
            return response('User is inactive', 200);
        }

        // if (($user->shopify_sync_mode ?? 'automatic') === "automatic") {
        //     return redirect('/apps/home?shop=' . $request->input('shop') . '&host=' . $request->query('host'));
        // }
        $apiVersion = config('services.shopify_api_version');
        $mainOrderids = [];
        foreach ($orderIds as $shopifyOrderId) {
            $existingOrder = Order::select('id')->where('shopify_order_number', $shopifyOrderId)->first();

            if ($existingOrder) {
                $mainOrderids[] = $existingOrder->id;
                continue;
            }

            try {
                // Fetch the order details from Shopify Admin API
                $shopifyToken = $shop->getValidShopifyToken();

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $shopifyToken,
                    'Content-Type' => 'application/json',
                ])->get("https://{$shopDomain}/admin/api/{$apiVersion}/orders/{$shopifyOrderId}.json");

                if ($response->failed()) {
                    Log::warning("Failed to fetch Shopify order: $shopifyOrderId", $response->json());
                    continue;
                }

                $orderData = $response->json()['order'] ?? null;
                Log::info($orderData);
                if (!$orderData) {
                    continue;
                }

                DB::beginTransaction();
                $user_id = $user->id;

                // Step 1: Get phone from either place
                $customerMobile = '';
                if (isset($orderData['customer']['phone']) && !empty($orderData['customer']['phone']) && $orderData['customer']['phone'] != "null") {
                    $customerMobile = $orderData['customer']['phone'];
                } elseif (isset($orderData['customer']['default_address']['phone']) && !empty($orderData['customer']['default_address']['phone']) && $orderData['customer']['default_address']['phone'] != "null") {
                    $customerMobile = $orderData['customer']['default_address']['phone'];
                } elseif (isset($orderData['billing_address']['phone']) && !empty($orderData['billing_address']['phone']) && $orderData['billing_address']['phone'] != "null") {
                    $customerMobile = $orderData['billing_address']['phone'];
                } elseif (isset($orderData['shipping_address']['phone']) && !empty($orderData['shipping_address']['phone']) && $orderData['shipping_address']['phone'] != "null") {
                    $customerMobile = $orderData['shipping_address']['phone'];
                }

                // Step 2: Normalize
                $customerMobile = preg_replace('/^\+?91/', '', $customerMobile); // remove +91 or 91 from start
                $customerMobile = preg_replace('/\D/', '', $customerMobile);     // keep only digits

                // Step 3: Validate length (India mobile numbers should be 10 digits)
                if (strlen($customerMobile) !== 10) {
                    $customerMobile = '';
                }

                $customer = Customer::create([
                    'user_id' => $user_id,
                    'first_name' => $orderData['customer']['first_name'] ?? '',
                    'last_name' => $orderData['customer']['last_name'] ?? '',
                    'email' => $orderData['customer']['email'] ?? '',
                    'mobile' => $customerMobile,
                    'status' => 1,
                ]);

                $addressData = $orderData['customer']['default_address'] ?? null;
                $customerAddress = null;
                if ($addressData) {
                    $address1 = $addressData['address1'] ?? '';
                    $address2 = $addressData['address2'] ?? '';
                    $fullAddress = trim("$address1 $address2");

                    $customerAddress = CustomerAddress::create([
                        'customer_id' => $customer->id,
                        'address' => $fullAddress,
                        'country' => $addressData['country_name'] ?? 'India',
                        'state' => $addressData['province'] ?? '',
                        'city' => $addressData['city'] ?? '',
                        'zip_code' => $addressData['zip'] ?? '',
                        'status' => 1,
                    ]);
                }

                $gateways = array_map('strtolower', $orderData['payment_gateway_names'] ?? []);
                $isCod = false;
                foreach ($gateways as $gateway) {
                    if (strpos($gateway, 'cash on delivery') !== false) {
                        $isCod = true;
                        break;
                    }
                }

                if (!$isCod && empty($gateways)) {
                    if ($orderData['financial_status'] === "pending") {
                        $isCod = true;
                    }
                }

                $totalAmount = $orderData['total_price'] ?? 0;
                $codAmount = $orderData['total_outstanding'] ?? 0;
                $totalWeightInGrams = $orderData['total_weight'] ?? 0;
                $totalShippingCost = $orderData['total_shipping_price_set']['shop_money']['amount'] ?? 0;
                $tags = array_values(array_filter(array_map('trim', explode(',', $orderData['tags'] ?? ''))));

                $totalWeight = $totalWeightInGrams > 0 ? ($totalWeightInGrams / 1000) : 0;
                $warehouse = CourierWarehouse::where('user_id', $user_id)->where('warehouse_status', 1)->first();

                $weightDimension = null;
                if ($totalWeightInGrams > 0) {
                    $weightDimension = WeightDimension::where('slab', '>=', $totalWeight)->orderBy('slab', 'asc')->first();
                }

                $orderCreate = [
                    'user_id' => $user->id,
                    'customer_id' => $customer->id ?? null,
                    'customer_address_id' => $customerAddress->id ?? null,
                    'warehouse_id' => $warehouse->id ?? null,
                    'order_prefix' => Order::generateOrderNumber($user->id),
                    'shopify_order_number' => $shopifyOrderId,
                    'shopify_order_id' => $orderData['order_number'] ?? $shopifyOrderId,
                    'shopify_order_name' => $orderData['name'] ?? null,
                    'is_shopify_order' => 1,
                    'shopify_tags' => $tags,

                    // New platform columns (for future compatibility)
                    'platform_source' => 'shopify',
                    'is_platform_order' => 1,
                    'platform_order_number' => $shopifyOrderId,
                    'platform_order_id' => $orderData['order_number'] ?? $shopifyOrderId,
                    'platform_order_name' => $orderData['name'] ?? null,
                    'platform_tags' => $tags,
                    'order_type' => $isCod ? 'cod' : 'prepaid',
                    'cod_amount' => $isCod ? $codAmount : 0,
                    'weight_order' => 1,
                    'shipping_mode' => 'Surface',
                    'order_date' => now()->toDateString(),
                    'status_courier' => 'New',
                    'status' => 1,
                    'total_amount' => $totalAmount,
                    'invoice_amount' => $totalAmount,
                    'total_shipping_cost' => $totalShippingCost,
                    'length' => $weightDimension ? $weightDimension->length : 0,
                    'width' => $weightDimension ? $weightDimension->width : 0,
                    'height' => $weightDimension ? $weightDimension->height : 0,
                    'weight' => $weightDimension ? $weightDimension->slab : $totalWeight,
                ];

                $order = Order::create($orderCreate);

                $itemDatas = $orderData['line_items'] ?? [];

                if (count($itemDatas) > 0) {
                    $gstAmount = 0;
                    foreach ($itemDatas as $key => $itemData) {
                        $gstData = [];
                        foreach ($itemData['tax_lines'] as $txLine) {
                            $gstData[] = [
                                'price' => $txLine['price'],
                                'rate' => $txLine['rate'],
                                'title' => $txLine['title']
                            ];
                            $gstAmount += $txLine['price'];
                        }
                        $quantity = $itemData['quantity'] ?? 0;

                        $productImage = $this->getProductByShopify($itemData, $shop);

                        $orderItems = [
                            'order_id' => $order->id,
                            'product_category' => $itemData['name'] ?? null,
                            'product_name' => $itemData['name'] ?? null,
                            'sku_number' => $itemData['sku'] ?? null,
                            'hsn_number' => null,
                            'amount' => $itemData['price'] ?? 0,
                            'ewaybillno' => null,
                            'product_image' => $productImage,
                            'quantity' => $quantity,
                            'discount' => $itemData['total_discount'] ?? 0,
                            'gst' => $gstData ?? [],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        OrderItem::create($orderItems);
                    }

                    $order->update(['total_gst_amount' => $gstAmount]);
                }

                OrderStatus::create([
                    'order_id' => $order->id,
                    'order_status' => 'New',
                ]);

                // Track Order Activity
                Helper::orderActivity($order->id, 'Order created manually from Shopify Admin Link.');
                $mainOrderids[] = $order->id;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Manual Shopify order store failed for {$shopifyOrderId}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return redirect()->route('shopify.order.create', [
            'order_ids' => implode(',', $mainOrderids),
            'user_id' => $user->id,
            'shop' => $request->input('shop'),
            'host' => $request->input('host'),
        ]);
    }

    public function shopifyOrderCreate(Request $request)
    {
        $orderIds = explode(',', $request->query('order_ids', ''));

        $userId = $request->query('user_id');
        $shop = $request->query('shop');
        //$shopifyOrderUrl = "https://{$shop}/admin/orders?inContextTimeframe=last_7_days";

        if (empty($orderIds) || !$userId) {
            //return redirect()->away($shopifyOrderUrl);
            return redirect('/apps/home?shop=' . $request->input('shop') . '&host=' . $request->query('host'));
        }

        $warehouses = CourierWarehouse::where('user_id', $userId)->where('warehouse_status', 1)->get();

        $shipments = ShippingCompany::select('id', 'name')
            ->where('status', 1)
            ->get();

        return view('embedded.order-create', compact('orderIds', 'userId', 'warehouses', 'shipments'));
    }

    public function orderShopifyBulkShipNow(Request $request)
    {
        $orderIds = $request->order_ids ? explode(',', $request->order_ids) : [];
        // ✅ Validation (existing behavior)
        if (empty($orderIds)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'No orders selected. Please select at least one order to continue.',
            ]);
        }

        try {

            DB::beginTransaction();

            // ✅ Fetch user safely
            $user = User::find($request->user_id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Invalid user. Please try again.',
                ]);
            }

            $data = $request->all();
            $data['is_shopify'] = null;

            // ✅ Dispatch job to queue
            ProcessBulkShipment::dispatch($orderIds, $data, $user);

            // ✅ Update orders queue flag
            Order::whereIn('id', $orderIds)
                ->update(['queue' => 1, 'shipment_failure_reason' => null]);

            DB::commit();

            $companyName = config('setting.company_name');

            return response()->json([
                'status' => 'success',
                'msg' => "Bulk shipment queued successfully. Processing will continue in the background — check the {$companyName} panel for status.",
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'msg' => 'Something went wrong while processing bulk shipment. Please try again.',
            ]);
        }
    }

    public function updateSync(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:automatic,manual',
        ]);

        $user = User::where('shop', $request->shop)->first();
        $user->update(['shopify_sync_mode' => $request->mode]);

        return response()->json(['status' => 'success', 'mode' => $request->mode]);
    }

    public function getSyncMode(Request $request)
    {
        $shopDomain = $request->query('shop'); // get shop param

        $shop = User::where('shop', $shopDomain)->first();
        if (!$shop) {
            return response()->json(['status' => 'error', 'mode' => 'automatic'], 404);
        }

        return response()->json([
            'status' => 'success',
            'mode' => $shop->shopify_sync_mode ?? 'automatic'
        ]);
    }

    public function getChargeGraph($shop, $host)
    {
        $url = route('shopify.billing.confirm', ['shop' => $shop, 'host' => $host]);

        $test = true;

        // Use double quotes for HEREDOC and correctly escape variables
        $query = <<<"GRAPHQL"
            mutation {
              appSubscriptionCreate(
                name: "Free Plan"
                returnUrl: "{$url}"
                test: {$test}
                lineItems: [{
                  plan: {
                    appRecurringPricingDetails: {
                      price: { amount: 0.0, currencyCode: USD }
                    }
                  }
                }]
              ) {
                confirmationUrl
                userErrors {
                  field
                  message
                }
              }
            }
            GRAPHQL;

        return $query;
    }

    /**
     * Display Shopify orders list page (for Ship Now button)
     */
    public function shopifyOrdersList(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');

        if (!$shop) {
            return response('Shop parameter is missing', 400);
        }

        return view('embedded.shopify-orders', compact('shop', 'host'));
    }

    /**
     * Fetch orders from Shopify API
     */
    public function getShopifyOrders(Request $request)
    {
        $shopDomain = $request->query('shop');

        if (!$shopDomain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop parameter is missing'
            ], 400);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop not found'
            ], 404);
        }

        try {
            $apiVersion = config('services.shopify_api_version');
            $shopifyToken = $shop->getValidShopifyToken();

            // Fetch orders from Shopify sorted by created_at desc
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shopifyToken,
                'Content-Type' => 'application/json',
            ])->get("https://{$shopDomain}/admin/api/{$apiVersion}/orders.json", [
                        'status' => 'any',
                        'limit' => 250,
                        'order' => 'created_at desc',
                        'fields' => 'id,order_number,name,created_at,updated_at,total_price,currency,financial_status,fulfillment_status,customer'
                    ]);

            if ($response->failed()) {
                Log::warning("Failed to fetch Shopify orders for {$shopDomain}", $response->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch orders from Shopify'
                ], 500);
            }

            $orders = $response->json()['orders'] ?? [];

            // Sort orders by order_number in descending order
            usort($orders, function ($a, $b) {
                return $b['order_number'] - $a['order_number'];
            });

            return response()->json([
                'status' => 'success',
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error("Exception while fetching Shopify orders", [
                'shop' => $shopDomain,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching orders'
            ], 500);
        }
    }

    /**
     * Get internal order ID from Shopify order ID
     */
    public function getOrderIdFromShopify(Request $request)
    {
        $shopifyOrderId = $request->query('shopify_order_id');
        $shopDomain = $request->query('shop');

        if (!$shopifyOrderId || !$shopDomain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing required parameters'
            ], 400);
        }

        // Verify shop exists
        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop not found'
            ], 404);
        }

        // Find order by Shopify order ID
        $order = Order::where('shopify_order_number', $shopifyOrderId)
            ->select('id', 'order_prefix', 'awb_number', 'status_courier')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'order_id' => $order->id,
            'order_prefix' => $order->order_prefix,
            'awb_number' => $order->awb_number,
            'status_courier' => $order->status_courier
        ]);
    }

    // Embedded Authentication Methods
    public function showLogin(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');

        if (!$shop) {
            abort(400, 'Missing shop parameter');
        }

        return view('embedded.login', compact('shop', 'host'));
    }

    public function postLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'shop' => 'required',
        ]);

        // Fetch user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'msg' => 'These credentials do not match our records.'
            ]);
        }

        // Check active status for non-admin users
        if (!$user->isAdminLevel() && $user->status == 0) {
            return response()->json([
                'status' => 'error',
                'msg' => 'This user is not active. Please contact the admin.'
            ]);
        }

        // Attempt login
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $shop = $request->shop;
            $host = $request->host;

            // Check if shop is registered
            $registeredShop = Shop::where('shopify_domain', $shop)->first();

            if ($registeredShop) {
                // Generate unique session token
                $sessionToken = $this->generateSessionToken();

                // Update user's shop association and session token
                $user->update([
                    'shop' => $shop,
                    'shopify_session_token' => $sessionToken
                ]);

                Shop::where('shopify_domain', $shop)->update(['is_charge' => 1]);

                // Return success with redirect URL and token
                $redirectUrl = '/apps/home?shop=' . $shop . '&host=' . $host;
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Login successful!',
                    'redirect' => $redirectUrl,
                    'shopify_session_token' => $sessionToken
                ]);
            }

            return response()->json([
                'status' => 'error',
                'msg' => 'This Shopify store is not registered with your account.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'msg' => 'These credentials do not match our records.'
        ]);
    }

    public function showRegister(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');

        if (!$shop) {
            abort(400, 'Missing shop parameter');
        }

        return view('embedded.register', compact('shop', 'host'));
    }

    public function postRegister(Request $request)
    {
        // Generate OTP
        $generatedOTP = rand(100000, 999999); // 6-digit OTP

        try {
            $shop = $request->shop;
            $host = $request->host;

            if ($shop && $host) {
                $registeredShop = Shop::where('shopify_domain', $shop)->first();
                if (!$registeredShop) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Please uninstall our app from Shopify and reinstall it to register correctly.'
                    ]);
                }

                if (User::where("shop", $shop)->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'This Shopify store is already registered. Please contact the administrator for assistance.'
                    ]);
                }
            }

            if (User::where("email", $request->email)->exists()) {
                return response()->json(['status' => 'error', 'msg' => 'The email has already exists.']);
            }

            // if (User::where("mobile", $request->mobile)->exists()) {
            //     return response()->json(['status' => 'error', 'msg' => 'The mobile has already exists.']);
            // }

            if (!isset($request->is_verify_otp) && $request->is_verify_otp == 0) {
                Mail::to($request->email)->send(new OtpMail($request->name, $generatedOTP));
                return response()->json(['status' => 'mail', 'msg' => 'The mail send you have proveided email', 'otp' => $generatedOTP]);
            }

            DB::beginTransaction();

            // Calculate next seller_id
            $maxSellerId = User::max('seller_id');
            $nextSellerId = $maxSellerId ? $maxSellerId + 1 : 1001;
            $defaultCodPlan = CodSetting::where('is_active', true)
                ->where('is_default', true)
                ->first();

            // Create the user
            $user = User::create([
                'seller_id' => $nextSellerId,
                'company_name' => $request->company_name,
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'xpass' => $request->password,
                'password' => Hash::make($request->password),
                'status' => 1,
                'charge_type' => 1,
                'charge' => 0,
                'otp' => $generatedOTP,
                'address' => $request->address,
                'state' => $request->state,
                'city' => $request->city,
                'country' => $request->country,
                'zip_code' => $request->zip_code,
                'gender' => $request->gender,
                'cod_plan_id' => $defaultCodPlan ? $defaultCodPlan->id : null,
                'cod_plan_active' => $defaultCodPlan ? 1 : 0,
                'shop' => $shop,
                'api_token' => $this->generateUniqueToken(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create default label preferences
            LabelPreference::create([
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);


            Notification::create([
                'task_id' => $user->id,
                'type' => 'New User',
                'role' => 'admin',
                'text' => $request->name . ' is a new user who has successfully registered.',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Notification::create([
                'user_id' => $user->id,
                'task_id' => $user->id,
                'type' => 'New User',
                'role' => 'user',
                'text' => 'Your registration is now complete. Thank you.',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update shop to mark as charged
            Shop::where('shopify_domain', $shop)->update(['is_charge' => 1]);

            DB::commit();

            // Auto-login the user
            Auth::login($user);

            // Generate session token for the newly registered user
            $sessionToken = $this->generateSessionToken();
            $user->update(['shopify_session_token' => $sessionToken]);

            $redirectUrl = route('embedded.home', ['shop' => $shop, 'host' => $host]);

            return response()->json([
                'status' => 'success',
                'msg' => 'The registration has been successfully created.',
                'redirect' => $redirectUrl,
                'shopify_session_token' => $sessionToken
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'msg' => 'Failed to register. Please try again.' . $e->getMessage()]);
        }
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (User::where('api_token', $token)->exists());

        return $token;
    }

    private function generateSessionToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32)); // 64 character hex string
        } while (User::where('shopify_session_token', $token)->exists());

        return $token;
    }
}
