<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use App\Helpers\Helper;

class WoocommerceController extends Controller
{
    public function orderCreated(Request $request)
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            $payload = $request->all();

            Log::info('WooCommerce order created', [
                'payload' => \Illuminate\Support\Arr::except($payload, ['api_key']),
            ]);

            // Extract order data from WooCommerce webhook
            $wooOrderId = $payload['id'] ?? null;
            $wooOrderNumber = $payload['number'] ?? null;
            $wooOrderKey = $payload['order_key'] ?? null;

            if (!$wooOrderId) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required'
                ], 400);
            }

            // Get user by API key from payload
            $apiKey = $payload['api_key'] ?? $request->header('X-API-Key') ?? null;

            if (!$apiKey) {
                DB::rollBack();
                Log::error('WooCommerce webhook missing API key', [
                    'payload' => \Illuminate\Support\Arr::except($payload, ['api_key']),
                    'headers' => $request->headers->all()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'API key is required. Please include api_key in the webhook payload or X-API-Key header.'
                ], 401);
            }

            // Find user by API token
            $user = User::where('api_token', $apiKey)->first();

            if (!$user) {
                DB::rollBack();
                Log::error('WooCommerce webhook invalid API key', [
                    'api_key' => substr((string) $apiKey, 0, 4) . '…'
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid API key. User not found.'
                ], 404);
            }

            Log::info('WooCommerce order mapped to user', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'woocommerce_order_id' => $wooOrderId
            ]);

            // Check if order already exists
            $existingOrder = Order::where('woocommerce_order_id', $wooOrderId)->where('user_id', $user->id)->first();
            if ($existingOrder) {
                DB::rollBack();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order already exists',
                    'order_id' => $existingOrder->id
                ], 200);
            }

            // Extract billing and shipping information
            $billing = $payload['billing'] ?? [];
            $shipping = $payload['shipping'] ?? [];

            // Normalize phone number (remove country code, keep only digits)
            $customerMobile = $billing['phone'] ?? $shipping['phone'] ?? '';
            $customerMobile = preg_replace('/^\+?91/', '', $customerMobile); // remove +91 or 91
            $customerMobile = preg_replace('/\D/', '', $customerMobile); // keep only digits

            // Validate length (10 digits for India)
            if (strlen($customerMobile) !== 10) {
                $customerMobile = '';
            }

            // Create customer
            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $billing['first_name'] ?? '',
                'last_name' => $billing['last_name'] ?? '',
                'email' => $billing['email'] ?? '',
                'mobile' => $customerMobile,
                'status' => 1,
            ]);

            // Create customer address (shipping address)
            $address1 = $shipping['address_1'] ?? $billing['address_1'] ?? '';
            $address2 = $shipping['address_2'] ?? $billing['address_2'] ?? '';
            $fullAddress = trim("$address1 $address2");

            $customerAddress = CustomerAddress::create([
                'customer_id' => $customer->id,
                'address' => $fullAddress,
                'country' => $shipping['country'] ?? $billing['country'] ?? 'India',
                'state' => $shipping['state'] ?? $billing['state'] ?? '',
                'city' => $shipping['city'] ?? $billing['city'] ?? '',
                'zip_code' => $shipping['postcode'] ?? $billing['postcode'] ?? '',
                'status' => 1,
            ]);

            // Determine if COD
            $paymentMethod = strtolower($payload['payment_method'] ?? '');
            $isCod = (strpos($paymentMethod, 'cod') !== false) ||
                     (strpos($paymentMethod, 'cash') !== false);

            // Get totals
            $totalAmount = $payload['total'] ?? 0;
            $codAmount = $isCod ? $totalAmount : 0;
            $totalTax = $payload['total_tax'] ?? 0;
            $shippingTotal = $payload['shipping_total'] ?? 0;
            $discountTotal = $payload['discount_total'] ?? 0;

            // Get user's default warehouse
            $warehouse = \App\Models\CourierWarehouse::where('user_id', $user->id)
                ->where('warehouse_status', 1)
                ->first();

            if (!$warehouse) {
                $warehouse = \App\Models\CourierWarehouse::where('user_id', $user->id)->first();
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_prefix' => Order::generateOrderNumber($user->id),
                // 'order_manual_id' => Order::generateOrderNumber($user->id),
                'customer_id' => $customer->id,
                'customer_address_id' => $customerAddress->id,
                'vendor_address_id' => $warehouse->id ?? null,
                'warehouse_id' => $warehouse->id ?? null,
                'woocommerce_order_id' => $wooOrderId,
                'woocommerce_order_number' => $wooOrderNumber,
                'woocommerce_order_key' => $wooOrderKey,
                'is_woocommerce_order' => 1,
                'total_amount' => $totalAmount,
                'cod_amount' => $codAmount,
                'status_courier' => 'New',
                'order_date' => now()->toDateString(),
                'order_type' => $isCod ? 'cod' : 'prepaid',
                'is_online' => 1,
                'tax' => $totalTax,
                'shipping_charge' => $shippingTotal,
                'shipping_mode' => 'Surface',
                'weight_order' => 1,
                'status' => 1,
                'length' => 0,
                'width' => 0,
                'height' => 0,
                'weight' => 0,
                'total_shipping_cost' => $shippingTotal,
            ]);

            // Create order items with tax and discount details
            $lineItems = $payload['line_items'] ?? [];

            if (count($lineItems) > 0) {
                $totalGstAmount = 0;
                $maxLength = 0;
                $maxWidth = 0;
                $maxHeight = 0;
                $totalWeight = 0;

                foreach ($lineItems as $item) {
                    // Extract tax information
                    $gstData = [];
                    $itemTaxes = $item['taxes'] ?? [];

                    foreach ($itemTaxes as $tax) {
                        $taxAmount = $tax['total'] ?? 0;
                        $gstData[] = [
                            'price' => $taxAmount,
                            'rate' => $tax['rate'] ?? 0,
                            'title' => $tax['label'] ?? 'Tax'
                        ];
                        $totalGstAmount += $taxAmount;
                    }

                    // Get product image from WooCommerce
                    $productImage = null;
                    if (isset($item['image']['src'])) {
                        $productImage = $item['image']['src'];
                    }
                    $dimensions = $item['dimensions'] ?? [];
                    // Calculate Dimensions & Weight
                    $iLength = (float)($dimensions['length'] ?? 0);
                    $iWidth  = (float)($dimensions['width'] ?? 0);
                    $iWeight = $item['weight'] ?? 0;
                    $iHeight = (float)($dimensions['height'] ?? 0);
                    $qty = $item['quantity'] ?? 1;

                    $totalWeight += (float)$iWeight * $qty;
                    if ((float)$iLength > $maxLength) $maxLength = (float)$iLength;
                    if ((float)$iWidth > $maxWidth) $maxWidth = (float)$iWidth;
                    if ((float)$iHeight > $maxHeight) $maxHeight = (float)$iHeight;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_name' => $item['name'] ?? '',
                        'product_category' => $item['name'] ?? '',
                        'product_discription' => $item['name'] ?? '',
                        'sku_number' => $item['sku'] ?? '',
                        'amount' => $item['price'] ?? 0,
                        'product_image' => $productImage,
                        'collect_amount' => $item['price'] ?? 0,
                        'discount' => ($item['subtotal'] ?? 0) - ($item['total'] ?? 0), // Discount per item
                        'gst' => $gstData,
                        'quantity' => $item['quantity'] ?? 1
                    ]);
                }

                // Update order with total GST amount and Dimensions
                $order->update([
                    'total_gst_amount' => $totalGstAmount,
                    'length' => $maxLength,
                    'width' => $maxWidth,
                    'height' => $maxHeight,
                    'weight' => $totalWeight > 0 ? $totalWeight : $order->weight,
                ]);
            }

            // Create Order Status
            \App\Models\OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => 'New',
            ]);

            // Track Order Activity
            Helper::orderActivity($order->id, 'Order created from WooCommerce.');

            // Commit transaction
            DB::commit();

            Log::info('WooCommerce order created successfully', [
                'order_id' => $order->id,
                'woocommerce_order_id' => $wooOrderId,
                'total_amount' => $totalAmount,
                'total_gst' => $totalGstAmount ?? 0,
                'items_count' => count($lineItems)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'order_id' => $order->id
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('WooCommerce order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Order creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle WooCommerce order cancellation webhook
     */
    public function orderCancelled(Request $request)
    {
        try {
            $payload = $request->all();

            Log::info('WooCommerce order cancelled webhook received', [
                'payload' => \Illuminate\Support\Arr::except($payload, ['api_key']),
            ]);

            // Get order ID
            $wooOrderId = $payload['id'] ?? null;

            if (!$wooOrderId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required'
                ], 400);
            }

            // Get user by API key
            $apiKey = $payload['api_key'] ?? $request->header('X-API-Key') ?? null;

            if (!$apiKey) {
                Log::error('WooCommerce cancellation webhook missing API key');
                return response()->json([
                    'status' => 'error',
                    'message' => 'API key is required'
                ], 401);
            }

            // Validate user
            $user = User::where('api_token', $apiKey)->first();
            if (!$user) {
                Log::error('WooCommerce cancellation webhook invalid API key');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid API key'
                ], 404);
            }

            // Find the order
            $order = Order::where('woocommerce_order_id', $wooOrderId)->where('user_id', $user->id)->first();

            if (!$order) {
                Log::warning('WooCommerce order not found for cancellation', [
                    'woocommerce_order_id' => $wooOrderId
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order not found'
                ], 200);
            }

            // Check if already cancelled
            if (in_array($order->status_courier, ['cancelled', 'seller cancelled'])) {
                Log::info('WooCommerce order already cancelled', [
                    'order_id' => $order->id
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order already cancelled'
                ], 200);
            }

            // Case 1: Order not shipped yet (no shipping company assigned)
            if (empty($order->shipping_company_id)) {
                $order->update([
                    'status_courier' => 'cancelled',
                    'reason_cancel' => $payload['reason'] ?? 'Cancelled from WooCommerce',
                    'order_cancel_date' => now()->toDateString(),
                ]);

                \App\Models\OrderStatus::create([
                    'order_id' => $order->id,
                    'order_status' => 'cancelled',
                ]);

                Helper::orderActivity($order->id, 'Order cancelled from WooCommerce.');

                Log::info('WooCommerce order cancelled (not shipped)', [
                    'order_id' => $order->id,
                    'woocommerce_order_id' => $wooOrderId
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order cancelled successfully'
                ], 200);
            }

            // Case 2: Order has shipping company but not ready to ship
            if ($order->status_courier !== 'ready to ship') {
                Log::info('WooCommerce order cannot be cancelled (status: ' . $order->status_courier . ')', [
                    'order_id' => $order->id
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order cannot be cancelled at this stage'
                ], 400);
            }

            // Case 3: Order is ready to ship - need to cancel with courier
            $shippingCompany = $order->shippingCompany;

            if (!$shippingCompany) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shipping company not found'
                ], 404);
            }

            // Cancel with courier (using the same method as Shopify)
            $orderCancelledData = $this->cancelCourierOrder($order, $shippingCompany);

            if (!$orderCancelledData) {
                Log::error('Failed to cancel order with courier', [
                    'order_id' => $order->id,
                    'shipping_company_id' => $shippingCompany->id
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Courier cancellation failed'
                ], 500);
            }

            // Update order
            $order->update($orderCancelledData);

            // Refund wallet if normal user
            if ($user->role === 'user' && $order->shipping_charge > 0) {
                $user->increment('wallet_amount', $order->shipping_charge);

                \App\Models\Billing::create([
                    'user_id' => $order->user_id,
                    'billing_type' => 'Order',
                    'billing_type_id' => $order->id,
                    'transaction_type' => 'credit',
                    'amount' => $order->shipping_charge,
                    'note' => 'WooCommerce order cancelled (AWB: ' . $order->awb_number . ')',
                ]);

                Log::info('Wallet refunded for cancelled WooCommerce order', [
                    'order_id' => $order->id,
                    'amount' => $order->shipping_charge
                ]);
            }

            // Create order status
            \App\Models\OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => 'cancelled',
            ]);

            // Log activity
            Helper::orderActivity($order->id, 'WooCommerce order cancelled with AWB: ' . $order->awb_number);

            Log::info('WooCommerce order cancelled successfully', [
                'order_id' => $order->id,
                'woocommerce_order_id' => $wooOrderId,
                'awb_number' => $order->awb_number
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('WooCommerce order cancellation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Order cancellation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order with courier service
     * (Shared method with Shopify - same logic)
     */
    private function cancelCourierOrder($order, $shippingCompany)
    {
        $date = now()->toDateString();

        try {
            switch ($shippingCompany->id) {
                case 1:
                case 9:
                    // XpressBees
                    $url = $shippingCompany->url . 'api/shipments2/cancel';
                    $response = Helper::callCurlApi(
                        $url,
                        ['awb' => $order->awb_number],
                        Helper::xpressBeesToken($shippingCompany->id)
                    );
                    if (!($response['status'] ?? false)) {
                        return null;
                    }
                    break;

                case 7:
                case 8:
                    // Delhivery/Other
                    $response = \Illuminate\Support\Facades\Http::asMultipart()
                        ->attach('username', $shippingCompany->email)
                        ->attach('password', $shippingCompany->password)
                        ->attach('awbs', $order->awb_number)
                        ->post($shippingCompany->url . 'apiv2/cancel_awb/');

                    if (!$response->successful() || ($response->json()[0]['success'] ?? false) !== true) {
                        return null;
                    }
                    break;

                case 11:
                    // Delhivery Service
                    $delhiveryService = app(\App\Services\DelhiveryService::class);
                    $response = $delhiveryService->cancelShipmentByLrNo($order->lr_no, $shippingCompany);
                    if (!($response['success'] ?? false)) {
                        return null;
                    }
                    break;

                case 2:
                    // Delhivery B2C
                    $delhiveryB2CService = app(\App\Services\DelhiveryB2CService::class);
                    $response = $delhiveryB2CService->cancelShipmentByAwbNumber($order->awb_number, $shippingCompany);
                    if (!($response['success'] ?? false)) {
                        return null;
                    }
                    break;

                case 10:
                    // Ekart
                    $ekartService = app(\App\Services\EkartService::class);
                    $response = $ekartService->cancelShipment($order, $shippingCompany->refresh());
                    if (!($response['success'] ?? false)) {
                        return null;
                    }
                    break;

                case 12:
                case 13:
                    // DTDC
                    $dtdcService = app(\App\Services\DtdcService::class);
                    $response = $dtdcService->cancelShipment($order, $shippingCompany->refresh());
                    if (!($response['success'] ?? false)) {
                        return null;
                    }
                    break;

                default:
                    Log::warning('Unsupported shipping company for cancellation', [
                        'shipping_company_id' => $shippingCompany->id
                    ]);
                    return null;
            }

            return [
                'status_courier' => 'cancelled',
                'order_cancel_date' => $date,
                'reason_cancel' => "AWB {$order->awb_number} cancelled by courier",
            ];

        } catch (\Exception $e) {
            Log::error('Courier cancellation exception', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'shipping_company_id' => $shippingCompany->id
            ]);
            return null;
        }
    }
}
