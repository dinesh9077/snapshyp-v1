<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order; 
use App\Models\StatusMapping;
use App\Services\ShadowfaxService;
use Illuminate\Support\Facades\Artisan;
use Log;
use App\Helpers\Helper;


class UpdateOrderStatus extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'shipment:update-status {status_courier}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Shipment status update command'; 
 
	public function handle()
	{ 
		error_reporting(0);

		$statusCourier = $this->argument('status_courier');

		$statusCourier = $this->argument('status_courier');
		if (empty($statusCourier)) {
			return true;
		}

		Log::info('UpdateOrderStatus: Starting', [
			'status_courier' => $statusCourier,
			'timestamp' => now()->toDateTimeString()
		]);

		$statusCourierMap = StatusMapping::where('internal_status', $statusCourier)->first()->courier_status ?? [];
		// Optimize query with chunking and selective loading 
		$today = now()->toDateString();
		$processedCount = 0;
		$updatedCount = 0;

		$statusMappings = StatusMapping::orderBy('position')->pluck('courier_status', 'internal_status')->toArray();
		 
		// Process orders in chunks to reduce memory usage
		Order::with(['shippingCompany'])
		->whereIn('status_courier', $statusCourierMap)
		->whereNotNull('awb_number')
		->where('awb_number', '!=', '')
		// send_whatsapp_notify is needed for the "already notified" check (else messages repeat)
		->select(['id', 'status_courier', 'awb_number', 'lr_no', 'shipping_company_id', 'user_id', 'send_whatsapp_notify'])
		// chunkById pages by id: chunk() pages by offset and skips rows once status_courier is updated
		->chunkById(50, function ($orders) use ($today, &$processedCount, &$updatedCount, &$statusMappings) {
			
			// Group orders by shipping company to optimize API calls
			$ordersByCompany = $orders->groupBy('shipping_company_id'); 
			foreach ($ordersByCompany as $companyId => $companyOrders) { 
				$this->processOrdersByCompany($companyOrders, $today, $processedCount, $updatedCount, $statusMappings);
			}
		});
			
		Log::info('UpdateOrderStatus: Completed', [
			'processed' => $processedCount,
			'updated' => $updatedCount,
			'timestamp' => now()->toDateTimeString()
		]);
		$this->info("Processed {$processedCount} orders, updated {$updatedCount} statuses.");
	}

	/**
	 * Process orders grouped by shipping company for better performance
	 */
	private function processOrdersByCompany($orders, $today, &$processedCount, &$updatedCount, &$statusMappings)
	{
		foreach ($orders as $order) {
			$processedCount++;
			$tempStatus = $order->status_courier; 
			
			Log::info('Processing order', [
				'order_id' => $order->id,
				'awb' => $order->awb_number,
				'current_status' => $tempStatus,
				'shipping_company_id' => $order->shipping_company_id
			]);
			
			// Delivered / cancelled / RTO delivered etc. never change again
			if ($this->isFinalStatus($tempStatus)) {
				continue;
			}

			$newStatus = $this->calculateNewStatus($order);

			if (strtolower(trim((string) $tempStatus)) !== strtolower(trim((string) $newStatus)) && !empty($newStatus)) {
				Log::info('Status changed', [
					'order_id' => $order->id,
					'awb' => $order->awb_number,
					'old_status' => $tempStatus,
					'new_status' => $newStatus
				]);
				
				// Normalize status
				if ($newStatus === 'Shipment delivered') {
					$newStatus = 'delivered';
				}

				$updateData = ['status_courier' => strtolower($newStatus)];
				
				// Handle delivery status
				if (strtolower($newStatus) === 'delivered') {
					$updateData['delivery_date'] = $today;
					
					// Queue WhatsApp notification instead of sending immediately
					try {
						dispatch(function () use ($order) {
							$whatsappService = app(\App\Services\WhatsappService::class);
							$whatsappService->sendOrderNotification($order->fresh(), 'delivered');
						});
					} catch (\Exception $e) {
						Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
					}
				}

				if (in_array(strtolower($newStatus), array_map('strtolower', $statusMappings['Out For Delivery']))) {

					try {
						// Ensure send_whatsapp_notify is always an array
						$notifiedStatuses = is_array($order->send_whatsapp_notify)
							? $order->send_whatsapp_notify
							: json_decode($order->send_whatsapp_notify, true) ?? [];

						if (!in_array('out_for_delivery', $notifiedStatuses)) {
							// Dispatch queued job (best practice: use a Job class)
							dispatch(function () use ($order) {
								$whatsappService = app(\App\Services\WhatsappService::class);
								$whatsappService->sendOrderNotification($order, 'out_for_delivery');

								// Refresh order and update notification log
								$updatedOrder = $order->fresh();
								$notified = is_array($updatedOrder->send_whatsapp_notify)
									? $updatedOrder->send_whatsapp_notify
									: json_decode($updatedOrder->send_whatsapp_notify, true) ?? [];

								$notified[] = 'out_for_delivery';
								$updatedOrder->update(['send_whatsapp_notify' => $notified]);
							}); // optional: set queue name
						}
					} catch (\Exception $e) {
						Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
					}
				}

				if (in_array(strtolower($newStatus), array_map('strtolower', $statusMappings['In Transit']))) {

					try {
						// Ensure send_whatsapp_notify is always an array
						$notifiedStatuses = is_array($order->send_whatsapp_notify)
							? $order->send_whatsapp_notify
							: json_decode($order->send_whatsapp_notify, true) ?? [];

						if (!in_array('in_transit__reached_hub', $notifiedStatuses)) {
							// Dispatch queued job (best practice: use a Job class)
							dispatch(function () use ($order) {
								$whatsappService = app(\App\Services\WhatsappService::class);
								$whatsappService->sendOrderNotification($order, 'in_transit__reached_hub');

								// Refresh order and update notification log
								$updatedOrder = $order->fresh();
								$notified = is_array($updatedOrder->send_whatsapp_notify)
									? $updatedOrder->send_whatsapp_notify
									: json_decode($updatedOrder->send_whatsapp_notify, true) ?? [];

								$notified[] = 'in_transit__reached_hub';
								$updatedOrder->update(['send_whatsapp_notify' => $notified]);
							}); // optional: set queue name
						}
					} catch (\Exception $e) {
						Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
					}
				} 

				if (in_array(strtolower($newStatus), array_map('strtolower', $statusMappings['RTO All']))) {

					try {
						// Ensure send_whatsapp_notify is always an array
						$notifiedStatuses = is_array($order->send_whatsapp_notify)
							? $order->send_whatsapp_notify
							: json_decode($order->send_whatsapp_notify, true) ?? [];

						if (!in_array('rto_return_to_origin', $notifiedStatuses)) {
							// Dispatch queued job (best practice: use a Job class)
							dispatch(function () use ($order) {
								$whatsappService = app(\App\Services\WhatsappService::class);
								$whatsappService->sendOrderNotification($order, 'rto_return_to_origin');

								// Refresh order and update notification log
								$updatedOrder = $order->fresh();
								$notified = is_array($updatedOrder->send_whatsapp_notify)
									? $updatedOrder->send_whatsapp_notify
									: json_decode($updatedOrder->send_whatsapp_notify, true) ?? [];

								$notified[] = 'rto_return_to_origin';
								$updatedOrder->update(['send_whatsapp_notify' => $notified]);
							}); // optional: set queue name
						}
					} catch (\Exception $e) {
						Log::error('WhatsApp notification queue failed: ' . $e->getMessage());
					}
				} 

				// Bulk update using query builder for better performance
				$order->update($updateData);

				Helper::orderActivity($order->id, "Order status updated: {$tempStatus} -> " . strtolower($newStatus) . " (auto_sync)");

				// Apply RTO billing/refund immediately on first RTO transition.
				if (in_array(strtolower($newStatus), array_map('strtolower', $statusMappings['RTO All'] ?? []))) {
					Artisan::call('order:apply-rto-charge', ['order_id' => $order->id]);
				}
				$updatedCount++;
				
				Log::info('Order status updated', [
					'order_id' => $order->id,
					'awb' => $order->awb_number,
					'final_status' => $newStatus
				]);
			}
		}
	}

	// Same final states as the Shadowfax webhook
	private function isFinalStatus(?string $status): bool
	{
		$status = strtolower(trim(str_replace(['_', '-'], ' ', (string) $status)));
		return in_array($status, ['delivered', 'cancelled', 'rto delivered', 'lost', 'damaged'], true);
	}

	private function calculateNewStatus($order)
	{
		$status = $order->status_courier;
		
		// Early return if no AWB number
		if (empty($order->awb_number)) {
			return $status;
		}
		
		$shippingCompany = $order->shippingCompany;
		if (!$shippingCompany) {
			return $status;
		}

		try {
			if ($shippingCompany->isShadowfax()) {
				// Track with the account the order was booked on (each Shadowfax account has its own API key)
				$shadowfax = app(ShadowfaxService::class);
				$tracking = $shadowfax->trackOrder($order->awb_number, $shippingCompany);
				if (!($tracking['success'] ?? false)) {
					Log::warning('Shadowfax tracking failed', [
						'order_id' => $order->id,
						'awb' => $order->awb_number,
						'shipping_company_id' => $shippingCompany->id,
						'message' => $tracking['message'] ?? null,
					]);
					return $status;
				}

				$courierStatus = $shadowfax->currentStatusFromTracking($tracking);
				return $courierStatus !== null ? ShadowfaxService::normalizeStatus($courierStatus) : $status;
			}

			Log::warning('Unsupported shipping company for tracking', [
				'shipping_company_id' => $shippingCompany->id,
				'order_id' => $order->id
			]);
		} catch (\Exception $e) {
			Log::error("Status tracking failed for order {$order->id}: " . $e->getMessage());
		}

		return $status;
	}
}
