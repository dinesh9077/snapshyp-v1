<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\SellerRemittance;
use App\Models\CodWalletExpense;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;

class CodRemittanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display list of all users with user role
     */
    public function usersList()
    {
        $authUser = app('authUser');

        // Only allow staff/admin to view this page
        if ($authUser->role === 'user') {
            abort(403, 'Unauthorized access');
        }

        // Calculate total payout amount across all users (completed)
        $totalPayoutAmount = SellerRemittance::where('settlement_status', 'completed')
            ->sum('amount_transferred_to_seller');

        // Calculate total pending payout amount across all users
        $totalPendingPayoutAmount = SellerRemittance::where('settlement_status', 'pending')
            ->sum('amount_transferred_to_seller');

        $users = User::where('role', 'user')->where('status', 1)->get();

        return view('cod-remittance.users-list', compact('totalPayoutAmount', 'totalPendingPayoutAmount', 'users'));
    }

    /**
     * AJAX handler for users list DataTable
     */
    // A seller sees only their own remittance data (same rule as show())
    private function abortUnlessOwnRemittance($userId): void
    {
        $authUser = app('authUser');
        if ($authUser->role === 'user' && (int) $authUser->id !== (int) $userId) {
            abort(403, 'Unauthorized access');
        }
    }

    public function usersListAjax(Request $request)
    {
        // Same rule as usersList(): the all-sellers list is for admin/staff
        if (app('authUser')->role === 'user') {
            abort(403, 'Unauthorized access');
        }

        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search.value') ?? '';

        // Get filter parameters
        $userId = $request->get('user_id');
        $completedPayoutFilter = $request->get('completed_payout_filter');
        $pendingPayoutFilter = $request->get('pending_payout_filter');

        // Base query - Get users with role='user'
        $query = User::where('role', 'user')->where('status', 1);

        // Apply user filter
        if (!empty($userId)) {
            $query->where('id', $userId);
        }

        // Apply completed payout filter
        if (!empty($completedPayoutFilter)) {
            if ($completedPayoutFilter === 'has_completed') {
                // Users with completed payout > 0
                $userIdsWithCompleted = SellerRemittance::where('settlement_status', 'completed')
                    ->where('amount_transferred_to_seller', '>', 0)
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();

                if (!empty($userIdsWithCompleted)) {
                    $query->whereIn('id', $userIdsWithCompleted);
                } else {
                    $query->whereRaw('1 = 0'); // No results
                }
            } elseif ($completedPayoutFilter === 'no_completed') {
                // Users with no completed payout or = 0
                $userIdsWithCompleted = SellerRemittance::where('settlement_status', 'completed')
                    ->where('amount_transferred_to_seller', '>', 0)
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();

                if (!empty($userIdsWithCompleted)) {
                    $query->whereNotIn('id', $userIdsWithCompleted);
                }
            }
        }

        // Apply pending payout filter
        if (!empty($pendingPayoutFilter)) {
            if ($pendingPayoutFilter === 'has_pending') {
                // Users with pending payout > 0
                $userIdsWithPending = SellerRemittance::where('settlement_status', 'pending')
                    ->where('amount_transferred_to_seller', '>', 0)
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();

                if (!empty($userIdsWithPending)) {
                    $query->whereIn('id', $userIdsWithPending);
                } else {
                    $query->whereRaw('1 = 0'); // No results
                }
            } elseif ($pendingPayoutFilter === 'no_pending') {
                // Users with no pending payout or = 0
                $userIdsWithPending = SellerRemittance::where('settlement_status', 'pending')
                    ->where('amount_transferred_to_seller', '>', 0)
                    ->pluck('user_id')
                    ->unique()
                    ->toArray();

                if (!empty($userIdsWithPending)) {
                    $query->whereNotIn('id', $userIdsWithPending);
                }
            }
        }

        // Apply search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('email', 'LIKE', "%{$searchValue}%")
                    ->orWhere('mobile', 'LIKE', "%{$searchValue}%")
                    ->orWhere('company_name', 'LIKE', "%{$searchValue}%");
            });
        }

        $totalRecords = $query->count();

        // Map DataTables sorting
        $columnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderColumn = $request->get('columns')[$columnIndex]['data'] ?? 'sr_no';
        $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

        $columnMap = [
            'name' => 'name',
            'email' => 'email',
            'mobile' => 'mobile',
            'company_name' => 'company_name',
            'status' => 'status',
        ];
        $orderBy = $columnMap[$orderColumn] ?? 'created_at';

        // Get paginated data
        $users = $query->orderBy($orderBy, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = [];
        foreach ($users as $index => $user) {
            // Calculate total payout amount for this user (completed)
            $userPayoutAmount = SellerRemittance::where('user_id', $user->id)
                ->where('settlement_status', 'completed')
                ->sum('amount_transferred_to_seller');

            // Calculate total pending payout amount for this user
            $userPendingPayoutAmount = SellerRemittance::where('user_id', $user->id)
                ->where('settlement_status', 'pending')
                ->sum('amount_transferred_to_seller');

            // Generate View button that redirects to user remittance page
            $viewUrl = route('user/cod-remittance', $user->id);
            $action = '<a href="' . $viewUrl . '" class="btn btn-icon waves-effect waves-light action-icon"><i class="mdi mdi-eye"></i></a>';

            $data[] = [
                'sr_no' => $start + $index + 1,
                'name' => $user->name ?? '-',
                'email' => $user->email ?? '-',
                'mobile' => $user->mobile ?? '-',
                'company_name' => $user->company_name ?? '-',
                'status' => $user->status == 1
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>',
                'total_payout_amount' => config('setting.currency') . ' ' . number_format($userPayoutAmount, 2),
                'total_pending_payout_amount' => config('setting.currency') . ' ' . number_format($userPendingPayoutAmount, 2),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Display user's COD remittance page
     */
    public function userRemittance(Request $request, $userId)
    {
        $user = app('authUser');
        // Ensure user can only view their own remittance
        if ($user->id != $userId && $user->role === "user") {
            abort(403, 'Unauthorized access');
        }

        $user = User::findOrFail($userId);

        // Mark notification as read if coming from notification click
        if ($request->has('notify_id')) {
            $notifyId = $request->get('notify_id');
            \App\Models\Notification::where('id', $notifyId)
                ->where('user_id', app('authUser')->id)
                ->update(['read_at' => 1]);
        }

        // Calculate remittance statistics
        $stats = $this->calculateRemittanceStats($userId);

        return view('cod-remittance.user-remittance', compact('user', 'stats'));
    }

    public function userRemittanceAjax(Request $request, $userId)
    {
        $this->abortUnlessOwnRemittance($userId);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search') ?? $request->get('search.value') ?? '';

        // Base query - Get delivered COD orders
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

        // Map DataTables sorting
        $columnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderColumn = $request->get('columns')[$columnIndex]['data'] ?? 'cycle_date';
        $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

        $columnMap = [
            'remittance_id' => 'remittance_id',
            'payment_scheduled_date' => 'payment_scheduled_date',
            'payment_date' => 'payment_date',
            'transferable_amount' => 'amount_transferred_to_seller',
            'settlement_status' => 'settlement_status',
            'utr' => 'utr',
        ];
        $orderBy = $columnMap[$orderColumn] ?? 'created_at';

        // Get paginated data
        $remittances = $query->orderBy($orderBy, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = [];
        $authUser = app('authUser');

        foreach ($remittances as $index => $remittance) {

            $action = '<a href="' . route('cod-remittance.show', $remittance->id) . '" class="btn btn-icon waves-effect waves-light action-icon"><i class="mdi mdi-eye"></i></a>';
            $action .= '  <a href="' . route('cod-remittance.export-excel', $remittance->id) . '?tab=cod" class="btn btn-icon waves-effect waves-light action-icon"><i class="mdi mdi-file-download"></i></a>';

            // Add Pay Now link for pending remittances (admin/staff only)
            if ($remittance->settlement_status === 'pending' && $authUser->role !== 'user' && floatval($remittance->amount_transferred_to_seller) >= 0.01) {
                $action .= ' <a href="javascript:void(0)" class="pay-now-link" 
                    data-remittance-id="' . $remittance->id . '" 
                    data-amount="' . $remittance->amount_transferred_to_seller . '" 
                    style="">Pay Now</a>';
            }

            $data[] = [
                'sr_no' => $start + $index + 1,
                'cycle_date' => Carbon::parse($remittance->cycle_from_date)->format('d M Y') . ' - ' . Carbon::parse($remittance->cycle_to_date)->format('d M Y'),
                'remittance_id' => '<a href="' . route('cod-remittance.show', $remittance->id) . '" style="text-decoration: underline !important;">' . $remittance->remittance_id . '</a>',
                'payment_scheduled_date' => $remittance->payment_scheduled_date ? Carbon::parse($remittance->payment_scheduled_date)->format('d M Y') : '-',
                'payment_date' => $remittance->payment_date ? Carbon::parse($remittance->payment_date)->format('d M Y') : '-',
                'delivery_count' => $remittance->total_delivery_count ?? 0,
                'cod_amount' => config('setting.currency') . ' ' . number_format($remittance->total_cod_remittance_amount, 2),
                'expenses' => config('setting.currency') . ' ' . number_format($remittance->total_expenses, 2),
                'transferable_amount' => config('setting.currency') . ' ' . number_format($remittance->amount_transferred_to_seller, 2),
                'next_cycle_of_wallet' => $remittance->amount_transferred_next_cycle_or_wallet > 0
                    ? '- ' . config('setting.currency') . ' ' . number_format($remittance->amount_transferred_next_cycle_or_wallet, 2)
                    : config('setting.currency') . ' 0',
                'settlement_status' => $remittance->settlement_status === "pending" ? "<span class='badge badge-danger'>PENDING</span>" : "<span class='badge badge-success'>COMPLETED</span>",
                'bank_account_no' => $remittance->bank_account_no ?? '-',
                'utr' => $remittance->utr ?? '-',
                'seller_payment_mode' => $remittance->seller_payment_mode ?? '-',
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Calculate remittance statistics
     */
    private function calculateRemittanceStats($userId)
    {
        // Get user instance to access user-specific settings
        $user = User::findOrFail($userId);

        // Get user-specific COD settings or use global config
        $codRemittanceCycleDays = $user->getCodRemittanceCycleDays();
        $codRemittancePayoutCycleDays = $user->getPayoutDays();

        // Get next upcoming settlement date
        $upcomingRemittance = SellerRemittance::where('user_id', $userId)
            ->whereDate('payment_scheduled_date', '>=', Carbon::now()->format('Y-m-d'))
            ->orderBy('payment_scheduled_date', 'desc')
            ->first();

        $nextSettlement = [];
        $nextSettlementDate = "";
        if ($upcomingRemittance) {
            $nextSettlementDate = Carbon::parse($upcomingRemittance->payment_scheduled_date);

            $cycleDate = $upcomingRemittance->cycle_from_date->format('d M Y') . ' - ' . $upcomingRemittance->cycle_to_date->format('d M Y');
            // Next upcoming settlement
            $nextSettlement = [
                'cycle_date' => $cycleDate,
                'delivery_count' => $upcomingRemittance->total_delivery_count,
                'cod_amount' => $upcomingRemittance->total_cod_remittance_amount,
                'expenses' => $upcomingRemittance->total_expenses,
                'transferable' => $upcomingRemittance->amount_transferred_to_seller,
            ];
        }

        // 1. Aggregate previous completed remittances
        $previousAggregate = SellerRemittance::where('user_id', $userId)
            ->where('settlement_status', 'completed')
            ->selectRaw('
                COALESCE(SUM(total_delivery_count), 0) as delivery_count,
                COALESCE(SUM(total_cod_remittance_amount), 0) as cod_amount,
                COALESCE(SUM(total_expenses), 0) as expenses,
                COALESCE(SUM(amount_transferred_to_seller), 0) as amount_transferred
            ')
            ->first();

        // 2. Get first completed settlement date (for "After XX date")
        $firstSettlementDate = SellerRemittance::where('user_id', $userId)
            ->where('settlement_status', 'completed')
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        // 3. Final formatted response
        $previousPayments = [
            'start_date' => $firstSettlementDate
                ? Carbon::parse($firstSettlementDate)->format('jS M Y')
                : '',

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
            // Get user payout days
            $codRemittancePayoutCycleDays = $user->getPayoutDays();
            $codRemittancePayoutCycleDays = array_map('trim', $codRemittancePayoutCycleDays);

            $cycleStartDate = Carbon::parse($upcomingRemittance->cycle_to_date)->addDay();

            $cycleData = Helper::calculateCycleFromDate($cycleStartDate, $codRemittancePayoutCycleDays);

            $cycleStartDate = $cycleData['cycle_start'];
            $cycleEndDate = $cycleData['cycle_end'];
            $nextUpcomingSettlementDate = $cycleData['payout_date']->format('d M Y');

            // Total delivered count
            $totalDeliveredCount = Order::where('user_id', $userId)
                ->whereNotNull('awb_number')
                ->where('order_type', 'cod')
                ->whereBetween('delivery_date', [$cycleStartDate, $cycleEndDate])
                ->whereIn('status_courier', ['delivered', 'Delivered'])
                ->count();

            // Total COD pending
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

            // Amount transferable
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
     * Display remittance detail page
     */
    public function show($id)
    {
        $remittance = SellerRemittance::with('user', 'orders')->findOrFail($id);

        // Check authorization
        $authUser = app('authUser');
        if ($authUser->role === 'user' && $authUser->id != $remittance->user_id) {
            abort(403, 'Unauthorized access');
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


        return view('cod-remittance.show', compact('remittance', 'codWalletExpenses', 'walletCount'));
    }

    /**
     * Get orders for remittance via AJAX
     */
    public function remittanceOrdersAjax(Request $request, $id)
    {
        $remittance = SellerRemittance::with('orders')->findOrFail($id);
        $this->abortUnlessOwnRemittance($remittance->user_id);

        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search.value') ?? '';
        $tab = $request->get('tab', 'cod'); // cod, shipping (wallet balance), shopdesk (service fee)

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

            $columnIndex = $request->get('order')[0]['column'] ?? 0;
            $orderColumn = $request->get('columns')[$columnIndex]['data'] ?? 'order_created_date';
            $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

            $columnMap = [
                'order_id' => 'order_prefix',
                'order_created_date' => 'created_at',
                'awb_number' => 'awb_number',
                'amount' => 'invoice_amount',
            ];
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';

            $orders = $query->orderBy($orderBy, $orderDir)
                ->offset($start)
                ->limit($length)
                ->get();

            foreach ($orders as $index => $order) {
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
                    'amount' => number_format($amount, 2),
                    'trigger_state' => '<span class="badge badge-success">' . strtoupper($order->status_courier) . '</span>',
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

            foreach ($expenses as $index => $expense) {
                $settlementDate = $expense->processed_at ? Carbon::parse($expense->processed_at)->format('Y-m-d') : '-';
                $orderCreatedDate = $expense->created_at ? Carbon::parse($expense->created_at)->format('Y-m-d') : '-';

                $data[] = [
                    'transaction_date' => $settlementDate,
                    'transaction_id' => $expense->reference_id ?? '-',
                    'transaction_type' => 'Spends',
                    'amount' => number_format($expense->cod_balance_used, 2),
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

            foreach ($orders as $index => $order) {
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
                    'amount' => config('setting.currency') . number_format($amount, 2),
                    'trigger_state' => '<span class="badge badge-success">' . ucfirst($order->status_courier) . '</span>',
                ];
            }
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    public function orderDetail($orderId)
    {
        // Find the order
        $order = Order::where('id', $orderId)
            ->orWhere('order_prefix', $orderId)
            ->firstOrFail();
        $this->abortUnlessOwnRemittance($order->user_id);

        // Get all transactions for this order (COD, Service Fee, etc.)
        $transactions = [];

        // COD Revenue transaction
        if ($order->order_type === 'cod' && in_array($order->status_courier, ['delivered', 'Delivered'])) {
            $transactions[] = [
                'date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d M Y') : '-',
                'awb_number' => $order->awb_number ?? '-',
                'trans_type' => 'Revenue',
                'transaction_details' => "COD Amt. (via " . config('setting.company_name') . ")",
                'amount' => $order->invoice_amount ?? 0,
                'trigger_state' => strtoupper($order->status_courier),
                'remittance_cycle' => $order->remittance_reference_id ? 'ID: ' . $order->remittance_reference_id : 'NA',
                'settlement_status' => $order->remittance_reference_id ? 'COMPLETED' : 'PENDING',
            ];
        }

        // Service Fee transaction
        if ($order->shipping_charge && $order->shipping_charge > 0) {
            $transactions[] = [
                'date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d M Y') : '-',
                'awb_number' => $order->awb_number ?? '-',
                'trans_type' => 'Spend',
                'transaction_details' => "Shipping Charge (via " . config('setting.company_name') . ")",
                'amount' => - ($order->shipping_charge),
                'trigger_state' => strtoupper($order->status_courier),
                'remittance_cycle' => $order->remittance_reference_id ? 'ID: ' . $order->remittance_reference_id : 'NA',
                'settlement_status' => $order->remittance_reference_id ? 'COMPLETED' : 'PENDING',
            ];
        }

        // Shopdesk Service Fees (if applicable)
        if ($order->remittance_amount && $order->remittance_amount > 0) {
            $transactions[] = [
                'date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d M Y') : '-',
                'awb_number' => $order->awb_number ?? '-',
                'trans_type' => 'Spend',
                'transaction_details' => config('setting.company_name') . ' Service Fees',
                'amount' => - ($order->remittance_amount),
                'trigger_state' => strtoupper($order->status_courier),
                'remittance_cycle' => $order->remittance_reference_id ? 'ID: ' . $order->remittance_reference_id : 'NA',
                'settlement_status' => $order->remittance_reference_id ? 'COMPLETED' : 'PENDING',
            ];
        }

        return view('cod-remittance.order-detail', compact('order', 'transactions'));
    }

    public function exportExcel(Request $request, $id)
    {
        $remittance = SellerRemittance::findOrFail($id);
        $this->abortUnlessOwnRemittance($remittance->user_id);
        $tab = $request->get('tab', 'cod');

        $fileName = 'remittance_' . $remittance->remittance_id . '_' . date('Y-m-d') . '.xlsx';

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

                    // Get user instance to access user-specific settings
                    $user = User::findOrFail($this->remittance->user_id);

                    // Get user-specific transfer percentage or use global config
                    $transferPercentage = $user->getCodWalletTransferValue();

                    // Get total COD wallet expenses for this cycle
                    $totalCodBalanceExpenses = CodWalletExpense::where('user_id', $this->remittance->user_id)
                        ->whereBetween('processed_at', [$this->remittance->cycle_from_date, $this->remittance->cycle_to_date])
                        ->where('status', 'completed')
                        ->sum('cod_balance_used');

                    // First pass: Calculate percentage-based amounts for each order
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

                    // Calculate remaining balance after percentage distribution
                    $remainingBalance = $totalCodBalanceExpenses - $totalPercentageAmount;

                    // Distribute remaining balance across orders from the end
                    if ($remainingBalance != 0 && count($orderExpenses) > 0) {
                        // Start from the last order and work backwards
                        for ($i = count($orderExpenses) - 1; $i >= 0 && $remainingBalance > 0; $i--) {
                            // Add remaining balance to this order
                            $orderExpenses[$i]['cod_balance_expense'] += $remainingBalance;
                            $remainingBalance = 0; // All distributed

                            // If this makes the order's COD balance negative, redistribute
                            // (This shouldn't happen in normal cases, but added for safety)
                        }

                        // If there's still remaining balance (negative case), distribute proportionally
                        if ($remainingBalance < 0) {
                            // This means we over-distributed, need to reduce
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

                    // Build data array for export
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
                    // Service Fee data
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
     * Process remittance payment (Admin only)
     */
    public function processPayment(Request $request)
    {
        $authUser = app('authUser');

        // Only allow admin/staff to process payments
        if ($authUser->role === 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Validate request using Validator
        $validator = Validator::make($request->all(), [
            'remittance_id' => 'required|exists:seller_remittances,id',
            'bank_account_no' => 'nullable|string|max:50',
            'utr' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'seller_payment_mode' => 'required|string|in:NEFT,RTGS,IMPS,UPI'
        ], [
            'remittance_id.required' => 'Remittance ID is required',
            'remittance_id.exists' => 'Invalid remittance ID',
            'utr.required' => 'UTR number is required',
            'utr.max' => 'UTR number cannot exceed 50 characters',
            'payment_date.required' => 'Payment date is required',
            'payment_date.date' => 'Invalid payment date format',
            'seller_payment_mode.required' => 'Payment mode is required',
            'seller_payment_mode.in' => 'Invalid payment mode selected'
        ]);

        // Return first validation error
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Find the remittance
            $remittance = SellerRemittance::findOrFail($request->remittance_id);

            // Check if already completed
            if ($remittance->settlement_status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This remittance has already been processed'
                ]);
            }

            // Update remittance with payment details
            $remittance->update([
                'settlement_status' => 'completed',
                'bank_account_no' => $request->bank_account_no,
                'utr' => $request->utr,
                'payment_date' => $request->payment_date,
                'seller_payment_mode' => $request->seller_payment_mode,
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully! Remittance ID: ' . $remittance->remittance_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
