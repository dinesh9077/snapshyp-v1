<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, File, Storage, Validator, Http, Log, Cache};
use App\Models\{
	Order,
	OrderItem,
	Vendor,
	VendorAddress,
	OrderActivity,
	OrderStatus,
	Billing,
	Packaging,
	WeightFreeze,
	Customer,
	User,
	CustomerAddress,
	PincodeService,
	ShippingCompany,
	CourierWarehouse,
	ProductCategory,
	CodVoucher,
	CourierCommission,
	StatusMapping,
	LabelPreference
};
use App\Exports\PendingStarOrderExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use App\Services\ShipMozo;
use App\Services\DelhiveryService;
use App\Services\DelhiveryB2CService;
use App\Services\RateCardService;
use App\Services\ShipmentProcessor;
use App\Services\UrbaneBoltService;
use App\Services\RoutxService;
use App\Services\ShadowfaxService;
use App\Imports\BulkOrder;
use App\Traits\ApiResponse;
use App\Support\OrderType;
use Helper;
use DNS1D, DNS2D;
use App\Exports\CodRemittanceExport;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OrderController extends Controller
{
	use ApiResponse;

	protected $delhiveryService;
	protected $shipMozo;
	protected $delhiveryB2CService;
	protected $rateCardService;
	protected $shipmentProcessor;
	protected $urbaneBoltService;
	protected $routxService;
	protected $shadowfaxService;

	public function __construct()
	{
		$this->shipMozo = new ShipMozo();
		$this->delhiveryService = new DelhiveryService();
		$this->delhiveryB2CService = new DelhiveryB2CService();
		$this->rateCardService = new RateCardService();
		$this->shipmentProcessor = new ShipmentProcessor();
		$this->urbaneBoltService = class_exists(UrbaneBoltService::class)
			? new UrbaneBoltService()
			: null;
		$this->routxService = new RoutxService();
		$this->shadowfaxService = new ShadowfaxService();
	}

	public function index(Request $request)
	{
		$start = $request->input("start") ?? $request->input("offset") ?? 0;
		$limit = $request->input("length") ?? $request->input("limit") ?? 10;

		$search = $request->input('search') ?? $request->input('search.value') ?? '';
		$statusInput = trim((string) $request->input('status', 'all'));
		$weightOrder = 1;

		$user = Auth::user();
		$role = $user->role;
		$id = $user->id;

		// Build the query for orders
		$query = Order::with([
			'customer:id,first_name,last_name,mobile,email',
			'customerAddress:id,address,city,state,country,zip_code',
			'warehouse:id,warehouse_name,contact_name,contact_number,company_name,address,city,state,zip_code',
			'user:id,name,company_name,email,mobile',
			'orderItems:id,order_id,product_category,product_discription,product_name,sku_number,hsn_number,amount,quantity,dimensions',
		])
			->select('id', 'order_prefix', 'user_id', 'courier_id', 'courier_logo', 'customer_id', 'delivery_date', 'customer_address_id', 'shipping_company_id', 'warehouse_id', 'status_courier', 'order_type', 'created_at', 'weight_order', 'cod_amount', 'awb_number', 'invoice_amount', 'length', 'width', 'height', 'weight', 'reason_cancel', 'courier_name', 'shopify_order_name', 'shopify_order_id', 'platform_source', 'lr_no')
			->when(!$user->isMasterAdmin(), function ($q) use ($role, $id) {
				if (in_array(strtolower($role), ['admin', 'reseller', 'staff'], true)) {
					$q->where(function ($subQ) use ($id) {
						$subQ->where('orders.user_id', $id)
							->orWhereHas('user', function ($uq) use ($id) {
								$uq->where('staff_id', $id);
							});
					});
				} else {
					$q->where('orders.user_id', $id);
				}
			})
			->where('weight_order', 1);

		// Status Handling (Case-insensitive mapping)
		if (strtolower($statusInput) !== "all") {
			// Find the mapping where internal_status matches $statusInput case-insensitively
			$mapping = StatusMapping::whereRaw('LOWER(internal_status) = ?', [strtolower($statusInput)])->first();

			if ($mapping) {
				$courierStatuses = $mapping->courier_status;
				if (!empty($courierStatuses)) {
					if (!is_array($courierStatuses)) {
						$courierStatuses = explode(',', $courierStatuses);
					}
					$courierStatuses = array_map(fn($s) => strtolower(trim($s)), $courierStatuses);
					$query->whereIn('status_courier', $courierStatuses);
				}
			}
		}

		// Filters
		if ($request->filled('courier_name')) {
			$query->where('orders.courier_name', $request->courier_name);
		}

		if ($request->filled('status_courier')) {
			$query->where('orders.status_courier', $request->status_courier);
		}

		if ($request->filled('user_id')) {
			$query->where('orders.user_id', (int) $request->user_id);
		}

		if ($request->filled('warehouse_id')) {
			$query->where('orders.warehouse_id', (int) $request->warehouse_id);
		}

		if ($request->filled('shipping_company_id')) {
			$query->where('orders.shipping_company_id', (int) $request->shipping_company_id);
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
			$query->where('orders.platform_source', 'wordpress');
		}

		if ($request->filled('is_portal_order')) {
			$query->where(function ($q) {
				$q->where('orders.is_shopify_order', 0)
					->orWhereNull('orders.is_shopify_order');
			})
				->where(function ($q) {
					$q->whereNull('orders.platform_source')
						->orWhereNotIn('orders.platform_source', ['shopify', 'wordpress', 'WordPress']);
				});
		}

		if ($request->filled('payment_type') || $request->filled('order_type')) {
			$query->where('orders.order_type', $request->payment_type ?? $request->order_type);
		}

		if ($request->filled('invoice_no')) {
			$query->where('orders.invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
		}

		// Weight range filter (Kg)
		if ($request->filled('min_weight') || $request->filled('max_weight')) {
			$min = ($request->input('min_weight') !== null && $request->input('min_weight') !== '') ? (float) $request->min_weight : null;
			$max = ($request->input('max_weight') !== null && $request->input('max_weight') !== '') ? (float) $request->max_weight : null;

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

		if ($request->filled('order_tag')) {
			$query->WhereRaw("JSON_SEARCH(orders.shopify_tags, 'one', ?) IS NOT NULL", [$request->order_tag]);
		}

		// Date Filter
		if ($request->filled('date_by') && $request->filled('from_date') && $request->filled('to_date')) {
			$query->whereBetween($request->date_by, [
				\Carbon\Carbon::parse($request->from_date)->startOfDay(),
				\Carbon\Carbon::parse($request->to_date)->endOfDay()
			]);
		} elseif ($request->filled('from_date') && $request->filled('to_date')) {
			$query->whereBetween('orders.created_at', [
				\Carbon\Carbon::parse($request->from_date)->startOfDay(),
				\Carbon\Carbon::parse($request->to_date)->endOfDay()
			]);
		}

		if ($weightOrder) {
			$query->where('orders.weight_order', (int) $weightOrder);
		}

		// Advanced Search (Bulk and Specific)
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

		// General Search Filter
		if (!empty($search)) {
			$query->where(function ($q) use ($search) {
				$q->where('orders.id', 'LIKE', "%$search%")
					->orWhere('orders.order_prefix', 'LIKE', "%$search%")
					->orWhere('orders.created_at', 'LIKE', "%$search%")
					->orWhere('orders.awb_number', 'LIKE', "%$search%")
					->orWhere('orders.shopify_order_number', 'LIKE', "%$search%")
					->orWhere('orders.shopify_order_id', 'LIKE', "%$search%")
					->orWhere('orders.courier_name', 'LIKE', "%$search%")
					->orWhere('orders.status_courier', 'LIKE', "%$search%")
					->orWhere('orders.lr_no', 'LIKE', "%$search%")
					->orWhereHas('customer', function ($sub) use ($search) {
						$sub->where('first_name', 'LIKE', "%$search%")
							->orWhere('last_name', 'LIKE', "%$search%")
							->orWhere('mobile', 'LIKE', "%$search%")
							->orWhere('email', 'LIKE', "%$search%");
					})
					->orWhereHas('user', function ($sub) use ($search) {
						$sub->where('name', 'LIKE', "%$search%")
							->orWhere('email', 'LIKE', "%$search%")
							->orWhere('company_name', 'LIKE', "%$search%")
							->orWhere('mobile', 'LIKE', "%$search%");
					})
					->orWhereHas('warehouse', function ($sub) use ($search) {
						$sub->where('warehouse_name', 'LIKE', "%$search%")
							->orWhere('company_name', 'LIKE', "%$search%")
							->orWhere('contact_name', 'LIKE', "%$search%")
							->orWhere('contact_number', 'LIKE', "%$search%");
					})
					->orWhereHas('orderItems', function ($sub) use ($search) {
						$sub->where('product_discription', 'LIKE', "%$search%")
							->orWhere('product_name', 'LIKE', "%$search%")
							->orWhere('amount', 'LIKE', "%$search%")
							->orWhere('quantity', 'LIKE', "%$search%");
					});
			});
		}

		$totalData = (clone $query)->count();

		$orders = $query->latest()
			->offset($start)
			->limit($limit)
			->get();

		return $this->successResponse([
			"draw" => intval($request->input('draw')),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalData,
			"counts" => $this->getStatusTotalCounts($request),
			"aaData" => $orders,
		], 'orders fetched successfully.');
	}

	private function getStatusTotalCounts(Request $request)
	{
		$user = Auth::user();
		$role = $user->role;
		$id = $user->id;
		$weightOrder = 1; // Force B2C as B2B is disabled

		$statusMappings = StatusMapping::orderBy('position')->get();

		$baseQuery = Order::query();
		if (!$user->isMasterAdmin()) {
			if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
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

		if ($weightOrder) {
			$baseQuery->where('orders.weight_order', $weightOrder);
		}

		// Filter sets matching buildOrderListingQuery
		if ($request->filled('is_shopify_order')) {
			if ($request->is_shopify_order == 1) {
				$baseQuery->where(function ($q) {
					$q->where('orders.is_shopify_order', 1)
						->orWhere('orders.platform_source', 'shopify');
				});
			} else {
				$baseQuery->where('orders.is_shopify_order', 0);
			}
		}

		if ($request->filled('is_wordpress_order')) {
			$baseQuery->where('orders.platform_source', 'wordpress');
		}

		if ($request->filled('is_portal_order')) {
			$baseQuery->where(function ($q) {
				$q->where('orders.is_shopify_order', 0)
					->orWhereNull('orders.is_shopify_order');
			})
				->where(function ($q) {
					$q->whereNull('orders.platform_source')
						->orWhereNotIn('orders.platform_source', ['shopify', 'wordpress', 'WordPress']);
				});
		}

		if ($request->filled('courier_name')) {
			$baseQuery->where('orders.courier_name', $request->courier_name);
		}

		if ($request->filled('status_courier')) {
			$baseQuery->where('orders.status_courier', $request->status_courier);
		}

		if ($request->filled('warehouse_id')) {
			$baseQuery->where('orders.warehouse_id', (int) $request->warehouse_id);
		}

		if ($request->filled('payment_type')) {
			$baseQuery->where('orders.order_type', $request->payment_type);
		}

		if ($request->filled('invoice_no')) {
			$baseQuery->where('orders.invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
		}

		if ($request->filled('min_weight') || $request->filled('max_weight')) {
			$min = $request->input('min_weight') !== null && $request->input('min_weight') !== '' ? (float) $request->min_weight : null;
			$max = $request->input('max_weight') !== null && $request->input('max_weight') !== '' ? (float) $request->max_weight : null;
			if (!is_null($min) && !is_null($max)) {
				$baseQuery->whereBetween('orders.weight', [$min, $max]);
			} elseif (!is_null($min)) {
				$baseQuery->where('orders.weight', '>=', $min);
			} elseif (!is_null($max)) {
				$baseQuery->where('orders.weight', '<=', $max);
			}
		}

		if ($request->filled('state') || $request->filled('city')) {
			$baseQuery->whereHas('customerAddress', function ($q) use ($request) {
				if ($request->filled('state')) {
					$q->where('state', $request->state);
				}
				if ($request->filled('city')) {
					$q->where('city', $request->city);
				}
			});
		}

		if ($request->filled('order_tag')) {
			$baseQuery->WhereRaw("JSON_SEARCH(orders.shopify_tags, 'one', ?) IS NOT NULL", [$request->order_tag]);
		}

		// Date filters
		if ($request->filled('date_by') && $request->filled('from_date') && $request->filled('to_date')) {
			$baseQuery->whereBetween($request->date_by, [
				\Carbon\Carbon::parse($request->from_date)->startOfDay(),
				\Carbon\Carbon::parse($request->to_date)->endOfDay()
			]);
		} elseif ($request->filled('from_date') && $request->filled('to_date')) {
			$baseQuery->whereBetween('orders.created_at', [
				\Carbon\Carbon::parse($request->from_date)->startOfDay(),
				\Carbon\Carbon::parse($request->to_date)->endOfDay()
			]);
		}

		$allTotal = (clone $baseQuery)->count();

		$rawCounts = $baseQuery
			->select('status_courier', DB::raw('count(*) as total'))
			->groupBy('status_courier')
			->get();

		$statusCounts = [];
		foreach ($rawCounts as $rc) {
			$s = strtolower(trim($rc->status_courier));
			$statusCounts[$s] = ($statusCounts[$s] ?? 0) + $rc->total;
		}

		$counts = [];
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
		}

		$counts['All'] = $allTotal;

		return $counts;
	}

	public function filterList(Request $request)
	{
		$user = Auth::user();

		// Tags with key caching (Matching Web)
		$tagsCacheKey = 'order_unique_tags_' . (($user->isAdminLevel()) ? 'all' : 'user_' . $user->id);
		$tags = Cache::remember($tagsCacheKey, 300, function () use ($user) {
			$query = Order::query()
				->whereNotNull('shopify_tags')
				->where('shopify_tags', '!=', '');

			if (!$user->isAdminLevel()) {
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

		// Warehouses
		$warehouseQuery = CourierWarehouse::query();
		if (!$user->isAdminLevel()) {
			if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
				$warehouseQuery->where(function ($q) use ($user) {
					$q->where('user_id', $user->id)
						->orWhereHas('user', function ($subQ) use ($user) {
							$subQ->where('staff_id', $user->id);
						});
				});
			} else {
				$warehouseQuery->where('user_id', $user->id);
			}
		}
		$warehouses = $warehouseQuery->get();

		// Distinct statuses (Matching Web - not filtering by weight_order)
		$statusQuery = Order::query();
		if (!$user->isAdminLevel()) {
			if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
				$statusQuery->where(function ($q) use ($user) {
					$q->where('orders.user_id', $user->id)
						->orWhereHas('user', function ($subQ) use ($user) {
							$subQ->where('staff_id', $user->id);
						});
				});
			} else {
				$statusQuery->where('orders.user_id', $user->id);
			}
		}
		$statuses = $statusQuery->distinct()->pluck('status_courier');

		// Distinct states and cities from customer addresses used in orders
		$addressQuery = Order::join('customer_addresses', 'customer_addresses.id', '=', 'orders.customer_address_id')
			->select('customer_addresses.state', 'customer_addresses.city');

		if (!$user->isAdminLevel()) {
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

		$states = (clone $addressQuery)->whereNotNull('customer_addresses.state')->where('customer_addresses.state', '!=', '')
			->distinct()->orderBy('customer_addresses.state')->pluck('customer_addresses.state');

		$cities = (clone $addressQuery)->whereNotNull('customer_addresses.city')->where('customer_addresses.city', '!=', '')
			->distinct()->orderBy('customer_addresses.city')->pluck('customer_addresses.city');

		$courierQuery = Order::distinct();
		if (!$user->isAdminLevel()) {
			if (in_array(strtolower($user->role), ['admin', 'reseller', 'staff'])) {
				$courierQuery->where(function ($q) use ($user) {
					$q->where('orders.user_id', $user->id)
						->orWhereHas('user', function ($subQ) use ($user) {
							$subQ->where('staff_id', $user->id);
						});
				});
			} else {
				$courierQuery->where('orders.user_id', $user->id);
			}
		}
		$couriers = $courierQuery->whereNotNull('courier_name')->where('courier_name', '!=', '')->pluck('courier_name');

		$paymentTypes = ['COD', 'Prepaid'];

		return $this->successResponse([
			'tags' => $tags,
			'warehouses' => $warehouses,
			'statuses' => $statuses,
			'states' => $states,
			'cities' => $cities,
			'couriers' => $couriers,
			'payment_types' => $paymentTypes,
			'counts' => $this->getStatusTotalCounts($request)
		], 'Filter data fetched successfully.');
	}

	public function orderStore(Request $request)
	{
		$request->merge(['weight_order' => 1]); // Force B2C as B2B is disabled
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
				return $this->errorResponse(OrderType::INVALID_MESSAGE);
			}
			$data['order_type'] = OrderType::sanitize($request->order_type);

			if (empty($request->reference_number)) {
				return $this->errorResponse('Reference number is required.');
			}

			if (Order::where('user_id', $user_id)->where('reference_number', $request->reference_number)->exists()) {
				return $this->errorResponse('This reference number already used');
			}

			// Use existing customer or create new one
			if ($request->filled('customer_id')) {
				$customerId = $request->customer_id;
				$customerAddressId = $request->customer_address_id;
			} else {
				// Create Customer
				$customer = Customer::create([
					'user_id' => $user_id,
					'first_name' => $request->first_name,
					'last_name' => $request->last_name,
					'email' => $request->email,
					'gst_number' => $request->gst_number ?? null,
					'mobile' => $request->mobile,
					'status' => 1,
				]);

				// Create Customer Address if available
				$customerAddress = null;
				if ($request->filled('address')) {
					$customerAddress = CustomerAddress::create([
						'customer_id' => $customer->id,
						'address' => $request->address,
						'country' => $request->country,
						'state' => $request->state,
						'city' => $request->city,
						'zip_code' => $request->zip_code,
						'status' => 1,
					]);
				}
				$customerId = $customer->id;
				$customerAddressId = $customerAddress ? $customerAddress->id : null;
			}

			$data['customer_id'] = $customerId;
			$data['customer_address_id'] = $customerAddressId;

			$data['user_id'] = $user_id;
			$data['order_prefix'] = $request->order_prefix ?? Order::generateOrderNumber($user_id);
			$data['status_courier'] = 'New';
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

			// if ($request->weight_order == 2) {
			// 	$this->b2bStore($order, $request);
			// } else {
			$this->b2cStore($order, $request);
			// }

			try {
				dispatch(function () use ($order) {
					$whatsappService = app(\App\Services\WhatsappService::class);
					$whatsappService->sendOrderNotification($order->fresh(), 'manifest_created');
				});
			} catch (\Exception $e) {
				Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
			}

			// Insert order status
			OrderStatus::insert([
				'order_id' => $order->id,
				'order_status' => 'New',
				'created_at' => now(),
				'updated_at' => now(),
			]);

			Helper::orderActivity($order->id, 'Order created.');

			DB::commit();

			return $this->successResponse($order, 'Order created successfully.');
		} catch (\Exception $e) {
			DB::rollBack();
			return $this->errorResponse($e->getMessage());
		}
	}

	private function b2cStore($order, $request)
	{
		$orderItems = [];
		if (!empty($request->product_category)) {
			foreach ($request->product_category as $key => $productCategory) {
				$amount = trim($request->amount[$key] ?? 0);
				$quantity = trim($request->quantity[$key] ?? 1);

				$orderItems[] = [
					'order_id' => $order->id,
					'product_category' => trim($productCategory),
					'product_name' => trim($request->product_name[$key] ?? null),
					'sku_number' => trim($request->sku_number[$key] ?? null),
					'hsn_number' => trim($request->hsn_number[$key] ?? null),
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
				$width += $request->width[$key] ?? 0;
				$length += $request->length[$key] ?? 0;
			}

			OrderItem::insert($orderItems);
		}

		$order->update([
			'length' => $length,
			'width' => $width,
			'height' => $height
		]);
		return;
	}

	public function orderEdit($id)
	{
		$order = Order::with('orderItems')->find($id);
		return $this->successResponse($order, 'order fetch successfully.');
	}

	public function orderUpdate(Request $request, $id)
	{
		$request->merge(['weight_order' => 1]); // Force B2C as B2B is disabled
		DB::beginTransaction();
		try {
			$user_id = Auth::id();
			$exclude = ['_token', 'product_name', 'product_category', 'sku_number', 'hsn_number', 'amount', 'quantity', 'order_image', 'invoice_document', 'first_name', 'last_name', 'email', 'gst_number', 'mobile', 'address', 'country', 'state', 'city', 'zip_code'];
			if ($request->weight_order == 2) {
				$exclude = array_merge($exclude, ['weight', 'length', 'width', 'height']);
			}
			if (empty($request->reference_number)) {
				return $this->errorResponse('Reference number is required.');
			}

			if (Order::where('user_id', $user_id)->where('reference_number', $request->reference_number)->where('id', '!=', $id)->exists()) {
				return $this->errorResponse('This reference number already used');
			}

			$data = $request->except($exclude);

			$order = Order::findOrFail($id);

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
			$data['is_fragile_item'] = $request->is_fragile_item ?? 0;
			$data['weight'] = $request->total_weight ?? $order->weight;
			$data['total_amount'] = $request->invoice_amount ?? $order->total_amount;
			$data['status'] = 1;
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

			DB::commit();

			$order = Order::with('orderItems')->find($id);

			return $this->successResponse($order, 'The order have been updated successfully.');
		} catch (\Exception $e) {
			DB::rollback();
			return $this->errorResponse('The failed to update order.please try again.');
		}
	}

	private function b2cUpdate($order, $request)
	{
		$orderItems = [];

		if (!empty($request->product_category)) {
			$ids = !empty($request->id) ? array_map('trim', $request->id) : [];
			OrderItem::where('order_id', $order->id)->whereNotIn('id', $ids)->delete();
			foreach ($request->product_category as $key => $productCategory) {
				$amount = trim($request->amount[$key] ?? 0);
				$quantity = trim($request->quantity[$key] ?? 1);

				$orderItemData = [
					'order_id' => $order->id,
					'product_category' => trim($productCategory),
					'product_name' => trim($request->product_name[$key] ?? null),
					'sku_number' => trim($request->sku_number[$key] ?? null),
					'hsn_number' => trim($request->hsn_number[$key] ?? null),
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'updated_at' => now()
				];

				// Check if order_item_id exists in the request
				if (!empty($request->id[$key])) {
					$itemId = trim($request->id[$key]);
					$orderItem = OrderItem::find($itemId);
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
		$height = $width = $length = $weight = 0;

		if (!empty($request->product_category)) {
			$ids = !empty($request->id) ? array_map('trim', $request->id) : [];
			OrderItem::where('order_id', $order->id)->whereNotIn('id', $ids)->delete();
			foreach ($request->product_category as $key => $productCategory) {
				$amount = trim($request->amount[$key] ?? 0);
				$quantity = trim($request->quantity[$key] ?? 1);

				$orderItemData = [
					'order_id' => $order->id,
					'product_category' => trim($productCategory),
					'product_name' => trim($request->product_name[$key] ?? null),
					'sku_number' => trim($request->sku_number[$key] ?? null),
					'hsn_number' => trim($request->hsn_number[$key] ?? null),
					'amount' => $amount,
					'ewaybillno' => null,
					'quantity' => $quantity,
					'updated_at' => now(),
					'dimensions' => json_encode([
						'no_of_box' => trim($request->no_of_box[$key] ?? 0),
						'weight' => trim($request->weight[$key] ?? 0),
						'length' => trim($request->length[$key] ?? 0),
						'width' => trim($request->width[$key] ?? 0),
						'height' => trim($request->height[$key] ?? 0),
					]),
				];

				// Accumulate totals
				$height += trim($request->height[$key] ?? 0);
				$width += trim($request->width[$key] ?? 0);
				$length += trim($request->length[$key] ?? 0);

				// Check if order_item_id exists in the request
				if (!empty($request->id[$key])) {
					$itemId = trim($request->id[$key]);
					$orderItem = OrderItem::find($itemId);
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
				'width' => $width,
				'height' => $height
			]);

			// Bulk insert for new items if any
			if (!empty($orderItems)) {
				OrderItem::insert($orderItems);
			}
		}
		return;
	}


	public function orderDetails($id)
	{
		$order = Order::with('customer', 'shippingCompany', 'customerAddress', 'warehouse', 'orderItems')->find($id);
		$orderActivities = OrderActivity::where('order_id', $id)->latest()->get();

		$trackingHistories = [];
		$epodData = null;
		if (!empty($order->awb_number)) {
			$shippingCompany = $order->shippingCompany ?? null;

			if (($shippingCompany && $shippingCompany->id == 1)) {
				$trackingResponse = $this->shadowfaxService->track($order->awb_number, $shippingCompany);

				if ($trackingResponse['success'] ?? false) {
					$trackingHistories = $trackingResponse['legacy_history'] ?? [];
				}
			} elseif (($shippingCompany && $shippingCompany->id == 2) && $this->urbaneBoltService) {
				$trackingResponse = $this->urbaneBoltService->trackOrderByAwbNo($order->awb_number, $shippingCompany);
				if ($trackingResponse['success'] ?? false) {
					$resp = $trackingResponse['response'];
					if (isset($resp['data']) && is_array($resp['data'])) {
						$respDetails = $resp['data'];
						$trackingHistories = $respDetails['scans'] ?? ($respDetails['shipment_track'] ?? ($respDetails['history'] ?? []));
					} else {
						$trackingHistories = $resp['scans'] ?? ($resp['shipment_track'] ?? ($resp['data'] ?? ($resp['history'] ?? [])));
					}

					if (strtolower($order->status_courier) == 'delivered') {
						$epodResponse = $this->urbaneBoltService->getEpod($order->awb_number, $shippingCompany);
						if ($epodResponse['success'] ?? false) {
							$epodData = $epodResponse['response'];
						}
					}
				}
			}
		}

		return $this->successResponse(compact('order', 'orderActivities', 'trackingHistories', 'epodData'), 'success');
	}

	public function orderCancel($id)
	{
		DB::beginTransaction();

		try {
			$order = Order::findOrFail($id);

			if ($order->status_courier === 'cancelled') {
				return $this->errorResponse('The order is already cancelled.');
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

			return $this->successResponse($order, 'The order has been successfully cancelled.');
		} catch (\Exception $e) {
			DB::rollback();
			return $this->errorResponse('Failed to cancel the order');
		}
	}

	public function orderCancelApi($orderId)
	{
		DB::beginTransaction();

		try {
			$order = Order::with(['user'])->findOrFail($orderId);

			if ($order->status_courier === 'cancelled') {
				return $this->errorResponse('The order is already cancelled.');
			}
			$user = $order->user;

			$shippingCompany = ShippingCompany::where('id', $order->shipping_company_id)
				->where('status', 1)
				->first();

			if (!$shippingCompany) {
				return $this->errorResponse('Shipment not found.');
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
					$errorMsg = $response['message'] ?? 'An error occurred during cancellation.';
					return $this->errorResponse($errorMsg);
				}

				$isQueuedCancellation = (bool) ($response['queued'] ?? false);
				$orderCancelledData = [
					'status_courier' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
					'order_cancel_date' => now(),
					'reason_cancel' => $response['message'] ?? "Shipment cancelled in Shadowfax for AWB: {$order->awb_number}.",
				];
			} elseif ($shippingCompany->id == 2) {
				if (!$this->urbaneBoltService) {
					return $this->errorResponse('UrbaneBolt service is not configured.');
				}

				$response = $this->urbaneBoltService->cancelShipmentByAwbNo($order->awb_number, $shippingCompany);

				if (!($response['success'] ?? false)) {
					$errorMsg = $response['response']['message'] ?? 'An error occurred during cancellation.';
					return $this->errorResponse($errorMsg);
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

				Billing::create([
					'user_id' => $order->user_id,
					'billing_type' => "Order",
					'billing_type_id' => $order->id,
					'transaction_type' => 'credit',
					'amount' => $walletRefund,
					'note' => 'Order canceled with AWB number: ' . $order->awb_number,
					'created_at' => now(),
					'updated_at' => now(),
				]);
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
			return $this->successResponse(
				$order,
				$isQueuedCancellation
				? 'Shadowfax accepted the cancellation request and queued it for the next facility scan.'
				: 'The order has been successfully cancelled.'
			);
		} catch (\Exception $e) {
			DB::rollback();
			return $this->errorResponse($e->getMessage());
		}
	}
	public function orderShipCharge($orderId)
	{
		$user = Auth::user();

		if ($user->role != "admin" && $user->kyc_status == 0) {
			return $this->errorResponse('Your order cannot be placed until your KYC is approved.');
		}

		$order = Order::with(['warehouse', 'user', 'customerAddress', 'orderItems'])->find($orderId);
		if (!$order) {
			return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
		}

		if (!$order->warehouse || !$order->customerAddress) {
			return $this->successResponse([
				'status' => 'missing_address',
				'order_id' => $order->id,
				'warehouse_id' => $order->warehouse_id,
				'customer_address_id' => $order->customer_address_id,
				'msg' => 'Pickup warehouse or customer address is missing.'
			], 'Incomplete order data');
		}

		if (
			empty($order->weight) || $order->weight <= 0 ||
			empty($order->length) || $order->length <= 0 ||
			empty($order->width) || $order->width <= 0 ||
			empty($order->height) || $order->height <= 0 ||
			empty($order->warehouse_id) ||
			empty($order->warehouse->zip_code) ||
			empty($order->customerAddress->zip_code)
		) {
			return $this->successResponse([
				'status' => 'lbh',
				'order_id' => $order->id,
				'length' => $order->length ?? 0,
				'width' => $order->width ?? 0,
				'height' => $order->height ?? 0,
				'weight' => $order->weight ?? 0,
				'warehouse_id' => $order->warehouse_id,
				'customer_address_id' => $order->customer_address_id,
				'pickup_code' => $order->warehouse->zip_code ?? null,
				'delivery_code' => $order->customerAddress->zip_code ?? null,
				'msg' => 'Package dimensions, warehouse, or delivery address details are missing or invalid.'
			], 'Incomplete order data');
		}

		$shippingCompanies = ShippingCompany::whereStatus(1)->get();
		$couriers = [];

		foreach ($shippingCompanies as $shippingCompany) {
			if ($shippingCompany->id == 1) {
				$requestData = new Request();
				$volumdata = [
					[
						'length' => $order->length ?? 0,
						'width' => $order->width ?? 0,
						'height' => $order->height ?? 0,
					]
				];

				$requestData->merge([
					'volumdata' => $volumdata,
					'pickup_code' => $order->warehouse->zip_code,
					'delivery_code' => $order->customerAddress->zip_code,
					'weight' => $order->weight,
					'payment_type' => $order->order_type,
					'cod_amount' => $order->cod_amount,
					'order_id' => $orderId,
					'type_of_package' => $order->weight_order,
					'orderItems' => $order->orderItems,
				]);

				$response = $this->rateCardService->getRate($user, $shippingCompany, $requestData, $isOrder = 1);
				if ($response && is_array($response)) {
					$couriers[] = $response;
				}
			} elseif ($shippingCompany->id == 2) {
				$requestData = new Request();
				$volumdata = [
					[
						'length' => $order->length ?? 0,
						'width' => $order->width ?? 0,
						'height' => $order->height ?? 0,
					]
				];

				$requestData->merge([
					'volumdata' => $volumdata,
					'pickup_code' => $order->warehouse->zip_code,
					'delivery_code' => $order->customerAddress->zip_code,
					'weight' => $order->weight,
					'payment_type' => $order->order_type,
					'cod_amount' => $order->cod_amount,
					'order_id' => $orderId,
					'type_of_package' => $order->weight_order,
					'orderItems' => $order->orderItems,
				]);

				$response = $this->rateCardService->getRate($user, $shippingCompany, $requestData, $isOrder = 1);
				if ($response && is_array($response)) {
					$couriers[] = $response;
				}
			}
		}

		$couriers = $couriers ? collect($couriers)->sortBy('total_charges')->values() : collect();

		return $this->successResponse($couriers, 'Couriers fetched successfully.');
	}

	public function orderShipNow(Request $request)
	{
		DB::beginTransaction();

		try {
			$requestData = $request->all();
			$requestData['is_bulk_shipment'] = 0;
			$response = $this->shipmentProcessor->orderShipNow($requestData);

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
			return $this->errorResponse('Something went wrong!');
		}
	}

	public function orderTrackingHistory($orderId)
	{
		$order = Order::with('shippingCompany')->find($orderId);
		$shippingCompany = $order->shippingCompany ?? null;

		if (!$shippingCompany) {
			return $this->errorResponse('Something went wrong.');
		}

		$trackingHistories = [];
		$expectedDeliveryDate = "N/A";
		$epodData = null;

		if (!empty($order->awb_number)) {
			if ($shippingCompany->id == 1) {
				$trackingResponse = $this->shadowfaxService->track($order->awb_number, $shippingCompany);

				if (!($trackingResponse['success'] ?? false)) {
					$errorMsg = $trackingResponse['message'] ?? 'An error occurred during tracking.';
					return $this->errorResponse($errorMsg);
				}

				$trackingHistories = $trackingResponse['legacy_history'] ?? [];
				$expectedDeliveryDate = $trackingResponse['expected_delivery_date'] ?? 'N/A';
			} else {
				$trackingHistories = OrderStatus::where('order_id', $order->id)
					->orderByDesc('id')
					->get();
			}
		}

		if (!$trackingHistories) {
			return $this->errorResponse('The order tracking data not found.');
		}

		return $this->successResponse(compact('order', 'trackingHistories', 'shippingCompany', 'expectedDeliveryDate', 'epodData'), 'success');
	}

	public function orderLableDownload($orderId)
	{
		try {
			$order = Order::with([
				'shippingCompany:id,logo,api_key',
				'customer:id,first_name,last_name,mobile',
				'customerAddress',
				'warehouse',
				'orderItems'
			])->findOrFail($orderId);

			$shipping = $order->shippingCompany ?? null;
			$customer = $order->customer ?? null;
			$customerAddr = $order->customerAddress ?? null;
			$products = $order->orderItems ?? null;
			$hideLabel = $order->warehouse ? $order->warehouse->label_options : [];
			$preference = LabelPreference::where('user_id', $order->user_id)->first();

			// Amazon ATS Label check
			$isAmazonATS = false;
			if (!empty($order->courier_name) && stripos($order->courier_name, 'Amazon ATS') !== false) {
				$isAmazonATS = true;
			}
			if ($shipping && stripos($shipping->name ?? '', 'Amazon ATS') !== false) {
				$isAmazonATS = true;
			}

			if ($isAmazonATS) {
				if (empty($order->awb_number)) {
					return $this->errorResponse('AWB Number is missing for Amazon ATS order.');
				}

				try {
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
						$html = '<!DOCTYPE html><html><body style="margin:0;padding:0;"><img src="' . $labelImage . '" style="width:100%;height:auto;"></body></html>';

						$paperSize = [0, 0, 288, 432];
						if ($preference && $preference->label_size === 'desktop') {
							$paperSize = 'letter';
						}

						$pdf = PDF::loadHtml($html)
							->setPaper($paperSize, 'portrait')
							->setOption('isHtml5ParserEnabled', true)
							->setOption('isRemoteEnabled', true);

						// For API, save it to public storage and return URL
						$fileName = 'order_label_' . $orderId . '_' . time() . '.pdf';
						$filePath = 'exports/' . $fileName;
						Storage::disk('public')->put($filePath, $pdf->output());

						return $this->successResponse([
							'download_url' => asset('storage/' . $filePath),
							'file_name' => $fileName
						], 'Label generated successfully.');

					} else {
						return $this->errorResponse('Amazon ATS Label not found. ' . ($result['message'] ?? ''));
					}
				} catch (\Exception $e) {
					return $this->errorResponse('Error fetching Amazon ATS Label: ' . $e->getMessage());
				}
			}

			// Generate barcodes safely
			$barcodePng = !empty($order->awb_number) ? DNS2D::getBarcodePNG($order->awb_number, 'QRCODE', 6, 6) : '';
			$orderIdBarcodePng = !empty($order->awb_number) ? DNS1D::getBarcodePNG($order->awb_number, 'C128') : '';

			$view = 'thermal-label';
			$paperSize = [0, 0, 288, 432];
			$orientation = 'portrait';

			if ($preference && $preference->label_size === 'desktop') {
				$view = 'desktop-label';
				$paperSize = 'letter';
			}

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

			$pdf = PDF::loadHtml($htmlView)
				->setPaper($paperSize, $orientation)
				->setOption('isHtml5ParserEnabled', true)
				->setOption('isRemoteEnabled', true);

			// For API, save it to public exports and return a URL instead of binary direct download
			$fileName = 'order_label_' . $orderId . '_' . time() . '.pdf';
			$filePath = 'exports/' . $fileName;
			Storage::disk('public')->put($filePath, $pdf->output());

			return $this->successResponse([
				'download_url' => asset('storage/' . $filePath),
				'file_name' => $fileName
			], 'Label generated successfully.');

		} catch (\Exception $e) {
			return $this->errorResponse('Failed to generate label: ' . $e->getMessage());
		}
	}

	public function alllabeldownload(Request $request)
	{
		error_reporting(0);
		try {
			$orderIds = $request->input('order_ids');
			if (empty($orderIds) || !is_array($orderIds)) {
				return $this->errorResponse('No orders selected');
			}

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
				return $this->errorResponse('No valid orders found');
			}

			$htmlSections = [];
			foreach ($orders as $order) {
				$shipping = $order->shippingCompany;
				$customer = $order->customer;
				$customerAddr = $order->customerAddress;
				$products = $order->orderItems;
				$preference = LabelPreference::where('user_id', $order->user_id)->first();
				$hideLabel = $order->warehouse ? $order->warehouse->label_options : [];

				$barcodePng = DNS2D::getBarcodePNG($order->awb_number, 'QRCODE', 6, 6);
				$orderIdBarcodePng = DNS1D::getBarcodePNG($order->awb_number, 'C128', 2.5, 50);

				$view = 'thermal-label';
				if ($preference && $preference->label_size === 'thermal') {
					$view = 'thermal-label';
				} elseif ($preference && $preference->label_size === 'desktop') {
					$view = 'desktop-label';
				}

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

				$htmlSections[] = trim(preg_replace('/\s+/', ' ', $html));
			}

			if (empty($htmlSections)) {
				return $this->errorResponse('All labels are empty');
			}

			$mergedHtml = implode('', $htmlSections);
			$firstOrder = $orders->first();
			$preference = LabelPreference::where('user_id', $firstOrder->user_id)->first();

			$paperSize = 'A4';
			$orientation = 'portrait';

			if ($preference && $preference->label_size === 'thermal') {
				$paperSize = [0, 0, 288, 432];
				$orientation = 'portrait';
			} elseif ($preference && $preference->label_size === 'desktop') {
				$paperSize = 'letter';
				$orientation = 'portrait';
			}

			$pdf = PDF::loadHtml($mergedHtml)
				->setPaper($paperSize, $orientation)
				->setOption('isHtml5ParserEnabled', true)
				->setOption('isRemoteEnabled', true);

			return $pdf->download('labels.pdf');
		} catch (\Exception $e) {
			return $this->errorResponse('PDF generation failed: ' . $e->getMessage());
		}
	}

	public function orderBulkStore(Request $request)
	{
		$user = Auth::user();
		$typeOfPackage = (int) $request->type_of_package;
		DB::beginTransaction(); // Begin Transaction

		try {
			// Import the Excel file
			$bulkOrder = new BulkOrder();
			$file = $request->file('bulk_excel');

			// Specify reader type as fallback if needed, but standard import should work if file is valid
			Excel::import($bulkOrder, $file);

			// Check if rows exist after removing the header
			if (empty($bulkOrder->rows)) {
				return $this->errorResponse('No valid data found in the Excel file.');
			}

			foreach ($bulkOrder->rows as $row) {
				$row = $this->normalizeBulkImportRow((array) $row, $typeOfPackage);
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

			DB::commit();
			return $this->successResponse(['type_of_package' => $typeOfPackage], 'The order has been successfully added.');
		} catch (\Exception $e) {
			DB::rollback();
			return $this->errorResponse($e->getMessage());
		}
	}

	private function normalizeBulkImportRow(array $row, int $typeOfPackage): array
	{
		$row = array_values($row);
		$expectedWithShippingMode = $typeOfPackage === 2 ? 30 : 27;
		$expectedWithoutShippingMode = $typeOfPackage === 2 ? 29 : 26;

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
			'user_id' => $user->id,
			'first_name' => $row[4] ?? '',
			'last_name' => $row[5] ?? '',
			'email' => $row[6] ?? null,
			'gst_number' => $row[7] ?? null,
			'mobile' => $row[8] ?? null,
			'status' => 1,
		]);

		// Create Customer Address if available
		$customerAddress = null;
		if ($row[9] ?? '') {
			$customerAddress = CustomerAddress::create([
				'customer_id' => $customer->id,
				'address' => $row[9],
				'zip_code' => $row[10],
				'city' => $row[11],
				'state' => $row[12],
				'country' => $row[13],
				'status' => 1,
			]);
		}

		// Assuming the column order matches the Excel data structure
		$data = [
			'order_prefix' => Order::generateOrderNumber($user->id),
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
			'user_id' => $user->id,
			'first_name' => $row[5] ?? '',
			'last_name' => $row[6] ?? '',
			'email' => $row[7] ?? null,
			'gst_number' => $row[8] ?? null,
			'mobile' => $row[9] ?? null,
			'status' => 1,
		]);

		// Create Customer Address if available
		$customerAddress = null;
		if ($row[10] ?? '') {
			$customerAddress = CustomerAddress::create([
				'customer_id' => $customer->id,
				'address' => $row[10],
				'zip_code' => $row[11],
				'city' => $row[12],
				'state' => $row[13],
				'country' => $row[14],
				'status' => 1,
			]);
		}

		// Assuming the column order matches the Excel data structure
		$data = [
			'order_prefix' => Order::generateOrderNumber($user->id),
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
				$width += $widthBox[$key] ?? 0;
				$height += $heightBox[$key] ?? 0;
			}

			OrderItem::insert($orderItems);
		}

		$order->update([
			'weight' => $weight,
			'length' => $length,
			'width' => $width,
			'height' => $height
		]);
	}

	public function codRemittance(Request $request)
	{
		$start = $request->input("offset", 0);
		$limit = $request->input("limit", 10);
		$search = $request->input('search');

		$user = Auth::user();
		$userId = $user->id;

		// Base query - Get Delivered Remittances (Matching Web)
		$query = SellerRemittance::where('user_id', $userId);

		// Filters matching Web
		if ($request->filled('settlement_status')) {
			$query->where('settlement_status', $request->settlement_status);
		}

		// Search filter
		if (!empty($search)) {
			$query->where(function ($q) use ($search) {
				$q->where('remittance_id', 'LIKE', "%{$search}%")
					->orWhere('settlement_status', 'LIKE', "%{$search}%")
					->orWhere('bank_account_no', 'LIKE', "%{$search}%")
					->orWhere('utr', 'LIKE', "%{$search}%")
					->orWhere('seller_payment_mode', 'LIKE', "%{$search}%");
			});
		}

		$totalData = $query->count();

		$remittances = $query->orderBy('created_at', 'desc')
			->offset($start)
			->limit($limit)
			->get();

		$data = [];
		foreach ($remittances as $remittance) {
			$data[] = [
				'id' => $remittance->id,
				'cycle_date' => \Carbon\Carbon::parse($remittance->cycle_from_date)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($remittance->cycle_to_date)->format('d M Y'),
				'remittance_id' => $remittance->remittance_id,
				'payment_scheduled_date' => $remittance->payment_scheduled_date ? \Carbon\Carbon::parse($remittance->payment_scheduled_date)->format('d M Y') : '-',
				'payment_date' => $remittance->payment_date ? \Carbon\Carbon::parse($remittance->payment_date)->format('d M Y') : '-',
				'delivery_count' => $remittance->total_delivery_count ?? 0,
				'cod_amount' => $remittance->total_cod_remittance_amount,
				'expenses' => $remittance->total_expenses,
				'transferable_amount' => $remittance->amount_transferred_to_seller,
				'next_cycle_of_wallet' => $remittance->amount_transferred_next_cycle_or_wallet,
				'settlement_status' => $remittance->settlement_status,
				'bank_account_no' => $remittance->bank_account_no ?? '-',
				'utr' => $remittance->utr ?? '-',
				'seller_payment_mode' => $remittance->seller_payment_mode ?? '-',
			];
		}

		return $this->successResponse([
			'remittances' => $data,
			'total_records' => $totalData
		], 'list fetched successfully.');
	}

	public function downloadRemittanceExcel($id)
	{
		try {
			$remittance = SellerRemittance::findOrFail($id);
			$tab = request('tab', 'cod');

			$fileName = 'remittance_' . $remittance->remittance_id . '_' . date('Y-m-d') . '.xlsx';

			// Using the same anonymous class logic as Web Controller
			return Excel::download(
				new class ($remittance, $tab) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
				private $remittance;
				private $tab;

				public function __construct($remittance, $tab)
				{
					$this->remittance = $remittance;
					$this->tab = $tab;
				}

				public function collection()
				{
					$data = collect();

					if ($this->tab === 'cod') {
						// COD Revenue data
						$orders = Order::where('remittance_reference_id', $this->remittance->id)
							->where('order_type', 'cod')
							->whereIn('status_courier', ['delivered', 'Delivered'])
							->get();

						// Get user instance to access user-specific settings
						$user = User::findOrFail($this->remittance->user_id);

						// Get user-specific transfer percentage or use global config
						// Fallback to 100 if method doesn't exist in model
						$transferPercentage = method_exists($user, 'getCodWalletTransferValue')
						? $user->getCodWalletTransferValue()
						: 100;

						// Get total COD wallet expenses for this cycle
						$totalCodBalanceExpenses = CodWalletExpense::where('user_id', $this->remittance->user_id)
							->whereBetween('processed_at', [$this->remittance->cycle_from_date, $this->remittance->cycle_to_date])
							->where('status', 'completed')
							->sum('cod_balance_used');

						// First pass: Calculate percentage-based amounts for each order
						$orderExpenses = [];
						$totalPercentageAmount = 0;

						foreach ($orders as $order) {
							$invoiceAmount = $order->invoice_amount ?? 0;
							$transferAmount = ($invoiceAmount * $transferPercentage) / 100;

							$orderExpenses[] = [
							'order' => $order,
							'invoice_amount' => $invoiceAmount,
							'transfer_amount' => $transferAmount,
							'cod_balance_expense' => $transferAmount
							];

							$totalPercentageAmount += $transferAmount;
						}

						// Calculate remaining balance after percentage distribution
						$remainingBalance = $totalCodBalanceExpenses - $totalPercentageAmount;

						// Distribute remaining balance across orders from the end
						if ($remainingBalance != 0 && count($orderExpenses) > 0) {
							for ($i = count($orderExpenses) - 1; $i >= 0 && $remainingBalance > 0; $i--) {
								$orderExpenses[$i]['cod_balance_expense'] += $remainingBalance;
								$remainingBalance = 0;
							}
							// Over-distribution check (negative balance)
							if ($remainingBalance < 0) {
								$remainingBalance = abs($remainingBalance);
								for ($i = 0; $i < count($orderExpenses) && $remainingBalance > 0; $i++) {
									if ($orderExpenses[$i]['cod_balance_expense'] >= $remainingBalance) {
										$orderExpenses[$i]['cod_balance_expense'] -= $remainingBalance;
										$remainingBalance = 0;
									} else {
										$remainingBalance -= $orderExpenses[$i]['cod_balance_expense'];
										$orderExpenses[$i]['cod_balance_expense'] = 0;
									}
								}
							}
						}

						foreach ($orderExpenses as $orderData) {
							$order = $orderData['order'];
							$data->push([
								'order_id' => $order->order_prefix ?? $order->id,
								'order_created_date' => \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
								'awb_number' => $order->awb_number ?? '-',
								'partner_name' => $order->courier_name ?? '-',
								'delivery_date' => $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '-',
								'amount' => number_format($orderData['invoice_amount'], 2),
								'cod_balance_expenses' => number_format($orderData['cod_balance_expense'], 2),
								'commission' => number_format($order->remittance_amount ?? 0, 2),
								'status' => strtoupper($order->status_courier),
							]);
						}
					} elseif ($this->tab === 'shopdesk') {
						// Service Fee data
						$orders = Order::where('remittance_reference_id', $this->remittance->id)
							->whereNotNull('remittance_amount')
							->where('remittance_amount', '>', 0)
							->get();

						foreach ($orders as $order) {
							$data->push([
								'settlement_date' => $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '-',
								'order_id' => $order->order_prefix ?? $order->id,
								'order_created_date' => \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
								'awb_number' => $order->awb_number ?? '-',
								'transaction_type' => 'Spend',
								'transaction_details' => config('setting.company_name') . ' Service Fee',
								'amount' => $order->invoice_amount ?? 0,
								'commission' => $order->remittance_amount ?? 0,
								'status' => strtoupper($order->status_courier),
							]);
						}
					}

					return $data;
				}

				public function headings(): array
				{
					if ($this->tab === 'cod') {
						return [
						'Order ID',
						'Order Created Date',
						'AWB Number',
						'Partner Name',
						'Delivery Date',
						'Amount',
						'cod Balance Used',
						'Commission',
						'Status',
						];
					} else {
						return [
						'Settlement Date',
						'Order ID',
						'Order Created Date',
						'AWB Number',
						'Transaction Type',
						'Transaction Details',
						'Amount',
						'Commission',
						'Status',
						];
					}
				}
				},
				$fileName
			);
		} catch (\Exception $e) {
			return $this->errorResponse('failed to download excel: ' . $e->getMessage());
		}
	}

	public function codPayout(Request $request)
	{
		$start = $request->input("offset", 0);
		$length = $request->input("limit", 10);
		$searchValue = $request->input("search");

		$user = auth()->user();
		$role = $user->role;
		$userId = $user->id;

		$query = CodVoucher::with(['user', 'codVoucherOrders']);

		// Filters
		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		} else {
			if ($role === "user") {
				$query->where('user_id', $userId);
			}
		}

		if ($request->filled('from_date') && $request->filled('to_date') && $request->filled('date_type')) {
			$query->whereBetween($request->date_type, [$request->from_date, $request->to_date]);
		} elseif ($request->filled('from_date') && $request->filled('date_type')) {
			$query->whereDate($request->date_type, $request->from_date);
		} elseif ($request->filled('to_date') && $request->filled('date_type')) {
			$query->whereDate($request->date_type, $request->to_date);
		}

		if ($request->filled('voucher_status')) {
			$query->where('voucher_status', $request->voucher_status);
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

		$vouchers = $query->offset($start)
			->limit($length)
			->orderBy('id', 'desc')
			->get();

		return $this->successResponse($vouchers, 'list fetched successfully.');
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
			return $this->successResponse($codPayout, 'Cod Payout successfully.');
		} catch (\Exception $e) {
			DB::rollBack();
			return $this->errorResponse('An error occurred. Please try again.');
		}
	}
	public function searchByAwb(Request $request)
	{
		$user = Auth::user();
		$isAdmin = $user->isAdminLevel();

		try {
			$query = $request->get('query');
			if (empty($query)) {
				return $this->successResponse(['results' => []], 'Query is empty');
			}

			$orders = Order::query()
				->where(function ($q) use ($query) {
					$q->where('order_prefix', 'LIKE', "%{$query}%")
						->orWhere('awb_number', 'LIKE', "%{$query}%");
				})
				->when(!$isAdmin, function ($q) use ($user) {
					$q->where('user_id', $user->id);
				})
				->with(['customer:id,first_name,last_name'])
				->select([
					'id',
					'order_prefix',
					'awb_number',
					'status_courier',
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
						'status_courier' => $order->status_courier,
						'customer_name' => $order->customer
							? $order->customer->first_name . ' ' . $order->customer->last_name
							: 'N/A',
						'created_at' => $order->created_at->format('Y-m-d H:i:s'),
						'weight_order' => $order->weight_order
					];
				});

			return $this->successResponse($orders, 'order fetched successfully.');
		} catch (\Exception $e) {
			return $this->errorResponse('An error occurred while searching orders');
		}
	}

	public function downloadBulkSample(\Illuminate\Http\Request $request)
	{
		$type = strtolower($request->query('type', 'b2c'));

		if ($type === 'b2b') {
			$path = public_path('assets/b2b_sample.xlsx');
			$filename = 'b2b_sample.xlsx';
		} elseif ($type === 'b2c') {
			$path = public_path('assets/b2c_sample.xlsx');
			$filename = 'b2c_sample.xlsx';
		} else {
			return $this->errorResponse('Invalid sample type.', 400);
		}

		if (!file_exists($path)) {
			return $this->errorResponse('Sample file not found.', 404);
		}

		return $this->successResponse([
			'url' => url('assets/' . $filename)
		], 'Sample Excel file URL fetched successfully.');
	}
}
