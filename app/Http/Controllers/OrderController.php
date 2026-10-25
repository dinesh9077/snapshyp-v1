<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Response, Storage, Validator, Http, Log};
use App\Models\{
	Order,
	OrderItem,
	OrderActivity,
	OrderStatus,
	Billing,
	Customer,
	User,
	Shop,
	CustomerAddress,
	ShippingCompany,
	CourierWarehouse,
	StatusMapping,
	CodVoucher,
	LabelPreference,
	CourierCommission,
	RateCardDetail,
	UserWallet
};
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use App\Services\ShipMozo;
use App\Services\DelhiveryB2CService;
use App\Services\RateCardService;
use App\Imports\BulkOrder;
use App\Support\OrderType;
use App\Exports\BulkOrderSampleExport;
use App\Jobs\ProcessBulkShipment;
use App\Helpers\Helper;
use DNS1D, DNS2D;
use App\Exports\CodRemittanceExport;
use App\Services\DelhiveryService;
use App\Services\RoutxService;
use App\Services\ShadowfaxService;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Support\Str;
use App\Exports\OrderListingExport;

class OrderController extends Controller
{
	protected $delhiveryB2CService;
	protected $shipMozo;
	protected $rateCardService;
	protected $delhiveryService;
	protected $routxService;
	protected $shadowfaxService;
	public function __construct()
	{
		$this->middleware('auth')->except(['orderLableGenerate']);
		$this->shipMozo = new ShipMozo();
		$this->delhiveryService = new DelhiveryService();
		$this->delhiveryB2CService = new DelhiveryB2CService();
		$this->rateCardService = new RateCardService();
		$this->routxService = new RoutxService();
		$this->shadowfaxService = new ShadowfaxService();
	}

	public function index()
	{
		// Default should land on ALL when no status is provided
		$status = request()->query('status', 'All');
		// Force B2C (weight_order = 1) as B2B is disabled
		$weightOrder = 1;
		if (!request('weight_order') || request('weight_order') != 1) {
			$params = request()->all();
			$params['weight_order'] = 1;
			return redirect()->to('order?' . http_build_query($params));
		}

		$status = request()->query('status', 'All');
		$statusMappings = StatusMapping::orderBy('position')->orderBy('id')->pluck('internal_status')->toArray();
		// Role-aware sellers list: admin sees all, users see only their own entry
		$authUser = auth()->user();
		$user = $authUser;
		$role = $user->role ?? null;
		$sellers = null;
		if (in_array($role, ['admin', 'master_admin'], true)) {
			$cacheKey = $role === 'master_admin' ? 'order_sellers_list_all' : 'order_sellers_list_admin_' . $user->id;
			$sellers = Cache::remember($cacheKey, 300, function () use ($role, $user) {
				$q = Order::join('users', 'users.id', '=', 'orders.user_id')
					->select('users.id', 'users.name');
				if ($role === 'admin') {
					$q->where('users.staff_id', $user->id);
				}
				return $q->groupBy('users.id', 'users.name')
					->orderBy('users.name', 'asc')
					->get();
			});
		}

		$shipments = ShippingCompany::select('id', 'name')
			->where('status', 1)
			->get();

		$warehouses = CourierWarehouse::where('user_id', $authUser->id)->where('warehouse_status', 1)->get();

		$start = new \DateTime('09:00');
		$end = new \DateTime('21:00');
		$interval = new \DateInterval('PT3H');
		$period = new \DatePeriod($start, $interval, $end);
		$timeSlots = [];
		foreach ($period as $dt) {
			$slotStart = $dt->format('h:i A');
			$slotEnd = $dt->add($interval)->format('h:i A');
			$timeSlots[] = "$slotStart - $slotEnd";
		}

		$internalStatuses = StatusMapping::getInternalStatuses();
		return view('order.index', compact('status', 'sellers', 'authUser', 'shipments', 'warehouses', 'statusMappings', 'timeSlots', 'internalStatuses'));
	}

	public function getFilterOptions()
	{
		$authUser = auth()->user();
		$user = $authUser;

		// Role-aware tags: admin sees all tags, users see tags only from their own orders
		$tagsCacheKey = 'order_unique_tags_' . (($user->isMasterAdmin()) ? 'all' : 'user_' . $user->id);
		$tags = Cache::remember($tagsCacheKey, 300, function () use ($user) {

			$query = Order::query()
				->whereNotNull('shopify_tags')
				->where('shopify_tags', '!=', '');

			if (!$user->isMasterAdmin()) {
                if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
                    $query->where(function ($q) use ($user) {
                        $q->where('orders.user_id', $user->id)
                          ->orWhereHas('user', function ($subQ) use ($user) {
                              $subQ->where('staff_id', $user->id);
                          });
                    });
                } else {
				    $query->where('orders.user_id', $user->id);
                }
			}

			$rows = $query->pluck('shopify_tags');

			$unique = [];

			foreach ($rows as $val) {

				// Already array because of cast
				if (is_array($val)) {
					foreach ($val as $t) {
						if (!empty($t)) {
							$unique[trim($t)] = true;
						}
					}
					continue;
				}

				// Fallback: comma-separated string
				foreach (explode(',', $val) as $t) {
					$t = trim($t);
					if ($t !== '') {
						$unique[$t] = true;
					}
				}
			}

			return array_keys($unique);
		});

