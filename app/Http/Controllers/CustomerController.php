<?php
	
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use App\Models\Customer;
	use App\Models\Order;
	use App\Models\CustomerAddress;
	use App\Models\CourierWarehouse; 
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\File;
	use Illuminate\Support\Facades\Storage;
	 
	class CustomerController extends Controller
	{
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
			return view('customer.index');
		}
		
		public function ajaxCustomer(Request $request)
		{
			$draw = $request->get('draw');
			$start = $request->get("start");
			$limit = $request->get("length");
			
			$order_arr = $request->get('order');
			$column_arr = $request->get('columns');
			$search = $request->get('search') ?? $request->get('search.value') ?? '';
			
			$columnIndex = $order_arr[0]['column']; 
			$orderColumn = $column_arr[$columnIndex]['data']; 
			$dir = $order_arr[0]['dir']; 
			
			// Map columns to database fields
			$sortMapping = [
				'id' => 'customers.id',
				'customer_name' => 'customers.id', // sorting by concat name is tricky, use id
				'mobile' => 'customers.mobile',
				'email' => 'customers.email',
				'address' => 'ca.address',
				'created_at' => 'customers.created_at',
				'action' => 'customers.id'
			];

			$orderColumn = $sortMapping[$orderColumn] ?? 'customers.id';
			
			$user = Auth::user();
			$role = $user->role;
			$userId = $user->id;
			$canManageCustomer = in_array($role, ['user', 'admin', 'master_admin'], true)
				|| config('permission.client.edit')
				|| config('permission.client.delete');
			
			// Base Query with join for sorting
			$query = Customer::with('latestCustomerAddress')
			->select('customers.*', DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"))
			->leftJoin('customer_addresses as ca', function($join) {
				$join->on('customers.id', '=', 'ca.customer_id')
					->whereRaw('ca.id = (SELECT MAX(id) FROM customer_addresses WHERE customer_id = customers.id)');
			})
			->when($role === "user", fn($q) => $q->where('customers.user_id', $userId));
			
			$totalData = $query->count();
			
			// Apply search filter
			if (!empty($search)) { 
				$query->where(function ($q) use ($search) {
					$q->where('customers.first_name', 'LIKE', "%{$search}%")
					->orWhere('customers.last_name', 'LIKE', "%{$search}%")
					->orWhere('customers.gst_number', 'LIKE', "%{$search}%")
					->orWhere('customers.mobile', 'LIKE', "%{$search}%")
					->orWhere('customers.email', 'LIKE', "%{$search}%") 
					->orWhere('ca.address', 'LIKE', "%{$search}%")
					->orWhere('ca.city', 'LIKE', "%{$search}%")
					->orWhere('ca.state', 'LIKE', "%{$search}%")
					->orWhere('customers.created_at', 'LIKE', "%{$search}%");
				});
			}
			
			$totalFiltered = $query->count();
			
			// Fetch paginated records
			$customers = $query->with('user:id,name')
			->offset($start)
			->limit($limit)
			->orderBy($orderColumn, $dir)
			->get();
			
			$data = [];
			foreach ($customers as $index => $customer) {
				$actionButtons = '';
					
				 if ($canManageCustomer) {
					$actionButtons .= '<a href="' . url('customer/edit', $customer->id) . '" 
						class="btn btn-icon waves-effect waves-light action-icon mr-1">
						<i class="mdi mdi-pencil"></i>
					</a>';
				}

				if ($canManageCustomer) {
					$actionButtons .= '<a href="' . url('customer/delete', $customer->id) . '" 
						class="btn btn-icon waves-effect waves-light action-icon" 
						data-toggle="tooltip" data-placement="bottom" title="Delete" 
						onClick="deleteRecord(this, event);"> 
						<i class="mdi mdi-trash-can-outline"></i> 
					</a>';
				}
				 
				$customerAddress = $customer->latestCustomerAddress ?? null; 
				$customer_address = $customerAddress 
				? implode(' ', array_filter([
					trim($customerAddress->address ?? ''),
					trim($customerAddress->city ?? ''),
					trim($customerAddress->state ?? ''),
					trim($customerAddress->zip_code ?? '')
				])) . (isset($customerAddress->country) ? ', ' . trim($customerAddress->country) : '')
				: '';
			 
				$data[] = [
					'id' => $start + $index + 1, 
					'customer_name' => $customer->customer_name,
					'mobile' => $customer->mobile,
					'email' => $customer->email,  
					'address' => $customer_address,  
					'created_at' => date('d M Y', strtotime($customer->created_at)),
					'action' => $actionButtons
				];
			}
 
			return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
			]);
		} 
		
		public function addCustomer()
		{
			return view('customer.add');
		}
		
		public function storeCustomer(Request $request)
		{ 
			$userId = Auth::id();
			$data = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'aadhar_front', 'aadhar_back', 'pancard');
			$data['user_id'] = $userId;

			if (Customer::where('mobile', $request->mobile)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The mobile number already exists.']);
			}

			// if (Customer::where('email', $request->email)->exists()) {
			// 	return response()->json(['status' => 'error', 'msg' => 'The email already exists.']);
			// }

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
					$path = $file->storeAs('public/customer/panacrd', $filename);  
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
				return response()->json(['status' => 'success', 'msg' => 'The customer has been successfully added.', 'customer_id' => $customer->id, 'customer_address_id' => $customerAddressId]);
			} catch (\Exception $e) {
				DB::rollBack();
				return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
			}
		}
		  
		public function editCustomer($id)
		{
			$customer = Customer::with('customerAddresses')->findOrFail($id);
			$this->authorizeCustomerAccess($customer);
			$customerAddresses = $customer->customerAddresses;
			return view('customer.edit', compact('customer', 'customerAddresses'));
		}
		
		public function updateCustomer(Request $request, $id)
		{
			$userId = Auth::id();
			$data = $request->except('_token', 'address', 'country', 'state', 'city', 'zip_code', 'id', 'aadhar_front', 'aadhar_back', 'pancard');
			$data['user_id'] = $userId;

			if (Customer::where('id', '!=', $id)->where('mobile', $request->mobile)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The mobile number already exists.']);
			}

			// if (Customer::where('id', '!=', $id)->where('email', $request->email)->exists()) {
			// 	return response()->json(['status' => 'error', 'msg' => 'The email already exists.']);
			// }

			try {
				DB::beginTransaction();
				$customer = Customer::findOrFail($id); // Fetch the customer record
				$this->authorizeCustomerAccess($customer);
			 
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
					// Delete addresses not present in the updated list
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
				return response()->json(['status' => 'success', 'msg' => 'The customer has been successfully updated.']);
			} catch (\Exception $e) {
				DB::rollBack();
				return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
			}
		}
		
		public function deleteCustomer($id)
		{
			try {
				DB::beginTransaction();
				
				$customer = Customer::find($id);
				$this->authorizeCustomerAccess($customer);
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
				return redirect()->route('customer')->with('success', 'The customer has been successfully deleted.');
			} catch (\Exception $e) {
				DB::rollBack();
				return redirect()->route('customer')->with('error', 'Something went wrong.');
			}
		}

		private function authorizeCustomerAccess(?Customer $customer): void
		{
			if (!$customer) {
				abort(404);
			}

			$user = Auth::user();
			if (in_array($user->role, ['admin', 'master_admin'], true) || (int) $customer->user_id === (int) $user->id) {
				return;
			}

			abort(403, 'Permission denied.');
		}
	}
