<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request; 
	use App\Models\User; 
	use App\Models\ShippingCompany;
	use App\Models\PickupRequest;
	use App\Models\Customer;
	use App\Models\CustomerAddress;
	use App\Models\CourierWarehouse;
	use DB,Auth,File,Helper; 
	use App\Services\DelhiveryService;
	use App\Services\ShipMozo;
	use App\Services\DelhiveryB2CService;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Validation\Rule;
	use App\Traits\ApiResponse;
	use Storage; 
	
	class WarehouseController extends Controller
	{	 
		use ApiResponse; 
		
		public function __construct()
		{ 
			$this->delhiveryService = new DelhiveryService();
			$this->delhiveryB2CService = new DelhiveryB2CService();
			$this->shipMozo = new ShipMozo();
		}
		 
		public function index(Request $request)
		{ 
			$user = Auth::user();
			$role = $user->role;
			$userId = $user->id;
			
			$search = $request->input('search');
			$offset = $request->input('offset');
			$limit = $request->input('limit');
			
			// Base query
			$query = CourierWarehouse::with('user:id,name');
			 	
			if ($role === "user") {
				$query->where('user_id', $userId);
			}

			// Shipment filter for active warehouses only
			if ($request->has('shipment') && filter_var($request->shipment, FILTER_VALIDATE_BOOLEAN)) {
				$query->where('warehouse_status', 1);
			}
 
			if (!empty($search)) { 
				$query->where(function ($q) use ($search) {
					$q->where('company_name', 'LIKE', "%{$search}%")
						->orWhere('contact_name', 'LIKE', "%{$search}%")
						->orWhere('contact_number', 'LIKE', "%{$search}%")
						->orWhere('warehouse_name', 'LIKE', "%{$search}%")
						->orWhere('address', 'LIKE', "%{$search}%")
						->orWhereDate('created_at', 'LIKE', "%{$search}%");
				});
			} 
			 
			$query->latest('id');
			
			if (isset($offset) && isset($limit)) {
				$query->offset($offset)->limit($limit);
			}
			
			$values = $query->get();
			
			return $this->successResponse($values, 'warehouse fetched successfully.');
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
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			// Prepare vendor data
			$labelOptions = [];
			foreach(['hide_contact', 'hide_address', 'hide_mobile', 'hide_product', 'hide_weight'] as $label_option)
			{
				$labelOptions[$label_option] = 	in_array($label_option, $request->label_options ?? []) ? true : false;
			}

			$warehouseData = $request->except('_token');
			$warehouseData['user_id'] = $user_id;
			$warehouseData['label_options'] = $labelOptions; 
			$warehouseData['created_at'] = $timestamp;
			$warehouseData['updated_at'] = $timestamp;

			DB::beginTransaction();
			try {
			
				$courierWarehouse = CourierWarehouse::create($warehouseData);
				$shippingCompanies = ShippingCompany::whereIn('id', [1, 2, 3, 4, 5])->where('status', 1)->get();
				$this->addWareHouseByAPI($courierWarehouse, $shippingCompanies);

				DB::commit();
				return $this->successResponse($courierWarehouse, 'The warehouse has been successfully added.'); 
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to add warehouse. Please try again.');  
			}
		}

		public function addWareHouseByAPI($courierWarehouse, $shippingCompanies)
		{
			if ($shippingCompanies->isNotEmpty()) {
				foreach ($shippingCompanies as $shippingCompany) {
					if (in_array($shippingCompany->id, [1, 3, 4])) {
						$data = $this->delhiveryB2CService->createWarehouse($courierWarehouse, $shippingCompany);
						 
						if (!isset($data['success']) || !$data['success']) {
							continue;
						}

						$responseSuccess = $data['response']['success'] ?? false;
						if ($responseSuccess === true || $responseSuccess === "True" || $responseSuccess === 1 || $responseSuccess === "1") 
						{ 
							$existingCreated = $courierWarehouse->created ?? [];
							if (is_string($existingCreated)) {
								$existingCreated = json_decode($existingCreated, true) ?? [];
							}
							$existingCreated['delhivery_status'] = 1;

							$courierWarehouse->created = $existingCreated;
							$courierWarehouse->shipping_id = $shippingCompany->id;
							$courierWarehouse->api_response = $data['response'];
							$courierWarehouse->save(); 
						}
					} elseif ($shippingCompany->id == 2) {
						$data = $this->shipMozo->createWarehouse($courierWarehouse, $shippingCompany);
						
						if (!empty($data['success']) || (isset($data['response']['result']) && $data['response']['result'] == 1)) {
							$existingCreated = $courierWarehouse->created ?? [];
							if (is_string($existingCreated)) {
								$existingCreated = json_decode($existingCreated, true) ?? [];
							}
							$existingCreated['ship_mozo'] = 1;
							$existingCreated['shipMozo'] = 1;
							
							$courierWarehouse->created = $existingCreated;
							$courierWarehouse->shipmozo_warehouse_id = $data['response']['data']['warehouse_id'] ?? $courierWarehouse->shipmozo_warehouse_id;
							$courierWarehouse->shipping_id = $data['response']['data']['warehouse_id'] ?? $courierWarehouse->shipping_id;
							$courierWarehouse->api_response = $data['response'];
							$courierWarehouse->save();
						}
					} elseif ($shippingCompany->id == 5) {
						$data = $this->delhiveryService->createWarehouse($courierWarehouse, $shippingCompany);
						 
						if (!isset($data['success']) || !$data['success']) {
							continue;
						}

						$responseSuccess = $data['response']['success'] ?? false;
						if ($responseSuccess === true || $responseSuccess === "True" || $responseSuccess === 1 || $responseSuccess === "1" || (isset($data['response']['name']) && !empty($data['response']['name']))) 
						{ 
							$existingCreated = $courierWarehouse->created ?? [];
							if (is_string($existingCreated)) {
								$existingCreated = json_decode($existingCreated, true) ?? [];
							}
							$existingCreated['delhivery_b2b_status'] = 1;

							$courierWarehouse->created = $existingCreated;
							$courierWarehouse->shipping_id = $shippingCompany->id;
							$courierWarehouse->api_response = $data['response'];
							$courierWarehouse->save(); 
						}
					}
				}
			}
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
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			DB::beginTransaction();
			try { 
				
				$labelOptions = [];
				foreach(['hide_contact', 'hide_address', 'hide_mobile', 'hide_product', 'hide_weight'] as $label_option)
				{
					$labelOptions[$label_option] = 	in_array($label_option, $request->label_options ?? []) ? true : false;
				}
				$courierWarehouse = CourierWarehouse::findOrFail($id); 
				$courierWarehouse->fill($request->all()); 
				$courierWarehouse->label_options = $labelOptions;
				$courierWarehouse->updated_at = $timestamp;

				// Check if warehouse name or address has changed
				$shouldUpdateAPI = $courierWarehouse->isDirty(['address', 'contact_number', 'contact_name', 'contact_email', 'company_name']);

				// Save updates
				$courierWarehouse->save();

				// If name or address changed, update via API
				if ($shouldUpdateAPI) {
					$shippingCompanies = ShippingCompany::whereIn('id', [1, 2, 3, 4, 5])->where('status', 1)->get();
					$this->updateWareHouseByAPI($courierWarehouse, $shippingCompanies);
				}

				DB::commit(); 
				return $this->successResponse($courierWarehouse, 'The warehouse has been successfully updated.'); 
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to update warehouse. Please try again.');   
			}
		}
 
		public function updateWareHouseByAPI($courierWarehouse, $shippingCompanies)
		{
			if ($shippingCompanies->isNotEmpty()) {
				foreach ($shippingCompanies as $shippingCompany) {
					if (in_array($shippingCompany->id, [1, 3, 4])) {
						$data = $this->delhiveryB2CService->updateWarehouse($courierWarehouse, $shippingCompany);
						if (!isset($data['success']) || !$data['success']) {
							continue;
						}

						$responseSuccess = $data['response']['success'] ?? false;
						if ($responseSuccess === true || $responseSuccess === "True" || $responseSuccess === 1 || $responseSuccess === "1") {

							$existingCreated = $courierWarehouse->created ?? [];  
							if (is_string($existingCreated)) {
								$existingCreated = json_decode($existingCreated, true) ?? [];
							} 
							$existingCreated['delhivery_status'] = 1;  
							
							$courierWarehouse->created = $existingCreated;
							$courierWarehouse->shipping_id   = $shippingCompany->id;
							$courierWarehouse->api_response  = $data['response'];
							$courierWarehouse->save();  
						}
					} elseif ($shippingCompany->id == 5) {
						$data = $this->delhiveryService->updateWarehouse($courierWarehouse, $shippingCompany);
						if (!isset($data['success']) || !$data['success']) {
							continue;
						}

						$responseSuccess = $data['response']['success'] ?? false;
						if ($responseSuccess === true || $responseSuccess === "True" || $responseSuccess === 1 || $responseSuccess === "1" || (isset($data['response']['name']) && !empty($data['response']['name']))) {

							$existingCreated = $courierWarehouse->created ?? [];  
							if (is_string($existingCreated)) {
								$existingCreated = json_decode($existingCreated, true) ?? [];
							} 
							$existingCreated['delhivery_b2b_status'] = 1;  
							
							$courierWarehouse->created = $existingCreated;
							$courierWarehouse->shipping_id   = $shippingCompany->id;
							$courierWarehouse->api_response  = $data['response'];
							$courierWarehouse->save();  
						}
					}
				}
			}
		}
		
		//customer
		public function customerList(Request $request)
		{ 
			$offset = $request->input('offset');
			$limit = $request->input('limit'); 
			$search = $request->input('search');
		 
			$user = Auth::user();
			$role = $user->role;
			$userId = $user->id;

			// Base Query
			$query = Customer::with(['user:id,name', 'customerAddresses'])
				->select('customers.*', DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"));
			
			// Always restrict by user_id if the role is 'user'
			if ($role === "user") {
				$query->where('user_id', $userId);
			}
			 
			// Apply search filter (matching Web logic)
			if (!empty($search)) { 
				$query->where(function ($q) use ($search) {
					$q->where('first_name', 'LIKE', "%{$search}%")
						->orWhere('last_name', 'LIKE', "%{$search}%")
						->orWhere('gst_number', 'LIKE', "%{$search}%") // Added GST search
						->orWhere('mobile', 'LIKE', "%{$search}%")
						->orWhere('email', 'LIKE', "%{$search}%")
						->orWhereHas('customerAddresses', function($sq) use ($search) { // Added address search
							$sq->where('address', 'LIKE', "%{$search}%")
								->orWhere('city', 'LIKE', "%{$search}%")
								->orWhere('state', 'LIKE', "%{$search}%")
								->orWhere('zip_code', 'LIKE', "%{$search}%")
								->orWhere('country', 'LIKE', "%{$search}%");
						})
						->orWhereDate('created_at', 'LIKE', "%{$search}%");
				});
			}
			 
			$query->latest('id');

			// Fetch paginated records
			if (isset($offset) && isset($limit)) {
				$query->offset($offset)->limit($limit);
			}

			$customers = $query->get();

			return $this->successResponse($customers, 'customer fetched successfully.');
		}

		public function storeCustomerAddress(Request $request)
		{
			// ✅ 1. Validate input
			$validator = Validator::make($request->all(), [
				'customer_id' => 'required|integer|exists:customers,id',
				'address'     => 'required|array|min:1',
				'address.*'   => 'required|string',
				'zip_code'    => 'required|array|min:1',
				'zip_code.*'  => 'required|string',
				'country'     => 'required|array|min:1',
				'country.*'   => 'required|string',
				'state'       => 'required|array|min:1',
				'state.*'     => 'required|string',
				'city'        => 'required|array|min:1',
				'city.*'      => 'required|string',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			try {
				DB::beginTransaction();
				$customer = Customer::findOrFail($request->customer_id);

				if ($request->has('address') && count($request->address) > 0) {
					$addresses = [];
					foreach ($request->address as $key => $addr) {
						$addresses[] = [
							'customer_id' => $customer->id,
							'address'     => $addr,
							'country'     => $request->country[$key],
							'state'       => $request->state[$key],
							'city'        => $request->city[$key],
							'zip_code'    => $request->zip_code[$key],
							'status'      => 1,
							'created_at'  => now(),
							'updated_at'  => now(),
						];
					}
					CustomerAddress::insert($addresses);
				}

				$customerAddressId = optional($customer->latestCustomerAddress)->id ?? '';
				DB::commit();

				return $this->successResponse([
					'customer_id' => $customer->id,
					'customer_address_id' => $customerAddressId,
					'latest_address' => $customer->latestCustomerAddress
				], 'The customer address has been successfully added.');

			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to add customer address. Please try again: ' . $e->getMessage(), 500);
			}
		}


		public function storeCustomer(Request $request)
		{
			// Validate input, including warehouse name uniqueness
			$validator = Validator::make($request->all(), [
				'first_name' => 'required|string|max:255',
				'last_name'  => 'required|string|max:255',
				'email'      => 'nullable|email|max:255',
				'mobile' => 'required|digits:10|unique:customers,mobile',
				'alternate_mobile' => 'nullable|digits:10', 
				'address'    => 'required|array|min:1',
				'address.*'  => 'required|string|max:500', 
				'zip_code'   => 'required|array|min:1',
				'zip_code.*' => 'required|string|max:20', 
				'country'    => 'required|array|min:1',
				'country.*'  => 'required|string|max:100', 
				'state'      => 'required|array|min:1',
				'state.*'    => 'required|string|max:100', 
				'city'       => 'required|array|min:1',
				'city.*'     => 'required|string|max:100', 
				'gst_number' => 'nullable|string|max:50',
				'status'     => 'sometimes|in:0,1',
			]);
 
			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}
			
			$userId = Auth::id();
			$data = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'aadhar_front', 'aadhar_back', 'pancard');
			$data['user_id'] = $userId;
  
			try {
				DB::beginTransaction();
				
				if ($request->hasFile('aadhar_front')) { 
					$file = $request->file('aadhar_front');  
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$path = $file->storeAs('public/customer/aadhar', $filename);  
					$data['aadhar_front'] = $filename;
				}
				if ($request->hasFile('aadhar_back')) { 
					$file = $request->file('aadhar_back');  
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$path = $file->storeAs('public/customer/aadhar', $filename);  
					$data['aadhar_back'] = $filename;
				}
				if ($request->hasFile('pancard')) { 
					$file = $request->file('pancard');  
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$path = $file->storeAs('public/customer/pancard', $filename);  
					$data['pancard'] = $filename;
				}
				
				$customer = Customer::create($data);
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

				return $this->successResponse([
					'customer' => $customer,
					'customer_id' => $customer->id,
					'customer_address_id' => $customerAddressId
				], 'The customer has been successfully added.');  
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to add customer. Please try again.');   
			}
		}
		   
		public function updateCustomer(Request $request, $id)
		{
			// Validate input, including warehouse name uniqueness
			$validator = Validator::make($request->all(), [
				'first_name' => 'required|string|max:255',
				'last_name'  => 'required|string|max:255',
				'email' => 'nullable|email|max:255',
				'mobile' => [
					'required',
					'digits:10',
					Rule::unique('customers', 'mobile')->ignore($id),
				],
				'alternate_mobile' => 'nullable|digits:10',
				'address'    => 'required|array|min:1',
				'address.*'  => 'required|string|max:500', 
				'zip_code'   => 'required|array|min:1',
				'zip_code.*' => 'required|string|max:20', 
				'country'    => 'required|array|min:1',
				'country.*'  => 'required|string|max:100', 
				'state'      => 'required|array|min:1',
				'state.*'    => 'required|string|max:100', 
				'city'       => 'required|array|min:1',
				'city.*'     => 'required|string|max:100', 
				'gst_number' => 'nullable|string|max:50',
				'status'     => 'required|in:0,1',
			]);
 
			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}
			
			$userId = Auth::id();
			$data = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'id', 'aadhar_front', 'aadhar_back', 'pancard');
			$data['user_id'] = $userId;

		  
			try {
				DB::beginTransaction();
				$customer = Customer::findOrFail($id); // Fetch the customer record
			 
				if ($request->hasFile('aadhar_front')) { 
					// Delete the old file if it exists
					if (!empty($customer->aadhar_front) && Storage::exists("public/customer/aadhar/{$customer->aadhar_front}")) {
						Storage::delete("public/customer/aadhar/{$customer->aadhar_front}");
					}

					// Store the new file
					$file = $request->file('aadhar_front');  
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$file->storeAs('public/customer/aadhar', $filename);  
					$data['aadhar_front'] = $filename;
				}

				if ($request->hasFile('aadhar_back')) { 
					if (!empty($customer->aadhar_back) && Storage::exists("public/customer/aadhar/{$customer->aadhar_back}")) {
						Storage::delete("public/customer/aadhar/{$customer->aadhar_back}");
					}

					$file = $request->file('aadhar_back');  
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$file->storeAs('public/customer/aadhar', $filename);  
					$data['aadhar_back'] = $filename;
				}

				if ($request->hasFile('pancard')) { 
					if (!empty($customer->pancard) && Storage::exists("public/customer/pancard/{$customer->pancard}")) {
						Storage::delete("public/customer/pancard/{$customer->pancard}");
					}

					$file = $request->file('pancard');  // FIXED: Removed incorrect `public/pancard`
					$filename = time() . '_' . $file->getClientOriginalName(); 
					$file->storeAs('public/customer/pancard', $filename);  
					$data['pancard'] = $filename;
				} 

				// Update customer record with new file names
				$customer->update($data);
				
				if ($request->has('address') && count($request->address) > 0) { 
					CustomerAddress::where('customer_id', $id)->whereNotIn('id', $request->id ?? [])->delete();

					foreach ($request->address as $key => $addr) {
						$addressData = [
							'address' => $addr,
							'country' => $request->country[$key],
							'state' => $request->state[$key],
							'city' => $request->city[$key],
							'zip_code' => $request->zip_code[$key],
							'updated_at' => now(),
						];

						if (!empty($request->id[$key])) {
							CustomerAddress::whereId($request->id[$key])->update($addressData);
						} else {
							$addressData['customer_id'] = $id;
							$addressData['status'] = 1;
							$addressData['created_at'] = now();
							CustomerAddress::create($addressData);
						}
					}
				}
				
				DB::commit(); 
				return $this->successResponse($customer, 'The customer has been successfully updated.');  
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to add customer. Please try again.');   
			}
		}
		
		public function deleteCustomer($id)
		{
			try {
				DB::beginTransaction();
				
				$customer = Customer::find($id);
				if (!empty($customer->aadhar_front) && Storage::exists("public/customer/aadhar/{$customer->aadhar_front}")) {
					Storage::delete("public/customer/aadhar/{$customer->aadhar_front}");
				}
				if (!empty($customer->aadhar_back) && Storage::exists("public/customer/aadhar/{$customer->aadhar_back}")) {
					Storage::delete("public/customer/aadhar/{$customer->aadhar_back}");
				}
				if (!empty($customer->pancard) && Storage::exists("public/customer/pancard/{$customer->pancard}")) {
					Storage::delete("public/customer/pancard/{$customer->pancard}");
				}
				$customer->customerAddresses()->delete();
				$customer->delete(); 
				
				DB::commit(); 
				return $this->successResponse([], 'The customer has been successfully deleted.');  
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to delete customer. Please try again.');   
			}
		}
		
		// pickup request
		public function pickupRequestList(Request $request)
		{
			$user = Auth::user();
			$warehouses = CourierWarehouse::where('user_id', $user->id)
				->where('warehouse_status', 1)
				->get();

			$shippingCompanies = ShippingCompany::whereIn('id', [1])
				->where('status', 1)
				->get(['id', 'name']);

            // Time Slots generation
            $start = new \DateTime('09:00');
            $end = new \DateTime('21:00'); // 9:00 PM
            $interval = new \DateInterval('PT3H'); // 1-hour gap or 3-hour per web controller
            $period = new \DatePeriod($start, $interval, $end);
    
            $timeSlots = [];
            foreach ($period as $dt) {
                $slotStart = $dt->format('h:i A');
                $slotEnd = $dt->add($interval)->format('h:i A');
                $timeSlots[] = "$slotStart - $slotEnd";
            }

            // Pagination Logic
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);
            
            $query = PickupRequest::with(['warehouse', 'user', 'shippingCompany'])
                ->where('user_id', $user->id)
                ->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                     $q->where('pickup_id', 'LIKE', "%{$search}%");
                });
            }

            $total = $query->count();
            $requests = $query->offset($offset)->limit($limit)->get();

            // Transform data if needed, or send as is
            $transformedRequests = $requests->map(function($value) {
                return [
                    'id' => $value->id,
                    'shipping_company_name' => $value->shippingCompany->name ?? '',
                    'pickup_id' => $value->pickup_id,
                    'warehouse_name' => $value->warehouse->warehouse_name ?? '',
                    'warehouse_address' => $value->warehouse->address ?? '',
                    'expected_package_count' => $value->expected_package_count,
                    'pickup_date' => $value->pickup_date,
                    'pickup_start_time' => $value->pickup_start_time,
                    'pickup_end_time' => $value->pickup_end_time,
                    'status' => $value->status,
                    'status_label' => $value->status == 1 ? 'Open' : 'Cancelled',
                    'created_at' => $value->created_at->toDateTimeString()
                ];
            });

			return $this->successResponse([
                'warehouses' => $warehouses,
                'shipping_companies' => $shippingCompanies,
                'time_slots' => $timeSlots,
                'pickup_requests' => $transformedRequests,
                'total' => $total
            ], 'Pickup request data fetched successfully.');
		}
		

		
		public function createPickupRequest(Request $request)
		{
            $validator = Validator::make($request->all(), [
                'shipping_company_id' => 'required',
                'warehouse_id' => 'required',
                'pickup_date' => 'required|date',
                'expected_package_count' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return $this->validateResponse('Validation failed', 422, $validator->errors());
            }

			DB::beginTransaction();
			
			try {
				// Validate if the shipping company exists
				$shippingCompany = ShippingCompany::where('status', 1)->where('id', $request->shipping_company_id)->first();
				
				if (!$shippingCompany) {
                    return $this->errorResponse('Shipping company not found or inactive.', 404);
				}
				
				if($shippingCompany->id == 1) // Delhivery B2C (was 3 or 1 in other logic, checking web controller it checks for id=1)
				{
					// Call the external service to create a pickup request
					$data = $this->delhiveryB2CService->createPickupRequest($request->all(), $shippingCompany); 
					 
					// Handle API response errors
					if (!($data['success'] ?? false)) {
						$errorMsg = ($data['response']['pickup_location'] 
									?? $data['response']['pickup_date']
									?? $data['response']['prepaid'])
									?? ($data['response']['error']['message'] ?? 'An error occurred.');
                        return $this->errorResponse($errorMsg, 400);
					}
 
					// Extract response data
					$responseData = $data['response'] ?? null; 
					if(isset($responseData['error']))
					{
                        return $this->errorResponse($responseData['error']['message'], 400);
					}
					if (!$responseData || empty($responseData['pickup_id'])) {
                        return $this->errorResponse('Pickup ID not received from the API.', 500);
					}
				}
                // Only id 1 is supported based on web controller, but keeping structure if needed for others.
                else {
                    return $this->errorResponse('Shipping company integration not supported for pickup yet.', 400);
                }

				// Prepare data for insertion
				$requestedData = $request->except('_token');
                $requestedData['pickup_start_time'] = $request->pickup_start_time ? date('H:i:s', strtotime($request->pickup_start_time)) : '';
			    $requestedData['pickup_end_time'] = $request->pickup_end_time ? date('H:i:s', strtotime($request->pickup_end_time)) : '';
				$requestedData['pickup_id'] = $responseData['pickup_id'];
				$requestedData['user_id'] = Auth::id();
				$requestedData['status'] = 1;
				$requestedData['created_at'] = now();
				$requestedData['updated_at'] = now();
				
				// Save to the database
				$pickupRequest = PickupRequest::create($requestedData);
				
				DB::commit();

                return $this->successResponse($pickupRequest, 'Pickup request created successfully.');
			} catch (\Exception $e) {
				DB::rollback(); 
                return $this->errorResponse('An unexpected error occurred. ' . $e->getMessage(), 500);
			}
		} 
		
		public function cancelPickupRequest($pickupId)
		{
			DB::beginTransaction();
			
			try {
				// Validate if the shipping company exists
				$pickupRequest = PickupRequest::find($pickupId);
				if (!$pickupRequest) {
					return redirect()->back()->with('error', 'Pickup Request not found.');
				}
				
				// Validate if the shipping company exists
				$shippingCompany = ShippingCompany::where('status', 1)->where('id', 1)->first();
				if (!$shippingCompany) {
					return redirect()->back()->with('error', 'Shipping company not found or inactive.');
				}
				
				// Call the external service to create a pickup request
				$data = $this->delhiveryService->cancelPickupRequest($pickupRequest->pickup_id, $shippingCompany); 
				
				// Handle API response errors
				if (!($data['success'] ?? false)) {
					$errorMsg = $data['response']['errors'][0]['message'] 
								?? ($data['response']['error']['message'] ?? 'An error occurred.');
					return redirect()->back()->with('error', $errorMsg);
				}

				// Ensure the response contains success confirmation
				if (isset($data['response']['success']) && !$data['response']['success']) { 
					return redirect()->back()->with('error', $data['response']['error']['message'] ?? 'An error occurred.');
				}
				
				// Extract response data
				$responseMsg = $data['response']['data']['message'] ?? 'Cancelled pickup request successfully';
				 
				$pickupRequest->update(['status' => 0, 'updated_at' => now()]);  
				DB::commit();

				return redirect()->back()->with('success', $responseMsg);
			} catch (\Exception $e) {
				DB::rollback(); 
				return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.');
			}
		} 
	}
