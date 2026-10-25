<?php

	namespace App\Http\Controllers\Api;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use App\Models\Order;
	use App\Models\OrderItem;
	use App\Models\Billing;
	use App\Models\Invoice;
	use App\Models\UserWallet;
	use App\Models\User;
	use App\Exports\PendingStarOrderExport;
	use DB,Auth,File,Helper;
	use Illuminate\Support\Facades\Http;
	use App\Exports\OrdersExport;
	use Maatwebsite\Excel\Facades\Excel;
	use Barryvdh\DomPDF\Facade\Pdf;
	use Illuminate\Support\Facades\Storage;
	use App\Exports\BillingInvoiceExport;
	use App\Traits\ApiResponse;
	use Carbon\Carbon;

	class ReportController extends Controller
	{
		use ApiResponse;

	public function reportOrderList(Request $request)
	{
		$start = $request->input("start") ?? $request->input("offset") ?? 0;
		$limit = $request->input("length") ?? $request->input("limit") ?? 10;
		$search = $request->input('search') ?? $request->input('search.value') ?? '';

		$currentDate = now()->toDateString();
		$user = Auth::user();
		$role = $user->role;
		$userId = $user->id;

		$query = Order::with(['user', 'warehouse', 'customer', 'customerAddress', 'orderItems']);

		if ($role === "user") {
			$query->where('user_id', $userId);
		}

		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		}

		if ($request->filled('courier_name')) {
			$query->where('courier_name', $request->courier_name);
		}

		$fromDate = $request->input('from_date') ?? $request->input('fromdate');
		$toDate = $request->input('to_date') ?? $request->input('todate');

		if ($fromDate && $toDate) {
			$query->whereBetween('order_date', [$fromDate, $toDate]);
		} elseif ($fromDate) {
			$query->whereDate('order_date', $fromDate);
		} elseif ($toDate) {
			$query->whereDate('order_date', $toDate);
		}

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('created_at', 'LIKE', "%{$search}%")
					->orWhereHas('customer', function ($q) use ($search) {
						$q->where('first_name', 'LIKE', "%{$search}%")
							->orWhere('last_name', 'LIKE', "%{$search}%")
							->orWhere('mobile', 'LIKE', "%{$search}%");
					})
					->orWhereHas('user', function ($q) use ($search) {
						$q->where('name', 'LIKE', "%{$search}%")
							->orWhere('email', 'LIKE', "%{$search}%")
							->orWhere('company_name', 'LIKE', "%{$search}%")
							->orWhere('mobile', 'LIKE', "%{$search}%");
					})
					->orWhereHas('warehouse', function ($q) use ($search) {
						$q->where('contact_name', 'LIKE', "%{$search}%")
							->orWhere('contact_number', 'LIKE', "%{$search}%")
							->orWhere('warehouse_name', 'LIKE', "%{$search}%");
					})
					->orWhere('awb_number', 'LIKE', "%{$search}%")
					->orWhere('courier_name', 'LIKE', "%{$search}%")
					->orWhere('status_courier', 'LIKE', "%{$search}%")
					->orWhere('id', 'LIKE', "%{$search}%")
					->orWhere('order_prefix', 'LIKE', "%{$search}%");
			});
		}

		$totalData = (clone $query)->count();

		$orders = $query->orderByDesc('id')
						->offset($start)
						->limit($limit)
						->get();

		$data = [];
		$i = $start + 1;
		foreach ($orders as $order)
		{
			$data[] = [
				'id' => $i++,
                'seller_name' => $order->user->name ?? 'N/A',
                'seller_company' => $order->user->company_name ?? 'N/A',
                'seller_email' => $order->user->email ?? 'N/A',
                'seller_mobile' => $order->user->mobile ?? 'N/A',

                'order_prefix' => $order->order_prefix,
                'courier_name' => $order->courier_name ?? 'N/A',
                'awb_number' => $order->awb_number,

				'customer_name' => (isset($order->customer) ? "{$order->customer->first_name} {$order->customer->last_name}" : "N/A"),
                'customer_email' => $order->customer->email ?? "N/A",
                'customer_mobile' => $order->customer->mobile ?? "N/A",

				'customer_address' => $order->customerAddress->address ?? "N/A",
				'customer_city' => $order->customerAddress->city ?? 'N/A',
				'customer_state' => $order->customerAddress->state ?? 'N/A',
				'customer_country' => $order->customerAddress->country ?? 'N/A',
				'customer_pincode' => $order->customerAddress->zip_code ?? 'N/A',

                'package_quantity' => $order->orderItems->sum('quantity'),
                'package_weight' => $order->weight ?? $order->orderItems->sum('weight'),
                'package_no_of_box' => $order->orderItems->first()->dimensions['no_of_box'] ?? null,
                'package_length' => $order->length ?? null,
                'package_width' => $order->width ?? null,
                'package_height' => $order->height ?? null,

                'order_type' => $order->order_type,
				'total_amount' => $order->order_type == "cod" ? $order->cod_amount : $order->invoice_amount,

                'pickup_warehouse' => $order->warehouse->warehouse_name ?? "N/A",
                'pickup_contact_name' => $order->warehouse->contact_name ?? "N/A",
                'pickup_address' => $order->warehouse->address ?? "N/A",
                'pickup_city' => $order->warehouse->city ?? "N/A",
                'pickup_state' => $order->warehouse->state ?? "N/A",
                'pickup_pincode' => $order->warehouse->zip_code ?? "N/A",
                'pickup_contact_number' => $order->warehouse->contact_number ?? "N/A",

				'status_courier' => $order->status_courier,
				'created_at' => $order->order_date,

                'order_items' => $order->orderItems->map(fn($item) => [
                    'product_category' => $item->product_category,
                    'product_name' => $item->product_name,
                    'sku_number' => $item->sku_number,
                    'hsn_number' => $item->hsn_number,
                    'amount' => $item->amount,
                    'quantity' => $item->quantity,
                ])
			];
		}

		return $this->successResponse([
			"draw" => intval($request->post('draw')),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalData,
			"aaData" => $data
		], 'list fetched successfully.');
	}



	public function reportOrderExport(Request $request)
	{
		try {
			$fileName = 'orders_report_' . time() . '.xlsx';
			$filePath = 'exports/' . $fileName;

			// Store the file in public disk
			Excel::store(new OrdersExport($request), $filePath, 'public');

			$downloadUrl = asset('storage/' . $filePath);

			return $this->successResponse([
				'download_url' => $downloadUrl,
				'file_name' => $fileName
			], 'Export generated successfully.');
		} catch (\Exception $e) {
			return $this->errorResponse('Failed to generate export: ' . $e->getMessage());
		}
	}

	public function passbookReportList(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->input("start") ?? $request->input("offset") ?? 0;
		$limit = $request->input("length") ?? $request->input("limit") ?? 10;

		$user = Auth::user();
		$role = $user->role;
		$userId = ($role === 'user') ? $user->id : ($request->user_id ?? $user->id);

		$fromDate = $request->input('from_date') ?? $request->input('fromdate');
		$toDate = $request->input('to_date') ?? $request->input('todate');

		$query = Billing::with(['order'])
			->when($userId, function ($q) use ($userId) {
				return $q->where('user_id', $userId);
			})
			->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
				$from = Carbon::parse($fromDate)->startOfDay();
				$to = Carbon::parse($toDate)->endOfDay();
				return $q->whereBetween('billings.created_at', [$from, $to]);
			})
			->when($fromDate && !$toDate, function ($q) use ($fromDate) {
				$from = Carbon::parse($fromDate)->startOfDay();
				$to = Carbon::parse($fromDate)->endOfDay();
				return $q->whereBetween('billings.created_at', [$from, $to]);
			})
			->when($toDate && !$fromDate, function ($q) use ($toDate) {
				$to = Carbon::parse($toDate)->endOfDay();
				return $q->where('billings.created_at', '<=', $to);
			});

		// Calculate Opening Balance
		$openingBalance = 0.00;
		if ($fromDate) {
			$before = Carbon::parse($fromDate)->startOfDay();
			$openingQuery = Billing::query()
				->when($userId, function ($q) use ($userId) {
					return $q->where('user_id', $userId);
				})
				->where('billings.created_at', '<', $before);

			$openingBalance = $openingQuery->get()->reduce(function ($carry, $item) {
				return $carry + (($item->transaction_type === 'credit') ? $item->amount : -$item->amount);
			}, 0.00);
		}

		$filteredAsc = $query->orderBy("billings.created_at", 'asc')->get();

		$running = $openingBalance;
		$runningBalances = [];
		foreach ($filteredAsc as $rec) {
			$running += ($rec->transaction_type === 'credit') ? $rec->amount : -$rec->amount;
			$runningBalances[] = $running;
		}

		$closingBalance = $running;
		$filteredDesc = $filteredAsc->sortByDesc('id')->values();
		$runningBalancesDesc = array_reverse($runningBalances);

		$data = [];
		foreach ($filteredDesc->slice($start, $limit) as $key => $value) {
			$originalIndex = $filteredDesc->search($value);
			$parsedNote = $this->parseMixedAwbNote($value->note);
			$data[] = [
				'id' => $value->id,
				'billing_type' => $value->billing_type,
				'billing_type_id' => $value->billing_type_id,
				'order_id' => $parsedNote['order_id'] ?? (($value->billing_type === 'Order') ? (string) $value->billing_type_id : null),
				'awb_number' => $parsedNote['awb_number'] ?? ($value->order->awb_number ?? null),
				'courier_name' => $parsedNote['courier_name'] ?? ($value->order->courier_name ?? null),
				'product_amount' => $parsedNote['product_amount'] ?? null,
				'no_of_box' => $parsedNote['no_of_box'] ?? null,
				'sku_number' => $parsedNote['sku_number'] ?? null,
				'transaction_type' => $value->transaction_type,
				'amount' => $value->amount,
				'balance' => $runningBalancesDesc[$originalIndex] ?? $openingBalance,
				'note' => $value->note,
				'order' => $value->order,
				'created_at' => $value->created_at->toDateTimeString(),
			];
		}

		return $this->successResponse([
			"draw" => intval($draw),
			"iTotalRecords" => $filteredAsc->count(),
			"iTotalDisplayRecords" => $filteredAsc->count(),
			"aaData" => $data,
			"opening_balance" => round($openingBalance, 2),
			"closing_balance" => round($closingBalance, 2),
		], 'list fetched successfully.');
	}

	private function parseMixedAwbNote(?string $note): array
	{
		$note = trim((string) $note);
		if ($note === '') {
			return [];
		}

		$pattern = '/^\s*(?<order_id>\d+)\s*\|\s*Awb\s*Number:\s*(?<awb_number>[^|]+?)\s*\|\s*Courier:\s*(?<courier_name>[^|]+?)\s*\|\s*Product\s*Details:\s*(?<details>.+)\s*$/i';
		if (!preg_match($pattern, $note, $matches)) {
			return [];
		}

		$details = trim($matches['details'] ?? '');
		$productAmount = null;
		$noOfBox = null;
		$skuNumber = null;

		if (preg_match('/Amount:\s*([0-9]+(?:\.[0-9]+)?)/i', $details, $amountMatch)) {
			$productAmount = $amountMatch[1];
		}
		if (preg_match('/No\s*Of\s*Box:\s*([0-9]+)/i', $details, $boxMatch)) {
			$noOfBox = $boxMatch[1];
		}
		if (preg_match('/Sku:\s*(.*)$/i', $details, $skuMatch)) {
			$skuNumber = trim($skuMatch[1]) !== '' ? trim($skuMatch[1]) : null;
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

	public function billingInvoiceList(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->input("start") ?? $request->input("offset") ?? 0;
		$limit = $request->input("length") ?? $request->input("limit") ?? 10;

		$search = $request->post('search') ?? $request->post('search.value') ?? '';

        // Sorting
        $columnIndex = $request->input('order.0.column');
        $columnName = $request->input("columns.{$columnIndex}.data") ?? 'created_at';
        $dir = $request->input('order.0.dir') ?? 'desc';

        if ($columnName === 'action' || empty($columnName)) {
            $columnName = 'created_at';
        }

		$user = auth()->user();
		$query = Invoice::with('user');

		if ($user->role === 'user') {
			$query->where('user_id', $user->id);
		}

		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		}

		if ($request->filled('year') && $request->filled('month') && is_numeric($request->year) && is_numeric($request->month)) {
			$query->whereYear('invoice_date', $request->year)
				  ->whereMonth('invoice_date', $request->month);
		}

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

		$totalData = $query->count();
		$totalFiltered = $query->count(); // In this logic, totalFiltered depends on search, recalculating might be needed if search applied effectively

		$invoices = $query
			->orderBy($columnName, $dir)
			->offset($start)
			->limit($limit)
			->get();

        $data = [];
        $index = $start + 1;

		foreach ($invoices as $invoice) {
            $data[] = [
                'id' => $index++,
                'invoice_state' => $invoice->invoice_state,
                'invoice_number' => $invoice->invoice_number,
                'invoice_period' => $invoice->invoice_period,
                'invoice_date' => $invoice->invoice_date,
                'total_amount' => number_format($invoice->total_amount, 2),
                'user_name' => trim(($invoice->user->name ?? '') . ' ' . ($invoice->user->company_name ?? '')),
                'pdf_url' => route('report.billing-invoice.pdf', $invoice->id),
                'excel_url' => route('report.billing-invoice.excel', $invoice->id)
            ];
		}

		return $this->successResponse([
			"draw" => intval($request->post('draw')),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data,
		], 'list fetched successfully');
	}

	public function userWiseReportList(Request $request)
	{
		$draw = (int) $request->post('draw', 1);
		$start = (int) $request->post('start', 0);
		$limit = (int) $request->post('length', 25);
        $user = Auth::user();

		$orderArr = $request->post('order', []);
		$columnsArr = $request->post('columns', []);
		$columnIndex = (int) ($orderArr[0]['column'] ?? 0);
		$orderKey = $columnsArr[$columnIndex]['data'] ?? 'id';
		$dir = ($orderArr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

		$base = Order::query()
			->join('users', 'users.id', '=', 'orders.user_id')
			->where('users.role', 'user')
			->where('users.kyc_status', '!=', 0);

        if ($user->role === 'user') {
            $base->where('orders.user_id', $user->id);
        }

        if ($request->filled('user_id')) {
            $base->where('orders.user_id', $request->user_id);
        }

		if ($request->filled('courier_name')) {
			$base->where('orders.courier_name', $request->courier_name);
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

		$search = trim((string) ($request->input('search') ?: $request->input('search.value', '')));
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
				SUM(COALESCE(orders.shipping_charge, 0)) as total_wallet_cut,
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
				SUM(COALESCE(orders.shipping_charge, 0)) as total_wallet_cut,
				SUM(COALESCE(orders.percentage_amount, 0)) as total_profit
			')
			->groupBy('orders.user_id', 'users.name', 'users.company_name', 'users.email', 'users.mobile');

		$totalBase = Order::query()
			->join('users', 'users.id', '=', 'orders.user_id')
			->where('users.role', 'user')
			->where('users.kyc_status', '!=', 0)
			->selectRaw('orders.user_id');

        if ($user->role === 'user') {
            $totalBase->where('orders.user_id', $user->id);
        }

		$totalBase = $totalBase
			->groupBy('orders.user_id');

		$recordsTotal = DB::query()->fromSub($totalBase, 'total_base')->count();
		$recordsFiltered = DB::query()->fromSub((clone $grouped), 'filtered_base')->count();

		$columnMap = [
			'id' => 'orders.user_id',
			'user_name' => 'users.name',
			'company_name' => 'users.company_name',
			'email' => 'users.email',
			'mobile' => 'users.mobile',
		];

		if (in_array($orderKey, ['total_orders', 'total_wallet_cut', 'total_profit'], true)) {
			$grouped->orderBy($orderKey, $dir);
		} else {
			$grouped->orderBy($columnMap[$orderKey] ?? 'orders.user_id', $dir);
		}

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
				'id' => $i++,
				'user_name' => $row->user_name ?? 'N/A',
				'company_name' => $row->company_name ?? '-',
				'email' => $row->email ?? '-',
				'mobile' => $row->mobile ?? '-',
				'total_orders' => (int) ($row->total_orders ?? 0),
				'wallet_cut' => number_format($walletCut, 2),
				'aggregator_charge' => number_format($aggregatorCharge, 2),
				'profit' => number_format($profit, 2),
			];
		}

		$totalWalletCut = (float) ($totals->total_wallet_cut ?? 0);
		$totalProfit = (float) ($totals->total_profit ?? 0);

		return $this->successResponse([
			'draw' => $draw,
			'recordsTotal' => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data' => $data,
			'totals' => [
				'total_orders' => (int) ($totals->total_orders ?? 0),
				'total_wallet_cut' => $totalWalletCut,
				'total_profit' => $totalProfit,
				'total_aggregator_charge' => $totalWalletCut - $totalProfit,
			]
		], 'list fetched successfully');
	}

	public function passbookUserList(Request $request)
	{
		$draw = $request->post('draw');
		$start = $request->post("start");
		$limit = $request->post("length");

		// For APIs some datatable vars could be missing so fallback
		if(!is_numeric($start)) $start = 0;
		if(!is_numeric($limit)) $limit = 25;

		$orderColumn = 'created_at';
		$query = User::where('role', 'user')
			->where('kyc_status', '!=', 0);

		$user = Auth::user();
		if ($user->role === 'user') {
			$query->where('id', $user->id);
		}

		if ($request->filled('user_id')) {
			$query->where('id', $request->user_id);
		}

		if ($request->filled('search')) {
			$search = $request->input('search');
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%");
			});
		}

		$allRecordsQuery = clone $query;
		$totalRecords = $allRecordsQuery->count();

		// Calculate total wallet amount across ALL filtered matching users
		$totalWallet = $allRecordsQuery->sum('wallet_amount');

		$data = [];
		$records = $query->orderBy('id', 'asc')->offset($start)->limit($limit)->get();

		foreach ($records as $key => $value) {
			$viewUrl = url('passbook', $value->id);
			$data[] = [
				'id' => $start + $key + 1,
				'name' => $value->name,
				'email' => $value->email,
				'balance' => number_format($value->wallet_amount, 2),
				'passbook_url' => url('passbook', $value->id)
			];
		}

		return $this->successResponse([
			"draw" => intval($draw),
			"iTotalRecords" => $totalRecords,
			"iTotalDisplayRecords" => $totalRecords,
			"aaData" => $data,
			"total_wallet_amount" => number_format($totalWallet, 2)
		], 'list fetched successfully');
	}

	public function shippingChargeList(Request $request)
	{
		$draw = (int) $request->post('draw', 1);
		$start = (int) $request->post('start', 0);
		$limit = (int) $request->post('length', 25);
		$user = Auth::user();

		$orderArr = $request->post('order', []);
		$columnsArr = $request->post('columns', []);

		$columnIndex = $orderArr[0]['column'] ?? 0;
		$orderKey = $columnsArr[$columnIndex]['data'] ?? 'id';
		$dir = ($orderArr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

		$orderMap = [
			'id' => 'orders.id',
			'order_date' => 'orders.order_date',
			'seller_details' => 'users.name',
			'order_details' => 'orders.order_no',
			'shippings' => 'orders.courier_name',
			'shipping_charges' => 'orders.shipping_charge',
			'aggregator_charges' => DB::raw('(COALESCE(orders.shipping_charge,0) - COALESCE(orders.percentage_amount,0))'),
			'profit' => 'orders.percentage_amount',
		];
		$orderByCol = $orderMap[$orderKey] ?? 'orders.id';

		// ---- Base query ----
		$base = Order::query()
			->select('orders.*')
			->with(['orderItems', 'user', 'shippingCompany'])
			->leftJoin('users', 'users.id', '=', 'orders.user_id')
			->whereNotNull('orders.shipping_company_id')
			->whereNotIn('status_courier', ['New', 'new', 'cancelled', 'Cancelled']);

		if ($user->role === 'user') {
			$base->where('orders.user_id', $user->id);
		} 

		$recordsTotal = (clone $base)->count();

		$filtered = (clone $base);

		if ($request->filled('user_id')) {
			$filtered->where('orders.user_id', $request->user_id);
		}

		if ($request->filled('shipping_company_id')) {
			$filtered->where('orders.shipping_company_id', $request->shipping_company_id);
		}
		$filterType = 'orders.' . ($request->filter_type ?: 'order_date');
		if ($request->filled('fromdate') && $request->filled('todate') && $request->filled('filter_type'))
		{
			$filtered->whereBetween($filterType, [$request->fromdate, $request->todate]);
		} elseif ($request->filled('fromdate')) {
			$filtered->whereDate($filterType, $request->fromdate);
		} elseif ($request->filled('todate')) {
			$filtered->whereDate($filterType, $request->todate);
		}

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

		$recordsFiltered = (clone $filtered)->count();

		$totals = (clone $filtered)
			->select([
				DB::raw('SUM(COALESCE(orders.shipping_charge,0)) as total_shipping'),
				DB::raw('SUM(COALESCE(orders.percentage_amount,0)) as total_profit')
			])
			->first();

		$totalShipping = (float) ($totals->total_shipping ?? 0);
		$totalProfit = (float) ($totals->total_profit ?? 0);

		$rows = (clone $filtered)
			->orderBy($orderByCol, $dir)
			->offset($start)
			->limit($limit)
			->get();

		$data = [];
		$i = $start + 1;
		foreach ($rows as $order) {
			$shipping_charges = (float) ($order->shipping_charge ?? 0);
			$aggregator_charges = (float) ($order->shipping_charge ?? 0) -  (float) ($order->percentage_amount ?? 0);
			$profit = (float) ($order->percentage_amount ?? 0);

			$data[] = [
				'id' => $i,
				'order_date' => $order->order_date,
				'delivery_date' => $order->delivery_date ?? 'N/A',
                'seller_name' => $order->user->name ?? 'N/A',
                'seller_company' => $order->user->company_name ?? 'N/A',
                'seller_email' => $order->user->email ?? 'N/A',
                'seller_mobile' => $order->user->mobile ?? 'N/A',

                'order_prefix' => $order->order_prefix,
                'courier_name' => $order->courier_name ?? 'N/A',
                'awb_number' => $order->awb_number,

                'shipping_charges' => number_format($shipping_charges, 2),
				'aggregator_charges' => number_format($aggregator_charges, 2),
				'profit' => number_format($profit, 2),

                'order_items' => $order->orderItems->map(fn($item) => [
                    'product_category' => $item->product_category,
                    'product_name' => $item->product_name,
                    'sku_number' => $item->sku_number,
                    'amount' => $item->amount,
                    'quantity' => $item->quantity,
                ])
			];
			$i++;
		}

		return $this->successResponse([
			'draw' => $draw,
			'recordsTotal' => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data' => $data,
			'totals' => [
					'total_shipping' => $totalShipping,
					'total_profit' => $totalProfit,
				]
		], 'list fetched successfully');
	}

		public function billingInvoicePdf($invoiceId)
		{
			try{

				$invoiceDetail = Invoice::with('user')->findOrFail($invoiceId);
				$user = $invoiceDetail->user;

				$invoice = [
					'number' => $invoiceDetail->invoice_number,
					'date' => \Carbon\Carbon::parse($invoiceDetail->invoice_date)->format('d M Y'),
					'period' => \Carbon\Carbon::parse($invoiceDetail->month_start)->format('d') . ' to ' . \Carbon\Carbon::parse($invoiceDetail->month_end)->format('d M Y'),
					'from' => [
						'name' => 'KSHIP EXPRESS',
						'address' => 'RING ROAD SURAT GUJARAT 395003',
						'gst' => '24AFKFS090154',
					],
					'to' => [
						'name' => $user->company_name ?? '',
						'address' => trim(($user->address ?? '') . ', ' . ($user->city ?? '') . ', ' . ($user->state ?? '') . ', ' . ($user->country ?? '')),
						'mobile' => $user->mobile ?? '',
					],
					'charges' => [
						'shipping' => round($invoiceDetail->base_amount, 2),
						'cgst' => round($invoiceDetail->cgst_amount, 2),
						'sgst' => round($invoiceDetail->sgst_amount, 2),
						'igst' => round($invoiceDetail->igst_amount, 2),
					],
				];

				$total = $invoiceDetail->total_amount;
				$pdf = Pdf::loadView('report.pdf-invoice', compact('invoice', 'total'))->setPaper('a4', 'portrait');
				$filename = 'invoice_' . $invoiceDetail->invoice_number . '.pdf';

				return $pdf->download($filename);
			}
			catch(\Exception $e)
			{
				return $this->errorResponse('failed to download pdf generation.');
			}
		}


		public function billingInvoiceExcel($invoiceId)
		{
			try{
				$invoice = Invoice::with(['orders.user'])->findOrFail($invoiceId);
				// Replace slashes to make a valid filename
				$sanitizedNumber = str_replace('/', '-', $invoice->invoice_number);
				$filename = 'invoice_' . $sanitizedNumber . '.xlsx';

				return Excel::download(new BillingInvoiceExport($invoice), $filename);
			}
			catch(\Exception $e)
			{
				return $this->errorResponse('failed to download excel generation.');
			}
		}

	public function rechargeList(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->input("start") ?? $request->input("offset") ?? 0;
		$limit = $request->input("length") ?? $request->input("limit") ?? 10;

		$user = Auth::user();
		$userId = $user->id;
		$role = $user->role;

		$query = UserWallet::with('user:id,name');

		if ($role === 'user') {
			$query->where('user_wallets.user_id', $userId);
		} elseif ($request->filled('user_id')) {
			$query->where('user_wallets.user_id', $request->user_id);
		}

		if ($request->filled('payment_mode')) {
			$query->where('user_wallets.transaction_type', $request->payment_mode);
		}

		if ($request->filled('status')) {
			$query->where('user_wallets.transaction_status', $request->status);
		}

		$fromDate = $request->input('from_date') ?? $request->input('fromdate');
		$toDate = $request->input('to_date') ?? $request->input('todate');

		if ($fromDate && $toDate) {
			$query->whereBetween('user_wallets.created_at', [
				Carbon::parse($fromDate)->startOfDay(),
				Carbon::parse($toDate)->endOfDay()
			]);
		}

		$totalData = (clone $query)->count();

		$searchValue = $request->input('search') ?? $request->input('search.value') ?? '';
		if (!empty($searchValue)) {
			$query->where(function ($q) use ($searchValue) {
				$q->whereHas('user', function ($q2) use ($searchValue) {
					$q2->where('name', 'LIKE', "%{$searchValue}%");
				})
				->orWhere('user_wallets.amount', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.created_at', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.order_id', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.txn_number', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.transaction_type', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.amount_type', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.pg_name', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.transaction_status', 'LIKE', "%{$searchValue}%")
				->orWhere('user_wallets.utr_no', 'LIKE', "%{$searchValue}%");
			});
		}

		$totalFiltered = $query->count();

		$values = $query->offset($start)
			->limit($limit)
			->orderByDesc('id')
			->get();

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $values
		]);
	}
	}
