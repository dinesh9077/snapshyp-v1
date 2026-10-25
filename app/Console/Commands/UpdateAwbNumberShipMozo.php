<?php
	namespace App\Console\Commands;

	use Illuminate\Console\Command;
	use App\Models\ShippingCompany;
	use Carbon\Carbon;
	use App\Models\Order;
	use App\Services\ShipMozo;
	use Illuminate\Support\Facades\Log;

	class UpdateAwbNumberShipMozo extends Command
	{
		protected $signature = 'update:awb-number-shipmozo';
		protected $description = 'Update Delhivery token every night at 11 PM';
		
		protected $shipMozo;

		public function __construct(ShipMozo $shipMozo)
		{
			parent::__construct();
			$this->shipMozo = $shipMozo;
		}

		public function handle()
		{
			$orders = Order::with('shippingCompany')
			->select('id', 'shipping_company_id', 'awb_number', 'shipment_id')
			->whereNotNull('shipment_id')
			->where('shipping_company_id', 2)
			->where(function ($q) {
				$q->whereNull('awb_number')
					->orWhere('awb_number', '');
			})
			->get();
			
			if(empty($orders))
			{
				return;
			}
			try { 
				foreach($orders as $order)
				{
					if(!$order->shippingCompany)
					{
						continue;
					}
					// Fetch the token from Delhivery API
					$response = $this->shipMozo->getOrderDetails($order->shipment_id, $order->shippingCompany);

					// Validate response structure
					if (!$response['success'] || (isset($response['response']['result']) && $response['response']['result'] == 0)) {
						continue;
					} 
					 
					// Extract token
					$awbNumber = $response['response']['data'][0]['shipping_details']['awb_number'] ?? null;  
					// Update token in database
					$order->update([
						'awb_number' => $awbNumber 
					]);
				}
					$this->info("update awb number updated successfully.");
			} catch (\Exception $e) {
				$this->info($e->getMessage());
			}
		}
	}
