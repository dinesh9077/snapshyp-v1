<?php

namespace App\Support;

use App\Models\CodVoucher;
use App\Models\CodWalletExpense;
use App\Models\Order;
use App\Models\SellerRemittance;
use App\Models\User;

/**
 * A seller's COD balances, computed on the server (the wallet page shows the same numbers).
 *
 * - cod_balance: delivered COD past the plan's hold days, not yet remitted, minus transfers since the last payout
 * - transfer_balance: the part of cod_balance that may move to the wallet (cod_wallet_transfer_value %)
 * - pending_payouts: remittances and vouchers not yet paid out
 * - available: what a COD-to-wallet transfer may use in total
 */
class CodBalance
{
    public static function for(User $user): array
    {
        $codBalance = 0;
        $transferBalance = 0;
        $transferPercent = 0;
        $today = now();

        if ($user->codPlan) {
            $userPlanDays = (int) ($user->codPlan->days + 1);
            $thresholdDate = $today->copy()->subDays($userPlanDays)->format('Y-m-d');

            $codBalance = Order::where('user_id', $user->id)
                ->where('is_remmitance', '0')
                ->where('order_type', 'cod')
                ->where('status_courier', 'delivered')
                ->whereDate('delivery_date', '<=', $thresholdDate)
                ->sum('cod_amount') ?? 0;

            $lastPayoutDate = CodVoucher::where('user_id', $user->id)
                ->orderBy('voucher_date', 'desc')
                ->value('voucher_date') ?? 0;

            $unsettledExpenses = CodWalletExpense::where('user_id', $user->id)
                ->whereDate('processed_at', '>', $lastPayoutDate)
                ->whereDate('processed_at', '<=', $today)
                ->where('status', 'completed')
                ->sum('cod_balance_used');

            $codBalance = max(0, $codBalance - $unsettledExpenses);
            $transferPercent = config('setting.cod_wallet_transfer_value', 0);
            $transferBalance = $transferPercent > 0 ? ($codBalance * $transferPercent / 100) : $codBalance;
        }

        $pendingPayouts = SellerRemittance::where('user_id', $user->id)
            ->where('settlement_status', 'pending')
            ->sum('amount_transferred_to_seller');

        $pendingPayouts += CodVoucher::where('user_id', $user->id)
            ->where('voucher_status', 0)
            ->sum('net_amount');

        $pendingPayouts = max(0, $pendingPayouts);

        return [
            'cod_balance' => $codBalance,
            'transfer_balance' => $transferBalance,
            'transfer_percent' => $transferPercent,
            'pending_payouts' => $pendingPayouts,
            'available' => max(0, $transferBalance + $pendingPayouts),
        ];
    }
}
