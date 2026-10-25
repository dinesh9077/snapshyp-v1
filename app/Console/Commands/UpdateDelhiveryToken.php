<?php
	namespace App\Console\Commands;

	use Illuminate\Console\Command;
	use App\Models\ShippingCompany;
	use Carbon\Carbon;
	use App\Services\DelhiveryService;
	use Illuminate\Support\Facades\Log;

	class UpdateDelhiveryToken extends Command
	{
		protected $signature = 'update:delhivery-token';
		protected $description = 'Update Delhivery token every night at 11 PM';
		
		protected $delhiveryService;

		public function __construct(DelhiveryService $delhiveryService)
		{
			parent::__construct();
			$this->delhiveryService = $delhiveryService;
		}

		public function handle()
		{
			$ids = [5];
			foreach ($ids as $id) {
				try {
					$shippingCompany = ShippingCompany::find($id);
					if (!$shippingCompany || $shippingCompany->status != 1) {
						continue;
					}

					$data = $this->delhiveryService->getToken($shippingCompany);

					if (!isset($data['success']) || !$data['success']) {
						Log::error("Failed to update Delhivery token for ID $id", ['response' => $data]);
						continue;
					}

					$token = $data['response']['data']['jwt'] ?? null;
					
					if ($token) {
						$shippingCompany->update([
							'api_key' => $token,
							'expired_at' => Carbon::now()->addHours(23),
							'updated_at' => Carbon::now(),
						]);
						$this->info("Delhivery token for ID $id updated successfully.");
					}
				} catch (\Exception $e) {
					$this->error("Error updating Delhivery token for ID $id: " . $e->getMessage());
				}
			}
		}
	}
