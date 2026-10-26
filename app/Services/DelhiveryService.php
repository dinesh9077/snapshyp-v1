<?php
namespace App\Services;

use App\Models\ShippingCompany;
use App\Models\CourierWarehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Support\OrderType;

class DelhiveryService
{
	public function getToken($shippingCompany = null)
	{
		if (!$shippingCompany) {
			return ['success' => false, 'message' => 'No shipping company provided'];
		}

		if (!$shippingCompany || $shippingCompany->status != 1) {
			return ['success' => false, 'message' => 'Shipping company not found or inactive'];
		}

		$baseUrl = rtrim($shippingCompany->url ?? '', '/');

		$requestBody = [
			'username' => $shippingCompany->email,
			'password' => $shippingCompany->password
		];

		$response = Http::withHeaders([
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->post($baseUrl . '/ums/login', $requestBody);

		// Never log the password or the issued token
		Log::info('Delhivery Token', [
			'company_id' => $shippingCompany->id,
			'request' => \Illuminate\Support\Arr::except((array) $requestBody, ['password']),
			'response_keys' => array_keys((array) $response->json()),
			'status' => $response->status()
		]);

		if ($response->successful()) {
			return [
				'success' => true,
				'request' => $requestBody,
				'response' => $response->json(),
			];
		}

		return [
			'success' => false,
			'request' => $requestBody,
			'response' => $response->json() ?? json_decode($response->body(), true),
		];
	}

	public function ensureTokenIsValid($shippingCompany)
	{
		if (!$shippingCompany || $shippingCompany->id != 5) {
			return $shippingCompany;
		}

		if (!$shippingCompany->expired_at || Carbon::now()->addMinutes(30)->greaterThanOrEqualTo($shippingCompany->expired_at)) {
			$data = $this->getToken($shippingCompany);
			if (isset($data['success']) && $data['success']) {
				$token = $data['response']['data']['jwt'] ?? null;
				if ($token) {
					$shippingCompany->api_key = $token;
					$shippingCompany->expired_at = Carbon::now()->addHours(23);
					$shippingCompany->save();
				}
			}
		}

		return $shippingCompany;
	}

	public function createWarehouse($courierWarehouse, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$pickup_address = implode(' ', [
			trim($courierWarehouse->address),
			trim($courierWarehouse->city),
			trim($courierWarehouse->state),
			trim($courierWarehouse->zip_code)
		]) . ', ' . trim($courierWarehouse->country);


		$requestBody = [
			"name" => trim($courierWarehouse->warehouse_name),
			"pin_code" => trim($courierWarehouse->zip_code),
			"city" => trim($courierWarehouse->city),
			"state" => trim($courierWarehouse->state),
			"country" => trim($courierWarehouse->country),
			"address_details" => [
					"address" => $pickup_address,
					"contact_person" => trim($courierWarehouse->contact_name),
					"phone_number" => trim($courierWarehouse->contact_number)
				],
			"ret_address" => [
				"pin" => trim($courierWarehouse->zip_code),
				"address" => $pickup_address,
			]
		];

		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
					'verify' => false,
				])
			->post($baseUrl . '/client-warehouse/create/', $requestBody);

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
	}

