<?php
	namespace App\Console\Commands;

	use Illuminate\Console\Command;
	use App\Models\ShippingCompany;
	use App\Models\Order;
	use Carbon\Carbon;
	use App\Services\DelhiveryService;
	use Illuminate\Support\Facades\Log;

	class UpdateDelhiveryLrNo extends Command
	{
		protected $signature = 'update:delhivery-lrno';
		protected $description = 'Update Delhivery LR number and AWB number every 5 minutes';
		
		protected $delhiveryService;

		public function __construct(DelhiveryService $delhiveryService)
		{
			parent::__construct();
			$this->delhiveryService = $delhiveryService;
		}

		public function handle()
		{
			try {
				
				$orders = Order::where('shipping_company_id', 1)
                ->whereNull('lr_no')
                ->whereNotNull('shipment_id')
                ->with('shippingCompany') 
                ->get();
				 
				if($orders->isEmpty())
				{
					return;
				}
					
				foreach($orders as $order)
				{ 
					$shippingCompany = $order->shippingCompany ?? null;

					if (!$shippingCompany) { 
						continue;
					}

					// Fetch manifest status from Delhivery
					$manifestStatusResponse = $this->delhiveryService->manifestStatus($order->shipment_id, $shippingCompany);
					
					if (!($manifestStatusResponse['success'] ?? false)) { 
						continue;
					}  
					
					if ((isset($manifestStatusResponse['response']['success']) && !$manifestStatusResponse['response']['success']))
					{
						continue;
					} 
					if($manifestStatusResponse['response']['data']['status'] == "DataError")
					{
						continue;
					}
					
					// Extract LR number and AWB number
					$responseData = $manifestStatusResponse['response']['data'] ?? [];
					
					$lr_no = $responseData['lrnum'] ?? null;
					$awb_number = $responseData['master_waybill'] ?? null;
					
					// Update order details only if LR number is available
					if ($lr_no) {
						$order->update([
							'lr_no' => $lr_no,
							'awb_number' => $awb_number,
							'api_response' => $manifestStatusResponse,
						]);  
					}  
				}
				$this->info("Delhivery lr and awb number updated successfully.");
			} catch (\Exception $e) {
				$this->info($e->getMessage());
			}
		}
	}
