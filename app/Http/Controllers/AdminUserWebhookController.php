<?php

namespace App\Http\Controllers;

use App\Models\UserWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminUserWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:clients.edit');
    }

    /**
     * List all webhooks for a specific user.
     */
    public function index($user_id)
    {
        $webhooks = UserWebhook::where('user_id', $user_id)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $webhooks]);
    }

    /**
     * Store a new webhook for a specific user.
     */
    public function store(Request $request, $user_id)
    {
        $request->validate([
            'url'      => 'required|url|max:500',
            'events'   => 'required|array|min:1',
            'events.*' => 'in:order.created,order.updated,order.status_changed,order.deleted',
        ]);

        // Exactly one webhook per merchant — edit the existing one instead of adding.
        $webhook = UserWebhook::where('user_id', $user_id)->first();

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
            'user_id'    => $user_id,
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

    /**
     * Update an existing webhook.
     */
    public function update(Request $request, $id)
    {
        $webhook = UserWebhook::findOrFail($id);

        $request->validate([
            'url'      => 'required|url|max:500',
            'events'   => 'required|array|min:1',
            'events.*' => 'in:order.created,order.updated,order.status_changed,order.deleted',
        ]);

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

    /**
     * Delete a webhook.
     */
    public function destroy($id)
    {
        $webhook = UserWebhook::findOrFail($id);
        $webhook->delete();
        return response()->json(['status' => 'success', 'msg' => 'Webhook deleted successfully.']);
    }
}
