<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Billing;
use App\Models\UserKyc;
use App\Models\Notification;
use App\Models\UserWallet;
use File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use App\Models\RateCard;
use App\Models\ShippingCompany;
use App\Models\LabelPreference;
use App\Models\CodSetting;

class UserController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index(Request $request)
	{
		$user = $request->user();

		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$taskId = $request->query('task_id');
		$notifyId = $request->query('notify_id');

		if ($taskId && $notifyId) {
			Notification::where('id', $notifyId)->update(['read_at' => now()]);
		}

		$user_id = $taskId ?: null;
		$kycStatus = $request->query('kyc_status', 0);

		return view('users.index', compact('user_id', 'kycStatus'));
	}

	public function ajaxUser(Request $request)
	{
		error_reporting(0);
		$draw = $request->get('draw');
		$start = $request->get("start");
		$limit = $request->get("length"); // Rows display per page

		$columnIndex_arr = $request->get('order');
		$columnName_arr = $request->get('columns');
		$order_arr = $request->get('order');
		$search_arr = $request->get('search');
		$kycStatus = $request->get('kyc_status');

		$columnIndex = $columnIndex_arr[0]['column']; // Column index
		$orderData = $columnName_arr[$columnIndex]['data']; // Column name
		$dir = $order_arr[0]['dir']; // asc or desc

		// Map columns to database fields
		$sortMapping = [
			'id' => 'users.id',
			'seller_id' => 'users.seller_id',
			'company_name' => 'users.company_name',
			'name' => 'users.name',
			'mobile' => 'users.mobile',
			'email' => 'users.email',
			'wallet_amount' => 'users.wallet_amount',
			'kyc_status' => 'users.kyc_status',
			'status' => 'users.status',
			'staff_member' => 'staff.name',
			'created_at' => 'users.created_at',
			'action' => 'users.id'
		];

		$order = $sortMapping[$orderData] ?? 'users.id';

		$user = Auth::user();
		$role = $user->role;
		$id = $user->id;

		// Base query with join for sorting
		$query = User::select('users.*')
			->leftJoin('users as staff', 'users.staff_id', '=', 'staff.id')
			->where('users.role', 'user')
			->where('users.kyc_status', $kycStatus);

		if (in_array($role, ['admin'], true)) {
			$query->where('users.staff_id', $id);
		}

		// Count total records for pagination
		$totalData = $query->count();

		// Apply search filter if present
		if ($search = $request->input('search')) {
			$query->where(function ($q) use ($search) {
				$q->where('users.company_name', 'LIKE', "%{$search}%")
					->orWhere('users.name', 'LIKE', "%{$search}%")
					->orWhere('users.mobile', 'LIKE', "%{$search}%")
					->orWhere('users.email', 'LIKE', "%{$search}%")
					->orWhere('users.wallet_amount', 'LIKE', "%{$search}%")
					->orWhere('staff.name', 'LIKE', "%{$search}%")
					->orWhere('users.created_at', 'LIKE', "%{$search}%");
			});
		}

		// Get filtered count for pagination
		$totalFiltered = $query->count();

		// Get the final paginated list of users
		$values = $query->offset($start)
			->limit($limit)
			->orderBy($order, $dir)
			->get();

		// Prepare response data
		$data = [];
		foreach ($values as $index => $value) {

			$mainData = [
				'id' => $start + $index + 1,
				'seller_id' => $value->seller_id,
				'company_name' => $value->company_name,
				'name' => $value->name,
				'mobile' => $value->mobile,
				'email' => $value->email,
				'wallet_amount' => config('setting.currency') . $value->wallet_amount,
				'kyc_status' => $value->kyc_status == 1 ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-danger">Pending</span>',
				'status' => $value->status == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In-Active</span>',
				'staff_member' => $value->createdBy->name ?? 'N/A',
				'created_at' => $value->created_at->format('d M Y'),
				'action' => $this->generateUserActions($value, $role, $id, $kycStatus)
			];

			$data[] = $mainData;
		}

		// Return the response in the correct format
		$response = [
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
		];

		return response()->json($response);
	}

	private function generateUserActions($value, $role, $id, $kycStatus)
	{
		$actionButtons = '';

		// ðŸ”¹ Manual Credit & Debit â€” show only if KYC is approved
		if (config('permission.clients.add') && $kycStatus == 1) {
			// Credit / Recharge
			$actionButtons .= '
				<a href="javascript:;"
				class="btn btn-icon waves-effect waves-light action-icon text-success mr-1"
				data-toggle="tooltip"
				title="Credit Manually (Add Balance)"
				data-id="' . $value->id . '"
				data-amount="' . $value->wallet_amount . '"
				onclick="creditUser(this, event)">
					<i class="mdi mdi-plus-circle-outline"></i>
				</a>';

			// Debit
			$actionButtons .= '
				<a href="javascript:;"
				class="btn btn-icon waves-effect waves-light action-icon text-danger mr-1"
				data-toggle="tooltip"
				title="Debit Manually (Reduce Balance)"
				data-id="' . $value->id . '"
				data-amount="' . $value->wallet_amount . '"
				onclick="debitUser(this, event)">
					<i class="mdi mdi-minus-circle-outline"></i>
				</a>';
		}

		// ðŸ”¹ Edit user
		if (config('permission.clients.edit')) {
			$actionButtons .= '
				<a href="' . url('users/edit', $value->id) . '?kyc_status=' . $value->kyc_status . '"
				class="btn btn-icon waves-effect waves-light action-icon mr-1"
				data-toggle="tooltip"
				title="Update User">
					<i class="mdi mdi-pencil"></i>
				</a>';
		}

		// ðŸ”¹ Custom Courier Commission

		// ðŸ”¹ Delete user
		if (config('permission.clients.delete')) {
			$actionButtons .= '
				<a href="' . url('users/delete', $value->id) . '?kyc_status=' . (int) $kycStatus . '"
				class="btn btn-icon waves-effect waves-light action-icon text-danger mr-1"
				data-toggle="tooltip"
				title="Delete User"
				onclick="deleteRecord(this, event)">
					<i class="mdi mdi-trash-can-outline"></i>
				</a>';
		}


		return $actionButtons;
	}

	// Active Shadowfax accounts a seller can be assigned to
	private function courierAccountsForForm()
	{
		return ShippingCompany::where('provider', ShippingCompany::PROVIDER_SHADOWFAX)->where('status', 1)->orderBy('id')->get(['id', 'name', 'is_default']);
	}

	// Empty or unknown means "use the default account"
	private function validCourierAccountId($id)
	{
		return $id && ShippingCompany::whereKey($id)->where('provider', ShippingCompany::PROVIDER_SHADOWFAX)->exists() ? (int) $id : null;
	}

	public function createUser()
	{
		$authUser = Auth::user();
		$canManageCharges = $authUser->isMasterAdmin();
		$isFranchiseCreator = $authUser->role === 'franchise';
		$b2cRateCards = $this->getClientFormRateCards($authUser, 'b2c');
		$b2bRateCards = $this->getClientFormRateCards($authUser, 'b2b');

		$courierAccounts = $this->courierAccountsForForm();

		return view('users.create', compact('b2cRateCards', 'b2bRateCards', 'canManageCharges', 'isFranchiseCreator', 'courierAccounts'));
	}

	public function storeUser(Request $request)
	{
		$authUser = Auth::user();
		$canManageCharges = $authUser->isMasterAdmin();
		$staffId = Auth::id();
		$data = $request->except('_token', 'password');
		// Franchise cannot assign rate cards to its sellers.
		// Sellers created under a franchise will inherit the franchise's assigned rate card automatically.

			$data['b2c_rate_card_id'] = (int) $request->input('b2c_rate_card_id', 0);
			$data['b2b_rate_card_id'] = (int) $request->input('b2b_rate_card_id', 0);
			$data['rate_card_id'] = $data['b2c_rate_card_id'];
		$data['assigned_shipping_company_id'] = $this->validCourierAccountId($request->input('assigned_shipping_company_id'));

		$data['password'] = Hash::make($request->password);
		$data['xpass'] = $request->password; // stored encrypted (User casts xpass)
		$data['api_token'] = $this->generateUniqueToken();
		$data['role'] = 'user';
		$data['staff_id'] = $staffId;

		if ($canManageCharges) {
			$data['b2c_charge_type'] = (int) $request->input('b2c_charge_type', 1);
			$data['b2c_charge'] = (float) $request->input('b2c_charge', 0);
			$data['b2b_charge_type'] = (int) $request->input('b2b_charge_type', 1);
			$data['b2b_charge'] = (float) $request->input('b2b_charge', 0);
			$data['charge_type'] = $data['b2c_charge_type'];
			$data['charge'] = $data['b2c_charge'];
		} else {
			unset($data['charge_type'], $data['charge'], $data['b2c_charge_type'], $data['b2c_charge'], $data['b2b_charge_type'], $data['b2b_charge']);
		}

		// Convert payout_days array to comma-separated string
		if (isset($data['payout_days']) && is_array($data['payout_days'])) {
			$data['payout_days'] = implode(',', $data['payout_days']);
		} elseif (empty($data['payout_days'])) {
			$data['payout_days'] = null;
		}

		// Start database transaction
		DB::beginTransaction();

		try {
			// Check if mobile or email already exists
			// if (User::whereMobile($request->mobile)->exists()) {
			// 	return response()->json(['status' => 'error', 'msg' => 'The mobile number already exists.']);
			// }

			if (User::whereEmail($request->email)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The email number already exists.']);
			}

			// Calculate next seller_id
			$maxSellerId = User::max('seller_id');
			$data['seller_id'] = $maxSellerId ? $maxSellerId + 1 : 1001;

			if (empty($data['cod_plan_id'])) {
				$defaultCodPlan = CodSetting::where('is_active', true)
					->where('is_default', true)
					->first();

				$data['cod_plan_id'] = $defaultCodPlan ? $defaultCodPlan->id : null;
				$data['cod_plan_active'] = $defaultCodPlan ? 1 : 0;
			}

			// Insert user data and get the inserted ID
			$user = User::create($data);
			$this->ensureUserKycRecord($user);

			// Create default label preferences
			LabelPreference::create([
				'user_id' => $user->id,
				'created_at' => now(),
				'updated_at' => now()
			]);

			// Commit transaction
			DB::commit();

			return response()->json(['status' => 'success', 'msg' => 'The user has been successfully added.', 'kyc_status' => $user->kyc_status]);
		} catch (\Exception $e) {
			// Rollback transaction if anything fails
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
		}
	}

	public function editUser($userId)
	{
		$user = User::find($userId);

		$authUser = Auth::user();
		$canManageCharges = $authUser->isMasterAdmin();
		$isFranchiseCreator = $authUser->role === 'franchise';
		$b2cRateCards = $this->getClientFormRateCards($authUser, 'b2c');
		$b2bRateCards = $this->getClientFormRateCards($authUser, 'b2b');

		$courierAccounts = $this->courierAccountsForForm();

		return view('users.edit', compact('user', 'b2cRateCards', 'b2bRateCards', 'canManageCharges', 'isFranchiseCreator', 'courierAccounts'));
	}

	public function updateUser(Request $request, $userId)
	{
		$authUser = Auth::user();
		$canManageCharges = $authUser->isMasterAdmin();
		$data = $request->except('_token', 'password');
		// Wallet changes go through recharge/offline; only the master admin may change a role
		unset($data['wallet_amount'], $data['api_token'], $data['xpass']);
		if (!$authUser->isMasterAdmin()) {
			unset($data['role']);
		}
		// Franchise cannot assign rate cards to its sellers.
		// Keep seller's rate card unset so it inherits from the parent franchise.

			$data['b2c_rate_card_id'] = (int) $request->input('b2c_rate_card_id', 0);
			$data['b2b_rate_card_id'] = (int) $request->input('b2b_rate_card_id', 0);
			$data['rate_card_id'] = $data['b2c_rate_card_id'];
		$data['assigned_shipping_company_id'] = $this->validCourierAccountId($request->input('assigned_shipping_company_id'));

		if ($canManageCharges) {
			$data['b2c_charge_type'] = (int) $request->input('b2c_charge_type', 1);
			$data['b2c_charge'] = (float) $request->input('b2c_charge', 0);
			$data['b2b_charge_type'] = (int) $request->input('b2b_charge_type', 1);
			$data['b2b_charge'] = (float) $request->input('b2b_charge', 0);
			$data['charge_type'] = $data['b2c_charge_type'];
			$data['charge'] = $data['b2c_charge'];
		} else {
			unset($data['charge_type'], $data['charge'], $data['b2c_charge_type'], $data['b2c_charge'], $data['b2b_charge_type'], $data['b2b_charge']);
		}

		// If a password is provided, hash it and store it
		if (!empty($request->password)) {
			$data['password'] = Hash::make($request->password);
			$data['xpass'] = $request->password; // stored encrypted (User casts xpass)
		}
		$data['updated_at'] = now();

		// Convert payout_days array to comma-separated string
		if (isset($data['payout_days']) && is_array($data['payout_days'])) {
			$data['payout_days'] = implode(',', $data['payout_days']);
		} elseif (empty($data['payout_days'])) {
			$data['payout_days'] = null;
		}

		// Start database transaction
		DB::beginTransaction();

		try {
			// Check if mobile or email already exists for other users
			// if (User::where('id', '!=', $userId)->whereMobile($request->mobile)->exists()) {
			// 	return response()->json(['status' => 'error', 'msg' => 'The mobile number already exists.']);
			// }

			if (User::where('id', '!=', $userId)->whereEmail($request->email)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The email number already exists.']);
			}

			// Update user data
			$user = User::findOrFail($userId);
			if (!$this->canManageAccount($authUser, $user)) {
				DB::rollBack();
				return response()->json(['status' => 'error', 'msg' => 'You are not allowed to update this user.'], 403);
			}
			$user->update($data);
			$this->ensureUserKycRecord($user);

			// Commit transaction
			DB::commit();

			return response()->json(['status' => 'success', 'msg' => 'The user has been updated successfully.', 'kyc_status' => $user->kyc_status]);
		} catch (\Exception $e) {
			// Rollback transaction if anything fails
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
		}
	}

	// Only the master admin may change admin/master_admin accounts
	private function canManageAccount($authUser, User $target): bool
	{
		return $authUser->isMasterAdmin() || !in_array($target->role, ['admin', 'master_admin'], true);
	}

	public function deleteUser(Request $request, $userId)
	{
		$target = User::findOrFail($userId);
		if (!$this->canManageAccount(Auth::user(), $target)) {
			abort(403);
		}

		DB::beginTransaction();
		try {
			$target->delete();
			DB::commit();

			// Prefer going back to the list page the action came from (approved/pending).
			// Fallback to users list with the current kyc_status if provided.
			$kycStatus = $request->query('kyc_status');
			if ($request->headers->has('referer')) {
				return redirect()->back()->with('success', 'The user has been successfully deleted.');
			}

			return redirect()
				->route('users', $kycStatus !== null ? ['kyc_status' => (int) $kycStatus] : [])
				->with('success', 'The user has been successfully deleted.');
		} catch (\Exception $e) {

			DB::rollBack();
			$kycStatus = $request->query('kyc_status');
			if ($request->headers->has('referer')) {
				return redirect()->back()->with('error', 'Something went wrong.');
			}

			return redirect()
				->route('users', $kycStatus !== null ? ['kyc_status' => (int) $kycStatus] : [])
				->with('error', 'Something went wrong.');
		}
	}

	public function rechargeOffline(Request $request)
	{

		$validated = $request->validate([
			'user_id' => ['required', 'integer', 'exists:users,id'],
			'amount' => ['required', 'numeric', 'min:1'],
			'transaction_type' => ['required', 'in:credit,debit'],
			'passkey' => "required"
		]);

		$user = Auth::user();
		// Manual wallet credit/debit is an admin/staff action; sellers must never reach it
		if ($user->role === 'user') {
			abort(403);
		}

		// Hash::check returns true when plain text matches hashed password
		if (!Hash::check($request->input('passkey'), $user->password)) {
			return back()->with('error', 'Passkey does not match.');
		}

		$userId = (int) $validated['user_id'];
		$amount = (float) $validated['amount'];
		$type = $validated['transaction_type'];

		DB::beginTransaction();
		try {
			// Lock the user row for a consistent balance check/update
			$user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

			if ($type === 'debit') {
				// guard: no overdraft
				if (($user->wallet_amount ?? 0) < $amount) {
					DB::rollBack();
					return back()->with('error', 'Insufficient wallet balance for debit.');
				}
				$delta = -$amount;
				$billingType = 'Wallet Adjustment';
				$billingTxnType = 'debit';
				$billingNoteDefault = 'Manual wallet debit by admin.';
				$flashOk = 'The amount has been successfully debited.';
			} else {
				$delta = +$amount;
				$billingType = 'Recharge Wallet';
				$billingTxnType = 'credit';
				$billingNoteDefault = 'Manual wallet credit by admin.';
				$flashOk = 'The amount has been successfully credited.';
			}

			// Create wallet ledger row (recommended explicit fields, not $request->except)
			$walletRow = UserWallet::create([
				'user_id' => $userId,
				'amount' => $amount,
				'transaction_type' => 'Manual',               // credit|debit (make sure column exists)
				'amount_type' => $type,               // credit|debit (make sure column exists)
				'status' => 1,
				'transaction_status' => 'Paid',
				'note' => $request->note ?? null,
				'created_at' => now(),
				'updated_at' => now(),
			]);

			// Update user balance atomically
			$user->wallet_amount = ($user->wallet_amount ?? 0) + $delta;
			$user->save();

			// Billing log
			Billing::create([
				'user_id' => $userId,
				'billing_type' => $billingType,
				'billing_type_id' => $walletRow->id,
				'transaction_type' => $billingTxnType,       // credit|debit
				'amount' => $amount,
				'note' => $billingNoteDefault,
				'created_at' => now(),
				'updated_at' => now(),
			]);

			DB::commit();
			return back()->with('success', $flashOk);
		} catch (\Throwable $e) {
			DB::rollBack();
			return back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	// USER KYC
	public function kycUser()
	{
		error_reporting(0);
		$user_id = Auth::id();
		$userkyc = UserKyc::where('user_id', $user_id)->first();
		return view('users.kyc-user-update', compact('userkyc'));
	}

	public function kycUserPancardUpdate(Request $request)
	{
		$user = Auth::user();
		$userKyc = UserKyc::where('user_id', $user->id)->first();

		$request->validate([
			'pancard' => 'required|string',
			'pancard_image' => ($userKyc && $userKyc->pancard_image) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
		], [
			'pancard_image.required' => 'Please upload the Pancard image.',
			'pancard_image.max' => 'The Pancard image must not be larger than 5MB.',
			'pancard_image.mimes' => 'Only JPEG, PNG, JPG, and PDF files are allowed for Pancard.',
		]);

		try {
			$data = [
				'pancard' => strtoupper(trim($request->input('pancard'))),
				'pancard_status' => 0, // Pending - admin will review
				'pan_full_name' => null,
				'pancard_category' => null,
				'pancard_text' => null,
				'pan_role' => 'user',
				'pan_reason' => null,
			];

			if ($request->hasFile('pancard_image')) {
				$path = storage_path("app/public/pancard");
				if (!File::exists($path)) {
					File::makeDirectory($path, 0777, true, true);
				}
				$file = $request->file('pancard_image');
				$filename = "pan_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['pancard_image'] = $filename;
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return redirect()->back()->with('success', 'Your PAN details have been submitted successfully. Pending admin verification.');
		} catch (\Throwable $e) {
			return redirect()->back()->with('error', 'Something went wrong while submitting PAN: ' . $e->getMessage());
		}
	}

	public function kycUserAadharOtp(Request $request)
	{
		// ---- QUICK KYC AADHAR OTP - COMMENTED OUT (Manual KYC Mode) ----
		// This method is no longer used in manual KYC mode.
		// Aadhar is now submitted directly via kycUserAadharUpdate without OTP verification.
		return response()->json(['status' => 'error', 'msg' => 'OTP flow is disabled in manual KYC mode.']);
	}

	public function kycUserAadharUpdate(Request $request)
	{
		$user = Auth::user();
		$userKyc = UserKyc::where('user_id', $user->id)->first();

		$request->validate([
			'aadhar' => 'required|numeric|digits:12',
			'aadhar_front' => ($userKyc && $userKyc->aadhar_front) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
			'aadhar_back' => ($userKyc && $userKyc->aadhar_back) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
		], [
			'aadhar.digits' => 'The Aadhar number must be exactly 12 digits.',
			'aadhar_front.required' => 'Please upload the Aadhar Front image.',
			'aadhar_back.required' => 'Please upload the Aadhar Back image.',
			'aadhar_front.max' => 'The Aadhar Front image must not be larger than 5MB.',
			'aadhar_back.max' => 'The Aadhar Back image must not be larger than 5MB.',
		]);

		try {
			$data = [
				'aadhar' => trim($request->input('aadhar')),
				'aadhar_status' => 0, // Pending - admin will review
				'aadhar_full_name' => null,
				'aadhar_address' => null,
				'aadhar_dob' => null,
				'aadhar_gender' => null,
				'aadhar_zip' => null,
				'aadhar_text' => null,
				'aadhar_role' => 'user',
				'aadhar_reason' => null,
			];

			$path = storage_path("app/public/aadhar");
			if (!File::exists($path)) {
				File::makeDirectory($path, 0777, true, true);
			}

			if ($request->hasFile('aadhar_front')) {
				$file = $request->file('aadhar_front');
				$filename = "aadhar_front_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['aadhar_front'] = $filename;
			}

			if ($request->hasFile('aadhar_back')) {
				$file = $request->file('aadhar_back');
				$filename = "aadhar_back_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['aadhar_back'] = $filename;
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return redirect()->back()->with('success', 'Your Aadhar details have been submitted successfully. Pending admin verification.');
		} catch (\Throwable $e) {
			return redirect()->back()->with('error', 'Something went wrong while submitting Aadhar: ' . $e->getMessage());
		}
	}

	public function kycUserGSTINSendOtp(Request $request)
	{
		// ---- QUICK KYC GSTIN OTP - COMMENTED OUT (Manual KYC Mode) ----
		// This method is no longer used in manual KYC mode.
		// GSTIN is now submitted directly via kycUserGSTINUpdate without OTP verification.
		return response()->json(['status' => 'error', 'msg' => 'OTP flow is disabled in manual KYC mode.']);
	}

	public function kycUserGSTINUpdate(Request $request)
	{
		$user = Auth::user();
		$userKyc = UserKyc::where('user_id', $user->id)->first();

		$request->validate([
			'gst' => 'required|string|size:15',
			'gst_image' => ($userKyc && $userKyc->gst_image) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
		], [
			'gst_image.required' => 'Please upload the GST certificate/image.',
			'gst_image.max' => 'The GST image must not be larger than 5MB.',
		]);

		try {
			$data = [
				'gst' => strtoupper(trim($request->input('gst'))),
				'gst_status' => 0, // Pending - admin will review
				'gst_business_name' => null,
				'gst_address' => null,
				'gstin_status' => null,
				'gst_mobile_no' => null,
				'gst_email' => null,
				'gst_text' => null,
				'gst_role' => 'user',
				'gst_reason' => null,
			];

			if ($request->hasFile('gst_image')) {
				$path = storage_path("app/public/gst");
				if (!File::exists($path)) {
					File::makeDirectory($path, 0777, true, true);
				}
				$file = $request->file('gst_image');
				$filename = "gst_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['gst_image'] = $filename;
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return redirect()->back()->with('success', 'Your GSTIN details have been submitted successfully. Pending admin verification.');
		} catch (\Throwable $e) {
			return redirect()->back()->with('error', 'Something went wrong while submitting GSTIN: ' . $e->getMessage());
		}
	}

	public function kycUserBankPennyDropUpdate(Request $request)
	{
		$user = Auth::user();
		$userKyc = UserKyc::where('user_id', $user->id)->first();

		$request->validate([
			'bank_name' => 'required|string',
			'account_holder_name' => 'required|string',
			'account_number' => 'required|numeric',
			'ifsc_code' => 'required|string',
			'bank_passbook' => ($userKyc && $userKyc->bank_passbook) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
		], [
			'bank_passbook.required' => 'Please upload your Bank Passbook/Cheque leaf.',
			'bank_passbook.max' => 'The Bank Passbook must not be larger than 5MB.',
		]);
		$account = trim($request->input('account_number'));
		$ifsc = trim($request->input('ifsc_code'));

		try {
			$data = [
				'account_number' => $account,
				'ifsc_code' => strtoupper($ifsc),
				'bank_status' => 0, // Pending - admin will review
				'bank_name' => $request->input('bank_name'),
				'account_holder_name' => $request->input('account_holder_name'),
				'amount_deposited' => 0,
				'bank_role' => 'user',
				'bank_text' => null,
				'bank_reason' => null
			];

			if ($request->hasFile('bank_passbook')) {
				$path = storage_path("app/public/bank");
				if (!File::exists($path)) {
					File::makeDirectory($path, 0777, true, true);
				}
				$file = $request->file('bank_passbook');
				$filename = "bank_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['bank_passbook'] = $filename;
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return redirect()->back()->with('success', 'Bank details submitted successfully. Pending admin verification.');

		} catch (\Throwable $e) {
			\Log::error('Web User Bank Manual Submission Exception: ' . $e->getMessage());
			return redirect()->back()->with('error', 'Error submitting bank details: ' . $e->getMessage());
		}
	}

    public function kycUserUdyamUpdate(Request $request)
	{
		$user = Auth::user();
		$userKyc = UserKyc::where('user_id', $user->id)->first();

        $request->validate([
            'udyam_number' => 'required|string',
			'udyam_image' => ($userKyc && $userKyc->udyam_image) ? 'nullable|mimes:jpeg,png,jpg,pdf|max:5120' : 'required|mimes:jpeg,png,jpg,pdf|max:5120',
        ], [
			'udyam_image.required' => 'Please upload the Udyam Aadhaar image.',
			'udyam_image.max' => 'The Udyam image must not be larger than 5MB.',
		]);

		$udyamNumber = $request->input('udyam_number');

		try {
			$data = [
				'udyam_number' => $udyamNumber,
				'udyam_status' => 0, // Pending
                'udyam_enterprise_name' => null,
				'udyam_text' => null,
				'udyam_role' => 'user',
				'udyam_reason' => null
			];

            if ($request->hasFile('udyam_image')) {
                $path = storage_path("app/public/udyam");
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0777, true, true);
                }
                $file = $request->file('udyam_image');
                $filename = "udyam_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
                $file->move($path, $filename);
                $data['udyam_image'] = $filename;
            }

			// Update UserKyc with just the number and pending status
			$userKyc = UserKyc::updateOrCreate(
				['user_id' => $user->id],
				$data
			);

			return redirect()->back()->with('success', 'Udyam Aadhaar Submitted Successfully. Verification is pending.');

		} catch (\Throwable $e) {
			return redirect()->back()->with('error', 'Error submitting Udyam Aadhaar: ' . $e->getMessage());
		}
	}

	private function kycUserDocumentUpdate(Request $request, $imageFields, $prefix)
	{
		$user_id = Auth::id();
		$data = $request->except('_token');
		$path_avatar = storage_path("app/public/kyc/{$user_id}");

		try {
			if (!File::exists($path_avatar)) {
				File::makeDirectory($path_avatar, 0777, true, true);
			}

			$imageFields = (array) $imageFields;
			foreach ($imageFields as $field) {
				if ($request->hasFile($field)) {
					$photo_image = $request->file($field);
					$getAvatar = "{$prefix}_" . time() . rand(111111, 999999) . '.' . $photo_image->getClientOriginalExtension();
					$filename = "$path_avatar/$getAvatar";

					if ($photo_image->getSize() > 2 * 1024 * 1024) {
						$sourceImage = imagecreatefromjpeg($photo_image->getRealPath());
						if ($sourceImage) {
							imagejpeg($sourceImage, $filename, 75);
							imagedestroy($sourceImage);
						} else {
							throw new \Exception("Invalid image format. Only JPEG supported for compression.");
						}
					} else {
						$photo_image->move($path_avatar, $getAvatar);
					}
					$data[$field] = $getAvatar;
				}
			}

			DB::beginTransaction();

			if (UserKyc::where('user_id', $user_id)->exists()) {
				UserKyc::where('user_id', $user_id)->update($data);
			} else {
				$data['user_id'] = $user_id;
				UserKyc::create($data);
			}

			DB::commit();

			return redirect()->back()->with('success', 'The KYC request has been successfully sent to the admin.');
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
		}
	}

	// KYC ADMIN
	public function kycUserRequest()
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}
		return view('users.kyc-user-request');
	}

	public function kycUserRequestAjax(Request $request)
	{
		error_reporting(0);
		$draw = $request->get('draw');
		$start = $request->get('start');
		$limit = $request->get('length'); // Rows display per page

		$order = $request->get('order');
		$columnNameArr = $request->get('columns');
		$searchValue = $request->get('search'); // Global search value

		// Determine the order column
		$columnIndex = $order[0]['column']; // Column index
		$orderBy = $columnNameArr[$columnIndex]['data']; // Column name
		$dir = $order[0]['dir']; // asc or desc

		$authUser = Auth::user();
		$role = $authUser->role;
		$id = $authUser->id;

		// Build base query
		$query = UserKyc::join('users as u', 'u.id', '=', 'user_kycs.user_id')
			->select('user_kycs.*', 'u.name', 'u.email', 'u.staff_id', 'u.kyc_status');

		// Filter by role
		if (in_array($role, ['staff', 'admin'], true)) {
			$query->where('u.staff_id', $id);
		}

		// Apply search filter if exists
		if ($searchValue) {
			$query->where(function ($query) use ($searchValue) {
				$query->where('u.name', 'LIKE', "%$searchValue%")
					->orWhere('u.email', 'LIKE', "%$searchValue%")
					->orWhere('user_kycs.udyam_number', 'LIKE', "%$searchValue%")
					->orWhere('user_kycs.udyam_enterprise_name', 'LIKE', "%$searchValue%");
			});
		}

		// Apply KYC status filter if selected
		if ($request->filled('kyc_status') && $request->input('kyc_status') !== '') {
			$kycStatus = (string) $request->input('kyc_status');
			$query->where(function ($query) use ($kycStatus) {
				$query->where('user_kycs.pancard_status', $kycStatus)
					->orWhere('user_kycs.bank_status', $kycStatus)
					->orWhere('user_kycs.aadhar_status', $kycStatus)
					->orWhere('user_kycs.gst_status', $kycStatus)
					->orWhere('user_kycs.udyam_status', $kycStatus);
			});
		}

		// Count total filtered data
		$totalFiltered = $query->count();

		// Prepare column mapping for sorting
		$orderMapping = [
			'id' => 'user_kycs.id',
			'user_name' => 'u.name',
			'user_email' => 'u.email',
			'pan_number' => 'user_kycs.pancard',

			'aadhaar_holder_name' => 'user_kycs.aadhar_full_name',
			'aadhaar_address' => 'user_kycs.aadhar_address',
			'aadhaar_zip_code' => 'user_kycs.aadhar_zip',
			'aadhaar_dob' => 'user_kycs.aadhar_dob',
			'aadhaar_gender' => 'user_kycs.aadhar_gender',
			'udyam_number' => 'user_kycs.udyam_number',
			'udyam_enterprise_name' => 'user_kycs.udyam_enterprise_name',
			'gst_number' => 'user_kycs.gst',
			'gst_business_name' => 'user_kycs.gst_business_name',
			'gst_address' => 'user_kycs.gst_address',
			'bank_name' => 'user_kycs.bank_name',
			'account_holder_name' => 'user_kycs.account_holder_name',
			'account_number' => 'user_kycs.account_number',
			'ifsc_code' => 'user_kycs.ifsc_code',
			'pancard_status' => 'user_kycs.pancard_status',
			'aadhar_status' => 'user_kycs.aadhar_status',
			'gst_status' => 'user_kycs.gst_status',
			'udyam_status' => 'user_kycs.udyam_status',
			'bank_status' => 'user_kycs.bank_status',
			'kyc_status' => 'u.kyc_status',
			'created_at' => 'user_kycs.updated_at',
		];

		$dbOrderBy = $orderMapping[$orderBy] ?? 'user_kycs.id';

		// Get paginated values
		$values = $query->offset($start)
			->limit($limit)
			->orderBy($dbOrderBy, $dir)
			->get();

		// Prepare data for response
		$data = [];
		$i = $start + 1; // To display the correct row index
		foreach ($values as $value) {

			// dropdowns instead of badges
			$pancardStatusHtml = $this->getStatusSelectHtml($value->id, 'pancard_status', $value->pancard_status);
			$aadharStatusHtml = $this->getStatusSelectHtml($value->id, 'aadhar_status', $value->aadhar_status);
			$gstStatusHtml = $this->getStatusSelectHtml($value->id, 'gst_status', $value->gst_status);
			$udyamStatusHtml = $this->getStatusSelectHtml($value->id, 'udyam_status', $value->udyam_status);
			$bankStatusHtml = $this->getStatusSelectHtml($value->id, 'bank_status', $value->bank_status);
			$kycStatusHtml = $this->getKycStatusSelectHtml($value->user_id, $value);

			$generateDocumentHtml = function ($src) {
				if (str_contains(strtolower($src), '.pdf')) {
					return '<a href="' . $src . '" target="_blank"><iframe src="' . $src . '#toolbar=0&navpanes=0&scrollbar=0" style="width: 50px; height: 50px; border-radius: 6px; border: 1px solid #ccc; pointer-events: none;" scrolling="no"></iframe></a>';
				}
				return '<a href="' . $src . '" target="_blank"><img src="' . $src . '" width="50" height="50" style="object-fit:cover;border-radius:6px;"></a>';
			};

			$pancardImgSrc = $value->pancard_image ? (str_contains($value->pancard_image, 'storage/') ? asset($value->pancard_image) : asset('storage/pancard/' . $value->pancard_image)) : asset('images/placeholder.png');
			$pancardImageHtml = $generateDocumentHtml($pancardImgSrc);

			$aadharFrontSrc = $value->aadhar_front ? (str_contains($value->aadhar_front, 'storage/') ? asset($value->aadhar_front) : asset('storage/aadhar/' . $value->aadhar_front)) : asset('images/placeholder.png');
			$aadharFrontHtml = $generateDocumentHtml($aadharFrontSrc);

			$aadharBackSrc = $value->aadhar_back ? (str_contains($value->aadhar_back, 'storage/') ? asset($value->aadhar_back) : asset('storage/aadhar/' . $value->aadhar_back)) : asset('images/placeholder.png');
			$aadharBackHtml = $generateDocumentHtml($aadharBackSrc);

			$udyamImgSrc = $value->udyam_image ? (str_contains($value->udyam_image, 'storage/') ? asset($value->udyam_image) : asset('storage/udyam/' . $value->udyam_image)) : asset('images/placeholder.png');
			$udyamImageHtml = $generateDocumentHtml($udyamImgSrc);

			$gstImgSrc = $value->gst_image ? (str_contains($value->gst_image, 'storage/') ? asset($value->gst_image) : asset('storage/gst/' . $value->gst_image)) : asset('images/placeholder.png');
			$gstImageHtml = $generateDocumentHtml($gstImgSrc);

			$bankPassbookSrc = $value->bank_passbook ? (str_contains($value->bank_passbook, 'storage/') ? asset($value->bank_passbook) : asset('storage/bank/' . $value->bank_passbook)) : asset('images/placeholder.png');
			$bankPassbookHtml = $generateDocumentHtml($bankPassbookSrc);

			$actionHtml = $this->getActionButton($value->id, $role, $id);

			$mainData = [
				'id' => $i,
				'user_name' => e($value->name ?? ''),
				'user_email' => e($value->email ?? ''),
				'pan_number' => e($value->pancard ?? ''),
				'pancard_image' => $pancardImageHtml,
				'aadhar_number' => e($value->aadhar ?? ''),
				'aadhar_front' => $aadharFrontHtml,
				'aadhar_back' => $aadharBackHtml,
				'udyam_number' => e($value->udyam_number ?? ''),
				'udyam_image' => $udyamImageHtml,
				'gst_number' => e($value->gst ?? ''),
				'gst_image' => $gstImageHtml,
				'bank_passbook' => $bankPassbookHtml,
				'bank_name' => e($value->bank_name ?? ''),
				'account_holder_name' => e($value->account_holder_name ?? ''),
				'account_number' => e($value->account_number ?? ''),
				'ifsc_code' => e($value->ifsc_code ?? ''),
				'pancard_status' => $pancardStatusHtml,
				'aadhar_status' => $aadharStatusHtml,
				'gst_status' => $gstStatusHtml,
				'udyam_status' => $udyamStatusHtml,
				'bank_status' => $bankStatusHtml,
				'kyc_status' => $kycStatusHtml,
				'created_at' => $value->updated_at ? $value->updated_at->format('d M Y') : '',
				'action' => $actionHtml,
			];

			$data[] = $mainData;
			$i++;
		}

		// Return response as JSON
		$response = [
			'draw' => intval($draw),
			'iTotalRecords' => $totalFiltered,
			'iTotalDisplayRecords' => $totalFiltered,
			'aaData' => $data
		];

		return response()->json($response);
	}

	private function getKycStatusSelectHtml(int $userId, $userKyc): string
	{
		$options = [
			0 => 'In-Active',
			1 => 'Active',
		];
		$userKyc = $userKyc->load('user');
		$html  = '<select class="form-select form-select-sm user-kyc-status-select select2"';
		$html .= ' data-user-id="' . $userId . '" style="min-width:130px">';
		foreach ($options as $k => $label) {
			$sel = ($userKyc->user->kyc_status == $k) ? 'selected' : '';

			$html .= '<option value="' . $k . '" ' . $sel . '>' . $label . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	private function getStatusSelectHtml(int $kycId, string $field, ?string $value): string
	{
		$options = [
			0  => 'Pending',
			1 => 'Approved',
			2 => 'Rejected',
		];
		$html  = '<select class="form-select form-select-sm kyc-status-select"';
		$html .= ' data-id="' . $kycId . '" data-field="' . $field . '" style="min-width:130px">';
		foreach ($options as $k => $label) {
			$sel = ($value == $k) ? 'selected' : '';

			$html .= '<option value="' . $k . '" ' . $sel . '>' . $label . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	public function kycUserUpdateStatus(Request $request)
	{
		$data = $request->validate([
			'id' => ['required', 'integer', 'exists:user_kycs,id'],
			'field' => ['required', 'in:pancard_status,aadhar_status,gst_status,udyam_status,bank_status'],
			'status' => ['required', 'in:0,1,2'],
			'reason' => ['nullable', 'string', 'max:255'],
		]);

		$userKyc = $this->findVisibleUserKycOrFail((int) $data['id']);

		$field = $data['field'];
		$status = (int) $data['status'];
		$reason = $data['reason'] ?? null;

		// Map reason/role columns based on field
		$isPan = $field === 'pancard_status';
		$isAadhar = $field === 'aadhar_status';
		$isGst = $field === 'gst_status';
		$isUdyam = $field === 'udyam_status';
		$isBank = $field === 'bank_status';

		$reasonKey = $isPan ? 'pan_reason' : ($isAadhar ? 'aadhar_reason' : ($isGst ? 'gst_reason' : ($isUdyam ? 'udyam_reason' : 'bank_reason')));
		$roleKey = $isPan ? 'pan_role' : ($isAadhar ? 'aadhar_role' : ($isGst ? 'gst_role' : ($isUdyam ? 'udyam_role' : 'bank_role')));
		$singleRole = 'role';

		$updateData = [
			$field => $status,
			'updated_at' => now(),
			$reasonKey => ($status === 2 ? $reason : null),
		];

		if (Schema::hasColumn('user_kycs', $roleKey)) {
			$updateData[$roleKey] = $status == 1 ? 'user' : 'admin';
		} elseif (Schema::hasColumn('user_kycs', $singleRole)) {
			$updateData[$singleRole] = $status == 1 ? 'user' : 'admin';
		}


		$userKyc->update($updateData);

		// Your existing post-update hook
		$this->userVerifiedKyc($userKyc);

		$msg = ucfirst(str_replace('_', ' ', $field)) . ' updated';
		session()->flash('success', $msg);

		return response()->json([
			'message' => $msg,
		]);
	}

	public function updateUserKycStatus(Request $request)
	{
		$data = $request->validate([
			'user_id' => ['required', 'integer', 'exists:users,id'],
			'status' => ['required', 'in:0,1'],
		]);

		try {
			$user = $this->findVisibleKycUserOrFail((int) $data['user_id']);
			$user->update(['kyc_status' => (int) $data['status']]);

			return response()->json([
				'status' => 'success',
				'message' => 'User KYC status updated successfully',
			]);
		} catch (\Exception $e) {
			return response()->json([
				'status' => 'error',
				'message' => 'Failed to update KYC status: ' . $e->getMessage(),
			], 500);
		}
	}

	// Helper to get the status badge HTML
	private function getStatusBadge($status)
	{
		switch ($status) {
			case 1:
				return '<span class="badge badge-success">Approved</span>';
			case 2:
				return '<span class="badge badge-danger">Rejected</span>';
			default:
				return '<span class="badge badge-warning">Pending</span>';
		}
	}

	// Helper to generate the action button
	private function getActionButton($id, $role, $userId)
	{
		$buttonHtml = '';
		if (config('permission.client_kyc_request.edit')) {
			$buttonHtml .= '<a href="' . url('users/kyc/verified', $id) . '" class="btn btn-icon waves-effect waves-light action-icon mr-1"> <i class="mdi mdi-eye"></i> </a>';
		}
		return $buttonHtml;
	}

	public function kycUserRequestVerified($userKycId)
	{
		$userkyc = $this->findVisibleUserKycOrFail((int) $userKycId);
		return view('users.kyc-user-verify', compact('userkyc'));
	}

	public function kycUserVerified(Request $request)
	{
		$status = $request->status;
		$type = $request->type;
		$id = $request->id;

		try {
			// Start a transaction to ensure data consistency
			DB::beginTransaction();

			// Prepare the update data based on type
			$columnMap = [
				'pancard' => 'pancard_status',
				'aadhar' => 'aadhar_status',
				'gst' => 'gst_status',
				'bank' => 'bank_status'
			];

			// Check if the provided type exists in the map
			if (!isset($columnMap[$type])) {
				return response()->json(['status' => 'error', 'msg' => 'Invalid verification type.']);
			}

			$userKyc = $this->findVisibleUserKycOrFail((int) $id);
			$userKyc->update([$columnMap[$type] => $status]);

			$this->userVerifiedKyc($userKyc);

			// Commit the transaction after the update is successful
			DB::commit();

			// Return success message based on type
			$verificationMessages = [
				'pancard' => 'The pancard is verified successfully.',
				'aadhar' => 'The aadhar is verified successfully.',
				'gst' => 'The GST is verified successfully.',
				'bank' => 'The bank detail is verified successfully.'
			];

			session()->flash('success', $verificationMessages[$type]);
			return response()->json(['status' => 'success', 'msg' => $verificationMessages[$type]]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to verify KYC: ' . $e->getMessage()]);
		}
	}

	public function userVerifiedKyc($userKyc)
	{
		$userKyc->user->update(['kyc_status' => 0]);
		
		$isPancardOk = $userKyc->pancard_status == 1;
		$isAadharOk = $userKyc->aadhar_status == 1;
		$isGstOk = $userKyc->gst_status == 1;
		$isBankOk = $userKyc->bank_status == 1;
		
		// If udyam is provided, it must also be approved. If not provided, we ignore it.
		$isUdyamOk = empty($userKyc->udyam_number) || $userKyc->udyam_status == 1;

		if ($isPancardOk && $isAadharOk && $isGstOk && $isBankOk && $isUdyamOk) {
			$userKyc->user->update(['kyc_status' => 1]);
		}
	}

	private function generateUniqueToken(): string
	{
		do {
			$token = bin2hex(random_bytes(16));
		} while (User::where('api_token', $token)->exists());

		return $token;
	}

    public function kycUserRejected(Request $request)
	{
		$status = 2;
		$type = $request->type;
		$id = $request->id;
		$text = $request->text;

		try {
			// Start a transaction to ensure data consistency
			DB::beginTransaction();

			// Prepare the update data based on type
			$columnMap = [
				'pancard' => 'pancard_status',
				'aadhar' => 'aadhar_status',
				'gst' => 'gst_status',
				'bank' => 'bank_status'
			];

			// Reason Map
			$reasonMap = [
				'pancard' => 'pan_reason',
				'aadhar' => 'aadhar_reason',
				'gst' => 'gst_reason',
				'bank' => 'bank_reason'
			];

			// Check if the provided type exists in the map
			if (!isset($columnMap[$type])) {
				return response()->json(['status' => 'error', 'msg' => 'Invalid verification type.']);
			}

			$userKyc = $this->findVisibleUserKycOrFail((int) $id);

			// Update status and reason
			$updateData = [
				$columnMap[$type] => $status
			];

			if (isset($reasonMap[$type])) {
				$updateData[$reasonMap[$type]] = $text;
			}

			$userKyc->update($updateData);

			$this->userVerifiedKyc($userKyc);

			// Commit the transaction after the update is successful
			DB::commit();

			// Return success message based on type
			$verificationMessages = [
				'pancard' => 'The pancard is rejected successfully.',
				'aadhar' => 'The aadhar is rejected successfully.',
				'gst' => 'The GST is rejected successfully.',
				'bank' => 'The bank detail is rejected successfully.'
			];

			session()->flash('success', $verificationMessages[$type]);
			return response()->json(['status' => 'success', 'msg' => $verificationMessages[$type]]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to reject KYC: ' . $e->getMessage()]);
		}
	}

	private function visibleKycUsersQuery()
	{
		$authUser = Auth::user();
		$query = User::query()->where('role', 'user');

		if (in_array($authUser->role, ['staff', 'admin'], true)) {
			$query->where('staff_id', $authUser->id);
		}

		return $query;
	}

	private function ensureUserKycRecord(User $user): void
	{
		UserKyc::firstOrCreate(['user_id' => $user->id]);
	}

	private function getClientFormRateCards(User $authUser, string $rateType)
	{
		$query = RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', $rateType)
			->orderBy('group_name');

		// Franchise users manage their own client assignments and should see the full rate-card list.
		if ($authUser->isMasterAdmin()) {
			return $query->get();
		}

		$assignedRateCardId = $authUser->getAssignedRateCardIdByType($rateType);
		if (!in_array($assignedRateCardId, [0, '0', null, 'all'], true)) {
			$query->where('id', $assignedRateCardId);
		}

		return $query->get();
	}

	private function findVisibleKycUserOrFail(int $userId): User
	{
		return (clone $this->visibleKycUsersQuery())->findOrFail($userId);
	}

	private function findVisibleUserKycOrFail(int $userKycId): UserKyc
	{
		return UserKyc::whereIn('user_id', $this->visibleKycUsersQuery()->select('id'))
			->findOrFail($userKycId);
	}

	// =====================================================================
	// QUICK KYC API VERIFICATION METHODS - COMMENTED OUT (Manual KYC Mode)
	// Restore these methods when switching back to QuickKYC automatic mode.
	// =====================================================================

	/* ---- BEGIN QUICK KYC ADMIN API METHODS (COMMENTED OUT) ----
	// API Verification Methods (Direct Http Calls)
	public function verifyPanApi(Request $request)
	{
		$request->validate(['kyc_id' => 'required|exists:user_kycs,id']);
		$userKyc = UserKyc::find($request->kyc_id);

		if (!$userKyc->pancard) {
			return response()->json(['status' => 'error', 'msg' => 'PAN number not found for this user']);
		}

		\Log::info('QuickeKYC PAN Request for KYC ID: ' . $userKyc->id, ['pancard' => trim($userKyc->pancard)]);

		try {
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/pan/pan', [
					'key' => trim(config('services.quick_kyc.key')),
					'id_number' => trim($userKyc->pancard),
				]);

			$result = $response->json();
			\Log::info('QuickeKYC PAN Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
				$userKyc->update([
					'pancard_status' => 1,
					'pan_full_name' => $result['data']['full_name'] ?? null,
					'pancard_category' => $result['data']['category'] ?? null,
					'pancard_text' => $result,
					'pan_role' => 'admin'
				]);

				$this->userVerifiedKyc($userKyc);

				return response()->json([
					'status' => 'success',
					'msg' => 'PAN Verified Successfully via API',
					'data' => $result
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC PAN Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}

	public function verifyGstApi(Request $request)
	{
		$request->validate(['kyc_id' => 'required|exists:user_kycs,id']);
		$userKyc = UserKyc::find($request->kyc_id);

		if (!$userKyc->gst) {
			return response()->json(['status' => 'error', 'msg' => 'GST number not found']);
		}

		\Log::info('QuickeKYC GST Request for KYC ID: ' . $userKyc->id, ['gst' => trim($userKyc->gst)]);

		try {
			// Using generate-otp for GST in v2 usually, but some implementations might use direct verification if available.
			// Replicating the logic from the API controller: generate-otp
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/corporate/gst-verification-v2/generate-otp', [
					'key' => trim(config('services.quick_kyc.key')),
					'id_number' => trim($userKyc->gst),
					'send_on_email' => true,
					'send_on_mobile' => true,
				]);

			$result = $response->json();
			\Log::info('QuickeKYC GST Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
				// For admin verification, it might send OTP to user.
				return response()->json([
					'status' => 'otp_sent', // Keeping it generic if it behaves like Aadhar
					'msg' => 'OTP Sent to User. Please check and enter.',
					'request_id' => $result['request_id'] ?? null
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC GST Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}

	public function verifyBankPennyDropApi(Request $request)
	{
		$request->validate(['kyc_id' => 'required|exists:user_kycs,id']);
		$userKyc = UserKyc::find($request->kyc_id);

		if ($request->has('account_number')) {
			$userKyc->account_number = $request->account_number;
			$userKyc->ifsc_code = $request->ifsc_code;
			$userKyc->save();
		}

		if (!$userKyc->account_number || !$userKyc->ifsc_code) {
			return response()->json(['status' => 'error', 'msg' => 'Account Number and IFSC Code are required']);
		}

		$account = $request->input('account_number') ?? $userKyc->account_number;
		$ifsc = $request->input('ifsc_code') ?? $userKyc->ifsc_code;

		\Log::info('QuickeKYC Bank Penny Drop Request for KYC ID: ' . $userKyc->id, ['account' => trim($account), 'ifsc' => trim($ifsc)]);

		try {
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/bank-verification/pd', [
					'key' => trim(config('services.quick_kyc.key')),
					'id_number' => trim($account),
					'ifsc' => trim($ifsc),
				]);

			$result = $response->json();
			\Log::info('QuickeKYC Bank Penny Drop Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
				$bankData = $result['data'] ?? [];
				$userKyc->update([
					'bank_status' => 1,
					'account_holder_name' => $bankData['full_name'] ?? null,
					'amount_deposited' => $bankData['amount_deposited'] ?? 0,
					'bank_role' => 'admin',
					'bank_text' => $result
				]);

				$this->userVerifiedKyc($userKyc);

				return response()->json([
					'status' => 'success',
					'msg' => 'Bank Account Verified Successfully via Penny Drop',
					'data' => $result
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC Bank Penny Drop Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}

	public function verifyAadhaarApi(Request $request)
	{
		$request->validate(['kyc_id' => 'required|exists:user_kycs,id']);
		$userKyc = UserKyc::find($request->kyc_id);

		if (!$userKyc->aadhar) {
			return response()->json(['status' => 'error', 'msg' => 'Aadhaar number not found']);
		}

		\Log::info('QuickeKYC Aadhaar Request for KYC ID: ' . $userKyc->id, ['aadhar' => trim($userKyc->aadhar)]);

		try {
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/aadhaar-v2/generate-otp', [
					'key' => trim(config('services.quick_kyc.key')),
					'id_number' => trim($userKyc->aadhar),
				]);

			$result = $response->json();
			\Log::info('QuickeKYC Aadhaar Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
				return response()->json([
					'status' => 'otp_sent',
					'msg' => 'OTP Sent to User Mobile. Please enter OTP.',
					'client_id' => $result['request_id'] ?? $result['client_id'] ?? null
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC Aadhaar Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}

	public function verifyAadhaarOtpApi(Request $request)
	{
		$request->validate([
			'kyc_id' => 'required|exists:user_kycs,id',
			'client_id' => 'required',
			'otp' => 'required'
		]);

		$userKyc = UserKyc::find($request->kyc_id);

		\Log::info('QuickeKYC Aadhaar OTP Submit for KYC ID: ' . $userKyc->id, ['request_id' => trim($request->client_id)]);

		try {
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/aadhaar-v2/submit-otp', [
					'key' => trim(config('services.quick_kyc.key')),
					'request_id' => trim($request->client_id),
					'otp' => trim($request->otp),
				]);

			$result = $response->json();
			\Log::info('QuickeKYC Aadhaar OTP Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
				$userKyc->update([
					'aadhar_status' => 1,
					'aadhar_full_name' => $result['data']['full_name'] ?? null,
					'aadhar_address' => json_encode($result['data']['address'] ?? []),
					'aadhar_text' => $result,
					'aadhar_role' => 'admin'
				]);

				$this->userVerifiedKyc($userKyc);

				return response()->json([
					'status' => 'success',
					'msg' => 'Aadhaar Verified Successfully',
					'data' => $result
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'OTP Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC Aadhaar OTP Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}

    public function verifyUdyamApi(Request $request)
	{
		$request->validate(['kyc_id' => 'required|exists:user_kycs,id']);
		$userKyc = UserKyc::find($request->kyc_id);

		if (!$userKyc->udyam_number) {
			return response()->json(['status' => 'error', 'msg' => 'Udyam number not found']);
		}

		\Log::info('QuickeKYC Udyam Request for KYC ID: ' . $userKyc->id, ['udyam_number' => trim($userKyc->udyam_number)]);

		try {
			$response = Http::withOptions(['verify' => false])
				->withHeaders([
					'Content-Type' => 'application/json',
				])
				->timeout(60)
				->post(config('services.quick_kyc.base_url') . '/api/v1/corporate/udyog-aadhaar', [
					'key' => trim(config('services.quick_kyc.key')),
					'id_number' => trim($userKyc->udyam_number),
				]);

			$result = $response->json();
			\Log::info('QuickeKYC Udyam Response:', ['status' => $response->status(), 'body' => $result]);

			if ($response->successful() && isset($result['status']) && strtolower($result['status']) === 'success') {
                $mainDetails = $result['data']['main_details'] ?? [];
                $enterpriseName = $mainDetails['name_of_enterprise '] ?? $mainDetails['name_of_enterprise'] ?? null;

				$userKyc->update([
					'udyam_status' => 1,
					'udyam_enterprise_name' => $enterpriseName,
					'udyam_text' => $result,
					'udyam_role' => 'admin'
				]);

				// $this->userVerifiedKyc($userKyc);

				return response()->json([
					'status' => 'success',
					'msg' => 'Udyam Verified Successfully via API',
					'data' => $result
				]);
			}

			return response()->json(['status' => 'error', 'msg' => 'Verification Failed: ' . ($result['message'] ?? 'Unknown Error'), 'debug' => $result]);
		} catch (\Exception $e) {
			\Log::error('QuickeKYC Udyam Exception: ' . $e->getMessage());
			return response()->json(['status' => 'error', 'msg' => 'API Error: ' . $e->getMessage()]);
		}
	}
	---- END QUICK KYC ADMIN API METHODS ---- */
}
