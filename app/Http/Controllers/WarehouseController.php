<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\User;
use App\Models\VendorAddress;
use App\Models\ShippingCompany;
use App\Models\PickupRequest;
use App\Models\CourierWarehouse;
use DB, Auth, File, Helper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DateTime, DateInterval, DatePeriod;
use Carbon\Carbon;


class WarehouseController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index()
	{
		return view('warehouse.index');
	}

	public function ajaxWarehouse(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->get("start", 0);
		$limit = $request->get("length", 10); // Rows per page

		$columnIndexArr = $request->get('order', []);
		$columnNameArr = $request->get('columns', []);
		$orderArr = $request->get('order', []);
		$searchArr = $request->get('search', []);

		$columnIndex = $columnIndexArr[0]['column'] ?? 0; // Column index
		$order = $columnNameArr[$columnIndex]['data'] ?? 'id'; // Column name
		$dir = $orderArr[0]['dir'] ?? 'asc'; // Order direction

		if ($order === 'customer_name') {
			$order = 'id';
		}

		$user = Auth::user();
		$role = $user->role;
		$userId = $user->id;
		$canManageWarehouse = in_array($role, ['user', 'admin', 'master_admin'], true)
			|| config('permission.warehouse.edit')
			|| config('permission.warehouse.delete');

		// Base query
		$query = CourierWarehouse::with('user:id,name');

		if ($role === "user") {
			$query->where('user_id', $userId);
		}

		// Apply search filter
		if (!empty($searchArr['value'])) {
			$search = $searchArr['value'];
			$query->where(function ($q) use ($search) {
				$q->where('company_name', 'LIKE', "%{$search}%")
					->orWhere('contact_name', 'LIKE', "%{$search}%")
					->orWhere('contact_number', 'LIKE', "%{$search}%")
					->orWhere('warehouse_name', 'LIKE', "%{$search}%")
					->orWhere('address', 'LIKE', "%{$search}%")
					->orWhereDate('created_at', 'LIKE', "%{$search}%");
			});
		}

		$totalData = $query->count();
		$totalFiltered = $totalData;

		// Fetch paginated data
		$values = $query->orderBy($order, $dir)
			->offset($start)
			->limit($limit)
			->get();

		// Format data for DataTable
		$i = $start + 1;
		$data = [];

		foreach ($values as $value) {
			$actionButtons = '';

			if ($canManageWarehouse) {
				$actionButtons .= '<a href="' . url('warehouse/edit', $value->id) . '"
						>
						<i class="mdi mdi-pencil class-pencil"></i>
					</a>';
			}

			//  if (config('permission.warehouse.delete')) {
			// 	 $actionButtons .= '<a href="' . url('warehouse/delete', $value->id) . '"

			// 		 data-toggle="tooltip" title="Delete" onClick="deleteRecord(this, event);">
			// 		 <i class="mdi mdi-trash-can-outline class-delete"></i>
			// 	 </a>';
			//  }

			$data[] = [
				'id' => $i++,
				'company_name' => $value->company_name,
				'contact_name' => $value->contact_name,
				'contact_number' => $value->contact_number,
				'warehouse_name' => $value->warehouse_name,
				'address' => $value->address,
				'warehouse_status' => $value->warehouse_status == 1
					? '<span class="badge badge-success">Active</span>'
					: '<span class="badge badge-danger">In-Active</span>',
				'created_at' => date('d M Y', strtotime($value->created_at)) .
					($role == "admin" ? '<br><p>' . ($value->user->name ?? 'N.A') . '</p>' : ''),
				'action' => $actionButtons
			];
		}

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data,
		]);
	}

	public function createWarehouse()
	{
		return view('warehouse.add');
	}

	public function storeWarehouse(Request $request)
	{
		$user_id = Auth::id();
		$timestamp = now();

		// Validate input, including warehouse name uniqueness
		$validator = Validator::make($request->all(), [
			'warehouse_name' => 'required|unique:courier_warehouses,warehouse_name',
			'company_name' => 'required|string|max:255',
			'contact_name' => 'required|string|max:255',
			'contact_email' => 'required|email',
			'contact_number' => 'required|digits_between:7,15',
			'address' => 'required|string',
			'country' => 'required|string',
			'state' => 'required|string',
			'city' => 'required|string',
			'zip_code' => 'required|digits:6',
		]);


		if ($validator->fails()) {
			return response()->json(['status' => 'info', 'msg' => $validator->errors()->first()]);
		}

		// Prepare vendor data
		$warehouseData = $request->except('_token');
		$warehouseData['user_id'] = $user_id;
		$warehouseData['label_options'] = $request->label_options ?? [];
		$warehouseData['created_at'] = $timestamp;
		$warehouseData['updated_at'] = $timestamp;

		DB::beginTransaction();
		try {

			$courierWarehouse = CourierWarehouse::create($warehouseData);

			DB::commit();
			return response()->json(['status' => 'success', 'msg' => 'The warehouse has been successfully added.', 'warehouse_id' => $courierWarehouse->id]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to add warehouse. Please try again. ' . $e->getMessage()]);
		}
	}

	public function editWarehouse($id)
	{
		$courierWarehouse = CourierWarehouse::find($id);
		$this->authorizeWarehouseAccess($courierWarehouse);
		return view('warehouse.edit', compact('courierWarehouse'));
	}

	public function updateWarehouse(Request $request, $id)
	{
		$user_id = Auth::id();
		$timestamp = now();

		// Validate input
		$validator = Validator::make($request->all(), [
			'warehouse_name' => 'required|unique:courier_warehouses,warehouse_name,' . $id,
			'company_name' => 'required|string|max:255',
			'contact_name' => 'required|string|max:255',
			'contact_email' => 'required|email',
			'contact_number' => 'required|digits_between:7,15',
			'address' => 'required|string',
			'country' => 'required|string',
			'state' => 'required|string',
			'city' => 'required|string',
			'zip_code' => 'required|digits:6',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'info', 'msg' => $validator->errors()->first()]);
		}

		DB::beginTransaction();
		try {

			$labelOptions = [];
			foreach (['hide_contact', 'hide_address', 'hide_mobile', 'hide_product', 'hide_weight'] as $label_option) {
				$labelOptions[$label_option] = 	in_array($label_option, $request->label_options ?? []) ? true : false;
			}
			$courierWarehouse = CourierWarehouse::findOrFail($id);
			$this->authorizeWarehouseAccess($courierWarehouse);
			$courierWarehouse->fill($request->all());
			$courierWarehouse->label_options = $labelOptions;
			$courierWarehouse->updated_at = $timestamp;

			// Save updates
			$courierWarehouse->save();

			DB::commit();
			return response()->json(['status' => 'success', 'msg' => 'The warehouse has been successfully updated.']);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to update warehouse. Please try again. ' . $e->getMessage()]);
		}
	}

	public function deleteWarehouse($id)
	{
		try {
			DB::beginTransaction();

			$courierWarehouse = CourierWarehouse::findOrFail($id);
			$this->authorizeWarehouseAccess($courierWarehouse);
			$courierWarehouse->delete();

			DB::commit();
			return redirect()->route('warehouse')->with('success', 'The warehouse has been successfully deleted.');
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->route('warehouse')->with('error', 'Something went wrong.');
		}
	}

	public function pickupRequestList()
	{
		$user = Auth::user();
		$warehouses = CourierWarehouse::where('user_id', $user->id)
			->where('warehouse_status', 1)
			->get();

		$shippingCompanies = ShippingCompany::where('provider', 'shadowfax')
			->where('status', 1)
			->get(['id', 'name']);

		$start = new DateTime('09:00');
		$end = new DateTime('21:00'); // 9:00 PM
		$interval = new DateInterval('PT3H'); // 1-hour gap
		$period = new DatePeriod($start, $interval, $end);

		$timeSlots = [];

		foreach ($period as $dt) {
			$slotStart = $dt->format('h:i A');
			$slotEnd = $dt->add($interval)->format('h:i A');
			$timeSlots[] = "$slotStart - $slotEnd";
		}

		return view('warehouse.pickup-request', compact('warehouses', 'timeSlots', 'shippingCompanies'));
	}

	public function pickupRequestAjax(Request $request)
	{
		$draw = $request->get('draw', 1);
		$start = $request->get("start", 0);
		$limit = $request->get("length", 10);

		$columnIndexArr = $request->get('order', []);
		$columnNameArr = $request->get('columns', []);
		$orderArr = $request->get('order', []);
		$searchArr = $request->get('search', []);

		$columnIndex = $columnIndexArr[0]['column'] ?? 0;
		$order = $columnNameArr[$columnIndex]['data'] ?? 'id';
		$dir = $orderArr[0]['dir'] ?? 'asc';

		if ($order === 'customer_name') {
			$order = 'id';
		}

		$user = Auth::user();
		$role = $user->role;
		$userId = $user->id;

		// Base query with necessary joins
		$query = PickupRequest::query()->with(['warehouse', 'user', 'shippingCompany']);

		if ($role === "user") {
			$query->where('user_id', $userId);
		}

		if ($request->filled('from_date') && $request->filled('to_date') && $request->filled('date_type')) {
			$from = Carbon::parse($request->from_date)->startOfDay();
			$to = Carbon::parse($request->to_date)->endOfDay();
			$query->whereBetween($request->date_type, [$from, $to]);
		} elseif ($request->filled('from_date') && $request->filled('date_type')) {
			$from = Carbon::parse($request->from_date)->startOfDay();
			$query->where($request->date_type, $from);

		} elseif ($request->filled('to_date') && $request->filled('date_type')) {
			$to = Carbon::parse($request->to_date)->endOfDay();
			$query->where($request->date_type, $to);
		}

		// Apply search filter
		if (!empty($searchArr)) {
			$search = trim($searchArr);

			$query->where(function ($q) use ($search) {
				$q->where('pickup_id', 'LIKE', "%{$search}%")
					->orWhereHas('warehouse', function ($q) use ($search) {
						$q->where('warehouse_name', 'LIKE', "%{$search}%")
							->orWhere('address', 'LIKE', "%{$search}%")
							->orWhere('contact_number', 'LIKE', "%{$search}%");
					})
					->orWhereHas('shippingCompany', function ($q) use ($search) {
						$q->where('name', 'LIKE', "%{$search}%");
					})
					->orWhereHas('user', function ($q) use ($search) {
						$q->where('name', 'LIKE', "%{$search}%")
							->orWhere('email', 'LIKE', "%{$search}%");
					})
					->orWhereDate('created_at', 'LIKE', "%{$search}%");
			});
		}

		$totalData = PickupRequest::count();
		$totalFiltered = $query->count();

		// Fetch paginated data
		$values = $query->orderBy($order, $dir)
			->offset($start)
			->limit($limit)
			->get();

		$data = [];
		$i = $start + 1;

		foreach ($values as $value) {
			$action = '';

			// Check permission before allowing cancel action
			// if ($value->status == 1 && $value->shipping_company_id == 1 && config('permission.pickup_request.delete')) {
			// 	$action = '<a href="' . url('pickup-request/cancel', $value->id) . '"
			// 		class="btn btn-danger btn-sm" data-toggle="tooltip"
			// 		title="Cancel Pickup Request" onClick="cancelPickupRequest(this, event);">
			// 		Cancel
			// 		</a>';
			// }

			$data[] = [
				'id' => $i++,
				'shipping_company_id' => $value->shippingCompany->name ?? '',
				'pickup_id' => $value->pickup_id,
				'warehouse_id' => ($value->warehouse->warehouse_name ?? '') . '<br>' . ($value->warehouse->address ?? ''),
				'expected_package_count' => $value->expected_package_count,
				'pickup_date' => date('d M Y', strtotime($value->pickup_date)) . '<br>' .
				date('h:i A', strtotime($value->pickup_start_time)) . ' - ' .
				date('h:i A', strtotime($value->pickup_end_time)),
				'shipper_contact' => $value->warehouse->contact_number ?? '',
				'status' => $value->status == 1
					? '<span class="badge badge-info">Open</span>'
					: '<span class="badge badge-danger">Cancelled</span>',
				'created_at' => date('d M Y', strtotime($value->created_at)) .
				($role == "admin" ? '<br><p>' . ($value->user->name ?? 'N.A') . '</p>' : ''),
				'action' => $action
			];
		}

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data,
		]);
	}

	public function createPickupRequest(Request $request)
	{
		// Validate if the shipping company exists
		$shippingCompany = ShippingCompany::where('status', 1)->where('id', $request->shipping_company_id)->first();

		if (!$shippingCompany) {
			if ($request->ajax()) return response()->json(['status' => 'error', 'msg' => 'Shipping company not found or inactive.']);
			return redirect()->back()->with('error', 'Shipping company not found or inactive.');
		}

		// No active courier supports pickup requests through this endpoint
		if ($request->ajax()) return response()->json(['status' => 'error', 'msg' => 'Pickup request is not supported for this courier.']);
		return redirect()->back()->with('error', 'Pickup request is not supported for this courier.');
	}

	private function authorizeWarehouseAccess(?CourierWarehouse $courierWarehouse): void
	{
		if (!$courierWarehouse) {
			abort(404);
		}

		$user = Auth::user();
		if (in_array($user->role, ['admin', 'master_admin'], true) || (int) $courierWarehouse->user_id === (int) $user->id) {
			return;
		}

		abort(403, 'Permission denied.');
	}
}
