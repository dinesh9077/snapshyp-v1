<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SellerRemittance;
use App\Models\Order;
use App\Models\User;
use App\Models\CodWalletExpense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helper;

class GenerateRemittancePayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remittance:generate-payout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate COD remittance payouts based on user-specific or global cycle days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting COD Remittance Payout Generation...');

        try {
            // Get all active users
            $users = User::where('status', 1)->where('role', 'user')->get();

            if ($users->isEmpty()) {
                $this->warn('No active users found.');
                return 0;
            }

            $this->info("Found {$users->count()} active users to process.");

            $generatedCount = 0;

            foreach ($users as $user) {
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("Processing User ID: {$user->id} - {$user->name}");

                // Get user-specific payout days (array of weekday names)
                $userPayoutDays = $user->getPayoutDays();
                $userPayoutDays = array_map('trim', $userPayoutDays);

                // Validate we have 1 or 2 payout days
                $payoutDayCount = count($userPayoutDays);
                if ($payoutDayCount < 1 || $payoutDayCount > 2) {
                    $this->warn("  User must have 1 or 2 payout days configured. Skipping.");
                    continue;
                }

                $this->info("  Payout Days: " . implode(' & ', $userPayoutDays));

                // Get the last cycle end date for this specific user
                $lastRemittance = SellerRemittance::where('user_id', $user->id)
                    ->orderBy('cycle_to_date', 'desc')
                    ->first();

                if ($lastRemittance) {
                    // Calculate next cycle starting from the day after last cycle ended
                    $lastCycleEndDate = Carbon::parse($lastRemittance->cycle_to_date);
                    $nextDayAfterCycle = $lastCycleEndDate->copy()->addDay();
                    $cycleData = Helper::calculateCycleFromDate($nextDayAfterCycle, $userPayoutDays);

                    $this->info("  Last cycle ended: {$lastRemittance->cycle_to_date}");
                    $this->info("  Starting next cycle from: {$nextDayAfterCycle->format('Y-m-d')}");
                } else {
                    // First cycle - calculate from the earliest delivered order date
                    $firstOrderDate = Order::where('user_id', $user->id)
                        ->where('order_type', 'cod')
                        ->whereIn('status_courier', ['delivered', 'Delivered'])
                        ->where('is_remmitance', '0')
                        ->min('delivery_date');

                    $startDate = $firstOrderDate ? Carbon::parse($firstOrderDate) : Carbon::today();
                    $cycleData = Helper::calculateCycleFromDate($startDate, $userPayoutDays);

                    $this->info("  First cycle for user - Earliest order date: " . ($firstOrderDate ?? 'None (using Today)'));
                }

                $cycleStartDate = $cycleData['cycle_start'];
                $cycleEndDate = $cycleData['cycle_end'];
                $paymentScheduledDate = $cycleData['payout_date'];

                $this->info("  Cycle: {$cycleStartDate->format('Y-m-d')} ({$cycleStartDate->format('l')}) to {$cycleEndDate->format('Y-m-d')} ({$cycleEndDate->format('l')})");
                $this->info("  Payout scheduled: {$paymentScheduledDate->format('Y-m-d')} ({$paymentScheduledDate->format('l')})");

                // Check if the cycle end date has passed (cycle is complete)
                if ($cycleEndDate->isFuture()) {
                    $this->warn("  Cycle not yet complete. Ends on: {$cycleEndDate->format('Y-m-d')}");
                    continue;
                }

                // Check if remittance already exists for this cycle
                if (SellerRemittance::where('user_id', $user->id)
                    ->where('cycle_from_date', $cycleStartDate)
                    ->where('cycle_to_date', $cycleEndDate)
                    ->exists()
                ) {
                    $this->warn("  Remittance already exists for this cycle. Skipping.");
                    continue;
                }

                // Get orders for this user in this cycle
                $orders = Order::where('user_id', $user->id)
                    ->whereBetween('delivery_date', [$cycleStartDate, $cycleEndDate])
                    ->whereIn('status_courier', ['delivered', 'Delivered'])
                    ->whereNotNull('awb_number')
                    ->where('is_remmitance', '0')
                    ->get(['id', 'invoice_amount', 'order_type']);

                // Generate unique remittance ID
                $remittanceId = 'REM' . $cycleStartDate->format('Ymd') . str_pad($user->id, 6, '0', STR_PAD_LEFT);

                if ($orders->isEmpty()) {
                    $remittance = SellerRemittance::create([
                        'user_id' => $user->id,
                        'cycle_from_date' => $cycleStartDate,
                        'cycle_to_date' => $cycleEndDate,
                        'remittance_id' => $remittanceId,
                        'payment_scheduled_date' => $paymentScheduledDate,
                        'payment_date' => null,
                        'total_delivery_count' => 0,
                        'total_cod_remittance_amount' => 0,
                        'total_prepaid_remittance_amount' => 0,
                        'total_expenses' => 0,
                        'amount_transferred_to_seller' => 0,
                        'amount_transferred_next_cycle_or_wallet' => 0,
                        'settlement_status' => 'completed',
                        'bank_account_no' => null,
                        'utr' => null,
                        'seller_payment_mode' => null,
                    ]);
                    $this->warn("  No orders found for this cycle.");
                    continue;
                }

                // Calculate totals
                $totalDeliveryCount = $orders->count();
                $totalInvoiceAmount = $orders->where('order_type', 'cod')->sum('invoice_amount');
                $totalPrepaidInvoiceAmount = $orders->where('order_type', 'prepaid')->sum('invoice_amount');

                // Create remittance record
                DB::beginTransaction();
                try {
                    // Get user-specific remittance fee settings
                    $userRemittanceFeeType = $user->getRemittanceServiceFeeType();
                    $userRemittanceFeeValue = (float) $user->getRemittanceServiceFeeValue();

                    $this->info("  Fee Settings: Type = {$userRemittanceFeeType}, Value = {$userRemittanceFeeValue}");

                    $expenseAmount = 0;
                    foreach ($orders as $order) {
                        if ($order->invoice_amount > 0) {
                            if ($userRemittanceFeeType === 'fixed') {
                                $remittanceFee = $userRemittanceFeeValue;
                            } else {
                                $remittanceFee = ($order->invoice_amount * $userRemittanceFeeValue) / 100;
                            }

                            $order->remittance_amount = $remittanceFee;
                            $order->save();
                            $expenseAmount += $remittanceFee;
                        }
                    }

                    $unsettledExpenses = CodWalletExpense::where('user_id', $user->id)
                        ->whereBetween('processed_at', [$cycleStartDate, $cycleEndDate])
                        ->where('status', 'completed')
                        ->sum('cod_balance_used') ?? 0;

                    // Get previous cycle's pending amount
                    $previousCyclePendingAmount = SellerRemittance::where('user_id', $user->id)
                        ->where('cycle_to_date', '<', $cycleStartDate)
                        ->orderBy('cycle_to_date', 'desc')
                        ->value('amount_transferred_next_cycle_or_wallet') ?? 0;

                    $totalExpenses = $unsettledExpenses + $expenseAmount + $previousCyclePendingAmount;

                    $netAmount = $totalInvoiceAmount - $totalExpenses;
                    $amountTransferable = max($netAmount, 0);
                    $amount_transferred_next_cycle_or_wallet = max(abs($netAmount) - $amountTransferable, 0);

                    if ($netAmount >= 0) {
                        $amountTransferable = $netAmount;
                        $amount_transferred_next_cycle_or_wallet = 0;
                    } else {
                        $amountTransferable = 0;
                        $amount_transferred_next_cycle_or_wallet = abs($netAmount);
                    }

                    $remittance = SellerRemittance::create([
                        'user_id' => $user->id,
                        'cycle_from_date' => $cycleStartDate,
                        'cycle_to_date' => $cycleEndDate,
                        'remittance_id' => $remittanceId,
                        'payment_scheduled_date' => $paymentScheduledDate,
                        'payment_date' => null,
                        'total_delivery_count' => $totalDeliveryCount,
                        'total_cod_remittance_amount' => $totalInvoiceAmount,
                        'total_prepaid_remittance_amount' => $totalPrepaidInvoiceAmount,
                        'total_expenses' => $totalExpenses,
                        'previous_cycle_pending_net_amount' => $previousCyclePendingAmount,
                        'amount_transferred_to_seller' => $amountTransferable,
                        'amount_transferred_next_cycle_or_wallet' => $amount_transferred_next_cycle_or_wallet,
                        'settlement_status' => $netAmount <= 0.01 ? 'completed' : 'pending',
                        'bank_account_no' => null,
                        'utr' => null,
                        'seller_payment_mode' => null,
                    ]);

                    Order::whereIn('id', $orders->pluck('id'))->update([
                        'remittance_reference_id' => $remittance->id,
                        'remittance_date' => now()->format('Y-m-d'),
                        'is_remmitance' => '1',
                    ]);

                    DB::commit();

                    $generatedCount++;
                    $this->info("  ✓ Generated remittance: {$remittanceId} | Amount: ₹{$totalInvoiceAmount} | Orders: {$totalDeliveryCount}");

                    // Log the generation
                    Log::info("COD Remittance Generated", [
                        'user_id' => $user->id,
                        'remittance_id' => $remittanceId,
                        'cycle_from' => $cycleStartDate->format('Y-m-d'),
                        'cycle_to' => $cycleEndDate->format('Y-m-d'),
                        'payment_scheduled' => $paymentScheduledDate->format('Y-m-d'),
                        'total_amount' => $totalInvoiceAmount,
                        'delivery_count' => $totalDeliveryCount,
                        'user_payout_days' => implode(',', $userPayoutDays),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  ✗ Failed to generate remittance: " . $e->getMessage());
                    Log::error("COD Remittance Generation Failed", [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✓ Remittance generation completed!");
            $this->info("Total remittances generated: {$generatedCount}");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error generating remittances: ' . $e->getMessage());
            Log::error('COD Remittance Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
