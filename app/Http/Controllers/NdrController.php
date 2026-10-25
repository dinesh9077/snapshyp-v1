<?php

namespace App\Http\Controllers;

use App\Models\ShippingCompany;
use Illuminate\Http\Request;
use App\Models\NdrDetail;
use App\Models\NdrRequest;
use App\Models\Order;
use App\Services\ShipMozo;
use DB, Auth, Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NdrController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index()
	{
		$status = request()->status ?? 'all';
		$filter = request()->filter ?? 'all';
		return view('ndr.index', compact('status', 'filter'));
	}

	public function ndrAjax(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->post("start");
		$limit = $request->post("length");
		$searchData = $request->input('search');
		$search = is_array($searchData) ? ($searchData['value'] ?? '') : ($searchData ?? '');

		$columnIndex = $request->post('order')[0]['column'] ?? 0;
		$order = $request->post('columns')[$columnIndex]['data'] ?? 'id';
		$dir = $request->post('order')[0]['dir'] ?? 'asc';

		$status = (string) $request->post('status', 'all');
		$filter = (string) $request->post('filter', 'all');

		if ($order == 'action') {
			$order = 'id';
		}

		$role = Auth::user()->role;
		$userId = Auth::user()->id;

		// Keep NDR list updated from both supported providers.
		$this->syncDelhiveryPendingOrdersToNdr();
		$this->syncShipmozoApiNdrToLocal();

		$query = NdrDetail::with(['orderAwb', 'orderAwb.user', 'orderAwb.customer'])
			->whereHas('orderAwb')
			->whereHas('orderAwb.user')
			->whereHas('orderAwb.customer');

		// Filter by shipping company
		if ($status === 'all') {
			$query->whereIn('shipping_id', [1, 2]);
		} elseif (in_array((int) $status, [1, 2], true)) {
			$query->where('shipping_id', (int) $status);
		}

		// Filter by NDR state (Resolved/Pending)
		if ($filter === 'resolved') {
			$query->where(function ($q) {
				$q->whereRaw('LOWER(status) LIKE ?', ['%delivered%'])
					->orWhere('total_attempts', '>=', 3);
			});
		} elseif ($filter === 'pending') {
			$query->where(function ($q) {
				$q->whereRaw('LOWER(status) NOT LIKE ?', ['%delivered%'])
					->where('total_attempts', '<', 3);
			});
		}

		// Restrict query based on role
		if ($role !== "admin") {
			$query->whereHas('orderAwb', function ($q) use ($userId) {
				$q->where('user_id', $userId);
			});
			$query->whereHas('orderAwb.user');
		}

		// Apply search filter with proper table references
		$query->when(!empty($search), function ($q) use ($search) {
			$q->whereHas('orderAwb.customer', function ($q) use ($search) {
				$q->where('first_name', 'LIKE', "%{$search}%")
					->orWhere('last_name', 'LIKE', "%{$search}%")
					->orWhere('mobile', 'LIKE', "%{$search}%");
			})
				->orWhereHas('orderAwb.user', function ($q) use ($search) {
					$q->where('name', 'LIKE', "%{$search}%")
						->orWhere('email', 'LIKE', "%{$search}%")
						->orWhere('company_name', 'LIKE', "%{$search}%")
						->orWhere('mobile', 'LIKE', "%{$search}%");
				})
				->orWhereHas('orderAwb', function ($q) use ($search) {
					$q->where('awb_number', 'LIKE', "%{$search}%")
						->orWhere('status_courier', 'LIKE', "%{$search}%")
						->orWhere('order_prefix', 'LIKE', "%{$search}%")
						->orWhere('created_at', 'LIKE', "%{$search}%")
						->orWhere('id', 'LIKE', "%{$search}%");
				});
		});

		// Get total records count before applying limit/offset
		$totalData = $query->count();
		$totalFiltered = $query->count();

		// Apply pagination & sorting
		$orders = $query->orderBy("ndr_details.ndr_date", "desc")
			->offset($start)
			->limit($limit)
			->get();

		$data = [];
		$i = $start + 1;

		foreach ($orders as $order) {
			$userName = $order->orderAwb && $order->orderAwb->user ? $order->orderAwb->user->name : 'N/A';
			$userEmail = $order->orderAwb && $order->orderAwb->user ? $order->orderAwb->user->email : 'N/A';
			$userMobile = $order->orderAwb && $order->orderAwb->user ? $order->orderAwb->user->mobile : 'N/A';
			$userCompanyName = $order->orderAwb && $order->orderAwb->user ? $order->orderAwb->user->company_name : 'N/A';

			$customerFName = $order->orderAwb && $order->orderAwb->customer ? $order->orderAwb->customer->first_name : 'N/A';
			$customerLName = $order->orderAwb && $order->orderAwb->customer ? $order->orderAwb->customer->last_name : 'N/A';
			$customerEmail = $order->orderAwb && $order->orderAwb->customer ? $order->orderAwb->customer->email : 'N/A';
			$customerMobile = $order->orderAwb && $order->orderAwb->customer ? $order->orderAwb->customer->mobile : 'N/A';

			$totalAmount = $order->orderAwb->total_amount ?? 0;
			$orderType = $order->orderAwb->order_type ?? '';
			$orderStatus = $order->orderAwb->status_courier ?? 'New';

			$shippingId = $order->orderAwb->shipping_company_id ?? '';
			$orderId = $order->orderAwb->id ?? '';
			$awbNo = $order->orderAwb->awb_number ?? '';
			$orderManualId = $order->orderAwb->order_manual_id ?? '';
			$displayOrderId = $orderManualId ?: $orderId;
			$orderDate = $order->orderAwb->order_date ?? null;
			$orderDate = $orderDate ? date('d-m-Y', strtotime($orderDate)) : '';

			$ndrRequest = NdrRequest::where('order_id', $orderId)->orderByDesc('created_at')->first();

			// Supported shipping companies: Delhivery and ShipMozo
			$takeAction = "";
			if (intval($shippingId) == 1) {
					// Extract NSL code from response array if available, otherwise try from remarks
					$nsl = null;
					if (is_array($order->response) && !empty($order->response['NSLCode'])) {
						$nsl = $order->response['NSLCode'];
					} elseif (is_string($order->remarks)) {
						// try to find pattern like EOD-123 in remarks
						if (preg_match('/EOD-\d+/', $order->remarks, $m)) {
							$nsl = $m[0];
						}
					}

					$reAttemptNsl = ["EOD-74", "EOD-15", "EOD-104", "EOD-43", "EOD-86", "EOD-11", "EOD-69", "EOD-6"];

					// Delhivery: allow Take Action when not delivered/rto, attempts less than 3, and NSL is in re-attempt list
					$canTake = true;
					if (in_array(strtolower($orderStatus), ["delivered", "rto"])) {
						$canTake = false;
					}
					if ($order->total_attempts >= 3) {
						$canTake = false;
					}
					if (empty($nsl) || !in_array($nsl, $reAttemptNsl, true)) {
						$canTake = false;
					}

					if ($canTake && (!$ndrRequest || $ndrRequest->created_at->toDateString() <= $order->ndr_date)) {
						$takeAction = "<div class='main-btn-1'>
							<a href='javascript:;'>
								<button type='button' class='btn-light-1'
									onclick='openDelhiveryNdrUpdateModal(this, event)'
									data-id='{$orderId}'
									data-awb_number='{$awbNo}'
									data-shipping_id='{$shippingId}'
									data-action='{$order->action}'>
									Take Action
								</button>
							</a>
						</div>";
					}
			} elseif (intval($shippingId) == 2) {
					// ShipMozo: allow Take Action when in NDR/Undelivered status
					if (in_array(strtolower($orderStatus), ["undelivered", "ndr", "out for delivery", "manifested"])) {
						$takeAction = "<div class='main-btn-1'>
							<a href='javascript:;'>
								<button type='button' class='btn-light-1'
									onclick='openShipMozoNdrUpdateModal(this, event)'
									data-id='{$orderId}'
									data-awb_number='{$awbNo}'
									data-shipping_id='{$shippingId}'>
									Take Action
								</button>
							</a>
						</div>";
					}
			}

			$data[] = [
				'id' => $i++,
				'order_date' => "<div class='main-cont1-2'>
									<p>#{$displayOrderId}</p> 
									<p>#{$awbNo}</p> 
									<p>{$orderDate}</p> 
								 </div>",
				'ndr_date' => date('d-m-Y', strtotime($order->ndr_date)),
				'seller' => "<div class='main-cont1-2'>
										<p>{$userName} ({$userCompanyName})</p>
										<p>{$userEmail}</p>
										<p>{$userMobile}</p>
									 </div>",
				'customer' => "<div class='main-cont1-2'>
										<p>{$customerFName} {$customerLName}</p>
										<p>{$customerEmail}</p>
										<p>{$customerMobile}</p>
									   </div>",
				'payment' => "<div class='main-cont1-2'>
										<p>{$totalAmount}</p>
										<p class='" . strtolower($orderType) . "'>{$orderType}</p>
									</div>",
				'status_courier' => "<p class='prepaid'>{$orderStatus}</p>",
				'exception_info' => "<div class='main-cont1-2'>
										<p>{$order->total_attempts} Attempt(s)</p>
										<p>{$order->remarks}</p>
									 </div>",
				'action' => $order->total_attempts < 3 ? $takeAction : ''
			];
		}

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
		]);
	}

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
			return response()->json(['errors' => $validator->errors()]);
		}

		try {
			$shipping_id = $request->input('shipping_company_id');
			$awb_number = $request->input('awb_number');
			$action = $request->input('action');

			// Delhivery handling (shipping_id == 1)
			if ($shipping_id == 1) {

				$delhivery = ShippingCompany::find($shipping_id);
				try {
					$service = app(\App\Services\DelhiveryNdrService::class);
					$mappedAction = 'RE-ATTEMPT';
					$resp = $service->sendNdrAction($awb_number, $mappedAction, $delhivery);

					$ndrStatus = $service->getNdrStatus($resp['upl_id'] ?? null, $delhivery);
					$remarks = $request->input('remarks');
					$status = $resp['success'] ? 'upl_sent' : 'upl_failed';

					if ($ndrStatus['success']) {
						$remarks = $ndrStatus['response']['remark'];
						$status = $ndrStatus['response']['status'];
					}

					// Record the request in NdrRequest table for audit
					NdrRequest::create([
						'order_id' => $request->input('order_id'),
						'action' => $action,
						'shipping_company_id' => $shipping_id,
						'reattemptdate' => $request->input('reattemptdate'),
						'remarks' => $remarks,
						'awb_number' => $awb_number,
						'address_1' => $request->input('address_1', null),
						'phone' => $request->input('phone', null),
					]);

					Helper::orderActivity($request->input('order_id'), "NDR action taken: " . strtoupper($action));

					$total_attempts = NdrRequest::where('awb_number', $awb_number)->count();
					// Create or update an NdrDetail entry reflecting API result (avoid duplicates)
					NdrDetail::updateOrCreate(
						['awb_number' => $awb_number],
						[
							'shipping_id' => $shipping_id,
							'order_id' => $request->input('order_id'),
							'ndr_date' => $request->input('reattemptdate') ? $request->input('reattemptdate') : now(),
							'total_attempts' => $total_attempts,
							'upl_id' => $resp['upl_id'] ?? null,
							'status' => $status,
							'action' => $mappedAction,
							'response' => $resp['response'] ?? $resp,
						]
					);

					return response()->json(['status' => 'success', 'msg' => 'Delhivery NDR request sent.']);
				} catch (\Throwable $e) {
					return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
				}
			} elseif ($shipping_id == 2) {
				// ShipMozo NDR implementation
				try {
					$shipMozo = app(\App\Services\ShipMozo::class);
					$shippingCompany = ShippingCompany::find($shipping_id);

					$mappedAction = $action;
					if (strtolower($action) == 're-attempt') {
						$mappedAction = 'RE-ATTEMPT';
					}

					$remarks = $request->input('remarks');
					$date = $request->input('reattemptdate');

					$resp = $shipMozo->ndrAction($awb_number, $mappedAction, $remarks, $shippingCompany, $date);

					if (!($resp['success'] ?? false)) {
						return response()->json(['status' => 'error', 'msg' => $resp['response']['message'] ?? ($resp['response']['error'] ?? 'An error occurred during ShipMozo NDR action.')]);
					}

					// Record the request
					NdrRequest::create([
						'order_id' => $request->input('order_id'),
						'action' => $action,
						'shipping_company_id' => $shipping_id,
						'reattemptdate' => $request->input('reattemptdate'),
						'remarks' => $remarks,
						'awb_number' => $awb_number,
					]);

					Helper::orderActivity($request->input('order_id'), "NDR action taken: " . strtoupper($action));

					$total_attempts = NdrRequest::where('awb_number', $awb_number)->count();

					NdrDetail::updateOrCreate(
						['awb_number' => $awb_number],
						[
							'shipping_id' => $shipping_id,
							'order_id' => $request->input('order_id'),
							'ndr_date' => now(),
							'total_attempts' => $total_attempts,
							'status' => 'action_taken',
							'action' => $mappedAction,
							'response' => $resp['response'],
						]
					);

					return response()->json(['status' => 'success', 'msg' => 'NDR Request raised successfully.']);
				} catch (\Throwable $e) {
					return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
				}

			} else {
				// Only Delhivery (ID=1) and ShipMozo (ID=2) are supported
				return response()->json(['status' => 'error', 'msg' => 'NDR action is only supported for Delhivery and ShipMozo.']);
			}
		} catch (\Exception $e) {

			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	private function syncDelhiveryPendingOrdersToNdr(): void
	{
		try {
			if (Cache::has('delhivery_pending_ndr_sync_lock')) {
				return;
			}
			Cache::put('delhivery_pending_ndr_sync_lock', 1, now()->addSeconds(60));

			$orders = Order::select('id', 'awb_number', 'shipping_company_id', 'status_courier', 'updated_at')
				->where('shipping_company_id', 1)
				->whereNotNull('awb_number')
				->where('awb_number', '!=', '')
				->whereRaw('LOWER(status_courier) = ?', ['pending'])
				->limit(500)
				->get();

			foreach ($orders as $order) {
				NdrDetail::updateOrCreate(
					['awb_number' => $order->awb_number],
					[
						'shipping_id' => 1,
						'order_id' => $order->id,
						'ndr_date' => optional($order->updated_at)->toDateString() ?? now()->toDateString(),
						'remarks' => 'Pending',
						'total_attempts' => NdrRequest::where('awb_number', $order->awb_number)->count(),
						'status' => 'pending',
						'action' => 'RE-ATTEMPT',
						'response' => [
							'source' => 'order_status',
							'status_courier' => $order->status_courier,
						],
					]
				);
			}
		} catch (\Throwable $e) {
			Log::error('Delhivery pending-to-NDR sync failed', ['message' => $e->getMessage()]);
		}
	}

	private function syncShipmozoApiNdrToLocal(): void
	{
		try {
			if (Cache::has('shipmozo_ndr_sync_lock')) {
				return;
			}
			Cache::put('shipmozo_ndr_sync_lock', 1, now()->addSeconds(120));

			$shippingCompany = ShippingCompany::where('id', 2)->where('status', 1)->first();
			if (!$shippingCompany) {
				return;
			}

			$shipMozo = app(ShipMozo::class);
			$page = 1;
			$maxPages = 5;

			while ($page <= $maxPages) {
				$resp = $shipMozo->getNdrAll($shippingCompany, [
					'per_page' => 100,
					'page' => $page,
				]);

				if (!($resp['success'] ?? false)) {
					break;
				}

				$payload = $resp['response'] ?? [];
				$rows = $payload['data'] ?? [];

				if (empty($rows)) {
					break;
				}

				$foundCount = 0;
				$missingCount = 0;

				foreach ($rows as $item) {
					$awb = trim((string) ($item['awb_number'] ?? ''));
					if ($awb === '') {
						continue;
					}

					$statusRaw = strtolower((string) ($item['webparex_status'] ?? $item['order_status'] ?? ''));
					$status = str_replace('_', ' ', $statusRaw);
					if (!$this->isShipmozoNdrStatus($status)) {
						continue;
					}

					$order = Order::select('id', 'awb_number', 'status_courier')
						->where('shipping_company_id', 2)
						->where(function ($q) use ($awb, $item) {
							$q->where('awb_number', $awb);
							if (!empty($item['ref_order_id'])) {
								$q->orWhere('order_prefix', trim($item['ref_order_id']));
							}
						})->first();

					if (!$order) {
						$missingCount++;
						continue;
					}
					
					$foundCount++;

					// Update AWB and Status in main orders table if needed
					$updateData = [];
					if (empty($order->awb_number) || trim((string)$order->awb_number) !== $awb) {
						$updateData['awb_number'] = $awb;
					}
					if (strtolower($order->status_courier) !== $status) {
						$updateData['status_courier'] = ucwords($status);
					}
					
					if (!empty($updateData)) {
						Order::where('id', $order->id)->update($updateData);
					}

					$remarks = (string) ($item['courier_status'] ?? $item['webparex_status'] ?? $item['order_status'] ?? 'NDR');
					$attempts = NdrRequest::where('awb_number', $awb)->count();

					NdrDetail::updateOrCreate(
						['awb_number' => $awb],
						[
							'shipping_id' => 2,
							'order_id' => $order->id,
							'ndr_date' => now()->toDateString(),
							'remarks' => $remarks,
							'total_attempts' => $attempts,
							'status' => $status ?: 'ndr',
							'action' => 'RE-ATTEMPT',
							'response' => $item,
						]
					);
				}

				Log::info("ShipMozo NDR Sync Page {$page}: Found {$foundCount}, Missing {$missingCount} orders locally.");

				$nextPageUrl = trim((string) ($payload['next_page_url'] ?? ''));
				if ($nextPageUrl === '') {
					break;
				}

				$page++;
			}
		} catch (\Throwable $e) {
			Log::error('ShipMozo get-ndr-all sync failed', ['message' => $e->getMessage()]);
		}
	}

	private function isShipmozoNdrStatus(string $status): bool
	{
		$status = strtolower(trim($status));
		return in_array($status, ['undelivered', 'ndr', 'out for delivery', 'out_for_delivery', 'manifested', 'pending', 'scheduled', 'in transit', 'in_transit'], true);
	}
}
