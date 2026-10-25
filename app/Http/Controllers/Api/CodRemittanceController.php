<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\SellerRemittance;
use App\Models\CodVoucher;
use App\Models\CodWalletExpense;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;
use App\Traits\ApiResponse;

class CodRemittanceController extends Controller
{
    use ApiResponse;

    /**
     * Display list of all users with remittance stats (Admin Only)
     */
    public function usersList(Request $request)
    {
        $authUser = Auth::user();

        // Base query for vouchers
        $query = CodVoucher::with(['user', 'codVoucherOrders']);

        // Stats calculation
        $payoutQuery = CodVoucher::where('voucher_status', 1);
        $pendingQuery = CodVoucher::where('voucher_status', 0);

        if ($authUser->role === 'user') {
            $query->where('user_id', $authUser->id);
            $payoutQuery->where('user_id', $authUser->id);
            $pendingQuery->where('user_id', $authUser->id);
        }

        $totalPayoutAmount = $payoutQuery->sum('net_amount');
        $totalPendingPayoutAmount = $pendingQuery->sum('net_amount');

        $start = $request->input("start") ?? 0;
        $length = $request->input("length") ?? 10;
        $search = $request->input('search');
        $searchValue = is_array($search) ? ($search['value'] ?? '') : ($request->input('search.value') ?? $search ?? '');

        // Get filter parameters
        $userId = $request->input('user_id');
        $voucherStatus = $request->input('voucher_status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $dateType = $request->input('date_type', 'voucher_date');

        if ($authUser->role === 'admin' && !empty($userId)) {
            $query->where('user_id', $userId);
        }

        if ($voucherStatus !== null && $voucherStatus !== '') {
            $query->where('voucher_status', $voucherStatus);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween($dateType, [$fromDate, $toDate]);
        } elseif (!empty($fromDate)) {
            $query->whereDate($dateType, $fromDate);
        } elseif (!empty($toDate)) {
            $query->whereDate($dateType, $toDate);
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('voucher_no', 'LIKE', "%{$searchValue}%")
                    ->orWhereHas('user', function ($uq) use ($searchValue) {
                        $uq->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('email', 'LIKE', "%{$searchValue}%")
                            ->orWhere('company_name', 'LIKE', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = $query->count();

        $vouchers = $query->orderBy('created_at', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = [];
        foreach ($vouchers as $voucher) {
            $data[] = [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'voucher_date' => $voucher->voucher_date,
                'amount' => (float)$voucher->amount,
                'cod_charges' => (float)$voucher->cod_charges,
                'gst_amount' => (float)$voucher->gst_amount,
                'net_amount' => (float)$voucher->net_amount,
                'voucher_status' => (int)$voucher->voucher_status,
                'status_label' => $voucher->voucher_status == 1 ? 'Paid' : 'Unpaid',
                'payout_date' => $voucher->payout_date,
                'reference_no' => $voucher->reference_no,
                'remarks' => $voucher->remarks,
                'user' => [
                    'id' => $voucher->user->id ?? null,
                    'name' => $voucher->user->name ?? '-',
                    'company_name' => $voucher->user->company_name ?? '-',
                ]
            ];
        }

        return $this->successResponse([
            'stats' => [
                'total_payout_amount' => (float)$totalPayoutAmount,
                'total_pending_payout_amount' => (float)$totalPendingPayoutAmount
            ],
            'users' => [
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ]
        ], 'Payout list fetched successfully');
    }

    /**
     * Display remittance statistics and list of remittances for the authenticated user
     */
    public function list(Request $request)
    {
        $user = Auth::user();

        // 1. Calculate Remittance Stats
        $stats = $this->calculateRemittanceStats($user->id);

        // 2. Get Remittance List (DataTable logic)
        $start = $request->input("start") ?? 0;
        $length = $request->input("length") ?? 10;
        $search = $request->input('search');
        $searchValue = is_array($search) ? ($search['value'] ?? '') : ($request->input('search.value') ?? $search ?? '');

        // Base query - Get remittances for user
        $query = SellerRemittance::where('user_id', $user->id);

        // Apply search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('remittance_id', 'LIKE', "%{$searchValue}%")
                    ->orWhere('settlement_status', 'LIKE', "%{$searchValue}%")
                    ->orWhere('bank_account_no', 'LIKE', "%{$searchValue}%")
                    ->orWhere('utr', 'LIKE', "%{$searchValue}%")
                    ->orWhere('seller_payment_mode', 'LIKE', "%{$searchValue}%");
            });
        }

        $totalRecords = $query->count();

        // Get paginated data
        $remittances = $query->orderBy('created_at', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        $remittanceList = [];
        
        foreach ($remittances as $remittance) {
            $remittanceList[] = [
                'id' => $remittance->id,
                'cycle_date' => Carbon::parse($remittance->cycle_from_date)->format('d M Y') . ' - ' . Carbon::parse($remittance->cycle_to_date)->format('d M Y'),
                'remittance_id' => $remittance->remittance_id,
                'payment_scheduled_date' => $remittance->payment_scheduled_date ? Carbon::parse($remittance->payment_scheduled_date)->format('d M Y') : '-',
                'payment_date' => $remittance->payment_date ? Carbon::parse($remittance->payment_date)->format('d M Y') : '-',
                'delivery_count' => $remittance->total_delivery_count ?? 0,
                'cod_amount' => $remittance->total_cod_remittance_amount,
                'expenses' => $remittance->total_expenses,
                'transferable_amount' => $remittance->amount_transferred_to_seller,
                'next_cycle_of_wallet' => $remittance->amount_transferred_next_cycle_or_wallet,
                'settlement_status' => $remittance->settlement_status,
                'bank_account_no' => $remittance->bank_account_no ?? '-',
                'utr' => $remittance->utr ?? '-',
                'seller_payment_mode' => $remittance->seller_payment_mode ?? '-',
            ];
        }

        return $this->successResponse([
            'stats' => $stats,
            'remittances' => [
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $remittanceList
            ]
        ], 'Remittance list fetched successfully');
    }

    /**
     * Display remittance detail
     */
    public function show($id)
    {
        $remittance = SellerRemittance::with('user')->findOrFail($id);
        $user = Auth::user();

        // Check authorization
        if ($user->role === 'user' && $user->id != $remittance->user_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $walletStats = CodWalletExpense::where('user_id', $remittance->user_id)
            ->whereBetween('processed_at', [$remittance->cycle_from_date, $remittance->cycle_to_date])
            ->where('status', 'completed')
            ->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(cod_balance_used), 0) as total_amount
        ')
            ->first();

        $walletCount  = $walletStats->total_count;
        $codWalletExpenses = $walletStats->total_amount;

        return $this->successResponse([
            'remittance' => $remittance,
            'codWalletExpenses' => $codWalletExpenses,
            'walletCount' => $walletCount
        ], 'Remittance details fetched successfully');
    }

    /**
     * Get orders/expenses for a remittance
     */
    public function remittanceOrders(Request $request, $id)
    {
        $remittance = SellerRemittance::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'user' && $user->id != $remittance->user_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $start = $request->input("start") ?? 0;
        $length = $request->input("length") ?? 10;
        $search = $request->input('search');
        $searchValue = is_array($search) ? ($search['value'] ?? '') : ($request->input('search.value') ?? $search ?? '');
        $tab = $request->input('tab', 'cod'); // cod, shipping (wallet balance), shopdesk (service fee)

        $data = [];
        $totalRecords = 0;

        if ($tab === 'cod') {
            // COD Revenue Tab - Show delivered COD orders
            $query = Order::where('remittance_reference_id', $remittance->id)
                ->where('order_type', 'cod')
                ->whereIn('status_courier', ['delivered', 'Delivered']);

            // Apply search
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('awb_number', 'LIKE', "%{$searchValue}%")
                        ->orWhere('id', 'LIKE', "%{$searchValue}%")
                        ->orWhere('order_prefix', 'LIKE', "%{$searchValue}%");
                });
            }

            $totalRecords = $query->count();

            $orders = $query->orderBy('created_at', 'desc')
                ->offset($start)
                ->limit($length)
                ->get();

            foreach ($orders as $order) {
                $settlementDate = $order->delivery_date ? Carbon::parse($order->delivery_date)->format('Y-m-d') : '-';
                $orderCreatedDate = Carbon::parse($order->created_at)->format('Y-m-d');
                $amount = $order->invoice_amount ?? 0;

                $data[] = [
                    'settlement_date' => $settlementDate,
                    'order_id' => $order->order_prefix ?? $order->id,
                    'order_created_date' => $orderCreatedDate,
                    'awb_number' => $order->awb_number ?? '-',
                    'transaction_type' => 'Revenue',
                    'transaction_details' => 'COD Amount',
                    'amount' => $amount,
                    'status' => strtoupper($order->status_courier),
                ];
            }
        } elseif ($tab === 'shipping') {
            // Wallet Balance Usage Tab - Show COD wallet expenses
            $query = CodWalletExpense::where('user_id', $remittance->user_id)
                ->whereBetween('processed_at', [$remittance->cycle_from_date, $remittance->cycle_to_date])
                ->where('status', 'completed');

            // Apply search
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('reference_id', 'LIKE', "%{$searchValue}%")
                        ->orWhere('remarks', 'LIKE', "%{$searchValue}%");
                });
            }

