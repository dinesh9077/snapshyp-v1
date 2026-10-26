<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\OrderType;

/**
 * Shadowfax Unified API Service (v3 Final)
 * Strictly follows the working v3 specification for Orders, Tracking, and Cancellation.
 */
class ShadowfaxService
{
    private function getBaseUrl($shippingCompany)
    {
        $url = trim((string) ($shippingCompany->url ?? 'https://dale.shadowfax.in/api'));
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '' || $path === '/') {
            $url = rtrim($url, '/') . '/api';
        }
        return rtrim($url, '/');
    }

    private function getHeaders($shippingCompany)
    {
        return [
            'Authorization' => 'Token ' . $shippingCompany->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function extractMessage($payload): string
    {
        if (is_string($payload)) {
            return trim($payload);
        }

        if (!is_array($payload)) {
            return '';
        }

        foreach (['message', 'detail', 'error', 'errors', 'remarks', 'cancel_remarks'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if (is_string($value)) {
                return trim($value);
            }

            if (is_array($value)) {
                $flat = [];
                array_walk_recursive($value, function ($item) use (&$flat) {
                    if (is_scalar($item)) {
                        $flat[] = (string) $item;
                    }
                });

                if (!empty($flat)) {
                    return implode(', ', $flat);
                }
            }
        }

        return '';
    }

    private function getNestedValue($payload, string $path)
    {
        $current = $payload;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function firstNonEmptyValue($payload, array $paths)
    {
        foreach ($paths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeStatusLabel(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return Str::headline(str_replace(['_', '-'], ' ', $value));
    }

    // Shadowfax event code / label => our status_courier (shared by the webhook and the status cron)
    public const STATUS_MAP = [
        'delivered' => 'delivered',
        'new' => 'new',
        'picked' => 'picked up',
        'assigned_for_seller_pickup' => 'pending pickup',
        'assigned_for_pickup' => 'pending pickup',
        'ofp' => 'out for pickup',
        'recd_at_rev_hub' => 'picked up',
        'item_manifested' => 'manifested',
        'bag_in_transit' => 'in transit',
        'bag_received' => 'in transit',
        'bag_received_at_via' => 'in transit',
        'recd_at_fwd_dc' => 'in transit',
        'recd_at_fwd_hub' => 'in transit',
        'assigned_for_delivery' => 'assigned for delivery',
        'ofd' => 'out for delivery',
        'nc' => 'not contactable',
        'na' => 'not attempted',
        'on_hold' => 'on hold',
        'pickup_on_hold' => 'pickup on hold',
        'pickup_not_attempted' => 'pickup not attempted',
        'cancelled_by_customer' => 'cancelled',
        'cancelled_by_seller' => 'cancelled',
        'rts' => 'rto',
        'rts_in_process' => 'rto in transit',
        'rts_ofd' => 'rto out for delivery',
        'rts_d' => 'rto delivered',
        'rts_nd' => 'rto undelivered',
        'rto' => 'rto',
        'rto_in_process' => 'rto in transit',
        'rto_d' => 'rto delivered',
        'rto_nd' => 'rto undelivered',
        'reopen_ndr' => 'reopen ndr',
        'lost' => 'lost',
        'out_for_delivery' => 'out for delivery',
        'out for delivery' => 'out for delivery',
        'in_transit' => 'in transit',
        'in transit' => 'in transit',
        'picked_up' => 'picked up',
        'picked up' => 'picked up',
        'rto_initiated' => 'rto',
        'rto initiated' => 'rto',
        'rto_delivered' => 'rto delivered',
        'rto delivered' => 'rto delivered',
        'cancelled' => 'cancelled',
    ];

    public static function normalizeStatus(?string $raw): string
    {
        $status = strtolower(trim((string) $raw));
        return self::STATUS_MAP[$status] ?? $status;
    }

    /**
     * Current shipment status code from a trackOrder() result: the top-level status when Shadowfax
     * sends one, else the most recent event of the history. Null when nothing usable came back.
     */
    public function currentStatusFromTracking(array $tracking): ?string
    {
        $response = is_array($tracking['response'] ?? null) ? $tracking['response'] : [];
        $current = $this->firstNonEmptyValue($response, ['data.status', 'data.current_status', 'order_details.status', 'current_status', 'event', 'status']);
        if (is_string($current) && trim($current) !== '' && !in_array(strtolower(trim($current)), ['success', 'ok', 'true', 'false', 'failure', 'error'], true)) {
            return $current;
        }

        $history = $tracking['history'] ?? [];
        if (empty($history)) {
            return null;
        }
        // Event lists may come oldest-first or newest-first: take the latest timestamp
        usort($history, function ($a, $b) {
            return strtotime((string) ($b['timestamp'] ?? '')) <=> strtotime((string) ($a['timestamp'] ?? ''));
        });
        $latest = $history[0];
        $code = $latest['status_id'] !== '' ? $latest['status_id'] : $latest['status'];

        return $code !== '' ? $code : null;
    }

    /**
     * Check Pincode Serviceability
     */
    public function checkPincode($pincode, $shippingCompany, $serviceType = 'customer_delivery')
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);
        try {
            $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                ->get($baseUrl . '/v1/clients/serviceability/', [
                    'service' => $serviceType,
                    'pincodes' => (string) $pincode
                ]);
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && is_array($data)) {
                    foreach ($data as $item) {
                        if (($item['code'] ?? '') == $pincode) {
                            if (!empty($item['services']) && is_array($item['services'])) {
                                return ['success' => true, 'services' => $item['services']];
                            }
                        }
                    }
                }
            }
            return ['success' => false, 'message' => "Pincode {$pincode} not serviceable"];
        } catch (\Exception $e) { return ['success' => false]; }
    }

    /**
     * Generate AWB Numbers (v3)
     */
    public function generateAwb($shippingCompany, int $count = 1)
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);

        try {
            Log::info("Shadowfax AWB Generation Request", ['count' => $count]);

            $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                ->post($baseUrl . '/v3/clients/generate_marketplace_awb/', [
                    'count' => $count
                ]);

            $result = $response->json();

            Log::info("Shadowfax AWB Generation Response", ['status' => $response->status(), 'body' => $result]);

            if ($response->successful() && ($result['message'] ?? '') === 'success') {
                $awbNumbers = $result['awb_numbers'] ?? [];
                if (!empty($awbNumbers) && is_array($awbNumbers)) {
                    return ['success' => true, 'awb_numbers' => $awbNumbers, 'awb_number' => $awbNumbers[0]];
                }
            }
            return ['success' => false, 'message' => $result['message'] ?? $result['errors'] ?? 'Failed to generate AWB'];
        } catch (\Exception $e) {
            Log::error('Shadowfax AWB Generation Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Create Order (v3 Unified)
     */
    public function createOrder($order, $shippingCompany)
    {
        // Business rule: an order must be COD or Prepaid before any shipment is booked.
        OrderType::validate($order->order_type);

        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);

        // 1. First generate AWB number from Shadowfax API
        $awbGeneration = $this->generateAwb($shippingCompany, 1);

        if (!$awbGeneration['success']) {
            Log::error('Shadowfax AWB Generation Failed', ['message' => $awbGeneration['message']]);
            return ['success' => false, 'message' => 'AWB Generation Failed: ' . $awbGeneration['message']];
        }

        $awbNumber = $awbGeneration['awb_numbers'];
        Log::info('Shadowfax Generated AWB', ['awb_number' => $awbNumber]);

        // 2. Dynamic order_service Mapping
        $orderService = 'regular';
        $mode = strtolower((string) ($order->mode ?? ''));
        if (str_contains($mode, 'surface')) { $orderService = 'surface'; }

        // 3. Build Payload matching v3 Specification with AWB
        // Orders store the payment type in order_type ('cod' / 'prepaid'); collect cod_amount, not invoice value
        $isCod = OrderType::isCod($order->order_type);
        $paymentMode = $isCod ? 'COD' : 'Prepaid';
        $codAmount = $isCod ? (string) ((float) $order->cod_amount > 0 ? $order->cod_amount : $order->invoice_amount) : "0";

        $productItems = [];
        foreach ($order->orderItems as $item) {
            $productItems[] = [
                'sku_name' => $item->product_name ?? 'Product',
                'sku_id' => $item->sku_number ?? 'NA',
                'price' => (float) ($item->amount ?? 0),
                'category' => $item->product_category ?? 'General',
                'seller_details' => [
                    'seller_name' => $order->warehouse->warehouse_name ?? 'Warehouse',
                    'seller_address' => $order->warehouse->address ?? 'Warehouse Address',
                    'seller_state' => $order->warehouse->state ?? 'N/A',
                ],
                'additional_details' => [
                    'quantity' => (int) ($item->quantity ?? 1),
                    'requires_extra_care' => 'False'
                ]
            ];
        }

        $payload = [
            'order_type' => 'marketplace',
            'order_details' => [
                'client_order_id' => 'SF' . ($order->order_prefix ?? (string) $order->id),
                'awb_number' => $awbNumber[0] ?? null,
                'actual_weight' => (float) ($order->weight ?? 0.5),
                'volumetric_weight' => (float) ($order->weight ?? 0.5),
                'product_value' => (float) $order->invoice_amount,
                'payment_mode' => $paymentMode,
                'cod_amount' => $codAmount,
                'total_amount' => (float) $order->invoice_amount,
                'order_service' => $orderService,
            ],
            'customer_details' => [
                'name' => $order->customer->first_name . ' ' . $order->customer->last_name,
                'contact' => $order->customer->mobile,
                'address_line_1' => $order->customerAddress->address,
                'city' => $order->customerAddress->city,
                'state' => $order->customerAddress->state,
                'pincode' => (int) $order->customerAddress->zip_code,
            ],
            'pickup_details' => [
                'name' => $order->warehouse->warehouse_name ?? 'Warehouse',
                'contact' => $order->warehouse->contact_number,
                'address_line_1' => $order->warehouse->address,
                'city' => $order->warehouse->city,
                'state' => $order->warehouse->state,
                'pincode' => (int) $order->warehouse->zip_code,
                'unique_code' => $order->warehouse->warehouse_id ?? ''
            ],
            'rts_details' => [
                'name' => $order->warehouse->warehouse_name ?? 'Warehouse',
                'contact' => $order->warehouse->contact_number,
                'address_line_1' => $order->warehouse->address,
                'city' => $order->warehouse->city,
                'state' => $order->warehouse->state,
                'pincode' => (int) $order->warehouse->zip_code,
            ],
            'product_details' => $productItems
        ];

        try {

            $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                ->post($baseUrl . '/v3/clients/orders/', $payload);

            $result = $response->json();

            if ($response->successful()) {
                if (($result['message'] ?? '') === 'Failure') {
                    $errors = is_array($result['errors'] ?? null) ? implode(', ', $result['errors']) : ($result['errors'] ?? 'Unknown Error');

                    // Handle "Already Created" Error to recover the AWB
                    if (str_contains($errors, 'already created with AWB')) {
                         preg_match('/AWB\s*:\s*([A-Z0-9]+)/i', $errors, $matches);
                         $awb = $matches[1] ?? ($result['AWB'] ?? null);
                         if ($awb) {
                             return ['success' => true, 'awb_number' => (string) $awb, 'data' => $result];
                         }
                    }
                    return ['success' => false, 'message' => $errors];
                }

                // Use the AWB number we generated or the one returned by API
                $responseAwb = $result['awb_number'] ?? ($result['data']['awb_number'] ?? null);
                // $awbNumber is the generated list; fall back to the AWB we actually sent
                $finalAwb = $responseAwb ?? ($awbNumber[0] ?? null);

                if ($finalAwb) {
                    return ['success' => true, 'awb_number' => (string) $finalAwb, 'data' => $result];
                }
            }

            return ['success' => false, 'message' => $result['errors'] ?? $result['message'] ?? 'API Error'];

        } catch (\Exception $e) {
            Log::error('Shadowfax Create Final Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    private function looksLikeEventList($value): bool
    {
        return is_array($value) && !empty($value) && array_is_list($value) && is_array($value[0]);
    }

    private function normalizeTrackingEvent(array $event): array
    {
        $scanDetail = is_array($event['ScanDetail'] ?? null) ? $event['ScanDetail'] : [];

        $statusId = $this->firstNonEmptyValue($event, ['event', 'status_id', 'status_code', 'code'])
            ?? $this->firstNonEmptyValue($scanDetail, ['ScanType']);

        $status = $this->firstNonEmptyValue($event, [
            'status',
            'status_display',
            'event_name',
            'activity',
            'current_status',
        ]) ?? $this->firstNonEmptyValue($scanDetail, ['Scan']);

        $remarks = $this->firstNonEmptyValue($event, [
            'comments',
            'remark',
            'remarks',
            'description',
            'message',
            'instructions',
        ]) ?? $this->firstNonEmptyValue($scanDetail, ['Instructions']);

        $location = $this->firstNonEmptyValue($event, [
            'current_location',
            'location',
            'location_name',
            'facility',
            'hub',
            'center',
            'city',
        ]) ?? $this->firstNonEmptyValue($scanDetail, ['ScannedLocation']);

        $timestamp = $this->firstNonEmptyValue($event, [
            'event_timestamp',
            'updated_at',
            'created_at',
            'timestamp',
            'statusDateTime',
            'date_at',
            'date',
            'scan_date_time',
            'created', // Shadowfax tracking_details items
        ]) ?? $this->firstNonEmptyValue($scanDetail, ['ScanDateTime']);

        $statusLabel = (string) ($status ?: $this->normalizeStatusLabel((string) $statusId));

        return [
            'status_id' => (string) ($statusId ?? ''),
            'status' => $statusLabel,
            'remarks' => (string) ($remarks ?? ''),
            'location' => (string) ($location ?? ''),
            'timestamp' => $timestamp ? (string) $timestamp : null,
            'raw' => $event,
        ];
    }

    private function synthesizeTrackingHistory(array $payload): array
    {
        $statusId = $this->firstNonEmptyValue($payload, ['data.status', 'status', 'event']);
        $status = $this->firstNonEmptyValue($payload, ['data.status_display', 'status_display', 'data.status', 'status']);

        if (!$statusId && !$status) {
            return [];
        }

        return [[
            'status_id' => (string) ($statusId ?? ''),
            'status' => (string) ($status ?: $this->normalizeStatusLabel((string) $statusId)),
            'remarks' => (string) ($this->firstNonEmptyValue($payload, ['message', 'detail']) ?? ''),
            'location' => (string) ($this->firstNonEmptyValue($payload, ['data.current_location', 'current_location']) ?? ''),
            'timestamp' => $this->firstNonEmptyValue($payload, ['data.updated_at', 'updated_at', 'data.order_date', 'order_date']),
            'raw' => $payload,
        ]];
    }

    private function extractTrackingHistory(array $payload): array
    {
        $sources = [];

        if ($this->looksLikeEventList($payload)) {
            $sources[] = $payload;
        }

        foreach (['data', 'result'] as $rootKey) {
            $root = $payload[$rootKey] ?? null;
            if ($this->looksLikeEventList($root)) {
                $sources[] = $root;
            }

            if (is_array($root)) {
                foreach ([
                    'history',
                    'histories',
                    'tracking_history',
                    'tracking_histories',
                    'status_history',
                    'events',
                    'event_history',
                    'scans',
                    'timeline',
                    'tracking_data',
                    'tracking_details',
                ] as $key) {
                    if ($this->looksLikeEventList($root[$key] ?? null)) {
                        $sources[] = $root[$key];
                    }
                }
            }
        }

        foreach ([
            'history',
            'histories',
            'tracking_history',
            'tracking_histories',
            'status_history',
            'events',
            'event_history',
            'scans',
            'timeline',
            'tracking_data',
            'tracking_details', // Shadowfax v4 track response
        ] as $key) {
            if ($this->looksLikeEventList($payload[$key] ?? null)) {
                $sources[] = $payload[$key];
            }
        }

        foreach ($sources as $source) {
            $history = array_values(array_filter(array_map(function ($event) {
                return $this->normalizeTrackingEvent($event);
            }, $source), function ($event) {
                return $event['status'] !== '' || $event['status_id'] !== '' || $event['timestamp'] !== null;
            }));

            if (!empty($history)) {
                return $history;
            }
        }

        return $this->synthesizeTrackingHistory($payload);
    }

    private function toLegacyHistory(array $history): array
    {
        return array_map(function ($item) {
            $fallbackStatus = $item['status'] !== ''
                ? $item['status']
                : $this->normalizeStatusLabel($item['status_id']);

            return [
                'ScanDetail' => [
                    'Scan' => $fallbackStatus,
                    'ScannedLocation' => $item['location'] !== '' ? $item['location'] : 'N/A',
                    'Instructions' => $item['remarks'] !== '' ? $item['remarks'] : $fallbackStatus,
                    'ScanDateTime' => $item['timestamp'] ?: now()->toDateTimeString(),
                ],
                'status' => $item['status'],
                'status_id' => $item['status_id'],
                'remarks' => $item['remarks'],
                'location' => $item['location'],
                'created_at' => $item['timestamp'],
                'raw' => $item['raw'],
            ];
        }, $history);
    }

    public function track($awbNumber, $shippingCompany)
    {
        return $this->trackOrder($awbNumber, $shippingCompany);
    }

    public function trackOrder($awbNumber, $shippingCompany)
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);

        try {
            $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                ->get($baseUrl . '/v4/clients/orders/' . urlencode((string) $awbNumber) . '/track/');

            $result = $response->json();

            if (!$response->successful() || !is_array($result)) {
                return [
                    'success' => false,
                    'message' => $this->extractMessage($result) ?: 'Unable to fetch tracking details.',
                    'response' => $result,
                ];
            }

            $history = $this->extractTrackingHistory($result);

            return [
                'success' => true,
                'message' => 'Tracking details fetched successfully.',
                'response' => $result,
                'history' => $history,
                'legacy_history' => $this->toLegacyHistory($history),
                'expected_delivery_date' => $this->firstNonEmptyValue($result, [
                    'data.promised_delivery_date',
                    'promised_delivery_date',
                    'data.estimated_delivery_date',
                    'estimated_delivery_date',
                    'data.edd',
                    'edd',
                ]) ?? 'N/A',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function shouldRetryCancellation(array $result): bool
    {
        $status = (int) ($result['status'] ?? 0);
        $message = strtolower((string) ($result['message'] ?? ''));

        if ($status === 404) {
            return true;
        }

        if (in_array($status, [400, 422], true)) {
            foreach (['required', 'field', 'request_id', 'cancel', 'awb_number', 'client_order_id'] as $keyword) {
                if (str_contains($message, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isCancellationAccepted(array $result): bool
    {
        $status = (int) ($result['status'] ?? 0);
        $message = strtolower((string) ($result['message'] ?? ''));
        $body = is_array($result['response'] ?? null) ? $result['response'] : [];

        // Shadowfax answers HTTP 200 with message "Failure" + errors when it refuses (e.g. already
        // picked up); treating that as cancelled would refund a shipment that is still moving
        $bodyFailed = strtolower((string) ($body['message'] ?? '')) === 'failure'
            || !empty($body['errors'])
            || (array_key_exists('success', $body) && $body['success'] === false)
            || in_array(strtolower((string) ($body['status'] ?? '')), ['failure', 'failed', 'error'], true);
        if ($bodyFailed) {
            return false;
        }

        if (in_array($status, [200, 304], true)) {
            return true;
        }

        return str_contains($message, 'marked as cancelled')
            || str_contains($message, 'queued for cancellation');
    }

    private function performCancellation($awbNumber, $shippingCompany, ?string $reason = null, ?string $clientOrderId = null): array
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);
        $reason = $reason ?: 'Request cancelled by customer';

        // $payloads = [
        //     ['awb_number' => (string) $awbNumber, 'reason' => $reason],
        //     ['awb_number' => (string) $awbNumber, 'cancel_reason' => $reason],
        //     ['awb_number' => (string) $awbNumber, 'cancel_remarks' => $reason],
        // ];

        // if ($clientOrderId) {
        //     array_unshift($payloads, ['client_order_id' => (string) $clientOrderId, 'reason' => $reason]);
        // }

        $payloads[] = ['request_id' => (string) ($awbNumber ?: $clientOrderId), 'cancel_remarks' => $reason];

        $lastResult = ['status' => 0, 'message' => 'Unable to cancel order.', 'response' => null];

        foreach ($payloads as $payload) {
            try {
                $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                    ->post($baseUrl . '/v3/clients/orders/cancel/', $payload);

                $result = [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'message' => $this->extractMessage($response->json()),
                ];
            } catch (\Exception $e) {
                $result = ['status' => 0, 'response' => null, 'message' => $e->getMessage()];
            }

            $lastResult = $result;

            if ($this->isCancellationAccepted($result)) {
                return [
                    'success' => true,
                    'queued' => str_contains(strtolower((string) $result['message']), 'queued'),
                    'message' => $result['message'] ?: 'Request has been marked as cancelled.',
                    'response' => $result['response'],
                    'status' => $result['status'],
                ];
            }

            if (!$this->shouldRetryCancellation($result)) {
                break;
            }
        }

        return [
            'success' => false,
            'queued' => false,
            'message' => $lastResult['message'] ?? 'Unable to cancel order.',
            'response' => $lastResult['response'],
            'status' => $lastResult['status'] ?? 0,
        ];
    }

    public function cancel($awbNumber, $shippingCompany, ?string $reason = null, ?string $clientOrderId = null)
    {
        return $this->performCancellation($awbNumber, $shippingCompany, $reason, $clientOrderId);
    }

    public function cancelOrder($awbNumber, $reasonOrShippingCompany, $shippingCompany = null, ?string $clientOrderId = null)
    {
        if ($shippingCompany === null) {
            return $this->performCancellation($awbNumber, $reasonOrShippingCompany, null, $clientOrderId);
        }

        $reason = is_string($reasonOrShippingCompany) ? $reasonOrShippingCompany : null;

        return $this->performCancellation($awbNumber, $shippingCompany, $reason, $clientOrderId);
    }

    public function generateLabel($awbNumber, $shippingCompany)
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);

        $candidates = [
            ['/v3/clients/orders/label/', ['awb_number' => (string) $awbNumber]],
            ['/v2/clients/orders/label/', ['awb_number' => (string) $awbNumber]],
            ['/v1/clients/orders/label/', ['awb_number' => (string) $awbNumber]],
            ['/v3/clients/orders/label/', ['request_id' => (string) $awbNumber]],
        ];

        foreach ($candidates as [$path, $payload]) {
            try {
                $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                    ->post($baseUrl . $path, $payload);

                $result = $response->json();
                $labelUrl = $result['label_url'] ?? ($result['data']['label_url'] ?? null);

                if ($response->successful() && $labelUrl) {
                    return [
                        'success' => true,
                        'response' => $result,
                        'status' => $response->status(),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Shadowfax label request failed', [
                    'awb_number' => $awbNumber,
                    'path' => $path,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => false,
            'response' => null,
            'message' => 'Shadowfax label generation failed.',
        ];
    }

    public function updateOrder($awbNumber, $shippingCompany, array $updates = [])
    {
        $baseUrl = $this->getBaseUrl($shippingCompany);
        $headers = $this->getHeaders($shippingCompany);

        $payload = array_merge([
            'awb_number' => (string) $awbNumber,
            'delivery_details' => null,
            'pickup_details' => null,
            'return_details' => null,
            'order_status' => null,
            'payment_mode' => null,
            'cod_amount' => null,
            'actual_weight' => null,
            'volumetric_weight' => null,
        ], $updates);

        try {
            $response = Http::withHeaders($headers)->withOptions(['verify' => false])
                ->post($baseUrl . '/v3/clients/order_update/', $payload);

            $result = $response->json();

            return [
                'success' => $response->successful() && !str_contains(strtolower((string) $this->extractMessage($result)), 'failure'),
                'message' => $this->extractMessage($result) ?: 'Shadowfax order update request processed.',
                'response' => $result,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
