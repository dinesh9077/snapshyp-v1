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

class DelhiveryB2CService
{

	public function createWarehouse($courierWarehouse, $delhivery)
	{
		$pickup_address = implode(' ', [
			trim($courierWarehouse->address),
			trim($courierWarehouse->city),
			trim($courierWarehouse->state),
			trim($courierWarehouse->zip_code)
		]) . ', ' . trim($courierWarehouse->country);

		$requestBody = [
			"phone" => trim($courierWarehouse->contact_number),
			"city" => trim($courierWarehouse->city),
			"name" => trim($courierWarehouse->warehouse_name),
			"pin" => trim($courierWarehouse->zip_code),
			"address" => $pickup_address,
			"country" => trim($courierWarehouse->country),
			"email" => "",
			"registered_name" => trim($courierWarehouse->company_name ?? ''),
			"return_address" => $pickup_address,
			"return_pin" => trim($courierWarehouse->zip_code),
			"return_city" => trim($courierWarehouse->city),
			"return_state" => trim($courierWarehouse->state),
			"return_country" => trim($courierWarehouse->country)
		];

		$baseUrl = rtrim($delhivery->url ?? '', '/');

		$response = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->post($baseUrl . '/api/backend/clientwarehouse/create/', $requestBody);

		// Handle the response
		$responseBody = $response->body();
		$dataArray = null;

		// 1. Try JSON decoding first
		$decodedJson = json_decode($responseBody, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decodedJson)) {
			// It's JSON
			$dataArray = $decodedJson;
		} else {
			// 2. Try XML decoding (Fallback)
			// Suppress errors to avoid crashing if it's plain text/HTML
			libxml_use_internal_errors(true);
			$xml = simplexml_load_string($responseBody, "SimpleXMLElement", LIBXML_NOCDATA);
			if ($xml !== false) {
				$json = json_encode($xml);
				$dataArray = json_decode($json, true);
			} else {
				// 3. Raw response (Text/HTML)
				$dataArray = ['error' => 'Invalid response structure', 'raw' => $responseBody];
				libxml_clear_errors();
			}
		}

		Log::info('DelhiveryB2C Create Warehouse', [
			'request' => $requestBody,
			'response' => $dataArray,
			'status' => $response->status()
		]);

		if ($response->successful() && $dataArray) {
			return [
				'success' => true,
				'request' => $requestBody,
				'response' => $dataArray,
			];
		}

