<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Models\Order;
use App\Models\StatusMapping;
use App\Models\NdrRequest;
use App\Models\NdrDetail;
use App\Services\WebhookService;

class WebhookController extends Controller
{
    public function delhiveryWebhook(Request $request)
    {
        // 1) Basic logging (optional but recommended)
        Log::info('Delhivery webhook received', [
            'payload' => $request->all(),
        ]);

        // 2) Extract fields safely

        $awbNumber = $request->input('Shipment.AWB');

        $shipStatus = $request->input('Shipment.Status.Status');
        $instructions = $request->input('Shipment.Status.Instructions'); // not used now but kept if needed
        $statusType = $request->input('Shipment.Status.StatusType');
        $today = now()->toDateString();

        if (empty($awbNumber)) {
            Log::warning('Delhivery webhook: missing AWB number', ['payload' => $request->all()]);
            return response('Missing AWB', 400);
        }

        // 3) Find order
        $order = Order::where('awb_number', $awbNumber)->first();

        if (!$order) {
            Log::warning('Delhivery webhook: order not found for AWB', ['awb' => $awbNumber]);
            return response('Order not found', 200); // return 200 so Delhivery doesn’t keep retrying forever
        }

        $oldStatus = $order->status_courier;
        $newStatus = $shipStatus ?? $oldStatus;

        // 4) Map RT / RTO types to friendly codes
        if (in_array(strtolower($newStatus), ["manifested", "not picked", "not picked up", "out for pickup", "pending pickup"])) {
            return response('No change', 200);
        }

        $statusType = $statusType ?? '';

        $newStatus = match ($statusType) {
            'RT' => 'rto',
            'RT-IT' => 'rto in transit',
            'RT-DL' => 'rto delivered',
            default => $shipStatus,
        };

        if (empty($newStatus)) {
            Log::info('Delhivery webhook: empty status, ignoring', ['awb' => $awbNumber]);
            return response('No status', 200);
        }

        // Normalize status for internal usage
        $normalizedStatus = strtolower(trim($newStatus));
        if ($normalizedStatus === 'shipment delivered') {
            $normalizedStatus = 'delivered';
        }

        // 5) If status has not changed, do nothing
        if (strtolower($oldStatus) === $normalizedStatus) {
            return response('No change', 200);
        }

        // A late or replayed scan must not move an order out of a final state (e.g. delivered -> in transit)
        if ($this->isFinalStatus($oldStatus)) {
            Log::info('Courier webhook ignored: order already in final status', ['order_id' => $order->id, 'old' => $oldStatus, 'new' => $normalizedStatus]);
            return response('Final status, ignored', 200);
        }

        // 6) Load status mappings into convenient structure
        $statusGroups = $this->loadStatusGroups();

        $updateData = ['status_courier' => $normalizedStatus];

        // mark delivery date if delivered
        if ($normalizedStatus === 'delivered') {
            $updateData['delivery_date'] = $today;
        }

        // 7) Queue WhatsApp notifications based on group membership
        try {
            // Delivered
            if ($normalizedStatus === 'delivered') {
                $this->queueWhatsappIfNotSent($order, 'delivered', 'delivered');
            }

            // Out for delivery
            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'Out For Delivery')) {
                $this->queueWhatsappIfNotSent($order, 'out_for_delivery', 'out_for_delivery');
            }

