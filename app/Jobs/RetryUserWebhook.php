<?php

namespace App\Jobs;

use App\Models\UserWebhook;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-delivers a webhook payload that failed on a previous attempt.
 *
 * Attempt 1 always runs inline in the originating request, so real-time delivery
 * does not depend on a queue worker. Only retries land here. Each run records its
 * own delivery log row and, on failure, WebhookService schedules the next attempt.
 *
 * `tries = 1` on purpose: retry accounting lives in WebhookService so the attempt
 * number stays consistent with what is written to `user_webhook_logs`.
 */
class RetryUserWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    protected int $webhookId;
    protected string $event;
    protected array $payload;
    protected int $attempt;

    public function __construct(int $webhookId, string $event, array $payload, int $attempt)
    {
        $this->webhookId = $webhookId;
        $this->event = $event;
        $this->payload = $payload;
        $this->attempt = $attempt;
    }

    public function handle(WebhookService $service): void
    {
        $webhook = UserWebhook::find($this->webhookId);

        // Deleted or disabled between attempts — stop retrying.
        if (!$webhook || (int) $webhook->status !== 1) {
            return;
        }

        $service->deliver($webhook, $this->event, $this->payload, $this->attempt);
    }
}
