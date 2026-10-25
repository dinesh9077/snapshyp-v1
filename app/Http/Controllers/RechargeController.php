<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Billing;
use App\Models\SellerRemittance;
use App\Models\Order;
use App\Models\CodWalletExpense;
use App\Models\CodVoucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use App\Services\MasterService;
use App\Models\FranchiseEarning;
use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class RechargeController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function walletRecharge()
	{
		$user = app('authUser');

		if ($user->role === 'master_admin') {
			return redirect()->route('home')->with('error', 'Master Admin does not have wallet access.');
		}
		$codBalance = 0;
		$codTransferBalance = 0;
		$walletTransferValue = 0;
		$today = now();
		if ($user->codPlan) {
			$userPlanDays = (int)($user->codPlan->days + 1);
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
			$walletTransferValue = config('setting.cod_wallet_transfer_value', 0);
			$codTransferBalance = $walletTransferValue > 0 ? ($codBalance * $walletTransferValue / 100) : $codBalance;
		}

		$codWalletBalance = SellerRemittance::where('user_id', $user->id)
			->where('settlement_status', 'pending')
			->sum('amount_transferred_to_seller');

		$codWalletBalance += CodVoucher::where('user_id', $user->id)
			->where('voucher_status', 0)
			->sum('net_amount');

		$codWalletBalance = max(0, $codWalletBalance);
		$codRechargeAvailable = max(0, $codTransferBalance + $codWalletBalance);
		$recentTransactions = UserWallet::where('user_id', $user->id)
			->latest()
			->limit(10)
			->get();

		return view('recharge.wallet', compact('user', 'codBalance', 'codTransferBalance', 'walletTransferValue', 'codWalletBalance', 'codRechargeAvailable', 'recentTransactions'));
	}

    public function storeCodWalletExpense(Request $request)
	{
		try {
			// Validate request using Validator facade
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|exists:users,id',
				'cod_amount' => 'required|numeric|min:0.01',
				'cod_balance_percent' => 'required|numeric|min:0',
				'cod_balance_amount' => 'required|numeric|min:0',
				'payment_method' => 'required|in:cod',
			]);

			// Check if validation fails
			if ($validator->fails()) {
				return response()->json([
					'status' => 'error',
					'msg' => $validator->errors()->first()
				]);
			}

			$userId = $request->user_id;
			$codAmount = (float) $request->cod_amount;
			$codBalancePercent = $request->cod_balance_percent;
			$codBalanceAmount = (float) $request->cod_balance_amount;
			$codWalletAmount = (float) ($request->cod_wallet_amount ?? 0);

			// Get user
			$user = User::findOrFail($userId);

			// Total available
			$totalAvailable = $codBalanceAmount + $codWalletAmount;

			// Check if requested amount exceeds available balance
			if ($codAmount > ($totalAvailable + 0.01)) { // Small epsilon for float comparison
				return response()->json([
					'status' => 'error',
					'msg' => 'Insufficient COD balance. Available: ₹' . number_format($totalAvailable, 2)
				]);
			}

			// Begin database transaction
			DB::beginTransaction();

			try {
				// Generate unique reference ID
				$referenceId = CodWalletExpense::generateReferenceId();

				// If we are using more than what is available in unremitted balance,
				// we need to deduct the rest from remitted pending payouts.
				$amountFromRemittance = 0;
				if ($codAmount > $codBalanceAmount) {
					$amountFromRemittance = $codAmount - $codBalanceAmount;
				}

			if ($amountFromRemittance > 0) {
					// Deduct from pending remittances, oldest first
					$pendingRemittances = SellerRemittance::where('user_id', $userId)
						->where('settlement_status', 'pending')
						->orderBy('cycle_from_date', 'asc')
						->get();

					$remainingToDeduct = $amountFromRemittance;
					foreach ($pendingRemittances as $remittance) {
						if ($remainingToDeduct <= 0) break;

						$availableInRemittance = $remittance->amount_transferred_to_seller;
						$deduction = min($availableInRemittance, $remainingToDeduct);

						$remittance->decrement('amount_transferred_to_seller', $deduction);
						$remittance->increment('total_expenses', $deduction);

						// If fully utilized, mark as completed
						if ($remittance->fresh()->amount_transferred_to_seller <= 0.01) {
							$remittance->update(['settlement_status' => 'completed']);
						}

						$remainingToDeduct -= $deduction;
					}

					// If we still have remaining to deduct, check CodVoucher
					if ($remainingToDeduct > 0.01) {
						$pendingVouchers = CodVoucher::where('user_id', $userId)
							->where('voucher_status', 0)
							->orderBy('voucher_date', 'asc')
							->get();

						foreach ($pendingVouchers as $voucher) {
							if ($remainingToDeduct <= 0) break;

							$availableInVoucher = $voucher->net_amount;
							$deduction = min($availableInVoucher, $remainingToDeduct);

							$voucher->increment('cod_balance_expense', $deduction);
							$voucher->decrement('net_amount', $deduction);

							// If fully utilized, mark as paid
							if ($voucher->fresh()->net_amount <= 0.01) {
								$voucher->update(['voucher_status' => 1]);
							}

							$remainingToDeduct -= $deduction;
						}
					}

					// If we still have remainingToDeduct, it means the user's codWalletAmount was outdated
					if ($remainingToDeduct > 0.01) {
						DB::rollBack();
						return response()->json([
							'status' => 'error',
							'msg' => 'Insufficient funds in COD Wallet (Pending Payouts).'
						]);
					}
				}

				$walletBillingType = 'COD to Wallet Transfer';
				$walletTransactionType =  'COD Utilization';
				$walletPgName = 'COD Balance';
				$successMessage = 'COD balance successfully transferred to wallet!';

				// Create COD wallet expense record
				$codExpense = CodWalletExpense::create([
					'user_id' => $userId,
					'cod_balance_used' => $codAmount,
					'wallet_amount_added' => $codAmount, // This is how much was added to wallet
					'transfer_percentage' => $codBalancePercent,
					'transaction_type' => 'cod_to_wallet',
					'status' => 'completed',
					'reference_id' => $referenceId,
					'remarks' => 'COD balance transferred to wallet. ' . ($amountFromRemittance > 0 ? '(₹' . number_format($amountFromRemittance, 2) . ' from Pending Payouts)' : ''),
					'processed_at' => now(),
				]);

				// Update user wallet
				$user->increment('wallet_amount', $codAmount);

				// Create billing entry
				Billing::create([
					'user_id' => $userId,
					'billing_type' => $walletBillingType,
					'billing_type_id' => $codExpense->id,
					'transaction_type' => 'credit',
					'amount' => $codAmount,
					'note' => "COD balance of ₹{$codAmount} transferred to wallet. Reference: {$referenceId}"
				]);

				// Create user wallet transaction record
				UserWallet::create([
					'user_id' => $userId,
					'amount' => $codAmount,
					'amount_type' => 'credit',
					'transaction_type' => $walletTransactionType,
					'pg_name' => $walletPgName,
					'transaction_status' => 'Paid',
					'txn_number' => $referenceId,
					'order_id' => null,
					'utr_no' => $referenceId,
					'status' => 1,
				]);

				DB::commit();

				return response()->json([
					'status' => 'success',
					'msg' => $successMessage,
					'data' => [
						'reference_id' => $referenceId,
						'cod_used' => $codAmount,
						'wallet_added' => $codAmount,
						'new_wallet_balance' => $user->fresh()->wallet_amount
					]
				]);
			} catch (\Exception $e) {
				DB::rollBack();
				throw $e;
			}
		} catch (\Exception $e) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to process COD transfer: ' . $e->getMessage()
			]);
		}
	}

    public function thankYou()
    {
        return view('recharge.thankyou');
    }

	public function rechargeList()
	{
		return view('recharge.index');
	}

	public function rechargeListAjax(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->get("start");
		$limit = $request->get("length"); // Rows per page

		$columnIndex_arr = $request->get('order');
		$columnName_arr = $request->get('columns');
		$order_arr = $request->get('order');


		$columnIndex = $columnIndex_arr[0]['column'] ?? 0;
		$orderData = $columnName_arr[$columnIndex]['data'] ?? 'id';
		$dir = $order_arr[0]['dir'] ?? 'desc';

		// Map columns to database fields
		$sortMapping = [
			'id' => 'user_wallets.id',
			'created_at' => 'user_wallets.created_at',
			'amount' => 'user_wallets.amount',
			'amount_type' => 'user_wallets.amount_type',
			'order_id' => 'user_wallets.order_id',
			'txn_number' => 'user_wallets.txn_number',
			'utr_no' => 'user_wallets.utr_no',
			'payment_mode' => 'user_wallets.transaction_type',
			'pg_name' => 'user_wallets.pg_name',
			'transaction_status' => 'user_wallets.transaction_status',
			'action' => 'user_wallets.id'
		];

		$order = $sortMapping[$orderData] ?? 'user_wallets.id';

		$userId = Auth::id();

		// Base query
		$query = UserWallet::with('user:id,name')
			->where('user_wallets.user_id', $userId);

		if ($request->filled('payment_mode')) {
			$query->where('user_wallets.transaction_type', $request->payment_mode);
		}

		if ($request->filled('status')) {
			$query->where('user_wallets.transaction_status', $request->status);
		}

		if ($request->filled('fromdate') && $request->filled('todate')) {
			$query->whereBetween('user_wallets.created_at', [
				Carbon::parse($request->fromdate)->startOfDay(),
				Carbon::parse($request->todate)->endOfDay()
			]);
		}

		// Clone query for total count
		$totalData = (clone $query)->count();

		// Search filter
		$searchValue = $request->input('search') ?? $request->input('search.value') ?? '';
		if (!empty($searchValue)) {
			$query->where(function ($q) use ($searchValue) {
				$q->whereHas('user', function ($q2) use ($searchValue) {
					$q2->where('name', 'LIKE', "%{$searchValue}%");
				})
					->orWhere('user_wallets.amount', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.created_at', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.order_id', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.txn_number', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.transaction_type', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.amount_type', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.pg_name', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.transaction_status', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.utr_no', 'LIKE', "%{$searchValue}%");
			});
		}

		$totalFiltered = $query->count();

		// Fetch paginated data
		$values = $query->offset($start)
			->limit($limit)
			->orderBy($order, $dir)
			->get();

		$data = [];
		$i = $start + 1;
		foreach ($values as $value) {
			// $receipt = '';
			// if ($value->payment_receipt) {
			// 	foreach (explode(',', $value->payment_receipt) as $image) {
			// 		$receipt .= '<a href="'.url('storage/receipt',$image).'" target="_blank">
			// 						<img src="'.url('storage/receipt',$image).'" style="height:50px">
			// 					 </a>';
			// 	}
			// }

			$data[] = [
				'id' => $i++,
				'created_at' => $value->created_at->format('d M Y'),
				'amount_type' => $value->amount_type ? ucwords($value->amount_type) : 'N/A',
				'amount' => $value->amount,
				'order_id' => $value->order_id ?? 'N/A',
				'txn_number' => $value->txn_number ?? 'N/A',
				'utr_no' => $value->utr_no ?? 'N/A',
				'payment_mode' => $value->transaction_type,
				'pg_name' => $value->pg_name ?? 'N/A',
				'transaction_status' => $value->transaction_status == "Paid"
					? '<span class="badge badge-success">Paid</span>'
					: ($value->transaction_status == "Rejected"
						? '<span class="badge badge-danger">Rejected</span><br><small>Reject Note: ' . e($value->reject_note) . '</small>'
						: '<span class="badge badge-warning">Pending</span>'
					),
			];
		}

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
		]);
	}

	public function rechargeListAdmin()
	{
		$users = User::where('role', 'user')->where('kyc_status', '!=', 0)->orderBy('name')->get();
		return view('recharge.recharg_history', compact('users'));
	}

	public function rechargeListAjaxAdmin(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->get("start");
		$limit = $request->get("length");

		$columnIndex = $request->input('order.0.column', 0);
		$orderData = $request->input("columns.$columnIndex.data", 'id');
		$dir = $request->input('order.0.dir', 'desc');

		// Map columns to database fields
		$sortMapping = [
			'id' => 'user_wallets.id',
			'username' => 'users.name',
			'created_at' => 'user_wallets.created_at',
			'amount' => 'user_wallets.amount',
			'amount_type' => 'user_wallets.amount_type',
			'order_id' => 'user_wallets.order_id',
			'txn_number' => 'user_wallets.txn_number',
			'utr_no' => 'user_wallets.utr_no',
			'payment_mode' => 'user_wallets.transaction_type',
			'pg_name' => 'user_wallets.pg_name',
			'transaction_status' => 'user_wallets.transaction_status',
			'action' => 'user_wallets.id'
		];

		$order = $sortMapping[$orderData] ?? 'user_wallets.id';

		// Base query with join for sorting
		$query = UserWallet::select('user_wallets.*')
			->leftJoin('users', 'user_wallets.user_id', '=', 'users.id')
			->with('user:id,name');

		if ($request->filled('user_id')) {
			$query->where('user_wallets.user_id', $request->user_id);
		}

		if ($request->filled('payment_mode')) {
			$query->where('user_wallets.transaction_type', $request->payment_mode);
		}

		if ($request->filled('status')) {
			$query->where('user_wallets.transaction_status', $request->status);
		}

		if ($request->filled('fromdate') && $request->filled('todate')) {
			$query->whereBetween('user_wallets.created_at', [
				Carbon::parse($request->fromdate)->startOfDay(),
				Carbon::parse($request->todate)->endOfDay()
			]);
		}

		// Clone for total count
		$totalData = (clone $query)->count();

		// Search filter
		$searchValue = $request->input('search') ?? $request->input('search.value') ?? '';
		if (!empty($searchValue)) {
			$query->where(function ($q) use ($searchValue) {
				$q->whereHas('user', function ($q2) use ($searchValue) {
					$q2->where('name', 'LIKE', "%{$searchValue}%");
				})
					->orWhere('user_wallets.amount', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.created_at', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.order_id', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.txn_number', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.transaction_type', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.amount_type', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.pg_name', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.transaction_status', 'LIKE', "%{$searchValue}%")
					->orWhere('user_wallets.utr_no', 'LIKE', "%{$searchValue}%")
					->orwhereHas('user', function ($q) use ($searchValue) {
						$q->where('name', 'LIKE', "%{$searchValue}%");
					});
			});
		}

		$totalFiltered = $query->count();

		// Fetch paginated data
		$values = $query->offset($start)
			->limit($limit)
			->orderBy($order, $dir)
			->get();

		$data = [];
		$i = $start + 1;

		foreach ($values as $value) {
			$array = [
				'id' => $i++,
				'username' => $value->user->name ?? 'N/A',
				'created_at' => $value->created_at->format('d M Y'),
				'amount_type' => $value->amount_type ? ucwords($value->amount_type) : 'N/A',
				'amount' => $value->amount,
				'order_id' => $value->order_id ?? 'N/A',
				'txn_number' => $value->txn_number ?? 'N/A',
				'utr_no' => $value->utr_no ?? 'N/A',
				'payment_mode' => $value->transaction_type,
				'pg_name' => $value->pg_name ?? 'N/A',
				'transaction_status' =>
				$value->transaction_status == "Paid"
					? '<span class="badge badge-success">Paid</span>'
					: ($value->transaction_status == "Rejected"
						? '<span class="badge badge-danger">Rejected</span><br><small>Reject Note: ' . e($value->reject_note) . '</small>'
						: '<span class="badge badge-warning">Pending</span>'
					),
			];


			$array['action'] = "";
			if ($value->transaction_status == "Pending") {
				$array['action'] = '<button type="button" data-id="' . $value->id . '" data-reject_note="' . ($value->reject_note ?? '') . '" data-status="' . $value->transaction_status . '" class="new-submit-thmebtn" onclick="approvedRequest(this);">Approve Request</button>';
			}

			$data[] = $array;
		}

		return response()->json([
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
		]);
	}

	public function rechargeWalletAction(Request $request)
	{
		$validated = $request->validate([
			'status' => "required|in:Pending,Paid,Rejected",
			'passkey' => "required"
		]);

		$user = Auth::user();
		// Hash::check returns true when plain text matches hashed password
		if (!Hash::check($request->input('passkey'), $user->password)) {
			return back()->with('error', 'Passkey does not match.');
		}

		$userWallet = UserWallet::with('user')->findOrFail($request->id);
		try {
			DB::transaction(function () use ($request, $userWallet) {
				if ($request->status === "Pending") {
					$userWallet->update(['transaction_status' => 'Pending']);
				}

				if ($request->status === "Paid") {
					// Credit to wallet
					$userWallet->user->increment('wallet_amount', $userWallet->amount);

					// Add billing entry
					Billing::create([
						'user_id'          => $userWallet->user_id,
						'billing_type'     => "Recharge Wallet",
						'billing_type_id'  => $userWallet->id,
						'transaction_type' => 'credit',
						'amount'           => $userWallet->amount,
						'note' => 'The amount has been credited and approved by the admin.'
					]);

					$userWallet->update(['transaction_status' => 'Paid']);
				}

				if ($request->status === "Rejected") {
					$userWallet->update([
						'transaction_status'       => 'Rejected',
						'reject_note'  => $request->reject_note,
					]);
				}
			});

			return back()->with('success', "The payment status has been updated successfully.");
		} catch (\Exception $e) {
			return back()->with('error', "Error: " . $e->getMessage());
		}
	}

	public function createRazorpayOrder(Request $request)
	{
		try {
			$minRechargeAmount = (float) config('setting.recharge_min_amount', 200);
			$maxRechargeAmount = (float) config('setting.recharge_max_amount', 10000);
			if ($maxRechargeAmount < $minRechargeAmount) {
				$maxRechargeAmount = $minRechargeAmount;
			}

			// Validate request
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|exists:users,id',
				'amount' => "required|numeric|min:{$minRechargeAmount}|max:{$maxRechargeAmount}"
			], [
				'amount.min' => "Minimum recharge amount is ₹{$minRechargeAmount}",
				'amount.max' => "Maximum recharge amount is ₹{$maxRechargeAmount}",
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => 'error',
					'msg' => $validator->errors()->first()
				], 422);
			}

			// Check if Razorpay is active
			if (!config('setting.razorpay_status')) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Razorpay payment gateway is currently disabled'
				], 400);
			}

			$userId = $request->user_id;
			$amount = $request->amount;

			// Initialize Razorpay API
			$api = new Api(
				config('setting.razorpay_key_id'),
				config('setting.razorpay_key_secret')
			);

			// Create Razorpay order
			$orderData = [
				'receipt' => 'wallet_' . $userId . '_' . time(),
				'amount' => $amount * 100, // Convert to paise
				'currency' => 'INR',
				'notes' => [
					'user_id' => $userId,
					'purpose' => 'Wallet Recharge'
				]
			];

			$razorpayOrder = $api->order->create($orderData);

			// Save order to database
			$userWallet = UserWallet::create([
				'user_id' => $userId,
				'amount' => $amount,
				'amount_type' => 'credit',
				'transaction_type' => 'Razorpay',
				'pg_name' => 'Razorpay',
				'order_id' => $razorpayOrder->id,
				'transaction_status' => 'Pending',
				'status' => 1,
				'payable_response' => json_encode($razorpayOrder->toArray())
			]);

			return response()->json([
				'status' => 'success',
				'msg' => 'Order created successfully',
				'order' => [
					'id' => $razorpayOrder->id,
					'amount' => $razorpayOrder->amount,
					'currency' => $razorpayOrder->currency,
					'receipt' => $razorpayOrder->receipt
				],
				'wallet_id' => $userWallet->id
			]);
		} catch (\Exception $e) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to create order: ' . $e->getMessage()
			], 500);
		}
	}

	public function verifyRazorpayPayment(Request $request)
	{
		try {
			// Validate request
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|exists:users,id',
				'razorpay_payment_id' => 'required',
				'razorpay_order_id' => 'required',
				'razorpay_signature' => 'required',
				'amount' => 'required|numeric|min:1'
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => 'error',
					'msg' => $validator->errors()->first()
				], 422);
			}

			// Initialize Razorpay API
			$api = new Api(
				config('setting.razorpay_key_id'),
				config('setting.razorpay_key_secret')
			);

			// Verify signature
			$attributes = [
				'razorpay_order_id' => $request->razorpay_order_id,
				'razorpay_payment_id' => $request->razorpay_payment_id,
				'razorpay_signature' => $request->razorpay_signature
			];

			try {
				$api->utility->verifyPaymentSignature($attributes);
			} catch (\Exception $e) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Payment signature verification failed'
				], 400);
			}

			// Find user wallet record
			$userWallet = UserWallet::where('order_id', $request->razorpay_order_id)
				->where('user_id', $request->user_id)
				->firstOrFail();

			// Check if already processed
			if ($userWallet->transaction_status === 'Paid') {
				return response()->json([
					'status' => 'error',
					'msg' => 'Payment already processed'
				], 400);
			}

			// Begin transaction
			DB::beginTransaction();

			try {
				$user = User::findOrFail($request->user_id);

				// Update user wallet balance
				$user->increment('wallet_amount', $userWallet->amount);

				// Create billing entry
				Billing::create([
					'user_id' => $user->id,
					'billing_type' => 'Recharge Wallet',
					'billing_type_id' => $userWallet->id,
					'transaction_type' => 'credit',
					'amount' => $userWallet->amount,
					'note' => "Payment received through Razorpay. Payment ID: {$request->razorpay_payment_id}"
				]);

				// Update user wallet record
				$userWallet->update([
					'transaction_status' => 'Paid',
					'txn_number' => $request->razorpay_payment_id,
					'utr_no' => $request->razorpay_payment_id,
					'payable_response' => json_encode($attributes)
				]);

				DB::commit();

				return response()->json([
					'status' => 'success',
					'msg' => 'Payment successful! ₹' . number_format($userWallet->amount, 2) . ' added to wallet.',
					'data' => [
						'transaction_id' => $request->razorpay_payment_id,
						'amount' => $userWallet->amount,
						'new_wallet_balance' => $user->fresh()->wallet_amount
					]
				]);
			} catch (\Exception $e) {
				DB::rollBack();
				throw $e;
			}
		} catch (\Exception $e) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Payment verification failed: ' . $e->getMessage()
			], 500);
		}
	}

	public function paymentFailed(Request $request)
	{
		try {
			// Find and update user wallet record
			if ($request->filled('order_id')) {
				$userWallet = UserWallet::where('order_id', $request->order_id)
					->where('user_id', $request->user_id)
					->first();

				if ($userWallet) {
					$userWallet->update([
						'transaction_status' => 'Failed',
						'payable_response' => json_encode([
							'payment_id' => $request->payment_id,
							'error' => $request->reason,
							'failed_at' => now()
						])
					]);
				}
			}

			return response()->json([
				'status' => 'success',
				'msg' => 'Payment failure logged'
			]);
		} catch (\Exception $e) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to log payment failure: ' . $e->getMessage()
			], 500);
		}
	}
    /*
    public function createZohoPaymentLink(Request $request)
    {
        try {
            $minRechargeAmount = (float) config('setting.recharge_min_amount', 200);
            $maxRechargeAmount = (float) config('setting.recharge_max_amount', 10000);
            if ($maxRechargeAmount < $minRechargeAmount) {
                $maxRechargeAmount = $minRechargeAmount;
            }

            $validator = Validator::make($request->all(), [
                'amount' => "required|numeric|min:{$minRechargeAmount}|max:{$maxRechargeAmount}",
            ], [
                'amount.min' => "Minimum recharge amount is ₹{$minRechargeAmount}",
                'amount.max' => "Maximum recharge amount is ₹{$maxRechargeAmount}",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'msg' => $validator->errors()->first()
                ], 422);
            }

            $user = Auth::user();
            $amount = $request->amount;

            Log::info("Zoho Payment Link Request: User: {$user->id}, Amount: {$amount}");

            // Check status from config
            if (config('setting.zoho_status') != 1) {
                return response()->json(['status' => 'error', 'msg' => 'Zoho Payment Gateway is disabled.']);
            }

            if (!config('setting.zoho_access_token') && !config('setting.zoho_refresh_token')) {
                 return response()->json(['status' => 'error', 'msg' => 'Zoho Payment Gateway is not configured properly. (Missing Tokens)']);
            }

            $accessToken = $this->getZohoAccessToken();
            if (!$accessToken) {
                Log::error("Zoho Access Token Retreival Failed for User: {$user->id}");
                return response()->json(['status' => 'error', 'msg' => 'Unable to generate Zoho Access Token. Please re-authorize in settings.']);
            }
            Log::info("Zoho Access Token Retrievel Successful");

            $customerId = $this->getZohoCustomerId($user, $accessToken);
            if (!$customerId) {
                 return response()->json(['status' => 'error', 'msg' => 'Unable to create/fetch Zoho Customer.']);
            }

            $accountId = config('setting.zoho_account_id');
            $internalRefId = 'W_' . time() . '_' . $user->id;

            // Use the configured Redirect URL from settings (which supports Ngrok/NDR) if available
            $redirectUrl = config('setting.zoho_redirect_url');
            if (!empty($redirectUrl)) {
                // Ensure we append the order_id as required by the callback
                $returnUrl = $redirectUrl . '?order_id=' . $internalRefId;
            } else {
                $returnUrl = route('zoho.payment.callback', ['order_id' => $internalRefId]);
            }

            $payload = [
                'customer_id' => $customerId,
                'amount' => (float)$amount, // Ensure float
                'currency' => 'INR',
                'description' => 'Wallet Recharge for ' . $user->email,
                'reference_id' => $internalRefId,
                'notify_customer' => true,
                'return_url' => $returnUrl,
            ];

            Log::info("Zoho Payment Link Request Payload: " . json_encode($payload));

            $response = Http::asJson()->withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken
            ])->post("https://payments.zoho.in/api/v1/paymentlinks?account_id={$accountId}", [
                'amount' => (float)$amount,
                'currency' => 'INR',
                'description' => 'Wallet Recharge for ' . $user->email,
                'reference_id' => $internalRefId,
                'email' => $user->email, // Add email directly as per docs
                'notify_customer' => [
                    'email' => true,
                ],
                'return_url' => $returnUrl,
            ]);

            if ($response->successful()) {
                $linkData = $response->json()['payment_links'];

                // Create Wallet Transaction (Pending)
                $userWallet = UserWallet::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'amount_type' => 'credit',
                    'transaction_type' => 'Zoho',
                    'pg_name' => 'Zoho',
                    'order_id' => $linkData['payment_link_id'], // Zoho Link ID
                    'txn_number' => $internalRefId, // Internal Ref ID
                    'transaction_status' => 'Pending',
                    'status' => 0, // Unpaid
                    'payable_response' => json_encode($linkData)
                ]);

                return response()->json([
                    'status' => 'success',
                    'payment_url' => $linkData['url']
                ]);
            }

            return response()->json(['status' => 'error', 'msg' => 'Zoho API Error: ' . $response->body()]);
            Log::error("Zoho Payment Link Generation Failed: " . $response->body());

        } catch (\Exception $e) {
             Log::error("Zoho Payment Link Exception: " . $e->getMessage());
             return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    public function zohoPaymentCallback(Request $request)
    {
        $referenceId = $request->order_id;
        Log::info("Zoho Payment Callback Received: " . json_encode($request->all()));

        if (!$referenceId) {
             return redirect()->route('recharge.wallet')->with('error', 'Invalid Callback Data.');
        }

        $userWallet = UserWallet::where('txn_number', $referenceId)->first();
        if (!$userWallet) {
             return redirect()->route('recharge.wallet')->with('error', 'Transaction not found.');
        }

        if ($userWallet->transaction_status == 'Paid') {
             return redirect()->route('recharge.thankyou', ['transaction_id' => $userWallet->txn_number])->with('success', 'Payment already processed.');
        }

        // Check Status from Zoho
        $accessToken = $this->getZohoAccessToken();
        $accountId = config('setting.zoho_account_id');

        // Fetch Payment Link Details to see if it's paid
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken
        ])->get("https://payments.zoho.in/api/v1/paymentlinks/{$userWallet->order_id}?account_id={$accountId}");

        if ($response->successful()) {
            $data = $response->json()['payment_links']; // Docs say 'payment_links' wrapper
            if ($data['status'] == 'paid') {
                 // Success
                 Log::info("Zoho Payment Verified for ID: {$userWallet->order_id}");
                 DB::transaction(function () use ($userWallet, $data) {
                    $userWallet->update([
                        'transaction_status' => 'Paid',
                        'payable_response' => json_encode($data)
                    ]);

                    // Credit Wallet
                    $user = User::find($userWallet->user_id);
                    $user->increment('wallet_amount', $userWallet->amount);

                    // Create Billing
                    Billing::create([
                        'user_id' => $user->id,
                        'billing_type' => 'Recharge Wallet',
                        'billing_type_id' => $userWallet->id,
                        'transaction_type' => 'credit',
                        'amount' => $userWallet->amount,
                        'note' => "Payment received via Zoho. Ref: " . $userWallet->txn_number
                    ]);
                 });

                 return redirect()->route('recharge.thankyou', ['transaction_id' => $userWallet->txn_number])->with('success', 'Payment successful.');
            }
        }

        return redirect()->route('recharge.wallet')->with('error', 'Payment not verified or failed.');
    }

    public function zohoWebhook(Request $request)
    {
        Log::info('Zoho Component Webhook Triggered');

        try {
            // 1. Get Payload with Fallback
            $payload = $request->all();

            if (empty($payload)) {
                 $rawContent = $request->getContent();
                 $payload = json_decode($rawContent, true);
                 Log::info('Zoho Webhook: Parsed raw content', ['valid_json' => !empty($payload)]);
            }

            Log::info('Zoho Component Webhook Payload:', $payload ?? []);

            if (empty($payload)) {
                Log::warning('Zoho Webhook: Empty Payload');
                return response()->json(['status' => 'ignored', 'message' => 'Empty payload'], 200);
            }

            // 2. Determine Event Type
            $eventType = $payload['event_type'] ?? null;

            if (!$eventType) {
                 Log::warning('Zoho Webhook: Missing event_type');
                 return response()->json(['status' => 'ignored', 'message' => 'Missing event_type'], 200);
            }

            // 3. Handle 'payment.success', 'payment_link.paid', or 'payment.succeeded'
            if (in_array($eventType, ['payment.success', 'payment_link.paid', 'payment.succeeded'])) {

                $data = $payload['data'] ?? ($payload['event_object'] ?? []);

                if (!is_array($data)) {
                    Log::error('Zoho Webhook: Data is not an array', ['data' => $data]);
                    return response()->json(['status' => 'error', 'message' => 'Invalid data format'], 200);
                }

                // Navigate to payment link data (sometimes it's nested)
                $paymentLinkData = $data['payment_link'] ?? ($data['payment_links'] ?? null);

                // If not found directly, use data itself if it has identifiers
                if (!$paymentLinkData) {
                    $paymentLinkData = $data;
                }

                $referenceId = $paymentLinkData['reference_id'] ?? ($paymentLinkData['reference_number'] ?? null);
                $paymentLinkId = $paymentLinkData['payment_link_id'] ?? ($paymentLinkData['payment_id'] ?? null);

                if (!$referenceId && !$paymentLinkId) {
                    Log::error('Zoho Webhook: Missing identifiers (reference_id/number or payment_link_id/id)');
                    return response()->json(['status' => 'error', 'message' => 'Missing identifiers'], 200);
                }

                Log::info("Zoho Webhook: Processing Payment for Ref ID: {$referenceId}, Link ID: {$paymentLinkId}");

                // 4. Find Transaction
                $userWallet = null;

                // Try finding by internal reference ID (txn_number)
                if ($referenceId) {
                    $userWallet = UserWallet::where('txn_number', trim($referenceId))->first();
                }

                // Try finding by Zoho Link ID (order_id)
                if (!$userWallet && $paymentLinkId) {
                    $userWallet = UserWallet::where('order_id', trim($paymentLinkId))->first();
                }

                if (!$userWallet) {
                    Log::error("Zoho Webhook: Transaction found neither by Ref ID {$referenceId} nor Link ID {$paymentLinkId}");
                    return response()->json(['status' => 'ignored', 'message' => 'Transaction not found'], 200);
                }

                // 5. Check if already Paid
                if (strtolower($userWallet->transaction_status) === 'paid') {
                    Log::info("Zoho Webhook: Transaction {$referenceId} ALREADY marked as paid.");
                    return response()->json(['status' => 'processed', 'message' => 'Already paid'], 200);
                }

                // 6. Mark as Paid
                DB::beginTransaction();
                try {
                    // Extract real Payment ID/UTR if available
                    $gatewayTxnId = null;
                    if (!empty($paymentLinkData['payments']) && is_array($paymentLinkData['payments'])) {
                        // Get the latest payment attempt
                        $lastPayment = end($paymentLinkData['payments']);
                        $gatewayTxnId = $lastPayment['payment_id'] ?? null;
                    }

                    // Update Wallet Transaction
                    $userWallet->transaction_status = 'Paid';
                    $userWallet->payable_response = json_encode($payload);
                    if ($referenceId) {
                        $userWallet->txn_number = trim($referenceId);
                    }
                    if ($gatewayTxnId) {
                        $userWallet->utr_no = $gatewayTxnId;
                    }
                    $userWallet->save();

                    // Credit User Wallet
                    $user = User::find($userWallet->user_id);
                    if ($user) {
                         $user->increment('wallet_amount', $userWallet->amount);

                         // Create Billing
                         Billing::create([
                            'user_id' => $user->id,
                            'billing_type' => 'Recharge Wallet',
                            'billing_type_id' => $userWallet->id,
                            'transaction_type' => 'credit',
                            'amount' => $userWallet->amount,
                            'note' => "Payment received via Zoho Webhook. Ref: " . ($gatewayTxnId ?? $userWallet->txn_number)
                        ]);

                        Log::info("Zoho Webhook: Wallet Credited for User ID {$user->id}. Amount: {$userWallet->amount}");
                    } else {
                        Log::error("Zoho Webhook: User ID {$userWallet->user_id} not found, but wallet txn updated.");
                    }

                    DB::commit();
                    return response()->json(['status' => 'success', 'message' => 'Payment processed'], 200);

                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error("Zoho Webhook: Update Failed. Error: " . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
                }

            } else {
                Log::info("Zoho Webhook: Ignored event type: {$eventType}");
                return response()->json(['status' => 'ignored', 'message' => 'Event type ignored'], 200);
            }

        } catch (\Throwable $e) {
            Log::error("Zoho Webhook: Top-level Exception: " . $e->getMessage());
             return response()->json(['status' => 'error', 'message' => 'Exception occurred'], 500);
        }
    }

    private function getZohoAccessToken()
    {
        $accessToken = config('setting.zoho_access_token');
        $expiresAt = config('setting.zoho_token_expires_at');
        // Add buffer time (e.g., 5 minutes)
        if ($accessToken && $expiresAt && Carbon::parse($expiresAt)->gt(now()->addMinutes(5))) {
            Log::info("Zoho Access Token: Using Cached Token");
            return $accessToken;
        }

        // Refresh Token
        $refreshToken = config('setting.zoho_refresh_token');
        $clientId = config('setting.zoho_client_id');
        $clientSecret = config('setting.zoho_client_secret');

        if (!$refreshToken) {
            Log::error("Zoho Access Token: Missing Refresh Token");
            return null;
        }

        $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token'
        ]);

        if ($response->successful()) {
            Log::info("Zoho Access Token: Refreshed Successfully");
            $data = $response->json();
            $newAccessToken = $data['access_token'];
            $expiresIn = $data['expires_in_sec'] ?? 3600; // Use seconds, fallback to 1 hour

            // Update in DB
            $setting = Setting::where('name', 'zoho_access_token')->first();
            if ($setting) $setting->update(['value' => $newAccessToken]);
            else Setting::create(['name' => 'zoho_access_token', 'value' => $newAccessToken]);

            $settingTimestamp = Setting::where('name', 'zoho_token_expires_at')->first();
            $newExpiry = now()->addSeconds($expiresIn);
            if ($settingTimestamp) $settingTimestamp->update(['value' => $newExpiry]);
            else Setting::create(['name' => 'zoho_token_expires_at', 'value' => $newExpiry]);

            return $newAccessToken;
        }

        Log::error('Zoho Token Refresh Failed: ' . $response->body());
        return null;
    }

    private function getZohoCustomerId($user, $accessToken)
    {
        $accountId = config('setting.zoho_account_id');

        Log::info("Zoho: Fetching/Creating customer for email: {$user->email}");

        // Try to fetch existing customer by email first
        try {
            $searchResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken
            ])->get("https://payments.zoho.in/api/v1/customers?account_id={$accountId}&email={$user->email}");

            if ($searchResponse->successful()) {
                $searchData = $searchResponse->json();
                if (!empty($searchData['customers']) && count($searchData['customers']) > 0) {
                    $existingCustomerId = $searchData['customers'][0]['customer_id'];
                    Log::info("Zoho: Existing customer found: {$existingCustomerId}");
                    return $existingCustomerId;
                }
            } else {
                Log::warning("Zoho Customer Search Failed: " . $searchResponse->body());
            }
        } catch (\Exception $e) {
            Log::error("Zoho Customer Search Exception: " . $e->getMessage());
        }

		$customerData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone_country_code' => 'IN',
        ];

        if (!empty($user->mobile)) {
            $customerData['phone'] = $user->mobile;
        }

        $createResponse = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken
        ])->post("https://payments.zoho.in/api/v1/customers?account_id={$accountId}", $customerData);

        Log::info("Zoho Customer Create Response: " . $createResponse->body());

        if ($createResponse->successful()) {
            return $createResponse->json()['customer']['customer_id'];
        }

        Log::error('Zoho Contact Creation Failed: ' . $createResponse->body());
        return null;
    }
    */
}
