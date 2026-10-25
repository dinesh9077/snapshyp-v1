<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\OrderType;

class RoutxService
{
    protected $baseUrl = 'https://app.routxmitra.com/api';

    private function logRequest($action, $payload, $baseUrl)
    {
        Log::info("Routx {$action} Request", [
            'base_url' => $baseUrl,
            'payload' => $payload
        ]);
    }

    private function logResponse($action, $response)
    {
        try {
            $body = $response->json();
        } catch (\Throwable $e) {
            $body = $response->body();
        }

        if ($response->successful()) {
            Log::info("Routx {$action} Response", [
                'status' => $response->status(),
                'body' => $body
            ]);
        } else {
            Log::error("Routx {$action} Error", [
                'status' => $response->status(),
                'body' => $body
            ]);
        }
    }

    private function getBaseUrl($shippingCompany)
    {
        $url = trim((string)($shippingCompany->url ?? ''));
        if ($url === '') {
            return $this->baseUrl;
        }
        return rtrim($url, '/');
    }

    private function normalizePaymentMode($orderType)
    {
        $value = strtolower(trim((string) $orderType));
        if (in_array($value, ['cod', 'cash_on_delivery', 'cash on delivery', '2'], true)) {
            return 'COD';
        }
        return 'Prepaid';
    }

    private function isCod($orderType)
    {
        return $this->normalizePaymentMode($orderType) === 'COD';
    }

    private function resolveEmail($primary, $secondary = null)
    {
        $primary = strtolower(trim((string) $primary));
        if ($primary !== '' && filter_var($primary, FILTER_VALIDATE_EMAIL)) {
            return $primary;
        }

        $secondary = strtolower(trim((string) $secondary));
        if ($secondary !== '' && filter_var($secondary, FILTER_VALIDATE_EMAIL)) {
            return $secondary;
        }

        $fallback = strtolower(trim((string) config('mail.from.address', 'support@example.com')));
        if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
            return $fallback;
        }

