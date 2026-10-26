<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\WhatsappSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class WhatsappController extends Controller
{
    protected $settings;
    public function __construct()
    {
        error_reporting(0);
        $this->settings = WhatsappSetting::getSettings();
    }

    public function whatsappChatieonsWebhook(Request $request)
    {
        Log::info('Chatieons Webhook Payload: ' . json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $data = $request->all();
        //Log::info('Chatieons Payload', ['payload' => $data]);
        // 🔹 Validate structure
        if (empty($data['entry'][0]['changes'][0]['value']['messages'])) {
            Log::warning('⚠️ No messages found in webhook payload');
            return response()->json(['status' => 'no_message'], 200);
        }

        $messageData = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $messageId = $messageData['id'] ?? null;
        $timestamp = $messageData['timestamp'] ?? null;

        // 🔹 Skip if missing essentials
        if (!$messageId || !$timestamp) {
            Log::warning('⚠️ Missing message ID or timestamp', ['message' => $messageData]);
            return response()->json(['status' => 'invalid_payload'], 200);
        }

        // 🔹 Prevent duplicate processing (5 min cache)
        $cacheKey = 'msg_' . md5($messageId . '_' . $timestamp);
        if (Cache::has($cacheKey)) {
            Log::info("🔁 Duplicate webhook skipped: $messageId @ $timestamp");
            return response()->json(['status' => 'duplicate_skipped'], 200);
        }
        Cache::put($cacheKey, true, now()->addMinutes(5));

        // 🔹 Extract common data
        $fromNumber = $messageData['from'] ?? null;
        $type = $messageData['type'] ?? null;

        // 🔹 Initialize variables
        $body = null;
        $buttonId = null;
        $buttonTitle = null;

        // 🔹 Handle different message types
        switch ($type) {
            case 'text':
                $body = $messageData['text']['body'] ?? null;
                break;

            case 'interactive':
                $interactiveType = $messageData['interactive']['type'] ?? null;

                if ($interactiveType === 'button_reply') {
                    $buttonId = $messageData['interactive']['button_reply']['id'] ?? null;
                    $buttonTitle = $messageData['interactive']['button_reply']['title'] ?? null;
                    $body = $buttonTitle;
                }
                break;

            case 'button':
                $interactiveType = $messageData['button']['payload'] ?? null;
                $buttonId = $interactiveType ?? null;
                $buttonTitle = $messageData['button']['text'] ?? null;
                $body = $buttonTitle;
                break;

            default:
                Log::info("📭 Unsupported message type: {$type}");
                return response()->json(['status' => 'unsupported'], 200);
        }

        Log::info('📨 Parsed WhatsApp Message', [
            'from' => $fromNumber,
            'type' => $type,
            'body' => $body,
            'button_id' => $buttonId,
            'button_title' => $buttonTitle,
        ]);

        // 🔹 Handle business logic
        if (empty($body)) {
            Log::warning('⚠️ Empty message body', ['data' => $messageData]);
            return response()->json(['status' => 'empty_body'], 200);
        }

        // 🔹 Logic Routing
        $normalizedBody = strtolower(trim($body));

        if (in_array($normalizedBody, ['confirm my order'])) {
            $this->sendConfirmOrderThankMessage($fromNumber);
        } elseif (in_array($normalizedBody, ['cancel order'])) {
            $this->sendCancelOrderMessage($fromNumber);
        } elseif (in_array($normalizedBody, ['address is correct'])) {
            $this->sendAddressIsCorrectMessage($fromNumber, $buttonId);
        } elseif (in_array($normalizedBody, ['edit my address'])) {
            $this->sendEditAddressMessage($fromNumber, $buttonId);
        }

        return response()->json(['status' => 'processed'], 200);
    }

    public function sendAddressIsCorrectMessage($fromNumber, $buttonId)
    {
        $orderId = explode('_', $buttonId)[1] ?? null;

        if (!$orderId) {
            Log::warning("❌ Invalid buttonId: {$buttonId}");
            return;
        }

        $order = Order::with(['customer', 'customerAddress'])->find($orderId);

        if (!$order) {
            Log::warning("❌ No order found for WhatsApp number: {$fromNumber}, orderId: {$orderId}");
            return;
        }

        $customer = $order->customer;
        $customerAddress = $order->customerAddress;

        $customer_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Valued Customer';
        $address = $customerAddress->address ?? 'Address not available';

        $data = [
            "phone" => $fromNumber,
            "template_name" => "address_confirmation_thankyou",
            "template_language" => "en",
            "components" => [
                [
                    "type" => "BODY",
                    "parameters" => [
                        ["type" => "text", "text" => $customer_name],
                        ["type" => "text", "text" => $address]
                    ]
                ]
            ]
        ];

        // Send message
        $this->sendTemplateMessage($data);

        Log::info("✅ Address confirmation message sent for Order ID: {$orderId}");
    }

    public function sendEditAddressMessage($fromNumber, $buttonId)
    {
        $orderId = explode('_', $buttonId)[1] ?? null;

        if (!$orderId) {
            Log::warning("❌ Invalid buttonId: {$buttonId}");
            return;
        }

        $order = Order::with(['customer', 'customerAddress'])->find($orderId);

        if (!$order) {
            Log::warning("❌ No order found for WhatsApp number: {$fromNumber}, orderId: {$orderId}");
            return;
        }

        $customer = $order->customer;
        $customerAddress = $order->customerAddress;

        $customer_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Valued Customer';
        $address = $customerAddress->address ?? 'Address not available';
        $customerAddressId = $customerAddress->id ?? 0;

        // ✅ Encode data (safe way)
        $dataPayload = [
            'address' => $address,
            'customer_address_id' => $customerAddressId,
            'fromNumber' => $fromNumber,
            'customer_name' => $customer_name,
        ];
        // base64url so the value survives the query string unchanged
        $encoded = rtrim(strtr(base64_encode(json_encode($dataPayload)), '+/', '-_'), '=');

        // Signed link: the address id inside can't be swapped for someone else's
        $editLink = \Illuminate\Support\Facades\URL::temporarySignedRoute('address.edit', now()->addDays(7), ['data' => $encoded]);

        // ✅ WhatsApp message
        $message = "Address Confirmation! 🏠\n\n"
            . "Thank you, {$customer_name}! 🙏\n"
            . "We’ll deliver your order to the following address:\n\n"
            . "📍 *Delivery Address:*\n{$address}\n\n"
            . "Would you like to make any changes before shipping?\n"
            . "👉 Tap below to update your address:\n{$editLink}\n\n"
            . "A complete address with a nearby landmark ensures smooth delivery. 🚚";

        $data = [
            "phone" => $fromNumber,
            "message" => $message,
            "header" => "Customer Address Edit",
            "footer" => "Powered by Chatieons"
        ];

        $this->sendMessage($data);

        Log::info("✅ Edit Address message sent for Order ID: {$orderId}");
    }



    public function sendConfirmOrderThankMessage($fromNumber)
    {
        $order = $this->getLatestOrderByMobile($fromNumber);

        if (!$order) {
            Log::warning("No order found for WhatsApp number: {$fromNumber}");
            return;
        }

        $customer = $order->customer;
        $shop = $order->user->shopDetail ?? null;

        $customer_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Valued Customer';
        $order_id = $order->shopify_order_id;
        $store_name = $shop->shop_name ?? 'N/A';

        $data = [
            "phone" => $fromNumber,
            "template_name" => "order_confirmation_thankyou",
            "template_language" => "en",
            "components" => [
                [
                    "type" => "BODY",
                    "parameters" => [
                        ["type" => "text", "text" => $customer_name],
                        ["type" => "text", "text" => $order_id],
                        ["type" => "text", "text" => $store_name]
                    ]
                ]
            ]
        ];

        $order->update(['confirm_shopify_order' => 1]);
        $result = $this->sendTemplateMessage($data);
        if ($result == 1) {
            $this->sendConfirmOrderMessage($fromNumber, $order);
        }
    }


    public function sendConfirmOrderMessage($fromNumber, $order)
    {
        $customer = $order->customer;
        $customer_name = $customer->first_name . ' ' . $customer->last_name;
        $delivery_address = $order->customerAddress->address ?? 'N/A';

        $message = "Address Confirmation! 🏠\n\n"
            . "Thank you, {$customer_name}! 🙏\n"
            . "We’ll deliver your order to the following address:\n\n"
            . "📍 *Delivery Address:*\n{$delivery_address}\n\n"
            . "Would you like to make any changes before shipping?\n"
            . "A complete address with a nearby landmark ensures smooth delivery. 🚚";


        $data = [
            "phone" => $fromNumber,
            "message" => $message,
            "header" => "Order Adress Confirmation",
            "footer" => "Powered by Chatieons",
            "buttons" => [
                [
                    "id" => "addresscorrect_{$order->id}",
                    "title" => "Address is Correct"
                ],
                [
                    "id" => "editaddress_{$order->id}",
                    "title" => "Edit My Address"
                ]
            ]
        ];

        $this->sendMessage($data);
    }

    public function sendCancelOrderMessage($fromNumber)
    {
        $order = $this->getLatestOrderByMobile($fromNumber);

        if (!$order) {
            Log::warning("No order found for WhatsApp number: {$fromNumber}");
            return;
        }

        $customer = $order->customer;
        $productName = $order->orderItems->pluck('product_name')->implode(', ');

        $data = [
            "phone" => $fromNumber,
            "template_name" => "order_cancellation_template",
            "template_language" => "en",
            "components" => [
                [
                    "type" => "BODY",
                    "parameters" => [
                        ["type" => "text", "text" => $customer->first_name . ' ' . $customer->last_name],
                        ["type" => "text", "text" => $order->shopify_order_id],
                        ["type" => "text", "text" => $productName],
                        ["type" => "text", "text" => $order->total_amount],
                        ["type" => "text", "text" => 5]
                    ]
                ]
            ]
        ];

        $order->update(['confirm_shopify_order' => 2]);
        $this->sendTemplateMessage($data);
    }

    public function getLatestOrderByMobile($mobile)
    {
        // Clean all non-digit characters (e.g., +, spaces, hyphens)
        $mobile = preg_replace('/\D/', '', $mobile);

        // If mobile starts with multiple 91s (e.g., 91917507642090)
        while (strpos($mobile, '91') === 0 && strlen($mobile) > 10) {
            $mobile = substr($mobile, 2);
        }

        // If starts with 0, remove it
        if (substr($mobile, 0, 1) === '0' && strlen($mobile) > 10) {
            $mobile = substr($mobile, 1);
        }

        $order = Order::with(['user.shopDetail', 'customer', 'orderItems', 'customerAddress'])->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->where('customers.mobile', $mobile)
            ->select('orders.*')
            ->latest('orders.created_at')
            ->first();

        return $order;
    }

    public function sendTemplateMessage($data)
    {
        $payload = array_merge(
            ['token' => $this->settings->api_key],
            $data
        );

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

    public function sendMessage($data)
    {
        $payload = array_merge([
            "token" => $this->settings->api_key
        ], $data);

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->post($this->settings->api_url . '/api/wpbox/sendmessage', $payload);

            if ($response->successful()) {
                // Log::info("✅ Message sent successfully to unregistered user", [
                // 	'payload' => $data,
                // 	'response' => $response->json(),
                // ]);
            } else {
                Log::warning("⚠️ SendMessage API call returned error", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❗ Exception while sending unregistered message", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
