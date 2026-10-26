<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NdrDetail;
use App\Models\NdrRequest;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log, Cache, Validator, Http};
use Helper;

class NdrController extends Controller
{
    use ApiResponse;

    /**
     * NDR List API
     */
    public function index(Request $request)
    {
        $start  = (int) ($request->input("start") ?? $request->input("offset") ?? 0);
        $limit  = (int) ($request->input("length") ?? $request->input("limit") ?? 10);
        $search = $request->input('search') ?? $request->input('search.value') ?? '';
        $status = (string) $request->input('status', 'all'); // Represents shipping company ID in web logic

        $user   = Auth::user();
        $role   = $user->role;
        $userId = $user->id;

        // Keep local NDR info updated with pending orders of the supported courier (Shadowfax, ID=1)
        $this->syncPendingOrdersToNdr();

        $query = NdrDetail::with(['orderAwb', 'orderAwb.user', 'orderAwb.customer'])
            ->whereHas('orderAwb')
            ->whereHas('orderAwb.user')
            ->whereHas('orderAwb.customer');

        // Filter by shipping company (matching web logic)
        if ($status === 'all') {
            $query->whereIn('shipping_id', \App\Models\ShippingCompany::where('provider', 'shadowfax')->pluck('id'));
        } elseif (in_array((int) $status, [1], true)) {
            $query->where('shipping_id', (int) $status);
        }

        // Access control by user role
        if ($role !== "admin") {
            $query->whereHas('orderAwb', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        // Apply general search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('orderAwb.customer', function ($cq) use ($search) {
                    $cq->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('orderAwb.user', function ($uq) use ($search) {
                    $uq->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('company_name', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('orderAwb', function ($oq) use ($search) {
                    $oq->where('awb_number', 'LIKE', "%{$search}%")
                        ->orWhere('status_courier', 'LIKE', "%{$search}%")
                        ->orWhere('order_prefix', 'LIKE', "%{$search}%")
                        ->orWhere('id', 'LIKE', "%{$search}%");
                });
            });
        }

        $totalData = $query->count();

        $ndrList = $query->orderBy("ndr_details.ndr_date", "desc")
            ->offset($start)
            ->limit($limit)
            ->get();

        // Transform collection to clean JSON format
        $data = $ndrList->map(function ($item) {
            $order = $item->orderAwb;
            $ndrRequest = NdrRequest::where('order_id', $order->id)->orderByDesc('created_at')->first();
            
            $canTakeAction = false;
            $orderStatus = strtolower($order->status_courier ?? 'new');
            $shippingId = (int) $order->shipping_company_id;

            if ($shippingId == 1) { // Shadowfax
                $nsl = null;
                if (is_array($item->response) && !empty($item->response['NSLCode'])) {
                    $nsl = $item->response['NSLCode'];
                } elseif (is_string($item->remarks)) {
                    if (preg_match('/EOD-\d+/', $item->remarks, $m)) {
                        $nsl = $m[0];
                    }
                }
                $reAttemptNsl = ["EOD-74", "EOD-15", "EOD-104", "EOD-43", "EOD-86", "EOD-11", "EOD-69", "EOD-6"];
                
                $canTakeAction = true;
                if (in_array($orderStatus, ["delivered", "rto"])) {
                    $canTakeAction = false;
                }
                if ($item->total_attempts >= 3) {
                    $canTakeAction = false;
                }
                if (empty($nsl) || !in_array($nsl, $reAttemptNsl, true)) {
                    $canTakeAction = false;
                }
                // Don't allow action if a request was already raised AFTER the latest NDR event date
                if ($canTakeAction && $ndrRequest && $ndrRequest->created_at->toDateString() > $item->ndr_date) {
                     $canTakeAction = false;
                }
            }

            return [
                'id' => $item->id,
                'order_id' => $order->id,
                'display_order_id' => $order->order_manual_id ?: $order->id,
                'awb_number' => $order->awb_number,
                'order_date' => $order->order_date ? date('d-m-Y', strtotime($order->order_date)) : '',
                'ndr_date' => date('d-m-Y', strtotime($item->ndr_date)),
                'seller' => [
                    'name' => $order->user->name ?? 'N/A',
                    'company' => $order->user->company_name ?? 'N/A',
                    'email' => $order->user->email ?? 'N/A',
                    'mobile' => $order->user->mobile ?? 'N/A',
                ],
                'customer' => [
                    'name' => trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? '')),
                    'email' => $order->customer->email ?? 'N/A',
                    'mobile' => $order->customer->mobile ?? 'N/A',
                ],
                'payment' => [
                    'amount' => $order->total_amount ?? 0,
                    'type' => $order->order_type ?? '',
                ],
                'status_courier' => $order->status_courier ?? 'New',
                'exception_info' => [
                    'attempts' => $item->total_attempts,
                    'remarks' => $item->remarks,
                ],
                'shipping_id' => $shippingId,
                'can_take_action' => $canTakeAction,
            ];
        });

        return $this->successResponse([
            "total_records" => $totalData,
            "data" => $data
        ], 'NDR list fetched successfully.');
    }

