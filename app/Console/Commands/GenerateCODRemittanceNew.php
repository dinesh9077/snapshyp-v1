<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Models\CodVoucher;
use App\Models\CodWalletExpense;
use DB;

class GenerateCODRemittanceNew extends Command
{
	protected $signature = 'cod:generate-remittance-new';
    protected $description = 'Generate COD Remittance grouped by user for eligible orders based on T+N delivery dates';

    public function handle()
    {
        $today = now();
        // Get users with active COD plans
        $usersWithCodPlans = User::where('cod_plan_active', 1)
            ->whereNotNull('cod_plan_id')
            ->with('codPlan')
            ->get();

        if ($usersWithCodPlans->isEmpty()) {
            $this->info("No users with active COD plans found. Skipping...");
            return 0;
        }

        $holidays = \App\Models\CodRemittanceHoliday::pluck('date')->toArray();
        $totalRemittancesCreated = 0;

        foreach ($usersWithCodPlans as $user) {
            if (!$user->codPlan) {
                $this->warn("User {$user->id} has active COD plan but plan not found. Skipping...");
                continue;
            }
            $codPlanId = $user->codPlan->id;

            // Calculate threshold date based on user's COD plans
            $userPlanDays = (int)($user->codPlan->days); // T+1, T+2, T+3, T+4

            $date = $today->copy();
            $daysSubtracted = 0;

            while ($daysSubtracted < $userPlanDays) {
                $date->subDay();
                // Check if date is a holiday
                if (!in_array($date->format('Y-m-d'), $holidays)) {
                    $daysSubtracted++;
                }
            }

            $thresholdDate = $date->format('Y-m-d');

            // Get eligible orders for this specific user based on their plan
            $eligibleOrders = Order::where('user_id', $user->id)
                ->where('is_remmitance', '0')
                ->where('order_type', 'cod')
                ->where('status_courier', 'delivered')
                ->whereDate('delivery_date', '<=', $thresholdDate)
                ->get();

            if ($eligibleOrders->isEmpty()) {
                continue; // No eligible orders for this user
            }

            $remittanceId = 'REM-' . strtoupper(uniqid());
            $totalAmount = $eligibleOrders->sum('cod_amount');

            $lastPayoutDate = CodVoucher::where('user_id', $user->id)
                ->orderBy('voucher_date', 'desc')
                ->value('voucher_date') ?? 0;

            $unsettledExpenses = CodWalletExpense::where('user_id', $user->id)
                ->whereDate('processed_at', '>', $lastPayoutDate)
                ->whereDate('processed_at', '<=', $today)
                ->where('status', 'completed')
                ->sum('cod_balance_used');

            // Calculate COD charges based on user's plan
            $codChargePercentage = $user->codPlan->percentage;
            $gstPercentage = $user->codPlan->gst_percentage;

            $codCharges = ($totalAmount * $codChargePercentage) / 100;
            $gstAmount = ($codCharges * $gstPercentage) / 100;
            $totalCharges = $codCharges + $gstAmount;
            $netAmount = $totalAmount - $totalCharges - $unsettledExpenses;

            DB::transaction(function () use ($user, $eligibleOrders, $remittanceId, $totalAmount, $codCharges, $gstAmount, $totalCharges, $netAmount, $today, $unsettledExpenses ) {
                $voucher = CodVoucher::create([
                    'user_id' => $user->id,
                    'voucher_no' => $remittanceId,
                    'amount' => $totalAmount,
                    'cod_charges' => $codCharges,
                    'gst_amount' => $gstAmount,
                    'total_charges' => $totalCharges,
                    'net_amount' => $netAmount,
                    'voucher_date' => $today->toDateString(),
                    'voucher_status' => $netAmount <= 0.01 ? 1 : 0,
                    'cod_plan_id' => $user->cod_plan_id,
                    'plan_percentage' => $user->codPlan->percentage,
                    'cod_balance_expense' => $unsettledExpenses,
                ]);

                Order::whereIn('id', $eligibleOrders->pluck('id'))->update([
                    'cod_voucher_id' => $voucher->id,
                    'is_remmitance' => '1',
                ]);
            });

            $this->info("COD Remittance created for user '{$user->name}' (ID: {$user->id})");
            $this->info("  - Plan: {$user->codPlan->plan_name} ({$user->codPlan->percentage}%)");
            $this->info("  - Voucher: {$remittanceId}");
            $this->info("  - Orders: {$eligibleOrders->count()}");
            $this->info("  - Total Amount: ₹{$totalAmount}");
            $this->info("  - COD Charges: ₹{$codCharges}");
            $this->info("  - GST: ₹{$gstAmount}");
            $this->info("  - Net Amount: ₹{$netAmount}");
            $this->info("  ---");

            $totalRemittancesCreated++;
        }

        $this->info("Total remittances created: {$totalRemittancesCreated}");

        return 0;
    }
}
