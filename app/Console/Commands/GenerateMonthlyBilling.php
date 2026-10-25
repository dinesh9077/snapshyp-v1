<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage; 
use App\Models\Order; 
use App\Models\Invoice; 
use Carbon\Carbon;

class GenerateMonthlyBilling extends Command
{
    protected $signature = 'billing:generate';
    protected $description = 'Generate monthly billing invoice PDF';

    const CGST_RATE = 0.09;
    const SGST_RATE = 0.09;
    const IGST_RATE = 0.18;
    const TAX_DIVISOR = 1.18;

    public function handle()
    {
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $ordersGrouped = Order::where(function ($query) use ($lastMonthStart, $lastMonthEnd) {
			$query->where(function ($q) use ($lastMonthStart, $lastMonthEnd) {
				$q->where('status_courier', 'delivered')
				  ->whereBetween('delivery_date', [$lastMonthStart, $lastMonthEnd])
				  ->whereNull('invoice_id');
			})
			->orWhere(function ($q) use ($lastMonthStart, $lastMonthEnd) {
				$q->whereBetween('order_date', [$lastMonthStart, $lastMonthEnd])
				  ->where('rto_charge_applied', 1);
			});
		})
		->get()
		->groupBy('user_id');
		 
        foreach ($ordersGrouped as $userId => $orders) {
            $user = $orders->first()->user;
            if (!$user) {
                $this->warn("User not found for user_id: $userId");
                continue;
            }

            $baseTotal = $cgstTotal = $sgstTotal = $igstTotal = $grandTotal = 0;
            $state = strtolower($user->state ?? '');

            foreach ($orders as $order) {
                $shipping = $order->rto_charge_applied == 1 ? ($order->rto_charge ?? 0) : ($order->shipping_charge ?? 0);
                $base = $shipping / self::TAX_DIVISOR;
				
                if ($state === 'gujarat') {
                    $cgstTotal += $base * self::CGST_RATE;
                    $sgstTotal += $base * self::SGST_RATE;
                } else {
                    $igstTotal += $base * self::IGST_RATE;
                }
				 
                $baseTotal += $base;
                $grandTotal += $shipping;
            }

            // Create invoice
            $invoiceNumber = $this->generateInvoiceNumber();
            $now = now();
			 
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber,
                'invoice_state' => ucfirst($state),
                'invoice_date' => $now->toDateString(),
                'month_start' => $lastMonthStart->format('Y-m-d'),
                'month_end' => $lastMonthEnd->format('Y-m-d'),
                'invoice_period' => $now->format('M Y'),
                'base_amount' => round($baseTotal, 2),
                'cgst_amount' => round($cgstTotal, 2),
                'sgst_amount' => round($sgstTotal, 2),
                'igst_amount' => round($igstTotal, 2),
                'total_amount' => round($grandTotal, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Update orders with invoice_id and invoice_no
            foreach ($orders as $order) {
                $order->update([
                    'invoice_id' => $invoice->id, 
                    'is_invoice_count' => 1
                ]);
            }

            $this->info("✅ Invoice generated for user_id {$userId}: {$invoiceNumber}");
        }
    }

    private function getFinancialYear(): string
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        $startYear = ($month < 4) ? $year - 1 : $year;
        $endYear = $startYear + 1;

        return $startYear . '-' . substr($endYear, -2); // e.g., 2025-26
    }

    private function generateInvoiceNumber(): string
    {
        $financialYear = $this->getFinancialYear();  
        $prefix = $financialYear . '/';

        $counter = 1;
        do {
            $number = str_pad($counter, 4, '0', STR_PAD_LEFT); // e.g., 0001
            $invoiceNumber = $prefix . $number;
            $exists = Invoice::where('invoice_number', $invoiceNumber)->exists();
            $counter++;
        } while ($exists);

        return $invoiceNumber;
    }
}
