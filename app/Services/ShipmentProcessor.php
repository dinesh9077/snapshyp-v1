<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Billing;
use App\Models\FranchiseEarning;
use App\Models\Shop;
use App\Models\ShippingCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helper;
use App\Support\OrderType;
use App\Services\{DelhiveryB2CService, DelhiveryService, FulfillShopifyOrder, ShipMozo, ShadowfaxService};

class ShipmentProcessor
{
    private const STAFF_FRANCHISE_CHARGE_FIXED = 1;
    private const STAFF_FRANCHISE_CHARGE_PERCENTAGE = 2;

    public function orderShipNow($requestData)
    {
        $delhiveryB2CService = app(DelhiveryB2CService::class);
        $routxService = app(RoutxService::class);
        $shadowfaxService = app(ShadowfaxService::class);
        $shopifyFulfill = app(FulfillShopifyOrder::class);

        if (empty($requestData['order_id'])) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Order reference missing for shipment.'
            ]);
        }

        $shippingCompany = ShippingCompany::findOrFail($requestData['shipping_company_id']);
        $order = Order::with(['warehouse', 'customer', 'customerAddress', 'orderItems', 'user'])
            ->findOrFail($requestData['order_id']);

        $user = $order->user;
        $actorUserId = (int) ($requestData['actor_user_id'] ?? 0);
        $actorUser = $actorUserId > 0 ? \App\Models\User::find($actorUserId) : null;

        // Business rule (shared with manual create, API and bulk import): the order
        // must be COD or Prepaid before any courier shipment is booked. Checked here at
        // the single gate every courier passes through, so the message surfaces cleanly;
        // each courier service also re-validates as a defensive safety net.
        if (!OrderType::isValid($order->order_type)) {
            if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                return ['status' => 'error', 'msg' => OrderType::INVALID_MESSAGE];
            }
            return response()->json(['status' => 'error', 'msg' => OrderType::INVALID_MESSAGE]);
        }

        $isRestrictedShadowfax = (int) $shippingCompany->id === 1
            && method_exists($user, 'canAccessShadowfax')
            && !$user->canAccessShadowfax();
        if ($isRestrictedShadowfax) {
            if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                return ['status' => 'error', 'msg' => 'This shipping API is not assigned to this franchise seller.'];
            }

            return response()->json([
                'status' => 'error',
                'msg' => 'This shipping API is not assigned to this franchise seller.'
            ]);
        }

        // Wallet validation
        if ($user->role === 'user') {
            if ($user->wallet_amount < 100) {
                if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                    return ['status' => 'error', 'msg' => 'Minimum 100 Wallet Balance required'];
                }
                return response()->json([
                    'status' => 'error',
                    'wallet' => 1,
                    'msg' => 'Minimum 100 Wallet Balance required'
                ]);
            }

            if ($requestData['total_charges'] > $user->wallet_amount) {
                if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                    return ['status' => 'error', 'msg' => 'Insufficient wallet balance'];
                }
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Insufficient wallet balance'
                ]);
            }
        }

        if (!$order->warehouse) {
            if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                return ['status' => 'error', 'msg' => 'Pickup warehouse address empty.'];
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Pickup warehouse address empty.'
            ]);
        }

        if (!empty($order->shipping_company_id) && !empty($order->awb_number)) {
            if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                return ['status' => 'error', 'msg' => 'Order already shipped.'];
            }
            return response()->json(['status' => 'error', 'msg' => 'Order already shipped.']);
        }

        $courierWarehouse = $order->warehouse;
        // Defaults
        $courier_name = null;
        $shipment_id = null;
        $lr_no = null;
        $awb_number = null;
        $courier_id = null;
        $statusCourier = 'new';
        $apiResponse = null;
        $courierLogo = $requestData['shipping_company_logo'] ?? null;

        // Shadowfax (ID = 1) - Replaced ROUTX with Shadowfax
        if ((int) $shippingCompany->id === 1) {
            Log::info('Shadowfax: Starting shipment processing', [
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'user_id' => $user->id
            ]);

            $createOrderResponse = $shadowfaxService->createOrder($order, $shippingCompany);

            if (!($createOrderResponse['success'] ?? false)) {
                $errorMsg = $createOrderResponse['message'] ?? 'Failed to create order in Shadowfax.';
                
                if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                    return ['status' => 'error', 'msg' => $errorMsg];
                }
                return response()->json(['status' => 'error', 'msg' => $errorMsg]);
            }

            // Extract AWB and Order ID from Shadowfax response
            $awb_number = $createOrderResponse['awb_number'] ?? null;
            $apiResponse = $createOrderResponse['data'] ?? [];
            $shipment_id = $apiResponse['order_id'] ?? ($apiResponse['data']['order_id'] ?? $order->order_prefix);
            
            if (empty($awb_number)) {
                $errorMsg = 'Shadowfax order created but AWB number not found in response.';
                if (($requestData['is_bulk_shipment'] ?? 0) == 1) {
                    return ['status' => 'error', 'msg' => $errorMsg];
                }
                return response()->json(['status' => 'error', 'msg' => $errorMsg]);
            }

            $lr_no = $awb_number;
            $statusCourier = 'manifested';
            $apiResponse = $createOrderResponse['data'] ?? [];
            $courier_id = $requestData['courier_id'] ?? 1;
            $courier_name = $requestData['courier_name'] ?? 'Shadowfax';
        }

        elseif ($shippingCompany->id === 3) {
            Log::info('Delhivery: Starting shipment processing', [
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'warehouse_id' => $courierWarehouse->id ?? null,
                'user_id' => $user->id
            ]);

            $existStatus = $courierWarehouse->created ?? [];

            if (empty($existStatus['delhivery_status']) || $existStatus['delhivery_status'] == 0) {
                Log::info('Delhivery: Warehouse not created, attempting to create', [
                    'warehouse_id' => $courierWarehouse->id,
                    'warehouse_name' => $courierWarehouse->company_name ?? null
                ]);

                $data = $delhiveryB2CService->createWarehouse($courierWarehouse, $shippingCompany);

                Log::info('Delhivery: Warehouse creation response', [
                    'warehouse_id' => $courierWarehouse->id,
                    'success' => $data['success'] ?? false,
                    'response' => $data['response'] ?? null
                ]);

                // Handle success OR "already exists" error
                if (!empty($data['success']) && !empty($data['response']['success'])) {
                    $existingCreated = $courierWarehouse->created ?? [];
                    if (is_string($existingCreated)) {
                        $existingCreated = json_decode($existingCreated, true) ?? [];
                    }
                    $existingCreated['delhivery_status'] = 1;
                    $courierWarehouse->created = $existingCreated;
                    $courierWarehouse->shipping_id = $shippingCompany->id;
                    $courierWarehouse->api_response = $data['response'];
                    $courierWarehouse->save();
                    $order->setRelation('warehouse', $courierWarehouse->fresh());

                    Log::info('Delhivery: Warehouse created successfully', [
                        'warehouse_id' => $courierWarehouse->id
                    ]);
                } else {
                    // Check if error is "already exists"
                    $errorMsg = $data['response']['error']['list-item'] ?? '';
                    if (str_contains($errorMsg, 'already exists')) {
                        // Warehouse already exists, mark as created
                        $existingCreated = $courierWarehouse->created ?? [];
                        if (is_string($existingCreated)) {
                            $existingCreated = json_decode($existingCreated, true) ?? [];
                        }
                        $existingCreated['delhivery_status'] = 1;
                        $courierWarehouse->created = $existingCreated;
                        $courierWarehouse->shipping_id = $shippingCompany->id;
                        // Still try to save api_response if it contains data (might happen if creation fails with 'exists' but returns metadata)
                        if (!empty($data['response']['data'])) {
                            $courierWarehouse->api_response = $data['response'];
                        }
                        $courierWarehouse->save();
                        $order->setRelation('warehouse', $courierWarehouse->fresh());

                        Log::info('Delhivery: Warehouse already exists, marked as created', [
                            'warehouse_id' => $courierWarehouse->id
                        ]);
                    } else {
                        Log::warning('Delhivery: Warehouse creation failed', [
                            'warehouse_id' => $courierWarehouse->id,
                            'response' => $data['response'] ?? null
                        ]);
                    }
                }
            }

            Log::info('Delhivery: Sending manifest request', [
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'total_charges' => $requestData['total_charges'] ?? null,
                'applicable_weight' => $requestData['applicable_weight'] ?? null,
                'payment_mode' => $requestData['payment_mode'] ?? null
            ]);

            $response = $delhiveryB2CService->manifest($order, $requestData, $shippingCompany);

            Log::info('Delhivery: Manifest response received', [
                'order_id' => $order->id,
                'success' => $response['success'] ?? false,
                'response' => $response
            ]);

            if (!($response['success'] ?? false)) {
                $errorMsg = $response['response']['errors'][0]['message'] ?? ($response['response']['error']['message'] ?? 'An error occurred.');

                Log::error('Delhivery: Manifest failed - success flag false', [
                    'order_id' => $order->id,
                    'error_message' => $errorMsg,
                    'full_response' => $response['response'] ?? null
                ]);

                if ($requestData['is_bulk_shipment'] == 1) {
                    return ['status' => 'error', 'msg' => $errorMsg];
                }
                return response()->json(['status' => 'error', 'msg' => $errorMsg]);
            }

            if ((isset($response['response']['success']) && !$response['response']['success'])) {
                $errmsg = $response['response']['rmk'];
                if (is_array($response['response']['packages']) && count($response['response']['packages']) > 0) {
                    $errmsg = $response['response']['packages'][0]['remarks'][0] ?? $response['response']['rmk'];
                }

                if (is_array($errmsg)) {
                    $errmsg = implode(', ', $errmsg);
                }


                Log::error('Delhivery: Manifest response indicates failure', [
                    'order_id' => $order->id,
                    'error_message' => $errmsg,
                    'rmk' => $response['response']['rmk'] ?? null,
                    'packages' => $response['response']['packages'] ?? null
                ]);

                if ($requestData['is_bulk_shipment'] == 1) {
                    return ['status' => 'error', 'msg' => $errmsg ?? 'An error occurred.'];
                }
                return response()->json(['status' => 'error', 'msg' => $errmsg ?? 'An error occurred.']);
            }

			// Check for waybill (Unified API might not return upload_wbn)
            if (empty($response['response']['packages'][0]['waybill'])) {
                Log::error('Delhivery: Missing waybill in response', [
                    'order_id' => $order->id,
                    'full_response' => $response['response'] ?? null
                ]);

                if ($requestData['is_bulk_shipment'] == 1) {
                    return ['status' => 'error', 'msg' => 'An error occurred (missing waybill).'];
                }
                return response()->json(['status' => 'error', 'msg' => 'An error occurred.']);
            }

            // Extract response data
            $shipment_id = $response['response']['upload_wbn'] ?? rand(11111111111111, 99999999999999);
            $lr_no = $order->order_prefix;
            $awb_number = $response['response']['packages'][0]['waybill'] ?? rand(11111111111111, 99999999999999);

            // Ensure shipment_id and awb_number are strings
            if (is_array($shipment_id)) {
                $shipment_id = reset($shipment_id);
            }
            if (is_array($awb_number)) {
                $awb_number = reset($awb_number);
            }

            $courier_id = $response['response']['packages'][0]['refnum'] ?? null;
            if (is_array($courier_id)) {
                $courier_id = reset($courier_id);
            }
            $statusCourier = 'manifested';
            $apiResponse = $response['response'] ?? null;


            Log::info('Delhivery: Shipment created successfully', [
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'shipment_id' => $shipment_id,
                'awb_number' => $awb_number,
                'courier_id' => $courier_id,
                'lr_no' => $lr_no,
                'status' => $statusCourier
            ]);

        }else {
            $awb_number = $this->generateLocalAwb($shippingCompany, $order);
            $shipment_id = 'LOCAL-' . $order->id . '-' . now()->format('YmdHis');
            $lr_no = $order->order_prefix;
            $statusCourier = 'manifested';
            $courier_id = 'LOCAL-' . $shippingCompany->id;
            $courier_name = $requestData['courier_name'] ?? $shippingCompany->name;
            $apiResponse = [
                'mode' => 'local',
                'message' => 'Shipment created under local manual courier flow.',
                'shipping_company_id' => $shippingCompany->id,
                'shipping_company_name' => $shippingCompany->name,
                'awb_number' => $awb_number,
            ];
        }

        if (is_array($apiResponse)) {
            $apiResponse['managed_pincode_settings'] = [
                'pickup_service' => strtolower((string) ($requestData['pickup_service'] ?? 'door')),
                'delivery_service' => strtolower((string) ($requestData['delivery_service'] ?? 'door')),
                'oda_category' => (string) ($requestData['oda_category'] ?? 'NONE'),
                'oda_charge_id' => $requestData['oda_charge_id'] ?? null,
                'oda_charge_name' => (string) ($requestData['oda_charge_name'] ?? $requestData['oda_category'] ?? 'NONE'),
                'opa_category' => (string) ($requestData['opa_category'] ?? 'NONE'),
                'opa_charge_id' => $requestData['opa_charge_id'] ?? null,
                'opa_charge_name' => (string) ($requestData['opa_charge_name'] ?? $requestData['opa_category'] ?? 'NONE'),
                'cod_enabled' => (int) ($requestData['cod_enabled'] ?? 1) === 1,
            ];
        }

        // Prepare update data (Matching your snippet's defaults)
        $totalCharges = (float) ($requestData['total_charges'] ?? 0);
        $codCharges = (float) (
            $requestData['cod_charges']
            ?? $requestData['cod_charge']
            ?? $requestData['charge_cod']
            ?? 0
        );
        $taxAmount = (float) (
            $requestData['tax']
            ?? $requestData['gst']
            ?? 0
        );
        $baseFreight = (float) (
            $requestData['freight_charges']
            ?? $requestData['base_freight']
            ?? 0
        );

        // Forward charge = non-COD part paid at shipment time.
        $forwardCharges = max(0, $totalCharges - $codCharges);
        if ($forwardCharges <= 0 && ($baseFreight > 0 || $taxAmount > 0)) {
            $forwardCharges = max(0, $baseFreight + $taxAmount);
        }
        $isCodOrder = strtolower((string) ($order->order_type ?? '')) === 'cod';

        $updateData = [
            'status_courier' => $statusCourier,
            'tax' => $taxAmount,
            'tax_percentage' => $taxAmount > 0 ? 18 : 0,
            'shipping_charge' => $totalCharges,
            'shipping_company_id' => $shippingCompany->id,
            'shipment_id' => $shipment_id,
            'awb_number' => $awb_number ?? null,
            'courier_name' => $courier_name ?? ($requestData['courier_name'] ?? null),
            'courier_id' => $courier_id,
            'courier_logo' => $courierLogo,
            'label' => null,
            'lr_no' => $lr_no,
            'api_response' => $apiResponse,
            'applicable_weight' => $requestData['applicable_weight'],
            'cod_charges' => $codCharges,
            'rto_charge' => (float) ($requestData['rto_charge'] ?? 0),
            'percentage_amount' => $requestData['percentage_amount'] ?? 0
        ];

        $order->update($updateData);

        if ($user->role === "user" && $totalCharges > 0) {
            $user->decrement('wallet_amount', $totalCharges);
        }

        if ($totalCharges > 0) {
            $franchiseEarningRecorded = false;

            try {
                $franchiseEarningRecorded = $this->recordStaffFranchiseEarning(
                    $order,
                    $user,
                    $actorUser,
                    $totalCharges,
                    $awb_number ?? null
                );
            } catch (\Throwable $ex) {
                Log::error('Staff franchise earning error: ' . $ex->getMessage());
            }

            if ($user->role === "user" && !empty($user->staff_id)) {
                $creatorUser = \App\Models\User::find($user->staff_id);
                if ($creatorUser && in_array($creatorUser->role, ['admin', 'franchise'], true)) {
                    try {
                        if ($creatorUser->role === 'franchise') {
                            if (!$franchiseEarningRecorded) {
                                $franchiseCommissionRate = method_exists($user, 'getFranchiseCommissionRate')
                                    ? (float) $user->getFranchiseCommissionRate()
                                    : 0;
                                $creatorProfit = round(($totalCharges * $franchiseCommissionRate) / 100, 2);

                                if ($creatorProfit > 0) {
                                    $earning = FranchiseEarning::firstOrCreate(
                                        [
                                            'franchise_user_id' => $creatorUser->id,
                                            'order_id' => $order->id,
                                        ],
                                        [
                                            'seller_user_id' => $user->id,
                                            'amount' => $creatorProfit,
                                            'status' => 'available',
                                            'remarks' => "Hardcoded {$franchiseCommissionRate}% commission for shipped order AWB: " . ($awb_number ?? $order->order_prefix),
                                        ]
                                    );

                                    if ($earning->wasRecentlyCreated) {
                                        $creatorUser->increment('wallet_amount', $creatorProfit);
                                    }
                                }
                            }
                        } else {
                            $rateService = app(\App\Services\RateCardService::class);

                            // Create a mock request to get the rate for the Admin
                            $mockRequest = \Illuminate\Http\Request::create('/order/shipment/charge', 'POST', [
                                'qty' => [1],
                                'length' => [(string)($order->length ?? 0)],
                                'width' => [(string)($order->width ?? 0)],
                                'height' => [(string)($order->height ?? 0)],
                                'volumdata' => [[
                                    'length' => (string)($order->length ?? 0),
                                    'width' =>  (string)($order->width ?? 0),
                                    'height' =>  (string)($order->height ?? 0),
                                ]],
                                'pickup_code' => (string)($order->warehouse->zip_code ?? ''),
                                'delivery_code' => (string)($order->customerAddress->zip_code ?? ''),
                                'weight' => (string)$order->weight,
                                'dimension_type' => $order->dimension_type ?? 'cm',
                                'payment_type' => strtoupper($order->order_type),
                                'cod_amount' => (string)($order->cod_amount ?? 0),
                                'inv_amount' => (string)($order->invoice_amount ?? 0),
                                'order_id' => $order->id,
                                'rov_type' => ($order->insurance_type == 1 ? 'ROV_OWNER' : 'ROV_CARRIER'),
                                'type_of_package' => $order->weight_order,
                                'orderItems' => $order->orderItems,
                            ]);

                            $creatorRates = $rateService->getRate($creatorUser, $shippingCompany, $mockRequest, 1);
                            $creatorTotalCost = null;

                            if (!empty($creatorRates) && is_array($creatorRates)) {
                                foreach ($creatorRates as $rate) {
                                    if ((string)($rate['courier_id'] ?? '') === (string)$courier_id) {
                                        $creatorTotalCost = $rate['total_charges'] ?? null;
                                        break;
                                    }
                                }
                                if (!$creatorTotalCost) {
                                    $creatorTotalCost = $creatorRates[0]['total_charges'] ?? null;
                                }
                            }

                            if (!$creatorTotalCost) {
                                $creatorCommData = $rateService->applyCourierCommission(
                                    $creatorUser,
                                    $shippingCompany,
                                    $baseFreight,
                                    $courier_id
                                );
                                $creatorCommissionAmount = $creatorCommData['commission_amount'] ?? 0;
                                $userCommissionAmount = (float)($requestData['percentage_amount'] ?? 0);
                                $creatorProfit = max(0, $userCommissionAmount - $creatorCommissionAmount);
                            } else {
                                $creatorProfit = max(0, $totalCharges - $creatorTotalCost);
                            }

                            if ($creatorProfit > 0) {
                                $creatorUser->increment('wallet_amount', $creatorProfit);
                                Billing::create([
                                    'user_id' => $creatorUser->id,
                                    'billing_type' => "Commission",
                                    'billing_type_id' => $order->id,
                                    'transaction_type' => 'credit',
                                    'amount' => $creatorProfit,
                                    'note' => "Margin profit for AWB: " . ($awb_number ?? $order->order_prefix),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    } catch (\Exception $ex) {
                        Log::error('Creator Profit calculation error: ' . $ex->getMessage());
                    }
                }
            }
        }

        if ($forwardCharges > 0) {
            $freightBase = $baseFreight > 0 ? round($baseFreight, 2) : round($forwardCharges / 1.18, 2);
            $freightGst = $taxAmount > 0 ? round($taxAmount, 2) : round($forwardCharges - $freightBase, 2);
            Billing::create([
                'user_id' => $user->id,
                'billing_type' => "Order",
                'billing_type_id' => $order->id,
                'transaction_type' => 'debit',
                'amount' => $forwardCharges,
                'note' => "Forward shipping charges debited for AWB: {$awb_number} | Base Freight: " . number_format($freightBase, 2, '.', '') . " | GST (18%): " . number_format($freightGst, 2, '.', '') . " | Total: " . number_format($forwardCharges, 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($isCodOrder) {
            $codBase = round(max(0, $codCharges), 2);
            $codGst = round($codBase * 0.18, 2);
            $codTotal = round($codBase + $codGst, 2);
            Billing::create([
                'user_id' => $user->id,
                'billing_type' => "Order",
                'billing_type_id' => $order->id,
                'transaction_type' => 'debit',
                'amount' => $codTotal,
                'note' => "COD charges debited for AWB: {$awb_number} | COD Base: " . number_format($codBase, 2, '.', '') . " | GST (18%): " . number_format($codGst, 2, '.', '') . " | Total: " . number_format($codTotal, 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'order_status' => 'pending pickup',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Helper::orderActivity($order->id, "Order is shipped with AWB: {$awb_number}");

        // Dispatch webhook: order.updated (status changed to manifested)
        try {
            app(\App\Services\WebhookService::class)->dispatch($user->id, 'order.updated', $order->fresh());
        } catch (\Throwable $webhookEx) {
            Log::error('Webhook dispatch failed (order.updated/manifested): ' . $webhookEx->getMessage());
        }

        try {
            dispatch(function () use ($order) {
                $whatsappService = app(\App\Services\WhatsappService::class);
                $whatsappServiceResponse = $whatsappService->sendOrderNotification($order->fresh(), 'shipment_picked_up');
            });
        } catch (\Exception $e) {
            Log::error('WhatsApp notification shipment_picked_up queue failed: ' . $e->getMessage());
        }

        $warehouse = $order->warehouse ?? null;
        if ($warehouse) {
            $pickupAddress = "<img src='" . asset('assets/images/order-1/location.png') . "' style='margin-right: 10px;'>
				<h5><b>Pick Up Address <br></b>{$warehouse->company_name}, {$warehouse->address},
				{$warehouse->city}, {$warehouse->state}, {$warehouse->country}, {$warehouse->zip_code}</h5>";
        } else {
            $pickupAddress = "<h5><b>Pick Up Address Not Available</b></h5>";
        }

        $msg = "<h5> <img src='" . asset('assets/images/order-1/green-teu.png') . "' style='margin-right: 5px;'>
			Your package has been assigned to <b>{$order->courier_name}</b>.";
        if (!empty($awb_number)) {
            $msg .= "The AWB number is <span>{$awb_number}</span></h5>";
        }
        if ($requestData['is_bulk_shipment'] == 1) {
            return 1;
        }

        // Shopify fulfillment if needed
        if ($order->is_shopify_order == 1 && !empty($order->shopify_order_number)) {
            $order->update(['queue' => 2]); // success
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
                    'on_pro_id' => $status === 'success' ? 1 : 0
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'msg' => $msg,
            'pickup_address' => $pickupAddress,
            'order_id' => $order->id,
            'shipping_id' => $shippingCompany->id
        ]);
    }

    private function generateLocalAwb(ShippingCompany $shippingCompany, Order $order): string
    {
        $companyCode = preg_replace('/[^A-Za-z0-9]/', '', (string) $shippingCompany->name);
        $companyPart = substr(strtoupper($companyCode ?: 'LCL'), 0, 4);

        do {
            $awb = $companyPart . $shippingCompany->id . now()->format('ymdHis') . random_int(100, 999);
        } while (Order::where('awb_number', $awb)->exists());

        return $awb;
    }

    private function recordStaffFranchiseEarning(Order $order, $orderOwner, $actorUser, float $totalCharges, ?string $awbNumber = null): bool
    {
        $staffUser = $this->resolveFranchiseChargeStaffUser($orderOwner, $actorUser);
        if (!$staffUser) {
            return false;
        }

        if (($staffUser->role ?? null) === 'franchise') {
            $franchiseOwner = $staffUser;
        } else {
            $franchiseOwner = method_exists($staffUser, 'getFranchiseOwner')
                ? $staffUser->getFranchiseOwner()
                : null;
        }

        if (!$franchiseOwner || ($franchiseOwner->role ?? null) !== 'franchise') {
            return false;
        }

        $isFranchiseActor = ($actorUser && ($actorUser->role ?? null) === 'franchise')
            || ($staffUser && ($staffUser->role ?? null) === 'franchise');

        if ($isFranchiseActor && $franchiseOwner) {
            $chargeType = (int) ($franchiseOwner->franchise_charge_type ?? self::STAFF_FRANCHISE_CHARGE_PERCENTAGE);
            $chargeValue = (float) ($franchiseOwner->franchise_charge ?? 1);
        } else {
            $chargeType = self::STAFF_FRANCHISE_CHARGE_PERCENTAGE;
            $chargeValue = 1.0;
        }
        if ($chargeValue <= 0) {
            return false;
        }

        $earningAmount = $chargeType === self::STAFF_FRANCHISE_CHARGE_PERCENTAGE
            ? round(($totalCharges * $chargeValue) / 100, 2)
            : round($chargeValue, 2);

        if ($earningAmount <= 0) {
            return false;
        }

        $earning = FranchiseEarning::firstOrCreate(
            [
                'franchise_user_id' => $franchiseOwner->id,
                'order_id' => $order->id,
            ],
            [
                'seller_user_id' => $orderOwner->id,
                'amount' => $earningAmount,
                'status' => 'available',
                'remarks' => sprintf(
                    'Staff %s franchise charge applied for shipped order AWB: %s',
                    $chargeType === self::STAFF_FRANCHISE_CHARGE_PERCENTAGE
                        ? rtrim(rtrim(number_format($chargeValue, 2, '.', ''), '0'), '.') . '%'
                        : '₹' . number_format($chargeValue, 2, '.', ''),
                    $awbNumber ?? $order->order_prefix
                ),
            ]
        );

        if ($earning->wasRecentlyCreated) {
            $franchiseOwner->increment('wallet_amount', $earningAmount);
            return true;
        }

        return false;
    }

    private function resolveFranchiseChargeStaffUser($orderOwner, $actorUser)
    {
        if ($actorUser && in_array(($actorUser->role ?? null), ['staff', 'franchise'], true)) {
            return $actorUser;
        }

        if ($orderOwner && in_array(($orderOwner->role ?? null), ['staff', 'franchise'], true)) {
            return $orderOwner;
        }

        return null;
    }
}