		return [
			'success' => false,
			'request' => $requestBody,
			'response' => $dataArray,
		];
	}

	public function updateWarehouse($courierWarehouse, $delhivery)
	{
		$pickup_address = trim($courierWarehouse->address);

		$requestBody = [
			"name"            => trim($courierWarehouse->warehouse_name),
			"phone"           => trim($courierWarehouse->contact_number ?? ''),
			"address"         => $pickup_address,
			"registered_name" => trim($courierWarehouse->company_name ?? $courierWarehouse->contact_name ?? ''),
		];

		$baseUrl = rtrim($delhivery->url ?? '', '/');
		$response = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->post($baseUrl . '/api/backend/clientwarehouse/edit/', $requestBody);

		$responseBody = $response->body();
		$dataArray = null;

		// 1. Try JSON decoding first
		$decodedJson = json_decode($responseBody, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decodedJson)) {
			// It's JSON
			$dataArray = $decodedJson;
		} else {
			// 2. Try XML decoding (Fallback)
			// Suppress errors to avoid crashing if it's plain text/HTML
			libxml_use_internal_errors(true);
			$xml = simplexml_load_string($responseBody, "SimpleXMLElement", LIBXML_NOCDATA);
			if ($xml !== false) {
				$json = json_encode($xml);
				$dataArray = json_decode($json, true);
			} else {
				// 3. Raw response (Text/HTML)
				$dataArray = ['error' => 'Invalid response structure', 'raw' => $responseBody];
				libxml_clear_errors();
			}
		}

		if ($response->successful() && $dataArray) {
			return [
				'success' => true,
				'request' => $requestBody,
				'response' => $dataArray,
			];
		}

		// If the response was unsuccessful, return an error response
		return [
			'success' => false,
			'request' => $requestBody,
			'response' => $dataArray,
		];
	}

	public function pincodeService($pinCode, $delhivery)
	{
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		$response = Http::withHeaders([
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->get("{$baseUrl}/c/api/pin-codes/json/?token={$delhivery->api_key}&filter_codes={$pinCode}");

		Log::info('DelhiveryB2C Pincode Service', [
			'pincode' => $pinCode,
			'response' => $response->json() ?? $response->body(),
			'status' => $response->status()
		]);

		// Handle the response
		if ($response->successful()) {
			return [
				'success' => $response->json()['delivery_codes'] ? true : false,
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
		$totalWeightInKg = $order->weight ?? 0;
		$totalInvoiceAmount = $order->orderItems->sum(fn($item) => $item->amount ?? 0);
		$sourcePin = $order->warehouse->zip_code ?? '';
		$consigneePin = $order->customerAddress->zip_code ?? '';

		$queryParam = [
			'md' => $order->shipping_mode == 'Surface' ? 'S' : 'E',
			'ss' => 'Delivered',
			'd_pin' => $sourcePin,
			'o_pin' => $consigneePin,
			'cgm' => $totalWeightInKg * 1000,
			'pt' => OrderType::isCod($order->order_type) ? 'COD' : 'Pre-paid',
			'cod' => OrderType::isCod($order->order_type) ? $order->cod_amount : 0,
		];

		$url = rtrim($delhivery->url, '/') . "/api/kinko/v1/invoice/charges/.json?" . http_build_query($queryParam);

		$response = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->get($url);

		Log::info('DelhiveryB2C Freight Estimate', [
			'request' => $queryParam,
			'response' => $response->json() ?? $response->body(),
			'status' => $response->status()
		]);

		// Handle the response
		if ($response->successful()) {
			return [
				'success' => true,
				'request' => $queryParam,
				'response' => $response->json(),
			];
		}

		// If the response was unsuccessful, return an error response
		return [
			'success' => false,
			'request' => $queryParam,
			'response' => json_decode($response->body(), true),
		];
	}

	public function rateCaculator($requestData, $delhivery)
	{
		$queryParam = [
			'md' => 'S',
			'ss' => 'Delivered',
			'd_pin' => $requestData['pickup_code'] ?? '',
			'o_pin' => $requestData['delivery_code'] ?? '',
			'cgm' => ($requestData['weight'] * 1000) ?? 0,
			'pt' => OrderType::isCod($requestData['payment_type'] ?? '') ? 'COD' : 'Pre-paid',
			'cod' => OrderType::isCod($requestData['payment_type'] ?? '') ? $requestData['cod_amount'] : 0,
		];

		$url = rtrim($delhivery->url, '/') . "/api/kinko/v1/invoice/charges/.json?" . http_build_query($queryParam);

		$response = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'verify' => false,
			])
			->get($url);

		Log::info('DelhiveryB2C Rate Calculator', [
			'request' => $queryParam,
			'response' => $response->json() ?? $response->body(),
			'status' => $response->status()
		]);

		// Handle the response
		if ($response->successful()) {
			return [
				'success' => true,
				'request' => $queryParam,
				'response' => $response->json(),
			];
		}

		// If the response was unsuccessful, return an error response
		return [
			'success' => false,
			'request' => $queryParam,
			'response' => json_decode($response->body(), true),
		];
	}

	public function manifest($order, $requestData, $delhivery)
	{
		// Business rule: an order must be COD or Prepaid before any shipment is booked.
		OrderType::validate($order->order_type);

		$totalWeightInGm = ($requestData['applicable_weight'] ?? 0) * 1000;

		$consigneeName = implode(' ', [
			$order->customer->first_name ?? '',
			$order->customer->last_name ?? ''
		]);
		$consigneeName = trim(preg_replace('/\s+/', ' ', $consigneeName));

		$customerAddress = $order->customerAddress ?? null;

		$customer_address = $customerAddress
			? preg_replace('/\s+/', ' ', implode(' ', array_filter([
				trim($customerAddress->address ?? '')
			])))
			: '';

		$warehouse = $order->warehouse ?? null;
		$pickup_address = '';

		if ($warehouse) {
			$addressParts = array_filter([
				trim($warehouse->address ?? ''),
				trim($warehouse->city ?? ''),
				trim($warehouse->state ?? ''),
				trim($warehouse->zip_code ?? '')
			]);

			$pickup_address = preg_replace('/\s+/', ' ', implode(', ', $addressParts));

			if (!empty($warehouse->country)) {
				$pickup_address .= ', ' . trim($warehouse->country);
			}
		}

		// Build product description list
		$product_name = ["Order Id-{$order->order_prefix}"];
		foreach ($order->orderItems ?? [] as $orderItems) {
			$product_name[] = "Product Category - {$orderItems->product_category}";
		}
		$productNamesString = implode(', ', array_unique($product_name));

		// sanitize helpers: remove only these forbidden characters: &, #, %, ;, \
		$removeForbiddenChars = function ($str) {
			if ($str === null)
				return '';
			// remove &, #, %, ; and backslash
			$clean = preg_replace('/[&#%;\\\\]/u', '', (string) $str);
			// remove control/non-printable characters
			$clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
			// collapse multiple whitespaces
			$clean = preg_replace('/\s+/', ' ', $clean);
			return trim($clean);
		};

		// Recursively sanitize array/object values (only strings are altered)
		$sanitizeArrayRecursive = function (&$arr) use (&$sanitizeArrayRecursive, $removeForbiddenChars) {
			if (is_array($arr)) {
				foreach ($arr as $k => &$v) {
					if (is_string($v)) {
						$v = $removeForbiddenChars($v);
					} elseif (is_array($v)) {
						$sanitizeArrayRecursive($v);
					} elseif (is_object($v)) {
						$v = json_decode(json_encode($v), true);
						$sanitizeArrayRecursive($v);
					}
				}
				return $arr;
			}
			return $arr;
		};

		// Prepare cleaned address/name strings (for immediate use)
		$productsDescClean = $removeForbiddenChars($productNamesString);
		$productsDescClean = preg_replace('/\s+/', ' ', $productsDescClean);
		$productsDescClean = trim($productsDescClean);

		$consigneeNameClean = $removeForbiddenChars($consigneeName);
		$pickupAddressClean = $removeForbiddenChars($pickup_address);
		$customerAddressClean = $removeForbiddenChars($customer_address);

		// Prepare structured pickup_location array
		$pickupLocationArray = [
			'name' => trim($warehouse->warehouse_name ?? ''),
			'city' => $warehouse->city ?? '',
			'pin' => $warehouse->zip_code ?? '',
			'country' => $warehouse->country ?? '',
			'phone' => $warehouse->contact_number ?? '',
			'add' => $pickupAddressClean,
			'client' => $warehouse->api_response['data']['client'] ?? "SNAPITSOLUTIONS FRANCHISE"
		];

		// Build payload (kept same structure as your original)
		$data = [
			"shipments" => [
				[
                    "name" => trim($consigneeNameClean),
					"add" => (string) trim($customerAddressClean),
                    "pin" => $order->customerAddress->zip_code ?? '',
                    "city" => trim($customerAddress->city ?? ''),
					"state" => trim($customerAddress->state ?? ''),
					"country" => trim($customerAddress->country ?? ''),
                    "phone" => $order->customer->mobile ?? '',
                    "order" => $order->order_prefix,
                    "payment_mode" => OrderType::isCod($order->order_type) ? 'COD' : 'Prepaid',
                    "return_pin" => "",
                    "return_city" => "",
                    "return_phone"=> "",
                    "return_add" => $pickupAddressClean,
                    "return_state" => "",
                    "return_country" => "",
                    "products_desc" => $productsDescClean,
                    "hsn_code"=> "",
                    "cod_amount" => $order->cod_amount,
                    "order_date" => now()->toDateString(),
                    "total_amount" => $order->total_amount,
                    "seller_add" => $pickupAddressClean,
                    "seller_name" => "",
                    "seller_inv"=> "",
                    "quantity"=> 1,
                    "waybill" => $order->awb_number ?? '',
                    "shipment_width" => $order->width,
                    "shipment_height" => $order->height,
                    "weight" => (int) $totalWeightInGm,
                    "shipping_mode" => (empty($order->shipping_mode) || strtolower($order->shipping_mode) == 'surface') ? 'Surface' : 'Express',
                    "address_type"=> "",
				]
			],
			"pickup_location" => $pickupLocationArray,
			"client" => $warehouse->api_response['data']['client'] ?? "SF MARUTI 0147412"
		];

		// If multi-piece shipment, populate shipments per box (keeps original idea)
		if (!empty($order->is_multi_piece) && !empty($order->boxes) && is_array($order->boxes)) {
			$shipments = [];
			foreach ($order->boxes as $box) {
				$boxEntry = $data['shipments'][0]; // base template
				$boxEntry['quantity'] = $box['quantity'] ?? $boxEntry['quantity'];
				$boxEntry['waybill'] = $box['waybill'] ?? $boxEntry['waybill'];
				$boxEntry['weight'] = ($box['weight'] ?? $boxEntry['weight']);
				if (!empty($box['products']) && is_array($box['products'])) {
					$boxEntry['products_desc'] = implode(', ', $box['products']);
				}
				$shipments[] = $boxEntry;
			}
			if (!empty($shipments)) {
				$data['shipments'] = $shipments;
			}
		}

		// Sanitize entire payload recursively to ensure no forbidden chars remain anywhere
		$sanitized = $data; // copy
		$sanitizeArrayRecursive($sanitized);

		// JSON encode sanitized payload (will be sent as form 'data')
		$jsonData = json_encode($sanitized, JSON_UNESCAPED_UNICODE);

		$baseUrl = rtrim($delhivery->url ?? '', '/');

		try {
			// use asForm to send format=json & data=<json string> as application/x-www-form-urlencoded
			$response = Http::withHeaders([
				'Authorization' => 'Token ' . ($delhivery->api_key ?? '')
			])->asForm()
				->withOptions(['verify' => false])
				->post($baseUrl . '/api/cmu/create.json', [
					'format' => 'json',
					'data' => $jsonData
				]);

			Log::info('DelhiveryB2C Manifest', [
				'request' => $sanitized,
				'response' => $response->json() ?? $response->body(),
				'status' => $response->status()
			]);

			if ($response->successful()) {
				return [
					'success' => true,
					'request' => [
						'format' => 'json',
						'data' => $sanitized
					],
					'response' => $response->json(),
				];
			}

			return [
				'success' => false,
				'request' => [
					'format' => 'json',
					'data' => $sanitized
				],
				'response' => json_decode($response->body(), true),
				'status' => $response->status()
			];
		} catch (\Throwable $e) {
			Log::error('DelhiveryB2C Manifest Error', ['error' => $e->getMessage()]);
			return [
				'success' => false,
				'request' => [
					'format' => 'json',
					'data' => $sanitized
				],
				'response' => ['error' => $e->getMessage()],
			];
		}
	}


	private function sendManifestRequest(string $baseUrl, $delhivery, array $payload)
	{
		$jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

		return Http::withHeaders([
			'Authorization' => 'Token ' . ($delhivery->api_key ?? '')
		])->asForm()
			->withOptions(['verify' => false])
			->post($baseUrl . '/api/cmu/create.json', [
				'format' => 'json',
				'data' => $jsonData
			]);
	}


	public function createPickupRequest($requestData, $delhivery)
	{
		$warehouseId = $requestData['warehouse_id'] ?? null;
		$courierWarehouse = $warehouseId ? CourierWarehouse::find($warehouseId) : null;

		if (!$courierWarehouse) {
			return ['success' => false, 'response' => ['error' => ['message' => 'Warehouse not found or ID missing.']]];
		}

		$startTime = ($requestData['pickup_start_time'] ?? null) ? date('H:i:s', strtotime($requestData['pickup_start_time'])) : '';
		$endTime = ($requestData['pickup_end_time'] ?? null) ? date('H:i:s', strtotime($requestData['pickup_end_time'])) : '';

		$requestBody = [
			'pickup_location' => trim($courierWarehouse->warehouse_name),
			'pickup_date' => $requestData['pickup_date'] ?? '',
			'pickup_time' => (string) $endTime,
			'expected_package_count' => (int) ($requestData['expected_package_count'] ?? 0),
		];

		// API Base URL
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		// Start the HTTP request
		$request = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Content-Type' => 'application/json',
		])->withOptions([
			'verify' => false,
		]);
		$response = $request->post($baseUrl . '/fm/request/new/', $requestBody);

		Log::info('DelhiveryB2C Pickup Request', [
			'request' => $requestBody,
			'response' => $response->json() ?? $response->body(),
			'status' => $response->status()
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
	}

	public function cancelShipmentByAwbNumber($awbNumber, $delhivery)
	{
		$baseUrl = rtrim($delhivery->url ?? '', '/');

		$requestBody = [
			'waybill' => $awbNumber,
			'cancellation' => "true"
		];

		// Make API request
		$response = Http::withHeaders([
			'Authorization' => 'Token ' . $delhivery->api_key,
			'Accept' => 'application/json',
		])->withOptions([
			'verify' => false,
		])->post($baseUrl . "/api/p/edit", $requestBody);

		Log::info('DelhiveryB2C Cancel Shipment', [
			'awb' => $awbNumber,
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
	}

	public function trackOrderByAwbNumber($awbNumber, $delhivery)
	{
		try {
			$baseUrl = rtrim($delhivery->url ?? '', '/');
			$token = $delhivery->api_key;

			// Build URL exactly like your working cURL
			$fullUrl = $baseUrl . '/api/v1/packages/json?waybill=' . urlencode($awbNumber) . '&token=' . urlencode($token);

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => $fullUrl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 300,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
			));

			$responseBody = curl_exec($curl);
			$error = curl_error($curl);

			curl_close($curl);

			Log::info('DelhiveryB2C Track Order', [
				'awb' => $awbNumber,
				'response' => $responseBody,
				'error' => $error
			]);

			// Curl error
			if ($error) {
				return [
					'success' => false,
					'response' => ['error' => 'CURL Error: ' . $error],
				];
			}

			// Convert JSON
			$json = json_decode($responseBody, true);

			return [
				'success' => true,
				'response' => $json,
			];
		} catch (\Exception $e) {
			Log::error('DelhiveryB2C Track Order Error', ['error' => $e->getMessage()]);
			return [
				'success' => false,
				'response' => ['error' => 'Unexpected error'],
			];
		}
	}
}
