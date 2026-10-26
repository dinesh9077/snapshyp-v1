<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\ShadowfaxService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class PublicTrackingController extends Controller
{
    use ApiResponse;

    /**
     * Track order by AWB number (Publicly accessible)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function track(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'awb' => 'nullable|string|required_without:order_id',
                'order_id' => 'nullable|string|required_without:awb',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first());
            }

            $awbNumber = trim((string) $request->input('awb', ''));
            $orderIdInput = trim((string) $request->input('order_id', ''));

            // Find order by AWB or order id/order prefix
            $orderQuery = Order::with(['shippingCompany']);
            if ($awbNumber !== '') {
                $orderQuery->where('awb_number', $awbNumber);
            } else {
                $orderQuery->where(function ($q) use ($orderIdInput) {
                    if (is_numeric($orderIdInput)) {
                        $q->orWhere('id', (int) $orderIdInput);
                    }
                    $q->orWhere('order_prefix', $orderIdInput);
                });
            }
            $order = $orderQuery->first();

            if (!$order) {
                $trackingRef = $awbNumber !== '' ? ('AWB: ' . $awbNumber) : ('Order ID: ' . $orderIdInput);
                return $this->errorResponse('Tracking details not found for ' . $trackingRef);
            }

            $shippingCompany = $order->shippingCompany;
            if (!$shippingCompany) {
                return $this->errorResponse('Shipping provider information not found.');
            }

            $trackingHistories = [];
            $expectedDeliveryDate = "N/A";

            if ($shippingCompany->isShadowfax()) { // Shadowfax
                $shadowfaxService = new ShadowfaxService();
                $trackingResponse = $shadowfaxService->track($order->awb_number, $shippingCompany);

                if ($trackingResponse['success'] ?? false) {
                    $expectedDeliveryDate = $trackingResponse['expected_delivery_date'] ?? 'N/A';

                    $trackingHistories = collect($trackingResponse['history'] ?? [])->map(function ($value) {
                        return [
                            'date' => date('d M Y, h:i A', strtotime($value['timestamp'] ?? now())),
                            'status' => $value['status'] ?? ($value['remarks'] ?? 'Update'),
                            'location' => $value['location'] ?? 'N/A',
                            'tag' => $value['status_id'] ?? 'SHADOWFAX'
                        ];
                    })->values()->toArray();
                }
            } else {
                $trackingHistories = [];
            }

            if (empty($trackingHistories)) {
                $orderStatuses = \App\Models\OrderStatus::where('order_id', $order->id)
                    ->orderByDesc('id')
                    ->get();

                $trackingHistories = collect($orderStatuses)->map(function ($status) use ($shippingCompany) {
                    return [
                        'date' => date('M d, Y h:i a', strtotime($status->created_at)),
                        'status' => $status->order_status,
                        'location' => 'Local Manual Update',
                        'tag' => $shippingCompany->name ?? 'Local Courier'
                    ];
                })->toArray();
            }

            if (empty($trackingHistories)) {
                return $this->errorResponse('No tracking history available yet for this shipment.');
            }

            return $this->successResponse([
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'awb_number' => $order->awb_number,
                'courier_name' => $order->courier_name,
                'lr_no' => $order->lr_no,
                'shipping_company_name' => $shippingCompany->name ?? null,
                'status' => $order->status_courier,
                'expected_delivery' => $expectedDeliveryDate,
                'history' => $trackingHistories
            ], 'Tracking data fetched successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while fetching tracking data: ' . $e->getMessage());
        }
    }
}
