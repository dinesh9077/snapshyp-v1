<?php 
	namespace App\Services;

	use App\Models\ShippingCompany;
	use App\Models\CourierWarehouse;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\Http;
	use Auth;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Log;
	use App\Models\Order;
	use App\Support\OrderType;

	class ShipMozo
	{  
		public function httpHeader($shippingCompany)
		{
			return [ 
				'public-key' => $shippingCompany->api_key,
				'private-key' => $shippingCompany->secret_key,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			];
		}
		
		public function createWarehouse($courierWarehouse, $shippingCompany)
		{ 
			try {
				$pickup_address = implode(' ', [
					trim($courierWarehouse->address),
					trim($courierWarehouse->city),
					trim($courierWarehouse->state),
					trim($courierWarehouse->zip_code)
				]) . ', ' . trim($courierWarehouse->country);
				
				
				$requestBody = [
					"address_title"      => trim($courierWarehouse->warehouse_name),
					"name"               => trim($courierWarehouse->contact_name),
					"phone"              => trim($courierWarehouse->contact_number),
					"alternate_phone"    => "",
					"email"              => trim($courierWarehouse->contact_email),
					"address_line_one"   => $pickup_address,
					"address_line_two"   => "",
					"pin_code"           => trim($courierWarehouse->zip_code)
				]; 

				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/create-warehouse', $requestBody);

				Log::info('ShipMozo: Create Warehouse response', [
					'warehouse_id' => $courierWarehouse->id,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				if ($response->successful()) {
					return [
						'success'  => true, 
						'request'  => $requestBody, 
						'response' => $response->json(),
					];
				}

				return [
					'success'  => false, 
					'request'  => $requestBody,
					'response' => json_decode($response->body(), true),
				];

			} catch (\Throwable $e) { 

				return [
					'success'  => false,
					'request'  => $requestBody,
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		}
		  

		public function updateWarehouse($courierWarehouse, $shippingCompany)
		{
			try {
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');

				$requestBody = [
					"order_id"     => trim($courierWarehouse->warehouse_name),
					"warehouse_id" => (int) ($courierWarehouse->shipmozo_warehouse_id ?? $courierWarehouse->shipping_id),
				];

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/order/update-warehouse', $requestBody);

				Log::info('ShipMozo: Update Warehouse response', [
					'warehouse_id' => $courierWarehouse->id,
					'status'       => $response->status(),
					'response'     => $response->json()
				]);

				$responseJson = $response->json();

				if ($response->successful() && isset($responseJson['result']) && $responseJson['result'] == "1") {
					return [
						'success'  => true,
						'request'  => $requestBody,
						'response' => $responseJson,
					];
				}

				return [
					'success'  => false,
					'request'  => $requestBody,
					'response' => $responseJson ?? json_decode($response->body(), true),
				];

			} catch (\Throwable $e) {
				return [
					'success'  => false,
					'request'  => $requestBody ?? [],
					'response' => ['error' => 'Unable to connect to update-warehouse service.'],
					'message'  => $e->getMessage(),
				];
			}
		}

		public function pincodeService($request, $shippingCompany)
		{  
			try {
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  

				$requestBody = [
					"pickup_pincode"   => $request['pickup_code'],
					"delivery_pincode" => $request['delivery_code'],
				];
			
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/pincode-serviceability', $requestBody);

				Log::info('ShipMozo: Pincode Service response', [
					'request' => $requestBody,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				if ($response->successful()) {
					return [
						'success'  => true, 
						'response' => $response->json(),
					];
				}

				return [
					'success'  => false, 
					'response' => json_decode($response->body(), true),
				];

			} catch (\Throwable $e) { 

				return [
					'success'  => false,
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			}
		}

		public function rateCaculator($requestData, $shippingCompany)
		{  	  
			try {
			
				$dimensions = []; 
				if (!empty($requestData['qty'])) {
					$conversionFactors = [ 
						"cm"   => 1
					]; 
					
					$factor = $conversionFactors[$requestData['dimension_type']] ?? 1; 
					foreach ($requestData['qty'] as $index => $qty) {
						$dimensions[] = [
							"no_of_box" => $qty ?? 0,
							"length" => ($requestData['length'][$index] ?? 0) * $factor,
							"width"  => ($requestData['width'][$index] ?? 0) * $factor,
							"height" => ($requestData['height'][$index] ?? 0) * $factor,
						];
					}
				}
				
				$codAmount = strtolower($requestData['payment_type']) === "prepaid" ? '' : ($requestData['cod_amount'] ?? ''); 
				
				$requestBody = [
					"order_id"        	=> "", 
					"pickup_pincode" 	=> $requestData['pickup_code'] ?? '',
					"delivery_pincode" 	=> $requestData['delivery_code'] ?? '', 
					"payment_type"    	=> $requestData['payment_type'],
					"shipment_type"  	=> "FORWARD",
					"order_amount"   	=> $requestData['inv_amount'] ?? 0,
					"type_of_package"	=> $requestData['type_of_package'] == 2 ? "B2B" : "SPS",
					"rov_type"        	=> $requestData['rov_type'] ?? '',
					"cod_amount"      	=> $codAmount,
					"weight"          	=> $requestData['weight'] * 1000,
					"dimensions"        => $dimensions,
				]; 
					
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/rate-calculator', $requestBody);

				Log::info('ShipMozo: Rate Calculator response', [
					'request' => $requestBody,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				if ($response->successful()) {
					return [
						'success'  => true, 
						'request'  => $requestBody, 
						'response' => $response->json(),
					];
				}

				return [
					'success'  => false, 
					'request'  => $requestBody, 
					'response' => json_decode($response->body(), true),
				];

			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		}
			
		public function orderRateCaculator($order, $shippingCompany)
		{  	  
			try {  
				$dimensions = []; 
				if (!empty($order->orderItems->isNotEmpty()) && $order->weight_order == 2) {
					$conversionFactors = [ 
						"cm"   => 1
					]; 
					
					$factor = $conversionFactors[$order->dimension_type] ?? 1; 
					foreach ($order->orderItems as $index => $orderItem) {
						$dimensions[] = [
							"no_of_box" => $orderItem->quantity ?? 0,
							"length" => ($orderItem->dimensions['length'] ?? 0) * $factor,
							"width"  => ($orderItem->dimensions['width'] ?? 0) * $factor,
							"height" => ($orderItem->dimensions['height'] ?? 0) * $factor,
						];
					}
				}
				else
				{
					$quantity = $order->orderItems->sum(fn($q) => $q->quantity);

					$dimensions[] = [
						"no_of_box" => $quantity ?? 0,
						"length"    => $order->length,
						"width"     => $order->width,
						"height"    => $order->height,
					];

				}
				
				$codAmount = strtolower($order->order_type) === "prepaid" ? '' : ($order->cod_amount ?? ''); 
				$requestBody = [
					"order_id"        	=> "", 
					"pickup_pincode" 	=> optional($order->warehouse)->zip_code ?? '',
					"delivery_pincode" 	=> optional($order->customerAddress)->zip_code ?? '', 
					"payment_type"    	=> strtoupper($order->order_type),
					"shipment_type"  	=> "FORWARD",
					"order_amount"   	=> $order->invoice_amount,
					"type_of_package"	=> $order->weight_order == 2 ? "B2B" : "SPS",
					"rov_type"        	=> $order->insurance_type == 1 ? 'ROV_OWNER' : 'ROV_CARRIER',
					"cod_amount"      	=> $codAmount,
					"weight"          	=> $order->weight * 1000,
					"dimensions"        => $dimensions,
				]; 
			
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/rate-calculator', $requestBody);

				Log::info('ShipMozo: Order Rate Calculator response', [
					'order_id' => $order->id,
					'request' => $requestBody,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				if ($response->successful()) {
					return [
						'success'  => true, 
						'request'  => $requestBody, 
						'response' => $response->json(),
					];
				}

				return [
					'success'  => false, 
					'request'  => $requestBody, 
					'response' => json_decode($response->body(), true),
				];

			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		} 
		
		public function pushOrder($order, $requestData, $shippingCompany)
		{
			// Business rule: an order must be COD or Prepaid before any shipment is booked.
			// Kept outside the try below so an invalid type surfaces to the caller.
			OrderType::validate($order->order_type);

			try {
				//$user = Auth::user()->load('userKyc'); 
				//$pancardNumber = $user->userKyc->pancard ?? $user->pancard_number ?? null;

				$totalWeightInGm = $requestData['applicable_weight'] * 1000;
				
				$consigneeName = implode(' ', [
					$order->customer->first_name ?? '',
					$order->customer->last_name ?? ''
				]);
			
				$customerAddress = $order->customerAddress ?? null; 
				$customer_address = $customerAddress 
				? implode(' ', array_filter([
					trim($customerAddress->address ?? ''),
					trim($customerAddress->city ?? ''),
					trim($customerAddress->state ?? ''),
					trim($customerAddress->zip_code ?? '')
				])) . (isset($customerAddress->country) ? ', ' . trim($customerAddress->country) : '')
				: '';
		
				$productDetail = $order->orderItems->map(function($item) {
					return [
						"name"             => $item->product_name,
						"sku_number"       => $item->sku_number,
						"quantity"         => $item->quantity,
						"discount"         => "",
						"hsn"              => $item->hsn_number,
						"unit_price"       => $item->amount,
						"product_category" => $item->product_category,
					];
				})->toArray(); 
				
				$dimensions = $order->orderItems->map(function($item) {
					return [
						"no_of_box"   => $item->dimensions['no_of_box'] ?? 0,
						"length"      => $item->dimensions['length'] ?? 0,
						"width"       => $item->dimensions['width'] ?? 0,
						"height"      => $item->dimensions['height'] ?? 0,
					];
				})->toArray(); 

				$codAmount = strtolower($order->order_type) === "prepaid" ? '' : ($order->cod_amount ?? ''); 
				
				// Ensure order_date is not in future (ShipMozo doesn't accept future dates)
				$orderDate = $order->order_date;
				if (strtotime($orderDate) > time()) {
					$orderDate = date('Y-m-d');
				}
				
				$requestBody = [
					"order_id"                     => $order->order_prefix,
					"order_date"                   => $orderDate,
					"order_type"                   => "ESSENTIALS",
					"consignee_name"               => $consigneeName,
					"consignee_phone"              => $order->customer->mobile ?? '',
					"consignee_alternate_phone"    => "",
					"consignee_email"              => $order->customer->email ?? '',
					"consignee_address_line_one"   => $customer_address ?? '',
					"consignee_address_line_two"   => "",
					"consignee_pin_code"           => $order->customerAddress->zip_code ?? '',
					"consignee_city"               => $order->customerAddress->city ?? '',
					"consignee_state"              => $order->customerAddress->state ?? '',
					"product_detail"               => $productDetail,
					"payment_type"                 => strtoupper($order->order_type),
					"cod_amount"                   => $codAmount ?? "",
					"shipping_charges"             => "",
					"weight"                       => $totalWeightInGm, 
					"warehouse_id"                 => $order->warehouse->shipmozo_warehouse_id ?? $order->warehouse->shipping_id ?? '',
					"gst_ewaybill_number"          => $order->ewaybillno,
					"gstin_number"                 => $order->customer->gst_number ?? ''
				]; 
				
				if($order->weight_order == 2)
				{
					$requestBody = array_merge(
						$requestBody,
						[
							"type_of_package" => "B2B",
							"dimensions" => $dimensions
						]
					);
				}
				else
				{
					$requestBody = array_merge(
						$requestBody,
						[
							"length" => $order->length,
							"width"  => $order->width,
							"height" => $order->height,
						]
					);

				}	
					
				// API Base URL
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  
					
				// Start the HTTP request 
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/push-order', $requestBody);
					
				Log::info('ShipMozo: Manifest response received', [
					'order_id' => $order->id,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				// Handle the response
				if ($response->successful()) {
					return [
						'success' => true, 
						'request' => $requestBody, 
						'response' => $response->json(),
					];
				} 
				
				// If the response was unsuccessful, return an error response
				return [
					'success' => false, 
					'request' => $requestBody, 
					'response' => json_decode($response->body(), true),
				];  
			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		}
		 
		public function assignCourier($orderId, $courierId, $shippingCompany)
		{  
			try{
				// API Base URL
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  
					
				$requestBody = [
					'order_id' => $orderId,
					'courier_id' => $courierId
				];
				
				// Start the HTTP request 
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/assign-courier', $requestBody);

				Log::info('ShipMozo: Assign Courier response', [
					'request' => $requestBody,
					'status' => $response->status(),
					'response' => $response->json()
				]);
					
				// Handle the response
				if ($response->successful()) {
					return [
						'success' => true, 
						'request' => $requestBody, 
						'response' => $response->json(),
					];
				} 
				
				// If the response was unsuccessful, return an error response
				return [
					'success' => false, 
					'request' => $requestBody, 
					'response' => json_decode($response->body(), true),
				];  
			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		}  

		public function trackOrder($awbNumber, $shippingCompany)
		{  
			try{
				// API Base URL
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  
					
				$requestBody = [
					'awb_number' => $awbNumber
					//'awb_number' => 'SF2079425818SPZ'
				];
				
				// Start the HTTP request 
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->get($baseUrl . '/track-order', $requestBody);
					
				Log::info('ShipMozo: Track Order response', [
					'awb_number' => $awbNumber,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				// Handle the response
				if ($response->successful()) {
					return [
						'success' => true, 
						'request' => $requestBody, 
						'response' => $response->json(),
					];
				} 
				
				// If the response was unsuccessful, return an error response
				return [
					'success' => false, 
					'request' => $requestBody, 
					'response' => json_decode($response->body(), true),
				];  
			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect to pincode service.'],
					'message'  => $e->getMessage(),
				];
			} 
		}
		
		public function cancelShipment($order, $shippingCompany)
		{  
			
			try{
				// API Base URL
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  
					
				$requestBody = [
					'order_id' => $order->shipment_id,
					'awb_number' => $order->awb_number,
				];
					
				// Start the HTTP request 
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/cancel-order', $requestBody);
					
				Log::info('ShipMozo: Cancel Order response', [
					'awb_number' => $order->awb_number,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				// Handle the response
				if ($response->successful()) {
					return [
						'success' => true, 
						'request' => $requestBody, 
						'response' => $response->json(),
					];
				} 
				
				// If the response was unsuccessful, return an error response
				return [
					'success' => false, 
					'request' => $requestBody, 
					'response' => json_decode($response->body(), true),
				];  
			} catch (\Throwable $e) {  
				return [
					'success'  => false,
					'request'  => $requestBody, 
					'response' => ['error' => 'Unable to connect api.'],
					'message'  => $e->getMessage(),
				];
			}
		} 
		
		public function getOrderDetails($orderPrefrenceNo, $shippingCompany)
		{  
			try {
			
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');  
					
				// Start the HTTP request 
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->get($baseUrl . '/get-order-detail/'.$orderPrefrenceNo);

				Log::info('ShipMozo: Get Order Detail response', [
					'order_preference_no' => $orderPrefrenceNo,
					'status' => $response->status(),
					'response' => $response->json()
				]);
					
				// Handle the response
				if ($response->successful()) {
					return [
						'success' => true,  
						'response' => $response->json(),
					];
				} 
				
				// If the response was unsuccessful, return an error response
				return [
					'success' => false,  
					'response' => json_decode($response->body(), true),
				];  
			} catch (\Throwable $e) {  
				return [
					'success'  => false, 
					'response' => ['error' => 'Unable to connect api.'],
					'message'  => $e->getMessage(),
				];
			} 
		}
		
		public function getLabelImage($baseUrl)
		{  
			try {
					
				// Make API request
				$response = Http::get($baseUrl);

				Log::info('ShipMozo: Get Label Image', [
					'url' => $baseUrl,
					'status' => $response->status()
				]);

				// Check if request was successful
				if ($response->successful()) {
					return [
						'success' => true,
						'response' => $response->json(),
					];
				}

				return [
					'success' => false,
					'response' => json_decode($response->body(), true),
				];

			} catch (\Exception $e) {
					
				return [
					'success' => false,
					'response' => ['error' => 'An unexpected error occurred.'],
				];
			}
		}
		
		public function waybillCopyByLrNo($lrNo, $shippingCompany)
		{  
			try {
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');

				// Make API request
				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)->withOptions([
					'verify' => true, 
				])->get("$baseUrl/lr_copy/print/$lrNo");

				Log::info('ShipMozo: Waybill Copy response', [
					'lrNo' => $lrNo,
					'status' => $response->status()
				]);

				// Check if request was successful
				if ($response->successful()) {
					return [
						'success' => true,
						'response' => $response->body(),
					];
				}

				return [
					'success' => false,
					'response' => json_decode($response->body(), true),
				];

			} catch (\Exception $e) {
					
				return [
					'success' => false,
					'response' => ['error' => 'An unexpected error occurred.'],
				];
			}
		}

		public function ndrAction($awbNumber, $action, $remarks, $shippingCompany, $date = null)
		{
			try {
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');

				$requestBody = [
					'awb_number' => $awbNumber,
					'action' => $action,
					'remarks' => $remarks,
					'date' => $date
				];

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true])
				->post($baseUrl . '/ndr-action', $requestBody);

				Log::info('ShipMozo: NDR Action response', [
					'request' => $requestBody,
					'status' => $response->status(),
					'response' => $response->json()
				]);

				if ($response->successful()) {
					return [
						'success' => true,
						'response' => $response->json(),
					];
				}

				return [
					'success' => false,
					'response' => json_decode($response->body(), true),
				];

			} catch (\Throwable $e) {
				return [
					'success' => false,
					'response' => ['error' => 'Unable to connect to NDR service.'],
					'message' => $e->getMessage(),
				];
			}
	    }

		public function getNdrAll($shippingCompany, array $params = [])
		{
			try {
				$baseUrl = rtrim($shippingCompany->url ?? '', '/');

				$query = [
					'per_page' => (int) ($params['per_page'] ?? 100),
					'page' => (int) ($params['page'] ?? 1),
				];

				if (!empty($params['from'])) {
					$query['from'] = $params['from'];
				}
				if (!empty($params['to'])) {
					$query['to'] = $params['to'];
				}

				$response = Http::withHeaders(
					$this->httpHeader($shippingCompany)
				)
				->withOptions(['verify' => true, 'proxy' => false])
				->timeout(20)
				->get($baseUrl . '/get-ndr-all', $query);

				Log::info('ShipMozo: Get NDR all response', [
					'request' => $query,
					'status' => $response->status(),
				]);

				if ($response->successful()) {
					return [
						'success' => true,
						'request' => $query,
						'response' => $response->json(),
					];
				}

				return [
					'success' => false,
					'request' => $query,
					'response' => json_decode($response->body(), true),
				];
			} catch (\Throwable $e) {
				return [
					'success' => false,
					'request' => $params,
					'response' => ['error' => 'Unable to fetch ShipMozo NDR list.'],
					'message' => $e->getMessage(),
				];
			}
		}
	
	    public function createPickupRequest($requestData, $shippingCompany)
	    {
	        $orderIds = isset($requestData['order_ids']) ? explode(',', $requestData['order_ids']) : [];

	        if (empty($orderIds)) {
	            return [
	                'success' => false,
	                'response' => ['error' => 'No orders selected for ShipMozo pickup.']
	            ];
	        }

	        $orders = Order::whereIn('id', $orderIds)->get();

	        if ($orders->isEmpty()) {
	            return [
	                'success' => false,
	                'response' => ['error' => 'Orders not found.']
	            ];
	        }

	        $successCount = 0;
	        $responses = [];
	        $pickupIds = [];
			$firstError = null;
	        
	        $baseUrl = rtrim($shippingCompany->url ?? '', '/');

	        foreach ($orders as $order) {
				
	            $requestBody = [
	                "order_id" => $order->shipment_id ?? $order->order_prefix
	            ];

	            try {
	                $response = Http::withHeaders(
	                    $this->httpHeader($shippingCompany)
	                )
	                ->withOptions(['verify' => true])
	                ->post($baseUrl . '/schedule-pickup', $requestBody);

	                $respData = $response->json();
	                $responses[] = $respData;
	                
	                Log::info('ShipMozo: Schedule Pickup response', [
	                    'order_id' => $order->id,
	                    'status' => $response->status(),
	                    'response' => $respData
	                ]);

	                if ($response->successful() && isset($respData['result']) && $respData['result'] == "1") {
	                    $successCount++;
	                    $pickupIds[] = $respData['data']['reference_id'] ?? $order->order_prefix;
	                } else {
						if (!$firstError) {
							$firstError = $respData['message'] ?? ($respData['error'] ?? 'Unknown error');
						}
					}
	            } catch (\Exception $e) {
	                $responses[] = ['error' => $e->getMessage()];
	                Log::error('ShipMozo: Schedule Pickup Error', [
	                    'order_id' => $order->id,
	                    'error' => $e->getMessage()
	                ]);
					if (!$firstError) {
						$firstError = $e->getMessage();
					}
	            }
	        }
	        
	        if ($successCount > 0) {
	            return [
	                'success' => true,
	                'response' => [
	                    'pickup_id' => implode(',', $pickupIds),
	                    'message' => "Pickup scheduled for $successCount orders.",
	                    'details' => $responses
	                ]
	            ];
	        }

	        return [
	            'success' => false,
	            'response' => ['error' => $firstError ?? 'Failed to schedule pickup.', 'details' => $responses]
	        ];
	    }
	}