        return 'jdaclogistics@gmail.com'; // Use a valid domain to pass API validation
    }

    /**
     * Create/Register a new warehouse in Routx.
     */
    public function createWarehouse($warehouse, $shippingCompany, $overrideName = null)
    {
        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        
        $email = $this->resolveEmail($warehouse->contact_email ?? null, $warehouse->email ?? null);

        $payload = [
            "PersonName" => $warehouse->contact_name,
            "WarehouseName" => $overrideName ?? $warehouse->warehouse_name,
            "Phone" => $warehouse->contact_number,
            "AlternativePhone" => $warehouse->contact_number,
            "EmailID" => $email,
            "email" => $email,
            "Email" => $email,
            "ContactEmail" => $email,
            "AddressLine1" => $warehouse->address,
            "AddressLine2" => $warehouse->address2 ?? '',
            "City" => $warehouse->city ?? '',
            "State" => $warehouse->state ?? '',
            "Pincode" => (string)$warehouse->zip_code,
            "WorkingDays" => "Monday,Tuesday,Wednesday,Thursday,Friday",
            "PickupSlot" => "9.30 to 6.00"
        ];

        try {
            $this->logRequest('CreateWarehouse', $payload, $baseUrl);
            $response = Http::withToken($token)
                ->post($baseUrl . '/orders/CreateWarehouse', $payload);

            $this->logResponse('CreateWarehouse', $response);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Routx Create Warehouse Error', ['msg' => $e->getMessage()]);
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    /**
     * Calculate shipping rates.
     */
    public function rateCalculator($order, $shippingCompany)
    {
        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $paymentMode = $this->normalizePaymentMode($order->order_type ?? '');
        
        $packageWeightList = [];
        if ($order->weight_order == 2) {
            foreach ($order->orderItems as $item) {
                $dim = is_string($item->dimensions) ? json_decode($item->dimensions, true) : $item->dimensions;
                $packageWeightList[] = [
                    "Length" => (float)($dim['length'] ?? 0),
                    "Width" => (float)($dim['width'] ?? 0),
                    "Height" => (float)($dim['height'] ?? 0),
                    "Qty" => (int)($dim['no_of_box'] ?? 1),
                    "Weight" => (float)($dim['weight'] ?? 0)
                ];
            }
        } else {
            $packageWeightList[] = [
                "Length" => (float)$order->length,
                "Width" => (float)$order->width,
                "Height" => (float)$order->height,
                "Qty" => 1,
                "Weight" => (float)$order->weight
            ];
        }

        $payload = [
            "ShipmentType" => "Forward",
            "PickupPincode" => (string)$order->warehouse->zip_code,
            "DeliveryPincode" => (string)$order->customerAddress->zip_code,
            "PaymentMode" => $paymentMode,
            "CODAmount" => $paymentMode === 'COD' ? (float)($order->cod_amount ?? 0) : 0,
            "InvoiceValue" => (float)$order->invoice_amount,
            "DocType" => "Non-Document",
            "Insurance" => (bool)$order->has_insurance,
            "DeliveryAppointment" => (bool)$order->is_appointment_delivery,
            "PackageWeightList" => $packageWeightList
        ];

        try {
            $this->logRequest('RateCalculator', $payload, $baseUrl);
            $response = Http::withToken($token)
                ->post($baseUrl . '/orders/rateCalculator', $payload);

            if (!$response->successful()) {
                $response = Http::withToken($token)
                    ->send('GET', $baseUrl . '/orders/rateCalculator', [
                        'json' => $payload
                    ]);
            }

            if (!$response->successful()) {
                $response = Http::withToken($token)
                    ->get($baseUrl . '/orders/rateCalculator', $payload);
            }

            if ($response->successful()) {
                $this->logResponse('RateCalculator', $response);
                $body = $response->json();
                if (is_array($body) && array_key_exists(0, $body)) {
                    return ['success' => true, 'response' => ['data' => $body]];
                }
                return ['success' => true, 'response' => $body];
            }

            $this->logResponse('RateCalculator', $response);

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Routx Rate Calculator Exception', ['msg' => $e->getMessage()]);
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    /**
     * Create Order in Routx.
     */
    public function createOrder($order, $requestData, $shippingCompany)
    {
        // Business rule: an order must be COD or Prepaid before any shipment is booked.
        OrderType::validate($order->order_type);

        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $paymentMode = $this->normalizePaymentMode($order->order_type ?? '');
        
        $packageWeightList = [];
        if ($order->weight_order == 2) {
            foreach ($order->orderItems as $item) {
                $dim = is_string($item->dimensions) ? json_decode($item->dimensions, true) : $item->dimensions;
                $packageWeightList[] = [
                    "Length" => (float)($dim['length'] ?? 0),
                    "Width" => (float)($dim['width'] ?? 0),
                    "Height" => (float)($dim['height'] ?? 0),
                    "Qty" => (int)($dim['no_of_box'] ?? 1),
                    "Weight" => (float)($dim['weight'] ?? 0)
                ];
            }
        } else {
            $packageWeightList[] = [
                "Length" => (float)$order->length,
                "Width" => (float)$order->width,
                "Height" => (float)$order->height,
                "Qty" => 1,
                "Weight" => (float)$order->weight
            ];
        }

        $orderItems = [];
        $itemsTotal = 0.0;
        foreach ($order->orderItems as $item) {
            $lineTotal = (float)($item->amount * $item->quantity);
            $itemsTotal += $lineTotal;
            $orderItems[] = [
                "ProductName" => $item->product_name,
                "HSN" => $item->hsn_number ?? "",
                "SKU" => $item->sku_number ?? "",
                "Qty" => (int)$item->quantity,
                "Price" => (float)$item->amount,
                "Total" => $lineTotal
            ];
        }

        $invoiceValue = (float)($order->invoice_amount ?? 0);
        if ($itemsTotal > 0 && $invoiceValue <= 0) {
            $invoiceValue = $itemsTotal;
        } elseif ($itemsTotal > 0 && $invoiceValue > 0 && $itemsTotal != $invoiceValue) {
            // Routx validates invoice value; align with item totals
            $invoiceValue = $itemsTotal;
        }

        $codAmount = $paymentMode === 'COD' ? (float)($order->cod_amount ?? 0) : 0;
        if ($paymentMode === 'COD' && $codAmount <= 0) {
            $codAmount = $invoiceValue;
        }

        $customerEmail = $this->resolveEmail($order->customer->email ?? null, null);
        $warehouseEmail = $this->resolveEmail($order->warehouse->contact_email ?? null, $order->warehouse->email ?? null);

        $payload = [
            "OrderNumber" => $requestData['routx_order_number'] ?? ($order->order_prefix ?? (string)$order->id),
            "PickupPincode" => (string)$order->warehouse->zip_code,
            "DestinationPincode" => (string)$order->customerAddress->zip_code,
            "WarehouseName" => $requestData['routx_warehouse_name'] ?? $order->warehouse->warehouse_name,
            "PickupEmail" => $warehouseEmail,
            "email" => $warehouseEmail,
            "PaymentMode" => $paymentMode,
            "InvoiceValue" => $invoiceValue,
            "HasInsurance" => (bool)$order->has_insurance,
            "IsAppointmentDelivery" => (bool)$order->is_appointment_delivery,
            "CODAmount" => $paymentMode === 'COD' ? $codAmount : null,
            "ProductType" => "General",
            "InvoiceNumber" => $order->invoice_no ?? $order->order_prefix,
            "ProductDescription" => $order->orderItems->first()->product_name ?? "General Items",
            "HSNCode" => $order->orderItems->first()->hsn_number ?? "",
            "EwaybillNumber" => $order->ewaybill_number ?? "",
            "CustomerName" => $order->customer->first_name . ' ' . $order->customer->last_name,
            "PhoneNumber" => $order->customer->mobile,
            "Email" => $customerEmail,
            "Address" => $order->customerAddress->address,
            "Address2" => $order->customerAddress->address2 ?? "",
            "City" => $order->customerAddress->city,
            "State" => $order->customerAddress->state,
            "PackageWeightList" => $packageWeightList,
            "OrderItems" => $orderItems
        ];

        try {
            $this->logRequest('CreateOrder', $payload, $baseUrl);
            $response = Http::withToken($token)
                ->post($baseUrl . '/orders/create', $payload);

            $this->logResponse('CreateOrder', $response);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Routx Create Order Error', ['msg' => $e->getMessage()]);
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    /**
     * Create Shipment for an existing order in Routx.
     */
    public function createShipment($order, $carrierId, $serviceType, $shippingCompany, $overrideOrderId = null)
    {
        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        
        $api1 = $order->api_response1 ?? [];
        if (is_string($api1)) {
            $api1 = json_decode($api1, true) ?? [];
        }
        
        // Prioritize the custom order number (string) as per documentation examples
        $orderIdentifier = $overrideOrderId ?? $api1['routx_order_number'] ?? ($order->order_prefix ?? (string)$order->id);
        $warehouseEmail = $this->resolveEmail($order->warehouse->contact_email ?? null, $order->warehouse->email ?? null);
        $payload = [
            "OrderId" => $orderIdentifier,
            "CarrierId" => (int)$carrierId,
            "ServiceType" => $serviceType
        ];

        try {
            $response = $this->postCreateShipment($token, $baseUrl, $payload);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    private function postCreateShipment($token, $baseUrl, $payload, $isRetry = false)
    {
        $action = $isRetry ? 'CreateShipmentForOrder Retry' : 'CreateShipmentForOrder';
        $this->logRequest($action, $payload, $baseUrl);
        $response = Http::withToken($token)
            ->post($baseUrl . '/orders/CreateShipmentForOrder', $payload);
        $this->logResponse($action, $response);
        return $response;
    }

    /**
     * Check Pincode Serviceability.
     */
    public function checkPincode($pincode, $shippingCompany)
    {
        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        
        try {
            $this->logRequest('PincodeServiceability', ['pincode' => $pincode], $baseUrl);
            $response = Http::withToken($token)
                ->get($baseUrl . '/orders/pincodeservisability/' . $pincode);

            $this->logResponse('PincodeServiceability', $response);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    /**
     * Track Shipment.
     */
    public function track($awb, $shippingCompany)
    {
        $token = $shippingCompany->api_key;
        if (stripos((string)$token, 'Bearer ') === 0) {
            $token = trim(substr((string)$token, 7));
        }
        $baseUrl = $this->getBaseUrl($shippingCompany);
        
        try {
            $this->logRequest('Track', ['awb' => $awb], $baseUrl);
            $response = Http::withToken($token)
                ->get($baseUrl . '/orders/tracking/' . $awb);

            $this->logResponse('Track', $response);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'response' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }
}
