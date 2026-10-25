<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookReceiverController extends Controller
{
    public function handle(Request $request)
    {
        $rawBody  = $request->getContent();
        $event    = $request->header('X-Webhook-Event', 'unknown');
        $received = $request->header('X-Webhook-Signature', '');
        $data     = $request->json()->all();
        $order    = $data['order'] ?? [];

        switch ($event) {

            case 'order.created':
                Log::info('Webhook: order.created received', [
                    'order_id'       => $order['id']             ?? null,
                    'order_prefix'   => $order['order_prefix']   ?? null,
                    'order_type'     => $order['order_type']      ?? null,
                    'status'         => $order['status']          ?? null,
                    'cod_amount'     => $order['cod_amount']      ?? null,
                    'invoice_amount' => $order['invoice_amount']  ?? null,
                    'customer'       => $order['customer']        ?? null,
                    'address'        => $order['address']         ?? null,
                    'items'          => $order['items']           ?? [],
                ]);
                break;

            case 'order.updated':
                Log::info('Webhook: order.updated received', [
                    'order_id'       => $order['id']             ?? null,
                    'order_prefix'   => $order['order_prefix']   ?? null,
                    'status'         => $order['status']         ?? null,
                    'awb_number'     => $order['awb_number']     ?? null,
                    'courier_name'   => $order['courier_name']   ?? null,
                    'cod_amount'     => $order['cod_amount']      ?? null,
                    'invoice_amount' => $order['invoice_amount']  ?? null,
                    'customer'       => $order['customer']        ?? null,
                    'address'        => $order['address']         ?? null,
                    'items'          => $order['items']           ?? [],
                ]);
                break;

            case 'order.status_changed':
                Log::info('Webhook: order.status_changed received', [
                    'order_id'       => $order['id']             ?? null,
                    'order_prefix'   => $order['order_prefix']   ?? null,
                    'status'         => $order['status']         ?? null,
                    'meta'           => $data['meta']            ?? null,
                ]);
                break;

            case 'order.deleted':
                Log::info('Webhook: order.deleted received', [
                    'order_id'       => $order['id']             ?? null,
                    'order_prefix'   => $order['order_prefix']   ?? null,
                    'status'         => $order['status']         ?? null,
                    'customer'       => $order['customer']        ?? null,
                ]);
                break;

            case 'webhook.test':
                Log::info('Webhook: test ping received', ['timestamp' => $data['timestamp'] ?? now()->toIso8601String()]);
                break;

            default:
                Log::info('Webhook: unknown event received', ['event' => $event, 'data' => $data]);
        }

        return response()->json(['status' => 'ok', 'event' => $event], 200);
    }
}
