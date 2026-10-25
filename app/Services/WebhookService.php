<?php

namespace App\Services;

use App\Jobs\RetryUserWebhook;
use App\Models\Order;
use App\Models\UserWebhook;
use App\Models\UserWebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    const EVENTS = [
        'order.created' => 'Order Created',
        'order.updated' => 'Order Updated',
        'order.status_changed' => 'Order Status Changed',
        'order.deleted' => 'Order Deleted / Cancelled',
    ];

    /** Total delivery attempts (1 immediate + MAX_ATTEMPTS-1 queued retries). */
    const MAX_ATTEMPTS = 3;

    /** Seconds to wait before retry #2 and #3. */
    const RETRY_BACKOFF = [60, 300];

    /**
     * Where the current status change originated (e.g. 'shadowfax_webhook').
     * Set this immediately before persisting a status change so the observer can
     * include it in the payload meta. It is consumed and reset on read.
     */
    public static ?string $source = null;

    public static function consumeSource(): string
    {
        $source = self::$source ?? 'system';
        self::$source = null;
        return $source;
    }

    /**
     * When true the observer stops firing. Used around bulk operations, where one
     * inline HTTP POST per row would otherwise stall the request for minutes.
     */
    public static bool $muted = false;

    /** Run a callback with webhook delivery suppressed. */
    public static function muted(callable $callback)
    {
        $previous = self::$muted;
        self::$muted = true;

        try {
            return $callback();
        } finally {
            self::$muted = $previous;
        }
    }

    /**
     * The single webhook registered by a merchant, or null when none is configured.
     */
    public function webhookFor(int $userId): ?UserWebhook
    {
        return UserWebhook::where('user_id', $userId)->first();
    }

    /**
     * Deliver an event to the merchant's webhook.
     *
     * No webhook configured, webhook disabled, or event not subscribed → skip silently.
     */
    public function dispatch(int $userId, string $event, Order $order, array $meta = []): void
    {
        if (self::$muted) {
            return;
        }

        $webhook = $this->webhookFor($userId);

        if (!$webhook || (int) $webhook->status !== 1) {
            return;
        }

        if (!in_array($event, $webhook->events ?? [], true)) {
            return;
        }

        $this->deliver($webhook, $event, $this->buildPayload($event, $order, $meta), 1);
    }

    public function buildPayload(string $event, Order $order, array $meta = []): array
    {
        $order->loadMissing(['customer', 'customerAddress', 'orderItems', 'warehouse']);

        $customer = $order->customer;
        $address  = $order->customerAddress;
        $items    = $order->orderItems->map(fn($item) => [
            'product_name'     => $item->product_name,
            'product_category' => $item->product_category,
            'sku_number'       => $item->sku_number,
            'amount'           => $item->amount,
            'quantity'         => $item->quantity,
        ])->toArray();

        $payload = [
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'order'     => [
                'id'             => $order->id,
                'order_prefix'   => $order->order_prefix,
                'order_type'     => $order->order_type,
                'status'         => $order->status_courier,
                'awb_number'     => $order->awb_number,
                'lr_no'          => $order->lr_no,
                'shipment_id'    => $order->shipment_id,
                'courier_name'   => $order->courier_name,
                'shipping_mode'  => $order->shipping_mode,
                'shipping_charge' => $order->shipping_charge,
                'cod_amount'     => $order->cod_amount,
                'invoice_amount' => $order->invoice_amount,
                'weight'         => $order->weight,
                'created_at'     => (string) $order->created_at,
                'updated_at'     => (string) $order->updated_at,
                'customer'       => $customer ? [
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'mobile'     => $customer->mobile,
                    'email'      => $customer->email,
                ] : null,
                'address' => $address ? [
                    'address'  => $address->address,
                    'city'     => $address->city     ?? null,
                    'state'    => $address->state    ?? null,
                    'country'  => $address->country  ?? null,
                    'zip_code' => $address->zip_code ?? null,
                ] : null,
                'pickup' => $order->warehouse ? [
                    'warehouse_name' => $order->warehouse->warehouse_name,
                    'city'           => $order->warehouse->city,
                    'state'          => $order->warehouse->state,
                    'zip_code'       => $order->warehouse->zip_code,
                ] : null,
                'items' => $items,
            ],
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return $payload;
    }

    /**
     * POST the payload and record the attempt. A failed attempt schedules the next
     * one on the queue, up to MAX_ATTEMPTS.
     */
    public function deliver(UserWebhook $webhook, string $event, array $payload, int $attempt): bool
    {
        $body      = json_encode($payload);
        $signature = !empty($webhook->secret_key)
            ? hash_hmac('sha256', $body, $webhook->secret_key)
            : '';

        $startedAt = microtime(true);
        $httpStatus = null;
        $responseBody = null;
        $errorMessage = null;
        $isSuccess = false;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Webhook-Event'     => $event,
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Attempt'   => (string) $attempt,
                    'User-Agent'          => 'Snapshyp-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            $httpStatus   = $response->status();
            $responseBody = substr($response->body(), 0, 500);
            $isSuccess    = $httpStatus >= 200 && $httpStatus < 300;

            if (!$isSuccess) {
                $errorMessage = "Endpoint responded with HTTP {$httpStatus}.";
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        UserWebhookLog::create([
            'webhook_id'    => $webhook->id,
            'user_id'       => $webhook->user_id,
            'event'         => $event,
            'url'           => $webhook->url,
            'payload'       => $payload,
            'status'        => $isSuccess ? 'success' : 'failed',
            'attempt'       => $attempt,
            'max_attempts'  => self::MAX_ATTEMPTS,
            'http_status'   => $httpStatus,
            'duration_ms'   => (int) round((microtime(true) - $startedAt) * 1000),
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
        ]);

        if (!$isSuccess) {
            $this->scheduleRetry($webhook, $event, $payload, $attempt);
        }

        return $isSuccess;
    }

    /**
     * Queue the next attempt with a backoff. Attempt 1 already ran inline, so the
     * webhook still fires in real time even when no queue worker is running — only
     * the retries need one.
     */
    private function scheduleRetry(UserWebhook $webhook, string $event, array $payload, int $attempt): void
    {
        $next = $attempt + 1;

        if ($next > self::MAX_ATTEMPTS) {
            Log::warning('Webhook delivery gave up after all attempts.', [
                'webhook_id' => $webhook->id,
                'user_id' => $webhook->user_id,
                'event' => $event,
                'attempts' => self::MAX_ATTEMPTS,
            ]);
            return;
        }

        $backoff = self::RETRY_BACKOFF;
        $delay = $backoff[$attempt - 1] ?? $backoff[count($backoff) - 1];

        try {
            RetryUserWebhook::dispatch($webhook->id, $event, $payload, $next)
                ->delay(now()->addSeconds($delay));
        } catch (\Throwable $e) {
            // A broken queue must never break the request that triggered the webhook.
            Log::error('Could not queue webhook retry.', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'attempt' => $next,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
