<?php
	
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use App\Models\Vendor;
	use App\Models\User;
	use App\Models\VendorAddress;
	use App\Models\ShippingCompany;
	use App\Models\CourierWarehouse;
	use DB,Auth,File,Helper;
	use App\Services\MasterService;
	use App\Services\DelhiveryService;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Validation\Rule;
	
	class VendorController extends Controller
	{	
		protected $masterService;
		
		public function __construct()
		{
			$this->middleware('auth');
			$this->masterService = new MasterService();
			$this->delhiveryService = new DelhiveryService();
		}
		 
		public function index()
		{ 
			return view('vendor.index');
		}
		
		public function ajaxVendor(Request $request)
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

			$role = Auth::user()->role;
			$userId = Auth::user()->id;

			// Base query
			$query = Vendor::select(
				'vendors.*',
				DB::raw("CONCAT(first_name, ' ', last_name) as vendor_name"),
				'users.wallet_amount'
			)
				->leftJoin('users', 'vendors.user_id', '=', 'users.id');

			if ($role !== "admin") {
				$query->where('vendors.user_id', $userId);
			}

			// Apply search filter
			if (!empty($searchArr['value'])) {
				$search = $searchArr['value'];
				$query->where(function ($q) use ($search) {
					$q->where('vendors.company_name', 'LIKE', "%{$search}%")
						->orWhere('vendors.first_name', 'LIKE', "%{$search}%")
						->orWhere('vendors.last_name', 'LIKE', "%{$search}%")
						->orWhere('vendors.mobile', 'LIKE', "%{$search}%")
						->orWhere('vendors.email', 'LIKE', "%{$search}%")
						->orWhereDate('vendors.created_at', 'LIKE', "%{$search}%");
				});
			}

			$totalFiltered = $query->count();

			// Fetch paginated data
			$vendors = $query->orderBy("vendors.$order", $dir)
				->offset($start)
				->limit($limit)
				->get();

			// Format data for DataTable
			$data = [];
			$i = $start + 1;

			foreach ($vendors as $vendor) {
				$data[] = [
					'id' => $i++,
					'company_name' => $vendor->company_name,
					'vendor_name' => $vendor->vendor_name,
					'mobile' => $vendor->mobile,
					'email' => $vendor->email,
					'status' => $vendor->status == 1
						? '<span class="badge badge-success">Active</span>'
						: '<span class="badge badge-danger">In-Active</span>',
					'wallet' => $vendor->wallet_amount,
					'created_at' => date('d M Y', strtotime($vendor->created_at)),
					'action' => '
						<a href="' . url('vendor/edit', $vendor->id) . '" class="btn btn-icon waves-effect waves-light action-icon mr-1">
							<i class="mdi mdi-pencil"></i>
						</a>
						<a href="' . url('vendor/delete', $vendor->id) . '" class="btn btn-icon waves-effect waves-light action-icon" data-toggle="tooltip" title="Delete" onClick="deleteRecord(this, event);">
							<i class="mdi mdi-trash-can-outline"></i>
						</a>',
				];
			}

			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => Vendor::count(),
				"iTotalDisplayRecords" => $totalFiltered,
				"aaData" => $data,
			]);
		}
		
		public function addVendor()
		{  
			return view('vendor.add');
		}
		
		public function storeVendor(Request $request)
		{
			$user_id = Auth::id();
			$timestamp = now();

			// Validate input, including warehouse name uniqueness
			$validator = Validator::make($request->all(), [
				'warehouse_name.*' => [
					'required',
					'distinct',
					Rule::notIn(CourierWarehouse::pluck('warehouse_name')->toArray()), // Ensures the warehouse name is not in the existing database
				],
				'address.*' => 'required',
				'country.*' => 'required',
				'state.*' => 'required',
				'city.*' => 'required',
				'zip_code.*' => 'required|digits:6',
			]);

			if ($validator->fails()) {
				return response()->json(['status' => 'info', 'msg' => $validator->errors()->first()]);
			}

			// Prepare vendor data
			$vendorData = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'warehouse_name');
			$vendorData['user_id'] = $user_id;
			$vendorData['created_at'] = $timestamp;
			$vendorData['updated_at'] = $timestamp;

			DB::beginTransaction();
			try {
				// Insert vendor
				$vendor = Vendor::create($vendorData);
				$shippingCompanies = ShippingCompany::whereIn('id', [2])->where('status', 1)->get();

				if ($request->has('address') && is_array($request->address)) {
					$vendorAddresses = [];
					foreach ($request->address as $key => $addr) {
						$vendorAddresses[] = [
							'vendor_id' => $vendor->id,
							'address' => $addr,
							'warehouse_name' => $request->warehouse_name[$key] ?? null,
							'country' => $request->country[$key] ?? null,
							'state' => $request->state[$key] ?? null,
							'city' => $request->city[$key] ?? null,
							'zip_code' => $request->zip_code[$key] ?? null,
							'status' => 1,
							'created_at' => $timestamp,
							'updated_at' => $timestamp,
						];
					}

					// Bulk insert addresses to reduce queries
					VendorAddress::insert($vendorAddresses);

					// Fetch inserted addresses for processing
					$insertedAddresses = VendorAddress::where('vendor_id', $vendor->id)->get();

					foreach ($insertedAddresses as $vendorAddress) {
						$this->addWareHouseByAPI($vendor, $vendorAddress, $shippingCompanies);
					}
				}

				DB::commit();
				return response()->json(['status' => 'success', 'msg' => 'The vendor has been successfully added.']);
			} catch (\Exception $e) {
				DB::rollBack();
				Log::error('Vendor creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

				return response()->json(['status' => 'error', 'msg' => 'Failed to add vendor. Please try again. ' . $e->getMessage()]);
			}
		}

		public function addWareHouseByAPI($vendor, $vendorAddress, $shippingCompanies)
		{
			if ($shippingCompanies->isNotEmpty()) {
				foreach ($shippingCompanies as $shippingCompany) {
					$data = $this->delhiveryService->createWarehouse($vendor, $vendorAddress, $shippingCompany);
					 
					if (!isset($data['success']) || !$data['success'] || (isset($data['response']['success']) && !$data['response']['success'])) {
						continue;
					}

					if ($data['response']['success']) {
						CourierWarehouse::create([
							'vendor_address_id' => $vendorAddress->id,
							'warehouse_name' => $vendorAddress->warehouse_name,
							'shipping_id' => $shippingCompany->id,
							'warehouse_status' => 1,
							'api_response' => $data['response'],
							'created_at' => now(),
							'updated_at' => now(),
						]); 
						$vendorAddress->update(['warehouse_status' => 1, 'shipping_id' => $shippingCompany->id]);
					}
				}
			}
		}
		   
		public function editVendor($id)
		{
			$vendor = Vendor::with('vendorAddresses')->find($id);  
			$vendorAddresses = $vendor->vendorAddresses;  
			return view('vendor.edit', compact('vendor', 'vendorAddresses'));
		}
		
		public function updateVendor(Request $request, $vendorId)
		{
			$user_id = Auth::id();
			$timestamp = now();

			// Validate input, including warehouse name uniqueness (excluding current vendor warehouses)
			$validator = Validator::make($request->all(), [
				'warehouse_name.*' => [
					'required',
					'distinct',
					Rule::notIn(CourierWarehouse::whereNotIn('vendor_address_id', VendorAddress::where('vendor_id', $vendorId)->pluck('id'))->pluck('warehouse_name')->toArray()),
				],
				'address.*' => 'required',
				'country.*' => 'required',
				'state.*' => 'required',
				'city.*' => 'required',
				'zip_code.*' => 'required|digits:6',
			]);

			if ($validator->fails()) {
				return response()->json(['status' => 'info', 'msg' => $validator->errors()->first()]);
			}

			DB::beginTransaction();
			try {
				// Fetch existing vendor
				$vendor = Vendor::findOrFail($vendorId);

				// Update vendor data
				$vendorData = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'warehouse_name');
				$vendorData['updated_at'] = $timestamp;
				$vendor->update($vendorData);

				$shippingCompanies = ShippingCompany::whereIn('id', [2])->where('status', 1)->get();
				
				// Process Addresses
				if ($request->has('address') && is_array($request->address))
				{
					$existingVendorAddresses = VendorAddress::where('vendor_id', $vendor->id)->get()->keyBy('id');
					$newVendorAddresses = [];

					foreach ($request->address as $key => $addr) {
						$addressId = $request->id[$key] ?? null;

						// Prepare address data
						$addressData = [
							'vendor_id' => $vendor->id,
							'address' => $addr,
							'warehouse_name' => $request->warehouse_name[$key] ?? null,
							'country' => $request->country[$key] ?? null,
							'state' => $request->state[$key] ?? null,
							'city' => $request->city[$key] ?? null,
							'zip_code' => $request->zip_code[$key] ?? null,
							'status' => 1,
							'updated_at' => $timestamp,
						];

						if ($addressId && isset($existingVendorAddresses[$addressId])) {
							// Check if warehouse_name has changed before updating the warehouse API
							$vendorAddress = $existingVendorAddresses[$addressId]; 
							// Fill the model with new data without saving
							$vendorAddress->fill($addressData);

							// Check if warehouse_name has changed
							if ($vendorAddress->isDirty('warehouse_name') || $vendorAddress->isDirty('address')) {
								$vendorAddress->save();
								$this->updateWareHouseByAPI($vendor, $vendorAddress, $shippingCompanies);
							} else {
								// If warehouse_name is unchanged, just save the updated address data
								$vendorAddress->save();
							}
						} else {
							// Add new address
							$addressData['created_at'] = $timestamp;
							$newVendorAddresses[] = $addressData;
						}
					}

					// Bulk insert new addresses
					if (!empty($newVendorAddresses)) {
						VendorAddress::insert($newVendorAddresses);

						// Fetch newly inserted addresses
						$insertedAddresses = VendorAddress::where('vendor_id', $vendor->id)
							->where('created_at', $timestamp)
							->get();

						foreach ($insertedAddresses as $vendorAddress) {
							$this->addWareHouseByAPI($vendor, $vendorAddress, $shippingCompanies);
						}
					}

					// Delete removed addresses
					$submittedIds = array_filter($request->id ?? []);
					VendorAddress::where('vendor_id', $vendor->id)
						->whereNotIn('id', $submittedIds)
						->delete();
				}

				DB::commit();
				return response()->json(['status' => 'success', 'msg' => 'The vendor has been successfully updated.']);
			} catch (\Exception $e) {
				DB::rollBack();
				Log::error('Vendor update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

				return response()->json(['status' => 'error', 'msg' => 'Failed to update vendor. Please try again. ' . $e->getMessage()]);
			}
		}
		
		public function updateWareHouseByAPI($vendor, $vendorAddress, $shippingCompanies)
		{ 
			if ($shippingCompanies->isNotEmpty()) {
				foreach ($shippingCompanies as $shippingCompany) {
					$data = $this->delhiveryService->updateWarehouse($vendor, $vendorAddress, $shippingCompany);
					dd(json_encode($data));
					if (!isset($data['success']) || !$data['success'] || 
						(isset($data['response']['success']) && !$data['response']['success'])) {
						continue;
					}
					
					if ($data['response']['success']) 
					{
						// Check if warehouse already exists
						$existingWarehouse = CourierWarehouse::where('vendor_address_id', $vendorAddress->id)
							->where('shipping_id', $shippingCompany->id)
							->first();

						if ($existingWarehouse) 
						{
							// Update existing warehouse
							$existingWarehouse->update([
								'warehouse_name' => $vendorAddress->warehouse_name,
								'warehouse_status' => 1,
								'api_response' => $data['response'],
								'updated_at' => now(),
							]);
						} else {
							// Create new warehouse if not exists
							CourierWarehouse::create([
								'vendor_address_id' => $vendorAddress->id,
								'warehouse_name' => $vendorAddress->warehouse_name,
								'shipping_id' => $shippingCompany->id,
								'warehouse_status' => 1,
								'api_response' => $data['response'],
								'created_at' => now(),
								'updated_at' => now(),
							]);
						}

						// Update vendor address status
						$vendorAddress->update([
							'warehouse_status' => 1,
							'shipping_id' => $shippingCompany->id
						]);
					}
				}
			}
		}
 
		public function deleteVendor($id)
		{ 
			try {
				DB::beginTransaction();
 
				$vendor = Vendor::find($id);  
				$vendor->vendorAddresses()->delete();
				$vendor->delete();
				
				DB::commit(); 
				return redirect()->route('vendor')->with('success', 'The vendor has been successfully deleted.');
			} catch (\Exception $e) {
				DB::rollBack(); 
				return redirect()->route('vendor')->with('error', 'Something went wrong.');
			} 
		}
	}
