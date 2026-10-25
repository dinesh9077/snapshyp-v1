<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\ExcessWeight;
use App\Models\User;
use App\Models\Billing;
use App\Models\weightDescrepencyHistory;

class AutoAcceptWeightDiscrepancies extends Command
{
    protected $signature = 'weight:accept-discrepancies';
    protected $description = 'Automatically accept weight discrepancies after 7 days';

    public function handle()
    {
        $this->info('Starting the weight discrepancy auto-accept process...');

        try {
            DB::beginTransaction();

            // Fetch orders that are not accepted and have excess weight older than 7 days
            $orders = Order::whereNotIn('weight_status', ['Accepted', 'Auto Accepted'])
                ->whereHas('excessWeight', function ($query) {
                    $query->whereDate('created_at', '<=', Carbon::now()->subDays(7)); // Orders older than 7 days
                })
                ->get();

            if ($orders->isEmpty()) {
                $this->info('No orders found with weight discrepancies older than 7 days.');
                DB::rollBack();
                return;
            }

            foreach ($orders as $order) {
                $excess_weight = ExcessWeight::where('order_id', $order->id)->first();
                if (!$excess_weight) continue;

                $excess_charge = $excess_weight->excess_charge;
                $user = User::find($order->user_id);

                if (!$user || $user->wallet_amount < $excess_charge) {
                    $this->warn("Skipping Order ID: {$order->id} due to insufficient wallet balance.");
                    continue;
                }

                // Deduct from User Wallet
                $user->decrement('wallet_amount', $excess_charge);

                // Insert Billing Record
                Billing::insert([
                    'user_id'         => $order->user_id,
                    'billing_type'    => "Order",
                    'billing_type_id' => $order->id,
                    'transaction_type'=> 'debit',
                    'amount'          => $excess_charge,
                    'note'            => 'Excess weight applied for AWB: ' . $order->awb_number,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // Insert into Weight Discrepancy History
                weightDescrepencyHistory::create([
                    'order_id'            => $order->id,
                    'action_by'           => 'System',
                    'remarks'             => 'Auto-accepted due to 7 days rule',
                    'status_descrepency'  => 'Auto Accepted',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // Update Order Status
                $order->update([
                    'weight_status'       => 'Auto Accepted',
                    'weight_update_date'  => now(),
                ]);

                $this->info("Weight discrepancy accepted for Order ID: {$order->id}");
            }

            DB::commit();
            $this->info('Weight discrepancy auto-accept process completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
