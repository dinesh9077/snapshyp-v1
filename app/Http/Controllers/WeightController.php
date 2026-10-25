<?php
	
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use App\Models\Order;
	use App\Models\User;
	use App\Models\weightDescrepencyHistory;
	use App\Models\ExcessWeight;
	use App\Models\weightDescrepency;
	use App\Models\WeightFreeze;
	use App\Models\Notification;
	use App\Models\Billing;
	use App\Models\WeightDiscrepancyRemark;
	use Maatwebsite\Excel\Facades\Excel;
	use Illuminate\Support\Facades\Storage;
	use File,Auth,DB,Helper;
	class WeightController extends Controller
	{
		public function __construct()
		{
			$this->middleware('auth');
		}

		public function descrepenciesAll(Request $request)
		{
			// Permission check (early return)
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission Denied');
			}

			/**
			 * Read inputs once to avoid repeated property access
			 */
			$userId = $request->input('user_id');
			$weightStatus = $request->input('weight_status');
			$weightUpdateDate = $request->input('weight_update_date');
			$date = $request->input('date');
			$search = $request->input('search');
			$taskId = $request->input('task_id');

			// Fetch active users (only id + name) for filters/dropdowns
			$users = User::where('status', 1)
				->select('id', 'name')
				->get();

			/*
			* Build the base query:
			* - eager load relationships but limit selected columns where possible
			* - ensure orders with AWB only
			*/
			$query = Order::with([
				'user:id,name',
				'orderItems',
				'excessWeight',
				'weightDescripencies'
			])
			->whereNotNull('awb_number');

			// apply filters
			$query->when($userId, fn($q) => $q->where('orders.user_id', $userId));
			$query->when($weightStatus, fn($q) => $q->where('orders.weight_status', $weightStatus));

			// Keep behaviour: both weight_update_date and date target orders.weight_update_date
			if ($weightUpdateDate) {
				$query->whereDate('orders.weight_update_date', $weightUpdateDate);
			}
			if ($date) {
				$query->whereDate('orders.weight_update_date', $date);
			}

			// search across a few columns
			$query->when($search, function ($q) use ($search) {
				$q->where(function ($sub) use ($search) {
					$sub->where('orders.awb_number', 'LIKE', "%{$search}%")
						->orWhere('orders.id', 'LIKE', "%{$search}%")
						->orWhere('orders.order_prefix', 'LIKE', "%{$search}%");
				});
			});

			// If a specific task id is provided, filter it (taskId overrides paged listing)
			if ($taskId) {
				$query->where('orders.id', $taskId);
			}

			$hasAnyFilter = $search || $userId || $weightUpdateDate || $date || $taskId || $weightStatus;
			// show both raised and unraised weights if searching, otherwise prioritize raised weights
			if (!$hasAnyFilter) {
				$query->where('is_raise_weight', 1);
			}
			// If filters are present, we don't restrict is_raise_weight so that user can find what they are looking for

			// ordering + pagination (extract perPage if passed, default 10)
			$perPage = (int) $request->input('per_page', 10);
			$orders = $query->orderByDesc('orders.id')->paginate(max(1, $perPage));

			return view('weight.descrepensies.all_descrepencies', compact('users', 'orders'));
		}
  
		public function raiseExcessWeight(Request $request)
		{
			try {
				DB::beginTransaction(); // Start transaction
				$orderId = $request->order_id;
				
				// Prepare data
				$data = $request->except('_token', 'product_images');
				$timestamp = now(); // Use Laravel's now() for cleaner code
				$data['created_at'] = $timestamp;
				$data['updated_at'] = $timestamp;
				$data['status'] = 'Accepted';
				
				$imagePaths = []; 
				if ($request->hasFile('product_images')) {
					foreach ($request->file('product_images') as $image) {
						// Generate a unique filename
						$filename = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

						// Store the file in the "weight_descrepency" folder within "storage/app/public"
						$path = $image->storeAs('weight_descrepency/'.$orderId, $filename, 'public');

						// Save the file path in an array
						$imagePaths[] = $path;
					}
				} 
				$data['product_images'] = $imagePaths;
		
				// Insert Excess Weight Data
				ExcessWeight::create($data);

				// Update Order Status
		    	$order = Order::find($orderId);

				$order->update([
					'weight_status' => 'Accepted',
					'is_raise_weight' => 1,
					'weight_update_date' => $timestamp
				]);

				// Deduct from wallet
				$this->processWeightDeduction($order, $request->excess_charge, config('setting.company_name'));

				DB::commit(); // Commit transaction if all operations succeed

				return redirect('weight/descripencies/all')->with('success', 'The raise excess weight has been added and auto-accepted successfully.');

			} catch (\Exception $e) {
				DB::rollBack(); // Rollback on failure
				return back()->with('error', 'An error occurred while raising excess weight: ' . $e->getMessage());
			}
		}
  
		public function bulkImportExcessWeight(Request $request)
		{
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission Denied');
			}

			$request->validate([
				'bulk_excel' => 'required|file|mimes:xlsx,xls,csv,txt',
			]);

			try {
				$sheets = Excel::toArray(new \stdClass(), $request->file('bulk_excel'));
				$rows = $sheets[0] ?? [];

				if (empty($rows)) {
					return back()->with('error', 'No rows found in the uploaded file.');
				}

				// Detect header row from first few rows to support both:
				// 1) legacy single-header format
				// 2) new two-row format (first row contains auto/* markers)
				$headerMap = [];
				$headerDetected = false;
				$headerRowIndex = null;

				foreach (array_slice($rows, 0, 5, true) as $candidateIndex => $candidateRow) {
					$candidateMap = [];
					foreach ((array) $candidateRow as $idx => $rawVal) {
						$clean = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $rawVal)));
						if ($clean !== '') {
							$candidateMap[$clean] = $idx;
						}
					}

					$hasAwb = isset($candidateMap['awbnumber']) || isset($candidateMap['awb']) || isset($candidateMap['waybill']);
					$hasChargeable = isset($candidateMap['chargeableweight']) || isset($candidateMap['chargedweight']) || isset($candidateMap['courierweight']) || isset($candidateMap['delhiveryweight']);

					if ($hasAwb && $hasChargeable) {
						$headerMap = $candidateMap;
						$headerDetected = true;
						$headerRowIndex = $candidateIndex;
						break;
					}
				}

				if ($headerDetected && $headerRowIndex !== null) {
					$rows = array_slice($rows, $headerRowIndex + 1);
				} else {
					// Fallback to legacy 4-column mapping (AWB, Applied, Chargeable, Excess Charge)
					$headerMap['awbnumber'] = 0;
					$headerMap['appliedweight'] = 1;
					$headerMap['chargeableweight'] = 2;
					$headerMap['excesscharge'] = 3;
				}

				$findHeaderIndex = function (array $map, array $exactKeys, array $prefixKeys = []) {
					foreach ($exactKeys as $key) {
						if (isset($map[$key])) {
							return $map[$key];
						}
					}
					foreach ($map as $key => $idx) {
						foreach ($prefixKeys as $prefix) {
							if (strpos($key, $prefix) === 0) {
								return $idx;
							}
						}
					}
					return -1;
				};

				$idxAwb = $findHeaderIndex($headerMap, ['awbnumber', 'awb', 'waybill'], ['awb', 'waybill']);
				$idxAppliedWt = $findHeaderIndex($headerMap, ['appliedweight', 'appliedwt', 'customerweight', 'sellerweight'], ['appliedweight', 'appliedwt']);
				$idxChargeableWt = $findHeaderIndex($headerMap, ['chargeableweight', 'chargedweight', 'delhiveryweight', 'courierweight'], ['chargeableweight', 'chargedweight']);
				$idxExcessChrg = $findHeaderIndex($headerMap, ['excesscharge', 'amount', 'chargedamount', 'billingamount'], ['excesscharge']);

				$successCount = 0;
				$failedRows = [];

				foreach ($rows as $index => $row) {
					$rowNumber = $headerDetected ? ($index + ($headerRowIndex + 2)) : ($index + 1);

					$awbRawValue = $idxAwb >= 0 ? ($row[$idxAwb] ?? '') : '';
					$awbNumber = $this->normalizeBulkAwb($awbRawValue);
					$appliedWeight = $idxAppliedWt >= 0 ? trim((string) ($row[$idxAppliedWt] ?? '')) : '';
					$chargeableWeight = $idxChargeableWt >= 0 ? trim((string) ($row[$idxChargeableWt] ?? '')) : '';
					$excessCharge = $idxExcessChrg >= 0 ? trim((string) ($row[$idxExcessChrg] ?? '')) : '';

					if ($awbNumber === '' && $appliedWeight === '' && $chargeableWeight === '' && $excessCharge === '') {
						continue;
					}

					// Explicit check to skip row if it matches header keywords (double protection)
					if (in_array(strtolower($awbNumber), ['awb number', 'awb', 'waybill', 'awbno'])) {
						continue;
					}

					if ($awbNumber === '') {
						$failedRows[] = "Row {$rowNumber}: AWB Number is required.";
						continue;
					}

					$order = Order::where('awb_number', (string) $awbNumber)->first();
					if (!$order) {
						$failedRows[] = "Row {$rowNumber}: Order not found locally for AWB '{$awbNumber}'.";
						continue;
					}

					$appliedWeightValue = preg_replace('/[^0-9.]/', '', $appliedWeight);
					$chargeableWeightValue = preg_replace('/[^0-9.]/', '', $chargeableWeight);
					$excessChargeValue = preg_replace('/[^a-zA-Z0-9.]/', '', $excessCharge); // allow "auto"

					if ($appliedWeightValue === '' || $chargeableWeightValue === '') {
						$failedRows[] = "Row {$rowNumber}: Applied Weight and Chargeable Weight are required.";
						continue;
					}

					try {
						DB::transaction(function () use ($order, $appliedWeightValue, $chargeableWeightValue, $excessChargeValue) {
							$timestamp = now();
							$excessWeightValue = max(0, $chargeableWeightValue - $appliedWeightValue);

							// Automated charge calculation if missing or "auto"
							$finalExcessCharge = 0;
							$isAutoExcessCharge = empty($excessChargeValue) || strtolower((string)$excessChargeValue) === 'auto';
							if ($isAutoExcessCharge) {
								$finalExcessCharge = $this->calculateAutoExcessCharge($order, $appliedWeightValue, $chargeableWeightValue);

								// Prevent silently saving 0 charge for positive discrepancy when rate is expected from card.
								if ($excessWeightValue > 0 && $finalExcessCharge <= 0) {
									throw new \RuntimeException('Auto excess charge could not be calculated from rate card. Please check rate card/zone or enter manual Excess Charge.');
								}
							} else {
								$finalExcessCharge = floatval(preg_replace('/[^0-9.]/', '', $excessChargeValue));
							}

							ExcessWeight::updateOrCreate(
								['order_id' => $order->id],
								[
									'user_id' => $order->user_id,
									'chargeable_weight' => $chargeableWeightValue,
									'excess_weight' => $excessWeightValue,
									'excess_charge' => $finalExcessCharge,
									'status' => 'Accepted',
									'updated_at' => $timestamp,
								]
							);

							Order::where('id', $order->id)->update([
								'weight_status' => 'Accepted',
								'is_raise_weight' => 1,
								'weight_update_date' => $timestamp,
							]);

							$this->processWeightDeduction($order, $finalExcessCharge, 'Bulk Import Auto');
						});

						$successCount++;
					} catch (\Throwable $e) {
						$failedRows[] = "Row {$rowNumber}: " . $e->getMessage();
					}
				}

				if ($successCount === 0) {
					$errorMessage = 'No rows were imported successfully.';
					if (!empty($failedRows)) {
						$errorMessage .= ' ' . implode(' | ', array_slice($failedRows, 0, 5));
					}
					return back()->with('error', $errorMessage);
				}

				$message = "{$successCount} row(s) imported successfully.";
				if (!empty($failedRows)) {
					$message .= " Failed rows: " . count($failedRows) . '. ' . implode(' | ', array_slice($failedRows, 0, 3));
				}

				return redirect('weight/descripencies/all')->with('success', $message);
			} catch (\Throwable $e) {
				return back()->with('error', 'Failed to import weight discrepancies: ' . $e->getMessage());
			}
		}

		private function calculateAutoExcessCharge($order, $appliedWeight, $chargeableWeight)
		{
			$rateCardService = new \App\Services\RateCardService();
			$user = User::find($order->user_id);
			$shippingCompany = $order->shippingCompany;
			
			if (!$user || !$shippingCompany) {
				return 0; // Fallback
			}

			$pickupPincode = optional($order->warehouse)->zip_code;
			$deliveryPincode = optional($order->customerAddress)->zip_code;

			if (!$pickupPincode || !$deliveryPincode) {
				return 0; // Needs pincodes for calculation
			}

			// Dummy request objects for RateCardService
			$reqApplied = (object)[
				'pickup_code' => $pickupPincode,
				'delivery_code' => $deliveryPincode,
				'weight' => $appliedWeight,
				'payment_type' => $order->order_type,
				'cod_amount' => $order->cod_amount,
				'order_id' => $order->id
			];

			$reqCharged = (object)[
				'pickup_code' => $pickupPincode,
				'delivery_code' => $deliveryPincode,
				'weight' => $chargeableWeight,
				'payment_type' => $order->order_type,
				'cod_amount' => $order->cod_amount,
				'order_id' => $order->id
			];

			// Calculate new rate for the heavier weight
			$ratesCharged = $rateCardService->getRate($user, $shippingCompany, $reqCharged, 0);
			$newTotal = 0;
			
			// Find the matching courier in the results
			if (!empty($ratesCharged)) {
				foreach ($ratesCharged as $r) {
					// Fuzzy matching for courier name if needed
					if ($r['shipping_company_id'] == $shippingCompany->id) {
						$newTotal = $r['total_charges'];
						break;
					}
				}
			}

			if ($newTotal <= 0) return 0;

			// Get original rate (best to use the one stored on the order)
			$originalTotal = $order->total_shipping_cost ?? $order->shipping_charge ?? 0;

			// If original total is missing or we want a fresh compare:
			if ($originalTotal <= 0) {
				$ratesApplied = $rateCardService->getRate($user, $shippingCompany, $reqApplied, 0);
				if (!empty($ratesApplied)) {
					foreach ($ratesApplied as $r) {
						if ($r['shipping_company_id'] == $shippingCompany->id) {
							$originalTotal = $r['total_charges'];
							break;
						}
					}
				}
			}

			$difference = $newTotal - $originalTotal;
			return max(0, round($difference, 2));
		}

		public function downloadBulkImportSample()
		{
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission Denied');
			}

			// New format: first row marks mandatory (*) and auto columns.
			$rows = [
				['auto', '*', 'auto', 'auto', '*', '*', 'auto', 'auto'],
				['Order ID', 'AWB Number', 'User Name', 'Courier Name', 'Applied Weight', 'Chargeable Weight', 'Excess Weight', 'Excess Charge (rate card for delivery, else manual)'],
				['', ' 1234567890123', '', '', '0.50', '1.00', '', 'auto'],
			];

			return $this->streamBulkImportCsvDownload('weight_discrepancy_bulk_sample.csv', $rows);
		}

		public function downloadBulkImportDummy()
		{
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission Denied');
			}

			// Fetch some real orders to provide valid sample data
			$realOrders = Order::whereNotNull('awb_number')
				->where('awb_number', '!=', '')
				->where('is_raise_weight', 0)
				->with('user:id,name')
				->latest()
				->limit(5)
				->get();

			$rows = [
				['auto', '*', 'auto', 'auto', '*', '*', 'auto', 'auto'],
				['Order ID', 'AWB Number', 'User Name', 'Courier Name', 'Applied Weight', 'Chargeable Weight', 'Excess Weight', 'Excess Charge (rate card for delivery, else manual)'],
			];

			if ($realOrders->count() > 0) {
				foreach ($realOrders as $o) {
					$applied = (float) ($o->applicable_weight ?? 0);
					$charged = $applied + 0.5;
					$excess = max(0, round($charged - $applied, 2));
					$rows[] = [
						$o->id,
						' ' . $o->awb_number, // space prefix keeps long AWB as text in Excel
						optional($o->user)->name ?? '',
						$o->courier_name ?? '',
						$applied,
						$charged,
						$excess,
						'auto'
					];
				}
			} else {
				$rows[] = ['', ' 1234567890123', '', '', '1.00', '1.50', '', 'auto'];
				$rows[] = ['', ' AWB0000000001', '', '', '0.50', '0.80', '', '20.00'];
			}

			return $this->streamBulkImportCsvDownload('weight_discrepancy_bulk_dummy.csv', $rows);
		}

		public function updateExcessWeight(Request $request)
		{
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission Denied');
			}

			$request->validate([
				'order_id' => 'required|integer|exists:orders,id',
				'chargeable_weight' => 'required|numeric|min:0',
				'excess_weight' => 'required|numeric|min:0',
				'excess_charge' => 'required|numeric|min:0',
				'replace_existing_images' => 'nullable|boolean',
				'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
			]);

			DB::beginTransaction();

			try {
				$order = Order::findOrFail($request->order_id);
				$excessWeight = ExcessWeight::where('order_id', $order->id)->orderByDesc('id')->firstOrFail();
				$timestamp = now();

				$existingImages = is_array($excessWeight->product_images) ? $excessWeight->product_images : [];
				$productImages = $existingImages;

				if ($request->hasFile('product_images')) {
					$replaceExistingImages = $request->boolean('replace_existing_images');

					if ($replaceExistingImages && !empty($existingImages)) {
						foreach ($existingImages as $oldImage) {
							Storage::disk('public')->delete($oldImage);
						}
					}

					$productImages = $this->storeWeightImages(
						$order->id,
						$request->file('product_images'),
						$existingImages,
						$replaceExistingImages
					);
				}

				$excessWeight->update([
					'chargeable_weight' => $request->chargeable_weight,
					'excess_weight' => $request->excess_weight,
					'excess_charge' => $request->excess_charge,
					'product_images' => $productImages,
					'updated_at' => $timestamp,
				]);

				$orderUpdate = [
					'weight_update_date' => $timestamp,
					'is_raise_weight' => 1,
				];

				if (empty($order->weight_status)) {
					$orderUpdate['weight_status'] = 'New';
				}

				$order->update($orderUpdate);

				weightDescrepencyHistory::create([
					'order_id' => $order->id,
					'action_by' => config('setting.company_name'),
					'remarks' => 'Excess weight updated by admin',
					'status_descrepency' => 'Updated',
					'created_at' => $timestamp,
					'updated_at' => $timestamp
				]);

				Notification::insert([
					'user_id'    => $order->user_id,
					'task_id'    => $order->id,
					'type'       => 'Weight Discrepancy',
					'role'       => 'user',
					'text'       => 'Weight discrepancy details were updated for AWB/Order No: ' . $order->awb_number . '.',
					'created_at' => $timestamp,
					'updated_at' => $timestamp
				]);

				DB::commit();

				return redirect('weight/descripencies/all')->with('success', 'Weight discrepancy updated successfully.');
			} catch (\Throwable $e) {
				DB::rollBack();
				return back()->with('error', 'Failed to update weight discrepancy: ' . $e->getMessage());
			}
		}
		
		public function weightHoistory($id)
		{
			$order = Order::whereId($id)->first();
			$weight_history = weightDescrepencyHistory::whereOrder_id($id)->orderBy('id','desc')->get();
			$view = view('weight.descrepensies.view_history',compact('weight_history'))->render();
			return response()->json(['view'=>$view,'header_msg'=>'Weight Discrepancy History for AWB: '.$order->awb_number]); 
		}

		public function weightAccepted($id)
		{
			try {
				DB::beginTransaction(); // Start transaction

				// Fetch Order & Excess Weight
				$order = Order::with('user')->findOrFail($id);
				$excess_weight = ExcessWeight::where('order_id', $id)->firstOrFail();
				$excess_charge = $excess_weight->excess_charge;

				// Use shared deduction logic
				$this->processWeightDeduction($order, $excess_charge, auth()->user()->name);

				DB::commit(); // Commit transaction

				return response()->json(['status' => 'success', 'msg' => 'Weight discrepancy has been accepted']);
			} catch (\Exception $e) {
				DB::rollBack(); // Rollback on failure
				return response()->json(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()], 500);
			}
		}
  
		public function weightbycourier(Request $request)
		{
			$id = $request->order_id;
			$status = $request->acton;
			
			$order = Order::whereId($id)->first();
			if($status == "Dispute Accepted by Courier")
			{
				
				$excess_weight = ExcessWeight::whereOrder_id($id)->first();
				$excess_charge = $excess_weight->excess_charge;
				
				$userwalt = User::whereId($order->user_id)->first(); 
				$walletamunt = $userwalt->wallet_amount - $excess_charge;
				User::whereId($order->user_id)->update(['wallet_amount'=>$walletamunt]); 
				
				Billing::insert([
				'user_id'=>$order->user_id,
				'billing_type'=>"Order",
				'billing_type_id'=>$order->id,
				'transaction_type'=>'debit',
				'amount'=>$excess_charge,
				'note'=>'Excess weight is apply with awb number : '.$order->awb_number,
				'created_at'=>date('Y-m-d H:i:s'),
				'updated_at'=>date('Y-m-d H:i:s'),
				]);
				
				weightDescrepencyHistory::insert(['order_id'=>$id,'action_by'=>'Courier','remarks'=>'','status_descrepency'=>$status,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
			}
			else
			{ 
				weightDescrepencyHistory::insert(['order_id'=>$id,'action_by'=>'Courier','remarks'=>'Reject, courier charged correctly as per seller content & qty.','status_descrepency'=>$status,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
			}
			Order::whereId($id)->update(['weight_status'=>$status,'weight_update_date'=>date('Y-m-d H:i:s')]); 
			return response()->json(['status'=>'success','msg'=>'weight descrepency has been accepted']); 
		}
		
		public function weightRemark($id)
		{
			$order = Order::find($id);
			$remarks = WeightDiscrepancyRemark::where('order_id', $id)->orderBy('id')->get(); 
			$role = auth()->user()->role;
			return view('weight.descrepensies.remark-form', compact('id', 'order', 'remarks', 'role'));
		}
		
		public function remarkStore(Request $request)
		{   
			$request->validate([
				'order_id' => 'required|integer',
				'remark' => 'required|string',
				'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
			]);
			
			DB::beginTransaction();
			try {
				$orderId = $request->order_id;
				$order = Order::find($orderId);
				$imagePaths = []; 
				if ($request->hasFile('images')) {
					foreach ($request->file('images') as $image) {
						 
						// Generate a unique filename
						$filename = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

						// Store the file in the "weight_descrepency" folder within "storage/app/public"
						$path = $image->storeAs('remark/'.$orderId, $filename, 'public');

						// Save the file path in an array
						$imagePaths[] = $path;
					}
				} 
				
				$remark = WeightDiscrepancyRemark::create([
					'order_id' => $orderId,
					'role' =>  auth()->user()->role,
					'user_id' => auth()->id(),
					'remark' => $request->remark,
					'images' => $imagePaths,
				]);
				
				$now= now();
				
				if(auth()->user()->role == "user")
				{
    				Notification::insert([
    					'task_id'    => $order->id,
    					'type'       => 'Weight Discrepancy',
    					'role'       => 'admin',
    					'text'       => $order->user->name . ' has created a new weight remark (Awb No: ' . $order->awb_number . ').',
    					'created_at' => $now,
    					'updated_at' => $now
    				]); 
				}
				else { 
    
    				Notification::insert([
    					'user_id'    => $order->user_id,
    					'task_id'    => $order->id,
    					'type'       => 'Weight Discrepancy',
    					'role'       => 'user',
    					'text'       => auth()->user()->name . ' has created a new weight remark (Awb No: ' . $order->awb_number . ').',
    					'created_at' => $now,
    					'updated_at' => $now
    				]);
    				
				}
				
				$order->update(['weight_status' => 'remark', 'weight_update_date' => now()]);
				DB::commit(); 
				return redirect()->back()->with('success', 'Remark submitted successfully.');
			} catch (\Exception $e) {
				DB::rollback();
				return redirect()->back()->with('error', 'Failed to submit remark.'); 
			}
		}
		
		public function weightview_image($id)
		{ 
			$weight_descrepencies = weightDescrepency::whereId($id)->orderBy('id','desc')->first();
			$view = view('weight.descrepensies.view_image',compact('weight_descrepencies'))->render();
			return response()->json(['view'=>$view,'header_msg'=>'Weight Discrepancy Image']); 
		}

		public function autoAccepted()
		{
			$excess_weights = ExcessWeight::select('excess_weights.*', 'o.id as order_id', 'o.user_id', 'o.awb_number')
				->join('orders as o', 'o.id', '=', 'excess_weights.order_id')
				->whereIn('o.weight_status', ['New', 'New Descrepency'])
				->get();

			if (count($excess_weights) > 0) {
				foreach ($excess_weights as $excess_weight) {
					$order = Order::find($excess_weight->order_id);
					if ($order) {
						$this->processWeightDeduction($order, $excess_weight->excess_charge, 'System Auto Accept');
					}
				}
			}
			return response()->json(['status' => 'success', 'msg' => 'All pending weight discrepancies have been accepted']);
		}

		private function processWeightDeduction($order, $excessCharge, $actionBy = 'Admin')
		{
			// Check if already billed for this order's weight discrepancy to avoid double deduction
			$alreadyBilled = Billing::where('user_id', $order->user_id)
				->where('billing_type', 'Order')
				->where('billing_type_id', $order->id)
				->where('note', 'like', '%weight%')
				->exists();

			if (!$alreadyBilled) {
				$user = User::find($order->user_id);
				if ($user) {
					$user->decrement('wallet_amount', $excessCharge);

					Billing::insert([
						'user_id' => $order->user_id,
						'billing_type' => "Order",
						'billing_type_id' => $order->id,
						'transaction_type' => 'debit',
						'amount' => $excessCharge,
						'note' => 'Excess weight applied for AWB: ' . $order->awb_number,
						'created_at' => now(),
						'updated_at' => now(),
					]);

					weightDescrepencyHistory::create([
						'order_id' => $order->id,
						'action_by' => $actionBy,
						'remarks' => 'Weight discrepancy accepted/deducted',
						'status_descrepency' => 'Accepted',
						'created_at' => now(),
						'updated_at' => now(),
					]);
				}
			} else {
				// Even if already billed, update history about the final acceptance
				weightDescrepencyHistory::create([
					'order_id' => $order->id,
					'action_by' => $actionBy,
					'remarks' => 'Final acceptance/Dispute resolved',
					'status_descrepency' => 'Accepted',
					'created_at' => now(),
					'updated_at' => now(),
				]);
			}

			// Update Order Status
			$order->update([
				'weight_status' => 'Accepted',
				'weight_update_date' => now(),
			]);

			$now = now();
			$notifText = $alreadyBilled 
			? '📦 Your shipment weight discrepancy (AWB: ' . $order->awb_number . ') has been resolved and accepted.'
			: '⚖️ A weight discrepancy has been detected and accepted for your shipment (AWB: ' . $order->awb_number . '). Charges have been deducted from your wallet.';

			if (auth()->check() && auth()->user()->role == "user") {
				Notification::insert([
					'task_id'    => $order->id,
					'type'       => 'Weight Discrepancy',
					'role'       => 'admin',
					'text'       => '✅ ' . $order->user->name . ' has accepted the weight discrepancy for Awb No: ' . $order->awb_number . '.',
					'created_at' => $now,
					'updated_at' => $now
				]);
			} else {
				Notification::insert([
					'user_id'    => $order->user_id,
					'task_id'    => $order->id,
					'type'       => 'Weight Discrepancy',
					'role'       => 'user',
					'text'       => $notifText,
					'created_at' => $now,
					'updated_at' => $now
				]);
			}
		}

		private function streamBulkImportCsvDownload($fileName, array $rows)
		{
			return response()->streamDownload(function () use ($rows) {
				$handle = fopen('php://output', 'w');

				foreach ($rows as $row) {
					fputcsv($handle, $row);
				}

				fclose($handle);
			}, $fileName, [
				'Content-Type' => 'text/csv',
			]);
		}

		private function normalizeBulkAwb($value): string
		{
			if ($value === null) {
				return '';
			}

			$str = trim((string) $value);
			if ($str === '') {
				return '';
			}

			$str = trim($str, "'\" \t\n\r\0\x0B");
			$hasScientificNotation = (bool) preg_match('/e[+-]?\d+$/i', $str);

			// Convert only scientific/decimal numeric values to plain integer-like string.
			if ($hasScientificNotation && is_numeric($str)) {
				$str = sprintf('%.0f', (float) $str);
			}

			return preg_replace('/\s+/', '', $str) ?? '';
		}

		private function storeWeightImages($orderId, $uploadedImages = [], array $existingImages = [], $replaceExistingImages = true)
		{
			$imagePaths = $replaceExistingImages ? [] : $existingImages;

			if (empty($uploadedImages)) {
				return $imagePaths;
			}

			foreach ($uploadedImages as $image) {
				$filename = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
				$path = $image->storeAs('weight_descrepency/' . $orderId, $filename, 'public');
				$imagePaths[] = $path;
			}

			return $imagePaths;
		}

		public function index(Request $request)
		{
			$userId = $request->user_id ?? auth()->user()->id;
			$users = User::where('status', 1)->get();

			// Use eager loading to optimize queries and avoid redundant joins
			$orders = Order::with(['user:id,name', 'excessWeight', 'orderItems'])
				->select('orders.*')
				->whereNotNull('orders.awb_number')
				->when($userId, fn($query) => $query->where('orders.user_id', $userId))
				->when($request->weight_status, fn($query) => $query->where('orders.weight_status', $request->weight_status))
				->when($request->date, fn($query) => $query->whereDate('orders.weight_update_date', $request->date))
				->when($request->search, function ($query, $search) {
					$query->where(function ($q) use ($search) {
						$q->where('orders.awb_number', 'LIKE', "%{$search}%")
							->orWhere('orders.id', 'LIKE', "%{$search}%")
							->orWhere('orders.order_prefix', 'LIKE', "%{$search}%");
					});
				})
				->whereHas('excessWeight')
				//->whereNotIn('orders.weight_status', ['Accepted', 'Auto Accepted'])
				->orderByDesc('orders.id')
				->paginate(10);

			return view('weight.descrepensies.index', compact('users', 'orders'));
		}
	}