            $totalRecords = $query->count();

            $expenses = $query->orderBy('processed_at', 'desc')
                ->offset($start)
                ->limit($length)
                ->get();

            foreach ($expenses as $expense) {
                $settlementDate = $expense->processed_at ? Carbon::parse($expense->processed_at)->format('Y-m-d') : '-';
                
                $data[] = [
                    'transaction_date' => $settlementDate,
                    'transaction_id' => $expense->reference_id ?? '-',
                    'transaction_type' => 'Spends',
                    'amount' => $expense->cod_balance_used,
                    'payment_mode' => 'COD Utilization',
                    'payment_status' => strtoupper($expense->status) ?? 'COMPLETED',
                ];
            }
        } elseif ($tab === 'shopdesk') {
            // Service Fee Tab - Show orders with remittance fees
            $query = Order::where('remittance_reference_id', $remittance->id)
                ->whereNotNull('remittance_amount')
                ->where('remittance_amount', '>', 0);

            // Apply search
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('awb_number', 'LIKE', "%{$searchValue}%")
                        ->orWhere('id', 'LIKE', "%{$searchValue}%")
                        ->orWhere('order_prefix', 'LIKE', "%{$searchValue}%");
                });
            }

            $totalRecords = $query->count();

            $orders = $query->orderBy('created_at', 'desc')
                ->offset($start)
                ->limit($length)
                ->get();

            foreach ($orders as $order) {
                $settlementDate = $order->delivery_date ? Carbon::parse($order->delivery_date)->format('Y-m-d') : '-';
                $orderCreatedDate = Carbon::parse($order->created_at)->format('Y-m-d');
                $amount = $order->remittance_amount ?? 0;

                $data[] = [
                    'settlement_date' => $settlementDate,
                    'order_id' => $order->order_prefix ?? $order->id,
                    'order_created_date' => $orderCreatedDate,
                    'awb_number' => $order->awb_number ?? '-',
                    'transaction_type' => 'Spend',
                    'transaction_details' => config('setting.company_name') . ' Service Fee',
                    'amount' => $amount,
                    'status' => strtoupper($order->status_courier),
                ];
            }
        }

        return $this->successResponse([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ], 'Remittance orders fetched successfully');
    }

    /**
     * Download Excel for Remittance
     */
    public function downloadExcel(Request $request, $id)
    {
        $remittance = SellerRemittance::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'user' && $user->id != $remittance->user_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $tab = $request->input('tab', 'cod');
        $fileName = 'remittance_' . $remittance->remittance_id . '_' . date('Y-m-d') . '.xlsx';

        // Reuse the anonymous class logic from Web Controller but specific to API if needed, 
        // however Excel::download returns a StreamedResponse which is fine for API too.
        
        return Excel::download(new class($remittance, $tab) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $remittance;
            private $tab;

            public function __construct($remittance, $tab)
            {
                $this->remittance = $remittance;
                $this->tab = $tab;
            }

            public function collection()
            {
                $data = collect();

                if ($this->tab === 'cod') {
                    // COD Revenue data
                    $orders = Order::where('remittance_reference_id', $this->remittance->id)
                        ->where('order_type', 'cod')
                        ->whereIn('status_courier', ['delivered', 'Delivered'])
                        ->get();

                    $user = User::findOrFail($this->remittance->user_id);
                    $transferPercentage = $user->getCodWalletTransferValue();

                    $totalCodBalanceExpenses = CodWalletExpense::where('user_id', $this->remittance->user_id)
                        ->whereBetween('processed_at', [$this->remittance->cycle_from_date, $this->remittance->cycle_to_date])
                        ->where('status', 'completed')
                        ->sum('cod_balance_used');

                    $orderExpenses = [];
                    $totalPercentageAmount = 0;

                    foreach ($orders as $order) {
                        $invoiceAmount = $order->invoice_amount ?? 0;
                        $transferAmount = ($invoiceAmount * $transferPercentage) / 100;

                        $orderExpenses[] = [
                            'order' => $order,
                            'invoice_amount' => $invoiceAmount,
                            'transfer_amount' => $transferAmount,
                            'cod_balance_expense' => $transferAmount
                        ];

                        $totalPercentageAmount += $transferAmount;
                    }

                    $remainingBalance = $totalCodBalanceExpenses - $totalPercentageAmount;

                    if ($remainingBalance != 0 && count($orderExpenses) > 0) {
                        for ($i = count($orderExpenses) - 1; $i >= 0 && $remainingBalance > 0; $i--) {
                            $orderExpenses[$i]['cod_balance_expense'] += $remainingBalance;
                            $remainingBalance = 0;
                        }

                        if ($remainingBalance < 0) {
                            $remainingBalance = abs($remainingBalance);
                            for ($i = 0; $i < count($orderExpenses) && $remainingBalance > 0; $i++) {
                                if ($orderExpenses[$i]['cod_balance_expense'] >= $remainingBalance) {
                                    $orderExpenses[$i]['cod_balance_expense'] -= $remainingBalance;
                                    $remainingBalance = 0;
                                } else {
                                    $remainingBalance -= $orderExpenses[$i]['cod_balance_expense'];
                                    $orderExpenses[$i]['cod_balance_expense'] = 0;
                                }
                            }
                        }
                    }

                    foreach ($orderExpenses as $orderData) {
                        $order = $orderData['order'];
                        $data->push([
                            'order_id' => $order->order_prefix ?? $order->id,
                            'order_created_date' => Carbon::parse($order->created_at)->format('Y-m-d'),
                            'awb_number' => $order->awb_number ?? '-',
                            'partner_name' => $order->courier_name ?? '-',
                            'delivery_date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('Y-m-d') : '-',
                            'amount' => number_format($orderData['invoice_amount'], 2),
                            'cod_balance_expenses' => number_format($orderData['cod_balance_expense'], 2),
                            'commission' => number_format($order->remittance_amount ?? 0, 2),
                            'status' => strtoupper($order->status_courier),
                        ]);
                    }
                } elseif ($this->tab === 'shopdesk') {
                    $orders = Order::where('remittance_reference_id', $this->remittance->id)
                        ->whereNotNull('remittance_amount')
                        ->where('remittance_amount', '>', 0)
                        ->get();

                    foreach ($orders as $order) {
                        $data->push([
                            'settlement_date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('Y-m-d') : '-',
                            'order_id' => $order->order_prefix ?? $order->id,
                            'order_created_date' => Carbon::parse($order->created_at)->format('Y-m-d'),
                            'awb_number' => $order->awb_number ?? '-',
                            'transaction_type' => 'Spend',
                            'transaction_details' => config('setting.company_name') . ' Service Fee',
                            'amount' => $order->invoice_amount ?? 0,
                            'commission' => $order->remittance_amount ?? 0,
                            'status' => strtoupper($order->status_courier),
                        ]);
                    }
                }

                return $data;
            }

            public function headings(): array
            {
                if ($this->tab === 'cod') {
                    return [
                        'Order ID',
                        'Order Created Date',
                        'AWB Number',
                        'Partner Name',
                        'Delivery Date',
                        'Amount',
                        'cod Balance Used',
                        'Commission',
                        'Status',
                    ];
                } else {
                    return [
                        'Settlement Date',
                        'Order ID',
                        'Order Created Date',
                        'AWB Number',
                        'Transaction Type',
                        'Transaction Details',
                        'Amount',
                        'Commission',
                        'Status',
                    ];
                }
            }
        }, $fileName);
    }

    /**
     * Process Payment (Admin Only)
     */
    public function processPayment(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->role === 'user') {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $validator = Validator::make($request->all(), [
            'remittance_id' => 'required|exists:seller_remittances,id',
            'bank_account_no' => 'nullable|string|max:50',
            'utr' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'seller_payment_mode' => 'required|string|in:NEFT,RTGS,IMPS,UPI'
        ]);

        if ($validator->fails()) {
            return $this->validateResponse($validator->errors()->first(), 422, $validator->errors());
        }

        try {
            $remittance = SellerRemittance::findOrFail($request->remittance_id);

            if ($remittance->settlement_status === 'completed') {
                return $this->errorResponse('This remittance has already been processed');
            }

            $remittance->update([
                'settlement_status' => 'completed',
                'bank_account_no' => $request->bank_account_no,
                'utr' => $request->utr,
                'payment_date' => $request->payment_date,
                'seller_payment_mode' => $request->seller_payment_mode,
                'updated_at' => now()
            ]);

            return $this->successResponse(
                ['remittance_id' => $remittance->remittance_id],
                'Payment processed successfully!'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process payment: ' . $e->getMessage(), 500);
        }
    }

    // Helper function (private)
    private function calculateRemittanceStats($userId)
    {
        $user = User::findOrFail($userId);
        $codRemittanceCycleDays = $user->getCodRemittanceCycleDays();
        $codRemittancePayoutCycleDays = $user->getPayoutDays();

        $upcomingRemittance = SellerRemittance::where('user_id', $userId)
            ->whereDate('payment_scheduled_date', '>=', Carbon::now()->format('Y-m-d'))
            ->orderBy('payment_scheduled_date', 'desc')
            ->first();

        $nextSettlement = [];
        $nextSettlementDate = "";
        if ($upcomingRemittance) {
            $nextSettlementDate = Carbon::parse($upcomingRemittance->payment_scheduled_date);
            $cycleDate = $upcomingRemittance->cycle_from_date->format('d M Y') . ' - ' . $upcomingRemittance->cycle_to_date->format('d M Y');
            
            $nextSettlement = [
                'cycle_date' => $cycleDate,
                'delivery_count' => $upcomingRemittance->total_delivery_count,
                'cod_amount' => $upcomingRemittance->total_cod_remittance_amount,
                'expenses' => $upcomingRemittance->total_expenses,
                'transferable' => $upcomingRemittance->amount_transferred_to_seller,
            ];
        }

        $previousAggregate = SellerRemittance::where('user_id', $userId)
            ->where('settlement_status', 'completed')
            ->selectRaw('
                COALESCE(SUM(total_delivery_count), 0) as delivery_count,
                COALESCE(SUM(total_cod_remittance_amount), 0) as cod_amount,
                COALESCE(SUM(total_expenses), 0) as expenses,
                COALESCE(SUM(amount_transferred_to_seller), 0) as amount_transferred
            ')
            ->first();

        $firstSettlementDate = SellerRemittance::where('user_id', $userId)
            ->where('settlement_status', 'completed')
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        $previousPayments = [
            'start_date' => $firstSettlementDate ? Carbon::parse($firstSettlementDate)->format('jS M Y') : '',
            'delivery_count'   => (int) ($previousAggregate->delivery_count ?? 0),
            'cod_amount'       => (float) ($previousAggregate->cod_amount ?? 0),
            'expenses'         => (float) ($previousAggregate->expenses ?? 0),
            'amount_transferred' => (float) ($previousAggregate->amount_transferred ?? 0),
        ];

        $totalDeliveredCount = 0;
        $totalCodPending = 0;
        $unsettledExpenses = 0;
        $amountTransferable = 0;
        $nextUpcomingSettlementDate = '';

        if ($upcomingRemittance) {
            $codRemittancePayoutCycleDays = $user->getPayoutDays();
            $codRemittancePayoutCycleDays = array_map('trim', $codRemittancePayoutCycleDays);
            
            $cycleStartDate = Carbon::parse($upcomingRemittance->cycle_to_date)->addDay();
            $cycleData = Helper::calculateCycleFromDate($cycleStartDate, $codRemittancePayoutCycleDays);
            
            $cycleStartDate = $cycleData['cycle_start'];
            $cycleEndDate = $cycleData['cycle_end'];
            $nextUpcomingSettlementDate = $cycleData['payout_date']->format('d M Y');

            $totalDeliveredCount = Order::where('user_id', $userId)
                ->whereNotNull('awb_number')
                ->where('order_type', 'cod')
                ->whereBetween('delivery_date', [$cycleStartDate, $cycleEndDate])
                ->whereIn('status_courier', ['delivered', 'Delivered'])
                ->count();

            $totalCodPending = Order::where('user_id', $userId)
                ->whereBetween('delivery_date', [$cycleStartDate, $cycleEndDate])
                ->whereNotNull('awb_number')
                ->where('order_type', 'cod')
                ->whereIn('status_courier', ['delivered', 'Delivered'])
                ->sum('invoice_amount');

            $unsettledExpenses = CodWalletExpense::where('user_id', $userId)
                ->whereBetween('processed_at', [$cycleStartDate, $cycleEndDate])
                ->where('status', 'completed')
                ->sum('cod_balance_used');
            
            $previousCyclePendingAmount = SellerRemittance::where('user_id', $userId)
                ->where('cycle_to_date', '<', $cycleStartDate)
                ->orderBy('cycle_to_date', 'desc')
                ->value('amount_transferred_next_cycle_or_wallet') ?? 0;

            $amountTransferable = $totalCodPending - $unsettledExpenses - $previousCyclePendingAmount;
        }

        return [
            'next_upcoming_settlement_date' => $nextUpcomingSettlementDate,
            'total_delivered_count' => $totalDeliveredCount,
            'total_cod_pending' => $totalCodPending,
            'unsettled_expenses' => $unsettledExpenses,
            'amount_transferable' => $amountTransferable,
            'next_settlement' => $nextSettlement,
            'next_settlement_date' => $nextSettlementDate ? $nextSettlementDate->format('d M Y') : '',
            'previous_payments' => $previousPayments,
            'upcomingRemittance' => $upcomingRemittance,
        ];
    }


    /**
     * Display remittance statistics and list of remittances for a specific user
     * Mirrors /user/cod-remittance/{userId}
     */
    public function userRemittance(Request $request, $userId)
    {
        $authUser = Auth::user();
        
        // Authorization check: Allow if admin OR if accessing own data
        if ($authUser->role === 'user' && $authUser->id != $userId) {
             return $this->errorResponse('Unauthorized access. Role: ' . $authUser->role . ', ID: ' . $authUser->id . ', Target: ' . $userId, 403);
        }

        $user = User::findOrFail($userId);

        // 1. Calculate Remittance Stats
        $stats = $this->calculateRemittanceStats($userId);

        // 2. Get Remittance List (DataTable logic equivalent)
        $start = $request->input("start") ?? 0;
        $length = $request->input("length") ?? 10;
        $search = $request->input('search');
        $searchValue = is_array($search) ? ($search['value'] ?? '') : ($request->input('search.value') ?? $search ?? '');

        // Base query - Get remittances for user
        $query = SellerRemittance::where('user_id', $userId);

        // Apply search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('remittance_id', 'LIKE', "%{$searchValue}%")
                    ->orWhere('settlement_status', 'LIKE', "%{$searchValue}%")
                    ->orWhere('bank_account_no', 'LIKE', "%{$searchValue}%")
                    ->orWhere('utr', 'LIKE', "%{$searchValue}%")
                    ->orWhere('seller_payment_mode', 'LIKE', "%{$searchValue}%");
            });
        }

        $totalRecords = $query->count();

        // Get paginated data
        $remittances = $query->orderBy('created_at', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        $remittanceList = [];
        
        foreach ($remittances as $remittance) {
             $remittanceList[] = [
                'id' => $remittance->id,
                'cycle_date' => Carbon::parse($remittance->cycle_from_date)->format('d M Y') . ' - ' . Carbon::parse($remittance->cycle_to_date)->format('d M Y'),
                'remittance_id' => $remittance->remittance_id,
                'payment_scheduled_date' => $remittance->payment_scheduled_date ? Carbon::parse($remittance->payment_scheduled_date)->format('d M Y') : '-',
                'payment_date' => $remittance->payment_date ? Carbon::parse($remittance->payment_date)->format('d M Y') : '-',
                'delivery_count' => $remittance->total_delivery_count ?? 0,
                'cod_amount' => $remittance->total_cod_remittance_amount,
                'expenses' => $remittance->total_expenses,
                'transferable_amount' => $remittance->amount_transferred_to_seller,
                'next_cycle_of_wallet' => $remittance->amount_transferred_next_cycle_or_wallet,
                'settlement_status' => $remittance->settlement_status,
                'bank_account_no' => $remittance->bank_account_no ?? '-',
                'utr' => $remittance->utr ?? '-',
                'seller_payment_mode' => $remittance->seller_payment_mode ?? '-',
            ];
        }

        return $this->successResponse([
            'user' => [
                 'id' => $user->id,
                 'name' => $user->name,
                 'company_name' => $user->company_name,
                 'email' => $user->email,
                 'mobile' => $user->mobile,
            ],
            'stats' => $stats,
             'remittances' => [
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $remittanceList
            ]
        ], 'User remittance details fetched successfully');
    }
}

