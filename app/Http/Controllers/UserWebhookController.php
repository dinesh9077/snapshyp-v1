<?php

namespace App\Http\Controllers;

use App\Models\UserWebhook;
use App\Models\UserWebhookLog;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * A merchant registers exactly one webhook URL. `store()` therefore creates it the
 * first time and edits it afterwards, so the UI never has to know which it is.
 */
class UserWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function rules(): array
    {
        return [
            'url'      => ['required', 'url', 'max:500', function ($attribute, $value, $fail) {
                if (!\App\Support\PublicUrl::isSafe($value)) {
                    $fail('The webhook URL must be a public http(s) address.');
                }
            }],
            'events'   => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', array_keys(WebhookService::EVENTS)),
        ];
    }

    /** The merchant's webhook, or null when none is registered yet. */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data'   => UserWebhook::where('user_id', Auth::id())->first(),
            'events' => WebhookService::EVENTS,
        ]);
    }

    /** Create the webhook, or update it when one already exists. */
    public function store(Request $request)
    {
        $request->validate($this->rules());

        $webhook = UserWebhook::where('user_id', Auth::id())->first();

        if ($webhook) {
            $webhook->update([
                'url'    => $request->url,
                'events' => $request->events,
            ]);

            return response()->json([
                'status' => 'success',
                'msg'    => 'Webhook updated successfully.',
                'data'   => $webhook->fresh(),
            ]);
        }

        $webhook = UserWebhook::create([
            'user_id'    => Auth::id(),
            'url'        => $request->url,
            'events'     => $request->events,
            'secret_key' => Str::random(40),
            'status'     => 1,
        ]);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Webhook added successfully.',
            'data'   => $webhook,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate($this->rules());

        $webhook = $this->currentWebhook();
        if (!$webhook) {
            return response()->json(['status' => 'error', 'msg' => 'No webhook is registered.'], 404);
        }

        $webhook->update([
            'url'    => $request->url,
            'events' => $request->events,
            'status' => $request->input('status', $webhook->status),
        ]);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Webhook updated successfully.',
            'data'   => $webhook->fresh(),
        ]);
    }

    public function destroy()
    {
        $webhook = $this->currentWebhook();
        if (!$webhook) {
            return response()->json(['status' => 'error', 'msg' => 'No webhook is registered.'], 404);
        }

        $webhook->delete();

        return response()->json(['status' => 'success', 'msg' => 'Webhook deleted successfully.']);
    }

    /** Enable / disable delivery without losing the URL or its secret. */
    public function toggleStatus()
    {
        $webhook = $this->currentWebhook();
        if (!$webhook) {
            return response()->json(['status' => 'error', 'msg' => 'No webhook is registered.'], 404);
        }

        $newStatus = $webhook->status == 1 ? 0 : 1;
        $webhook->update(['status' => $newStatus]);

        return response()->json([
            'status'     => 'success',
            'msg'        => 'Webhook ' . ($newStatus == 1 ? 'enabled' : 'disabled') . '.',
            'new_status' => $newStatus,
        ]);
    }

    public function regenerateSecret()
    {
        $webhook = $this->currentWebhook();
        if (!$webhook) {
            return response()->json(['status' => 'error', 'msg' => 'No webhook is registered.'], 404);
        }

        $newSecret = Str::random(40);
        $webhook->update(['secret_key' => $newSecret]);

        return response()->json([
            'status'     => 'success',
            'msg'        => 'Secret key regenerated.',
            'secret_key' => $newSecret,
        ]);
    }

    /**
     * Send a sample payload so the merchant can verify their endpoint.
     * The attempt is written to the delivery log like any other delivery.
     */
    public function test()
    {
        $webhook = $this->currentWebhook();
        if (!$webhook) {
            return response()->json(['status' => 'error', 'msg' => 'No webhook is registered.'], 404);
        }

        $payload = [
            'event'     => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'message'   => 'This is a test webhook from Snapshyp.',
            'order'     => [
                'id'             => 0,
                'order_prefix'   => 'TEST-001',
                'order_type'     => 'prepaid',
                'status'         => 'New',
                'awb_number'     => 'TEST123456789',
                'courier_name'   => 'Test Courier',
                'invoice_amount' => 500.00,
            ],
        ];

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
                    'X-Webhook-Event'     => 'webhook.test',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Attempt'   => '1',
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

        // A test is never retried — one attempt, recorded either way.
        UserWebhookLog::create([
            'webhook_id'    => $webhook->id,
            'user_id'       => $webhook->user_id,
            'event'         => 'webhook.test',
            'url'           => $webhook->url,
            'payload'       => $payload,
            'status'        => $isSuccess ? 'success' : 'failed',
            'attempt'       => 1,
            'max_attempts'  => 1,
            'http_status'   => $httpStatus,
            'duration_ms'   => (int) round((microtime(true) - $startedAt) * 1000),
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
        ]);

        return response()->json([
            'status'      => $isSuccess ? 'success' : 'error',
            'msg'         => $isSuccess
                ? 'Test webhook delivered. Endpoint responded with HTTP ' . $httpStatus . '.'
                : 'Test webhook failed: ' . ($errorMessage ?? 'Unknown error'),
            'http_status' => $httpStatus,
        ]);
    }

    /**
     * Delivery logs — request payload, response body, HTTP status and attempt number.
     */
    public function logs(Request $request)
    {
        $query = UserWebhookLog::where('user_id', Auth::id())->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate((int) $request->input('per_page', 20)),
        ]);
    }

    private function currentWebhook(): ?UserWebhook
    {
        return UserWebhook::where('user_id', Auth::id())->first();
    }
}
