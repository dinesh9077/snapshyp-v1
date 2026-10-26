<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Models\Order;
use App\Models\StatusMapping;
use App\Models\NdrDetail;
use App\Services\WebhookService;

class WebhookController extends Controller
{
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

            // Same Shadowfax status mapping as the status-sync cron
            $normalizedStatus = \App\Services\ShadowfaxService::normalizeStatus($newStatus);

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

                // The seller asked to cancel on Shopify too: unfulfil + cancel it now that the courier confirmed
                if ($order->cancel_on_channel) {
                    $order->update(['cancel_on_channel' => false]);
                    app(\App\Services\ShopifyOrderSync::class)->cancel($order, 'Cancelled in the shipping panel, confirmed by Shadowfax');
                }
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



