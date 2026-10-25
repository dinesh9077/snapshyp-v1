<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Support\OrderType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingInvoiceService
{
    private const CGST_RATE = 0.09;
    private const SGST_RATE = 0.09;
    private const IGST_RATE = 0.18;
    private const TAX_DIVISOR = 1.18;

    public function generateForMonth(int $year, int $month, ?int $userId = null): array
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return $this->generateForPeriod($periodStart, $periodEnd, $userId);
    }

    public function generateForPreviousMonth(?Carbon $referenceDate = null, ?int $userId = null): array
    {
        $referenceDate = $referenceDate ? $referenceDate->copy() : now();
        $periodStart = $referenceDate->copy()->subMonth()->startOfMonth();
        $periodEnd = $referenceDate->copy()->subMonth()->endOfMonth();

        return $this->generateForPeriod($periodStart, $periodEnd, $userId);
    }

    public function generateForCurrentMonth(?Carbon $referenceDate = null, ?int $userId = null): array
    {
        $referenceDate = $referenceDate ? $referenceDate->copy() : now();
        $periodStart = $referenceDate->copy()->startOfMonth();
        $periodEnd = $referenceDate->copy()->endOfMonth();

        return $this->generateForPeriod($periodStart, $periodEnd, $userId, $referenceDate);
    }

    public function generateForPeriod(Carbon $periodStart, Carbon $periodEnd, ?int $userId = null, ?Carbon $invoiceDate = null): array
    {
        $invoiceDate = $invoiceDate ? $invoiceDate->copy() : now();

        $candidateOrders = Order::with(['user', 'user.userKyc', 'shippingCompany'])
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereNull('invoice_id')
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->whereRaw('LOWER(COALESCE(status_courier, "")) = ?', ['delivered'])
                        ->whereBetween('delivery_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                })->orWhere(function ($q) use ($periodStart, $periodEnd) {
                    $q->where('rto_charge_applied', 1)
                        ->whereBetween('order_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                });
            })
            ->get()
            ->groupBy(function ($order) {
                return $order->user_id . ':' . ($order->shipping_company_id ?? 'null');
            });

        $generatedCount = 0;
        $updatedCount = 0;
        $assignedOrders = 0;
        $invoices = collect();

        foreach ($candidateOrders as $groupKey => $orders) {
            $user = $orders->first()->user;
            if (!$user) {
                continue;
            }
            $companyId = $orders->first()->shipping_company_id;

            $existingInvoice = Invoice::with('orders')
                ->where('user_id', $user->id)
                ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('shipping_company_id', $companyId);
                }, function ($query) {
                    $query->whereNull('shipping_company_id');
                })
                ->whereDate('month_start', $periodStart->toDateString())
                ->whereDate('month_end', $periodEnd->toDateString())
                ->first();

            $invoice = $existingInvoice ?: new Invoice();
            $allOrders = $existingInvoice
                ? $existingInvoice->orders->keyBy('id')->merge($orders->keyBy('id'))->values()
                : $orders->values();

            $totals = $this->calculateTotals($allOrders, strtolower(trim((string) ($user->state ?? ''))));

            $invoice->fill([
                'user_id' => $user->id,
                'shipping_company_id' => $companyId,
                'invoice_state' => $this->normalizeState($user->state),
                'invoice_date' => $invoiceDate->toDateString(),
                'month_start' => $periodStart->toDateString(),
                'month_end' => $periodEnd->toDateString(),
                'invoice_period' => $periodStart->format('M Y'),
                'base_amount' => round($totals['base_amount'], 2),
                'cgst_amount' => round($totals['cgst_amount'], 2),
                'sgst_amount' => round($totals['sgst_amount'], 2),
                'igst_amount' => round($totals['igst_amount'], 2),
                'total_amount' => round($totals['total_amount'], 2),
            ]);

            if (!$existingInvoice) {
                $invoice->invoice_number = $this->generateInvoiceNumber($invoiceDate);
            }

            $invoice->save();

            Order::whereIn('id', $orders->pluck('id')->all())->update([
                'invoice_id' => $invoice->id,
                'is_invoice_count' => 1,
            ]);

            $assignedOrders += $orders->count();
            $invoices->push($invoice);

            if ($existingInvoice) {
                $updatedCount++;
            } else {
                $generatedCount++;
            }
        }

        return [
            'generated_count' => $generatedCount,
            'updated_count' => $updatedCount,
            'assigned_orders' => $assignedOrders,
            'invoices' => $invoices,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ];
    }

    private function calculateTotals(Collection $orders, string $state): array
    {
        $forwardTotal = 0;
        $codTotal = 0;
        $rtoTotal = 0;
        $baseTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;
        $igstTotal = 0;
        $grandTotal = 0;

        foreach ($orders as $order) {
            $row = $this->orderChargeBreakdown($order, $state);

            $forwardTotal += $row['forward_amount'];
            $codTotal += $row['cod_amount'];
            $rtoTotal += $row['rto_amount'];
            $baseTotal += $row['base_amount'];
            $cgstTotal += $row['cgst_amount'];
            $sgstTotal += $row['sgst_amount'];
            $igstTotal += $row['igst_amount'];
            $grandTotal += $row['total_amount'];
        }

        return [
            'forward_amount' => round($forwardTotal, 2),
            'cod_amount' => round($codTotal, 2),
            'rto_amount' => round($rtoTotal, 2),
            'base_amount' => $baseTotal,
            'cgst_amount' => $cgstTotal,
            'sgst_amount' => $sgstTotal,
            'igst_amount' => $igstTotal,
            'total_amount' => $grandTotal,
        ];
    }

    public function summarizeInvoice(Invoice $invoice, bool $persist = false): array
    {
        $invoice->loadMissing(['orders.user', 'orders.shippingCompany', 'user', 'user.userKyc', 'shippingCompany']);

        $state = strtolower(trim((string) ($invoice->invoice_state ?: optional($invoice->user)->state ?: '')));
        $totals = $this->calculateTotals($invoice->orders, $state);
        $normalizedState = $this->normalizeState($invoice->invoice_state ?: optional($invoice->user)->state);

        if ($persist) {
            $invoice->fill([
                'invoice_state' => $normalizedState,
                'base_amount' => round($totals['base_amount'], 2),
                'cgst_amount' => round($totals['cgst_amount'], 2),
                'sgst_amount' => round($totals['sgst_amount'], 2),
                'igst_amount' => round($totals['igst_amount'], 2),
                'total_amount' => round($totals['total_amount'], 2),
            ]);

            if ($invoice->isDirty()) {
                $invoice->save();
            }
        }

        return [
            'invoice_state' => $normalizedState,
            'order_count' => $invoice->orders->count(),
            'forward_amount' => round($totals['forward_amount'], 2),
            'cod_amount' => round($totals['cod_amount'], 2),
            'rto_amount' => round($totals['rto_amount'], 2),
            'base_amount' => round($totals['base_amount'], 2),
            'cgst_amount' => round($totals['cgst_amount'], 2),
            'sgst_amount' => round($totals['sgst_amount'], 2),
            'igst_amount' => round($totals['igst_amount'], 2),
            'total_amount' => round($totals['total_amount'], 2),
        ];
    }

    public function invoiceOrderRows(Invoice $invoice): Collection
    {
        $invoice->loadMissing([
            'orders.user',
            'orders.shippingCompany',
            'orders.customer',
            'orders.customerAddress',
            'orders.warehouse',
            'user',
            'shippingCompany',
        ]);

        $state = strtolower(trim((string) ($invoice->invoice_state ?: optional($invoice->user)->state ?: '')));

        return $invoice->orders->values()->map(function (Order $order, $index) use ($invoice, $state) {
            $row = $this->orderChargeBreakdown($order, $state);
            $customer = $order->customer;
            $customerAddress = $order->customerAddress;
            $firstName = isset($customer->first_name) ? $customer->first_name : '';
            $lastName = isset($customer->last_name) ? $customer->last_name : '';
            $customerName = trim((string) ($firstName . ' ' . $lastName));
            $addressParts = collect([
                isset($customerAddress->address) ? $customerAddress->address : '',
                isset($customerAddress->city) ? $customerAddress->city : '',
                isset($customerAddress->state) ? $customerAddress->state : '',
                isset($customerAddress->country) ? $customerAddress->country : '',
                isset($customerAddress->zip_code) ? $customerAddress->zip_code : '',
            ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');

            $orderDate = $order->order_date ? Carbon::parse($order->order_date)->format('d M Y') : '';
            $deliveryDate = $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d M Y') : '';

            $warehouse = $order->warehouse;
            $warehouseAddressParts = collect([
                isset($warehouse->warehouse_name) ? $warehouse->warehouse_name : '',
                isset($warehouse->contact_name) ? $warehouse->contact_name : '',
                !empty($warehouse->address) ? "Address: {$warehouse->address}" : '',
                isset($warehouse->city) ? $warehouse->city : '',
                isset($warehouse->state) ? $warehouse->state : '',
                isset($warehouse->zip_code) ? $warehouse->zip_code : '',
                isset($warehouse->contact_number) ? $warehouse->contact_number : '',
            ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');

            $orderItems = $order->orderItems;
            $productDetails = $orderItems->map(function ($item) {
                $description = trim((string) (isset($item->product_description) ? $item->product_description : ''));
                $details = [];
                if ($description !== '') {
                    $details[] = $description;
                }
                $details[] = "Amount: {$item->amount}";
                $details[] = "No Of Box: {$item->quantity}";
                $details[] = "Sku: " . (isset($item->sku_number) ? $item->sku_number : '');
                return implode(' ', array_filter($details));
            })->implode(' | ');

            $noOfBox = $orderItems->sum('quantity');
            $totalWeightInKg = $orderItems->sum('dimensions.weight') ?: 0;
            $orderDetails = collect([
                ($order->order_prefix ?: '') . $order->id,
                $order->awb_number ? "Awb Number: {$order->awb_number}" : null,
                $order->courier_name ? "Courier: {$order->courier_name}" : null,
                $productDetails ? "Product Details: {$productDetails}" : null,
            ])->filter()->implode(' | ');

            $customerDetails = collect([
                $customerName,
                !empty($customer->email) ? $customer->email : '',
                !empty($customer->mobile) ? $customer->mobile : '',
            ])->filter()->implode(' | ');

            $packageDetailsParts = collect([
                "No Of Box: {$noOfBox}",
                "Weight In Kg: {$totalWeightInKg}",
                $productDetails ? strip_tags($productDetails) : null,
            ])->filter()->implode(' | ');

            $paymentParts = collect([
                $order->order_type,
                OrderType::isCod($order->order_type) ? "Cod Amount : {$order->cod_amount}" : "Invoice Amount : {$order->invoice_amount}",
            ])->filter()->implode(" | ");

            return [
                'Sr.No'              => $index + 1,
                'Order Details'      => $orderDetails,
                'Customer Details'   => $customerDetails,
                'Customer Address'   => $addressParts,
                'Seller Details'     => optional($order->user)->name ?: '',
                'City'               => isset($customerAddress->city) ? $customerAddress->city : '',
                'State'              => isset($customerAddress->state) ? $customerAddress->state : '',
                'Country'            => isset($customerAddress->country) ? $customerAddress->country : '',
                'Pincode'            => isset($customerAddress->zip_code) ? $customerAddress->zip_code : '',
                'Package Details'    => $packageDetailsParts,
                'Payment'            => $paymentParts,
                'Pickup Address'     => $warehouseAddressParts,
                'Status'             => !empty($order->status_courier) ? $order->status_courier : '',
                'Created At'         => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                'Forward Charge'     => round($row['forward_amount'], 2),
                'COD Charge'         => round($row['cod_amount'], 2),
                'RTO Charge'         => round($row['rto_amount'], 2),
                'Taxable Amount'     => round($row['base_amount'], 2),
                'CGST'               => round($row['cgst_amount'], 2),
                'SGST'               => round($row['sgst_amount'], 2),
                'IGST'               => round($row['igst_amount'], 2),
                'Invoice Amount'     => round($row['total_amount'], 2),
            ];
        })->values();
    }

    public function orderChargeBreakdown(Order $order, string $state): array
    {
        $forwardAmount = 0.0;
        $codAmount = 0.0;
        $rtoAmount = 0.0;

        if ((int) ($order->rto_charge_applied ?? 0) === 1 && (float) ($order->rto_charge ?? 0) > 0) {
            $rtoAmount = (float) ($order->rto_charge ?? 0);
        } else {
            $forwardAmount = (float) ($order->shipping_charge ?? 0);
            $codAmount = (float) ($order->cod_charges ?? 0);
        }

        $totalAmount = $forwardAmount + $codAmount + $rtoAmount;
        $baseAmount = $totalAmount / self::TAX_DIVISOR;
        $cgstAmount = 0.0;
        $sgstAmount = 0.0;
        $igstAmount = 0.0;

        if ($state === 'gujarat') {
            $cgstAmount = $baseAmount * self::CGST_RATE;
            $sgstAmount = $baseAmount * self::SGST_RATE;
        } else {
            $igstAmount = $baseAmount * self::IGST_RATE;
        }

        return [
            'forward_amount' => $forwardAmount,
            'cod_amount' => $codAmount,
            'rto_amount' => $rtoAmount,
            'base_amount' => $baseAmount,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => $igstAmount,
            'total_amount' => $totalAmount,
        ];
    }

    private function normalizeState(?string $state): string
    {
        $state = trim((string) $state);

        return $state !== '' ? ucfirst(strtolower($state)) : 'Unknown';
    }

    private function generateInvoiceNumber(?Carbon $invoiceDate = null): string
    {
        $invoiceDate = $invoiceDate ? $invoiceDate->copy() : now();
        $financialYear = $this->getFinancialYear($invoiceDate);
        $prefix = $financialYear . '/';
        $counter = 1;

        do {
            $number = str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $number;
            $exists = Invoice::where('invoice_number', $invoiceNumber)->exists();
            $counter++;
        } while ($exists);

        return $invoiceNumber;
    }

    private function getFinancialYear(Carbon $date): string
    {
        $year = $date->year;
        $month = $date->month;

        $startYear = $month < 4 ? $year - 1 : $year;
        $endYear = $startYear + 1;

        return $startYear . '-' . substr((string) $endYear, -2);
    }
}
