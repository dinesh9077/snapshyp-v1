<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\StatusMapping;
use App\Models\Billing; // Assuming this is your debit log model
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;


class ApplyRtoReturnCharge extends Command
{
	protected $signature = 'order:apply-rto-charge {order_id?}';
	protected $description = 'Apply RTO return charges on eligible orders';

	public function handle()
	{
		$orderId = $this->argument('order_id');

		$statusCourierMap = StatusMapping::where('internal_status', 'RTO All')->first()->courier_status ?? [];
		$normalizedStatusMap = collect((array) $statusCourierMap)
			->map(fn($s) => strtolower(trim((string) $s)))
			->filter()
			->values()
			->all();

		$eligibleOrders = Order::with(['user', 'user.rateCard'])
			->where(function ($q) use ($normalizedStatusMap) {
				if (!empty($normalizedStatusMap)) {
					$q->whereIn(DB::raw('LOWER(status_courier)'), $normalizedStatusMap);
				}
				$q->orWhere(DB::raw('LOWER(status_courier)'), 'like', 'rto%');
			})
			->when(!empty($orderId), function ($q) use ($orderId) {
				$q->where('id', (int) $orderId);
			})
			->where(function ($q) {
				$q->whereNull('rto_charge_applied')->orWhere('rto_charge_applied', 0);
			})
			->get();

		if ($eligibleOrders->isEmpty()) {
			$this->info("No eligible orders found.");
			return;
		}

		// One transaction per order: a bad order no longer rolls back (and blocks) all the others
		foreach ($eligibleOrders as $candidate) {
			if ($candidate->user && $candidate->user->rateCard && $candidate->user->rateCard->is_rto_charge == 0) {
				continue;
			}

			try {
			DB::transaction(function () use ($candidate) {
				// Lock and re-check: the scheduler and UpdateOrderStatus can both run this for the same order
				$order = Order::whereKey($candidate->id)->lockForUpdate()->first();
				if (!$order || $order->rto_charge_applied) {
					return;
				}
				$user = \App\Models\User::whereKey($order->user_id)->lockForUpdate()->first();
				if (!$user) {
					\Illuminate\Support\Facades\Log::warning('RTO charge skipped: order has no user', ['order_id' => $order->id]);
					return;
				}
				$totalShippingCharge = (float) ($order->shipping_charge ?? 0);
				$codCharge = (float) ($order->cod_charges ?? 0);

				// Fallback: some old orders may not have cod_charges saved.
				if ($codCharge <= 0) {
					$codCharge = (float) Billing::where('billing_type', 'Order')
						->where('billing_type_id', $order->id)
						->where('transaction_type', 'debit')
						->where(function ($q) {
							$q->where('note', 'like', '%COD charges debited%')
							  ->orWhere('note', 'like', '%COD charge debited%');
						})
						->sum('amount');
				}

				// Forward/Base part previously charged at ship time.
				$forwardCharge = (float) Billing::where('billing_type', 'Order')
					->where('billing_type_id', $order->id)
					->where('transaction_type', 'debit')
					->where('note', 'like', '%Forward shipping charges debited%')
					->sum('amount');

				// RTO rule: debit forward/base again, refund COD.
				$rtoCharge = $forwardCharge > 0
					? $forwardCharge
					: max(0, $totalShippingCharge - $codCharge);

				if ($rtoCharge > 0) {
					Billing::insert([
						'user_id'         => $order->user_id,
						'billing_type'    => "Order",
						'billing_type_id' => $order->id,
						'transaction_type' => 'debit',
						'amount'          => $rtoCharge,
						'note'            => "RTO base freight charges debited for AWB: {$order->awb_number}",
						'created_at'      => now(),
						'updated_at'      => now()
					]);
					$user->decrement('wallet_amount', $rtoCharge);
				}

				if ($codCharge > 0 && strtolower((string) ($order->order_type ?? '')) === 'cod') {
					Billing::insert([
						'user_id'         => $order->user_id,
						'billing_type'    => "Order",
						'billing_type_id' => $order->id,
						'transaction_type' => 'credit',
						'amount'          => $codCharge,
						'note'            => "COD charges refunded on RTO for AWB: {$order->awb_number}",
						'created_at'      => now(),
						'updated_at'      => now()
					]);
					$user->increment('wallet_amount', $codCharge);
				}

				// Mark charge applied
				$order->rto_charge_applied = true;
				$order->rto_charge = $rtoCharge;
				$order->save();

				Helper::orderActivity($order->id, "RTO charges applied: ₹{$rtoCharge}");

				$this->info("Applied RTO charge of ₹{$rtoCharge} for Order #{$order->id}");
			});
			} catch (\Throwable $e) {
				$this->error("Order #{$candidate->id}: " . $e->getMessage());
			}
		}
	}
}
