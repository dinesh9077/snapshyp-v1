<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors a cancellation in our panel onto the seller's Shopify order: first cancels its
 * fulfillments (the order goes back to "unfulfilled"), then cancels the order itself.
 * Never refunds the customer and never throws; the outcome is logged on the order.
 */
class ShopifyOrderSync
{
    public function cancel(Order $order, string $note = 'Cancelled in the shipping panel'): array
    {
        try {
            $shopifyOrderId = $this->shopifyOrderId($order);
            if (!$shopifyOrderId) {
                return ['status' => 'skipped', 'msg' => 'Not a Shopify order.'];
            }

            $user = User::find($order->user_id);
            $shop = ($user && $user->shop) ? Shop::where('shopify_domain', $user->shop)->first() : null;
            if (!$shop) {
                return $this->finish($order, ['status' => 'error', 'msg' => 'Shopify store not connected.']);
            }

            $domain = $shop->shopify_domain;
            $token = $shop->getValidShopifyToken();
            $gid = 'gid://shopify/Order/' . $shopifyOrderId;

            $state = $this->graphql($domain, $token, '
                query orderState($id: ID!) {
                  order(id: $id) { id cancelledAt fulfillments(first: 20) { id status } }
                }', ['id' => $gid]);
            $shopifyOrder = $state['data']['order'] ?? null;
            if (!$shopifyOrder) {
                return $this->finish($order, ['status' => 'error', 'msg' => 'Order not found on Shopify.', 'response' => $state]);
            }
            if (!empty($shopifyOrder['cancelledAt'])) {
                return $this->finish($order, ['status' => 'success', 'msg' => 'Already cancelled on Shopify.']);
            }

            // 1) Fulfillments first: Shopify will not cancel an order whose fulfillments are still active
            $errors = [];
            $unfulfilled = 0;
            foreach ($shopifyOrder['fulfillments'] ?? [] as $fulfillment) {
                if (!in_array(strtoupper((string) $fulfillment['status']), ['SUCCESS', 'OPEN', 'PENDING'], true)) {
                    continue;
                }
                $res = $this->graphql($domain, $token, '
                    mutation fulfillmentCancel($id: ID!) {
                      fulfillmentCancel(id: $id) { fulfillment { id status } userErrors { field message } }
                    }', ['id' => $fulfillment['id']]);
                $userErrors = $res['data']['fulfillmentCancel']['userErrors'] ?? [['message' => 'No response']];
                if (empty($userErrors)) {
                    $unfulfilled++;
                } else {
                    $errors[] = 'Fulfillment: ' . implode('; ', array_column($userErrors, 'message'));
                }
            }

            // 2) The order: restock, no refund, no customer email
            $res = $this->graphql($domain, $token, '
                mutation orderCancel($orderId: ID!, $reason: OrderCancelReason!, $restock: Boolean!, $notifyCustomer: Boolean, $refundMethod: OrderCancelRefundMethodInput, $staffNote: String) {
                  orderCancel(orderId: $orderId, reason: $reason, restock: $restock, notifyCustomer: $notifyCustomer, refundMethod: $refundMethod, staffNote: $staffNote) {
                    job { id done }
                    orderCancelUserErrors { field message code }
                  }
                }', [
                'orderId' => $gid,
                'reason' => 'OTHER',
                'restock' => true,
                'notifyCustomer' => false,
                'refundMethod' => ['originalPaymentMethodsRefund' => false],
                'staffNote' => mb_substr($note . ($order->awb_number ? ' (AWB ' . $order->awb_number . ')' : ''), 0, 255),
            ]);
            $cancelErrors = $res['data']['orderCancel']['orderCancelUserErrors'] ?? null;
            if ($cancelErrors === null) {
                $errors[] = 'Order cancel: ' . json_encode($res['errors'] ?? $res);
            } elseif (!empty($cancelErrors)) {
                $errors[] = 'Order cancel: ' . implode('; ', array_column($cancelErrors, 'message'));
            }

            $summary = ($unfulfilled ? "{$unfulfilled} fulfillment(s) cancelled. " : '')
                . (empty($cancelErrors) && $cancelErrors !== null ? 'Shopify order cancelled.' : 'Shopify order not cancelled.');

            return $this->finish($order, [
                'status' => empty($errors) ? 'success' : 'error',
                'msg' => trim($summary . (empty($errors) ? '' : ' ' . implode(' | ', $errors))),
            ]);
        } catch (\Throwable $e) {
            return $this->finish($order, ['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    private function shopifyOrderId(Order $order): ?string
    {
        $isShopify = ($order->platform_source === 'shopify') || (int) $order->getRawOriginal('is_shopify_order') === 1;
        $id = $order->platform_order_number ?: $order->shopify_order_number;
        return ($isShopify && $id) ? (string) $id : null;
    }

    private function graphql(string $domain, string $token, string $query, array $variables): array
    {
        $apiVersion = config('services.shopify_api_version', '2025-10');
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post("https://{$domain}/admin/api/{$apiVersion}/graphql.json", [
            'query' => $query,
            'variables' => $variables,
        ]);

        return $response->json() ?? ['errors' => 'HTTP ' . $response->status()];
    }

    private function finish(Order $order, array $result): array
    {
        $level = $result['status'] === 'success' ? 'info' : 'error';
        Log::$level('Shopify cancel sync', ['order_id' => $order->id] + $result);
        try {
            Helper::orderActivity($order->id, 'Shopify: ' . $result['msg']);
        } catch (\Throwable $e) {
            // activity log is informational
        }
        return $result;
    }
}
