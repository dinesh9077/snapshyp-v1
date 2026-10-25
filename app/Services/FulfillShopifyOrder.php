<?php

namespace App\Services;

use Auth, DB;
use Illuminate\Support\Facades\Http;

class FulfillShopifyOrder
{
    function fulfillOrder($shopDomain, $accessToken, $orderId, $trackingNumber = null, $companyId = null)
    {
        $orderId = "gid://shopify/Order/{$orderId}";
        $apiVersion = config('services.shopify_api_version', '2025-10');
        if (!$companyId) {
            return null;
        }
        $trackingCompany = $this->courierPartner($companyId);

        // 1️⃣ Fetch Fulfillment Orders
        $query = '
                query getFO($orderId: ID!) {
                  order(id: $orderId) {
                    id
                    name
                    fulfillmentOrders(first: 10) {
                      edges {
                        node {
                          id
                          status 
                          lineItems(first: 50) {
                            edges {
                              node {
                                id
                                remainingQuantity
                                lineItem { id name sku quantity }
                              }
                            }
                          }
                          assignedLocation { location { id name } }
                        }
                      }
                    }
                  }
                }
            ';

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://{$shopDomain}/admin/api/{$apiVersion}/graphql.json", [
            'query' => $query,
            'variables' => ['orderId' => $orderId]
        ]);

        $data = $response->json();

        $fulfillmentOrders = $data['data']['order']['fulfillmentOrders']['edges'] ?? [];

        if (empty($fulfillmentOrders)) {
            return ['error' => 'No fulfillment orders found for this order'];
        }

        $lineItemsByFO = [];

        // 2️⃣ Prepare line items dynamically for fulfillment
        foreach ($fulfillmentOrders as $foEdge) {
            $fo = $foEdge['node'];
            $status = strtolower($fo['status']);

            $lineItems = [];
            foreach ($fo['lineItems']['edges'] as $liEdge) {
                $li = $liEdge['node'];
                $remainingQty = $li['remainingQuantity'];
                if ($remainingQty > 0 && $status === "open") {
                    $lineItems[] = [
                        'id' => $li['id'],
                        'quantity' => $remainingQty
                    ];
                }
            }

            if (!empty($lineItems)) {
                $lineItemsByFO[] = [
                    'fulfillmentOrderId' => $fo['id'],
                    'fulfillmentOrderLineItems' => $lineItems
                ];
            }
        }

        if (empty($lineItemsByFO)) {
            return ['error' => 'No fulfillable line items found'];
        }

        // 3️⃣ Create Fulfillment
        $mutation = '
                mutation fulfillmentCreate($fulfillment: FulfillmentInput!) {
                  fulfillmentCreate(fulfillment: $fulfillment) {
                    fulfillment { id status trackingInfo { number company url } }
                    userErrors { field message }
                  }
                }
            ';

        $variables = [
            'fulfillment' => [
                'lineItemsByFulfillmentOrder' => $lineItemsByFO,
                'trackingInfo' => [
                    'number' => (string) $trackingNumber ?? 'TRACK' . rand(10000, 99999),
                    'company' => $trackingCompany ?? 'Delhivery'
                ],
                'notifyCustomer' => false
            ]
        ];

        $fulfillResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://{$shopDomain}/admin/api/{$apiVersion}/graphql.json", [
            'query' => $mutation,
            'variables' => $variables
        ]);

        return $fulfillResponse->json();
    }

    public function courierPartner($companyId)
    {
        // Map company IDs to names
        $mapping = [
            1  => 'Delhivery'
        ];

        // Return mapped name or null if not found
        return $mapping[$companyId] ?? null;
    }
}