		$warehouse = CourierWarehouse::query();
		if (!$user->isMasterAdmin()) {
            if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
                $warehouse->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('user', function ($subQ) use ($user) {
                          $subQ->where('staff_id', $user->id);
                      });
                });
            } else {
			    $warehouse->where('user_id', $user->id);
            }
		}
		$warehouses = $warehouse->get();

		$statuses = Order::distinct();
		if (!$user->isMasterAdmin()) {
            if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
                $statuses->where(function ($q) use ($user) {
                    $q->where('orders.user_id', $user->id)
                      ->orWhereHas('user', function ($subQ) use ($user) {
                          $subQ->where('staff_id', $user->id);
                      });
                });
            } else {
			    $statuses->where('orders.user_id', $user->id);
            }
		}
		$statuses = $statuses->pluck('status_courier');

		// Distinct states and cities from customer addresses used in orders
		$addressQuery = Order::join('customer_addresses', 'customer_addresses.id', '=', 'orders.customer_address_id')
			->select('customer_addresses.state', 'customer_addresses.city')
			->whereNotNull('customer_addresses.state')
			->where('customer_addresses.state', '!=', '')
			->whereNotNull('customer_addresses.city')
			->where('customer_addresses.city', '!=', '');

		if (!$user->isMasterAdmin()) {
            if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
                $addressQuery->where(function ($q) use ($user) {
                    $q->where('orders.user_id', $user->id)
                      ->orWhereHas('user', function ($subQ) use ($user) {
                          $subQ->where('staff_id', $user->id);
                      });
                });
            } else {
			    $addressQuery->where('orders.user_id', $user->id);
            }
		}

		$states = (clone $addressQuery)->distinct()->orderBy('customer_addresses.state')
			->pluck('customer_addresses.state');

		$cities = (clone $addressQuery)->distinct()->orderBy('customer_addresses.city')
			->pluck('customer_addresses.city');

		$courier_names = Order::distinct();
		if (!$user->isMasterAdmin()) {
            if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
                $courier_names->where(function ($q) use ($user) {
                    $q->where('orders.user_id', $user->id)
                      ->orWhereHas('user', function ($subQ) use ($user) {
                          $subQ->where('staff_id', $user->id);
                      });
                });
            } else {
			    $courier_names->where('orders.user_id', $user->id);
            }
		}
		$courier_names = $courier_names->whereNotNull('courier_name')->where('courier_name', '!=', '')->pluck('courier_name');

		$view = view('order.filter', compact('tags', 'warehouses', 'statuses', 'states', 'cities', 'courier_names', 'authUser'))->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function getStatusCounts()
	{
		$user = Auth::user();
		$weightOrder = request('weight_order', 1);

		$statusMappings = StatusMapping::orderBy('position')->get();

		$baseQuery = Order::query();
		if (!$user->isMasterAdmin()) {
            if (in_array(strtolower($user->role), ['admin',  'reseller', 'staff'])) {
                $baseQuery->where(function ($q) use ($user) {
                    $q->where('orders.user_id', $user->id)
                      ->orWhereHas('user', function ($subQ) use ($user) {
                          $subQ->where('staff_id', $user->id);
                      });
                });
            } else {
			    $baseQuery->where('orders.user_id', $user->id);
            }
		}

		// Filter by weight order
		if ($weightOrder) {
			$baseQuery->where('orders.weight_order', $weightOrder);
		}

		if (request()->filled('is_shopify_order')) {
			if (request()->is_shopify_order == 1) {
				$baseQuery->where(function ($q) {
					$q->where('orders.is_shopify_order', 1)
						->orWhere('orders.platform_source', 'shopify');
				});
			} else {
				$baseQuery->where('orders.is_shopify_order', 0);
			}
		}

		if (request()->filled('is_wordpress_order')) {
			// WooCommerce orders should be counted under WordPress bucket
			$baseQuery->whereIn('orders.platform_source', ['wordpress', 'woocommerce', 'WordPress', 'WooCommerce']);
		}

		if (request()->filled('is_portal_order')) {
			$baseQuery->where(function ($q) {
				$q->where('orders.is_shopify_order', 0)
					->orWhereNull('orders.is_shopify_order');
			})
				->where(function ($q) {
					$q->whereNull('orders.platform_source')
						->orWhereNotIn('orders.platform_source', ['shopify', 'wordpress', 'WordPress', 'woocommerce', 'WooCommerce']);
				});
		}

		// Get status counts from DB in one go
		$rawCounts = (clone $baseQuery)
			->select('status_courier', DB::raw('count(*) as total'))
			->groupBy('status_courier')
			->get();

		$statusCounts = [];
		foreach ($rawCounts as $rc) {
			$s = strtolower(trim($rc->status_courier));
			$statusCounts[$s] = ($statusCounts[$s] ?? 0) + $rc->total;
		}

		$counts = [];
		$grandTotal = 0;

		foreach ($statusMappings as $mapping) {
			$count = 0;
			$courierStatuses = $mapping->courier_status;

			if (!empty($courierStatuses)) {
				if (!is_array($courierStatuses)) {
					$courierStatuses = explode(',', $courierStatuses);
				}

				foreach ($courierStatuses as $cs) {
					$normalized = strtolower(trim($cs));
					$count += ($statusCounts[$normalized] ?? 0);
				}
			}

			$counts[$mapping->internal_status] = $count;
			$grandTotal += $count;
		}

		// "All" count should be the sum of all orders in this filtered set
		$counts['All'] = (clone $baseQuery)->count();

		return response()->json(['status' => 'success', 'counts' => $counts]);
	}

	public function orderAjax(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->post("start");
		$limit = $request->post("length"); // Rows per page

		$columnIndex_arr = $request->post('order');
		$columnName_arr = $request->post('columns');
		$order_arr = $request->post('order');
		$search_arr = $request->post('search');
		$status = strtolower($request->post('status'));
		$weightOrder = strtolower($request->post('weightOrder'));

		$columnIndex = $columnIndex_arr[0]['column'] ?? 0; // Column index
		$order = $columnName_arr[$columnIndex]['data'] ?? 'id'; // Column name
		$dir = $order_arr[0]['dir'] ?? 'desc'; // Sorting direction

		// Ensure 'action' column is ordered by 'id'
		if ($order == 'action') {
			$order = 'id';
		}

		$user = Auth::user();
		$role = $user->role;
		$query = $this->buildOrderListingQuery($request);

		$totalData = $query->count();
		$totalFiltered = $totalData;

		// Apply search filter
		if (!empty($search_arr)) {
			$this->applyOrderListingSearch($query, $search_arr);
			$totalFiltered = $query->count();
		}
		// Fetch paginated and sorted data
		\Log::info('Order AJAX Query: ' . $query->toSql());
		\Log::info('Order AJAX Bindings: ' . json_encode($query->getBindings()));

		$orders = $query->offset($start)
			->limit($limit)
			->orderBy('orders.' . $order, $dir)
			->get();

		// Prepare data response
		$data = [];
		$i = $start + 1;

		foreach ($orders as $j => $order) {
			$isCOD   = strtolower($order->order_type) === "cod";
			$amount  = $isCOD ? $order->cod_amount : $order->invoice_amount;
			$label   = $isCOD ? 'COD Amount' : 'Invoice Amount';

			$warehouse = optional($order->warehouse);
			$customer  = optional($order->customer);
			$address   = optional($order->customerAddress)->address;
			$warehouseFullAddress = trim(implode(', ', array_filter([
				$warehouse->address ?? null,
				$warehouse->city ?? null,
				$warehouse->state ?? null,
				$warehouse->zip_code ?? null,
			], function ($v) {
				return $v !== null && trim((string) $v) !== '';
			})));

			$orderLink = url("order/details/{$order->id}") .
				'?weight_order=' . urlencode($weightOrder) .
				'&status=' . urlencode(ucwords($status));

			$orderHtml = "<a href='{$orderLink}' class='order-id-link'>";
			$orderHtml .= "<strong>ID:</strong> #" . e($order->order_prefix ?? $order->id);
			
			$refNum = !empty($order->reference_number) ? e($order->reference_number) : '-';
			$orderHtml .= "<br><span class='text-muted'>Ref No:</span> " . $refNum;

			// Always show user name and company name
			$orderUserName    = $order->user->name ?? 'N.A';
			$orderCompanyName = $order->user->company_name ?? ($warehouse->company_name ?? null);

			$orderHtml .= "<br><span class='text-muted'>User:</span> " . e($orderUserName);

			if (!empty($orderCompanyName)) {
				$orderHtml .= "<br><span class='text-muted'>Company:</span> " . e($orderCompanyName);
			}

			$orderHtml .= "</a>";

			// Show Shopify order info only if exists
			if (!empty($order->shopify_order_name) || !empty($order->shopify_order_id)) {
				$orderHtml .= "<br>Shopify Order : " .
					e($order->shopify_order_name ?? $order->shopify_order_id);

				if (!empty($order->confirm_shopify_order)) {
					$confirmName = $order->confirm_shopify_order == 1 ? 'Confirmed By Customer' : 'Cancelled By Customer';
					$orderHtml .= '<span class="ml-2 text-warning">' . $confirmName . '</span>';
				}
				$orderHtml .= '<br><img src="' . url('assets/shopify.png') . '" alt="Shopify" style="width: 25px;">';
			}

			 if (!empty($order->woocommerce_order_id)) {
				$orderHtml .= '<br>WooCommerce Order : ' . e($order->woocommerce_order_number ?? $order->woocommerce_order_id) . '</p>';
				$orderHtml .= '<img src="' . url('assets/wordpress.jpg') . '" alt="WordPress" style="width: 25px;">';
			}

			// Check if order is credited
			$order->is_credited = UserWallet::where('order_id', $order->id)
				->where('transaction_type', 'Lost Order Credit')
				->exists();

			$data[] = [
				'DT_RowId' => 'order-row-' . $order->id,
				'DT_RowClass' => $order->queue == 1 && strtolower($order->status_courier) == "new" ? 'processing-row' : '',
				'id' => ($status == "all") ? $i : (($order->queue == 1)
					? "<input type='checkbox' class='order-checkbox' value='' disabled>"
					: "<input type='checkbox' class='order-checkbox' value='{$order->id}' data-shipping-company-id='{$order->shipping_company_id}'>"),
				//'id' => $i,

				'order_id' => "<div class='order-id-wrap'>{$orderHtml}</div>",


				// Seller details
				'seller_details' => "
						<div class='main-cont1-2 seller-details-wrap'>
							<p><strong>" . e($warehouse->warehouse_name) . "</strong></p>
							<p class='small-text'>" . e(trim((string) ($warehouse->contact_name ?? ''))) . " | " . e(trim((string) ($warehouse->contact_number ?? ''))) . "</p>
							<p class='small-text address-text'>" . e($warehouseFullAddress ?: 'N/A') . "</p>
						</div>",

				// Customer details + Tooltip
				'customer_details' => "
						<div class='main-cont1-2 customer-details-wrap'>
							<p><strong>" . optional($customer)->first_name . " " . optional($customer)->last_name . "</strong></p>
							<p class='small-text'>" . optional($customer)->mobile . "</p>
							 <div class='tooltip small-text'>View Address<span class='tooltiptext'><b>" . optional($customer)->first_name . " " . optional($customer)->last_name .
					"</b><br><b>Address</b>: " . $address . "</span></div>
						</div>
					",

				// Shipment details
				'shipment_details' => $this->orderShipmentDetailHtml($order, $status, $weightOrder),

				// Package details
				'package_details' => $this->orderPackageDetailHtml($order),

				// Courier + Action
				'status_courier' => $this->statusCourieHtml($order),
				'delivery_date' => $order->delivery_date ?? 'N/A',
				'action'         => $this->orderAction($order, $status, $weightOrder),
			];

			$i++;
		}


		return response()->json([
			"draw" => intval($draw),
			"recordsTotal" => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data" => $data,
			"iTotalRecords" => intval($totalData),
			"iTotalDisplayRecords" => intval($totalFiltered),
			"aaData" => $data
		]);
	}

	public function exportExcel(Request $request)
	{
		$query = $this->buildOrderListingQuery($request);
		$this->applyOrderListingSearch($query, $request->input('search'));

		$orders = $query->orderBy('orders.id', 'desc')->get();
		$status = Str::slug((string) $request->input('status', 'all'));
		$weightOrder = $request->input('weightOrder', $request->input('weight_order', 1));
		$fileName = 'orders_' . ($status ?: 'all') . '_w' . $weightOrder . '_' . date('Ymd_His') . '.xlsx';

		return Excel::download(new OrderListingExport($orders), $fileName);
	}

	private function buildOrderListingQuery(Request $request)
	{
		$status = strtolower((string) $request->input('status'));
		$weightOrder = 1;


		$user = Auth::user();
		$role = strtolower((string) $user->role);
		$id = $user->id;

		$query = Order::with([
			'customer:id,first_name,last_name,mobile,email',
			'customerAddress:id,address,city,state,country,zip_code',
			'warehouse:id,warehouse_name,contact_name,contact_number,company_name,address,city,state,zip_code',
			'user:id,name,company_name,email,mobile',
			'orderItems:id,order_id,product_category,product_name,sku_number,hsn_number,amount,quantity,dimensions',
		])

			->when(!$user->isMasterAdmin(), function ($q) use ($role, $id) {
				if (in_array($role, ['admin', 'reseller', 'staff'], true)) {
					$q->where(function ($subQ) use ($id) {
						$subQ->where('orders.user_id', $id)
							->orWhereHas('user', function ($uq) use ($id) {
								$uq->where('staff_id', $id);
							});
					});
				} else {
					$q->where('orders.user_id', $id);
				}
			});

		if ($status !== '' && $status !== "all") {
			$statusMappings = StatusMapping::orderBy('position')->pluck('courier_status', 'internal_status')->toArray();
			$statusCourier = $statusMappings[$request->input('status')] ?? [];

			if (!empty($statusCourier)) {
				if (!is_array($statusCourier)) {
					$statusCourier = explode(',', $statusCourier);
				}

				$statusCourierOriginal = $statusCourier;
				$statusCourierLower = array_map(fn($s) => strtolower(trim($s)), $statusCourierOriginal);
				$statusCourierUpper = array_map(fn($s) => strtoupper(trim($s)), $statusCourierOriginal);
				$statusCourierTitle = array_map(fn($s) => ucwords(trim($s)), $statusCourierOriginal);

				$statusCourierAll = array_unique(array_merge($statusCourierOriginal, $statusCourierLower, $statusCourierUpper, $statusCourierTitle));
				$query->whereIn('status_courier', $statusCourierAll);
			}
		}

		if ($request->filled('courier_name')) {
			$query->where('orders.courier_name', $request->courier_name);
		}

		if ($request->filled('status_courier')) {
			$query->where('orders.status_courier', $request->status_courier);
		}

		if ($request->filled('user_id')) {
			$query->where('orders.user_id', $request->user_id);
		}

		if ($request->filled('is_shopify_order')) {
			if ($request->is_shopify_order == 1) {
				$query->where(function ($q) {
					$q->where('orders.is_shopify_order', 1)
						->orWhere('orders.platform_source', 'shopify');
				});
			} else {
				$query->where('orders.is_shopify_order', 0);
			}
		}

		if ($request->filled('platform_source')) {
			$query->where('orders.platform_source', $request->platform_source);
		}

		if ($request->filled('is_wordpress_order')) {
			// WooCommerce orders should be grouped under WordPress filter
			$query->whereIn('orders.platform_source', ['wordpress', 'woocommerce', 'WordPress', 'WooCommerce']);
		}

		if ($request->filled('is_portal_order')) {
			$query->where(function ($q) {
				$q->where('orders.is_shopify_order', 0)
					->orWhereNull('orders.is_shopify_order');
			})
				->where(function ($q) {
					$q->whereNull('orders.platform_source')
						->orWhereNotIn('orders.platform_source', ['shopify', 'wordpress', 'WordPress', 'woocommerce', 'WooCommerce']);
				});
		}


		if ($request->filled('warehouse_id')) {
			$query->where('orders.warehouse_id', (int) $request->warehouse_id);
		}

		if ($request->filled('payment_type')) {
			$query->where('orders.order_type', $request->payment_type);
		}

		if ($request->filled('invoice_no')) {
			$query->where('orders.invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
		}

		// Weight range filter (Kg)
		if ($request->filled('min_weight') || $request->filled('max_weight')) {
			$min = $request->input('min_weight') !== null && $request->input('min_weight') !== '' ? (float) $request->min_weight : null;
			$max = $request->input('max_weight') !== null && $request->input('max_weight') !== '' ? (float) $request->max_weight : null;

			if (!is_null($min) && !is_null($max)) {
				$query->whereBetween('orders.weight', [$min, $max]);
			} elseif (!is_null($min)) {
				$query->where('orders.weight', '>=', $min);
			} elseif (!is_null($max)) {
				$query->where('orders.weight', '<=', $max);
			}
		}

		// State / City filter from customer address
		if ($request->filled('state') || $request->filled('city')) {
			$query->whereHas('customerAddress', function ($q) use ($request) {
				if ($request->filled('state')) {
					$q->where('state', $request->state);
				}
				if ($request->filled('city')) {
					$q->where('city', $request->city);
				}
			});
		}

		if ($request->filled('date_by') && $request->filled('from_date') && $request->filled('to_date')) {
			$query->whereBetween($request->date_by, [
				Carbon::parse($request->from_date)->startOfDay(),
				Carbon::parse($request->to_date)->endOfDay(),
			]);
		}

		$shippingCompanyId = $request->input('shipping_company_id', $request->input('shippingCompanyId'));
		if (!empty($shippingCompanyId)) {
			$query->where('orders.shipping_company_id', (int) $shippingCompanyId);
		}

		$query->where('orders.weight_order', 1);


		if ($request->filled('order_tag')) {
			$query->WhereRaw("JSON_SEARCH(orders.shopify_tags, 'one', ?) IS NOT NULL", [$request->order_tag]);
		}

		if ($request->filled('search_by') && $request->filled('search_value')) {
			$searchValues = array_filter(array_map('trim', explode(',', trim($request->search_value))));

			if (!empty($searchValues)) {
				if ($request->search_by === 'awb_number') {
					$query->whereIn('orders.awb_number', $searchValues);
				} elseif ($request->search_by === 'order_prefix') {
					$query->whereIn('orders.order_prefix', $searchValues);
				}
			}
		}

		if ($request->filled('order_search') && $request->filled('order_search_value')) {
			if ($request->order_search === 'hsn_number') {
				$query->whereHas('orderItems', function ($q) use ($request) {
					$q->where('hsn_number', 'LIKE', "%$request->order_search_value%");
				});
			} elseif ($request->order_search === 'sku_number') {
				$query->whereHas('orderItems', function ($q) use ($request) {
					$q->where('sku_number', 'LIKE', "%$request->order_search_value%");
				});
			}
		}

		if ($request->filled('customer_search') && $request->filled('customer_search_value')) {
			$query->whereHas('customer', function ($q) use ($request) {
				$q->where($request->customer_search, $request->customer_search_value);
			});
		}

		return $query;
	}

	private function applyOrderListingSearch($query, $search)
	{
		if (is_array($search)) {
			$search = $search['value'] ?? '';
		}

		$search = trim((string) $search);
		if ($search === '') {
			return;
		}

		$query->where(function ($q) use ($search) {
			$q->where('orders.id', 'LIKE', "%$search%")
				->orWhere('orders.order_prefix', 'LIKE', "%$search%")
				->orWhere('orders.created_at', 'LIKE', "%$search%")
				->orWhere('orders.awb_number', 'LIKE', "%$search%")
				->orWhere('orders.shopify_order_number', 'LIKE', "%$search%")
				->orWhere('orders.shopify_order_id', 'LIKE', "%$search%")
				->orWhere('orders.courier_name', 'LIKE', "%$search%")
				->orWhere('orders.status_courier', 'LIKE', "%$search%")
				->orWhere('orders.lr_no', 'LIKE', "%$search%");

			$q->orWhereHas('customer', function ($q) use ($search) {
				$q->where('first_name', 'LIKE', "%$search%")
					->orWhere('last_name', 'LIKE', "%$search%")
					->orWhere('mobile', 'LIKE', "%$search%")
					->orWhere('email', 'LIKE', "%$search%");
			});

			$q->orWhereHas('user', function ($q) use ($search) {
				$q->where('name', 'LIKE', "%$search%")
					->orWhere('email', 'LIKE', "%$search%")
					->orWhere('company_name', 'LIKE', "%$search%")
					->orWhere('mobile', 'LIKE', "%$search%");
			});

			$q->orWhereHas('warehouse', function ($q) use ($search) {
				$q->where('warehouse_name', 'LIKE', "%$search%")
					->orWhere('company_name', 'LIKE', "%$search%")
					->orWhere('contact_name', 'LIKE', "%$search%")
					->orWhere('contact_number', 'LIKE', "%$search%")
                    ->orWhere('address', 'LIKE', "%$search%");;
			});

			$q->orWhereHas('orderItems', function ($q) use ($search) {
				$q->where('product_discription', 'LIKE', "%$search%")
					->orWhere('amount', 'LIKE', "%$search%")
					->orWhere('quantity', 'LIKE', "%$search%");
			});
		});
	}

	public function statusCourieHtml($order)
	{
		$statusText = $order->status_courier;
		if (strtolower($statusText) == "lost" && ($order->is_credited ?? false)) {
			// $statusText .= " <span class='badge badge-success' style='font-size: 10px;'>Credited</span>";
			$statusText .= " (Credited)";
		}

		$statusColor = strtolower($order->status_courier) == "cancelled" ? 'cod' : 'prepaid';
		$output = "<p class='{$statusColor}'>{$statusText}</p>";
		if (strtolower($order->status_courier) == "cancelled") {
			$output .= "<p style='padding-left:0; color:red;'>" . $order->reason_cancel ?? 'The client has cancelled this order before it was shipped.' . "</p>";
		}
		if (!empty($order->shipment_failure_reason) && strtolower($order->status_courier) == "new") {
			$output .= "<p style='padding-left:0; color:red; font-size: 11px;'text-wrap: wrap;><b>Error:</b> " . e($order->shipment_failure_reason) . "</p>";
		}
		$output .= "<p style='padding-left:0'>" . date('Y M d | h:i A', strtotime($order->created_at)) . "</p>";
		return $output;

	}

	function orderShipmentDetailHtml($order, $status, $weightOrder)
	{
		// Hide shipping details for orders that are still New OR without AWB
		if (
			strtolower($status) == 'new' ||
			strtolower($order->status_courier) == 'new' ||
			empty($order->awb_number)
		) {
			return '';
		}

		$output = '';
		$output .= "<div class='main-cont1-1 shipment-details-wrap'>
			<div class='checkbox checkbox-purple'>
			Order Id/LR No: <a href='" . url("order/details/{$order->id}") . "?weight_order=" . $weightOrder . "&status=" . ucwords($status) . "'>#{$order->id}</a>
			</div>
			<div class='checkbox checkbox-purple'>
			Courier: {$order->courier_name}
			</div>";
		if ($status != "new") {
			$output .= "<div class='checkbox checkbox-purple'>
				AWB Number: <a href='" . url("order/tracking-history/{$order->id}") . "?weight_order=" . $weightOrder . "&status=" . ucwords($status) . "'>#{$order->awb_number}</a>
				</div>";
		}

		$output .= "</div>";

		return $output;
	}

	function orderPackageDetailHtml($order)
	{
		if (!$order) {
			return '';
		}

		$isCOD   = strtolower($order->order_type) === "cod";
		$amount  = $isCOD ? $order->cod_amount : $order->invoice_amount;
		$label   = $isCOD ? 'COD' : 'Prepaid';
		$paymentInfo = "
			<div class='payment-info-wrap mt-1'>
				<span class='badge' style='background-color: " . ($isCOD ? '#fff3e0' : '#e3f2fd') . "; color: " . ($isCOD ? '#e65100' : '#0d47a1') . "; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;'>{$label}: ₹{$amount}</span>
			</div>
		";

		$length = (float) $order->length;
		$width  = (float) $order->width;
		$height = (float) $order->height;
		$weight = (float) $order->weight;

		// Prepare order items JSON
		$items = $order->orderItems->map(fn($item) => [
			'product_category' => $item->product_category,
			'product_name' => $item->product_name,
			'sku_number' => $item->sku_number ?? 'N/A',
			'hsn_number' => $item->hsn_number ?? 'N/A',
			'amount' => $item->amount,
			'quantity' => $item->quantity,
		]);

		$jsonItems = htmlspecialchars($items->toJson(), ENT_QUOTES, 'UTF-8'); // safely encode JSON

		// Avoid division by zero → standard divisor 5000
		$volumetricWt = ($length * $width * $height) / 5000;

		if ($order->weight_order == 2) {
			$totalBox = 0;
			$totalVolumetric = 0;
			foreach ($order->orderItems as $box) {
				$dimensions = $box->dimensions;

				if (is_string($dimensions)) {
					$dimensions = json_decode($dimensions, true) ?: [];
				} elseif (is_object($dimensions)) {
					$dimensions = (array) $dimensions;
				} elseif (!is_array($dimensions)) {
					$dimensions = [];
				}

				$boxCount = (float) ($dimensions['no_of_box'] ?? 0);
				$boxLength = (float) ($dimensions['length'] ?? 0);
				$boxWidth = (float) ($dimensions['width'] ?? 0);
				$boxHeight = (float) ($dimensions['height'] ?? 0);

				$totalBox += $boxCount;

				if ($boxLength > 0 && $boxWidth > 0 && $boxHeight > 0 && $boxCount > 0) {
					$volume = $boxLength * $boxWidth * $boxHeight;
					$totalVolumetric += ($volume / 5000) * $boxCount;
				}
			}

			if ($totalVolumetric <= 0 && $length > 0 && $width > 0 && $height > 0) {
				$totalVolumetric = $volumetricWt;
			}

			return "
					<div class='main-cont1-2 package-details-wrap'>
						<p class='small-text'>{$totalBox} Boxe(s) - (B2B)</p>
						<p class='small-text'>Dead wt.: " . number_format($weight, 2) . " Kg</p>
						<p class='small-text'>Volumetric wt.: " . number_format($totalVolumetric, 2) . " Kg</p>
						{$paymentInfo}
					</div>
					<span style='padding-left:0; font-size: 11px;'>
							<a href='javascript:;' class='show-details-btn' data-order='{$jsonItems}' style=' color: #1A4BEC ;'  >
								View Products
							</a>
					</span>
				";
		} else {
			return "
					<div class='main-cont1-2 package-details-wrap'>
						<p class='small-text'>Dead: " . number_format($weight, 2) . " Kg</p>
						<p class='small-text'>{$length}x{$width}x{$height} cm</p>
						<p class='small-text'>Vol: " . number_format($volumetricWt, 2) . " Kg</p>
						{$paymentInfo}
					</div>
					<span style='padding-left:0; font-size: 11px;'>
						<a href='javascript:;' class='show-details-btn' data-order='{$jsonItems}' style=' color: #1A4BEC ;'  >
							View Products
						</a>
					</span>
				";
		}
	}

	function orderAction($order, $status, $weightOrder)
	{
		$authUser = auth()->user();
		$role = strtolower(trim((string) ($authUser->role ?? '')));
		$canManageThisOrder = $this->canAccessOrderAction($order, $authUser);
		$canClone = $canManageThisOrder && (config('permission.order.add') || in_array($role, ['user', 'admin', 'master_admin'], true));
		$canEdit = $canManageThisOrder && (config('permission.order.edit') || in_array($role, ['user', 'admin', 'master_admin'], true));
		$canCancel = $canManageThisOrder && (config('permission.order.delete') || in_array($role, ['user', 'admin', 'master_admin'], true));
		$canShipNow = $canManageThisOrder;

		$output = "<div class='main-btn-1'>";
		if ($order->queue == 1 && strtolower($order->status_courier) == "new") {
			$output .= "<a href='javascript:;'>
				<button type='button' class='btn-light-1' style='cursor: not-allowed; opacity: 0.6;'>
				Processing
				</button>
				</a>";

			// Disabled dropdown for queued orders
			$output .= "<div class='mian-btn'>
				<div class='btn-group'>
				<button class='dropbtn' type='button' style='cursor: not-allowed; opacity: 0.6;'>
				<i class='fas fa-ellipsis-v'></i>
				</button>
				</div>
				</div>";
		} else {
			if (($order->status_courier === "New" || $order->status_courier === "new") && $canShipNow) {
				$output .= "<a href='javascript:;'>
					<button type='button' class='customization_popup_trigger btn-light-1' data-weight-order=" . $weightOrder . " onclick='shipNow(this, event)' data-id='{$order->id}'>
					Ship Now
					</button>
					</a>";
			}
			$output .= "<div class='mian-btn'>
				<div class='btn-group'>
				<button class='dropbtn' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
				<i class='fas fa-ellipsis-v'></i>
				</button>
				<div class='dropdown-menu order'>";
			$output .= "<a class='dropdown-item' href='" . url("order/details/{$order->id}") . "?weight_order=" . $weightOrder . "&status=" . ucwords($status) . "'>View Order</a>
				<hr class='m-0' />";

			if (strtolower($order->status_courier) === "lost" && !($order->is_credited ?? false)) {
				$output .= "<a class='dropdown-item' href='javascript:;' onclick='openCreditModal(this)' data-id='{$order->id}' data-invoice-amount='{$order->invoice_amount}' data-order-prefix='{$order->order_prefix}'> Credit Lost Order </a><hr class='m-0' />";
			}
			if ($canClone) {
				$output .= "<a class='dropdown-item' href='" . url("order/clone/{$order->id}") . "?weight_order=" . $weightOrder . "'>Clone Order</a>
					<hr class='m-0' />";
			}

			if ($order->status_courier === "New" || $order->status_courier === "new") {
				if ($canEdit) {
					$output .= "<a class='dropdown-item' href='" . url("order/edit/{$order->id}") . "?weight_order=" . $weightOrder . "'>Edit Order</a>
						<hr class='m-0' />";
				}

				if ($canCancel) {
					/* $output .="<a class='dropdown-item' href='" . url("order/delete/{$order->id}") . "?weight_order=".$weightOrder."' onclick='deleteOrder(this, event)'>Delete Order</a>
						<hr class='m-0' />"; */
					$output .= "<a class='dropdown-item' href='" . url("order/cancel/{$order->id}") . "?weight_order=" . $weightOrder . "' data-order_prefix='{$order->order_prefix}' style='color: red;' onclick='cancelNewOrder(this, event)'>
						Cancel Order
						</a>";
				}
			}

			if (!empty($order->shipping_company_id) && !empty($order->awb_number)) {
				if (!in_array(strtolower($order->status_courier), ["cancelled", "new"])) {
					$output .= "<a class='dropdown-item' href='" . url("order/tracking-history/{$order->id}") . "?weight_order=" . $weightOrder . "'> Tracking Order </a><hr class='m-0' />";
					if (auth()->check() && auth()->user()->isMasterAdmin() && (int) $order->shipping_company_id !== 1) {
						$output .= "<a class='dropdown-item manual-status-update-trigger' href='javascript:;' data-id='{$order->id}' data-raw-status='{$order->status_courier}'> Manual Status Update </a><hr class='m-0' />";
					}
					// if ($order->shipping_company_id == 1) {
					// 	$output .= "<a class='dropdown-item' href='" . url("order/waybill-copy/{$order->id}") . "?weight_order=" . $weightOrder . "' target='_blank'> Waybill Copy</a><hr class='m-0' />";

					// 	$output .= "<a class='dropdown-item' href='" . url("order/shipping-lable/{$order->id}") . "?weight_order=" . $weightOrder . "' target='_blank'> Shipping Label </a><hr class='m-0' />";
					// }
					$output .= "<a class='dropdown-item' href='" . url("order/download-label/{$order->id}") . "?weight_order=" . $weightOrder . "' target='_blank'> Order Label </a><hr class='m-0' />";

					if (in_array(strtolower($order->status_courier), ["manifested", "in transit", "Pickup Pending", "open", "scheduled"])) {
						if ($canCancel) {
							$output .= "<a class='dropdown-item' href='" . url("order/cancel-api/{$order->id}") . "?weight_order=" . $weightOrder . "' data-order_prefix='{$order->order_prefix}' style='color: red;' onclick='cancelOrderApi(this, event)'>
								Cancel Order
								</a>";
						}
					}
				}
			}

			$output .= "</div>
				</div>
				</div>";
		}

		$output .= "</div>";

		return $output;
	}

	public function orderCreate()
	{
		$user = Auth::user();
		// Force B2C (weight_order = 1) because B2B is disabled
		$weightOrder = 1;
		return view('order.create-b2c', compact('user'));
	}

	public function creditLostOrder(Request $request)
	{
		$request->validate([
			'order_id' => 'required|exists:orders,id',
			'amount' => 'required|numeric|min:1',
			'note' => 'nullable|string'
		]);

		$order = Order::findOrFail($request->order_id);

		// Validation: Max amount = min(invoice_amount, 2000)
		$maxAmount = min($order->invoice_amount, 2000);

		if ($request->amount > $maxAmount) {
			return response()->json(['status' => 'error', 'msg' => "Amount cannot exceed ₹{$maxAmount} (Max limit: Invoice Amount or ₹2000)"]);
		}

		// Check if already credited
		$existingCredit = UserWallet::where('order_id', $order->id)
			->where('transaction_type', 'Lost Order Credit')
			->first();

		if ($existingCredit) {
			return response()->json(['status' => 'error', 'msg' => 'This order has already been credited.']);
		}

		DB::transaction(function () use ($order, $request) {
			// Credit User Wallet
			$user = User::findOrFail($order->user_id);
			$user->increment('wallet_amount', $request->amount);

			// Log in UserWallet
			UserWallet::create([
				'user_id' => $user->id,
				'amount' => $request->amount,
				'amount_type' => 'credit',
				'transaction_type' => 'Lost Order Credit',
				'pg_name' => 'System',
				'order_id' => $order->id,
				'txn_number' => 'LOST-CREDIT-' . time(),
				'transaction_status' => 'Paid',
				'status' => 1,
				'payable_response' => json_encode(['note' => $request->note])
			]);

			// Log in Billing
			Billing::create([
				'user_id' => $user->id,
				'billing_type' => 'Lost Order Credit',
				'billing_type_id' => $order->id,
				'transaction_type' => 'credit',
				'amount' => $request->amount,
				'note' => $request->note ?? "Credit for Lost Order #{$order->order_prefix}"
			]);
		});

		return response()->json(['status' => 'success', 'msg' => 'Credit added successfully.']);
	}

	public function orderStore(Request $request)
	{
		DB::beginTransaction();
		try {
			$user_id = Auth::id();

			$exclude = ['_token', 'product_name', 'product_category', 'sku_number', 'hsn_number', 'amount', 'quantity', 'order_image', 'invoice_document', 'first_name', 'last_name', 'email', 'gst_number', 'mobile', 'address', 'country', 'state', 'city', 'zip_code'];
			if ($request->weight_order == 2) {
				$exclude = array_merge($exclude, ['weight', 'length', 'width', 'height']);
			}
			$data = $request->except($exclude);

			// Shared business rule: order_type must be COD or Prepaid (stored canonical lowercase).
			if (!OrderType::isValid($request->order_type)) {
				return response()->json(['status' => 'error', 'msg' => OrderType::INVALID_MESSAGE]);
			}
			$data['order_type'] = OrderType::sanitize($request->order_type);

			// Check if order prefix already exists
			if (Order::where('order_prefix', $request->order_prefix)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The order number already exists.']);
			}

			if (empty($request->reference_number)) {
				return response()->json(['status' => 'error', 'msg' => 'Reference number is required.']);
			}

			if (Order::where('user_id', $user_id)->where('reference_number', $request->reference_number)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'This reference number already used']);
			}

			// // Create Customer
			// $customer = Customer::create([
			// 	'user_id'    => $user_id,
			// 	'first_name' => $request->first_name,
			// 	'last_name'  => $request->last_name,
			// 	'email'      => $request->email,
			// 	'gst_number' => $request->gst_number ?? null,
			// 	'mobile'     => $request->mobile,
			// 	'status'     => 1,
			// ]);

			// // Create Customer Address if available
			// $customerAddress = null;
			// if ($request->filled('address')) {
			// 	$customerAddress = CustomerAddress::create([
			// 		'customer_id' => $customer->id,
			// 		'address'     => $request->address,
			// 		'country'     => $request->country,
			// 		'state'       => $request->state,
			// 		'city'        => $request->city,
			// 		'zip_code'    => $request->zip_code,
			// 		'status'      => 1,
			// 	]);
			// }

			$data['user_id'] = $user_id;
			$data['shipping_mode'] = 'Surface';
			$data['status_courier'] = 'New';
			// $data['customer_id'] = $customer->id;
			// $data['customer_address_id'] = $customerAddress->id ?? null;
			$data['weight'] = $request->total_weight;
			$data['is_fragile_item'] = $request->is_fragile_item ?? 0;
			$data['total_amount'] = $request->invoice_amount;
			$data['status'] = 1;
			$data['created_at'] = now();
			$data['updated_at'] = now();

			$order = Order::create($data);

			$imagePaths = [];
			if ($request->hasFile('order_image')) {
				foreach ($request->file('order_image') as $image) {
					$filename = time() . '_' . $image->getClientOriginalName();
					$path = $image->storeAs('public/orders/' . $order->id, $filename);
					$imagePaths[] = $filename; // Store file path
				}
			}

			$invoiceDocPaths = [];
			if ($request->hasFile('invoice_document')) {
				foreach ($request->file('invoice_document') as $image) {
					$filename = time() . '_' . $image->getClientOriginalName();
					$path = $image->storeAs('public/orders/' . $order->id, $filename);
					$invoiceDocPaths[] = $filename; // Store file path
				}
			}
			$order->update(['order_image' => $imagePaths, 'invoice_document' => $invoiceDocPaths]);

			if ($request->weight_order == 2) {
				$this->b2bStore($order, $request);
			} else {
				$this->b2cStore($order, $request);
			}

			try {
				dispatch(function () use ($order) {
					$whatsappService = app(\App\Services\WhatsappService::class);
					$whatsappServiceResponse = $whatsappService->sendOrderNotification($order->fresh(), 'manifest_created');
				});
			} catch (\Exception $e) {
				Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
			}

			// Insert order status
			OrderStatus::insert([
				'order_id' => $order->id,
				'order_status' => 'New',
				'created_at' => now(),
				'updated_at' => now()
			]);

			Helper::orderActivity($order->id, 'Order created.');

			DB::commit(); // Commit Transaction

			// Dispatch webhook: order.created
			try {
				app(\App\Services\WebhookService::class)->dispatch($user_id, 'order.created', $order->fresh());
			} catch (\Throwable $webhookEx) {
				\Illuminate\Support\Facades\Log::error('Webhook dispatch failed (order.created): ' . $webhookEx->getMessage());
			}

			return response()->json(['status' => 'success', 'msg' => 'The order has been successfully added.']);
		} catch (\Exception $e) {
			DB::rollback(); // Rollback in case of failure
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	private function b2cStore($order, $request)
	{
		$orderItems = [];
		if (!empty($request->product_category)) {
			foreach ($request->product_category as $key => $productCategory) {
				$amount = $request->amount[$key] ?? 0;
				$quantity = $request->quantity[$key] ?? 1;

				$orderItems[] = [
					'order_id' => $order->id,
					'product_category' => $productCategory,
					'product_name' => $request->product_name[$key] ?? null,
					'sku_number' => $request->sku_number[$key] ?? null,
					'hsn_number' => $request->hsn_number[$key] ?? null,
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'created_at' => now(),
					'updated_at' => now()
				];
			}
			OrderItem::insert($orderItems);
		}
	}

	private function b2bStore($order, $request)
	{
		$orderItems = [];
		$height = $width = $length = $weight = 0;

		if (!empty($request->product_category)) {
			foreach ($request->product_category as $key => $productCategory) {
				$amount = $request->amount[$key] ?? 0;
				$quantity = $request->quantity[$key] ?? 1;

				$orderItems[] = [
					'order_id' => $order->id,
					'product_category' => $productCategory,
					'product_name' => $request->product_name[$key] ?? null,
					'sku_number' => $request->sku_number[$key] ?? null,
					'hsn_number' => $request->hsn_number[$key] ?? null,
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'created_at' => now(),
					'updated_at' => now(),
					'dimensions' => json_encode([
						'no_of_box' => $request->no_of_box[$key] ?? 0,
						'weight' => $request->weight[$key] ?? 0,
						'length' => $request->length[$key] ?? 0,
						'width' => $request->width[$key] ?? 0,
						'height' => $request->height[$key] ?? 0,
					]),
				];

				// Accumulate totals
				$height += $request->height[$key] ?? 0;
				$width  += $request->width[$key] ?? 0;
				$length += $request->length[$key] ?? 0;
			}

			OrderItem::insert($orderItems);
		}

		$order->update([
			'length' => $length,
			'width'  => $width,
			'height' => $height
		]);
		return;
	}


	public function orderEdit($id)
	{
		$user = Auth::user();
		$order = Order::with('orderItems')->find($id);
		$this->authorizeOrderAccess($order);
		$weightOrder = request('weight_order', 1);

		return view($weightOrder == 1 ? 'order.edit-b2c' : 'order.edit-b2b', compact('user', 'order', 'weightOrder'));
	}

	public function orderUpdate(Request $request, $id)
	{
		DB::beginTransaction();
		$request->merge(['weight_order' => 1]); // Force B2C
		try {
			$user_id = Auth::id();
			$exclude = ['_token', 'product_name', 'product_category', 'sku_number', 'hsn_number', 'amount', 'quantity', 'order_image', 'invoice_document', 'first_name', 'last_name', 'email', 'gst_number', 'mobile', 'address', 'country', 'state', 'city', 'zip_code'];
			if ($request->weight_order == 2) {
				$exclude = array_merge($exclude, ['weight', 'length', 'width', 'height']);
			}
			$data = $request->except($exclude);

			if (empty($request->reference_number)) {
				return response()->json(['status' => 'error', 'msg' => 'Reference number is required.']);
			}

			if (Order::where('user_id', $user_id)->where('reference_number', $request->reference_number)->where('id', '!=', $id)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'This reference number already used']);
			}

			$order = Order::findOrFail($id);
			$this->authorizeOrderAccess($order);
			// $order->customer()->update([
			// 	'user_id'    => $user_id,
			// 	'first_name' => $request->first_name,
			// 	'last_name'  => $request->last_name,
			// 	'email'      => $request->email,
			// 	'gst_number' => $request->gst_number ?? null,
			// 	'mobile'     => $request->mobile,
			// 	'status'     => 1,
			// 	'updated_at' => now(), // ✅ fixed
			// ]);

			// if (!empty($request->address)) {
			// 	$order->customerAddress()->update([
			// 		'customer_id' => $order->customer_id,
			// 		'address'     => $request->address,
			// 		'country'     => $request->country,
			// 		'state'       => $request->state,
			// 		'city'        => $request->city,
			// 		'zip_code'    => $request->zip_code,
			// 		'status'      => 1,
			// 		'updated_at'  => now(), // ✅ fixed
			// 	]);
			// }

			$data['user_id'] = $user_id;
			$data['shipping_mode'] = 'Surface';
			$data['is_fragile_item'] = $request->is_fragile_item ?? 0;
			$data['weight'] = $request->total_weight;
			$data['total_amount'] = $request->invoice_amount;
			$data['status'] = 1;
			$data['created_at'] = now();
			$data['updated_at'] = now();

			$imagePaths = $order->order_image ?? [];
			if ($request->hasFile('order_image')) {
				foreach ($request->file('order_image') as $image) {
					$filename = time() . '_' . $image->getClientOriginalName();
					$path = $image->storeAs('public/orders/' . $order->id, $filename);
					$imagePaths[] = $filename; // Store file path
				}
			}

			$invoiceDocPaths = $order->invoice_document ?? [];
			if ($request->hasFile('invoice_document')) {
				foreach ($request->file('invoice_document') as $image) {
					$filename = time() . '_' . $image->getClientOriginalName();
					$path = $image->storeAs('public/orders/' . $order->id, $filename);
					$invoiceDocPaths[] = $filename; // Store file path
				}
			}
			$data['order_image'] = $imagePaths;
			$data['invoice_document'] = $invoiceDocPaths;
			$order->update($data);

			if ($request->weight_order == 2) {
				$this->b2bUpdate($order, $request);
			} else {
				$this->b2cUpdate($order, $request);
			}

			Helper::orderActivity($order->id, 'Order updated.');

			DB::commit(); // Commit Transaction

			// Dispatch webhook: order.updated
			try {
				app(\App\Services\WebhookService::class)->dispatch($user_id, 'order.updated', $order->fresh());
			} catch (\Throwable $webhookEx) {
				\Illuminate\Support\Facades\Log::error('Webhook dispatch failed (order.updated): ' . $webhookEx->getMessage());
			}

			return response()->json(['status' => 'success', 'msg' => 'The order have been updated successfully.']);
		} catch (\Exception $e) {
			DB::rollback(); // Rollback in case of failure
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	private function b2cUpdate($order, $request)
	{
		$orderItems = [];
		$itemIds = array_values(array_filter((array) $request->input('item_id', [])));

		if (!empty($request->product_category)) {
			OrderItem::where('order_id', $order->id)->whereNotIn('id', $itemIds)->delete();
			foreach ($request->product_category as $key => $productCategory) {
				$amount = $request->amount[$key] ?? 0;
				$quantity = $request->quantity[$key] ?? 1;

				$orderItemData = [
					'order_id' => $order->id,
					'product_category' => $productCategory,
					'product_name' => $request->product_name[$key],
					'sku_number' => $request->sku_number[$key],
					'hsn_number' => $request->hsn_number[$key],
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'updated_at' => now()
				];

				// Check if order_item_id exists in the request
				if (!empty($itemIds[$key])) {
					$orderItem = OrderItem::find($itemIds[$key]);
					if ($orderItem) {
						$orderItem->update($orderItemData); // Update existing item
					}
				} else {
					$orderItemData['created_at'] = now();
					$orderItems[] = $orderItemData; // Add new item for bulk insert
				}
			}

			// Bulk insert for new items if any
			if (!empty($orderItems)) {
				OrderItem::insert($orderItems);
			}
		}
		return;
	}

	private function b2bUpdate($order, $request)
	{
		$totalAmount = 0;
		$orderItems = [];
		$itemIds = array_values(array_filter((array) $request->input('item_id', [])));
		$height = $width = $length = $weight = 0;

		if (!empty($request->product_category)) {
			OrderItem::where('order_id', $order->id)->whereNotIn('id', $itemIds)->delete();
			foreach ($request->product_category as $key => $productCategory) {
				$amount = $request->amount[$key] ?? 0;
				$quantity = $request->quantity[$key] ?? 1;

				$orderItemData = [
					'order_id' => $order->id,
					'product_category' => $productCategory,
					'product_name' => $request->product_name[$key],
					'sku_number' => $request->sku_number[$key],
					'hsn_number' => $request->hsn_number[$key],
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'updated_at' => now(),
					'dimensions' => json_encode([
						'no_of_box' => $request->no_of_box[$key] ?? 0,
						'weight' => $request->weight[$key] ?? 0,
						'length' => $request->length[$key] ?? 0,
						'width' => $request->width[$key] ?? 0,
						'height' => $request->height[$key] ?? 0,
					]),
				];

				// Accumulate totals
				$height += $request->height[$key] ?? 0;
				$width  += $request->width[$key] ?? 0;
				$length += $request->length[$key] ?? 0;

				// Check if order_item_id exists in the request
				if (!empty($itemIds[$key])) {
					$orderItem = OrderItem::find($itemIds[$key]);
					if ($orderItem) {
						$orderItemData['dimensions'] = json_decode($orderItemData['dimensions'], true);
						$orderItem->update($orderItemData); // Update existing item
					}
				} else {
					$orderItemData['created_at'] = now();
					$orderItems[] = $orderItemData; // Add new item for bulk insert
				}
			}

			$order->update([
				'length' => $length,
				'width'  => $width,
				'height' => $height
			]);

			// Bulk insert for new items if any
			if (!empty($orderItems)) {
				OrderItem::insert($orderItems);
			}
		}
		return;
	}

	public function orderClone($id)
	{
		$order = Order::with('orderItems')->find($id);
		$this->authorizeOrderAccess($order);
		$weightOrder = (int) request('weight_order', $order->weight_order ?? 1);

		// Force B2C (weight_order = 1) as B2B is disabled
		if ($weightOrder === 2) {
			$weightOrder = 1;
		}

		$user = Auth::user();

		if ($weightOrder === 1) {
			$formMode = 'clone';
			return view('order.clone-b2c', compact('user', 'order', 'weightOrder', 'formMode'));
		}

		DB::beginTransaction();
		try {
			$clonedOrder = $order->replicate();
			$clonedOrder->order_prefix = Order::generateOrderNumber($order->user_id);
			$clonedOrder->weight_order = $weightOrder;
			$clonedOrder->status_courier = 'New';
			$clonedOrder->reason_cancel = null;
			$clonedOrder->shipment_failure_reason = null;
			$clonedOrder->order_cancel_date = null;
			$clonedOrder->delivery_date = null;
			$clonedOrder->shipping_company_id = null;
			$clonedOrder->shipment_id = null;
			$clonedOrder->awb_number = null;
			$clonedOrder->courier_name = null;
			$clonedOrder->courier_id = null;
			$clonedOrder->courier_logo = null;
			$clonedOrder->lr_no = null;
			$clonedOrder->api_response = null;
			$clonedOrder->api_response1 = null;
			$clonedOrder->round_off = 0;
			$clonedOrder->percentage_amount = 0;
			$clonedOrder->shipping_charge = 0;
			$clonedOrder->cod_charges = 0;
			$clonedOrder->rto_charge = 0;
			$clonedOrder->total_shipping_cost = 0;
			$clonedOrder->weight_status = null;
			$clonedOrder->weight_update_date = null;
			$clonedOrder->applicable_weight = null;
			$clonedOrder->is_raise_weight = 0;
			$clonedOrder->rto_charge_applied = 0;
			$clonedOrder->is_remmitance = 0;
			$clonedOrder->is_payout = 0;
			$clonedOrder->remittance_reference_id = null;
			$clonedOrder->remittance_amount = null;
			$clonedOrder->remittance_date = null;
			$clonedOrder->invoice_id = null;
			$clonedOrder->is_invoice_count = 0;
			$clonedOrder->queue = 0;
			$clonedOrder->pickup_date = null;
			$clonedOrder->pickup_time = null;
			$clonedOrder->send_whatsapp_notify = null;
			$clonedOrder->created_at = now();
			$clonedOrder->updated_at = now();
			$clonedOrder->save();

			foreach ($order->orderItems as $item) {
				$clonedItem = $item->replicate();
				$clonedItem->order_id = $clonedOrder->id;
				$clonedItem->created_at = now();
				$clonedItem->updated_at = now();
				$clonedItem->save();
			}

			OrderStatus::create([
				'order_id' => $clonedOrder->id,
				'order_status' => 'New',
			]);

			Helper::orderActivity($clonedOrder->id, 'Order cloned from #' . ($order->order_prefix ?? $order->id) . '.');

			DB::commit();

			return redirect('order/details/' . $clonedOrder->id . '?weight_order=' . $weightOrder . '&status=New')
				->with('success', 'Order cloned successfully.');
		} catch (\Exception $e) {
			DB::rollBack();
			return back()->with('error', $e->getMessage());
		}
	}

	public function orderDelete($id)
	{
		DB::beginTransaction();
		try {
			$order = Order::find($id);
			$order->orderItems()->delete();

			$documents = [
				'order_image',
				'invoice_document'
			];

			foreach ($documents as $document) {
				$imagePaths = $order->$document ?? [];

				if (!empty($imagePaths) && is_array($imagePaths)) {
					foreach ($imagePaths as $image) {
						$filePath = "public/orders/{$order->id}/{$image}";

						if (Storage::exists($filePath)) {
							Storage::delete($filePath);
						}
					}
				}
			}

			// Remove the entire order directory if it's empty
			$orderDirectory = 'public/orders/' . $order->id;
			if (Storage::exists($orderDirectory) && count(Storage::files($orderDirectory)) === 0) {
				Storage::deleteDirectory($orderDirectory);
			}

			$userId = $order->user_id;
			$orderCopy = $order->replicate(); // keep a copy before deletion for webhook
			$order->delete();

			DB::commit();

			// Dispatch webhook: order.deleted
			try {
				app(\App\Services\WebhookService::class)->dispatch($userId, 'order.deleted', $orderCopy);
			} catch (\Throwable $webhookEx) {
				\Illuminate\Support\Facades\Log::error('Webhook dispatch failed (order.deleted): ' . $webhookEx->getMessage());
			}

			return redirect()->back()->with('success', 'The order has been successfully deleted.');
		} catch (\Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong.');
		}
	}

	public function orderShippingLableDownload($orderId)
	{
		$order = Order::with('shippingCompany')->find($orderId);
		if (!$order->shippingCompany && !empty($order->awb_number)) {
			return back()->with('error', 'Something went wrong');
		}

		if ($order->shippingCompany->id == 1) {
			$response = $this->shadowfaxService->generateLabel($order->awb_number, $order->shippingCompany);

			if ($response['success'] ?? false) {
				$labelUrl = $response['response']['label_url'] ?? ($response['response']['data']['label_url'] ?? null);
				if ($labelUrl) {
					return redirect()->away($labelUrl);
				}
			}
			return back()->with('error', 'Label generation for Shadowfax failed or not found in response.');
		} elseif (in_array($order->shippingCompany->id, [3, 4, 5])) {
			$response = $this->delhiveryService->shippingLableByLrNo($order->lr_no, $order->shippingCompany);

			if (!($response['success'] ?? false)) {
				$errorMsg = $response['response']['errors'][0]['message'] ?? ($response['response']['error']['message'] ?? 'An error occurred.');
				return back()->with('error', $errorMsg);
			}

			if ((isset($response['response']['success']) && !$response['response']['success'])) {
				return back()->with('error', $response['response']['error']['message'] ?? 'An error occurred.');
			}
			if (!$response['response']['data']) {
				return back()->with('error', 'Something went wrong');
			}
			$data = $response['response']['data'] ?? [];

			// Fetch each label image and convert to Base64
			$labels = [];
			foreach ($data as $url) {
				$response = $this->delhiveryService->getLabelImage($url);
				if (isset($response['response']['success']) && $response['response']['success']) {
					$labels[] = $response['response']['data'] ?? '';
				}
			}
			return view('order.shipping-label', compact('labels', 'order'));
		}
		return back()->with('error', 'Something went wrong');
	}

	public function orderWayBillCopy($orderId)
	{
		$order = Order::with('shippingCompany')->find($orderId);
		if (!$order->shippingCompany && !empty($order->awb_number)) {
			return back()->with('error', 'Something went wrong');
		}
		if ($order->shippingCompany->id == 1) {
			// Routx currently does not have a waybill endpoint in documentation.
			return back()->with('error', 'Waybill copy for Routx is not available via API.');
		} elseif (in_array($order->shippingCompany->id, [3, 4, 5])) {
			$response = $this->delhiveryService->waybillCopyByLrNo($order->lr_no, $order->shippingCompany);

			if (!($response['success'] ?? false)) {
				$errorMsg = $response['response']['errors'][0]['message'] ?? ($response['response']['error']['message'] ?? 'An error occurred.');
				return back()->with('error', $errorMsg);
			}

			if ((isset($response['response']['success']) && !$response['response']['success'])) {
				return back()->with('error', $response['response']['error']['message'] ?? 'An error occurred.');
			}
			if (!$response['response']) {
				return back()->with('error', 'Something went wrong');
			}
			return Response::make($response['response'], 200, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'inline; filename="LR_Copy_' . $order->lr_no . '.pdf"'
			]);
		}
		return back()->with('error', 'Something went wrong');
	}

	public function downloadShippingLabel($id)
	{
		$order = Order::find($id);
		$labelUrl = $order->label;

		// Download the file from the URL
		$response = Http::get($labelUrl);

		// Define the path where you want to save the file
		$filePath = 'public/labels/' . $id . '.pdf';

		// Save the file to the server
		Storage::put($filePath, $response->body());

		// Generate a URL to access the saved file
		$fileUrl = url(Storage::url($filePath));

		// Return the full URL for downloading the file
		return $fileUrl;
	}

	public function orderLableDownload($orderId)
	{
		error_reporting(0);

		// Load order with related data
		$order = Order::with([
			'shippingCompany:id,logo,api_key,url',
			'customer:id,first_name,last_name,mobile',
			'customerAddress',
			'warehouse',
			'orderItems'
		])->findOrFail($orderId);

		// Amazon ATS Label check
		$isAmazonATS = false;
		if (!empty($order->courier_name) && stripos($order->courier_name, 'Amazon ATS') !== false) {
			$isAmazonATS = true;
		}
		if ($order->shippingCompany && stripos($order->shippingCompany->name ?? '', 'Amazon ATS') !== false) {
			$isAmazonATS = true;
		}

		if ($isAmazonATS) {
				if (empty($order->awb_number)) {
				return back()->with('error', 'AWB Number is missing for Amazon ATS order.');
			}

			try {
				// Fetch ShipMozo credentials (assuming ID 2 is ShipMozo)
				$shipMozoCompany = ShippingCompany::find(2);
				$headers = [];
				if ($shipMozoCompany) {
					$headers = [
						'public-key' => $shipMozoCompany->api_key,
						'private-key' => $shipMozoCompany->secret_key,
						'Content-Type' => 'application/json',
						'Accept' => 'application/json'
					];
				}

				$response = Http::withHeaders($headers)
					->get("https://shipping-api.com/app/api/v1/get-order-label/{$order->awb_number}");
				$result = $response->json();

				if (isset($result['result']) && $result['result'] == '1' && !empty($result['data'][0]['label'])) {
					$labelImage = $result['data'][0]['label'];

					// Use full page image for the label
					$html = '<!DOCTYPE html><html><body style="margin:0;padding:0;"><img src="' . $labelImage . '" style="width:100%;height:auto;"></body></html>';

					$preference = LabelPreference::where('user_id', $order->user_id)->first();
					$paperSize = [0, 0, 288, 432]; // Default Thermal 4x6 (inches * 72)

					if ($preference && $preference->label_size === 'desktop') {
						$paperSize = 'letter';
					}

					return PDF::loadHtml($html)
						->setPaper($paperSize, 'portrait')
						->setOption('isHtml5ParserEnabled', true)
						->setOption('isRemoteEnabled', true)
						->download('order_label_' . $orderId . '.pdf');
				} else {
					return back()->with('error', 'Amazon ATS Label not found. ' . ($result['message'] ?? ''));
				}
			} catch (\Exception $e) {
				return back()->with('error', 'Error fetching Amazon ATS Label: ' . $e->getMessage());
			}
		}

		// UrbaneBolt Label Download
		// if($order->shipping_company_id == 2) {
		// 	$response = $this->urbaneBoltService->shippingLableByLrNo($order->awb_number, $order->shippingCompany);

		// 	if ($response['success'] ?? false) {
		// 		$labelUrl = $response['response']['link'] ?? ($response['response']['url'] ?? ($response['response']['label_url'] ?? null));

		// 		if ($labelUrl) {
		// 			return redirect($labelUrl);
		// 		}
		// 	}
		// }

		// Related data
		$shipping = $order->shippingCompany ?? null;
		$customer = $order->customer ?? null;
		$customerAddr = $order->customerAddress ?? null;
		$products = $order->orderItems ?? null;
		$hideLabel = $order->warehouse ? $order->warehouse->label_options : [];
		$preference = LabelPreference::where('user_id', $order->user_id)->first();
		// Generate barcodes
		$barcodePng = !empty($order->awb_number) ? DNS2D::getBarcodePNG($order->awb_number, 'QRCODE', 6, 6) : '';
		//$orderIdBarcodePng = DNS1D::getBarcodePNG($order->shipment_id ?? $order->order_prefix, 'C128', 2.5, 60);
		$orderIdBarcodePng  = !empty($order->awb_number) ? DNS1D::getBarcodePNG($order->awb_number, 'C128') : '';

		$view = 'thermal-label';
		if ($preference && $preference->label_size === 'thermal') {
			$view = 'thermal-label';
		} elseif ($preference && $preference->label_size === 'desktop') {
			$view = 'desktop-label';
		}

		// Render Blade HTML
		$htmlView = view('order.' . $view, compact(
			'shipping',
			'order',
			'customer',
			'customerAddr',
			'products',
			'barcodePng',
			'hideLabel',
			'preference',
			'orderIdBarcodePng'
		))->render();

		// ✅ Create PDF with dynamic paper size based on preference
		$paperSize = 'A4';
		$orientation = 'portrait';

		if ($preference && $preference->label_size === 'thermal') {
			// Thermal printer: 4 inches x 6 inches
			$paperSize = [0, 0, 288, 432]; // 4in x 6in in points (72 points per inch)
			$orientation = 'portrait';
		} elseif ($preference && $preference->label_size === 'desktop') {
			// Desktop printer: 8.5 inches x 11 inches (Letter size)
			$paperSize = 'letter';
			$orientation = 'portrait';
		}

		$pdf = PDF::loadHtml($htmlView)
			->setPaper($paperSize, $orientation)
			->setOption('isHtml5ParserEnabled', true)
			->setOption('isRemoteEnabled', true);

		// ✅ Return label PDF
		return $pdf->download('order_label_' . $orderId . '.pdf');
	}

	public function alllabeldownload(Request $request)
	{
		error_reporting(0);
		try {
			$orderIds = $request->input('order_ids');
			if (empty($orderIds) || !is_array($orderIds)) {
				return back()->with('error', 'No orders selected');
			}

			// Eager load related models in one go
			$orders = Order::with([
				'shippingCompany',
				'customer',
				'customerAddress',
				'orderItems',
				'warehouse',
				'user'
			])
				->whereIn('id', $orderIds)->get();

			if ($orders->isEmpty()) {
				return back()->with('error', 'No valid orders found');
			}

			$htmlSections = [];
			// Pre-fetch ShipMozo credentials for Amazon ATS orders
			$shipMozoCompany = ShippingCompany::find(2);

			foreach ($orders as $order) {
				// Amazon ATS Label Check
				$isAmazonATS = false;
				if (!empty($order->courier_name) && stripos($order->courier_name, 'Amazon ATS') !== false) {
					$isAmazonATS = true;
				}
				if ($order->shippingCompany && stripos($order->shippingCompany->name ?? '', 'Amazon ATS') !== false) {
					$isAmazonATS = true;
				}

				if ($isAmazonATS) {
					try {
						$headers = [];
						if ($shipMozoCompany) {
							$headers = [
								'public-key' => $shipMozoCompany->api_key,
								'private-key' => $shipMozoCompany->secret_key,
								'Content-Type' => 'application/json',
								'Accept' => 'application/json'
							];
						}

						if (!empty($order->awb_number)) {
							$response = Http::withHeaders($headers)
								->get("https://shipping-api.com/app/api/v1/get-order-label/{$order->awb_number}");
							$result = $response->json();

							if (isset($result['result']) && $result['result'] == '1' && !empty($result['data'][0]['label'])) {
								$labelImage = $result['data'][0]['label'];
								// Use full page image for the label
								$html = '<!DOCTYPE html><html><body style="margin:0;padding:0;"><img src="' . $labelImage . '" style="width:100%;height:auto;"></body></html>';
								$htmlSections[] = trim(preg_replace('/\s+/', ' ', $html));
								continue;
							}
						}
					} catch (\Exception $e) {
						Log::error('Bulk Amazon ATS Label Error Order #' . $order->id . ': ' . $e->getMessage());
					}
				}

				$shipping       = $order->shippingCompany;
				$customer       = $order->customer;
				$customerAddr   = $order->customerAddress;
				$products       = $order->orderItems;
				$preference 	= LabelPreference::where('user_id', $order->user_id)->first();

				$hideLabel =  $order->warehouse ? $order->warehouse->label_options : [];

				$barcodePng = !empty($order->awb_number) ? DNS2D::getBarcodePNG($order->awb_number, 'QRCODE', 6, 6) : '';
				//$orderIdBarcodePng = DNS1D::getBarcodePNG($order->shipment_id ?? $order->order_prefix, 'C128', 2.5, 60);
				$orderIdBarcodePng = !empty($order->awb_number) ? DNS1D::getBarcodePNG($order->awb_number, 'C128', 2.5, 50) : '';

				$view = 'thermal-label';
				if ($preference && $preference->label_size === 'thermal') {
					$view = 'thermal-label';
				} elseif ($preference && $preference->label_size === 'desktop') {
					$view = 'desktop-label';
				}

				// Generate HTML for label
				$html = view('order.' . $view, compact(
					'shipping',
					'order',
					'customer',
					'products',
					'customerAddr',
					'barcodePng',
					'hideLabel',
					'preference',
					'orderIdBarcodePng'
				))->render();

				// Optional: remove excessive whitespace (disable if layout breaks)
				$htmlSections[] = trim(preg_replace('/\s+/', ' ', $html));
			}

			if (empty($htmlSections)) {
				return back()->with('error', 'All labels are empty');
			}

			$mergedHtml = implode('', $htmlSections);

			// Get first order's preference for paper size
			$firstOrder = $orders->first();
			$preference = LabelPreference::where('user_id', $firstOrder->user_id)->first();

			$paperSize = 'A4';
			$orientation = 'portrait';

			if ($preference && $preference->label_size === 'thermal') {
				// Thermal printer: 4 inches x 6 inches
				$paperSize = [0, 0, 288, 432]; // 4in x 6in in points
				$orientation = 'portrait';
			} elseif ($preference && $preference->label_size === 'desktop') {
				// Desktop printer: 8.5 inches x 11 inches
				$paperSize = 'letter';
				$orientation = 'portrait';
			}

			$pdf = PDF::loadHtml($mergedHtml)
				->setPaper($paperSize, $orientation)
				->setOption('isHtml5ParserEnabled', true)
				->setOption('isRemoteEnabled', true);

			return $pdf->download('labels.pdf');
		} catch (\Exception $e) {
			return back()->with('error', 'PDF generation failed: ' . $e->getMessage());
		}
	}

	public function orderCancel($id)
	{
		DB::beginTransaction(); // Begin Transaction

		try {
			// Update Order Status
			$order = Order::findOrFail($id);
			$this->authorizeOrderAccess($order);

			if ($order->status_courier === 'cancelled') {
				return back()->with('error', 'The order is already cancelled.');
			}

			// Cancel on channel logic
			if (request('cancel_on_channel') == 1) {
				$this->cancelShopifyOrder($order);
			}

			$order->update([
				'status_courier' => 'cancelled',
				'reason_cancel' => 'The client has cancelled this order before it was shipped.',
				'order_cancel_date' => now(),
			]);


			// Insert Order Status Record
			OrderStatus::create([
				'order_id' => $id,
				'order_status' => 'cancelled',
				'created_at' => now(),
				'updated_at' => now(),
			]);

			// Log Order Activity
			Helper::orderActivity($id, 'Order cancelled.');

			DB::commit(); // Commit Transaction

			// Dispatch webhook: order.deleted (order cancelled = treated as deleted/closed)
			try {
				app(\App\Services\WebhookService::class)->dispatch($order->user_id, 'order.deleted', $order->fresh());
			} catch (\Throwable $webhookEx) {
				\Illuminate\Support\Facades\Log::error('Webhook dispatch failed (order.deleted/cancel): ' . $webhookEx->getMessage());
			}

			return back()->with('success', 'The order has been successfully cancelled.');
		} catch (\Exception $e) {
			DB::rollback(); // Rollback Transaction on Failure
			if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
				return back()->with('error', 'You are not allowed to cancel this order.');
			}
			return back()->with('error', 'Failed to cancel the order: ' . $e->getMessage());
		}
	}

	public function orderCancelApi($orderId)
	{
		DB::beginTransaction();

		try {
			$order = Order::with(['user'])->findOrFail($orderId);
			$this->authorizeOrderAccess($order);

			if ($order->status_courier === 'cancelled') {
				return back()->with('error', 'The order is already cancelled.');
			}
			$user = $order->user;

			$shippingCompany = ShippingCompany::where('id', $order->shipping_company_id)
				->where('status', 1)
				->first();

			if (!$shippingCompany) {
				return back()->with('error', 'Shipment not found.');
			}

			$orderCancelledData = [];
			if ($shippingCompany->id == 1) {
				$response = $this->shadowfaxService->cancel(
					$order->awb_number,
					$shippingCompany,
					'Request cancelled by customer',
					$order->order_prefix
				);

				if (!($response['success'] ?? false)) {
					return back()->with('error', $response['message'] ?? 'An error occurred during cancellation.');
				}

				$isQueuedCancellation = (bool) ($response['queued'] ?? false);
				$orderCancelledData = [
					'status_courier' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
					'order_cancel_date' => now(),
					'reason_cancel' => $response['message'] ?? "Shipment cancelled in Shadowfax for AWB: {$order->awb_number}.",
				];

				if (!$isQueuedCancellation && request('cancel_on_channel') == 1) {
					$this->cancelShopifyOrder($order);
				}
			} elseif (in_array($shippingCompany->id, [3, 4, 5])) {
				$response = $this->delhiveryB2CService->cancelShipmentByAwbNumber($order->awb_number, $shippingCompany);

				if (!($response['success'] ?? false)) {
					$responseData = $response['response'] ?? [];
					$errorMsg = is_array($responseData) ? ($responseData['errors'][0]['message'] ?? 'An error occurred.') : 'An error occurred.';
					return back()->with('error', $errorMsg);
				}

				// Cancel on channel logic
				if (request('cancel_on_channel') == 1) {
					$this->cancelShopifyOrder($order);
				}

				$orderCancelledData = [
					'status_courier' => 'cancelled',
					'order_cancel_date' => now(),
					'reason_cancel' => "For AWB number {$order->awb_number}, Shipment has been cancelled.",
				];
			}

			if (!$orderCancelledData) {
				throw new \Exception('Something went wrong while processing the cancellation.');
			}

			$order->update($orderCancelledData);

			$isQueuedCancellation = strtolower((string) ($orderCancelledData['status_courier'] ?? '')) === 'cancel requested';

			if (!$isQueuedCancellation && $user->role == "user") {
				$walletRefund = $order->shipping_charge ?? 0;
				$user->increment('wallet_amount', $walletRefund);

				$isCod = strtolower($order->order_type ?? '') === 'cod';
				$codCharges = (float) ($order->cod_charges ?? 0);
				$codBase = $codCharges;
				$codGst = round($codBase * 0.18, 2);
				$codTotal = $isCod ? round($codBase + $codGst, 2) : 0;
				$freightTotal = max(0, round($walletRefund - $codTotal, 2));
				$freightBase = round($freightTotal / 1.18, 2);
				$freightGst = round($freightTotal - $freightBase, 2);

				if ($freightTotal > 0) {
					Billing::create([
						'user_id' => $order->user_id,
						'billing_type' => "Order",
						'billing_type_id' => $order->id,
						'transaction_type' => 'credit',
						'amount' => $freightTotal,
						'note' => 'Order canceled with AWB number: ' . $order->awb_number . ' | Base Freight: ' . number_format($freightBase, 2, '.', '') . ' | GST (18%): ' . number_format($freightGst, 2, '.', '') . ' | Total: ' . number_format($freightTotal, 2, '.', ''),
						'created_at' => now(),
						'updated_at' => now(),
					]);
				}

				if ($isCod && $codTotal > 0) {
					Billing::create([
						'user_id' => $order->user_id,
						'billing_type' => "Order",
						'billing_type_id' => $order->id,
						'transaction_type' => 'credit',
						'amount' => $codTotal,
						'note' => 'COD refund for AWB: ' . $order->awb_number . ' | COD Base: ' . number_format($codBase, 2, '.', '') . ' | GST (18%): ' . number_format($codGst, 2, '.', '') . ' | Total: ' . number_format($codTotal, 2, '.', ''),
						'created_at' => now(),
						'updated_at' => now(),
					]);
				}
			}

			OrderStatus::create([
				'order_id' => $order->id,
				'order_status' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
				'created_at' => now(),
				'updated_at' => now(),
			]);

			Helper::orderActivity(
				$order->id,
				$isQueuedCancellation
					? 'Shadowfax cancellation queued for AWB number: ' . $order->awb_number
					: 'Order canceled with AWB number: ' . $order->awb_number
			);

			DB::commit();

			if (!$isQueuedCancellation) {
				try {
					app(\App\Services\WebhookService::class)->dispatch($order->user_id, 'order.deleted', $order->fresh());
				} catch (\Throwable $webhookEx) {
					\Illuminate\Support\Facades\Log::error('Webhook dispatch failed (order.deleted/cancel-api): ' . $webhookEx->getMessage());
				}
			}

			return back()->with(
				'success',
				$isQueuedCancellation
					? 'Shadowfax accepted the cancellation request and queued it for the next facility scan.'
					: 'The order has been successfully cancelled.'
			);
		} catch (\Exception $e) {
			DB::rollBack();
			return back()->with('error', $e->getMessage());
		}
	}

	public function orderTrackingHistory($orderId)
	{
		$order = Order::with('shippingCompany')->find($orderId);
		$shippingCompany = $order->shippingCompany ?? [];

		if (!$shippingCompany) {
			return back()->with('error', 'Something went wrong.');
		}

		$trackingHistories = [];
		$expectedDeliveryDate = "N/A";
		$epodData = null;
		if (!empty($order->awb_number)) {
			if ($shippingCompany->id == 1) {
				$trackingResponse = $this->shadowfaxService->track($order->awb_number, $shippingCompany);

				if (!($trackingResponse['success'] ?? false)) {
					$errorMsg = $trackingResponse['message'] ?? 'An error occurred during tracking.';
					return back()->with('error', $errorMsg);
				}

				$trackingHistories = $trackingResponse['response']['tracking_details'] ?? [];
				$expectedDeliveryDate = $trackingResponse['expected_delivery_date'] ?? 'N/A';
			} else {
				$trackingHistories = OrderStatus::where('order_id', $order->id)
					->orderByDesc('id')
					->get();
			}
		}

		if (!$trackingHistories) {
			return back()->with('error', 'The order tracking data not found.');
		}


		return view('order.tracking-history', compact('order', 'trackingHistories', 'shippingCompany', 'expectedDeliveryDate', 'epodData'));
	}

	public function orderManualStatusUpdate(Request $request, $id)
	{
		$authUser = auth()->user();
		if (!$authUser || !$authUser->isMasterAdmin()) {
			abort(403, 'Permission denied');
		}

		$order = Order::with('shippingCompany')->findOrFail($id);
		if (!$order->shippingCompany || (int) $order->shipping_company_id === 1) {
			return back()->with('error', 'Manual status update is allowed only for local shipping companies.');
		}

		$validated = $request->validate([
			'status_courier' => 'required|string|max:100',
			'remarks' => 'nullable|string|max:255',
		]);

		$newStatus = trim($validated['status_courier']);
		$oldStatus = (string) ($order->status_courier ?? 'new');
		$order->status_courier = $newStatus;
		if (strtolower($newStatus) === 'delivered') {
			$order->delivery_date = now();
		}
		$order->save();

		OrderStatus::create([
			'order_id' => $order->id,
			'order_status' => $newStatus . (!empty($validated['remarks']) ? ' - ' . $validated['remarks'] : ''),
			'created_at' => now(),
			'updated_at' => now(),
		]);

		Helper::orderActivity($order->id, "Local order status updated manually: {$oldStatus} -> {$newStatus}");

		return back()->with('success', 'Order status updated successfully.');
	}

	public function manualStatusUpdate(Request $request)
	{
		$request->validate([
			'order_id' => 'required|exists:orders,id',
			'status' => 'required|string|max:100',
		]);

		$user = Auth::user();
		if (!$user || !$user->isMasterAdmin()) {
			return response()->json(['status' => 'error', 'msg' => 'Unauthorized action.']);
		}

		$order = Order::findOrFail($request->order_id);
		if ((int) $order->shipping_company_id === 1) {
			return response()->json(['status' => 'error', 'msg' => 'Status for this shipping company cannot be updated manually.']);
		}

		$oldStatus = (string) $order->status_courier;
		$newStatus = trim((string) $request->status);

		$order->status_courier = $newStatus;
		if (strtolower($newStatus) === 'delivered' && empty($order->delivery_date)) {
			$order->delivery_date = now();
		}
		$order->save();

		OrderStatus::create([
			'order_id' => $order->id,
			'order_status' => $newStatus,
			'created_at' => now(),
			'updated_at' => now(),
		]);

		Helper::orderActivity($order->id, "Local order status updated manually: {$oldStatus} -> {$newStatus}");

		return response()->json(['status' => 'success', 'msg' => 'Order status updated successfully.']);
	}

	public function orderDetails($id)
	{
		$order = Order::with('customer', 'shippingCompany', 'customerAddress', 'warehouse', 'orderItems')->find($id);
		$this->authorizeOrderAccess($order);
		$orderActivities = OrderActivity::where('order_id', $id)->latest()->get();

		$epodData = null;
		$trackingHistories = [];
		if (!empty($order->awb_number)) {
			$shippingCompany = $order->shippingCompany ?? null;

			if ($shippingCompany && $shippingCompany->id == 1) {
				$trackingResponse = $this->shadowfaxService->track($order->awb_number, $shippingCompany);

				if ($trackingResponse['success'] ?? false) {
					$trackingHistories = $trackingResponse['legacy_history'] ?? [];
				}
			} elseif ($shippingCompany) {
				$trackingHistories = OrderStatus::where('order_id', $order->id)
					->orderByDesc('id')
					->get();
			}
		}

		return view('order.details', compact('order', 'orderActivities', 'trackingHistories', 'epodData'));
	}

	private function authorizeOrderAccess(?Order $order): void
	{
		if (!$order) {
			abort(404);
		}

		$authUser = Auth::user();
		if (!$authUser) {
			abort(403, 'Permission denied');
		}

		if ($authUser->isMasterAdmin()) {
			return;
		}

		if (in_array(strtolower((string) $authUser->role), ['admin', 'reseller', 'staff'], true)) {
			$canAccess = (int) $order->user_id === (int) $authUser->id
				|| User::where('id', $order->user_id)->where('staff_id', $authUser->id)->exists();

			if ($canAccess) {
				return;
			}
		}

		if ((int) $order->user_id !== (int) $authUser->id) {
			abort(403, 'Permission denied');
		}
	}

	private function canAccessOrderAction(Order $order, $authUser): bool
	{
		if (!$authUser) {
			return false;
		}

		if ($authUser->isMasterAdmin()) {
			return true;
		}

		$role = strtolower(trim((string) ($authUser->role ?? '')));
		if (in_array($role, ['admin',  'reseller', 'staff'], true)) {
			return (int) $order->user_id === (int) $authUser->id
				|| User::where('id', $order->user_id)->where('staff_id', $authUser->id)->exists();
		}

		return (int) $order->user_id === (int) $authUser->id;
	}

	public function orderShipCharge(Request $request, $orderId)
	{
		$order = Order::with(['warehouse', 'user', 'customerAddress', 'orderItems'])->find($orderId);
		if (!$order) {
			return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
		}

		$user = $order->user ?? null;
		if (!$user) {
			return response()->json(['status' => 'error', 'message' => 'User order not found'], 404);
		}

		if (
			empty($order->weight) || $order->weight <= 0 ||
			empty($order->length) || $order->length <= 0 ||
			empty($order->width) || $order->width <= 0 ||
			empty($order->height) || $order->height <= 0 ||
			empty($order->warehouse_id)
		) {
			return response()->json([
				'status' => 'lbh',
				'order_id' => $order->id,
				'length' => $order->length ?? 0,
				'width' => $order->width ?? 0,
				'height' => $order->height ?? 0,
				'weight' => $order->weight ?? 0,
				'warehouse_id' => $order->warehouse_id,
				'msg' => 'Package dimensions (length, width, or height) and warehouse are missing or invalid. Please update them to proceed with delivery.'
			]);
		}

		$rateCardId = $user->rate_card_id;
		// $allowedShippingCompanyIds = RateCardDetail::where('rate_card_id', $rateCardId)
		// 	->where('status', 1)
		// 	->pluck('shipping_company_id')
		// 	->unique()
		// 	->toArray();

		// Use the user who owns the order for rate calculation
		$shippingCompanies = ShippingCompany::whereStatus(1)->get();
		$couriers = [];
		$weight = $order->weight;

		foreach ($shippingCompanies as $shippingCompany) {
			$requestData = Request::create('/order/shipment/charge', 'POST', [
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
				'weight' => (string)$weight,
				'dimension_type' => $order->dimension_type ?? 'cm',
				'payment_type' => strtoupper($order->order_type),
				'cod_amount' => (string)($order->cod_amount ?? 0),
				'inv_amount' => (string)($order->invoice_amount ?? 0),
				'order_id' => $orderId,
				'rov_type' => ($order->insurance_type == 1 ? 'ROV_OWNER' : 'ROV_CARRIER'),
				'type_of_package' => $order->weight_order,
				'orderItems' => $order->orderItems,
			]);

			$response = $this->rateCardService->getRate($user, $shippingCompany, $requestData, 1);
			if ($response && is_array($response)) {
				$couriers = array_merge($couriers, $response);
			}
		}
			// if ($shippingCompany->id == 1) {
			// 	$pincodeServiceData = $this->delhiveryB2CService->pincodeService($order->customerAddress->zip_code ?? '', $shippingCompany);

			// 	if (
			// 		!($pincodeServiceData['success'] ?? false) ||
			// 		(isset($pincodeServiceData['response']['success']) && !$pincodeServiceData['response']['success'])
			// 	) {
			// 		continue;
			// 	}

			// 	$response = $this->delhiveryB2CService->freightEstimate($order, $shippingCompany);

			// 	if (
			// 		!($response['success'] ?? false) ||
			// 		(isset($response['response']['success']) && !$response['response']['success'])
			// 	) {
			// 		continue;
			// 	}

			// 	$responseData = $response['response'] ?? [];
			// 	if (!$responseData) {
			// 		continue;
			// 	}

			// 	foreach ($responseData as $responseValue) {
			// 		$taxData = $responseValue['tax_data'] ? array_sum($responseValue['tax_data']) : 0;
			// 		$totalAmount = $responseValue['charge_DL'] ?? 0;
			// 		$chargeDph = $responseValue['charge_DPH'] ?? 0;
			// 		$chargeCod = $responseValue['charge_COD'] ?? 0;
			// 		$chargeRto = $responseValue['charge_RTO'] ?? 0;

			// 		$totalCharges = $taxData + $totalAmount + $chargeDph + $chargeCod;

			// 		$percentageAmount = ($role === "user" && $charge > 0)
			// 			? ($charge_type == 1 ? $charge : ($totalCharges * $charge) / 100)
			// 			: 0;
			// 		$totalCharges += $percentageAmount;

			// 		$tax = $shippingCompany->tax > 0 ? (($totalCharges * $shippingCompany->tax) / 100) : 0;
			// 		$roundedTotalCharges = round($totalCharges + $tax);
			// 		$roundOffAmount = $roundedTotalCharges - ($totalCharges + $tax);

			// 		$couriers[] = [
			// 			'direct_rate' => $totalAmount ?? 0,
			// 			'order_id' => $orderId,
			// 			'tax' => $tax,
			// 			'round_off' => $roundOffAmount,
			// 			'shipping_company_id' => $shippingCompany->id,
			// 			'shipping_company_name' => $shippingCompany->name,
			// 			'shipping_company_logo' => asset('storage/shipping-logo/' . $shippingCompany->logo),
			// 			'courier_id' => '',
			// 			'courier_name' => "{$shippingCompany->name} {$order->shipping_mode}",
			// 			'freight_charges' => ($totalAmount + $chargeDph),
			// 			'cod_charges' => $chargeCod,
			// 			'total_charges' => $roundedTotalCharges,
			// 			'rto_charge' => $chargeRto,
			// 			'min_weight' => ($responseValue['charged_weight'] / 1000),
			// 			'chargeable_weight' => max($volumetricWt, $weight) ?? 0,
			// 			'applicable_weight' =>  max($volumetricWt, $weight) ?? 0,
			// 			'percentage_amount' => $percentageAmount,
			// 			'responseData' => $responseValue
			// 		];
			// 	}

		$couriers = $couriers ? collect($couriers)->sortBy('total_charges') : collect();

		if ($couriers->isEmpty()) {
            $extraData = [
                'zipcodes' => \App\Models\Zipcode::whereIn('pincode', [
                    (string) ($order->warehouse->zip_code ?? ''),
                    (string) ($order->customerAddress->zip_code ?? ''),
                ])->get()->keyBy('pincode'),
            ];

			return response()->json([
				'status' => 'error',
				'msg' => $this->rateCardService->getUnavailableRateMessage($user, $requestData, $extraData),
				'view' => ''
			]);
		}

		if ($request->has('raw')) {
			return response()->json([
				'status' => 'success',
				'couriers' => $couriers
			]);
		}

		$view = view('order.shipment_charges', [
			'order' => $order,
			'couriers' => $couriers,
			'total_courier' => count($couriers)
		])->render();

		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function orderShipNow(Request $request)
	{
		DB::beginTransaction();

		try {
			$requestData = json_decode($request->data, true) ?? [];
			$requestData['is_bulk_shipment'] = 0;
			$requestData['actor_user_id'] = auth()->id();
			$shipmentProcessor = app(\App\Services\ShipmentProcessor::class);
			$response = $shipmentProcessor->orderShipNow($requestData);
			// If service returns error response → rollback
			if (
				$response instanceof \Illuminate\Http\JsonResponse &&
				$response->getData()->status === 'error'
			) {
				DB::rollBack();
				return $response;
			}

			DB::commit();
			return $response;
		} catch (\Throwable $e) {
			DB::rollBack();

			return response()->json([
				'status' => 'error',
				'msg' => 'Something went wrong! ' . $e->getMessage(),
			]);
		}
	}

	// Warehouse pickup
	public function orderWarehouseList()
	{
		$user = Auth::user();
		$query = CourierWarehouse::query();
		if ($user->role === "user") {
			$query->where('user_id', $user->id);
		}
		$pickupWarehouses = $query->where('warehouse_status', 1)
			->get();

		$options = ['<option value="">Select Pickup Location</option>'];

		foreach ($pickupWarehouses as $pickupWarehouse) {
			$jsonWarehouse = htmlspecialchars(json_encode($pickupWarehouse), ENT_QUOTES, 'UTF-8');

			$options[] = '<option value="' . $pickupWarehouse->id . '" data-warehouse=\'' . $jsonWarehouse . '\'>'
				. $pickupWarehouse->warehouse_name . ' - (' . $pickupWarehouse->company_name . ')</option>';
		}

		return response()->json([
			'status' => 'success',
			'output' => implode('', $options)
		]);
	}

	public function orderWarehouseCreate()
	{
		$view = view('order.modals.warehouse-create')->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	// Recipeint/Customer
	public function orderCustomerList()
	{
		$user = Auth::user();
		$includeCustomerId = request('include_customer_id'); // For edit/clone pages

		$customer = Customer::query();
		if ($user->role === "user") {
			// If we need to include a specific customer (for edit/clone), add it to the query
			if ($includeCustomerId) {
				$customer->where(function ($query) use ($user, $includeCustomerId) {
					$query->where('user_id', $user->id)
						->orWhere('id', $includeCustomerId);
				});
			} else {
				$customer->where('user_id', $user->id);
			}
		}
		$customers = $customer->where('status', 1)->get();

		$options = ['<option value="">Select Recipeint/Customer</option>'];

		foreach ($customers as $customer) {
			$options[] = '<option value="' . $customer->id . '">'
				. $customer->first_name . ' ' . $customer->last_name . ' - (' . $customer->mobile . ')</option>';
		}

		return response()->json([
			'status' => 'success',
			'output' => implode('', $options)
		]);
	}

	public function orderCustomerSearchByMobile()
	{
		$user = Auth::user();
		$mobile = request('mobile');

		if (!$mobile || strlen($mobile) != 10) {
			return response()->json([
				'status' => 'error',
				'message' => 'Invalid mobile number'
			]);
		}

		// Step 1: Search in current user's customers first
		$customer = Customer::query();
		if ($user->role === "user") {
			$customer->where('user_id', $user->id);
		}
		$customer->where('mobile', $mobile)->where('status', 1);
		$foundCustomer = $customer->first();

		// Step 2: If not found in user's data, search entire database
		if (!$foundCustomer) {
			$foundCustomer = Customer::where('mobile', $mobile)
				->where('status', 1)
				->first();
		}

		if ($foundCustomer) {
			return response()->json([
				'status' => 'success',
				'customer' => [
					'id' => $foundCustomer->id,
					'name' => $foundCustomer->first_name . ' ' . $foundCustomer->last_name,
					'mobile' => $foundCustomer->mobile,
					'user_id' => $foundCustomer->user_id,
					'is_own' => ($user->role === "user" && $foundCustomer->user_id == $user->id)
				]
			]);
		}

		return response()->json([
			'status' => 'not_found',
			'message' => 'No customer found with this mobile number'
		]);
	}

	public function orderCustomerAddressList($customerId)
	{
		$user = Auth::user();
		$customerAddresses = CustomerAddress::where('customer_id', $customerId)->get();

		$options = ['<option value="">Select Customer Address</option>'];

		foreach ($customerAddresses as $customerAddress) {
			$jsonAddress = htmlspecialchars(json_encode($customerAddress), ENT_QUOTES, 'UTF-8');
			$options[] = '<option value="' . $customerAddress->id . '" data-address=\'' . $jsonAddress . '\'>'
				. $customerAddress->address . ' ' . $customerAddress->city . ' ' . $customerAddress->states . ' ' . $customerAddress->country . ', ' . $customerAddress->zip_code . '</option>';
		}

		return response()->json([
			'status' => 'success',
			'output' => implode('', $options)
		]);
	}

	public function orderCustomerCreate()
	{
		$view = view('order.modals.customer-create')->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function orderCustomerAddressCreate($customerId)
	{
		$view = view('order.modals.customer-address-create', compact('customerId'))->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function orderCustomerAddressStore(Request $request)
	{
		try {
			DB::beginTransaction();
			$customer = Customer::findOrFail($request->customer_id);

			if ($request->has('address') && count($request->address) > 0) {
				$addresses = [];
				foreach ($request->address as $key => $addr) {
					$addresses[] = [
						'customer_id' => $customer->id,
						'address' => $addr,
						'country' => $request->country[$key],
						'state' => $request->state[$key],
						'city' => $request->city[$key],
						'zip_code' => $request->zip_code[$key],
						'status' => 1,
						'created_at' => now(),
						'updated_at' => now(),
					];
				}
				CustomerAddress::insert($addresses);
			}
			$customerAddressId = optional($customer->latestCustomerAddress)->id ?? '';
			DB::commit();
			return response()->json(['status' => 'success', 'msg' => 'The customer address has been successfully added.', 'customer_id' => $customer->id, 'customer_address_id' => $customerAddressId]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	//Bulk Order
	public function orderBulkCreate()
	{
		$user = Auth::user();
		return view('order.bulk-create', compact('user'));
	}

	public function orderBulkSampleDownload(Request $request)
	{
		$typeOfPackage = (int) $request->input('type', 1);
		$typeOfPackage = $typeOfPackage === 2 ? 2 : 1;
		$fileName = $typeOfPackage === 2 ? 'b2b_bulk_order_sample.xlsx' : 'b2c_bulk_order_sample.xlsx';

		return Excel::download(new BulkOrderSampleExport($typeOfPackage), $fileName);
	}

	public function orderBulkStore(Request $request)
	{
		$request->validate([
			'warehouse_id' => ['required', 'exists:courier_warehouses,id'],
			'type_of_package' => ['required', 'in:1,2'],
			'bulk_excel' => ['required', 'file', 'mimes:xlsx,xls'],
		]);

		$user = Auth::user();
		$typeOfPackage = (int) $request->type_of_package;
		DB::beginTransaction(); // Begin Transaction

		try {
			// Import the Excel file
			$bulkOrder = new BulkOrder();
			Excel::import($bulkOrder, $request->file('bulk_excel'));

			// Check if rows exist after removing the header
			if (empty($bulkOrder->rows)) {
				return response()->json(['status' => 'error', 'msg' => 'No valid data found in the Excel file.']);
			}

			$rowErrors = [];

			foreach ($bulkOrder->rows as $index => $row) {
				$row = $this->normalizeBulkImportRow((array) $row, $typeOfPackage);

				// Excel row number: +1 for the stripped header, +1 to make it 1-based.
				$rowNumber = $index + 2;

				// Shared business rule: order_type must be COD or Prepaid. The raw cell
				// is validated (not the "?? 'cod'" default) so empty / null / garbage fails
				// instead of silently defaulting to COD.
				if (!OrderType::isValid($row[2] ?? null)) {
					$rowErrors[] = ['row' => $rowNumber, 'message' => OrderType::INVALID_MESSAGE];
					continue;
				}
				// Persist the canonical lowercase value (accepts "COD", "Prepaid", etc.).
				$row[2] = OrderType::sanitize($row[2]);

				$dateValue = $row[0] ?? null;
				$orderDate = null;

				if ($dateValue) {
					if (is_numeric($dateValue)) {
						// Excel serial number → Carbon date
						$orderDate = Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
					} else {
						// If already string (like 2025-09-24)
						$orderDate = \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
					}
				}

				if ($typeOfPackage === 1) {
					$this->b2cBulkStore($request, $user, $row, $orderDate);
				} else {
					$this->b2bBulkStore($request, $user, $row, $orderDate);
				}
			}

			// Any invalid order_type aborts the whole import (nothing is committed) and the
			// failing rows are returned as a report the UI can show.
			if (!empty($rowErrors)) {
				DB::rollback();
				return response()->json([
					'status' => 'error',
					'msg'    => 'Some rows have an invalid Order Type. No orders were imported.',
					'errors' => $rowErrors,
				]);
			}

			DB::commit(); // Commit Transaction
			return response()->json(['status' => 'success', 'msg' => 'The order has been successfully added.', 'type_of_package' => $typeOfPackage]);
		} catch (\Exception $e) {
			DB::rollback(); // Rollback in case of failure
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	private function normalizeBulkImportRow(array $row, int $typeOfPackage): array
	{
		$row = array_values($row);
		$expectedWithShippingMode = $typeOfPackage === 2 ? 31 : 28;
		$expectedWithoutShippingMode = $typeOfPackage === 2 ? 30 : 27;

		if ($typeOfPackage === 2) {
			$columnOne = strtolower(trim((string) ($row[1] ?? '')));
			$hasShippingMode = in_array($columnOne, ['surface', 'air', 'express'], true);
			if (!$hasShippingMode) {
				array_splice($row, 1, 0, ['surface']);
			}

			$insuranceColumn = $row[4] ?? null;
			$hasInsuranceType = in_array((string) $insuranceColumn, ['1', '2'], true);
			if (!$hasInsuranceType) {
				array_splice($row, 4, 0, [1]);
			}
		} elseif (count($row) === $expectedWithoutShippingMode) {
			array_splice($row, 1, 0, ['surface']);
		}

		if (count($row) < $expectedWithShippingMode) {
			throw new \InvalidArgumentException(
				$typeOfPackage === 2
					? 'Invalid B2B sample format. Please download the latest B2B sample file and upload again.'
					: 'Invalid B2C sample format. Please download the latest B2C sample file and upload again.'
			);
		}

		return array_slice($row, 0, $expectedWithShippingMode);
	}

	private function b2cBulkStore($request, $user, $row, $orderDate)
	{
		// Create Customer
		$customer = Customer::create([
			'user_id'    => $user->id,
			'first_name' => $row[4] ?? '',
			'last_name'  => $row[5] ?? '',
			'email'      => $row[6] ?? null,
			'gst_number' => $row[7] ?? null,
			'mobile'     => $row[8] ?? null,
			'status'     => 1,
		]);

		// Create Customer Address if available
		$customerAddress = null;
		if ($row[9] ?? '') {
			$customerAddress = CustomerAddress::create([
				'customer_id' => $customer->id,
				'address'     => $row[9],
				'zip_code'    => $row[10],
				'city'        => $row[11],
				'state'       => $row[12],
				'country'     => $row[13],
				'status'      => 1,
			]);
		}

		// Assuming the column order matches the Excel data structure
		if (empty($row[27])) {
			throw new \Exception('Reference number is required.');
		}
		if (Order::where('user_id', $user->id)->where('reference_number', $row[27])->exists()) {
			throw new \Exception('Reference number ' . $row[27] . ' is already used.');
		}

		$data = [
			'order_prefix' => Order::generateOrderNumber($user->id),
			'reference_number' => $row[27],
			'order_date' => $orderDate,
			'shipping_mode' => $row[1] ? strtolower($row[1]) : 'surface',
			'order_type' => $row[2] ?? 'cod',
			'cod_amount' => $row[3] ?? 0,
			'warehouse_id' => $request->warehouse_id,
			'customer_id' => $customer->id ?? null,
			'customer_address_id' => $customerAddress->id ?? null,
			'invoice_no' => $row[20] ?? null,
			'invoice_amount' => $row[21] ?? 0,
			'ewaybillno' => $row[22] ?? 0,
			'dimension_type' => 'cm',
			'total_amount' => $row[21] ?? 0,
			'user_id' => $user->id,
			'status_courier' => 'New',
			'weight_order' => 1,
			'weight' => $row[23] ?? 0,
			'length' => $row[24] ?? 0,
			'width' => $row[25] ?? 0,
			'height' => $row[26] ?? 0,
			'status' => 1,
			'created_at' => now(),
			'updated_at' => now()
		];

		$order = Order::create($data);

		// Extract and explode values
		$productCategory = $row[14] ? explode(',', $row[14]) : null;
		$productName = $row[15] ? explode(',', $row[15]) : null;
		$productSku = $row[16] ? explode(',', $row[16]) : null;
		$productHsn = $row[17] ? explode(',', $row[17]) : null;
		$productAmount = $row[18] ? explode(',', $row[18]) : null;
		$productQuantity = $row[19] ? explode(',', $row[19]) : null;


		$orderItems = [];
		$totalAmount = 0; // Initialize total amount

		if ($productCategory) {
			foreach ($productCategory as $key => $productCat) {
				$orderItems[] = [
					'order_id' => $order->id,
					'product_category' => $productCat,
					'product_name' => $productName[$key] ?? 0,
					'sku_number' => $productSku[$key] ?? null,
					'hsn_number' => $productHsn[$key] ?? null,
					'amount' => $productAmount[$key] ?? 0,
					'ewaybillno' => null,
					'quantity' => $productQuantity[$key] ?? 0,
					'created_at' => now(),
					'updated_at' => now()
				];
			}
		}

		// Bulk Insert Order Items
		OrderItem::insert($orderItems);

		// Insert Order Status
		OrderStatus::insert([
			'order_id' => $order->id,
			'order_status' => 'New',
			'created_at' => now(),
			'updated_at' => now()
		]);

		Helper::orderActivity($order->id, 'Order created.');
	}

	private function b2bBulkStore($request, $user, $row, $orderDate)
	{
		// Create Customer
		$customer = Customer::create([
			'user_id'    => $user->id,
			'first_name' => $row[5] ?? '',
			'last_name'  => $row[6] ?? '',
			'email'      => $row[7] ?? null,
			'gst_number' => $row[8] ?? null,
			'mobile'     => $row[9] ?? null,
			'status'     => 1,
		]);

		// Create Customer Address if available
		$customerAddress = null;
		if ($row[10] ?? '') {
			$customerAddress = CustomerAddress::create([
				'customer_id' => $customer->id,
				'address'     => $row[10],
				'zip_code'    => $row[11],
				'city'        => $row[12],
				'state'       => $row[13],
				'country'     => $row[14],
				'status'      => 1,
			]);
		}

		// Assuming the column order matches the Excel data structure
		if (empty($row[30])) {
			throw new \Exception('Reference number is required.');
		}
		if (Order::where('user_id', $user->id)->where('reference_number', $row[30])->exists()) {
			throw new \Exception('Reference number ' . $row[30] . ' is already used.');
		}

		$data = [
			'order_prefix' => Order::generateOrderNumber($user->id),
			'reference_number' => $row[30],
			'order_date' => $orderDate,
			'shipping_mode' => $row[1] ? strtolower($row[1]) : 'surface',
			'order_type' => $row[2] ?? 'cod',
			'cod_amount' => $row[3] ?? 0,
			'insurance_type' => in_array((string) ($row[4] ?? '1'), ['1', '2'], true) ? (int) $row[4] : 1,
			'warehouse_id' => $request->warehouse_id,
			'customer_id' => $customer->id ?? null,
			'customer_address_id' => $customerAddress->id ?? null,
			'invoice_no' => $row[21] ?? null,
			'invoice_amount' => $row[22] ?? 0,
			'ewaybillno' => $row[23] ?? 0,
			'dimension_type' => 'cm',
			'total_amount' => $row[22] ?? 0,
			'user_id' => $user->id,
			'status_courier' => 'New',
			'weight_order' => 2,
			'status' => 1,
			'created_at' => now(),
			'updated_at' => now()
		];

		$order = Order::create($data);

		// Extract and explode values
		$productCategory = $row[15] ? explode(',', $row[15]) : null;
		$productName = $row[16] ? explode(',', $row[16]) : null;
		$productSku = $row[17] ? explode(',', $row[17]) : null;
		$productHsn = $row[18] ? explode(',', $row[18]) : null;
		$productAmount = $row[19] ? explode(',', $row[19]) : null;
		$productQuantity = $row[20] ? explode(',', $row[20]) : null;

		$noOfBox = $row[24] ? explode(',', $row[24]) : null;
		$weightBox = $row[25] ? explode(',', $row[25]) : null;
		$lengthBox = $row[26] ? explode(',', $row[26]) : null;
		$widthBox = $row[27] ? explode(',', $row[27]) : null;
		$heightBox = $row[28] ? explode(',', $row[28]) : null;
		$weight = $row[29] ?? 0;

		$orderItems = [];
		$height = $width = $length = 0;

		if (!empty($productCategory)) {
			foreach ($productCategory as $key => $productCat) {
				$amount = $request->amount[$key] ?? 0;
				$quantity = $request->quantity[$key] ?? 1;

				$orderItems[] = [
					'order_id' => $order->id,
					'product_category' => $productCat,
					'product_name' => $productName[$key] ?? null,
					'sku_number' => $productSku[$key] ?? null,
					'hsn_number' => $productHsn[$key] ?? null,
					'amount' => $productAmount[$key] ?? 0,
					'ewaybillno' => null,
					'quantity' => $productQuantity[$key] ?? 0,
					'created_at' => now(),
					'updated_at' => now(),
					'dimensions' => json_encode([
						'no_of_box' => $noOfBox[$key] ?? 0,
						'weight' => $weightBox[$key] ?? 0,
						'length' => $lengthBox[$key] ?? 0,
						'width' => $widthBox[$key] ?? 0,
						'height' => $heightBox[$key] ?? 0,
					]),
				];

				// Accumulate totals
				$length += $lengthBox[$key] ?? 0;
				$width  += $widthBox[$key] ?? 0;
				$height += $heightBox[$key] ?? 0;
			}

			OrderItem::insert($orderItems);
		}

		$order->update([
			'weight' => $weight,
			'length' => $length,
			'width'  => $width,
			'height' => $height
		]);
	}

	public function codOrderRemittance()
	{
		$shippingCompanies = ShippingCompany::whereStatus(1)->get();
		$users = User::where('role', 'user')->where('kyc_status', 1)->get();
		return view('remmitance.index', compact('shippingCompanies', 'users'));
	}

	public function remmitanceAjax(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->post("start", 0);
		$limit = $request->post("length", 10);

		// Handle ordering safely
		$order = $request->post('order', []);
		$columns = $request->post('columns', []);

		$orderColumnIndex = $order[0]['column'] ?? 0;
		$orderColumnName = $columns[$orderColumnIndex]['data'] ?? 'id';
		$orderDir = $order[0]['dir'] ?? 'asc';

		$orderColumnName = $orderColumnName === 'action' ? 'id' : $orderColumnName;

		$search = $request->input('search'); // DataTables sends `search[value]`
		$status = $request->post('status');

		$user = Auth::user();
		$role = $user->role;
		$userId = $user->id;

		// Base query
		$baseQuery = Order::with(['user'])
			->where('orders.status_courier', 'delivered')
			->where('orders.is_remmitance', '0')
			->where('orders.order_type', 'cod');

		if ($request->filled('user_id')) {
			$baseQuery->where('user_id', $request->user_id);
		} else {
			if ($role === "user") {
				$baseQuery->where('user_id', $userId);
			}
		}

		if ($request->filled('from_date') && $request->filled('to_date')) {
			$from = Carbon::parse($request->from_date)->startOfDay();
			$to = Carbon::parse($request->to_date)->endOfDay();
			$baseQuery->whereBetween('orders.delivery_date', [$from, $to]);
		} elseif ($request->filled('from_date')) {
			$from = Carbon::parse($request->from_date)->startOfDay();
			$baseQuery->whereDate('orders.delivery_date', $from);
		} elseif ($request->filled('to_date')) {
			$to = Carbon::parse($request->to_date)->endOfDay();
			$baseQuery->whereDate('orders.delivery_date', $to);
		}

		if ($request->filled('shipping_company_id')) {
			$baseQuery->where('orders.shipping_company_id', $request->shipping_company_id);
		}

		// Search filter
		if (!empty($search)) {
			$baseQuery->where(function ($query) use ($search) {
				$query->where('orders.created_at', 'like', "%{$search}%")
					->orWhere('orders.awb_number', 'like', "%{$search}%")
					->orWhere('orders.courier_name', 'like', "%{$search}%")
					->orWhere('orders.status_courier', 'like', "%{$search}%")
					->orWhere('orders.id', 'like', "%{$search}%")
					->orWhere('orders.order_prefix', 'like', "%{$search}%")
					->orWhereHas('user', function ($q) use ($search) {
						$q->where('name', 'like', "%{$search}%")
							->orwhere('email', 'like', "%{$search}%")
							->orwhere('mobile', 'like', "%{$search}%")
							->orwhere('company_name', 'like', "%{$search}%");
					});
			});
		}

		$totalData = $baseQuery->count();

		$resultsCloned = (clone $baseQuery)->select(DB::raw('SUM(orders.total_amount) as total_Amt'))->first();

		// Pagination and Sorting
		$orders = $baseQuery
			->offset($start)
			->limit($limit)
			->get();

		// Prepare output
		$data = [];
		$i = $start + 1;
		foreach ($orders as $order) {
			$userName = $order->user->name ?? 'N/A';
			$sellerCompany = $order->user->company_name ?? 'N/A';
			$userEmail = $order->user->email ?? 'N/A';
			$userMobile = $order->user->mobile ?? 'N/A';

			$data[] = [
				'id' =>  $i,
				'order_id' => $order->id . ' <br>#' . $order->order_prefix,
				'seller_details' => "<div class='main-cont1-2'><p>{$userName} ({$sellerCompany})</p><p>{$userEmail}</p><p>{$userMobile}</p></div>",
				'amount' => $order->cod_amount,
				'delivery_date' => $order->delivery_date,
				'shipment_details' => "<p>{$order->courier_name}</p><p>AWB No: <b>{$order->awb_number}</b></p>",
				'status_courier' => "<p class='prepaid'>{$order->status_courier}</p>",
			];
			$i++;
		}

		return response()->json([
			"draw" => intval($draw),
			"recordsTotal" => intval($totalData),
			"recordsFiltered" => intval($totalData),
			"data" => $data,
			"iTotalRecords" => intval($totalData),
			"iTotalDisplayRecords" => intval($totalData),
			"aaData" => $data,
			"totAmt" => $resultsCloned->total_Amt ?? 0,
		]);
	}


	public function downloadRemittanceExcel($id)
	{
		$codVoucher = CodVoucher::with(['codVoucherOrders'])->find($id);
		return Excel::download(new CodRemittanceExport($codVoucher), $codVoucher->voucher_no . '.xlsx');
	}

	public function codPayout()
	{
		$users = CodVoucher::select(DB::raw('MAX(id) as id'), 'user_id')
			->groupBy('user_id')
			->with('user')
			->get();

		return view('remmitance.cod-payout', compact('users'));
	}

	public function codPayoutAjax(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->get("start");
		$length = $request->get("length");
		$searchValue = $request->input("search");

		$user = auth()->user();
		$role = $user->role;
		$userId = $user->id;

		$query = CodVoucher::with(['user', 'codVoucherOrders']);

		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		} else {
			if ($role === "user") {
				$query->where('user_id', $userId);
			}
		}

		if ($request->filled('voucher_status')) {
			$query->where('voucher_status', $request->voucher_status);
		}

		if ($request->filled('from_date') && $request->filled('to_date') && $request->filled('date_type')) {
			$query->whereBetween($request->date_type, [$request->from_date, $request->to_date]);
		} elseif ($request->filled('from_date') && $request->filled('date_type')) {
			$query->whereDate($request->date_type, $request->from_date);
		} elseif ($request->filled('to_date') && $request->filled('date_type')) {
			$query->whereDate($request->date_type, $request->to_date);
		}

		if (!empty($searchValue)) {
			$query->where(function ($q) use ($searchValue) {
				$q->where('voucher_no', 'like', "%$searchValue%")
					->orWhereHas('user', function ($uq) use ($searchValue) {
						$uq->where('name', 'like', "%$searchValue%")
							->orWhere('email', 'like', "%$searchValue%")
							->orWhere('mobile', 'like', "%$searchValue%")
							->orWhere('company_name', 'like', "%$searchValue%");
					});
			});
		}

		$total = $query->count();

		$vouchers = $query->offset($start)
			->limit($length)
			->orderBy('id', 'desc')
			->get();

		$data = [];
		$i = $start + 1;
		foreach ($vouchers as $voucher) {
			$userName = $voucher->user->name ?? 'N/A';
			$sellerCompany = $voucher->user->company_name ?? 'N/A';
			$userEmail = $voucher->user->email ?? 'N/A';
			$userMobile = $voucher->user->mobile ?? 'N/A';

			$action = '<div class="main-btn-1">
				<div class="mian-btn">
				<div class="btn-group">';
			// if ($voucher->voucher_status == 0 && auth()->user()->role != "user" && config('permission.cod_payout.add')) {
			// 	$action .= '<button class="btn btn-primary" data-payout-id="' . $voucher->id . '" onclick="payNow(this)" type="button"> Pay Now</button>';
			// }
			$url = route('remmitance.download.excel', $voucher->id);
			$action .= '<a class="btn btn-success" style="border:1px solid #1288DB !important;background:#E4F2FD!important;color: #1288DB !important;" href="' . $url . '">Download</a>';
			$action .= '</div>
				</div>
				</div>';

			$data[] = [
				'id' => $i,
				'seller_details' => "<div class='main-cont1-2'><p>{$userName} ({$sellerCompany})</p><p>{$userEmail}</p><p>{$userMobile}</p></div>",
				'voucher_date' => $voucher->voucher_date,
				'voucher_no' => $voucher->voucher_no,
				'net_amount' => $voucher->net_amount ?? '',
				'cod_charges' => $voucher->cod_charges ?? '',
				'gst_amount' => $voucher->gst_amount ?? '',
				'amount' => $voucher->amount ?? '',
				'status' => $voucher->voucher_status == 0 ? '<span class="badge badge-danger">UnPaid</span>' : '<span class="badge badge-success">Paid</span>',
				'remarks' => $voucher->remarks ?? 'N/A',
				'reference_no' => $voucher->reference_no ?? 'N/A',
				'payout_date' => $voucher->payout_date ?? 'N/A',
				'action' => $action,
			];
			$i++;
		}

		return response()->json([
			"draw" => intval($draw),
			"recordsTotal" => $total,
			"recordsFiltered" => $total,
			"data" => $data,
		]);
	}

	public function storePayout(Request $request)
	{
		try {
			DB::beginTransaction();

			$data = $request->except('id', '_token');
			$data['voucher_status'] = 1;

			$codPayout = CodVoucher::findOrFail($request->id);
			$codPayout->update($data);
			DB::commit();

			return redirect()->back()->with('success', 'Cod Payout successfully.');
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->back()->with('error', 'An error occurred. Please try again.');
		}
	}

	public function searchByAwb(Request $request)
	{
		try {
			$query = $request->get('query');
			if (empty($query)) {
				return response()->json([
					'results' => []
				]);
			}

			$orders = Order::where(function ($q) use ($query) {
				$q->where('order_prefix', 'LIKE', "%{$query}%")
					->orWhere('awb_number', 'LIKE', "%{$query}%")
					->orWhere('platform_order_id', 'LIKE', "%{$query}%")
					->orWhere('platform_order_number', 'LIKE', "%{$query}%")
					->orWhere('platform_order_name', 'LIKE', "%{$query}%");
			})
				->with(['customer:id,first_name,last_name'])
				->select([
					'id',
					'order_prefix',
					'awb_number',
					'status_courier',
					'platform_source',
					'platform_order_number',
					'platform_order_name',
					'customer_id',
					'created_at',
					'weight_order'
				])
				->orderBy('created_at', 'desc')
				->limit(10)
				->get()
				->map(function ($order) {
					return [
						'id' => $order->id,
						'order_prefix' => $order->order_prefix,
						'awb_number' => $order->awb_number,
						'platform_source' => $order->platform_source,
						'platform_order_number' => $order->platform_order_number,
						'platform_order_name' => $order->platform_order_name,
						'status_courier' => $order->status_courier,
						'customer_name' => $order->customer
							? $order->customer->first_name . ' ' . $order->customer->last_name
							: 'N/A',
						'created_at' => $order->created_at->format('Y-m-d H:i:s'),
						'weight_order' => $order->weight_order
					];
				});

			return response()->json([
				'results' => $orders
			]);
		} catch (\Exception $e) {
			\Log::error('Order search error: ' . $e->getMessage());
			return response()->json([
				'error' => 'An error occurred while searching orders',
				'results' => []
			], 500);
		}
	}

	public function orderPackageDimensionUpdate(Request $request)
	{
		$data = array_filter($request->except('_token', 'order_id'), function ($value) {
			return $value !== null && $value !== '' && $value !== '0' && $value !== 0;
		});
		$orderId = $request->order_id;

		DB::beginTransaction();
		try {

			Order::whereId($orderId)->update($data);
			Helper::orderActivity($orderId, 'Order dimension updated.');
			DB::commit();
			return response()->json([
				'status' => 'success',
				'msg' => 'Package dimensions updated successfully. You can now process the shipment again.'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	public function orderPackageDimensionBulkUpdate(Request $request)
	{
		$data = array_filter($request->except('_token', 'order_ids'), function ($value) {
			return $value !== null && $value !== '' && $value !== '0' && $value !== 0;
		});
		$orderIds = $request->order_ids ? explode(',', $request->order_ids) : [];
		$orderIds = array_values(array_filter($orderIds, function ($id) {
			return $id !== '';
		}));
		if (empty($orderIds)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No orders selected.'
			], 400);
		}

		DB::beginTransaction();
		try {
			Order::whereIn('id', $orderIds)->update($data);

			// ✅ Activity log (lightweight loop)
			foreach ($orderIds as $orderId) {
				Helper::orderActivity($orderId, 'Order dimension updated.');
			}

			DB::commit();

			return response()->json([
				'status' => 'success',
				'msg' => 'Package dimensions updated successfully. You can now process the shipment again.'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => $e->getMessage()
			], 500);
		}
	}

	public function orderBulkShipNow(Request $request)
	{
		$orderIds = $request->order_ids ? explode(',', $request->order_ids) : [];
		$orderIds = array_values(array_filter($orderIds, function ($id) {
			return $id !== '';
		}));

		if (empty($orderIds)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No orders selected. Please select at least one order to continue.',
			]);
		}

		$user = Auth::user();
		$data = $request->all();
		$data['is_shopify'] = 0;

		try {

			DB::beginTransaction();

			ProcessBulkShipment::dispatch($orderIds, $data, $user);

			Order::whereIn('id', $orderIds)
				->update(['queue' => 1, 'shipment_failure_reason' => null]);

			DB::commit();

			return response()->json([
				'status' => 'success',
				'msg'    => 'Bulk shipment queued successfully. Processing will continue in the background.',
			]);
		} catch (\Exception $e) {

			DB::rollBack();

			return response()->json([
				'status' => 'error',
				'msg'    => 'Something went wrong while queuing the bulk shipment. Please try again.',
			]);
		}
	}

	public function orderBulkCancel(Request $request)
	{
		$orderIds = array_values($request->input('order_ids'));
		$status = strtolower($request->input('status'));

		$orderIds = array_values(array_filter($orderIds, function ($id) {
			return $id !== '';
		}));

		if (empty($orderIds)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No orders selected. Please select at least one order to continue.',
			]);
		}
		if (!$status) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Something Went Wrong.',
			]);
		}

		DB::beginTransaction();
		try {
			$result = [
				'processed' => 0,
				'cancelled' => 0,
				'skipped' => 0,
				'errors' => 0,
			];

			if ($status === "new") {
				$result = $this->cancelBulkOrder($orderIds, $request->input('cancel_on_channel', 0));
			} else {
				$result = $this->cancelBulkShipment($orderIds, $request->input('cancel_on_channel', 0));
			}

			DB::commit();

			return response()->json([
				'status' => 'success',
				'msg' => 'Orders cancelled successfully',
				'summary' => $result,
			]);
		} catch (\Throwable $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to cancel orders. Try again or contact support.',
			]);
		}
	}

	public function cancelBulkOrder($orderIds, $cancelOnChannel = 0)
	{
		$now = Carbon::now();
		$chunkSize = 100;
		$total = 0;
		foreach (array_chunk($orderIds, $chunkSize) as $chunk) {
			$total += count($chunk);

			// Cancel on channel logic for bulk
			if ($cancelOnChannel == 1) {
				$orders = Order::whereIn('id', $chunk)->get();
				foreach ($orders as $order) {
					$this->cancelShopifyOrder($order);
				}
			}

			Order::whereIn('id', $chunk)
				->update([
					'status_courier' => 'cancelled',
					'order_cancel_date' => $now->toDateString(),
					'updated_at' => $now,
				]);

			$statusRows = [];
			$statusActivityLog = [];

			foreach ($chunk as $oid) {
				$statusRows[] = [
					'order_id' => $oid,
					'order_status' => 'cancelled',
					'created_at' => $now,
					'updated_at' => $now,
				];

				$statusActivityLog[] = [
					'order_id' => $oid,
					'activity_name' => "Order cancelled.",
					'date' => $now->format('Y-m-d'),
					'created_at' => $now,
					'updated_at' => $now,
				];
			}

			DB::table('order_activities')->insert($statusActivityLog);
			DB::table('order_statuses')->insert($statusRows);
		}

		return [
			'processed' => $total,
			'cancelled' => $total,
			'skipped' => 0,
			'errors' => 0,
		];
	}

	public function cancelBulkShipment($orderIds, $cancelOnChannel = 0)
	{
		$now = Carbon::now();
		$chunkSize = 10;

		$orders = Order::with(['user', 'shippingCompany'])
			->whereIn('id', $orderIds)
			->get();

		// initialize counters
		$total = count($orderIds);
		$attempted = 0;
		$success = 0;
		$skipped = 0;
		$errors = 0;

		if ($orders->isEmpty()) {
			// nothing to do
			return [
				'processed' => 0,
				'cancelled' => 0,
				'skipped' => $total,
				'errors' => 0,
			];
		}

		foreach ($orders->chunk($chunkSize) as $chunk) {
			foreach ($chunk as $order) {
				// skip already-cancelled
				if ($order->status_courier === "cancelled" || $order->status_courier === "seller cancelled") {
					$skipped++;
					continue;
				}

				$user = $order->user ?? null;
				if (!$user) {
					$skipped++;
					continue;
				}

				$shippingCompany = $order->shippingCompany ?? null;
				if (!$shippingCompany) {
					$skipped++;
					continue;
				}

				$orderCancelledData = [];

				// attempt cancellation based on courier
				try {
					if ($shippingCompany->id == 1) {
						$attempted++;
						$response = $this->shadowfaxService->cancel(
							$order->awb_number,
							$shippingCompany,
							'Request cancelled by customer',
							$order->order_prefix
						);
						if (!($response['success'] ?? false)) {
							$errors++;
							continue;
						}

						$isQueuedCancellation = (bool) ($response['queued'] ?? false);

						if (!$isQueuedCancellation && $cancelOnChannel == 1) {
							$this->cancelShopifyOrder($order);
						}

						$orderCancelledData = [
							'status_courier' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
							'order_cancel_date' => now()->toDateString(),
							'reason_cancel' => $response['message'] ?? "Shipment cancelled in Shadowfax for AWB: {$order->awb_number}.",
						];
					} elseif (in_array($shippingCompany->id, [3, 4, 5])) {
						$attempted++;
						$response = $this->delhiveryB2CService->cancelShipmentByAwbNumber($order->awb_number, $shippingCompany);
						if (!($response['success'] ?? false)) {
							$errors++;
							continue;
						}

						// Cancel on channel logic for bulk
						if ($cancelOnChannel == 1) {
							$this->cancelShopifyOrder($order);
						}

						$orderCancelledData = [
							'status_courier' => 'cancelled',
							'order_cancel_date' => now()->toDateString(),
							'reason_cancel' => "For AWB number {$order->awb_number}, Shipment has been cancelled.",
						];
					}
				} catch (\Throwable $ex) {
					// count as error and continue
					$errors++;
					continue;
				}

				if (empty($orderCancelledData)) {
					$skipped++;
					continue;
				}

				$order->update($orderCancelledData);

				$isQueuedCancellation = strtolower((string) ($orderCancelledData['status_courier'] ?? '')) === 'cancel requested';

				if (!$isQueuedCancellation && $user->role === 'user') {
					// Use update directly with increment to avoid fetching full user record
					$user->increment('wallet_amount', $order->shipping_charge);

					$walletRefund = (float) ($order->shipping_charge ?? 0);
					$isCod = strtolower($order->order_type ?? '') === 'cod';
					$codCharges = (float) ($order->cod_charges ?? 0);
					$codBase = $codCharges;
					$codGst = round($codBase * 0.18, 2);
					$codTotal = $isCod ? round($codBase + $codGst, 2) : 0;
					$freightTotal = max(0, round($walletRefund - $codTotal, 2));
					$freightBase = round($freightTotal / 1.18, 2);
					$freightGst = round($freightTotal - $freightBase, 2);

					if ($freightTotal > 0) {
						Billing::create([
							'user_id' => $order->user_id,
							'billing_type' => 'Order',
							'billing_type_id' => $order->id,
							'transaction_type' => 'credit',
							'amount' => $freightTotal,
							'note' => 'Order is cancelled with AWB number: ' . $order->awb_number . ' | Base Freight: ' . number_format($freightBase, 2, '.', '') . ' | GST (18%): ' . number_format($freightGst, 2, '.', '') . ' | Total: ' . number_format($freightTotal, 2, '.', ''),
							'created_at' => now(),
							'updated_at' => now(),
						]);
					}

					if ($isCod && $codTotal > 0) {
						Billing::create([
							'user_id' => $order->user_id,
							'billing_type' => 'Order',
							'billing_type_id' => $order->id,
							'transaction_type' => 'credit',
							'amount' => $codTotal,
							'note' => 'COD refund for AWB: ' . $order->awb_number . ' | COD Base: ' . number_format($codBase, 2, '.', '') . ' | GST (18%): ' . number_format($codGst, 2, '.', '') . ' | Total: ' . number_format($codTotal, 2, '.', ''),
							'created_at' => now(),
							'updated_at' => now(),
						]);
					}
				}

				OrderStatus::insert([
					'order_id' => $order->id,
					'order_status' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
					'created_at' => now(),
					'updated_at' => now()
				]);
				Helper::orderActivity(
					$order->id,
					$isQueuedCancellation
						? 'Shadowfax cancellation queued for awb number : ' . $order->awb_number
						: 'Order is canceled with awb number : ' . $order->awb_number
				);
				$success++;
			}
		}

		return [
			'processed' => $total,
			'attempted' => $attempted,
			'cancelled' => $success,
			'skipped' => $skipped,
			'errors' => $errors,
		];
	}

	private function cancelShopifyOrder($order)
	{
		if ($order->is_platform_order == 1 && $order->platform_source == 'shopify') {
			$user = User::find($order->user_id);
			if ($user && $user->role == 'user' && $user->shop) {
				$shop = Shop::where('shopify_domain', $user->shop)->first();
				if ($shop && $order->platform_order_number) {
				    $shopifyToken = $shop->getValidShopifyToken();
					$apiVersion = config('services.shopify_api_version', '2023-01');
					$response = Http::withHeaders([
						'X-Shopify-Access-Token' => $shopifyToken,
						'Content-Type' => 'application/json',
					])->post("https://{$user->shop}/admin/api/{$apiVersion}/orders/{$order->platform_order_number}/cancel.json");

					if ($response->failed()) {
						Log::error("Failed to cancel Shopify order: {$order->platform_order_number}", $response->json());
					}
				}
			}
		}
	}

	public function orderBulkSyncChannel(Request $request)
	{
		$orderIds = $request->input('order_ids', []);
		$orderIds = array_values(array_filter($orderIds, function ($id) {
			return $id !== '';
		}));

		if (empty($orderIds)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No orders selected.'
			]);
		}

		$orders = Order::whereIn('id', $orderIds)->get();
		$syncedCount = 0;
		$deletedCount = 0;
		$apiVersion = config('services.shopify_api_version', '2023-01');

		foreach ($orders as $order) {
			try {
				if ($order->is_platform_order && $order->platform_source == 'shopify' && strtolower($order->status_courier) == 'new') {
					$user = User::find($order->user_id);
					if ($user && $user->role == 'user' && $user->shop) {
						$shop = Shop::where('shopify_domain', $user->shop)->first();
						$shopifyId = $order->platform_order_number ?? $order->shopify_order_number;

						if ($shop && $shop->shopify_token && $shopifyId) {
                            $shopifyToken = $shop->getValidShopifyToken();
							$response = Http::withHeaders([
								'X-Shopify-Access-Token' => $shopifyToken,
								'Content-Type' => 'application/json',
							])->get("https://{$user->shop}/admin/api/{$apiVersion}/orders/{$shopifyId}.json");


							if ($response->successful()) {
								$shopifyOrder = $response->json('order');

								$fulfillmentStatus = $shopifyOrder['fulfillment_status'] ?? null;
								if ($fulfillmentStatus === 'fulfilled') {
									$order->delete();
									$order->orderItems()->delete();
									$order->orderStatus()->delete();
									$order->orderActivities()->delete();
									$deletedCount++;
								} else {
								}
								$syncedCount++;
							} else {
								Log::error("Failed to sync Shopify order: {$shopifyId}", $response->json());
							}
						}
					}
				}
			} catch (\Exception $e) {
				Log::error("Exception syncing order {$order->id}: " . $e->getMessage());
			}
		}

		return response()->json([
			'status' => 'success',
			'msg' => "Sync complete. {$syncedCount} orders checked. {$deletedCount} fulfilled orders were removed."
		]);
	}

	public function courierImage($imageUrl, $courierId)
	{
		// Build file info
		$extension = 'png';
		$filename = "{$courierId}.{$extension}";
		$localPath = "courier-logo/{$filename}";

		// ✅ Check if file already exists
		if (Storage::disk('public')->exists($localPath)) {
			// Return existing local file URL
			return asset("storage/{$localPath}");
		}

		// Fetch image from URL
		try {
			$response = Http::withOptions(['verify' => false])->get($imageUrl);

			if (!$response->successful()) {
				// If failed, return original image URL
				return $imageUrl;
			}

			// Save file locally
			Storage::disk('public')->put($localPath, $response->body());

			// Return local URL
			return asset("storage/{$localPath}");
		} catch (\Exception $e) {
			return $imageUrl;
		}
	}

}
