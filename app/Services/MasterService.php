<?php
	namespace App\Services;
	use App\Models\ShippingCompany;
	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Http;

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
	}
