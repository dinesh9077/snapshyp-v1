<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\CodVoucher;
use DB;

class GenerateCODRemittance extends Command
{
    protected $signature = 'cod:generate-remittance';
    protected $description = 'Generate COD Remittance grouped by user for eligible orders on fixed monthly dates';

    public function handle()
    {
        $deliveryDays = config('setting.delivery_day');
        $allowedDays = config('setting.payout_days') ? explode(',', config('setting.payout_days')) : [];
        if (!$allowedDays) {
            $this->info("Payout days not defined in setting");
            return 0;
        }

        $today = now();
        $day = $today->format('j'); // Day without leading 0
        $thresholdDate = $today->copy()->subDays($deliveryDays)->format('Y-m-d');

        if (!in_array($day, $allowedDays)) {
            $this->info("Today is not a remittance day. Skipping...");
            return 0;
        }

        $thresholdDate = $today->copy()->subDays($deliveryDays)->format('Y-m-d');

        $eligibleOrders = Order::where('is_remmitance', '0')
            ->where('order_type', 'cod')
            ->where('status_courier', 'delivered')
            ->whereDate('delivery_date', '<=', $thresholdDate)
            ->get()
            ->groupBy('user_id');

        foreach ($eligibleOrders as $userId => $orders) {
            $remittanceId = 'REM-' . strtoupper(uniqid());

            $totalAmount = $orders->sum('cod_amount');

            DB::transaction(function () use ($userId, $orders, $remittanceId, $totalAmount, $today) {
                $codChargePercentage = config('setting.cod_charge', 0);
                $gstPercentage = config('setting.gst_rate', 18);
                $codCharges = ($totalAmount * $codChargePercentage) / 100;
                $gstAmount = ($codCharges * $gstPercentage) / 100;
                $totalCharges = $codCharges + $gstAmount;
                $netAmount = $totalAmount - $totalCharges;

                $voucher = CodVoucher::create([
                    'user_id' => $userId,
                    'voucher_no' => $remittanceId,
                    'amount' => $totalAmount,
                    'cod_charges' => $codCharges,
                    'gst_amount' => $gstAmount,
                    'total_charges' => $totalCharges,
                    'net_amount' => $netAmount,
                    'voucher_date' => $today->toDateString(),
                    'voucher_status' => $netAmount <= 0.01 ? 1 : 0,
                ]);

                Order::whereIn('id', $orders->pluck('id'))->update([
                    'cod_voucher_id' => $voucher->id,
                    'is_remmitance' => '1',
                ]);
            });

            $this->info("COD Remittance created for user_id {$userId} with ID {$remittanceId}");
        }

        return 0;
    }
}
