<?php
	
	namespace App\Http\Controllers\Api;
	
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use App\Models\Order;
	use App\Models\User;
	use App\Models\weightDescrepencyHistory;
	use App\Models\ExcessWeight;
	use App\Models\weightDescrepency;
	use App\Models\WeightFreeze;
	use App\Models\Notification;
	use App\Models\Billing;
	use App\Models\WeightDiscrepancyRemark;
	use File,Auth,DB,Helper;
	use App\Traits\ApiResponse;   
	
	class WeightController extends Controller
	{
		use ApiResponse; 
		public function index(Request $request)
		{   
		    if(auth()->user()->role == 'user'){
		        $userId = auth()->user()->id;
		    } else {
		        $userId = $request->user_id;
		    } 
			$offset = $request->offset ?? 0;
			$limit = $request->limit ?? 10;
			  
			$query = Order::with(['user:id,name', 'excessWeight', 'orderItems'])
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
			->whereHas('excessWeight');

			$total = (clone $query)->count();

			$orders = $query->orderByDesc('orders.id')
			->offset($offset) 
			->limit($limit)
			->get();
			 
			return $this->successResponse([
				'orders' => $orders,
				'total_count' => $total
			], 'list fetched successfully.');
		}
		
		public function weightRemark($id)
		{
			try {
				$order = Order::with('weightDescripencies')->findOrFail($id); 
				$remarks = WeightDiscrepancyRemark::where('order_id', $id)->orderBy('id')->get();
				return $this->successResponse([
					'order' => $order,
					'remarks' => $remarks
				], 'remark fetched successfully.');
			} catch (\Exception $e) {
				return $this->errorResponse('remark fetched failed.'); 
			}
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
				return $this->successResponse([], 'Remark submitted successfully.');   
			} catch (\Exception $e) {
				DB::rollback();
				return $this->errorResponse('Failed to submit remark.');  
			}
		}
		
		public function weightAccepted($id)
		{
			try {
				DB::beginTransaction(); // Start transaction

				// Fetch Order & Excess Weight
				$order = Order::with('user')->findOrFail($id);
				$excess_weight = ExcessWeight::where('order_id', $id)->firstOrFail();
				$excess_charge = $excess_weight->excess_charge;

				// Deduct from User Wallet
				$user = User::findOrFail($order->user_id);
				if ($user->wallet_amount < $excess_charge) {
					return $this->errorResponse('Insufficient wallet balance');   
				}
				$user->decrement('wallet_amount', $excess_charge);

				// Insert Billing Record
				Billing::insert([
					'user_id'         => $order->user_id,
					'billing_type'    => "Order",
					'billing_type_id' => $order->id,
					'transaction_type'=> 'debit',
					'amount'          => $excess_charge,
					'note'            => 'Excess weight applied for AWB: ' . $order->awb_number,
					'created_at'      => now(),
					'updated_at'      => now(),
				]);

				// Insert into Weight Discrepancy History
				weightDescrepencyHistory::create([
					'order_id'            => $id,
					'action_by'           => 'Dispute accepted in favour of ' . config('setting.company_name'),
					'remarks'             => '',
					'status_descrepency'  => 'Accepted',
					'created_at'          => now(),
					'updated_at'          => now(),
				]);
				
				$now = now();
                
                if(auth()->user()->role == "user")
                {
                    Notification::insert([
    					'task_id'    => $order->id,
    					'type'       => 'Weight Discrepancy',
    					'role'       => 'admin',
    				    'text'       => '✅ ' . $order->user->name . ' has accepted the weight discrepancy for  Awb No: ' . $order->awb_number . '.',
    					'created_at' => $now,
    					'updated_at' => $now
    				]);
                }
                else
                {
                    Notification::insert([
                        'user_id'    => $order->user_id,
                        'task_id'    => $order->id,
                        'type'       => 'Weight Discrepancy',
                        'role'       => 'user',
                        'text'       => '📦 Your shipment weight discrepancy (AWB/Order No: ' . $order->awb_number . ') has been approved by admin. Updated charges will apply.',
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);

                }
				// Update Order Status
				$order->update([
					'weight_status'    => 'Accepted', 
					'weight_update_date'    => now(), 
				]);

				DB::commit();  
				return $this->successResponse([], 'Weight discrepancy has been accepted');   
			} catch (\Exception $e) {
				DB::rollBack(); 
				return $this->errorResponse('Failed to accept weight descrepencies.');   
			}
		} 
	}
