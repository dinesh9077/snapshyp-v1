<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NdrDetail;
use App\Models\NdrRequest;
use App\Models\Order;
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

		// Keep NDR list updated with pending orders of the supported courier (Shadowfax, ID=1).
		$this->syncPendingOrdersToNdr();

		$query = NdrDetail::with(['orderAwb', 'orderAwb.user', 'orderAwb.customer'])
			->whereHas('orderAwb')
			->whereHas('orderAwb.user')
			->whereHas('orderAwb.customer');

		// Filter by shipping company
		if ($status === 'all') {
			$query->whereIn('shipping_id', \App\Models\ShippingCompany::where('provider', 'shadowfax')->pluck('id'));
		} elseif (in_array((int) $status, [1], true)) {
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

			// Supported shipping company: Shadowfax (ID=1)
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

					// Allow Take Action when not delivered/rto, attempts less than 3, and NSL is in re-attempt list
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
									onclick='openReattemptNdrUpdateModal(this, event)'
									data-id='{$orderId}'
									data-awb_number='{$awbNo}'
									data-shipping_id='{$shippingId}'
									data-action='{$order->action}'>
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

			// Shadowfax handling (shipping_id == 1)
			if (\App\Models\ShippingCompany::isShadowfaxId($shipping_id)) {

				try {
					// No courier NDR API is integrated for Shadowfax; the request is recorded locally only.
					$mappedAction = 'RE-ATTEMPT';
					$remarks = $request->input('remarks');
					$status = 'upl_failed';

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
					// Create or update an NdrDetail entry for this request (avoid duplicates)
					NdrDetail::updateOrCreate(
						['awb_number' => $awb_number],
						[
							'shipping_id' => $shipping_id,
							'order_id' => $request->input('order_id'),
							'ndr_date' => $request->input('reattemptdate') ? $request->input('reattemptdate') : now(),
							'total_attempts' => $total_attempts,
							'upl_id' => null,
							'status' => $status,
							'action' => $mappedAction,
						]
					);

					return response()->json(['status' => 'success', 'msg' => 'NDR request raised successfully.']);
				} catch (\Throwable $e) {
					return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
				}
			} else {
				// Only Shadowfax (ID=1) is supported
				return response()->json(['status' => 'error', 'msg' => 'NDR action is only supported for Shadowfax.']);
			}
		} catch (\Exception $e) {

			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	private function syncPendingOrdersToNdr(): void
	{
		try {
			if (Cache::has('pending_ndr_sync_lock')) {
				return;
			}
			Cache::put('pending_ndr_sync_lock', 1, now()->addSeconds(60));

			$orders = Order::select('id', 'awb_number', 'shipping_company_id', 'status_courier', 'updated_at')
				->whereIn('shipping_company_id', \App\Models\ShippingCompany::where('provider', 'shadowfax')->pluck('id'))
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
			Log::error('Pending-to-NDR sync failed', ['message' => $e->getMessage()]);
		}
	}
}
