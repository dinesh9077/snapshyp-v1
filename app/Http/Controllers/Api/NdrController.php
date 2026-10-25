<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingCompany;
use App\Models\NdrDetail;
use App\Models\NdrRequest;
use App\Models\Order;
use App\Services\ShipMozo;
use App\Services\DelhiveryNdrService;
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

        // Keep local NDR info updated from APIs
        $this->syncDelhiveryPendingOrdersToNdr();
        $this->syncShipmozoApiNdrToLocal();

        $query = NdrDetail::with(['orderAwb', 'orderAwb.user', 'orderAwb.customer'])
            ->whereHas('orderAwb')
            ->whereHas('orderAwb.user')
            ->whereHas('orderAwb.customer');

        // Filter by shipping company (matching web logic)
        if ($status === 'all') {
            $query->whereIn('shipping_id', [1, 2]);
        } elseif (in_array((int) $status, [1, 2], true)) {
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

            if ($shippingId == 1) { // Delhivery
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
            } elseif ($shippingId == 2) { // ShipMozo
                if (in_array($orderStatus, ["undelivered", "ndr", "out for delivery", "manifested"])) {
                    $canTakeAction = true;
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

            if ($shipping_id == 1) { // Delhivery
                $delhivery = ShippingCompany::find($shipping_id);
                $service = app(DelhiveryNdrService::class);
                $mappedAction = 'RE-ATTEMPT';
                
                $resp = $service->sendNdrAction($awb_number, $mappedAction, $delhivery);
                
                $ndrStatusResp = $service->getNdrStatus($resp['upl_id'] ?? null, $delhivery);
                $statusResult = $resp['success'] ? 'upl_sent' : 'upl_failed';

                if ($ndrStatusResp['success']) {
                    $remarks = $ndrStatusResp['response']['remark'] ?? $remarks;
                    $statusResult = $ndrStatusResp['response']['status'] ?? $statusResult;
                }

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
                        'upl_id' => $resp['upl_id'] ?? null,
                        'status' => $statusResult,
                        'action' => $mappedAction,
                        'response' => $resp['response'] ?? $resp,
                    ]
                );

                DB::commit();
                return $this->successResponse(null, 'Delhivery NDR request sent.');

            } elseif ($shipping_id == 2) { // ShipMozo
                $shipMozo = app(ShipMozo::class);
                $shippingCompany = ShippingCompany::find($shipping_id);
                $mappedAction = (strtolower($action) == 're-attempt') ? 'RE-ATTEMPT' : $action;

                $resp = $shipMozo->ndrAction($awb_number, $mappedAction, $remarks, $shippingCompany, $date);

                if (!($resp['success'] ?? false)) {
                    DB::rollBack();
                    return $this->errorResponse($resp['response']['message'] ?? ($resp['response']['error'] ?? 'An error occurred during ShipMozo NDR action.'));
                }

                NdrRequest::create([
                    'order_id' => $order_id,
                    'action' => $action,
                    'shipping_company_id' => $shipping_id,
                    'reattemptdate' => $date,
                    'remarks' => $remarks,
                    'awb_number' => $awb_number,
                ]);

                Helper::orderActivity($order_id, "NDR action taken: " . strtoupper($action));

                $total_attempts = NdrRequest::where('awb_number', $awb_number)->count();
                NdrDetail::updateOrCreate(
                    ['awb_number' => $awb_number],
                    [
                        'shipping_id' => $shipping_id,
                        'order_id' => $order_id,
                        'ndr_date' => now(),
                        'total_attempts' => $total_attempts,
                        'status' => 'action_taken',
                        'action' => $mappedAction,
                        'response' => $resp['response'],
                    ]
                );

                DB::commit();
                return $this->successResponse(null, 'NDR Request raised successfully.');
            } else {
                DB::rollBack();
                return $this->errorResponse('NDR action is only supported for Delhivery and ShipMozo.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Synchronization Helpers
     */
    private function syncDelhiveryPendingOrdersToNdr()
    {
        try {
            if (Cache::has('delhivery_pending_ndr_sync_lock')) return;
            Cache::put('delhivery_pending_ndr_sync_lock', 1, now()->addSeconds(60));

            $orders = Order::select('id', 'awb_number', 'shipping_company_id', 'status_courier', 'updated_at')
                ->where('shipping_company_id', 1)
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
        } catch (\Throwable $e) { Log::error('Delhivery pending NDR sync failed: ' . $e->getMessage()); }
    }

    private function syncShipmozoApiNdrToLocal()
    {
        try {
            if (Cache::has('shipmozo_ndr_sync_lock')) return;
            Cache::put('shipmozo_ndr_sync_lock', 1, now()->addSeconds(120));

            $shippingCompany = ShippingCompany::where('id', 2)->where('status', 1)->first();
            if (!$shippingCompany) return;

            $shipMozo = app(ShipMozo::class);
            $page = 1; $maxPages = 5;
            while ($page <= $maxPages) {
                $resp = $shipMozo->getNdrAll($shippingCompany, ['per_page' => 100, 'page' => $page]);
                if (!($resp['success'] ?? false)) break;
                $rows = $resp['response']['data'] ?? [];
                if (empty($rows)) break;

                foreach ($rows as $item) {
                    $awb = trim((string)($item['awb_number'] ?? ''));
                    if ($awb === '') continue;
                    $status = str_replace('_', ' ', strtolower((string)($item['webparex_status'] ?? $item['order_status'] ?? '')));
                    if (!$this->isShipmozoNdrStatus($status)) continue;

                    $order = Order::select('id', 'awb_number', 'status_courier')
                        ->where('shipping_company_id', 2)
                        ->where(function ($q) use ($awb, $item) {
                            $q->where('awb_number', $awb);
                            if (!empty($item['ref_order_id'])) $q->orWhere('order_prefix', trim($item['ref_order_id']));
                        })->first();

                    if (!$order) continue;
                    
                    if (strtolower((string)$order->status_courier) !== $status) {
                        Order::where('id', $order->id)->update(['status_courier' => ucwords($status)]);
                    }

                    NdrDetail::updateOrCreate(['awb_number' => $awb], [
                        'shipping_id' => 2, 'order_id' => $order->id,
                        'ndr_date' => now()->toDateString(),
                        'remarks' => (string)($item['courier_status'] ?? $item['webparex_status'] ?? 'NDR'),
                        'total_attempts' => NdrRequest::where('awb_number', $awb)->count(),
                        'status' => $status ?: 'ndr', 'action' => 'RE-ATTEMPT', 'response' => $item,
                    ]);
                }
                if (empty($resp['response']['next_page_url'])) break;
                $page++;
            }
        } catch (\Throwable $e) { Log::error('ShipMozo NDR sync failed: ' . $e->getMessage()); }
    }

    private function isShipmozoNdrStatus(string $status): bool
    {
        $status = strtolower(trim($status));
        return in_array($status, ['undelivered', 'ndr', 'out for delivery', 'out_for_delivery', 'manifested', 'pending', 'scheduled', 'in transit', 'in_transit'], true);
    }
}
