<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Billing;
use App\Models\ShippingCompany;
use App\Models\CourierCommission;
use App\Models\Notification;
use App\Models\AppBanner;
use App\Models\Order;
use App\Models\CodWalletExpense;
use App\Models\StatusMapping;
use App\Models\UserKyc;
use App\Models\Ticket;
use App\Models\SellerRemittance;
use App\Models\NdrDetail;
use App\Support\CodBalance;
use App\Models\ShippingWeight;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\ShipMozo;
use App\Services\MasterService;
use File;
use PDF;

class CommonController extends Controller
{
	use ApiResponse;

	protected $shipMozo;
	protected $rateCardService;

	public function __construct()
	{
		$this->shipMozo = new ShipMozo();
		$this->rateCardService = \App\Services\RateCardService::class;
		// Actually, standard is new Class() or injection.
		// Since ShippingController uses `new RateCardService()`, we will do the same.
		$this->rateCardService = new \App\Services\RateCardService();
	}

	public function dashboard(Request $request)
	{
		try {
			$user = Auth::user();
			if (!$user) {
				return $this->errorResponse('Unauthorized', 401);
			}
			$isMasterAdmin = $user->isMasterAdmin();

			// Common query filter for Orders
			$orderFilter = function ($query) use ($user) {
				$role = strtolower((string) $user->role);
				if (in_array($role, ['admin', 'reseller', 'staff'], true)) {
					$query->where(function ($q) use ($user) {
						$q->where('user_id', $user->id)
							->orWhereHas('user', function ($subQ) use ($user) {
								$subQ->where('staff_id', $user->id);
							});
					});
				} else {
					$query->where('user_id', $user->id);
				}
			};

			$today = Carbon::today();
			$yesterday = Carbon::yesterday();

			// Base Order query with filter
			$baseOrderQuery = Order::query()->when(!$isMasterAdmin, $orderFilter);

			$fromDate = $request->input('from_date') ?? $request->input('fromdate');
			$toDate = $request->input('to_date') ?? $request->input('todate');

			if ($fromDate && $toDate) {
				$baseOrderQuery->whereBetween('order_date', [$fromDate, $toDate]);
			} elseif ($fromDate) {
				$baseOrderQuery->whereDate('order_date', $fromDate);
			} elseif ($toDate) {
				$baseOrderQuery->whereDate('order_date', $toDate);
			}

			$readyShipMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'Ready To Ship')->value('courier_status') ?? []);
			$pendingPickupMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'Pending Pickup')->value('courier_status') ?? []);
			$inTransitMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'In Transit')->value('courier_status') ?? []);
			$outForDeliveryMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'Out For Delivery')->value('courier_status') ?? []);
			$rtoAllMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'RTO All')->value('courier_status') ?? []);
			$cancelledMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'Cancelled')->value('courier_status') ?? []);
			$deliveredMap = $this->normalizeCourierStatus(StatusMapping::where('internal_status', 'Delivered')->value('courier_status') ?? []);

			// --- counts (separate queries for clarity & safety) ---
			$countsQuery = clone $baseOrderQuery;
			$readytoship = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $readyShipMap);
			$manifested = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $pendingPickupMap);
			$inTransit = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $inTransitMap);
			$outForDelivery = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $outForDeliveryMap);
			$delivered = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $deliveredMap);
			$cancelledOrder = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $cancelledMap);
			$rto = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 1), $rtoAllMap);

			$readytoshipB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $readyShipMap);
			$manifestedB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $pendingPickupMap);
			$inTransitB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $inTransitMap);
			$outForDeliveryB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $outForDeliveryMap);
			$deliveredB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $deliveredMap);
			$cancelledOrderB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $cancelledMap);
			$rtoB2b = $this->countWhenStatusIn((clone $countsQuery)->where('weight_order', 2), $rtoAllMap);

			$orderStats = $baseOrderQuery->selectRaw("
				SUM(CASE WHEN (LOWER(platform_source) = 'shopify' AND is_platform_order = 1) OR is_shopify_order = 1 THEN 1 ELSE 0 END) as total_shopify_order,
				SUM(CASE WHEN LOWER(platform_source) = 'wordpress' THEN 1 ELSE 0 END) as total_wordpress_order,
				SUM(CASE WHEN (platform_source IS NULL OR (LOWER(platform_source) != 'shopify' AND LOWER(platform_source) != 'wordpress')) AND (is_shopify_order = 0 OR is_shopify_order IS NULL) THEN 1 ELSE 0 END) as total_portal_order,
				SUM(invoice_amount) as totalInvoiceAmount,
				SUM(CASE WHEN DATE(created_at) = ? THEN invoice_amount ELSE 0 END) as todaysInvoiceAmount,
				SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as yesterday_total_order,
				SUM(CASE WHEN weight_order = 1 THEN 1 ELSE 0 END) as total_b2c_order,
				SUM(CASE WHEN weight_order = 2 THEN 1 ELSE 0 END) as total_b2b_order,
				SUM(CASE WHEN weight_order = 1 THEN invoice_amount ELSE 0 END) as total_b2c_revenue,
				SUM(CASE WHEN weight_order = 2 THEN invoice_amount ELSE 0 END) as total_b2b_revenue,
				COUNT(*) as total_orders
			", [$today->toDateString(), $yesterday->toDateString()])->first();

			// Remittance Data
			$remittanceQuery = SellerRemittance::query()
				->when(!$isMasterAdmin, $orderFilter);

			$totalCodSnapshotAmount = (clone $remittanceQuery)->sum('total_cod_remittance_amount') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->sum('amount');

			$totalPaidAmount = (clone $remittanceQuery)
				->where('settlement_status', 'completed')
				->sum('total_cod_remittance_amount') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->where('voucher_status', 1)->sum('amount');

			$totalUnpaidAmount = (clone $remittanceQuery)
				->where('settlement_status', 'pending')
				->sum('amount_transferred_to_seller') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->where('voucher_status', 0)->sum('net_amount');

			$unprocessedDeliveredCod = Order::query()
				->when(!$isMasterAdmin, $orderFilter)
				->where('order_type', 'cod')
				->whereIn('status_courier', ['delivered', 'Delivered'])
				->where('is_remmitance', '0')
				->sum('cod_amount');

			$lastPayoutDate = \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)
				->orderBy('voucher_date', 'desc')
				->value('voucher_date') ?? 0;

			$unsettledExpensesForDashboard = \App\Models\CodWalletExpense::when(!$isMasterAdmin, $orderFilter)
				->whereDate('processed_at', '>', $lastPayoutDate)
				->whereDate('processed_at', '<=', $today)
				->where('status', 'completed')
				->sum('cod_balance_used');

			$pendingCodRemittance = max(0, $totalUnpaidAmount + $unprocessedDeliveredCod - $unsettledExpensesForDashboard);

			$lastPayout = (clone $remittanceQuery)
				->where('settlement_status', 'completed')
				->whereNotNull('payment_date')
				->orderBy('payment_date', 'desc')
				->first(['payment_date', 'amount_transferred_to_seller']);

			$upcomingPayout = (clone $remittanceQuery)
				->where('settlement_status', 'pending')
				->orderBy('payment_scheduled_date', 'asc')
				->first(['payment_scheduled_date', 'amount_transferred_to_seller']);

			// NDR Statistics
			$ndrQuery = NdrDetail::query()
				->when(!$isMasterAdmin, function ($q) use ($user) {
					$role = strtolower((string) $user->role);
					if (in_array($role, ['admin', 'reseller', 'staff'], true)) {
						$q->whereHas('orderAwb', function ($subQ) use ($user) {
							$subQ->where('user_id', $user->id)
								->orWhereHas('user', function ($subQ2) use ($user) {
									$subQ2->where('staff_id', $user->id);
								});
						});
					} else {
						$q->whereHas('orderAwb', function ($subQ) use ($user) {
							$subQ->where('user_id', $user->id);
						});
					}
				});

			$totalNdrOrders = (clone $ndrQuery)->count();
			$resolvedNdr = (clone $ndrQuery)
				->where(function ($q) {
					$q->whereRaw('LOWER(status) LIKE ?', ['%delivered%'])
						->orWhere('total_attempts', '>=', 3);
				})
				->count();
			$pendingNdr = (clone $ndrQuery)
				->where(function ($q) {
					$q->whereRaw('LOWER(status) NOT LIKE ?', ['%delivered%'])
						->where('total_attempts', '<', 3);
				})
				->count();

			// Today recharge
			$todayRecharge = UserWallet::when(!$isMasterAdmin, function ($q) use ($user) {
				$q->where('user_id', $user->id);
			})
				->whereDate('created_at', $today)
				->whereRaw('LOWER(transaction_status) = ?', ['paid'])
				->sum('amount');

			// Overall Wallet balance
			$availableWalletAmount = User::whereNotNull('wallet_amount')
				->whereNull('deleted_at')
				->when(!$isMasterAdmin, function ($q) use ($user) {
					return $q->where('id', $user->id);
				})
				->sum('wallet_amount');

			$ticketStats = Ticket::selectRaw("
					COUNT(*) as total,
					SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
					SUM(CASE WHEN status = 'close' THEN 1 ELSE 0 END) as close
				")
				->when(!$isMasterAdmin, $orderFilter)
				->first();

			$recentOrders = Order::with([
				'customer:id,first_name,last_name,mobile,email',
				'customerAddress:id,address',
				'warehouse:id,warehouse_name,contact_name,contact_number,company_name',
				'user:id,name,company_name,email,mobile',
				'orderItems:id,order_id,product_category,product_name,sku_number,hsn_number,amount,quantity,dimensions',
			])
				->select('id', 'order_prefix', 'user_id', 'customer_id', 'shopify_order_name', 'shopify_order_id', 'confirm_shopify_order', 'customer_address_id', 'shipping_company_id', 'warehouse_id', 'status_courier', 'order_type', 'created_at', 'weight_order', 'cod_amount', 'awb_number', 'invoice_amount', 'length', 'width', 'height', 'weight', 'reason_cancel', 'courier_name')
				->when(!$isMasterAdmin, $orderFilter)
				->where('status_courier', 'New')
				->oldest()
				->limit(10)
				->get();

			if ($user->role != 'user') {
				// Top 10 Clients
				$topClients = Order::select(
					'user_id',
					DB::raw('COUNT(*) as total_orders'),
					DB::raw('SUM(CASE WHEN LOWER(status_courier) = \'manifested\' THEN 1 ELSE 0 END) as manifested_count'),
					DB::raw('SUM(CASE WHEN LOWER(status_courier) NOT IN (\'new\', \'manifested\', \'delivered\', \'cancelled\') AND LOWER(status_courier) NOT LIKE \'%rto%\' THEN 1 ELSE 0 END) as in_transit_count'),
					DB::raw('SUM(CASE WHEN LOWER(status_courier) = \'delivered\' THEN 1 ELSE 0 END) as delivered_count'),
					DB::raw('SUM(CASE WHEN LOWER(status_courier) LIKE \'%rto%\' THEN 1 ELSE 0 END) as rto_count')
				)
					->when(!$isMasterAdmin, $orderFilter)
					->with('user:id,name,company_name')
					->groupBy('user_id')
					->orderByDesc('total_orders')
					->limit(10)
					->get();

				$riskPincodesQuery = DB::table('orders')
					->join('customer_addresses', 'customer_addresses.id', '=', 'orders.customer_address_id')
					->selectRaw("
						customer_addresses.zip_code as pincode,
						COUNT(orders.id) as total_orders,
						SUM(CASE WHEN LOWER(status_courier) LIKE '%rto%' THEN 1 ELSE 0 END) as rto_count,
						ROUND(
							(SUM(CASE WHEN LOWER(status_courier) LIKE '%rto%' THEN 1 ELSE 0 END) / COUNT(orders.id)) * 100, 2
						) as rto_percent
					");

				if (!$isMasterAdmin) {
					$role = strtolower((string) $user->role);
					if (in_array($role, ['admin', 'reseller', 'staff'], true)) {
						$riskPincodesQuery->whereExists(function ($query) use ($user) {
							$query->select(DB::raw(1))
								->from('users')
								->whereColumn('users.id', 'orders.user_id')
								->where(function ($q) use ($user) {
									$q->where('users.id', $user->id)
										->orWhere('users.staff_id', $user->id);
								});
						});
					} else {
						$riskPincodesQuery->where('orders.user_id', $user->id);
					}
				}

				$riskPincodes = $riskPincodesQuery->groupBy('customer_addresses.zip_code')
					->havingRaw('COUNT(orders.id) > 20')
					->orderByDesc('rto_percent')
					->limit(10)
					->get();
			} else {
				$topClients = [];
				$riskPincodes = [];
			}

			$courierWiseCount = Order::whereNotNull('shipping_company_id')
				->whereNotNull('courier_name')
				->when(!$isMasterAdmin, $orderFilter)
				->select(
					'courier_name',
					DB::raw('COUNT(*) as total_orders'),
					DB::raw('SUM(CASE WHEN weight_order = 1 THEN 1 ELSE 0 END) as b2c_count'),
					DB::raw('SUM(CASE WHEN weight_order = 2 THEN 1 ELSE 0 END) as b2b_count')
				)
				->groupBy('courier_name')
				->orderByDesc('total_orders')
				->limit(10)
				->get();

			$data = [
				'todayRecharge' => $todayRecharge,
				'availableWalletAmount' => $availableWalletAmount,
				'totalOrders' => $orderStats->total_orders ?? 0,
				'totalShopifyOrder' => $orderStats->total_shopify_order ?? 0,
				'totalWordPressOrder' => $orderStats->total_wordpress_order ?? 0,
				'totalPortalOrder' => $orderStats->total_portal_order ?? 0,
				'yesterdayTotalOrder' => (int) ($orderStats->yesterday_total_order ?? 0),
				'totalB2cOrder' => (int) ($orderStats->total_b2c_order ?? 0),
				'totalB2bOrder' => (int) ($orderStats->total_b2b_order ?? 0),
				'totalB2cRevenue' => (float) ($orderStats->total_b2c_revenue ?? 0),
				'totalB2bRevenue' => (float) ($orderStats->total_b2b_revenue ?? 0),
				'totalInvoiceAmount' => $orderStats->totalInvoiceAmount ?? 0,
				'todaysInvoiceAmount' => $orderStats->todaysInvoiceAmount ?? 0,
				'totalCodAmount' => $totalCodSnapshotAmount ?? 0,
				'totalPaidAmount' => $totalPaidAmount ?? 0,
				'totalUnpaidAmount' => $totalUnpaidAmount ?? 0,
				'pendingCodRemittance' => $pendingCodRemittance ?? 0,
				'lastPayout' => $lastPayout,
				'upcomingPayout' => $upcomingPayout,
				'totalNdrOrders' => $totalNdrOrders,
				'resolvedNdr' => $resolvedNdr,
				'pendingNdr' => $pendingNdr,
				'totalTickets' => $ticketStats->total ?? 0,
				'totalOpenTickets' => $ticketStats->open ?? 0,
				'totalClosedTickets' => $ticketStats->close ?? 0,
				'topClients' => $topClients,
				'riskPincodes' => $riskPincodes,
				'recentOrders' => $recentOrders,
				'readytoship_b2c' => $readytoship,
				'manifested_b2c' => $manifested,
				'cancelled_b2c' => $cancelledOrder,
				'inTransit_b2c' => $inTransit,
				'rto_b2c' => $rto,
				'outForDelivery_b2c' => $outForDelivery,
				'delivered_b2c' => $delivered,
				'readytoship_b2b' => $readytoshipB2b,
				'manifested_b2b' => $manifestedB2b,
				'cancelled_b2b' => $cancelledOrderB2b,
				'inTransit_b2b' => $inTransitB2b,
				'rto_b2b' => $rtoB2b,
				'outForDelivery_b2b' => $outForDeliveryB2b,
				'delivered_b2b' => $deliveredB2b,
				'courierWiseCount' => $courierWiseCount
			];

			return $this->successResponse($data, 'dashboard data fetched successfully.');
		} catch (\Exception $e) {
			return $this->errorResponse($e->getMessage() . ' Line: ' . $e->getLine());
		}
	}

	protected function normalizeCourierStatus($mapping): array
	{
		if (is_array($mapping)) {
			return $mapping;
		}
		if (is_string($mapping)) {
			$decoded = json_decode($mapping, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $decoded;
			}
			return array_filter(array_map('trim', explode(',', $mapping)));
		}
		return [];
	}

	protected function countWhenStatusIn($query, array $statusList): int
	{
		if (empty($statusList)) {
			return 0;
		}
		$statusList = array_values(array_filter($statusList, fn($v) => $v !== null && $v !== ''));
		if (empty($statusList)) {
			return 0;
		}
		return (int) $query->whereIn('status_courier', $statusList)->count();
	}

	public function courierWiseCount(Request $request)
	{
		$user = Auth::user();
		$isAdmin = $user->isAdminLevel();
		$start = (int) ($request->input('start') ?? $request->input('offset') ?? 0);
		$length = (int) ($request->input('length') ?? $request->input('limit') ?? 10);

		// Base query
		$baseQuery = Order::query()
			->whereNotNull('shipping_company_id')
			->whereNotNull('courier_name')
			->when(!$isAdmin, function ($q) use ($user) {
				$q->where('user_id', $user->id);
			})
			->when($request->filled('status_courier'), function ($q) use ($request) {
				// status_courier here holds the courier name filter
				$q->where('courier_name', 'like', '%' . $request->input('status_courier') . '%');
			})
			->when($request->filled('search'), function ($q) use ($request) {
				$q->where('courier_name', 'like', '%' . $request->input('search') . '%');
			})
			->when($request->filled('fromdate'), function ($q) use ($request) {
				$q->whereDate('order_date', '>=', $request->input('fromdate'));
			})
			->when($request->filled('todate'), function ($q) use ($request) {
				$q->whereDate('order_date', '<=', $request->input('todate'));
			});

		// Calculate total count (distinct couriers)
		$totalDataQuery = (clone $baseQuery)->select('courier_name')->distinct();
		$totalData = DB::table(DB::raw("({$totalDataQuery->toSql()}) as sub"))
			->mergeBindings($totalDataQuery->getQuery())
			->count();

		// aggregation
		$rows = $baseQuery->select(
			'courier_name',
			DB::raw('COUNT(*) as total_orders'),
			DB::raw('SUM(CASE WHEN weight_order = 1 THEN 1 ELSE 0 END) as b2c_count'),
			DB::raw('SUM(CASE WHEN weight_order = 2 THEN 1 ELSE 0 END) as b2b_count')
		)
			->groupBy('courier_name')
			->orderByDesc('total_orders')
			->offset($start)
			->limit($length)
			->get();

		return $this->successResponse([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $totalData,
			'recordsFiltered' => $totalData,
			'data' => $rows
		], 'courier-wise counts fetched successfully.');
	}

	public function notification()
	{
		$user = Auth::user();
		$query = Notification::where('read_at', 0)->where('role', $user->role);

		if ($user->role == "user") {
			$query->where('user_id', $user->id);
		}

		// clone query so we don’t run it twice
		$notifications = (clone $query)->latest()->get();
		$count = $query->count(); // runs SELECT COUNT(*)

		foreach ($notifications as $notification) {
			$notification->time_ago = \Helper::posted($notification->created_at);
		}

		$data = [
			'notifications' => $notifications,
			'count' => $count
		];

		return $this->successResponse($data, 'detail fetched successfully.');
	}


	public function notificationClearAll()
	{
		DB::beginTransaction();
		try {
			$user = Auth::user();
			$query = Notification::where('role', $user->role);

			if ($user->role !== "admin") {
				$query->where('user_id', $user->id);
			}

			$query->update(['read_at' => 1]);
			DB::commit();
			return $this->successResponse([], 'Notifications have been successfully cleared.');
		} catch (\Exception $e) {
			DB::rollBack();
			return $this->errorResponse('Something went wrong.');
		}
	}

	public function rateCalculator(Request $request)
	{
		$user = Auth::user();
		$requestedCompanyId = (int) $request->input('company_id', 0);
		$shippingCompanies = ShippingCompany::query()
			->whereStatus(1)
			->when($requestedCompanyId > 0, function ($query) use ($requestedCompanyId) {
				$query->where('id', $requestedCompanyId);
			})
			->get();
		$couriers = [];

		foreach ($shippingCompanies as $shippingCompany) {
			$response = $this->rateCardService->getRate($user, $shippingCompany, $request);

			if ($response && is_array($response)) {
				$couriers[] = $response;
			}
		}

		if (empty($couriers)) {
			return $this->errorResponse($this->rateCardService->getUnavailableRateMessage($user, $request));
		}

		return $this->successResponse($couriers, 'detail fetched successfully.');
	}

	public function pincodeServiceabilityCheck(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'pickup_code' => 'required|digits:6',
			'delivery_code' => 'required|digits:6',
			'weight_order' => 'nullable|in:1,2'
		]);

		if ($validator->fails()) {
			return $this->errorResponse($validator->errors()->first(), 422);
		}

		$user = Auth::user();
		$weightOrder = (int) ($request->weight_order ?? 1);

		// Preload zipcodes for efficiency
		$zipcodes = \App\Models\Zipcode::whereIn('pincode', [$request->pickup_code, $request->delivery_code])
			->get()
			->keyBy('pincode');

		$extraData = ['zipcodes' => $zipcodes];

		$shippingCompanies = ShippingCompany::whereStatus(1)->get(['id', 'name']);
		$serviceable = [];
		$reasons = [];

		foreach ($shippingCompanies as $shippingCompany) {
			$tmpRequest = $request->duplicate([
				'type_of_package' => $weightOrder,
				'pickup_code' => $request->pickup_code,
				'delivery_code' => $request->delivery_code,
				'weight' => 1 // Dummy for filter
			]);

			$passesPincode = $this->rateCardService->passesManagedPincodeFilter(
				(int) $shippingCompany->id,
				$tmpRequest,
				$extraData
			);

			if ($passesPincode) {
				$serviceable[] = [
					'id' => $shippingCompany->id,
					'name' => $shippingCompany->name
				];
			} else {
				$message = $this->rateCardService->getUnavailableRateMessage($user, $tmpRequest, $extraData);
				$reasons[] = [
					'courier' => $shippingCompany->name,
					'reason' => $message
				];
			}
		}

		$serviceableCount = count($serviceable);
		$overallServiceable = $serviceableCount > 0;
		$message = $overallServiceable
			? "Serviceable by {$serviceableCount} couriers."
			: 'Pincode not serviceable by any courier.';

		return $this->successResponse([
			'serviceable' => $overallServiceable,
			'couriers' => $serviceable,
			'reasons' => $reasons,
			'message' => $message,
			'pickup_code' => $request->pickup_code,
			'delivery_code' => $request->delivery_code
		], 'Pincode serviceability checked successfully.');
	}

	public function updateProfile(Request $request, $id = null)
	{
		try {
			$authUser = Auth::user();
			$user = $authUser;

			if ($id !== null) {
				if (!$authUser->isAdminLevel() && (int) $authUser->id !== (int) $id) {
					return $this->errorResponse('Unauthorized access.', 403);
				}

				$user = User::find($id);
				if (!$user) {
					return $this->errorResponse('User not found.', 404);
				}
			}
			$normalizableFields = ['name', 'email', 'mobile', 'company_name', 'address', 'city', 'state', 'country', 'zip_code', 'gender'];

			foreach ($normalizableFields as $field) {
				if ($request->has($field) && is_string($request->input($field))) {
					$request->merge([
						$field => trim($request->input($field), " \t\n\r\0\x0B\"'")
					]);
				}
			}

			$rules = [
				'name' => 'sometimes|string|max:255',
				'company_name' => 'sometimes|string|max:255',
				'address' => 'sometimes|string|max:500',
				'city' => 'sometimes|string|max:255',
				'state' => 'sometimes|string|max:255',
				'country' => 'sometimes|string|max:255',
				'zip_code' => 'sometimes|digits_between:4,10',
				'gender' => 'sometimes|in:Male,Female,Other',
				'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
			];

			// Validate unique fields only if the user is actually changing them.
			if ($request->filled('email') && strtolower((string) $request->email) !== strtolower((string) $user->email)) {
				$rules['email'] = ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)];
			}

			if ($request->filled('mobile') && (string) $request->mobile !== (string) $user->mobile) {
				$rules['mobile'] = ['sometimes', 'digits_between:8,15', Rule::unique('users', 'mobile')->ignore($user->id)];
			}

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			DB::beginTransaction(); // Start transaction

			// Only profile fields; role, wallet, KYC, tokens etc. must never come from here
			$data = $request->only(array_merge($normalizableFields, ['pancard_number']));

			if ($request->hasFile('profile_image')) {
				$photo_image = $request->file('profile_image');
				$getAvatar = time() . rand(111111, 999999) . '.' . $photo_image->getClientOriginalExtension();
				$path_avatar = storage_path("app/public/profile");

				// Ensure directory exists
				if (!File::exists($path_avatar)) {
					File::makeDirectory($path_avatar, 0777, true, true);
				}

				// Delete old profile image if exists
				if ($user->profile_image && File::exists($path_avatar . '/' . $user->profile_image)) {
					File::delete($path_avatar . '/' . $user->profile_image);
				}

				// Move file
				$photo_image->move($path_avatar, $getAvatar);
				$data['profile_image'] = $getAvatar;
			}

			// Update user profile
			$user->update($data);

			// Fresh user instance to get updated data
			$user = $user->fresh();

			$user->profile_image = $user->profile_image
				? url('storage/profile/' . $user->profile_image)
				: asset('assets/images/profile-logo.png');

			DB::commit(); // Commit transaction
			return $this->successResponse($user, 'Profile has been updated successfully.');

		} catch (\Exception $e) {
			DB::rollBack();
			return $this->errorResponse('An error occurred while updating your profile. Please try again. ' . $e->getMessage());
		}
	}

	public function updatePassword(Request $request)
	{
		// Validate request input
		$validator = Validator::make($request->all(), [
			'old_password' => 'required',
			'new_password' => 'required|min:8|confirmed',
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		DB::beginTransaction();
		try {
			// Get the authenticated user
			$user = Auth::user();

			// Check if old password is correct
			if (!Hash::check($request->old_password, $user->password)) {
				return $this->errorResponse('The old password is incorrect.', 400);
			}

			// Update user password
			$user->update([
				'password' => Hash::make($request->new_password),
				'xpass' => $request->new_password, // stored encrypted (User casts xpass)
			]);

			// Revoke current token to match web's logout behavior
			$token = $user->token();
			if ($token) {
				$token->revoke();
			}

			DB::commit();
			return $this->successResponse([], 'Your password has been changed successfully. Please log in again.');

		} catch (\Exception $e) {
			DB::rollBack();
			return $this->errorResponse('An error occurred while updating your password. Please try again.');
		}
	}

	public function rechargeWalletStore(Request $request)
	{
		try {
			$user = Auth::user();
			$userId = $request->input('user_id') ?? $user->id;
			$minRechargeAmount = (float) config('setting.recharge_min_amount', 200);
			$maxRechargeAmount = (float) config('setting.recharge_max_amount', 10000);
			if ($maxRechargeAmount < $minRechargeAmount) {
				$maxRechargeAmount = $minRechargeAmount;
			}

			// Validate request
			$validator = Validator::make($request->all(), [
				'amount' => "required|numeric|min:{$minRechargeAmount}|max:{$maxRechargeAmount}"
			], [
				'amount.min' => "Minimum recharge amount is ₹{$minRechargeAmount}",
				'amount.max' => "Maximum recharge amount is ₹{$maxRechargeAmount}",
			]);

			if ($validator->fails()) {
				return $this->errorResponse($validator->errors()->first());
			}

			// Check if Razorpay is active
			if (!config('setting.razorpay_status')) {
				return $this->errorResponse('Razorpay payment gateway is currently disabled');
			}

			$amount = $request->amount;

			// Initialize Razorpay API
			$api = new \Razorpay\Api\Api(
				config('setting.razorpay_key_id'),
				config('setting.razorpay_key_secret')
			);

			// Create Razorpay order
			$orderData = [
				'receipt' => 'wallet_' . $userId . '_' . time(),
				'amount' => $amount * 100, // Convert to paise
				'currency' => 'INR',
				'notes' => [
					'user_id' => $userId,
					'purpose' => 'Wallet Recharge'
				]
			];

			$razorpayOrder = $api->order->create($orderData);

			// Save order to database
			$userWallet = UserWallet::create([
				'user_id' => $userId,
				'amount' => $amount,
				'amount_type' => 'credit',
				'transaction_type' => 'Razorpay',
				'pg_name' => 'Razorpay',
				'order_id' => $razorpayOrder->id,
				'transaction_status' => 'Pending',
				'status' => 1,
				'payable_response' => json_encode($razorpayOrder->toArray())
			]);

			return $this->successResponse([
				'order' => [
					'id' => $razorpayOrder->id,
					'amount' => $razorpayOrder->amount,
					'currency' => $razorpayOrder->currency,
					'receipt' => $razorpayOrder->receipt,
					'key_id' => config('setting.razorpay_key_id')
				],
				'wallet_id' => $userWallet->id
			], 'Order created successfully');
		} catch (\Exception $e) {
			return $this->errorResponse('Failed to create order: ' . $e->getMessage());
		}
	}

	public function rechargeWalletRazorpay(Request $request)
	{
		try {
			// Validate request
			$validator = Validator::make($request->all(), [
				'razorpay_payment_id' => 'required',
				'razorpay_order_id' => 'required',
				'razorpay_signature' => 'required'
			]);

			if ($validator->fails()) {
				return $this->errorResponse($validator->errors()->first());
			}

			// Initialize Razorpay API
			$api = new \Razorpay\Api\Api(
				config('setting.razorpay_key_id'),
				config('setting.razorpay_key_secret')
			);

			// Verify signature
			$attributes = [
				'razorpay_order_id' => $request->razorpay_order_id,
				'razorpay_payment_id' => $request->razorpay_payment_id,
				'razorpay_signature' => $request->razorpay_signature
			];

			try {
				$api->utility->verifyPaymentSignature($attributes);
			} catch (\Exception $e) {
				return $this->errorResponse('Payment signature verification failed');
			}

			// Find user wallet record
			$userWallet = UserWallet::with('user')->where('order_id', $request->razorpay_order_id)->firstOrFail();

			// Check if already processed
			if ($userWallet->transaction_status === 'Paid') {
				return $this->errorResponse('Payment already processed');
			}

			$user = $userWallet->user;

			DB::transaction(function () use ($user, $request, $userWallet, $attributes) {
				// Update wallet amount
				$user->increment('wallet_amount', $userWallet->amount);

				// Record billing
				Billing::create([
					'user_id' => $user->id,
					'billing_type' => 'Recharge Wallet',
					'billing_type_id' => $userWallet->id,
					'transaction_type' => 'credit',
					'amount' => $userWallet->amount,
					'note' => "Payment received through Razorpay. Payment ID: {$request->razorpay_payment_id}"
				]);

				// Update UserWallet record
				$userWallet->update([
					'transaction_status' => 'Paid',
					'txn_number' => $request->razorpay_payment_id,
					'utr_no' => $request->razorpay_payment_id,
					'payable_response' => json_encode($attributes)
				]);
			});

			return $this->successResponse([
				'transaction_id' => $request->razorpay_payment_id,
				'amount' => $userWallet->amount,
				'new_wallet_balance' => $user->fresh()->wallet_amount
			], 'The transaction has been charged and your wallet updated successfully.');

		} catch (\Exception $e) {
			return $this->errorResponse($e->getMessage());
		}
	}

	public function rechargeWalletFailed(Request $request)
	{
		try {
			if ($request->filled('order_id')) {
				$userWallet = UserWallet::where('order_id', $request->order_id)
					->where('user_id', Auth::id())
					->first();

				if ($userWallet) {
					$userWallet->update([
						'transaction_status' => 'Failed',
						'payable_response' => json_encode([
							'payment_id' => $request->payment_id,
							'error' => $request->reason,
							'failed_at' => now()
						])
					]);
				}
			}

			return $this->successResponse([], 'Payment failure logged');
		} catch (\Exception $e) {
			return $this->errorResponse('Failed to log payment failure: ' . $e->getMessage());
		}
	}

	public function codToWalletTransfer(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'cod_amount' => 'required|numeric|min:0.01',
				'payment_method' => 'required|in:cod',
			]);

			if ($validator->fails()) {
				return $this->errorResponse($validator->errors()->first());
			}

			$codAmount = (float) $request->cod_amount;

			// Balance comes from the server, never from the request; the row lock stops
			// two simultaneous transfers from both spending the same balance
			DB::beginTransaction();
			$user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
			$userId = $user->id;
			$balances = CodBalance::for($user);
			$codBalanceAmount = (float) $balances['transfer_balance'];
			$codBalancePercent = $balances['transfer_percent'];

			if ($codAmount > $codBalanceAmount + 0.01) {
				DB::rollBack();
				return $this->errorResponse('Insufficient COD balance. Available: ₹' . number_format($codBalanceAmount, 2));
			}

			try {
				$referenceId = CodWalletExpense::generateReferenceId();

				$codExpense = CodWalletExpense::create([
					'user_id' => $userId,
					'cod_balance_used' => $codAmount,
					'wallet_amount_added' => $codAmount,
					'transfer_percentage' => $codBalancePercent,
					'transaction_type' => 'cod_to_wallet',
					'status' => 'completed',
					'reference_id' => $referenceId,
					'remarks' => 'COD balance transferred to wallet',
					'processed_at' => now(),
				]);

				$user->increment('wallet_amount', $codAmount);

				Billing::create([
					'user_id' => $userId,
					'billing_type' => 'COD to Wallet Transfer',
					'billing_type_id' => $codExpense->id,
					'transaction_type' => 'credit',
					'amount' => $codAmount,
					'note' => "COD balance of ₹{$codAmount} transferred to wallet. Reference: {$referenceId}"
				]);

				UserWallet::create([
					'user_id' => $userId,
					'amount' => $codAmount,
					'amount_type' => 'credit',
					'transaction_type' => 'COD Utilization',
					'pg_name' => 'COD Balance',
					'transaction_status' => 'Paid',
					'txn_number' => $referenceId,
					'order_id' => null,
					'utr_no' => $referenceId,
					'status' => 1,
				]);

				DB::commit();

				return $this->successResponse([
					'reference_id' => $referenceId,
					'cod_used' => $codAmount,
					'wallet_added' => $codAmount,
					'new_wallet_balance' => $user->fresh()->wallet_amount
				], 'COD balance successfully transferred to wallet!');
			} catch (\Exception $e) {
				DB::rollBack();
				throw $e;
			}
		} catch (\Exception $e) {
			if (DB::transactionLevel() > 0) {
				DB::rollBack();
			}
			return $this->errorResponse('Failed to process COD transfer: ' . $e->getMessage());
		}
	}

	public function banners()
	{
		$banners = AppBanner::where('status', 1)->get();
		foreach ($banners as $banner) {
			$banner->banner_image = $banner->banner_image
				? url('storage/' . $banner->banner_image)
				: $banner->banner_image;
		}
		return $this->successResponse($banners, 'detail fetched successfully.');
	}

	/**
	 * List of shipping companies
	 */
	public function shippingCompanies()
	{
		try {
			$shippings = ShippingCompany::latest()->where('status', 1)->get();
			return $this->successResponse($shippings, 'Shipping companies fetched successfully.');
		} catch (\Exception $e) {
			return $this->errorResponse($e->getMessage());
		}
	}

	/**
	 * Get Secure Public Download Link for Price List PDF (For Mobile App)
	 */
	public function downloadPriceListPdf(Request $request)
	{
		try {
			$user = Auth::user();

			// Defaults: B2B and All Providers
			$type = 'b2b';

			// We encrypt the user ID so the link is secure and doesn't require login
			$token = encrypt($user->id);

			$downloadUrl = route('public.rate-card.download', [
				'token' => $token,
				'type' => $type
			]);

			return $this->successResponse([
				'download_url' => $downloadUrl
			], 'Price list download link generated successfully.');

		} catch (\Exception $e) {
			return $this->errorResponse($e->getMessage());
		}
	}

	/**
	 * List of Shipping Weights (All Companies)
	 */
	public function shippingWeight(Request $request)
	{
		try {
			$query = ShippingWeight::with(['shippingCompany:id,name'])
				->where('status', 1)
				->orderBy('weight', 'asc');

			if ($request->filled('shipping_company_id')) {
				$query->where('shipping_company_id', $request->shipping_company_id);
			}

			$weights = $query->get(['id', 'weight', 'shipping_company_id', 'status']);

			return $this->successResponse($weights, 'Shipping weights fetched successfully.');
		} catch (\Exception $e) {
			return $this->errorResponse($e->getMessage());
		}
	}
}