	public function updateWarehouse($courierWarehouse, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$pickup_address = implode(' ', [
			trim($courierWarehouse->address),
			trim($courierWarehouse->city),
			trim($courierWarehouse->state),
			trim($courierWarehouse->zip_code)
		]) . ', ' . trim($courierWarehouse->country);


		$requestBody = [
			"cl_warehouse_name" => trim($courierWarehouse->warehouse_name),
			"update_dict" => [
					"city" => trim($courierWarehouse->city),
					"state" => trim($courierWarehouse->state),
					"country" => trim($courierWarehouse->country),
					"address_details" => [
							"address" => $pickup_address,
							"contact_person" => trim($courierWarehouse->contact_name),
							"phone_number" => trim($courierWarehouse->contact_number),
							"company" => trim($courierWarehouse->company_name)
						],
					"ret_address" => [
						"pin" => trim($courierWarehouse->zip_code),
						"address" => $pickup_address,
						"city" => trim($courierWarehouse->city),
						"state" => trim($courierWarehouse->state),
						"country" => trim($courierWarehouse->country),
						"pin" => trim($courierWarehouse->zip_code)
					]
				]
		];

		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
					'verify' => false,
				])
			->patch($baseUrl . '/client-warehouses/update', $requestBody);

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
	}

	public function pincodeService($pinCode, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
					'verify' => false,
				])
			->get($baseUrl . '/pincode-service/' . $pinCode);

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
	}

	public function freightEstimate($order, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$totalWeightInKg = $order->orderItems->sum(function($item) { return $item->dimensions['weight'] ?? 0; });
		$totalInvoiceAmount = $order->orderItems->sum(function($item) { return $item->amount ?? 0; });
		$sourcePin = $order->warehouse->zip_code ?? '';
		$consigneePin = $order->customerAddress->zip_code ?? '';

		$conversionFactors = [
			"ft" => 30.48,
			"inch" => 2.54,
			"cm" => 1 // No conversion needed
		];

		// Determine the correct conversion factor
		$factor = $conversionFactors[$order->dimension_type] ?? 1;

		$dimensions = $order->orderItems->map(function($orderItem) use ($factor) {
			return [
				"length_cm" => ($orderItem->dimensions['length'] ?? 0) * $factor,
				"width_cm" => ($orderItem->dimensions['width'] ?? 0) * $factor,
				"height_cm" => ($orderItem->dimensions['height'] ?? 0) * $factor,
				"box_count" => $orderItem->quantity ?? 0,
			];
		})->toArray();


		$requestBody = [
			"dimensions" => $dimensions,
			"weight_g" => $totalWeightInKg > 0 ? ($totalWeightInKg * 1000) : 0,
			"cheque_payment" => false,
			"source_pin" => $sourcePin,
			"consignee_pin" => $consigneePin,
			"payment_mode" => $order->order_type,
			"inv_amount" => $totalInvoiceAmount,
			"freight_mode" => strtolower($order->freight_mode),
			"rov_insurance" => $order->insurance_type == 1 ? false : true
		];
		if (OrderType::isCod($order->order_type)) {
			$requestBody['cod_amount'] = $order->cod_amount;
		}
		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->post($baseUrl . '/freight/estimate', $requestBody);

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
	}

	public function rateCaculator($requestData, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$dimensions = [];
		if (!empty($requestData['qty'])) { // Check if qty is not empty
			$conversionFactors = [
				"ft" => 30.48,
				"inch" => 2.54,
				"cm" => 1 // No conversion needed
			];

			// Determine the correct conversion factor
			$factor = $conversionFactors[$requestData['dimension_type']] ?? 1;

			foreach ($requestData['qty'] as $index => $qty) {
				$dimensions[] = [
					"length_cm" => ($requestData['length'][$index] ?? 0) * $factor,
					"width_cm" => ($requestData['width'][$index] ?? 0) * $factor,
					"height_cm" => ($requestData['height'][$index] ?? 0) * $factor,
					"box_count" => $qty ?? 0,
				];
			}
		}

		$requestBody = [
			"dimensions" => $dimensions,
			"weight_g" => ($requestData['weight'] * 1000) ?? 0,
			"cheque_payment" => false,
			"source_pin" => $requestData['pickup_code'] ?? '',
			"consignee_pin" => $requestData['delivery_code'] ?? '',
			"payment_mode" => $requestData['payment_type'] ?? '',
			"inv_amount" => $requestData['inv_amount'] ?? 0,
			"freight_mode" => strtolower($requestData['freight_mode']),
			"rov_insurance" => false
		];

		if (OrderType::isCod($requestData['payment_type'] ?? '')) {
			$requestBody['cod_amount'] = $requestData['cod_amount'] ?? 0;
		}

		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->post($baseUrl . '/freight/estimate', $requestBody);

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
	}

	public function manifest($order, $requestData, $delhivery)
	{
		// Business rule: an order must be COD or Prepaid before any shipment is booked.
		OrderType::validate($order->order_type);

		$delhivery = $this->ensureTokenIsValid($delhivery);
		$user = Auth::user()->load('userKyc');
		$pancardNumber = $user->userKyc->pancard ?? $user->pancard_number ?? null;

		$totalWeightInGm = $order->orderItems->sum(function($item) { return $item->dimensions['weight'] ?? 0; }) * 1000;

		$consigneeName = implode(' ', [
			$order->customer->first_name ?? '',
			$order->customer->last_name ?? ''
		]);

		$dropOffLocation = [
			"consignee_name" => $consigneeName,
			"address" => $order->customerAddress->address ?? '',
			"city" => $order->customerAddress->city ?? '',
			"state" => $order->customerAddress->state ?? '',
			"zip" => $order->customerAddress->zip_code ?? '',
			"phone" => $order->customer->mobile ?? '',
			"email" => $order->customer->email ?? '',
		];

		$shipmentDetails = $order->orderItems->map(function($item) {
			return [
				"order_id" => "{$item->id}",
				"box_count" => $item->quantity,
				"description" => $item->product_discription,
				"weight" => ($item->dimensions['weight'] ?? 0) * 1000,
				"waybills" => [],
				"master" => 0
			];
		})->toArray();

		$dimensions = $order->orderItems->map(function($item) {
			return [
				"box_count" => $item->quantity,
				"length" => $item->dimensions['length'] ?? 0,
				"width" => $item->dimensions['width'] ?? 0,
				"height" => $item->dimensions['height'] ?? 0
			];
		})->toArray();


		$invoices = [
			[
				"inv_num" => $order->invoice_no,
				"inv_amt" => $order->invoice_amount,
				"inv_qr_code" => '',
				"ewaybill" => $order->ewaybillno ?? ''
			]
		];

		$billingAddress = [
			"name" => $user->name,
			"consignor" => $user->name,
			"company" => $user->company_name,
			"address" => $user->address,
			"city" => $user->city,
			"state" => $user->state,
			"pin" => $user->zip_code,
			"phone" => $user->mobile,
			"pan_number" => $pancardNumber ?? null
		];

		$docData = [
			[
				"doc_type" => "INVOICE_COPY",
				"doc_meta" => [
					"invoice_num" => [$order->invoice_no]
				]
			]
		];

		$requestBody = [
			'lrn' => '',
			'pickup_location_name' => $order->warehouse->warehouse_name ?? '',
			'payment_mode' => $order->order_type,
			'cod_amount' => $order->cod_amount,
			'weight' => (float) $totalWeightInGm,
			'dropoff_location' => json_encode($dropOffLocation),
			'rov_insurance' => 0,
			'invoices' => json_encode($invoices),
			'shipment_details' => json_encode($shipmentDetails),
			'dimensions' => json_encode($dimensions),
			'doc_data' => json_encode($docData),
			'freight_mode' => strtolower($order->freight_mode),
			'fm_pickup' => 1,
			'billing_address' => json_encode($billingAddress)
		];

		// API Base URL
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		// Start the HTTP request
		$request = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
		])->withOptions([
					'verify' => false,
				]);

		// Attach Images One by One
		if (!empty($order->invoice_document)) {
			$filePath = 'orders/' . $order->id . '/' . $order->invoice_document[0];

			// Check if the file exists in storage
			if (!Storage::disk('public')->exists($filePath)) {
				return [
					'success' => false,
					'message' => 'invoice document not found in storage.'
				];
			}

			// Get the full path of the file
			$fullPath = Storage::disk('public')->path($filePath);

			// Attach the file to the request
			$request = $request->attach(
				'doc_file',
				file_get_contents($fullPath),
				basename($fullPath)
			);
		}


		// Send the request
		$response = $request->asMultipart()->post($baseUrl . '/manifest', $requestBody);

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
	}

	public function createPickupRequest($requestData, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$courierWarehouse = CourierWarehouse::find($requestData['warehouse_id']);
		$startTime = $requestData['pickup_start_time'] ? date('H:i:s', strtotime($requestData['pickup_start_time'])) : '';
		$endTime = $requestData['pickup_end_time'] ? date('H:i:s', strtotime($requestData['pickup_end_time'])) : '';
		$requestBody = [
			'client_warehouse' => $courierWarehouse->warehouse_name,
			'pickup_date' => $requestData['pickup_date'] ?? '',
			'start_time' => (string) $startTime,
			'end_time' => (string) $endTime,
			'expected_package_count' => (int) $requestData['expected_package_count'] ?? 0,
		];

		// API Base URL
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		// Start the HTTP request
		$request = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
		])->withOptions([
					'verify' => false,
				]);
		// Send the request
		$response = $request->post($baseUrl . '/pickup_requests', $requestBody);

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
	}

	public function cancelPickupRequest($pickupId, $delhivery)
	{

		// API Base URL
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		// Start the HTTP request
		$request = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
		])->withOptions([
					'verify' => false,
				]);
		// Send the request
		$response = $request->delete($baseUrl . '/pickup_requests/' . $pickupId);

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
	}

	public function manifestStatus($jobId, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		// Make API request
		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $delhivery->api_key,
			'Accept' => 'application/json',
		])->withOptions([
					'verify' => false, // Disable SSL verification (only if needed)
				])->get("$baseUrl/manifest", ['job_id' => $jobId]);

		Log::info('Delhivery Manifest Status', [
			'jobId' => $jobId,
			'response' => $response->json(),
			'status' => $response->status()
		]);

		// Check if request was successful
		if ($response->successful()) {
			return [
				'success' => true,
				'response' => $response->json(),
			];
		}

		// Return error response if failed
		return [
			'success' => false,
			'response' => json_decode($response->body(), true),
		];
	}

	public function cancelShipmentByLrNo($lrNo, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		try {
			$baseUrl = rtrim($delhivery->url ?? '', '/');

			// Make API request
			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $delhivery->api_key,
				'Accept' => 'application/json',
			])->withOptions([
						'verify' => false,
					])->delete("$baseUrl/lrn/cancel/$lrNo");

			Log::info('Delhivery Cancel Shipment LRN', [
				'lrNo' => $lrNo,
				'response' => $response->json() ?? $response->body(),
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

	public function trackOrderByLrNo($lrNo, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		try {
			$baseUrl = rtrim($delhivery->url ?? '', '/');

			// Make API request
			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $delhivery->api_key,
				'Accept' => 'application/json',
			])->withOptions([
						'verify' => false,
					])->get("$baseUrl/lrn/track", ['lrnum' => $lrNo]);
			
			Log::info('Delhivery Track Order LRN', [
				'lrNo' => $lrNo,
				'response' => $response->json() ?? $response->body(),
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

	public function shippingLableByLrNo($lrNo, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		try {
			$baseUrl = rtrim($delhivery->url ?? '', '/');

			// Make API request
			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $delhivery->api_key,
				'Accept' => 'application/json',
			])->withOptions([
						'verify' => false,
					])->get("$baseUrl/label/get_urls/std/$lrNo");

			Log::info('Delhivery Shipping Label LRN', [
				'lrNo' => $lrNo,
				'response' => $response->json() ?? $response->body(),
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

	public function getLabelImage($baseUrl)
	{
		try {

			// Make API request
			$response = Http::get($baseUrl);

			Log::info('Delhivery Label Image', [
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

	public function waybillCopyByLrNo($lrNo, $delhivery)
	{
		$delhivery = $this->ensureTokenIsValid($delhivery);
		try {
			$baseUrl = rtrim($delhivery->url ?? '', '/');

			// Make API request
			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $delhivery->api_key,
				'Accept' => 'application/json',
			])->withOptions([
						'verify' => false,
					])->get("$baseUrl/lr_copy/print/$lrNo");

			Log::info('Delhivery Waybill Copy LRN', [
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

}
