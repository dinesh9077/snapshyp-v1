<?php
	namespace App\Services;
	use App\Models\ShippingCompany;
	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Http;
	use App\Models\Order;
	use App\Services\{DelhiveryB2CService};

	class MasterService
	{
		public function getShippingCompanies($status = null)
		{
			$query = ShippingCompany::query();
			if($status)
			{
				$query->where('status', $status);
			}
			return $query->get();
		}

		public function getShippingCompaniesById($shippingCompanyId = null)
		{
			$query = ShippingCompany::where('id', $shippingCompanyId)->where('status', 1);
			return $query->first();
		}

		public function getRoles($status = null)
		{
			$query = Role::query();
			$authUser = Auth::user();
			$accessibleOwnerIds = $this->getAccessibleRoleOwnerIds($authUser);
			if (!empty($accessibleOwnerIds)) {
				$query->whereIn('user_id', $accessibleOwnerIds);
			}
			if($status)
			{
				$query->where('status', $status);
			}
			return $query->get();
		}

		private function getAccessibleRoleOwnerIds($authUser): array
		{
			if (!$authUser || $authUser->isMasterAdmin()) {
				return [];
			}

		

			if ($authUser->role === 'staff') {
				$creator = $authUser->createdBy;
				if ($creator && $creator->role === 'franchise') {
					return [(int) $creator->id];
				}
			}

			return [];
		}

		public function rechargeOrderCreate($amount)
		{
			try {
				$orderAmount = (int) round((float) $amount * 100); // whole paise (avoid float 20009.999…)
				$orderUrl = config('services.recharge_gateway.url') . '/create-order-gateway';

				$response = Http::withOptions(['verify' => true])
				->withHeaders([
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				])
				->post($orderUrl, [
					"secret_key" => config('services.recharge_gateway.secret'),
					"user"       => config('services.recharge_gateway.user'),
					"amount"     => $orderAmount,
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
					'response' => ['error' => 'Unable to connect to order service.'],
					'message'  => $e->getMessage()
				];
			}
		}

		public function getRechargeStatus($orderId)
		{
			try {
				$orderUrl = config('services.recharge_gateway.url') . '/check-payment-gateway';

				$response = Http::withOptions(['verify' => true])
					->withHeaders([
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
					])
					->post($orderUrl, [
						"secret_key" => config('services.recharge_gateway.secret'),
						"user" => config('services.recharge_gateway.user'),
						"id" => $orderId,
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
					'response' => ['error' => 'Unable to connect to order service.'],
					'message' => $e->getMessage()
				];
			}
		}

		public function calculateCharge($orderId, $request, $user = null)
		{
			$delhiveryB2CService = app(DelhiveryB2CService::class);

			$order = Order::with(['warehouse', 'user', 'customerAddress', 'orderItems'])->find($orderId);
			if (!$order) {
				return;
			}

			$user = $order->user ?? null;
			if (!$user) {
			return;
			}

			if (
				empty($order->weight) || $order->weight <= 0 ||
				empty($order->length) || $order->length <= 0 ||
				empty($order->width) || $order->width <= 0 ||
				empty($order->height) || $order->height <= 0 ||
				empty($order->warehouse_id)
			) {
				return;
			}

			$role = $user->role;
			$charge = $user->charge;
			$charge_type = $user->charge_type;
			$shippingCompanyId = $request->courier_shipment_id;
			$shippingCompany = ShippingCompany::where('id', $shippingCompanyId)->first();
			$couriers = [];

			$volumetricWt = $order->length * $order->width * $order->height / 5000;
			$weight = $order->weight;

			if ((int) $shippingCompany->id === 1) {
				$routxService = app(RoutxService::class);

				// Mock order for RoutxService
				$mockOrder = (object)[
					'weight_order' => $order->weight_order ?? 1,
					'length' => (float)$order->length,
					'width' => (float)$order->width,
					'height' => (float)$order->height,
					'weight' => (float)$order->weight,
					'order_type' => $order->order_type ?? 'Prepaid',
					'cod_amount' => (float)($order->cod_amount ?? 0),
					'invoice_amount' => (float)$order->invoice_amount,
					'has_insurance' => (bool)$order->has_insurance,
					'is_appointment_delivery' => (bool)$order->is_appointment_delivery,
					'warehouse' => (object)['zip_code' => $order->warehouse->zip_code],
					'customerAddress' => (object)['zip_code' => $order->customerAddress->zip_code],
					'orderItems' => $order->orderItems
				];

				$response = $routxService->rateCalculator($mockOrder, $shippingCompany);

				if (!($response['success'] ?? false) || empty($response['response']['data'])) {
					return;
				}

				// For manual recalculation, we might just take the first matched option or based on request
				$apiData = $response['response']['data'];
				$selectedRate = $apiData[0]; // Default to first

				$freightCharges = (float)($selectedRate['FreightCharges'] ?? 0);
				$codCharges = (float)($selectedRate['CODCharges'] ?? 0);
				$tax = (float)($selectedRate['GST'] ?? 0);
				$totalCharges = (float)($selectedRate['TotalAmount'] ?? 0);

				return [
					'direct_rate' => $freightCharges,
					'order_id' => $orderId,
					'tax' => $tax,
					'round_off' => 0,
					'shipping_company_id' => $shippingCompany->id,
					'shipping_company_name' => $shippingCompany->name,
					'shipping_company_logo' => asset('storage/shipping-logo/' . $shippingCompany->logo),
					'courier_id' => $selectedRate['CarrierId'] ?? '',
					'courier_name' => $selectedRate['ServiceType'] ?? $shippingCompany->name,
					'freight_charges' => $freightCharges,
					'cod_charges' => $codCharges,
					'total_charges' => $totalCharges,
					'rto_charge' => 0,
					'min_weight' => $order->weight,
					'chargeable_weight' => max($volumetricWt, $weight) ?? 0,
					'applicable_weight' => max($volumetricWt, $weight) ?? 0,
					'percentage_amount' => 0,
					'responseData' => $selectedRate
				];
			}

			if (in_array((int) $shippingCompany->id, [3, 4, 5])) {
				$pincodeServiceData = $delhiveryB2CService->pincodeService($order->customerAddress->zip_code ?? '', $shippingCompany);

				if (
					!($pincodeServiceData['success'] ?? false) ||
					(isset($pincodeServiceData['response']['success']) && !$pincodeServiceData['response']['success'])
				) {
					return;
				}

				$response = $delhiveryB2CService->freightEstimate($order, $shippingCompany);

				if (
					!($response['success'] ?? false) ||
					(isset($response['response']['success']) && !$response['response']['success'])
				) {
					return;
				}

				$responseData = $response['response'] ?? [];
				if (!$responseData) {
					return;
				}

				foreach ($responseData as $responseValue) {
					$taxData = $responseValue['tax_data'] ? array_sum($responseValue['tax_data']) : 0;
					$totalAmount = $responseValue['charge_DL'] ?? 0;
					$chargeDph = $responseValue['charge_DPH'] ?? 0;
					$chargeCod = $responseValue['charge_COD'] ?? 0;
					$chargeRto = $responseValue['charge_RTO'] ?? 0;

					$totalCharges = $taxData + $totalAmount + $chargeDph + $chargeCod;

					$percentageAmount = ($role === "user" && $charge > 0)
						? ($charge_type == 1 ? $charge : ($totalCharges * $charge) / 100)
						: 0;
					$totalCharges += $percentageAmount;

					$tax = $shippingCompany->tax > 0 ? (($totalCharges * $shippingCompany->tax) / 100) : 0;
					$roundedTotalCharges = round($totalCharges + $tax);
					$roundOffAmount = $roundedTotalCharges - ($totalCharges + $tax);

					return [
						'direct_rate' => $totalAmount ?? 0,
						'order_id' => $orderId,
						'tax' => $tax,
						'round_off' => $roundOffAmount,
						'shipping_company_id' => $shippingCompany->id,
						'shipping_company_name' => $shippingCompany->name,
						'shipping_company_logo' => asset('storage/shipping-logo/' . $shippingCompany->logo),
						'courier_id' => '',
						'courier_name' => "{$shippingCompany->name} {$order->shipping_mode}",
						'freight_charges' => ($totalAmount + $chargeDph),
						'cod_charges' => $chargeCod,
						'total_charges' => $roundedTotalCharges,
						'rto_charge' => $chargeRto,
						'min_weight' => ($responseValue['charged_weight'] / 1000),
						'chargeable_weight' => max($volumetricWt, $weight) ?? 0,
						'applicable_weight' => max($volumetricWt, $weight) ?? 0,
						'percentage_amount' => $percentageAmount,
						'responseData' => $responseValue
					];
				}
			}
			return;
		}
	}