    /**
     * Raise NDR Request API
     */
    public function raiserequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'action' => 'required|in:re-attempt,RE-ATTEMPT,PICKUP_RESCHEDULE,change_address,change_phone,rto,update_paymode',
            'shipping_company_id' => 'required',
            'awb_number' => 'required',
            'reattemptdate' => 'required|date',
            'remarks' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $shipping_id = $request->input('shipping_company_id');
            $order_id    = $request->input('order_id');
            $awb_number  = $request->input('awb_number');
            $action      = $request->input('action');
            $remarks     = $request->input('remarks');
            $date        = $request->input('reattemptdate');

            if (\App\Models\ShippingCompany::isShadowfaxId($shipping_id)) { // Shadowfax
                // No courier NDR API is integrated for Shadowfax; the request is recorded locally only.
                $mappedAction = 'RE-ATTEMPT';
                $statusResult = 'upl_failed';

                NdrRequest::create([
                    'order_id' => $order_id,
                    'action' => $action,
                    'shipping_company_id' => $shipping_id,
                    'reattemptdate' => $date,
                    'remarks' => $remarks,
                    'awb_number' => $awb_number,
                    'address_1' => $request->input('address_1', null),
                    'phone' => $request->input('phone', null),
                ]);

                Helper::orderActivity($order_id, "NDR action taken: " . strtoupper($action));

                $total_attempts = NdrRequest::where('awb_number', $awb_number)->count();
                NdrDetail::updateOrCreate(
                    ['awb_number' => $awb_number],
                    [
                        'shipping_id' => $shipping_id,
                        'order_id' => $order_id,
                        'ndr_date' => $date ?: now(),
                        'total_attempts' => $total_attempts,
                        'upl_id' => null,
                        'status' => $statusResult,
                        'action' => $mappedAction,
                    ]
                );

                DB::commit();
                return $this->successResponse(null, 'NDR request raised successfully.');

            } else {
                DB::rollBack();
                return $this->errorResponse('NDR action is only supported for Shadowfax.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Synchronization Helpers
     */
    private function syncPendingOrdersToNdr()
    {
        try {
            if (Cache::has('pending_ndr_sync_lock')) return;
            Cache::put('pending_ndr_sync_lock', 1, now()->addSeconds(60));

            $orders = Order::select('id', 'awb_number', 'shipping_company_id', 'status_courier', 'updated_at')
                ->whereIn('shipping_company_id', \App\Models\ShippingCompany::where('provider', 'shadowfax')->pluck('id'))
                ->whereNotNull('awb_number')->where('awb_number', '!=', '')
                ->whereRaw('LOWER(status_courier) = ?', ['pending'])
                ->limit(500)->get();

            foreach ($orders as $order) {
                NdrDetail::updateOrCreate(['awb_number' => $order->awb_number], [
                    'shipping_id' => 1, 'order_id' => $order->id,
                    'ndr_date' => optional($order->updated_at)->toDateString() ?? now()->toDateString(),
                    'remarks' => 'Pending', 'total_attempts' => NdrRequest::where('awb_number', $order->awb_number)->count(),
                    'status' => 'pending', 'action' => 'RE-ATTEMPT',
                    'response' => ['source' => 'order_status', 'status_courier' => $order->status_courier],
                ]);
            }
        } catch (\Throwable $e) { Log::error('Pending NDR sync failed: ' . $e->getMessage()); }
    }
}
