<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Models\Order;
	use App\Models\OrderItem;
	use App\Models\Billing;
	use App\Models\Invoice;
	use App\Models\User;
	use App\Models\ShippingCompany;
	use App\Exports\PendingStarOrderExport;
	use DB,Auth,File,Helper;
	use Illuminate\Support\Facades\Http;
	use App\Exports\OrdersExport;
	use Maatwebsite\Excel\Facades\Excel;
	use Barryvdh\DomPDF\Facade\Pdf;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Schema;
	use App\Exports\BillingInvoiceExport;
	use App\Services\BillingInvoiceService;
	use Carbon\Carbon;

	class ReportController extends Controller
	{
		private function riskChargeSql(string $table = 'orders'): string
		{
			return "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.api_response1, '$.risk_charge')) AS DECIMAL(10,2)), 0)";
		}

		private function isCommissionOverheadName(string $name): bool
		{
			$name = strtolower(trim($name));

			return $name !== '' && (
				str_contains($name, 'commission')
				|| str_contains($name, 'user extra charge')
				|| str_contains($name, 'markup')
			);
		}

		private function extractShippingChargeBreakdown($order): array
		{
			$pricingMeta = is_array($order->api_response1 ?? null) ? $order->api_response1 : [];
			$finalShipping = round((float) ($order->shipping_charge ?? ($pricingMeta['wallet_debit_amount'] ?? $pricingMeta['total_charges'] ?? 0)), 2);
			$taxAmount = round((float) ($pricingMeta['tax'] ?? $order->tax ?? 0), 2);
			$codCharges = round((float) ($pricingMeta['cod_charges'] ?? $order->cod_charges ?? 0), 2);
			$baseFreight = round((float) ($pricingMeta['base_freight'] ?? $pricingMeta['freight_charges'] ?? 0), 2);
			$storedAdditionalCharges = round((float) ($order->percentage_amount ?? 0), 2);
			$overheadDetails = is_array($pricingMeta['overhead_details'] ?? null) ? $pricingMeta['overhead_details'] : [];

			$nonCommissionCharges = [];
			$nonCommissionTotal = 0.0;

			foreach ($overheadDetails as $detail) {
				$name = trim((string) ($detail['name'] ?? ''));
				$value = round((float) ($detail['value'] ?? 0), 2);

				if (!empty($detail['is_info']) || $value <= 0 || $this->isCommissionOverheadName($name)) {
					continue;
				}

				$nonCommissionCharges[] = [
					'label' => $name !== '' ? $name : 'Other Charge',
					'amount' => $value,
				];
				$nonCommissionTotal += $value;
			}

			$commissionCharge = round(max(0, $storedAdditionalCharges - $nonCommissionTotal), 2);
			if ($commissionCharge == 0.0 && $storedAdditionalCharges > 0 && empty($nonCommissionCharges)) {
				$commissionCharge = $storedAdditionalCharges;
			}

			$aggregatorCharge = round(max(0, $finalShipping - $commissionCharge), 2);

			if ($baseFreight <= 0 && $aggregatorCharge > 0) {
				$baseFreight = round(max(0, $aggregatorCharge - $taxAmount - $codCharges - $nonCommissionTotal), 2);
			}

			$aggregatorParts = [];
			if ($baseFreight > 0) {
				$aggregatorParts[] = ['label' => 'Base Freight', 'amount' => $baseFreight];
			}
			if ($codCharges > 0) {
				$aggregatorParts[] = ['label' => 'COD Charge', 'amount' => $codCharges];
			}
			foreach ($nonCommissionCharges as $charge) {
				$aggregatorParts[] = $charge;
			}
			if ($taxAmount > 0) {
				$aggregatorParts[] = ['label' => 'GST', 'amount' => $taxAmount];
			}

			return [
				'shipping_charge_total' => $finalShipping,
				'stored_additional_charges' => $storedAdditionalCharges,
				'commission_charge' => $commissionCharge,
				'aggregator_charge' => $aggregatorCharge,
				'aggregator_parts' => $aggregatorParts,
			];
		}

		private function formatBreakdownParts(array $parts): string
		{
			$formatted = [];

			foreach ($parts as $part) {
				$label = trim((string) ($part['label'] ?? ''));
				$amount = round((float) ($part['amount'] ?? 0), 2);

				if ($label === '' || $amount <= 0) {
					continue;
				}

				$formatted[] = $label . ': ' . number_format($amount, 2);
			}

			return implode(' | ', $formatted);
		}

		private function ownedShippingCompanyIds($user): array
		{
			if (!$user || $user->role !== 'user' || (int) $user->is_shipping_company !== 1) {
				return [];
			}

			return ShippingCompany::where('user_id', $user->id)->pluck('id')->all();
		}

		private function invoiceHasShippingCompanyColumn(): bool
		{
			static $hasColumn;

			if ($hasColumn === null) {
				$hasColumn = Schema::hasColumn('invoices', 'shipping_company_id');
			}

			return $hasColumn;
		}

		private function parseMixedAwbNote(?string $note): array
		{
			$note = trim((string) $note);
			if ($note === '') {
				return [];
			}

			// Pattern to extract Order ID, AWB, Courier and Details from the note
			$pattern = '/^\s*(?<order_id>\d+)\s*\|\s*Awb\s*Number:\s*(?<awb_number>[^|]+?)\s*\|\s*Courier:\s*(?<courier_name>[^|]+?)\s*\|\s*Product\s*Details:\s*(?<details>.+)\s*$/i';
			if (!preg_match($pattern, $note, $matches)) {
				return [];
			}

			$details = trim($matches['details'] ?? '');
			$productAmount = null;
			$noOfBox = null;
			$skuNumber = null;

			// Extract Amount, No Of Box, Sku from details
			if (preg_match('/Amount:\s*([0-9]+(?:\.[0-9]+)?)/i', $details, $amountMatch)) {
				$productAmount = $amountMatch[1];
			}
			if (preg_match('/No\s*Of\s*Box:\s*([0-9]+)/i', $details, $boxMatch)) {
				$noOfBox = $boxMatch[1];
			}
			if (preg_match('/Sku:\s*(.*)$/i', $details, $skuMatch)) {
				$skuNumber = trim($skuMatch[1]) !== '' ? trim($skuMatch[1]) : '-';
			}

			return [
				'order_id' => trim($matches['order_id'] ?? ''),
				'awb_number' => trim($matches['awb_number'] ?? ''),
				'courier_name' => trim($matches['courier_name'] ?? ''),
				'product_amount' => $productAmount,
				'no_of_box' => $noOfBox,
				'sku_number' => $skuNumber,
			];
		}

		private function authUser()
		{
			return Auth::user();
		}

		private function isDataScopedViewer(): bool
		{
			$user = $this->authUser();
			return $user ? $user->isDataScopedRole() : false;
		}

		private function scopedUsersQuery()
		{
			if ($this->isDataScopedViewer()) {
				return User::where('id', $this->authUser()->id)->orderBy('name');
			}

			return User::where('role', 'user')->where('kyc_status', '!=', 0)->orderBy('name');
		}

		public function __construct()
		{
			$this->middleware('auth');
		}

		public function index(Request $request)
		{
			$user = Auth::user();
			$users = $this->scopedUsersQuery()->get();
			$status = $request->query('status', 'New');

			// Get active shipping companies (except Shipmozo)
			$companies = ShippingCompany::where('status', 1)->where('id', '!=', 2)->pluck('name')->toArray();

			// Get sub-couriers of Shipmozo (matching dashboard logic)
			$subs = Order::where('shipping_company_id', 2)
				->whereNotNull('courier_name')
				->where('courier_name', '!=', '')
				->distinct()
				->pluck('courier_name')
				->toArray();

			$couriersList = collect(array_merge($companies, $subs))->unique()->sort()->values()->all();
			$couriers = array_combine($couriersList, $couriersList);
			$defaultFrom = $request->query('fromdate', now()->copy()->startOfMonth()->toDateString());
			$defaultTo = $request->query('todate', now()->copy()->endOfMonth()->toDateString());
			$selectedCourier = $request->query('courier_name', '');
			$packageType = $request->query('package_type', '');

			return view('report.order', compact('status', 'users', 'couriers', 'defaultFrom', 'defaultTo', 'selectedCourier', 'packageType'));
		}

    	public function reportOrderAjax(Request $request)
		{
			$draw = $request->post('draw');
			$start = $request->post("start");
			$limit = $request->post("length"); // Rows per page

			$columnIndex = $request->post('order')[0]['column'] ?? 0;
			$orderColumn = $request->post('columns')[$columnIndex]['data'] ?? 'id';
			$dir = $request->post('order')[0]['dir'] ?? 'asc';
			$search = $request->input('search') ?? $request->input('search.value') ?? '';

			// Fix incorrect assignment (`=` instead of `==`)
			if ($orderColumn == 'action') {
				$orderColumn = 'id';
			}

			$currentDate = now()->toDateString();
			$userId = Auth::id();

			// Base query with necessary joins
			$query = Order::with(['user', 'warehouse', 'customer', 'customerAddress', 'orderItems']);

			// Apply filters
			if ($this->isDataScopedViewer()) {
				$query->where('user_id', $userId);
			}

			if ($this->isDataScopedViewer()) {
				$query->where('user_id', $userId);
			} elseif ($request->filled('user_id')) {
				$query->where('user_id', $request->user_id);
			}

			if ($request->filled('courier_name')) {
				$filter = $request->courier_name;
				$company = ShippingCompany::where('name', $filter)->first();
				if ($company) {
					$query->where(function($q) use ($company, $filter) {
						$q->where('orders.shipping_company_id', $company->id)
						  ->orWhere('orders.courier_name', $filter);
					});
				} else {
					$query->where('orders.courier_name', $filter);
				}
			}

			if ($request->filled('package_type')) {
				$query->where('orders.weight_order', (int) $request->package_type);
			}

			if ($request->filled('fromdate') && $request->filled('todate')) {
				$query->whereBetween('order_date', [$request->fromdate, $request->todate]);
			} elseif ($request->filled('fromdate')) {
				$query->whereDate('order_date', $request->fromdate);
			} elseif ($request->filled('todate')) {
				$query->whereDate('order_date', $request->todate);
			}else {
				$query->whereDate('order_date', $currentDate);
			}

			// Apply search filter
			if ($search) {
				$query->where(function ($q) use ($search) {
					$q->where('created_at', 'LIKE', "%{$search}%")
					  ->orWhereHas('customer', function($q) use ($search){
						  $q->where('first_name', 'LIKE', "%{$search}%")
						  ->orwhere('last_name', 'LIKE', "%{$search}%")
						  ->orwhere('mobile', 'LIKE', "%{$search}%");
						})
					  ->orWhereHas('user', function($q) use ($search){
						  $q->where('name', 'LIKE', "%{$search}%")
						  ->orwhere('email', 'LIKE', "%{$search}%")
						  ->orwhere('company_name', 'LIKE', "%{$search}%")
						  ->orwhere('mobile', 'LIKE', "%{$search}%");
						})
					  ->orWhereHas('warehouse', function($q) use ($search){
						  $q->where('contact_name', 'LIKE', "%{$search}%")
						  ->orwhere('contact_number', 'LIKE', "%{$search}%")
						  ->orwhere('warehouse_name', 'LIKE', "%{$search}%");
						})
					  ->orWhere('awb_number', 'LIKE', "%{$search}%")
					  ->orWhere('courier_name', 'LIKE', "%{$search}%")
					  ->orWhere('status_courier', 'LIKE', "%{$search}%")
					  ->orWhere('id', 'LIKE', "%{$search}%")
					  ->orWhere('order_prefix', 'LIKE', "%{$search}%");
				});
			}

			// Get total count before applying pagination
			$totalData = $query->count();

			// Apply ordering, pagination, and fetch results
			$orders = $query->orderBy('orders.' . $orderColumn, $dir)
							->offset($start)
							->limit($limit)
							->get();

			// Format data
			$data = [];
			$i = $start + 1;
			foreach ($orders as $order)
			{
				$totalOrderTypeAmount =  $order->order_type == "cod" ? $order->cod_amount : $order->invoice_amount;
				$totalOrderTypeLabel =  $order->order_type == "cod" ? 'Cod Amount' : 'Invoice Amount';

				$customeraddr = $order->customerAddress;
				$data[] = [
					'id' => $i++,
					'seller_details' => "<div class='main-cont1-2'><p>" .
					(isset($order->user) ? "{$order->user->name} ({$order->user->company_name})" : "N/A") .
					"</p><p>" . ($order->user->email ?? "N/A") . "</p><p>" . ($order->user->mobile ?? "N/A") . "</p></div>",

					'order_details' => $this->orderShipmentDetailHtml($order),

					'customer_details' => "<div class='main-cont1-2'>
						<p>" . (isset($order->customer) ? "{$order->customer->first_name} {$order->customer->last_name}" : "N/A") . "</p>
						<p>" . ($order->customer->email ?? "N/A") . "</p>
						<p>" . ($order->customer->mobile ?? "N/A") . "</p>
					</div>",

					'customer_address' => "<p style='word-wrap:break-word; white-space:normal;'>" . ($customeraddr->address ?? "N/A") . "</p>",
					'customer_city' => $customeraddr->city ?? 'N/A',
					'customer_state' => $customeraddr->state ?? 'N/A',
					'customer_country' => $customeraddr->country ?? 'N/A',
					'customer_pincode' => $customeraddr->zip_code ?? 'N/A',


					'package_details' => $this->orderPackageDetailHtml($order),

					'total_amount' => "<div class='main-cont1-2'>
					<p class='" . strtolower($order->order_type) . "'>{$order->order_type}</p>
					<p>{$totalOrderTypeLabel} : {$totalOrderTypeAmount}</p>
					</div>",

					'pickup_address' => "<div class='tooltip'>" .
					(isset($order->warehouse) ? strtoupper($order->warehouse->warehouse_name) : "N/A") .
					"<span class='tooltiptext'><b>" . ($order->warehouse->contact_name ?? "N/A") .
					"</b><br><b>Address</b>: " . ($order->warehouse->address ?? "N/A") . " " . ($order->warehouse->city ?? "") .
					"<br>" . ($order->warehouse->state ?? "") . "-" . ($order->warehouse->zip_code ?? "") .
					"<br>" . ($order->warehouse->contact_number ?? "") . "</span></div>",

					'status_courier' => "<p class='prepaid'>{$order->status_courier}</p>",
					'created_at' => $order->order_date,
				];
			}

			// Response
			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $totalData,
				"iTotalDisplayRecords" => count($data),
				"aaData" => $data
			]);
		}

		function reportOrderExport(Request $request)
		{
			return Excel::download(new OrdersExport($request), 'orders.xlsx');
		}

		function orderShipmentDetailHtml($order)
		{
			// Prepare order items JSON
			$items = $order->orderItems->map(fn($item) => [
				'product_category' => $item->product_category,
				'product_name' => $item->product_name,
				'sku_number' => $item->sku_number,
				'hsn_number' => $item->hsn_number,
				'amount' => $item->amount,
				'quantity' => $item->quantity,
			]);

			$jsonItems = htmlspecialchars($items->toJson(), ENT_QUOTES, 'UTF-8'); // safely encode JSON

			$html = "<div class='main-cont1-1'>";

			$html .= "<div class='checkbox checkbox-purple'>
						Order Prefix/LR No:
						<a href='" . url("order/details/{$order->id}") . "'>#{$order->order_prefix}</a>
					  </div>";

			$html .= "<div class='checkbox checkbox-purple'>
						Courier: " . ($order->courier_name ?? 'N/A') . "
					  </div>";

			if ($order->awb_number) {
				$html .= "<div class='checkbox checkbox-purple'>
							AWB Number:
							<a href='" . url("order/details/{$order->id}") . "'>#{$order->awb_number}</a>
						  </div>";
			}

			// View Products button with JSON
			$html .= "<span style='padding-left:0'>
						<a href='javascript:;' class='show-details-btn' data-order='{$jsonItems}'     >
							View Products
						</a>
					  </span>";

			$html .= "</div>";

			return $html;
		}

		public static function orderPackageDetailHtml($order)
		{
			$orderItems = $order->orderItems;

			/* B2B logic disabled
			if ($order->weight_order == 2) {
				$productDetails = $orderItems->map(function ($item) {
					return "
						<p class='text-white'>
							No Of Box: ".($item->dimensions['no_of_box'] ?? '')."<br>
							Weight Per Box: ".($item->dimensions['weight'] ?? '')."<br>
							Length: ".($item->dimensions['length'] ?? '')."<br>
							Width: ".($item->dimensions['width'] ?? '')."<br>
							Height: ".($item->dimensions['height'] ?? '')."
						</p>
					";
				})->implode('<hr>');
			} else {
            */
				$productDetails = "
					<p class='text-white'>
						Weight In Kg: ".($order->weight ?? '')."<br>
						Length: ".($order->length ?? '')."<br>
						Width: ".($order->width ?? '')."<br>
						Height: ".($order->height ?? '')."
					</p>
				";
			// }

			$quantitiy = $orderItems->sum('quantity');
			$totalWeightInKg = $order->weight ?? 0;

			$output = '';
			$output .= "<div class='main-cont1-1'>
				<div class='checkbox checkbox-purple'>
					Quantitiy: {$quantitiy}
				</div>
				<div class='checkbox checkbox-purple'>
					Weight In Kg: {$totalWeightInKg}
				</div>";

				$output .= "<div class='tooltip'>View Package Details<span class='tooltiptext'><b>" . $productDetails. "</span></div>
			</div>";

			return $output;
		}

		public function shippingCharge()
		{
			if (auth()->user()->normalizedRole() === 'user') {
				abort(403, 'Permission denied');
			}
			$users = $this->scopedUsersQuery()->get();
			$companies = ShippingCompany::where('status', 1)->where('id', '!=', 2)->orderBy('name')->get();

			$shippingCompanies = [];
			foreach ($companies as $comp) {
				$shippingCompanies[] = $comp;
			}
			// Add Shipmozo sub-couriers
			$subs = Order::where('shipping_company_id', 2)
				->whereNotNull('courier_name')
				->where('courier_name', '!=', '')
				->distinct()
				->pluck('courier_name');
			foreach ($subs as $sub) {
				$shippingCompanies[] = (object)['id' => $sub, 'name' => $sub];
			}

			return view('report.shipping-charge', compact('users', 'shippingCompanies'));
		}

		public function shippingChargeAjax(Request $request)
		{
			$draw = (int) $request->post('draw', 1);
			$start = (int) $request->post('start', 0);
			$limit = (int) $request->post('length', 25);

			$orderArr = $request->post('order', []);
			$columnsArr = $request->post('columns', []);

			$columnIndex = $orderArr[0]['column'] ?? 0;
			$orderKey = $columnsArr[$columnIndex]['data'] ?? 'id';
			$dir = ($orderArr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

			// Map DataTables columns -> real DB columns or expressions
			$orderMap = [
				'id' => 'orders.id',
				'order_date' => 'orders.order_date',
				'delivery_date' => 'orders.delivery_date',
				'seller_details' => 'users.name',
				'order_details' => 'orders.id',
				'shippings' => 'orders.courier_name',
				'aggregator_charges' => 'orders.shipping_charge',
				'commission_charges' => 'orders.percentage_amount',
				'shipping_charges' => 'orders.shipping_charge',
				'profit' => 'orders.percentage_amount',
				'profit_percent' => 'orders.percentage_amount', 
			];
			$orderByCol = $orderMap[$orderKey] ?? 'orders.id';

			$authUserId = Auth::id();

			// ---- Base query with join for sorting ----
			$base = Order::query()
				->select('orders.*')
				->leftJoin('users', 'orders.user_id', '=', 'users.id')
				->with(['orderItems', 'user', 'shippingCompany'])
				->whereNotNull('orders.shipping_company_id')
				->whereNotNull('orders.awb_number')
				->whereNotIn('orders.status_courier', ['cancelled', 'Cancelled']);

			// If role needs scoping
			if ($this->isDataScopedViewer()) {
				$base->where('orders.user_id', $authUserId);
			}

			// For recordsTotal (unfiltered except auth scope)
			$recordsTotal = (clone $base)->count();

			// ---- Apply filters (UI filters) ----
			$filtered = (clone $base);

			if ($this->isDataScopedViewer()) {
				$filtered->where('orders.user_id', $authUserId);
			} elseif ($request->filled('user_id')) {
				$filtered->where('orders.user_id', $request->user_id);
			}

			if ($request->filled('shipping_company_id')) {
				$filter = $request->shipping_company_id;
				if (is_numeric($filter)) {
					$filtered->where('orders.shipping_company_id', $filter);
				} else {
					$filtered->where('orders.courier_name', $filter);
				}
			}
			$filterType = $request->filter_type;
			if ($request->filled('fromdate') && $request->filled('todate') && $request->filled('filter_type'))
			{
				$filtered->whereBetween($filterType, [$request->fromdate, $request->todate]);
			} elseif ($request->filled('fromdate')) {
				$filtered->whereDate($filterType, $request->fromdate);
			} elseif ($request->filled('todate')) {
				$filtered->whereDate($filterType, $request->todate);
			}

			// Search (simple)
			if ($request->filled('search')) {
				$search = $request->input('search');
				$filtered->where(function ($q) use ($search) {
					$q->where('orders.created_at', 'like', "%{$search}%")
						->orWhere('orders.awb_number', 'like', "%{$search}%")
						->orWhere('orders.order_date', 'like', "%{$search}%")
						->orWhere('orders.courier_name', 'like', "%{$search}%")
						->orWhere('orders.status_courier', 'like', "%{$search}%")
						->orWhere('orders.id', 'like', "%{$search}%")
						->orWhere('orders.order_prefix', 'like', "%{$search}%")
						->orWhereHas('user', function ($u) use ($search) {
							$u->where('name', 'like', "%{$search}%")
								->orWhere('email', 'like', "%{$search}%")
								->orWhere('company_name', 'like', "%{$search}%")
								->orWhere('mobile', 'like', "%{$search}%");
						});
				});
			}

			// ---- Counts after filters ----
			$recordsFiltered = (clone $filtered)->count();

			// ---- Totals on filtered set ----
			$totalShipping = 0.0;
			$totalProfit = 0.0;
			foreach ((clone $filtered)
				->select('orders.shipping_charge', 'orders.percentage_amount', 'orders.api_response1', 'orders.cod_charges', 'orders.tax')
				->cursor() as $summaryOrder) {
				$summary = $this->extractShippingChargeBreakdown($summaryOrder);
				$totalShipping += (float) $summary['shipping_charge_total'];
				$totalProfit += (float) $summary['commission_charge'];
			}

			$totalShipping = round($totalShipping, 2);
			$totalProfit = round($totalProfit, 2);
			$totalProfitPercent = $totalShipping > 0 ? ($totalProfit / $totalShipping) * 100 : 0;

			// ---- Fetch page data ----
			$rows = (clone $filtered)
				->orderBy($orderByCol, $dir)
				->offset($start)
				->limit($limit)
				->get();

			// ---- Build DataTables rows ----
			$data = [];
			$i = $start + 1;
			foreach ($rows as $order) {
				$breakdown = $this->extractShippingChargeBreakdown($order);
				$shipping_charge_total = (float) $breakdown['shipping_charge_total'];
				$aggregator_charges = (float) $breakdown['aggregator_charge'];
				$commission = (float) $breakdown['commission_charge'];
				$profit = $commission;
				$profit_percent = $shipping_charge_total > 0 ? ($profit / $shipping_charge_total) * 100 : 0;

				$aggregatorChargeHtml = "<div class='main-cont1-2'><p>" . number_format($aggregator_charges, 2) . "</p></div>";

				$commissionHtml = "<div class='main-cont1-2'><p>" . number_format($commission, 2) . "</p></div>";

				$shipping_charge_html = "<div class='main-cont1-2'><p>" . number_format($shipping_charge_total, 2) . "</p></div>";

				$data[] = [
					'id' => $i,
					'order_date' => $order->order_date,
					'delivery_date' => $order->delivery_date ?? 'N/A',
					'seller_details' => "<div class='main-cont1-2'><p>" .
					(isset($order->user) ? e($order->user->name) . ' (' . e($order->user->company_name) . ')' : 'N/A') .
						"</p><p>" . e($order->user->email ?? 'N/A') . "</p><p>" . e($order->user->mobile ?? 'N/A') . "</p></div>",
					'order_details' => $this->orderShipmentDetailHtml($order),
					'shippings' => "<div class='main-cont1-2'><p>" . e($order->courier_name) . "</p></div>",
					'aggregator_charges' => $aggregatorChargeHtml,
					'commission_charges' => $commissionHtml,
					'shipping_charges' => $shipping_charge_html,
					'profit' => "<div class='main-cont1-2'><p>" . number_format($profit, 2) . "</p></div>",
					'profit_percent' => "<div class='main-cont1-2'><p>" . number_format($profit_percent, 2) . "%</p></div>",
				];
				$i++;
			}

			return response()->json([
				'draw' => $draw,
				'recordsTotal' => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data' => $data,             // modern key
				'totals' => [
						'total_shipping' => $totalShipping, // numeric
						'total_profit' => $totalProfit,   // numeric
						'total_profit_percent' => $totalProfitPercent,
					],
			]);
		}

		public function passbookUser()
		{
			$users = $this->scopedUsersQuery()->get();
			return view('report.passbook-user', compact('users'));
		}

		public function passbookUserAjax(Request $request)
		{
			$draw = $request->post('draw');
			$start = $request->post("start");
			$limit = $request->post("length");

			$columnIndex = $request->post('order')[0]['column'];
			$orderColumn = $request->post('columns')[$columnIndex]['data'];
			$orderDir = $request->post('order')[0]['dir'];

			$orderColumn = ($orderColumn == 'action') ? 'created_at' : $orderColumn;
			if ($orderColumn == 'balance') {
				$orderColumn = 'wallet_amount';
			}

			$query = User::query()
				->when($this->isDataScopedViewer(), function ($q) {
					return $q->where('id', Auth::id());
				}, function ($q) {
					return $q->where('role', 'user')->where('kyc_status', '!=', 0);
				})
				->when(!$this->isDataScopedViewer() && $request->user_id, function ($q) use ($request) {
					return $q->where('id', $request->user_id);
				});

			if ($request->filled('search')) {
				$search = $request->input('search');
				$query->where(function ($q) use ($search) {
					$q->where('name', 'like', "%{$search}%")
						->orWhere('email', 'like', "%{$search}%");
				});
			}

			// Get all filtered records
			$allRecords = $query->orderBy($orderColumn, $orderDir)->get();

			// Calculate total wallet amount
			$totalWallet = $allRecords->sum('wallet_amount');

			$data = [];
			foreach ($allRecords as $key => $value) {
			// Create URL for passbook
				$viewUrl = url('passbook', $value->id);
				$data[] = [
					'id' => $start + $key + 1,
					'name' => $value->name,
					'email' => $value->email,
					'balance' => number_format($value->wallet_amount, 2),
					'action' => '<a href="' . $viewUrl . '" class="btn btn-sm btn-success">Passbook</a>'
				];
			}

			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $allRecords->count(),
				"iTotalDisplayRecords" => $allRecords->count(),
				"aaData" => $data,
				"total_wallet_amount" => number_format($totalWallet, 2) // ← Added here
			]);
		}


		public function passbookReport($userId)
		{
			if ($this->isDataScopedViewer() && (int) Auth::id() !== (int) $userId) {
				abort(403, 'Permission denied');
			}
			$user = User::find($userId);
			$defaultFrom = now()->copy()->startOfMonth()->toDateString();
			$defaultTo = now()->copy()->endOfMonth()->toDateString();
			return view('report.passbook', compact('user', 'defaultFrom', 'defaultTo'));
		}
		public function passbookReportAjax(Request $request)
		{
			$draw = $request->post('draw');
			$start = $request->post("start");
			$limit = $request->post("length");

			$columnIndex = $request->post('order')[0]['column'];
			$orderColumn = $request->post('columns')[$columnIndex]['data'];
			$orderDir = $request->post('order')[0]['dir'];

			$orderColumn = ($orderColumn == 'action') ? 'created_at' : $orderColumn;

			$scopedUserId = $this->isDataScopedViewer() ? Auth::id() : null;

			// Base query for filtered records (applies user_id and date range if provided)
			$query = Billing::with(['order', 'userWallet'])
				->when($scopedUserId, function ($q) use ($scopedUserId) {
					return $q->where('user_id', $scopedUserId);
				})
				->when(!$scopedUserId && $request->user_id, function ($q) use ($request) {
					return $q->where('user_id', $request->user_id);
				})
				->when($request->fromdate && $request->todate, function ($q) use ($request) {
					$from = Carbon::parse($request->fromdate)->startOfDay();
					$to = Carbon::parse($request->todate)->endOfDay();
					return $q->whereBetween('billings.created_at', [$from, $to]);
				})
				// If only fromdate provided - filter that single day
				->when($request->fromdate && !$request->todate, function ($q) use ($request) {
					$from = Carbon::parse($request->fromdate)->startOfDay();
					$to = Carbon::parse($request->fromdate)->endOfDay();
					return $q->whereBetween('billings.created_at', [$from, $to]);
				})
				// If only todate provided - everything up to that day inclusive
				->when($request->todate && !$request->fromdate, function ($q) use ($request) {
					$to = Carbon::parse($request->todate)->endOfDay();
					return $q->where('billings.created_at', '<=', $to);
				});

			// --- Opening balance: sum of all transactions BEFORE fromdate (if provided)
			$openingBalance = 0.00;
			if ($request->fromdate) {
				$before = Carbon::parse($request->fromdate)->startOfDay();

				$openingQuery = Billing::query()
					->when($scopedUserId, function ($q) use ($scopedUserId) {
						return $q->where('user_id', $scopedUserId);
					})
					->when(!$scopedUserId && $request->user_id, function ($q) use ($request) {
						return $q->where('user_id', $request->user_id);
					})
					->where('billings.created_at', '<', $before);

				// Sum credits as +amount, debits as -amount
				$openingBalance = $openingQuery->get()->reduce(function ($carry, $item) {
					return $carry + (($item->transaction_type === 'credit') ? $item->amount : -$item->amount);
				}, 0.00);
			} else {
				// If no fromdate provided, opening balance is zero (or you can compute all prior if desired)
				$openingBalance = 0.00;
			}

			// Get filtered records in ASC order (oldest → newest) to compute running balances cleanly
			$filteredAsc = $query->orderBy("billings.created_at", 'asc')->get();

			// Build running balances array
			$running = $openingBalance;
			$runningBalances = []; // same index as $filteredAsc
			foreach ($filteredAsc as $rec) {
				$running += ($rec->transaction_type === 'credit') ? $rec->amount : -$rec->amount;
				$runningBalances[] = $running;
			}

			// Closing balance = running after last filtered record (or opening if none)
			$closingBalance = $running;

			// Attach running balances to the records for easier sorting
			$recordsWithBalance = [];
			foreach ($filteredAsc as $key => $rec) {
				$rec->calculation_balance = $runningBalances[$key];
				$recordsWithBalance[] = $rec;
			}

			// Apply sorting requested by DataTables
			$filteredSorted = collect($recordsWithBalance)->sort(function($a, $b) use ($orderColumn, $orderDir) {
				if ($orderColumn === 'balance') {
					$valA = (float)$a->calculation_balance;
					$valB = (float)$b->calculation_balance;
				} elseif ($orderColumn === 'credit' || $orderColumn === 'debit') {
					$valA = (float)$a->amount;
					$valB = (float)$b->amount;
				} elseif ($orderColumn === 'id') {
					$valA = (int)$a->id;
					$valB = (int)$b->id;
				} else {
					$valA = $a->{$orderColumn} ?? '';
					$valB = $b->{$orderColumn} ?? '';
				}

				if ($orderDir === 'asc') {
					return $valA <=> $valB;
				} else {
					return $valB <=> $valA;
				}
			})->values();

			// Apply pagination slice
			$pagedRecords = $filteredSorted->slice($start, $limit > 0 ? $limit : $filteredSorted->count())->values();

			// Prepare data rows (matching $pagedRecords order)
			$data = [];
			foreach ($pagedRecords as $key => $value) {

				$isOrderBilling = ($value->billing_type === 'Order');
				$billingTypeHtml = $isOrderBilling
					? '<div class="main-cont1-1">
						<div class="checkbox checkbox-purple">Order Id:
							<a href="' . url('order/details/' . $value->billing_type_id) . '" target="_blank"> #' . $value->billing_type_id . ' </a>
						</div>
						<div class="checkbox checkbox-purple">Courier Name:
							<a href="' . url('order/details/' . $value->billing_type_id) . '" target="_blank"> ' . ($value->order->courier_name ?? 'N/A') . ' </a>
						</div>
						<div class="checkbox checkbox-purple">Awb Number:
							<a href="' . url('order/details/' . $value->billing_type_id) . '" target="_blank"> ' . ($value->order->awb_number ?? 'N/A') . ' </a>
						</div>
					</div>'
					: "<div class='main-cont1-2'><p>{$value->billing_type}</p></div>";

				$parsed = $this->parseMixedAwbNote($value->note);
				$orderId = $parsed['order_id'] ?? '-';
				$awbNo = $parsed['awb_number'] ?? '-';
				$cName = $parsed['courier_name'] ?? '-';
				$pAmt = $parsed['product_amount'] ?? '-';
				$nBox = $parsed['no_of_box'] ?? '-';
				$skuNumber = $parsed['sku_number'] ?? '-';

				// If it's an Order billing but parsing failed, try fetching from relationship
				if ($isOrderBilling && $orderId === '-') {
					$orderId = $value->billing_type_id;
					$awbNo = $value->order->awb_number ?? '-';
					$cName = $value->order->courier_name ?? '-';
				}

				$displayNote = $value->note;
				$breakdown = $this->parseChargeBreakdownNote($value->note);
				$noteHtml = '';

				if ($breakdown) {
					$noteRaw = explode(' | ', $value->note)[0];
					$displayNote = $noteRaw;
					$noteHtml = "<div class='main-cont1-2'>
						<p>{$noteRaw}</p>
						<p><b>Base:</b> ₹" . number_format($breakdown['base'], 2) . "</p>
						<p><b>GST:</b> ₹" . number_format($breakdown['gst'], 2) . "</p>
					</div>";
				} else {
					$noteHtml = "<div class='main-cont1-2'><p>" . e($value->userWallet->note ?? $value->note) . "</p></div>";
				}

				$debitHtml = ($value->transaction_type == 'debit')
					? '<span class="text-danger">' . number_format($value->amount, 2) . '</span>'
					: '-';
				
				$creditHtml = ($value->transaction_type == 'credit')
					? '<span class="text-success">' . number_format($value->amount, 2) . '</span>'
					: '-';

				$data[] = [
					'id' => $start + $key + 1,
					'billing_type' => $billingTypeHtml,
					'order_id' => "<div class='main-cont1-2'><p>{$orderId}</p></div>",
					'awb_number' => "<div class='main-cont1-2'><p>{$awbNo}</p></div>",
					'courier_name' => "<div class='main-cont1-2'><p>{$cName}</p></div>",
					'product_amount' => "<div class='main-cont1-2'><p>{$pAmt}</p></div>",
					'no_of_box' => "<div class='main-cont1-2'><p>{$nBox}</p></div>",
					'sku_number' => "<div class='main-cont1-2'><p>{$skuNumber}</p></div>",
					'debit' => $debitHtml,
					'credit' => $creditHtml,
					'balance' => number_format($value->calculation_balance, 2),
					'note' => $noteHtml,
					'created_at' => "<div class='main-cont1-2'><p>" . date('Y M d | h:i A', strtotime($value->created_at)) . "</p></div>",
				];
			}

			if($openingBalance == 0)
			{
				$firstBilling = Billing::where('user_id', $request->user_id)->orderBy('id')->first();
				$openingBalance = $firstBilling ? $firstBilling->amount : 0;
			}
			return response()->json([
				"draw"                 => intval($draw),
				"recordsTotal"         => $filteredAsc->count(),
				"recordsFiltered"      => $filteredAsc->count(),
				"data"                 => $data,
				"aaData"               => $data,
				"opening_balance"      => number_format($openingBalance, 2),
				"closing_balance"      => number_format($closingBalance, 2),
			]);
		}

		public function userWiseReport()
		{
			if (auth()->user()->normalizedRole() === 'user') {
				abort(403, 'Permission denied');
			}

			$users = $this->scopedUsersQuery()->get(['id', 'name', 'company_name']);

			// Get active shipping companies (except Shipmozo)
			$companies = ShippingCompany::where('status', 1)->where('id', '!=', 2)->pluck('name')->toArray();

			$subs = Order::query()
				->where('shipping_company_id', 2)
				->whereNotNull('courier_name')
				->where('courier_name', '!=', '')
				->distinct()
				->pluck('courier_name')
				->toArray();

			$couriers = collect(array_merge($companies, $subs))->unique()->sort()->values();

			$statuses = Order::query()
				->whereNotNull('status_courier')
				->where('status_courier', '!=', '')
				->distinct()
				->orderBy('status_courier')
				->pluck('status_courier');

			$defaultFrom = now()->copy()->startOfMonth()->toDateString();
			$defaultTo = now()->copy()->endOfMonth()->toDateString();

			return view('report.user-wise', compact('users', 'couriers', 'statuses', 'defaultFrom', 'defaultTo'));
		}

		public function userWiseReportAjax(Request $request)
		{
			$draw = (int) $request->post('draw', 1);
			$start = (int) $request->post('start', 0);
			$limit = (int) $request->post('length', 25);

			$orderArr = $request->post('order', []);
			$columnsArr = $request->post('columns', []);
			$columnIndex = (int) ($orderArr[0]['column'] ?? 0);
			$orderKey = $columnsArr[$columnIndex]['data'] ?? 'id';
			$dir = ($orderArr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

			$base = Order::query()
				->join('users', 'users.id', '=', 'orders.user_id');

			if (!$this->isDataScopedViewer()) {
				$base->where('users.role', 'user');
			}

			if ($this->isDataScopedViewer()) {
				$base->where('orders.user_id', Auth::id());
			} elseif ($request->filled('user_id')) {
				$base->where('orders.user_id', $request->user_id);
			}

			if ($request->filled('courier_name')) {
				$filter = $request->courier_name;
				$company = ShippingCompany::where('name', $filter)->first();
				if ($company) {
					$base->where(function($q) use ($company, $filter) {
						$q->where('orders.shipping_company_id', $company->id)
						  ->orWhere('orders.courier_name', $filter);
					});
				} else {
					$base->where('orders.courier_name', $filter);
				}
			}

			$statusCourier = $request->input('status_courier') ?: $request->input('status');
			if (!empty($statusCourier)) {
				$base->where('orders.status_courier', $statusCourier);
			}

			if ($request->filled('fromdate') && $request->filled('todate')) {
				$base->whereBetween('orders.order_date', [$request->fromdate, $request->todate]);
			} elseif ($request->filled('fromdate')) {
				$base->whereDate('orders.order_date', $request->fromdate);
			} elseif ($request->filled('todate')) {
				$base->whereDate('orders.order_date', $request->todate);
			}

			$searchData = $request->input('search');
			$search = is_array($searchData) ? ($searchData['value'] ?? '') : ($searchData ?? '');
			$search = trim((string) $search);
			if ($search !== '') {
				$base->where(function ($q) use ($search) {
					$q->where('users.name', 'like', "%{$search}%")
						->orWhere('users.company_name', 'like', "%{$search}%")
						->orWhere('users.email', 'like', "%{$search}%")
						->orWhere('users.mobile', 'like', "%{$search}%")
						->orWhere('orders.courier_name', 'like', "%{$search}%")
						->orWhere('orders.status_courier', 'like', "%{$search}%")
						->orWhere('orders.awb_number', 'like', "%{$search}%")
						->orWhere('orders.order_prefix', 'like', "%{$search}%");
				});
			}

			$totals = (clone $base)
				->selectRaw('
					COUNT(orders.id) as total_orders,
					SUM(COALESCE(orders.shipping_charge, 0) - ' . $this->riskChargeSql() . ') as total_wallet_cut,
					SUM(COALESCE(orders.percentage_amount, 0)) as total_profit
				')
				->first();

			$grouped = (clone $base)
				->selectRaw('
					orders.user_id,
					users.name as user_name,
					users.company_name,
					users.email,
					users.mobile,
					COUNT(orders.id) as total_orders,
					SUM(COALESCE(orders.shipping_charge, 0) - ' . $this->riskChargeSql() . ') as total_wallet_cut,
					SUM(COALESCE(orders.percentage_amount, 0)) as total_profit
				')
				->groupBy('orders.user_id', 'users.name', 'users.company_name', 'users.email', 'users.mobile');

			$totalBase = Order::query()
				->join('users', 'users.id', '=', 'orders.user_id')
				->where('users.role', 'user')
				->selectRaw('orders.user_id')
				->groupBy('orders.user_id');

			$recordsTotal = DB::query()->fromSub($totalBase, 'total_base')->count();
			$recordsFiltered = DB::query()->fromSub((clone $grouped), 'filtered_base')->count();

			$columnMap = [
				'id'                => 'orders.user_id',
				'user_name'         => 'users.name',
				'company_name'      => 'users.company_name',
				'email'             => 'users.email',
				'mobile'            => 'users.mobile',
				'total_orders'      => 'total_orders',
				'wallet_cut'        => 'total_wallet_cut',
				'profit'            => 'total_profit',
				'aggregator_charge' => DB::raw('(SUM(COALESCE(orders.shipping_charge, 0)) - SUM(COALESCE(orders.percentage_amount, 0)))'),
			];

			$orderBy = $columnMap[$orderKey] ?? 'orders.user_id';
			$grouped->orderBy($orderBy, $dir);

			$rows = (clone $grouped)
				->offset($start)
				->limit($limit)
				->get();

			$data = [];
			$i = $start + 1;
			foreach ($rows as $row) {
				$walletCut = (float) ($row->total_wallet_cut ?? 0);
				$profit = (float) ($row->total_profit ?? 0);
				$aggregatorCharge = $walletCut - $profit;

				$data[] = [
					'id'               => $i++,
					'user_name'        => $row->user_name ?? 'N/A',
					'company_name'     => $row->company_name ?? '-',
					'email'            => $row->email ?? '-',
					'mobile'           => $row->mobile ?? '-',
					'total_orders'     => (int) ($row->total_orders ?? 0),
					'wallet_cut'       => number_format($walletCut, 2),
					'aggregator_charge'=> number_format($aggregatorCharge, 2),
					'profit'           => number_format($profit, 2),
				];
			}

			$totalWalletCut = (float) ($totals->total_wallet_cut ?? 0);
			$totalProfit = (float) ($totals->total_profit ?? 0);

			return response()->json([
				'draw'            => $draw,
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data'            => $data,
				'totals'          => [
					'total_orders'           => (int) ($totals->total_orders ?? 0),
					'total_wallet_cut'       => $totalWalletCut,
					'total_profit'           => $totalProfit,
					'total_aggregator_charge'=> $totalWalletCut - $totalProfit,
				],
			]);
		}

		public function billingInvoice()
		{
			$users = User::where('role', 'user')->orderBy('name', 'asc')->get();
			return view('report.invoice', compact('users'));
		}

		public function billingInvoiceAjax(Request $request)
		{
			$draw = $request->post('draw');
			$start = $request->post("start");
			$limit = $request->post("length");

			$columnIndex_arr = $request->post('order');
			$columnName_arr = $request->post('columns');
			$order_arr = $request->post('order');
			$search = $request->post('search') ?? $request->post('search.value') ?? '';

			$columnIndex = $columnIndex_arr[0]['column'];
			$orderKey = $columnName_arr[$columnIndex]['data'] ?? 'created_at';
			$dir = $order_arr[0]['dir'] ?? 'desc';

			$columnMap = [
				'id' => 'invoices.id',
				'invoice_number' => 'invoices.invoice_number',
				'invoice_date' => 'invoices.invoice_date',
				'total_amount' => 'invoices.total_amount',
				'user_name' => 'users.name',
				'shipping_company_name' => 'invoices.id',
				'invoice_period' => 'invoices.invoice_period',
			];
			$order = $columnMap[$orderKey] ?? 'invoices.created_at';

			$user = auth()->user();
			$ownedCompanyIds = $this->ownedShippingCompanyIds($user);
			$hasShippingCompanyColumn = $this->invoiceHasShippingCompanyColumn();

			$query = Invoice::query()
				->select('invoices.*')
				->with(['user'])
				->leftJoin('users', 'users.id', '=', 'invoices.user_id');

			if ($hasShippingCompanyColumn) {
				$columnMap['shipping_company_name'] = 'shipping_companies.name';
				$order = $columnMap[$orderKey] ?? 'invoices.created_at';
				$query->with('shippingCompany')
					->leftJoin('shipping_companies', 'shipping_companies.id', '=', 'invoices.shipping_company_id');
			}

			if ($hasShippingCompanyColumn && !empty($ownedCompanyIds)) {
				$query->whereIn('invoices.shipping_company_id', $ownedCompanyIds);
			} elseif ($user->role === 'user' && (int) $user->is_shipping_company === 1) {
				$query->whereRaw('1 = 0');
			} elseif ($user->isDataScopedRole()) {
				$query->where('invoices.user_id', $user->id);
			}

			if ($user->isDataScopedRole()) {
				$query->where('invoices.user_id', $user->id);
			} elseif ($request->filled('user_id')) {
				$query->where('invoices.user_id', $request->user_id);
			}

			if ($request->filled('year') && $request->filled('month') && is_numeric($request->year) && is_numeric($request->month)) {
				$query->whereYear('invoice_date', (int) $request->year)
					  ->whereMonth('invoice_date', (int) $request->month);
			}

			$totalData = $query->count();

			// Apply search filter
			if (!empty($search)) {
				$query->where(function ($q) use ($search) {
					$q->whereHas('user', function ($sub) use ($search) {
						$sub->where('name', 'LIKE', "%{$search}%")
							->orWhere('email', 'LIKE', "%{$search}%")
							->orWhere('company_name', 'LIKE', "%{$search}%");
					})
					->orWhere('invoice_number', 'LIKE', "%{$search}%")
					->orWhere('invoice_period', 'LIKE', "%{$search}%")
					->orWhere('invoice_date', 'LIKE', "%{$search}%")
					->orWhere('total_amount', 'LIKE', "%{$search}%");
				});
			}

			$totalFiltered = $query->count();

			$invoices = $query
				->orderBy($order, $dir)
				->offset($start)
				->limit($limit)
				->get();

			$data = [];
			$index = $start + 1;
			$billingInvoiceService = app(BillingInvoiceService::class);

			foreach ($invoices as $invoice) {
				$summary = $billingInvoiceService->summarizeInvoice($invoice, true);

				$data[] = [
					'id' => $index++,
					'invoice_state' => $summary['invoice_state'],
					'invoice_number' => $invoice->invoice_number,
					'invoice_period' => $invoice->invoice_period,
					'invoice_date' => $invoice->invoice_date,
					'total_amount' => number_format($summary['total_amount'], 2),
					'user_name' => trim(($invoice->user->name ?? '') . ' ' . ($invoice->user->company_name ?? '')),
					'shipping_company_name' => $hasShippingCompanyColumn ? ($invoice->shippingCompany->name ?? 'N/A') : 'N/A',
					'action' => '
						<a href="' . route('report.billing-invoice.pdf', $invoice->id) . '" class="btn btn-sm btn-danger" target="_blank">PDF</a>
						<a href="' . route('report.billing-invoice.excel', $invoice->id) . '" class="btn btn-sm btn-success" target="_blank">Excel</a>'
				];
			}

			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $totalData,
				"iTotalDisplayRecords" => $totalFiltered,
				"aaData" => $data,
			]);
		}

		public function billingInvoicePdf($invoiceId)
		{
			$authUser = auth()->user();
			$ownedCompanyIds = $this->ownedShippingCompanyIds($authUser);
			$hasShippingCompanyColumn = $this->invoiceHasShippingCompanyColumn();
			$query = Invoice::with(['user', 'user.userKyc']);
			if ($hasShippingCompanyColumn) {
				$query->with('shippingCompany');
			}

			if ($hasShippingCompanyColumn && !empty($ownedCompanyIds)) {
				$query->whereIn('shipping_company_id', $ownedCompanyIds);
			} elseif ($authUser->role === 'user' && (int) $authUser->is_shipping_company === 1) {
				$query->whereRaw('1 = 0');
			} elseif ($authUser->isDataScopedRole()) {
				$query->where('user_id', $authUser->id);
			}

			$invoiceDetail = $query->findOrFail($invoiceId);
			$summary = app(BillingInvoiceService::class)->summarizeInvoice($invoiceDetail, true);
			$user = $invoiceDetail->user;
			$headerLogo = config('setting.header_logo');
			$logoPath = null;

			if (!empty($headerLogo)) {
				$candidatePath = base_path('storage/settings/' . $headerLogo);
				if (is_file($candidatePath)) {
					$logoPath = $candidatePath;
				}
			}

			$shippingUserName = trim((string) ($user->company_name ?: $user->name ?: ''));
			$shippingUserAddress = collect([
				$user->address ?? '',
				$user->city ?? '',
				$user->state ?? '',
				$user->country ?? '',
			])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');
			$shippingUserGst = optional($user->userKyc)->gst ?? 'N/A';
			$shippingUserMobile = $user->mobile ?? '';

			$authUser = auth()->user();
			$isShippingCompanyLogin = (int) ($authUser->is_shipping_company ?? 0) === 1;

			$threePl = [
				'name' => 'KSHIP EXPRESS',
				'address' => 'RING ROAD SURAT GUJARAT 395003',
				'gst' => '24AFKFS090154',
				'mobile' => '',
				'gstNumber' => '24AFKFS090154',
			];
			$shippingCompanyBlock = [
				'name' => $shippingUserName ?: 'N/A',
				'address' => $shippingUserAddress ?: 'N/A',
				'gst' => $shippingUserGst,
				'mobile' => $shippingUserMobile,
				'gstNumber' => $shippingUserGst,
			];

			if ($isShippingCompanyLogin) {
				$invoiceFrom = [
					'name' => $shippingCompanyBlock['name'],
					'address' => $shippingCompanyBlock['address'],
					'gst' => $shippingCompanyBlock['gst'],
				];
				$invoiceTo = [
					'name' => $threePl['name'],
					'address' => $threePl['address'],
					'mobile' => $threePl['mobile'],
					'gstNumber' => $threePl['gstNumber'],
				];
			} else {
				$invoiceFrom = [
					'name' => $threePl['name'],
					'address' => $threePl['address'],
					'gst' => $threePl['gst'],
				];
				$invoiceTo = [
					'name' => $shippingCompanyBlock['name'],
					'address' => $shippingCompanyBlock['address'],
					'mobile' => $shippingCompanyBlock['mobile'],
					'gstNumber' => $shippingCompanyBlock['gstNumber'],
				];
			}

			$invoice = [
				'number' => $invoiceDetail->invoice_number,
				'date' => \Carbon\Carbon::parse($invoiceDetail->invoice_date)->format('d M Y'),
				'period' => \Carbon\Carbon::parse($invoiceDetail->month_start)->format('d') . ' to ' . \Carbon\Carbon::parse($invoiceDetail->month_end)->format('d M Y'),
				'from' => $invoiceFrom,
				'to' => $invoiceTo,
				'summary' => [
					'order_count' => $summary['order_count'],
					'forward' => $summary['forward_amount'],
					'cod' => $summary['cod_amount'],
					'rto' => $summary['rto_amount'],
					'taxable' => $summary['base_amount'],
				],
				'charges' => [
					'shipping' => round($summary['base_amount'], 2),
					'cgst' => round($summary['cgst_amount'], 2),
					'sgst' => round($summary['sgst_amount'], 2),
					'igst' => round($summary['igst_amount'], 2),
				],
			];

			$total = $summary['total_amount'];
			$pdf = Pdf::loadView('report.pdf-invoice', compact('invoice', 'total', 'logoPath'))->setPaper('a4', 'portrait');
			$sanitizedNumber = str_replace(['/', '\\'], '-', $invoiceDetail->invoice_number);
			$filename = 'invoice_' . $sanitizedNumber . '.pdf';

			return $pdf->download($filename);
		}


		public function billingInvoiceExcel($invoiceId)
		{
			$authUser = auth()->user();
			$ownedCompanyIds = $this->ownedShippingCompanyIds($authUser);
			$hasShippingCompanyColumn = $this->invoiceHasShippingCompanyColumn();
			$query = Invoice::with(['orders.user']);
			if ($hasShippingCompanyColumn) {
				$query->with('shippingCompany');
			}

			if ($hasShippingCompanyColumn && !empty($ownedCompanyIds)) {
				$query->whereIn('shipping_company_id', $ownedCompanyIds);
			} elseif ($authUser->role === 'user' && (int) $authUser->is_shipping_company === 1) {
				$query->whereRaw('1 = 0');
			} elseif ($authUser->isDataScopedRole()) {
				$query->where('user_id', $authUser->id);
			}

			$invoice = $query->findOrFail($invoiceId);
			// Replace slashes to make a valid filename
			$sanitizedNumber = str_replace('/', '-', $invoice->invoice_number);
			$filename = 'invoice_' . $sanitizedNumber . '.xlsx';

			return Excel::download(new BillingInvoiceExport($invoice), $filename);
		}

		private function parseChargeBreakdownNote($note)
		{
			if (!is_string($note) || trim($note) === '') {
				return null;
			}
			if (preg_match('/\[Breakdown: Base: ([\d.]+), GST: ([\d.]+)\]/', $note, $matches)) {
				return [
					'base' => $matches[1],
					'gst'  => $matches[2]
				];
			}
			return null;
		}

		public function sellerOrdersReport(Request $request)
		{
			$user = auth()->user();
			$isAdminViewer = $user && !$user->isDataScopedRole();
			$isShippingCompanyUser = $user && $user->normalizedRole() === 'user' && (int) $user->is_shipping_company === 1;

			if (!$isAdminViewer && !$isShippingCompanyUser) {
				abort(403, 'Permission denied');
			}
			$defaultFrom = now()->copy()->startOfMonth()->toDateString();
			$defaultTo = now()->copy()->toDateString();

			if ($isAdminViewer) {
				return view('report.shipping-company-summary', compact('defaultFrom', 'defaultTo'));
			}

			return view('report.seller-orders', compact('defaultFrom', 'defaultTo'));
		}

		public function sellerOrdersReportAjax(Request $request)
		{
			$user = auth()->user();
			$isAdminViewer = $user && !$user->isDataScopedRole();
			$isShippingCompanyUser = $user && $user->normalizedRole() === 'user' && (int) $user->is_shipping_company === 1;

			if (!$isAdminViewer && !$isShippingCompanyUser) {
				return response()->json([
					"draw" => (int) $request->post('draw', 1),
					"iTotalRecords" => 0,
					"iTotalDisplayRecords" => 0,
					"aaData" => [],
				], 403);
			}

			$shippedStatuses = ['manifested', 'delivered', 'ready to ship'];
			$draw = $request->post('draw');
			$start = $request->post("start");
			$limit = $request->post("length");

			$columnIndex = $request->post('order')[0]['column'] ?? 0;
			$orderColumn = $request->post('columns')[$columnIndex]['data'] ?? 'id';
			$dir = $request->post('order')[0]['dir'] ?? 'desc';
			$search = $request->input('search') ?? $request->input('search.value') ?? '';

			if ($orderColumn == 'action') {
				$orderColumn = 'id';
			}

			if ($isAdminViewer) {
				$query = ShippingCompany::where('status', 1);

				if ($search) {
					$query->where('name', 'LIKE', "%{$search}%");
				}

				$totalData = $query->count();

				$companies = $query->orderBy('name', $dir === 'desc' ? 'desc' : 'asc')
								 ->offset($start)
								 ->limit($limit)
								 ->get();

				$data = [];
				foreach ($companies as $key => $company) {
					$orderQuery = Order::where('shipping_company_id', $company->id);

					if ($request->filled('fromdate') && $request->filled('todate')) {
						$orderQuery->whereBetween('order_date', [$request->fromdate, $request->todate]);
					} elseif ($request->filled('fromdate')) {
						$orderQuery->whereDate('order_date', $request->fromdate);
					} elseif ($request->filled('todate')) {
						$orderQuery->whereDate('order_date', $request->todate);
					}

					$totalOrders = (clone $orderQuery)->count();
					$shippedOrders = (clone $orderQuery)->whereIn(DB::raw('LOWER(status_courier)'), $shippedStatuses)->count();
					$deliveredOrders = (clone $orderQuery)->where('status_courier', 'delivered')->count();
					$cancelledOrders = (clone $orderQuery)->whereIn('status_courier', ['cancelled', 'Cancelled'])->count();
					$b2bOrders = (clone $orderQuery)->where('weight_order', 2)->count();
					$b2cOrders = (clone $orderQuery)->where(function ($q) {
						$q->whereNull('weight_order')->orWhere('weight_order', '!=', 2);
					})->count();
					$viewAllUrl = route('report.order', [
						'courier_name' => $company->name,
						'fromdate' => $request->fromdate,
						'todate' => $request->todate,
					]);
					$viewB2bUrl = route('report.order', [
						'courier_name' => $company->name,
						'fromdate' => $request->fromdate,
						'todate' => $request->todate,
						'package_type' => 2,
					]);
					$viewB2cUrl = route('report.order', [
						'courier_name' => $company->name,
						'fromdate' => $request->fromdate,
						'todate' => $request->todate,
						'package_type' => 1,
					]);

					$data[] = [
						'id' => $start + $key + 1,
						'name' => $company->name,
						'total_orders' => $totalOrders,
						'shipped_orders' => $shippedOrders,
						'delivered_orders' => $deliveredOrders,
						'cancelled_orders' => $cancelledOrders,
						'b2b_orders' => '<a href="' . $viewB2bUrl . '">' . $b2bOrders . '</a>',
						'b2c_orders' => '<a href="' . $viewB2cUrl . '">' . $b2cOrders . '</a>',
						'view_orders' => '<a href="' . $viewAllUrl . '" class="btn btn-table">View Orders</a>',
					];
				}

				return response()->json([
					"draw" => intval($draw),
					"iTotalRecords" => $totalData,
					"iTotalDisplayRecords" => $totalData,
					"aaData" => $data
				]);
			}


			$userId = Auth::id();
			$shippingCompanyIds = ShippingCompany::where('user_id', $userId)->pluck('id')->toArray();

			$query = Order::with(['user', 'warehouse', 'customer', 'customerAddress', 'orderItems', 'excessWeight'])
				->whereIn('shipping_company_id', $shippingCompanyIds)
				->whereIn(DB::raw('LOWER(status_courier)'), $shippedStatuses);

			if ($request->filled('fromdate') && $request->filled('todate')) {
				$query->whereBetween('order_date', [$request->fromdate, $request->todate]);
			} elseif ($request->filled('fromdate')) {
				$query->whereDate('order_date', $request->fromdate);
			} elseif ($request->filled('todate')) {
				$query->whereDate('order_date', $request->todate);
			}

			if ($search) {
				$query->where(function ($q) use ($search) {
					$q->where('awb_number', 'LIKE', "%{$search}%")
					  ->orWhere('courier_name', 'LIKE', "%{$search}%")
					  ->orWhere('id', 'LIKE', "%{$search}%")
					  ->orWhere('order_prefix', 'LIKE', "%{$search}%")
					  ->orWhereHas('user', function($u) use ($search){
						  $u->where('name', 'LIKE', "%{$search}%")
						  ->orwhere('company_name', 'LIKE', "%{$search}%");
					  });
				});
			}

			$totalData = $query->count();

			$orders = $query->orderBy('orders.' . $orderColumn, $dir)
							->offset($start)
							->limit($limit)
							->get();

			$data = [];
			$i = $start + 1;
			foreach ($orders as $order)
			{
				$customeraddr = $order->customerAddress;

				// Admin added weight (Chargeable Weight from ExcessWeight if exists, otherwise original weight)
				$adminWeight = $order->excessWeight->chargeable_weight ?? $order->weight;
				$shippedAmount = (float) ($order->shipping_charge ?? 0);
				$data[] = [
					'id' => $i++,
					'awb_number' => $order->awb_number,
					'order_prefix' => $order->order_prefix,
					'user_details' => (isset($order->user) ? "{$order->user->name} ({$order->user->company_name})" : "N/A"),
					'customer_details' => (isset($order->customer) ? "{$order->customer->first_name} {$order->customer->last_name}" : "N/A"),
					'courier_name' => $order->courier_name,
					'shipped_amount' => config('setting.currency') . ' ' . number_format($shippedAmount, 2),
					'applied_weight' => $order->weight . " Kg",
					'admin_weight' => $adminWeight . " Kg",
					'status' => $order->status_courier,
					'order_date' => $order->order_date,
				];
			}

			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $totalData,
				"iTotalDisplayRecords" => $totalData,
				"aaData" => $data
			]);
		}
	}
