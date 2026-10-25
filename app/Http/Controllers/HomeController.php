<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Order;
use App\Models\UserWallet;
use App\Models\StatusMapping;
use App\Models\UserKyc;
use App\Models\User;
use App\Models\Ticket;
use App\Models\SellerRemittance;
use App\Models\NdrDetail;
use App\Models\ShippingCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowBalanceAlert;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('auth');
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function index()
	{
		$user = Auth::user();
		$isMasterAdmin = $user->isMasterAdmin();
		$statusMappings = StatusMapping::orderBy('position')->orderBy('id')->pluck('internal_status')->toArray();

		$orderFilter = function ($query) use ($user) {
			$role = strtolower((string) $user->role);
			$hasAdminDashboard = config('permission.admin_dashboard.view') || $user->isMasterAdmin() || in_array($role, ['admin', 'reseller', 'franchise'], true);

			if ($hasAdminDashboard && in_array($role, ['admin', 'reseller', 'staff', 'franchise'], true)) {
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


		// ✅ Get all counts in one query with conditional sums - ENFORCING B2C ONLY (weight_order = 1)
		// Updated to support both old (is_shopify_order) and new (platform_source) columns
		$orderStats = $baseOrderQuery->where('weight_order', 1)->selectRaw("
			SUM(CASE WHEN (LOWER(platform_source) = 'shopify' AND is_platform_order = 1) OR is_shopify_order = 1 THEN 1 ELSE 0 END) as total_shopify_order,
			-- WooCommerce orders are treated as WordPress orders in the dashboard
			SUM(CASE WHEN LOWER(platform_source) IN ('wordpress', 'woocommerce') THEN 1 ELSE 0 END) as total_wordpress_order,
			SUM(CASE WHEN (platform_source IS NULL OR (LOWER(platform_source) NOT IN ('shopify', 'wordpress', 'woocommerce'))) AND (is_shopify_order = 0 OR is_shopify_order IS NULL) THEN 1 ELSE 0 END) as total_portal_order,
			SUM(invoice_amount) as totalInvoiceAmount,
			COUNT(*) as total_orders
		")
			->first();


		// ✅ Get Remittance Data from seller_remittances table
		$remittanceQuery = SellerRemittance::query()
			->when(!$isMasterAdmin, $orderFilter);

		// Total COD Amount in Snapshot (Sum of all processed remittances gross amount)
		$totalCodSnapshotAmount = (clone $remittanceQuery)->sum('total_cod_remittance_amount') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->sum('amount');

		// Total Paid Amount (Gross amount from completed remittances)
		$totalPaidAmount = (clone $remittanceQuery)
			->where('settlement_status', 'completed')
			->sum('total_cod_remittance_amount') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->where('voucher_status', 1)->sum('amount');

		// Total Unpaid Amount (Gross amount from pending remittances)
		$totalUnpaidAmount = (clone $remittanceQuery)
			->where('settlement_status', 'pending')
			->sum('amount_transferred_to_seller') + \App\Models\CodVoucher::when(!$isMasterAdmin, $orderFilter)->where('voucher_status', 0)->sum('net_amount');

		// Pending COD Remittance (Top Card) = Unpaid Processed Gross + Unprocessed Delivered Gross
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

		// Last Payout (most recent completed payment)
		$lastPayout = (clone $remittanceQuery)
			->where('settlement_status', 'completed')
			->whereNotNull('payment_date')
			->orderBy('payment_date', 'desc')
			->first(['payment_date', 'amount_transferred_to_seller']);

		// Upcoming Payout (pending payments)
		$upcomingPayout = (clone $remittanceQuery)
			->where('settlement_status', 'pending')
			->orderBy('payment_scheduled_date', 'asc')
			->first(['payment_scheduled_date', 'amount_transferred_to_seller']);

		// ✅ Get NDR Statistics from ndr_details table
		$ndrQuery = NdrDetail::query()
			->when(!$isMasterAdmin, function ($q) use ($user) {
				$role = strtolower((string) $user->role);

				if (in_array($role, ['admin', 'reseller', 'staff', 'franchise'], true)) {
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

		// Total NDR Orders
		$totalNdrOrders = (clone $ndrQuery)->count();

		// Resolved NDR (orders with status containing 'delivered' or attempts >= 3)
		$resolvedNdr = (clone $ndrQuery)
			->where(function ($q) {
				$q->whereRaw('LOWER(status) LIKE ?', ['%delivered%'])
					->orWhere('total_attempts', '>=', 3);
			})
			->count();

		// Pending NDR (orders that are not resolved)
		$pendingNdr = (clone $ndrQuery)
			->where(function ($q) {
				$q->whereRaw('LOWER(status) NOT LIKE ?', ['%delivered%'])
					->where('total_attempts', '<', 3);
			})
			->count();

		// ✅ Today recharge (filtered by user if not admin)
		$todayRecharge = UserWallet::when(!$isMasterAdmin, function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})
			->whereDate('created_at', $today)
			->whereRaw('LOWER(transaction_status) = ?', ['paid'])
			->sum('amount');

		$totalWalletAmount = UserWallet::when(!$isMasterAdmin, function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})
			->whereRaw('LOWER(transaction_status) = ?', ['paid'])
			->sum('amount');

		// ✅ Overall Wallet balance
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

		$totalTickets       = $ticketStats->total ?? 0;
		$totalOpenTickets   = $ticketStats->open ?? 0;
		$totalClosedTickets = $ticketStats->close ?? 0;

		// ✅ Get Client insights - filtered by staff if needed
		$clientQuery = User::where('role', 'user');
		if (!$isMasterAdmin && in_array(strtolower((string) $user->role), ['admin', 'reseller', 'staff', 'franchise'], true)) {
			$clientQuery->where('staff_id', $user->id);
		}

		$clientStats = (object) [
			'approved_clients' => (clone $clientQuery)->where('status', 1)->where('kyc_status', 1)->count(),
			'inactive_users'   => (clone $clientQuery)->where('status', 0)->count(),
		];

		// Pending KYC Requests logic: at least one document pending
		$pendingKycCount = User::where('role', 'user')
			->where('kyc_status', 0) // Only count users who aren't fully approved yet
			->whereHas('userKyc', function ($q) {
				$q->where(function ($sub) {
					$sub->where(function ($s) {
						$s->where('pancard_status', 0)->whereNotNull('pancard')->where('pancard', '!=', '');
					})->orWhere(function ($s) {
						$s->where('aadhar_status', 0)->whereNotNull('aadhar')->where('aadhar', '!=', '');
					})->orWhere(function ($s) {
						$s->where('gst_status', 0)->whereNotNull('gst')->where('gst', '!=', '');
					})->orWhere(function ($s) {
						$s->where('udyam_status', 0)->whereNotNull('udyam_number')->where('udyam_number', '!=', '');
					})->orWhere(function ($s) {
						$s->where('bank_status', 0)->whereNotNull('account_number')->where('account_number', '!=', '');
					});
				});
			})
			->when(!$isMasterAdmin && in_array(strtolower((string) $user->role), ['admin', 'reseller', 'staff', 'franchise'], true), function ($q) use ($user) {
				$q->where('staff_id', $user->id);
			})
			->count();

		$hasAdminDashboard = config('permission.admin_dashboard.view') || $isMasterAdmin || in_array(strtolower((string) $user->role), ['admin', 'reseller', 'franchise'], true);

		if ($hasAdminDashboard && $user->role != 'user') {
			// Top 10 Clients - Dynamic query grouping by user_id
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
				$hasAdminDashboard = config('permission.admin_dashboard.view') || $user->isMasterAdmin() || in_array($role, ['admin', 'reseller', 'franchise'], true);

				if ($hasAdminDashboard && in_array($role, ['admin', 'reseller', 'staff', 'franchise'], true)) {
					$riskPincodesQuery->whereExists(function ($query) use ($user) {
						$query->select(DB::raw(1))
							->from('users')
							->whereColumn('users.id', 'orders.user_id')
							->where(function ($q) use ($user) {
								$q->where('id', $user->id)
									->orWhere('staff_id', $user->id);
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
			$topClients = $riskPincodes = collect();
		}

		//$banners = DB::table('app_banners')->where('status', 1)->get();

		$userCouriers = \App\Models\ShippingCompany::where('status', 1)
			->select('id as shipping_company_id', 'name as courier_name')
			->get();


		return view('home', [
			'statusMappings' => $statusMappings,
			'userCouriers' => $userCouriers,
			'user'                  => $user,
			'todayRecharge'         => $todayRecharge,
			'availableWalletAmount'   => $availableWalletAmount,
			'totalOrders'			=> $orderStats->total_orders,
			'totalShopifyOrder'		=> $orderStats->total_shopify_order,
			'totalWordPressOrder'   => $orderStats->total_wordpress_order,
			'totalPortalOrder'		=> $orderStats->total_portal_order,
			'totalInvoiceAmount'    => $orderStats->totalInvoiceAmount,
			'totalCodAmount'        => $totalCodSnapshotAmount ?? 0,
			'totalPaidAmount'       => $totalPaidAmount ?? 0,
			'totalUnpaidAmount'     => $totalUnpaidAmount ?? 0,
			'pendingCodRemittance'  => $pendingCodRemittance ?? 0,
			'lastPayout'            => $lastPayout,
			'upcomingPayout'        => $upcomingPayout,
			'totalWalletAmount'     => $totalWalletAmount,
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
			'totalTickets' => $totalTickets,
			'totalOpenTickets' => $totalOpenTickets,
			'totalClosedTickets' => $totalClosedTickets,
			'totalNdrOrders' => $totalNdrOrders,
			'resolvedNdr' => $resolvedNdr,
			'pendingNdr' => $pendingNdr,
			'topClients' => $topClients,
			'riskPincodes' => $riskPincodes,
			'hasAdminDashboard' => $hasAdminDashboard,
			'clientStats' => $clientStats,
			'pendingKycCount' => $pendingKycCount,
		]);
	}

	function normalizeCourierStatus($mapping): array
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

	function countWhenStatusIn($query, array $statusList): int
	{
		if (empty($statusList)) {
			return 0;
		}

		// if values in statusList are null/empty filter them out
		$statusList = array_values(array_filter($statusList, function ($v) {
			return $v !== null && $v !== '';
		}));

		if (empty($statusList)) {
			return 0;
		}

		return (int) $query->whereIn('status_courier', $statusList)->count();
	}

	public function courierWiseTotalAjax(Request $request)
	{
		// DataTables params
		$draw = (int) $request->post('draw', 1);
		$start = (int) $request->post('start', 0);
		$length = (int) $request->post('length', 10); // default 10

		$orderArr = $request->post('order', []);
		$columnsArr = $request->post('columns', []);
		$searchVal = $request->input('search.value', $request->input('search', null));

		// Map DataTables column index/name to DB ordering expression (safe)
		// Columns expected by frontend: courier_name, total_orders, b2c_count, b2b_count
		$orderMap = [
			'courier_name' => 'courier_name',
			'total_orders' => DB::raw('total_orders'),
			'b2c_count' => DB::raw('b2c_count'),
			'b2b_count' => DB::raw('b2b_count'),
		];

		// Determine order column and direction
		$orderColumnIndex = $orderArr[0]['column'] ?? null;
		$orderDir = ($orderArr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
		$orderColumnName = 'total_orders'; // default

		if ($orderColumnIndex !== null && isset($columnsArr[$orderColumnIndex]['data'])) {
			$candidate = $columnsArr[$orderColumnIndex]['data'];
			if (isset($orderMap[$candidate])) {
				$orderColumnName = $candidate;
			}
		}

		// Base query: apply filters first, then aggregate
		$baseQuery = Order::query()
			->whereNotNull('shipping_company_id')
			->whereNotNull('courier_name')
			->where('courier_name', 'not like', '%ShipMozo%')
			->when($request->filled('status_courier'), function ($q) use ($request) {
				// status_courier here holds the courier name filter
				$q->where('courier_name', 'like', '%' . $request->input('status_courier') . '%');
			})
			->when($request->filled('fromdate'), function ($q) use ($request) {
				$q->whereDate('order_date', '>=', $request->input('fromdate'));
			})
			->when($request->filled('todate'), function ($q) use ($request) {
				$q->whereDate('order_date', '<=', $request->input('todate'));
			})
			// now aggregate
			->select(
				'courier_name',
				DB::raw('COUNT(*) as total_orders'),
				DB::raw('SUM(CASE WHEN weight_order = 1 THEN 1 ELSE 0 END) as b2c_count'),
				DB::raw('SUM(CASE WHEN weight_order = 2 THEN 1 ELSE 0 END) as b2b_count')
			)
			->groupBy('courier_name');


		// Apply global search (search across courier_name)
		if (!empty($searchVal)) {
			$baseQuery->havingRaw('courier_name LIKE ?', ["%{$searchVal}%"]);
			// Note: because we're grouping, search must be in HAVING or use subquery approach.
		}

		// Count total distinct couriers (without filters) - use a raw query for performance
		$recordsTotal = (int) Order::whereNotNull('shipping_company_id')
			->whereNotNull('courier_name')
			->select('courier_name')
			->distinct()
			->count();

		// Count filtered records (after search)
		// Because of groupBy, easiest way is to wrap into a subquery
		$filteredQuery = DB::table(DB::raw("({$baseQuery->toSql()}) as sub"))
			->mergeBindings($baseQuery->getQuery()) // merge bindings so raw works
			->select(DB::raw('COUNT(*) as count'));
		$recordsFilteredRow = $filteredQuery->first();
		$recordsFiltered = $recordsFilteredRow ? (int) $recordsFilteredRow->count : 0;

		// Apply ordering (we can order by alias names since DB returns them)
		// Build a query to fetch paginated results; use order by appropriate alias/column
		$finalQuery = $baseQuery;

		// If ordering on aggregate columns, use orderByRaw for alias fallback
		if (in_array($orderColumnName, ['total_orders', 'b2c_count', 'b2b_count'])) {
			$finalQuery->orderBy(DB::raw($orderColumnName), $orderDir);
		} else {
			$finalQuery->orderBy($orderColumnName, $orderDir);
		}

		// Apply pagination
		if ($length > 0) {
			$finalQuery->offset($start)->limit($length);
		}

		$rows = $finalQuery->get();

		// Build response data
		$data = $rows->map(function ($r, $index) use ($start, $request) {

			$b2cUrl = url('order')
				. '?weight_order=1'
				. '&status=All'
				. '&courier_name=' . urlencode($r->courier_name)
				. '&from_date=' . urlencode($request->fromdate ?? '')
				. '&to_date=' . urlencode($request->todate ?? '');

			$b2bUrl = url('order')
				. '?weight_order=2'
				. '&status=All'
				. '&courier_name=' . urlencode($r->courier_name)
				. '&from_date=' . urlencode($request->fromdate ?? '')
				. '&to_date=' . urlencode($request->todate ?? '');

			return [
				'id' => $start + $index + 1,
				'courier_name' => $r->courier_name,
				'total_orders' => (int) $r->total_orders,
				'b2c_count' => '<a target="_blank" href="' . $b2cUrl . '">'
					. (int) $r->b2c_count .
					'</a>',

				'b2b_count' => '<a target="_blank" href="' . $b2bUrl . '">'
					. (int) $r->b2b_count .
					'</a>',
			];
		});

		return response()->json([
			'draw' => $draw,
			'recordsTotal' => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data' => $data
		]);
	}

	public function getLogisticsChartData(Request $request)
	{
		$user = Auth::user();
		$isAdmin = $user->isAdminLevel();

		$query = Order::query()
			->where('weight_order', 1)
			->where('courier_name', 'not like', '%ShipMozo%');

		// 1. Role Check
		if (!$isAdmin) {
			$query->where('user_id', $user->id);
		}

		// 2. Filter by Courier (dropdown)
		if ($request->filled('shipping_company_id')) {
			$query->where('shipping_company_id', 'like', '%' . $request->shipping_company_id . '%');
		}

		// 3. Dynamic Aggregation
		// VOIDED: Static case statements removed.
		// New Logic: Group by status_courier directly from DB.
		$statusCounts = $query->select('status_courier', DB::raw('count(*) as count'))
			->groupBy('status_courier')
			->get();

		// Prepare Labels and Data
		// We'll always include "Total Shipments" as the first item
		$totalShipments = $statusCounts->sum('count');

		$labels = ['Total Shipments'];
		$dataValues = [$totalShipments];

		// Map DB statuses to Chart
		foreach ($statusCounts as $status) {
			// Use the DB value 'status_courier' as the label.
			// You might want to ucfirst() or format it if needed.
			$label = $status->status_courier ?: 'Unknown';
			$labels[] = ucfirst($label);
			$dataValues[] = (int) $status->count;
		}

		// Generate Colors (Dynamic or Fixed Palette)
		// We start with a dark color for Total, then cycle grays/others
		$colors = ['#424242']; // for Total
		$baseColors = [
			'#9e9e9e',
			'#cfcfcf',
			'#b0b0b0',
			'#e0e0e0',
			'#757575',
			'#f2f2f2',
			'#6c757d',
			'#343a40',
			'#adb5bd',
			'#495057'
		];

		foreach ($statusCounts as $index => $status) {
			// Cycle through base colors
			$colors[] = $baseColors[$index % count($baseColors)];
		}

		return response()->json([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => 'Orders',
					'data' => $dataValues,
					'backgroundColor' => $colors,
					'borderWidth' => 1,
					'maxBarThickness' => 40,
					'barPercentage' => 0.8,
					'categoryPercentage' => 0.8
				]
			]
		]);
	}

	public function notification()
	{
		$user = Auth::user();
		$query = Notification::where('read_at', 0);

		if ($user->role == "user") {
			$query->where('user_id', $user->id)->where('role', 'user');
		} else if ($user->isAdminLevel()) {
			$query->where('role', 'admin');
		} else {
			$query->where('role', $user->role);
		}

		// clone query so we don’t run it twice
		$notifications = (clone $query)->latest()->get();

		$count = $query->count(); // runs SELECT COUNT(*)
		$view = view('notification', compact('notifications'))->render();

		return response()->json(['view' => $view, 'count' => $count]);
	}

	public function notificationClearAll()
	{
		DB::beginTransaction();
		try {
			$user = Auth::user();

			if ($user->role == 'user') {
				Notification::where('user_id', $user->id)
				            ->where('role', 'user')
				            ->update(['read_at' => 1]);
			} else if ($user->isAdminLevel()) {
				Notification::where('role', 'admin')
				            ->update(['read_at' => 1]);
			} else {
				Notification::where('role', $user->role)
				            ->update(['read_at' => 1]);
			}

			DB::commit();

			return back()->with('success', 'Notifications have been successfully cleared.');
		} catch (\Exception $e) {
			DB::rollBack();
			return back()->with('error', 'Something went wrong.');
		}
	}

	public function checkAmount()
	{
		$user = auth()->user();

		// Ensure only admins can trigger this check
		if (!$user->isAdminLevel()) {
			return response()->json(['showPopup' => false]);
		}

		// Fetch users with low wallet balance
		$lowBalanceUsers = UserWallet::select('users.id', 'users.name', 'user_wallets.amount')
			->join('users', 'user_wallets.user_id', '=', 'users.id')
			->where('user_wallets.amount', '<=', 100)
			->get();

		if ($lowBalanceUsers->isEmpty()) {
			return response()->json(['showPopup' => false]);
		}

		$userNames = $lowBalanceUsers->pluck('name')->toArray();
		$notifications = [];

		try {
			DB::beginTransaction(); // Start transaction

			foreach ($lowBalanceUsers as $userNotify) {
				$notifications[] = [
					'user_id'    => $userNotify->id,
					'role'       => "admin",
					'type'       => "Low Balance",
					'text'       => "{$userNotify->name} has a low wallet balance.",
					'created_at' => now(),
					'updated_at' => now(),
				];
			}

			// Bulk insert notifications for efficiency
			Notification::insert($notifications);

			DB::commit(); // Commit transaction

			return response()->json(['showPopup' => true, 'users' => $userNames]);
		} catch (\Exception $e) {
			DB::rollBack(); // Rollback transaction in case of failure
			Log::error('Low balance notification failed: ' . $e->getMessage());
			return response()->json(['showPopup' => false]);
		}
	}
	public function lowbalance()
	{
		$user = auth()->user();

		if ($user->role !== 'user') {
			return response()->json(['showPopup' => false]);
		}

		$lowBalanceUser = User::where('id', $user->id)
			->where('wallet_amount', '<=', 100)
			->first(['id', 'name', 'email', 'mobile', 'sms_date']);

		if (!$lowBalanceUser) {
			return response()->json(['showPopup' => false]);
		}

		$today = now();
		$smsDate = $lowBalanceUser->sms_date ? Carbon::parse($lowBalanceUser->sms_date) : null;

		// Message content
		$message = "Alert! Low wallet balance. Top-up needed for continued service. Thank you. Regards, " . config('setting.company_name');

		try {
			DB::beginTransaction(); // Start transaction

			// Send email & SMS only if it hasn’t been sent today
			if (!$smsDate || !$smsDate->isSameDay($today)) {
				// Send Email Notification
				try {
					Mail::to($lowBalanceUser->email)->send(new LowBalanceAlert($lowBalanceUser, $message));
				} catch (\Exception $e) {
				}

				// Update SMS date to prevent duplicate messages
				$lowBalanceUser->update(['sms_date' => $today->format('Y-m-d')]);
			}

			DB::commit(); // Commit transaction
			return response()->json([
				'showPopup' => true,
				'msg' => "You have low balance in your wallet!"
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['showPopup' => false, 'msg' => "You have low balance in your wallet!"]);
		}
	}

	public function kycpending()
	{
		$user = auth()->user();

		if ($user->role !== 'user') {
			return response()->json(['showPopup' => false]);
		}

		$userKyc = UserKyc::where('user_id', $user->id)->first();

		if (!$userKyc) {
			return response()->json(['showPopup' => false]);
		}

		$kycChecks = [
			'pancard_status' => 'PanCard',
			'aadhar_status' => 'Aadhar Card',
			'bank_status' => 'Bank',
		];

		foreach ($kycChecks as $field => $kycType) {
			if ($userKyc->$field == 0) {
				return response()->json([
					'showPopup' => true,
					'msg' => "You need to complete your {$kycType} KYC Request!!!"
				]);
			}
		}
		return response()->json(['showPopup' => false]);
	}
}