            // In transit / reached hub
            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'In Transit')) {
                $this->queueWhatsappIfNotSent($order, 'in_transit__reached_hub', 'in_transit__reached_hub');
            }

            // RTO
            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
                $this->queueWhatsappIfNotSent($order, 'rto_return_to_origin', 'rto_return_to_origin');
            }
        } catch (\Throwable $e) {
            Log::error('Delhivery webhook: WhatsApp notification error', [
                'awb' => $awbNumber,
                'status' => $normalizedStatus,
                'message' => $e->getMessage(),
            ]);
        }

        // 8) Update order once
        $order->update($updateData);
        $order = $order->fresh();
        $this->triggerRtoSettlementIfNeeded($order, $normalizedStatus, $statusGroups);
        $this->dispatchStatusWebhooks($order, $oldStatus, $normalizedStatus, 'delhivery_webhook');

        // --- NDR detection & persistence (additive - won't interrupt existing flow) ---
        try {
            $data = $request->all();
            $shipment = data_get($data, 'Shipment', null);

            if ($shipment) {
                $awb = data_get($shipment, 'AWB');
                $nsl = data_get($shipment, 'NSLCode');
                $status = data_get($shipment, 'Status.Status');
                $instructions = data_get($shipment, 'Status.Instructions') ?? $status;


                $reAttemptNsl = ["EOD-74", "EOD-15", "EOD-104", "EOD-43", "EOD-86", "EOD-11", "EOD-69", "EOD-6"];
                $pickupRescheduleNsl = ["EOD-777", "EOD-21"];

                $action = null;
                if (in_array($nsl, $reAttemptNsl, true)) {
                    $action = 'RE-ATTEMPT';
                } elseif (in_array($nsl, $pickupRescheduleNsl, true)) {
                    $action = 'PICKUP_RESCHEDULE';
                }

                if ($action) {
                    $attempts = NdrRequest::where('awb_number', $awb)->count() ?? 0;
                    NdrDetail::updateOrCreate(
                        [
                            'awb_number' => $awb,
                            'order_id' => $order->id
                        ],
                        [
                            'shipping_id' => $order->shipping_company_id,
                            'ndr_date' => now()->toDateString(),
                            'remarks' => $instructions,
                            'total_attempts' => $attempts ?? 0,
                            'status' => $status,   // pending / submitted / etc
                            'action' => $action,   // RE-ATTEMPT / PICKUP_RESCHEDULE
                            'response' => $shipment,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            \Log::error('NDR detection error', ['error' => $e->getMessage(), 'payload' => $data ?? null]);
        }

        return response('OK', 200);
    }

    protected function loadStatusGroups(): array
    {
        $groups = [];

        StatusMapping::orderBy('position')->get()->each(function ($row) use (&$groups) {
            $internal = $row->internal_status;
            $courier = $row->courier_status;

            $groups[$internal] = $this->normalizeCourierStatusList($courier);
        });

        return $groups;
    }

    /**
     * Normalize mapping value into an array of lowercase strings.
     * Accepts array, JSON string, or comma-separated string.
     */
    protected function normalizeCourierStatusList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn($v) => strtolower(trim($v)),
                $value
            )));
        }

        if (is_string($value)) {
            // Try JSON decode first
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter(array_map(
                    fn($v) => strtolower(trim($v)),
                    $decoded
                )));
            }

            // Fallback to comma-separated
            return array_values(array_filter(array_map(
                fn($v) => strtolower(trim($v)),
                explode(',', $value)
            )));
        }

        return [];
    }

    /**
     * Check if a status belongs to a given internal group.
     */
    protected function statusInGroup(string $status, array $groups, string $groupKey): bool
    {
        if (!array_key_exists($groupKey, $groups)) {
            return false;
        }

        $status = strtolower(trim($status));

        return in_array($status, $groups[$groupKey], true);
    }

    protected function queueWhatsappIfNotSent(Order $order, string $notifyKey, string $templateSlug): void
    {
        // Ensure send_whatsapp_notify is treated as array
        $already = $order->send_whatsapp_notify;

        if (!is_array($already)) {
            $already = $already
                ? json_decode($already, true)
                : [];
        }

        if (!is_array($already)) {
            $already = [];
        }

        if (in_array($notifyKey, $already, true)) {
            return; // already notified for this status
        }

        // Queue a closure – in real life, better to use a dedicated Job class
        dispatch(function () use ($order, $notifyKey, $templateSlug) {
            try {
                $whatsappService = app(\App\Services\WhatsappService::class);
                $whatsappService->sendOrderNotification($order->fresh(), $templateSlug);

                // Reload and update the notification log
                $reloaded = $order->fresh();

                $notified = $reloaded->send_whatsapp_notify;
                if (!is_array($notified)) {
                    $notified = $notified
                        ? json_decode($notified, true)
                        : [];
                }
                if (!is_array($notified)) {
                    $notified = [];
                }

                $notified[] = $notifyKey;

                $reloaded->update([
                    'send_whatsapp_notify' => $notified,
                ]);
            } catch (\Throwable $e) {
                Log::error('WhatsApp notification dispatch failed', [
                    'order_id' => $order->id,
                    'notifyKey' => $notifyKey,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * UrbaneBolt Webhook Handler
     * Receives real-time shipment status updates from UrbaneBolt
     */
    public function urbaneboltWebhook(Request $request)
    {
        $payload = $request->all();

        // 1) Log incoming webhook
        Log::info('UrbaneBolt webhook received', [
            'payload' => $payload,
        ]);

        // UrbaneBolt sends an array of status update objects
        $updates = is_array($payload) && isset($payload[0]) ? $payload : [$payload];

        foreach ($updates as $data) {
            $awbNumber = $data['awb'] ?? $data['awb_number'] ?? $data['waybill'] ?? $data['tracking_number'] ?? null;
            $status = $data['status_description'] ?? $data['status'] ?? $data['shipment_status'] ?? null;
            $remarks = $data['remarks'] ?? $data['message'] ?? '';
            $today = now()->toDateString();

            if (empty($awbNumber)) {
                Log::warning('UrbaneBolt webhook: missing AWB number', ['payload' => $data]);
                continue;
            }

            $order = Order::where('awb_number', $awbNumber)->first();

            if (!$order) {
                Log::warning('UrbaneBolt webhook: order not found for AWB', ['awb' => $awbNumber]);
                continue;
            }

            $oldStatus = $order->status_courier;
            $newStatus = $status ?? $oldStatus;

            if (in_array(strtolower($newStatus), ["manifested", "not picked", "not picked up", "out for pickup", "pending pickup"])) {
                continue;
            }

            $normalizedStatus = strtolower(trim($newStatus));

            $statusMapping = [
                'shipment delivered' => 'delivered',
                'delivered' => 'delivered',
                'out for delivery' => 'out for delivery',
                'in transit' => 'in transit',
                'reached hub' => 'in transit',
                'picked up' => 'picked up',
                'rto initiated' => 'rto',
                'rto in transit' => 'rto in transit',
                'rto delivered' => 'rto delivered',
                'cancelled' => 'cancelled',
                'lost' => 'lost',
                'damaged' => 'damaged',
            ];

            $normalizedStatus = $statusMapping[$normalizedStatus] ?? $normalizedStatus;

            if (strtolower($oldStatus) === $normalizedStatus) {
                continue;
            }

            // A late or replayed scan must not move an order out of a final state
            if ($this->isFinalStatus($oldStatus)) {
                continue;
            }

            $statusGroups = $this->loadStatusGroups();

            $updateData = ['status_courier' => $normalizedStatus];

            if ($normalizedStatus === 'delivered') {
                $updateData['delivery_date'] = $today;
            }

            try {
                if ($normalizedStatus === 'delivered') {
                    $this->queueWhatsappIfNotSent($order, 'delivered', 'delivered');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'Out For Delivery')) {
                    $this->queueWhatsappIfNotSent($order, 'out_for_delivery', 'out_for_delivery');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'In Transit')) {
                    $this->queueWhatsappIfNotSent($order, 'in_transit__reached_hub', 'in_transit__reached_hub');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
                    $this->queueWhatsappIfNotSent($order, 'rto_return_to_origin', 'rto_return_to_origin');
                }
            } catch (\Throwable $e) {
                Log::error('UrbaneBolt webhook: WhatsApp notification error', [
                    'awb' => $awbNumber,
                    'status' => $normalizedStatus,
                    'message' => $e->getMessage(),
                ]);
            }

            $order->update($updateData);
            $order = $order->fresh();
            $this->triggerRtoSettlementIfNeeded($order, $normalizedStatus, $statusGroups);
            $this->dispatchStatusWebhooks($order, $oldStatus, $normalizedStatus, 'urbanebolt_webhook');

            try {
                $ndrStatus = $data['ndr_status'] ?? null;
                $ndrAction = $data['ndr_action'] ?? null;

                if ($ndrStatus || $ndrAction) {
                    $attempts = NdrRequest::where('awb_number', $awbNumber)->count() ?? 0;

                    NdrDetail::updateOrCreate(
                        [
                            'awb_number' => $awbNumber,
                            'order_id' => $order->id
                        ],
                        [
                            'shipping_id' => $order->shipping_company_id,
                            'ndr_date' => now()->toDateString(),
                            'remarks' => $remarks,
                            'total_attempts' => $attempts ?? 0,
                            'status' => $normalizedStatus,
                            'action' => $ndrAction ?? 'RE-ATTEMPT',
                            'response' => $data,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::error('UrbaneBolt NDR detection error', [
                    'error' => $e->getMessage(),
                    'payload' => $data
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Xpressbees Webhook Handler
     * Receives real-time shipment status updates from Xpressbees
     */
    public function xpressbeesWebhook(Request $request)
    {
        $payload = $request->all();
        $rawPayload = $request->getContent(); // Get raw body for hash validation
        $headerSignature = $request->header('X-Hmac-SHA256');

        Log::info('Xpressbees webhook received', [
            'payload' => $payload,
            'signature' => $headerSignature
        ]);

        // 1) Expert Validation: Verify Signature if Secret is configured
        $shippingCompany = \App\Models\ShippingCompany::find(3);
        if ($shippingCompany && !empty($shippingCompany->secret_key) && $headerSignature) {
            $computedSignature = base64_encode(hash_hmac('sha256', $rawPayload, $shippingCompany->secret_key, true));

            if (!hash_equals($headerSignature, $computedSignature)) {
                Log::warning('Xpressbees webhook: signature mismatch', [
                    'provided' => $headerSignature,
                    'computed' => $computedSignature
                ]);
                return response('Unauthorized Signature', 401);
            }
        }

        $awbNumber = $payload['awb_number'] ?? null;
        $status = $payload['status'] ?? null;
        $message = $payload['message'] ?? '';
        $today = now()->toDateString();

        if (empty($awbNumber)) {
            Log::warning('Xpressbees webhook: missing AWB number', ['payload' => $payload]);
            return response('Missing AWB', 400);
        }

        $order = Order::where('awb_number', $awbNumber)->first();

        if (!$order) {
            Log::warning('Xpressbees webhook: order not found for AWB', ['awb' => $awbNumber]);
            return response('Order not found', 200);
        }

        $oldStatus = $order->status_courier;
        $newStatus = $status ?? $oldStatus;
        if (in_array(strtolower($newStatus), ["manifested", "not picked", "not picked up", "out for pickup", "pending pickup"])) {
            return response('No change', 200);
        }

        $normalizedStatus = strtolower(trim($newStatus));

        if ($normalizedStatus === 'shipment delivered') {
            $normalizedStatus = 'delivered';
        }

        if (strtolower($oldStatus) === $normalizedStatus) {
            return response('No change', 200);
        }

        // A late or replayed scan must not move an order out of a final state (e.g. delivered -> in transit)
        if ($this->isFinalStatus($oldStatus)) {
            Log::info('Courier webhook ignored: order already in final status', ['order_id' => $order->id, 'old' => $oldStatus, 'new' => $normalizedStatus]);
            return response('Final status, ignored', 200);
        }

        $statusGroups = $this->loadStatusGroups();

        $updateData = ['status_courier' => $normalizedStatus];

        if ($normalizedStatus === 'delivered') {
            $updateData['delivery_date'] = $today;
        }

        try {
            if ($normalizedStatus === 'delivered') {
                $this->queueWhatsappIfNotSent($order, 'delivered', 'delivered');
            }

            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'Out For Delivery')) {
                $this->queueWhatsappIfNotSent($order, 'out_for_delivery', 'out_for_delivery');
            }

            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'In Transit')) {
                $this->queueWhatsappIfNotSent($order, 'in_transit__reached_hub', 'in_transit__reached_hub');
            }

            if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
                $this->queueWhatsappIfNotSent($order, 'rto_return_to_origin', 'rto_return_to_origin');
            }
        } catch (\Throwable $e) {
            Log::error('Xpressbees webhook: WhatsApp notification error', [
                'awb' => $awbNumber,
                'status' => $normalizedStatus,
                'message' => $e->getMessage(),
            ]);
        }

        $order->update($updateData);
        $order = $order->fresh();
        $this->triggerRtoSettlementIfNeeded($order, $normalizedStatus, $statusGroups);
        $this->dispatchStatusWebhooks($order, $oldStatus, $normalizedStatus, 'xpressbees_webhook');

        // Expert NDR Detection Logic
        try {
            // We only process NDR if the status is NOT a known success status
            $successStatuses = ['delivered', 'picked up', 'in transit', 'manifested', 'out for delivery'];

            if (!in_array($normalizedStatus, $successStatuses)) {
                $ndrKeywords = ['failed', 'undelivered', 'refused', 'incorrect', 're-attempt', 'reattempt', 'untraceable', 'held'];
                $isNdr = false;

                foreach ($ndrKeywords as $keyword) {
                    if (str_contains($normalizedStatus, $keyword) || str_contains(strtolower($message), $keyword)) {
                        $isNdr = true;
                        break;
                    }
                }

                if ($isNdr) {
                    $attempts = NdrRequest::where('awb_number', $awbNumber)->count() ?? 0;

                    NdrDetail::updateOrCreate(
                        [
                            'awb_number' => $awbNumber,
                            'order_id' => $order->id
                        ],
                        [
                            'shipping_id' => $order->shipping_company_id,
                            'ndr_date' => now()->toDateString(),
                            'remarks' => trim($message . " " . ($payload['location'] ?? '')),
                            'total_attempts' => $attempts,
                            'status' => $normalizedStatus,
                            'action' => 'RE-ATTEMPT',
                            'response' => json_encode($payload),
                        ]
                    );

                    Log::info("NDR captured for Xpressbees", ['awb' => $awbNumber, 'status' => $normalizedStatus]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Xpressbees NDR detection error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }

        return response('OK', 200);
    }
    /**
     * Shipmozo Webhook Handler
     */
    public function shipmozoWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Shipmozo webhook received', ['payload' => $payload]);
        $updates = (is_array($payload) && array_keys($payload) === range(0, count($payload) - 1))
            ? $payload
            : [$payload];

        foreach ($updates as $data) {
            $awbNumber = $data['awb_number']
                ?? $data['awb']
                ?? $data['tracking_number']
                ?? $data['waybill']
                ?? $data['lr_no']
                ?? data_get($data, 'status_feed.scan.0.awb_number')
                ?? data_get($data, 'status_feed.scan.0.awb')
                ?? null;

            $orderRef = $data['order_id']
                ?? $data['ref_order_id']
                ?? data_get($data, 'order.order_id')
                ?? null;

            $statusRaw = $data['current_status']
                ?? $data['status']
                ?? $data['shipment_status']
                ?? $data['webparex_status']
                ?? $data['courier_status']
                ?? data_get($data, 'status_feed.scan.0.status')
                ?? data_get($data, 'status_feed.scan.0.status_description')
                ?? null;

            $today = now()->toDateString();

            $order = null;
            if (!empty($awbNumber)) {
                $order = Order::where('awb_number', $awbNumber)->first();
            }

            // Fallback: ShipMozo may send only order reference and not AWB.
            if (!$order && !empty($orderRef)) {
                $order = Order::where('order_prefix', $orderRef)
                    ->orWhere('order_manual_id', $orderRef)
                    ->orWhere('shipment_id', $orderRef)
                    ->first();
            }

            if (!$order) {
                Log::warning('Shipmozo webhook: order not found', [
                    'awb' => $awbNumber,
                    'order_ref' => $orderRef,
                    'payload' => $data
                ]);
                continue;
            }

            if (empty($awbNumber)) {
                $awbNumber = $order->awb_number;
            }

            $oldStatus = (string) $order->status_courier;
            $newStatus = (string) ($statusRaw ?? $oldStatus);

            // Normalize separators from provider values like OUT_FOR_DELIVERY.
            $normalizedStatus = strtolower(trim(str_replace(['_', '-'], ' ', $newStatus)));
            $normalizedStatus = preg_replace('/\s+/', ' ', $normalizedStatus);

            // Normalize specific Shipmozo statuses to standard system statuses
            $statusMapping = [
                'shipment delivered' => 'delivered',
                'delivered to consignee' => 'delivered',
                'delivered' => 'delivered',
                'out for delivery' => 'out for delivery',
                'shipment received at facility' => 'in transit',
                'reached hub' => 'in transit',
                'in transit' => 'in transit',
                'shipment picked up' => 'picked up',
                'picked up' => 'picked up',
                'pickup scheduled' => 'pickup scheduled',
                'out for pickup' => 'out for pickup',
                'rto initiated' => 'rto',
                'rto in transit' => 'rto in transit',
                'rto delivered' => 'rto delivered',
                'cancelled' => 'cancelled',
                'lost' => 'lost',
                'damaged' => 'damaged',
                'manifested' => 'manifested',
                'manifested at origin' => 'manifested',
                'pickup pending' => 'pending pickup',
                'not picked' => 'pending pickup',
                'not picked up' => 'pending pickup',
            ];

            if (array_key_exists($normalizedStatus, $statusMapping)) {
                $normalizedStatus = $statusMapping[$normalizedStatus];
            }

            if (empty($normalizedStatus)) {
                Log::warning('Shipmozo webhook: empty status after normalization', ['payload' => $data]);
                continue;
            }

            $oldNormalized = strtolower(trim(str_replace(['_', '-'], ' ', $oldStatus)));
            $oldNormalized = preg_replace('/\s+/', ' ', $oldNormalized);

            if ($oldNormalized === $normalizedStatus) {
                continue;
            }

            // A late or replayed scan must not move an order out of a final state
            if ($this->isFinalStatus($oldStatus)) {
                continue;
            }

            $statusGroups = $this->loadStatusGroups();

            $updateData = ['status_courier' => $normalizedStatus];

            if ($normalizedStatus === 'delivered') {
                $updateData['delivery_date'] = $today;
            }

            try {
                if ($normalizedStatus === 'delivered') {
                    $this->queueWhatsappIfNotSent($order, 'delivered', 'delivered');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'Out For Delivery')) {
                    $this->queueWhatsappIfNotSent($order, 'out_for_delivery', 'out_for_delivery');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'In Transit')) {
                    $this->queueWhatsappIfNotSent($order, 'in_transit__reached_hub', 'in_transit__reached_hub');
                }

                if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
                    $this->queueWhatsappIfNotSent($order, 'rto_return_to_origin', 'rto_return_to_origin');
                }
            } catch (\Throwable $e) {
                Log::error('Shipmozo webhook: WhatsApp notification error', [
                    'awb' => $awbNumber,
                    'status' => $normalizedStatus,
                    'message' => $e->getMessage(),
                ]);
            }

            $order->update($updateData);
            $order = $order->fresh();

            $this->triggerRtoSettlementIfNeeded($order, $normalizedStatus, $statusGroups);

            // Ensure activity log is always captured for status updates from webhook.
            try {
                \Helper::orderActivity($order->id, "Order status updated via Shipmozo webhook: {$oldStatus} -> {$normalizedStatus}");
            } catch (\Throwable $e) {
                Log::error('Shipmozo webhook: orderActivity log failed', [
                    'order_id' => $order->id,
                    'awb' => $awbNumber,
                    'status' => $normalizedStatus,
                    'message' => $e->getMessage(),
                ]);
            }

            // Notify seller webhooks for order status update.
            $this->dispatchStatusWebhooks($order, $oldStatus, $normalizedStatus, 'shipmozo_webhook');

            // NDR Detection
            try {
                $successStatuses = ['delivered', 'picked up', 'in transit', 'manifested', 'out for delivery', 'shipment received at facility', 'pickup scheduled', 'shipment picked up'];

                $isSuccess = false;
                foreach ($successStatuses as $s) {
                    if (str_contains($normalizedStatus, $s)) {
                        $isSuccess = true;
                        break;
                    }
                }

                if (!$isSuccess) {
                    $ndrKeywords = ['failed', 'undelivered', 'refused', 'incorrect', 're-attempt', 'reattempt', 'untraceable', 'held', 'rto'];
                    $isNdr = false;

                    foreach ($ndrKeywords as $keyword) {
                        if (str_contains($normalizedStatus, $keyword)) {
                            $isNdr = true;
                            break;
                        }
                    }

                    if ($isNdr) {
                        $attempts = NdrRequest::where('awb_number', $awbNumber)->count() ?? 0;
                        $remarks = data_get($data, 'status_feed.scan.0.status') ?? $normalizedStatus;
                        if (data_get($data, 'status_feed.scan.0.location')) {
                            $remarks .= ' - ' . data_get($data, 'status_feed.scan.0.location');
                        }

                        NdrDetail::updateOrCreate(
                            [
                                'awb_number' => $awbNumber,
                                'order_id' => $order->id
                            ],
                            [
                                'shipping_id' => $order->shipping_company_id,
                                'ndr_date' => now()->toDateString(),
                                'remarks' => $remarks,
                                'total_attempts' => $attempts,
                                'status' => $normalizedStatus,
                                'action' => 'RE-ATTEMPT',
                                'response' => json_encode($data),
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Shipmozo NDR detection error', [
                    'error' => $e->getMessage(),
                    'payload' => $data
                ]);
            }
        }

        return response('OK', 200);
    }
    /**
     * Shadowfax Webhook Handler
     */
    public function shadowfaxWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Shadowfax webhook received', ['payload' => $payload]);

        // Shadowfax sends either a single object or an array
        $updates = (is_array($payload) && isset($payload[0])) ? $payload : [$payload];

        foreach ($updates as $data) {
            $awbNumber = $data['awb'] ?? $data['awb_number'] ?? null;
            $statusRaw = $data['event'] ?? $data['status'] ?? $data['current_status'] ?? null;
            $remarks = $data['comments'] ?? $data['remarks'] ?? $data['remark'] ?? '';
            $location = $data['current_location'] ?? $data['location'] ?? $data['city'] ?? '';
            $today = now()->toDateString();

            if (empty($awbNumber)) {
                Log::warning('Shadowfax webhook: missing AWB number', ['payload' => $data]);
                continue;
            }

            $order = Order::where('awb_number', $awbNumber)->first();
            if (!$order) {
                Log::warning('Shadowfax webhook: order not found for AWB', ['awb' => $awbNumber]);
                continue;
            }

            $oldStatus = $order->status_courier;
            $newStatus = $statusRaw ?? $oldStatus;

            // Normalize status strings
            $normalizedStatus = strtolower(trim($newStatus));

            $statusMapping = [
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

            $normalizedStatus = $statusMapping[$normalizedStatus] ?? $normalizedStatus;

            if (strtolower($oldStatus) === $normalizedStatus) {
                continue;
            }

            // A late or replayed scan must not move an order out of a final state
            if ($this->isFinalStatus($oldStatus)) {
                continue;
            }

            $statusGroups = $this->loadStatusGroups();
            $updateData = ['status_courier' => $normalizedStatus];

            if ($normalizedStatus === 'delivered') {
                $updateData['delivery_date'] = $today;
            }

            // Queue notifications
            // try {
            //     if ($normalizedStatus === 'delivered') {
            //         $this->queueWhatsappIfNotSent($order, 'delivered', 'delivered');
            //     }
            //     if ($this->statusInGroup($normalizedStatus, $statusGroups, 'Out For Delivery')) {
            //         $this->queueWhatsappIfNotSent($order, 'out_for_delivery', 'out_for_delivery');
            //     }
            //     if ($this->statusInGroup($normalizedStatus, $statusGroups, 'In Transit')) {
            //         $this->queueWhatsappIfNotSent($order, 'in_transit__reached_hub', 'in_transit__reached_hub');
            //     }
            //     if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
            //         $this->queueWhatsappIfNotSent($order, 'rto_return_to_origin', 'rto_return_to_origin');
            //     }
            // } catch (\Throwable $e) {
            //     Log::error('Shadowfax webhook: Notification error', ['awb' => $awbNumber, 'error' => $e->getMessage()]);
            // }

            $order->update($updateData);
            $order = $order->fresh();

            // A queued cancellation was not refunded at cancel time; refund once Shadowfax confirms it
            if (strtolower(trim((string) $oldStatus)) === 'cancel requested' && $normalizedStatus === 'cancelled') {
                $this->refundConfirmedCancellation($order);
            }

            $this->triggerRtoSettlementIfNeeded($order, $normalizedStatus, $statusGroups);
            $this->dispatchStatusWebhooks($order, $oldStatus, $normalizedStatus, 'shadowfax_webhook');

            // NDR logic (simplified)
            if (!in_array($normalizedStatus, ['delivered', 'picked up', 'in transit', 'manifested'])) {
                if (str_contains($normalizedStatus, 'failed') || str_contains($normalizedStatus, 'attempt')) {
                    NdrDetail::updateOrCreate(
                        ['awb_number' => $awbNumber, 'order_id' => $order->id],
                        [
                            'shipping_id' => $order->shipping_company_id,
                            'ndr_date' => now()->toDateString(),
                            'remarks' => $remarks . ($location ? " - " . $location : ""),
                            'status' => $normalizedStatus,
                            'action' => 'RE-ATTEMPT',
                            'response' => json_encode($data),
                        ]
                    );
                }
            }
        }

        return response('OK', 200);
    }
    protected function triggerRtoSettlementIfNeeded(Order $order, string $normalizedStatus, array $statusGroups): void
    {
        if ($this->statusInGroup($normalizedStatus, $statusGroups, 'RTO All')) {
            Artisan::call('order:apply-rto-charge', ['order_id' => $order->id]);
        }
    }

    // Refund the seller's shipping charge (once) when the courier confirms a queued cancellation
    protected function refundConfirmedCancellation(Order $order): void
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
                $user = \App\Models\User::whereKey($order->user_id)->lockForUpdate()->first();
                $amount = (float) ($order->shipping_charge ?? 0);
                if (!$user || $user->role !== 'user' || $amount <= 0) {
                    return;
                }

                $alreadyRefunded = \App\Models\Billing::where('billing_type', 'Order')
                    ->where('billing_type_id', $order->id)
                    ->where('transaction_type', 'credit')
                    ->where('note', 'like', '%cancellation confirmed%')
                    ->exists();
                if ($alreadyRefunded) {
                    return;
                }

                $user->increment('wallet_amount', $amount);
                \App\Models\Billing::create([
                    'user_id' => $user->id,
                    'billing_type' => 'Order',
                    'billing_type_id' => $order->id,
                    'transaction_type' => 'credit',
                    'amount' => $amount,
                    'note' => 'Shipping charges refunded, cancellation confirmed by courier for AWB: ' . $order->awb_number,
                ]);
                app(\App\Services\ShipmentProcessor::class)->reverseShippingCredits($order);
            });
        } catch (\Throwable $e) {
            Log::error('Refund on confirmed cancellation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    // Statuses an order never leaves once reached
    protected function isFinalStatus(?string $status): bool
    {
        $status = strtolower(trim(str_replace(['_', '-'], ' ', (string) $status)));
        return in_array($status, ['delivered', 'cancelled', 'rto delivered', 'lost', 'damaged'], true);
    }

    protected function dispatchStatusWebhooks(Order $order, string $oldStatus, string $newStatus, string $source): void
    {
        try {
            \Helper::orderActivity($order->id, "Order status updated: {$oldStatus} -> {$newStatus} ({$source})");
        } catch (\Throwable $e) {
            Log::error('Order activity log failed for status update', [
                'order_id' => $order->id,
                'source' => $source,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $service = app(WebhookService::class);
            $service->dispatch((int) $order->user_id, 'order.updated', $order);
            $service->dispatch((int) $order->user_id, 'order.status_changed', $order, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'source' => $source,
            ]);
        } catch (\Throwable $e) {
            Log::error('Seller webhook dispatch failed', [
                'order_id' => $order->id,
                'source' => $source,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $e->getMessage(),
            ]);
        }
    }
}



