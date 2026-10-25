<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Notification;
use App\Models\User;
use App\Models\Shop;
use App\Models\ShippingCompany;
use App\Models\WeightDimension;
use App\Services\MasterService;
use App\Services\FulfillShopifyOrder;
use App\Services\ShipmentProcessor;
use App\Services\RateCardService;
use App\Models\CourierCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessBulkShipment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $orderIds;
    protected array $requestData;
    protected $user;

    public function __construct(array $orderIds, array $requestData = [], $user = null)
    {
        $this->orderIds = $orderIds;
        $this->requestData = $requestData;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * NOTE: services are injected by Laravel.
     */
    public function handle(
        RateCardService $rateCardService,
        FulfillShopifyOrder $shopifyFulfill,
        ShipmentProcessor $shipmentProcessor
    ): void {
        $summary = [
            'total' => count($this->orderIds),
            'processed' => 0,  // successful shipments
            'failed' => 0,  // calculated but shipNow failed
            'skipped' => 0,  // invalid/missing orders, missing charges, etc.
            'errors' => 0,  // exceptions thrown
        ];

        Order::whereIn('id', $this->orderIds)->update(['queue' => 0]);
        $shippingCompany = ShippingCompany::whereStatus(1)->where('id', $this->requestData['courier_shipment_id'])->first();
        if (!$shippingCompany) {
            return;
        }
        foreach ($this->orderIds as $orderId) {
            $order = Order::with('orderItems')->find($orderId);

            try {

                if (!$order) {
                    $summary['skipped']++;
                    continue;
                }

                if ((int) $order->weight_order === 2 && !in_array((int) $shippingCompany->id, [1, 3, 4, 5])) {
                    $summary['skipped']++;
                    $order->update([
                        'queue' => 0,
                        'shipment_failure_reason' => 'B2B shipment is supported only with Delhivery/Routx.'
                    ]);
                    continue;
                }

                $updateData = [];
                if (empty($this->requestData['is_shopify'])) {
                    $updateData = [
                        'length' => $this->requestData['length'] ?? $order->length ?? 0,
                        'width' => $this->requestData['width'] ?? $order->width ?? 0,
                        'height' => $this->requestData['height'] ?? $order->height ?? 0,
                        'weight' => $this->requestData['weight'] ?? $order->weight ?? 0,
                        'warehouse_id' => $this->requestData['warehouse_id'] ?? $order->warehouse_id ?? null,
                    ];
                }

                $updateData['shipping_mode'] = $this->requestData['shipping_mode'] ?? $order->shipping_mode;
                $order->update($updateData);

                if (
                    ($order->weight <= 0 || is_null($order->weight)) &&
                    ($order->length <= 0 || is_null($order->length)) &&
                    ($order->width <= 0 || is_null($order->width)) &&
                    ($order->height <= 0 || is_null($order->height))
                ) {
                    $summary['skipped']++;
                    $order->update([
                        'queue' => 0,
                        'shipment_failure_reason' => 'Weight and dimensions all missing (0/null).'
                    ]);
                    continue;
                }

                if (
                    $order->weight >= 0.5 &&
                    (
                        $order->length <= 0 || is_null($order->length) ||
                        $order->width <= 0 || is_null($order->width) ||
                        $order->height <= 0 || is_null($order->height)
                    )
                ) {

                    $defDimension = WeightDimension::where('slab', $order->weight)->first();
                    if ($defDimension) {
                        $order->length = ($order->length > 0) ? $order->length : $defDimension->length;
                        $order->width = ($order->width > 0) ? $order->width : $defDimension->width;
                        $order->height = ($order->height > 0) ? $order->height : $defDimension->height;
                        $order->save();
                    } else {
                        $summary['skipped']++;
                        $order->update([
                            'queue' => 0,
                            'shipment_failure_reason' => 'Invalid dimensions for the given weight.',
                        ]);
                        continue;
                    }
                }

                $requestData = new Request();
                $weight = 0;
                $volumdata = [];
                $weight = $order->weight;
                $volumdata[] = [
                    'length' => (string)($order->length ?? 0),
                    'width' => (string)($order->width ?? 0),
                    'height' => (string)($order->height ?? 0),
                ];

                // Merge properly into the request
                $requestData->merge([
                    'volumdata' => $volumdata,  // assign a key!
                    'pickup_code' => $order->warehouse->zip_code ?? '',
                    'delivery_code' => $order->customerAddress->zip_code ?? '',
                    'weight' => (string)$weight,
                    'payment_type' => $order->order_type,
                    'cod_amount' => (string)($order->cod_amount ?? 0),
                    'order_id' => $orderId,
                    'type_of_package' => (int) $order->weight_order,
                    'orderItems' => $order->orderItems,
                ]);

                // Calculate charges via RateCardService
                $rateResults = $rateCardService->getRate($this->user, $shippingCompany, $requestData, $isOrder = 1);

                if ($shippingCompany->id == 1 && !empty($this->requestData['courier_id'])) {
                    // For Routx/Aggregators, pick the selected courier
                    $targetCourierId = (string) $this->requestData['courier_id'];
                    $filteredCouriers = array_filter($rateResults ?? [], function($r) use ($targetCourierId) {
                        return (string)($r['courier_id'] ?? '') === $targetCourierId;
                    });
                    $charges = !empty($filteredCouriers) ? array_values($filteredCouriers)[0] : (!empty($rateResults) ? $rateResults[0] : null);
                } else {
                    $charges = !empty($rateResults) ? $rateResults[0] : null;
                }

                if (!is_array($charges) || empty($charges)) {
                    $summary['skipped']++;
                    $order->update(['queue' => 0, 'shipment_failure_reason' => 'Pincode not serviceable or rate card not found']);
                    continue;
                }

                $requestParam = [
                    'order_id' => $charges['order_id'],
                    'shipping_company_id' => $charges['shipping_company_id'],
                    'courier_id' => $charges['courier_id'],
                    'freight_charges' => $charges['freight_charges'] ?? null,
                    'cod_charges' => $charges['cod_charges'] ?? 0,
                    'tax' => $charges['tax'] ?? 0,
                    'round_off' => $charges['round_off'] ?? '',
                    'total_charges' => $charges['total_charges'] ?? 0,
                    'applicable_weight' => $charges['applicable_weight'] ?? 0,
                    'chargeable_weight' => $charges['chargeable_weight'] ?? 0,
                    'rto_charge' => $charges['rto_charge'] ?? null,
                    'percentage_amount' => $charges['percentage_amount'] ?? null,
                    'courier_name' => $charges['courier_name'] ?? null,
                    'shipping_company_logo' => $charges['shipping_company_logo'] ?? null,
                    'is_bulk_shipment' => 1,
                    'actor_user_id' => $this->user->id ?? null,
                ];

                $response = $shipmentProcessor->orderShipNow($requestParam);

                if ($response === 1) {
                    $order->refresh();
                    $order->update(['queue' => 2, 'shipment_failure_reason' => null]); // success

                    // Shopify fulfillment if needed
                    if ($order->is_shopify_order == 1 && !empty($order->shopify_order_number)) {
                        $user = $order->user;
                        $shop = Shop::where('shopify_domain', $user->shop)->first();

                        if ($shop) {
                             $shopifyToken = $shop->getValidShopifyToken();

                            $fulfillResponse = $shopifyFulfill->fulfillOrder(
                                $shop->shopify_domain,
                                $shopifyToken,
                                $order->shopify_order_number,
                                $order->awb_number,
                                $order->shipping_company_id
                            );

                            $status = strtolower($fulfillResponse['data']['fulfillmentCreate']['fulfillment']['status'] ?? '');
                            $order->update([
                                'on_pro_id' => $status === 'success' ? 1 : 0,
                            ]);
                        }
                    }

                    $summary['processed']++;
                } else {
                    // ShipNow returned non-success
                    $summary['failed']++;
                    $failureReason = is_array($response) ? ($response['msg'] ?? 'Shipment failed') : 'Shipment failed';
                    $order->update(['queue' => 0, 'shipment_failure_reason' => $failureReason]);
                }
            } catch (\Throwable $e) {
                $summary['errors']++;

                if ($order) {
                    $order->update(['queue' => 0, 'shipment_failure_reason' => $e->getMessage()]);
                }
                continue;
            }
        }

        // Notification for the user (or system)
        if ($this->user) {
            Notification::insert([
                'user_id' => $this->user->id,
                'task_id' => $this->user->id,
                'type' => 'Bulk Shipment',
                'role' => 'user',
                'text' => sprintf(
                    'Bulk shipment completed: %d processed, %d failed, %d skipped, %d errors (Total: %d)',
                    $summary['processed'],
                    $summary['failed'],
                    $summary['skipped'],
                    $summary['errors'],
                    $summary['total']
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            Log::info('Bulk shipment completed (no user)', $summary);
        }
    }
}
