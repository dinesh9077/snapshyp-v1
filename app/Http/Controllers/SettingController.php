<?php

namespace App\Http\Controllers;

use App\Models\RateCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\User;
use App\Models\LabelPreference;
use App\Models\UserKyc;

use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index()
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}
		return view('setting.general');
	}

	public function wordpressIntegration()
	{
		$apiToken = \Illuminate\Support\Facades\Auth::user()->api_token ?? '';
		return view('setting.wordpress-integration', compact('apiToken'));
	}


	public function updateGeneral(Request $request)
	{
		// Logos are saved under the public storage folder, so only image types may be uploaded
		$request->validate([
			'login_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
			'header_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
			'small_header_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
			'fevicon_icon' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg,ico|max:1024',
		]);

		$data = $request->except('_token', 'login_logo', 'header_logo', 'fevicon_icon', 'small_header_logo');

		$path_avatar = "storage/app/public/settings";



		// Create directory if it doesn't exist
		if (!File::isDirectory($path_avatar)) {
			File::makeDirectory($path_avatar, 0777, true, true);
		}

		// Initialize the transaction
		DB::beginTransaction();

		try {
			/*
			// ZOHO OAUTH HANDLING (commented/disabled)
			// If Manual Refresh Token is provided, FORCE generate a new Access Token to verify it and ensure we have correct permissions
			if (!empty($data['zoho_refresh_token'])) {
				 $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
					'refresh_token' => $data['zoho_refresh_token'],
					'client_id' => $data['zoho_client_id'],
					'client_secret' => $data['zoho_client_secret'],
					'grant_type' => 'refresh_token',
				]);

				if ($response->successful()) {
					$tokenData = $response->json();
					$data['zoho_access_token'] = $tokenData['access_token'];
					// Use expires_in_sec for seconds, fallback to 1 hour
					$expiresIn = $tokenData['expires_in_sec'] ?? 3600;
					$data['zoho_token_expires_at'] = now()->addSeconds($expiresIn);
				} else {
					// If generation fails (e.g. invalid refresh token, wrong datacenter, wrong client id/secret), throw error
					throw new \Exception('Failed to generate Access Token from the provided Refresh Token. Error: ' . ($response->json()['error'] ?? $response->body()));
				}
			}
			// Else if Auth Code is provided, exchange it
			elseif (!empty($data['zoho_authorization_code'])) {
				$response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
					'grant_type' => 'authorization_code',
					'client_id' => $data['zoho_client_id'],
					'client_secret' => $data['zoho_client_secret'],
					'redirect_uri' => $data['zoho_redirect_url'],
					'code' => $data['zoho_authorization_code']
				]);

				if ($response->successful()) {
					$tokenData = $response->json();
					$data['zoho_access_token'] = $tokenData['access_token'];
					if (isset($tokenData['refresh_token'])) {
						$data['zoho_refresh_token'] = $tokenData['refresh_token'];
					}
					// Use expires_in_sec for seconds, fallback to 1 hour
					$expiresIn = $tokenData['expires_in_sec'] ?? 3600;
					$data['zoho_token_expires_at'] = now()->addSeconds($expiresIn);
				} else {
					throw new \Exception('Zoho Token Generation Failed: ' . $response->body());
				}
			}

			unset($data['zoho_authorization_code']);
			*/

			$files = ['login_logo', 'header_logo', 'fevicon_icon', 'small_header_logo'];
			foreach ($files as $file) {
				if (!empty($request->file($file))) {
					$photo_image = $request->file($file);
					$getAvatar = time() . rand(111111, 999999) . '.' . $photo_image->getClientOriginalExtension();
					$photo_image->move($path_avatar, $getAvatar);
					$data[$file] = $getAvatar;
				}
			}

			// Update or insert settings
			foreach ($data as $key => $value) {
				// Convert null values to empty strings
				if (is_null($value)) {
					$value = '';
				}

				$setting = Setting::where('name', $key)->first();
				if ($setting) {
					// Update existing setting
					$setting->update(['value' => $value, 'updated_at' => now()]);
				} else {
					// Insert new setting
					Setting::create(['name' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
				}
			}

			// Commit the transaction
			DB::commit();
			return response()->json([
				'status' => 'success',
				'message' => 'The general setting has been updated successfully.'
			]);
		} catch (\Exception $e) {
			// Rollback the transaction in case of an error
			DB::rollBack();

			// Return error response
			return response()->json([
				'status' => 'error',
				'message' => 'Failed to update general settings: ' . $e->getMessage()
			]);
		}
	}

	// Profile
	public function profile()
	{
		$user = Auth::user();
		$rateCards = RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', '!=', 'b2b')
			->get();
		return view('setting.profile', compact('user', 'rateCards'));
	}

	public function updateProfile(Request $request)
	{
		try {
			DB::beginTransaction(); // Start transaction

			$user_id = Auth::id();

			if ($request->has('mobile') && $request->mobile != Auth::user()->mobile) {
				if (User::where('id', '!=', $user_id)->where('mobile', $request->mobile)->exists()) {
					return redirect()->route('profile')->with('error', 'The mobile number already exists.');
				}
			}

			if ($request->filled('email') && User::where('id', '!=', $user_id)->where('email', $request->email)->exists()) {
				return redirect()->route('profile')->with('error', 'The email already exists.');
			}

			// Only the fields on the profile form; role, wallet, KYC etc. must never come from here
			$fields = ['name', 'company_name', 'email', 'mobile', 'address', 'city', 'state', 'country', 'zip_code', 'pancard_number'];
			if (Auth::user()->role !== 'user') {
				$fields[] = 'rate_card_id';
			}
			$data = $request->only($fields);

			if ($request->hasFile('profile_image')) {
				$photo_image = $request->file('profile_image');
				$getAvatar = time() . rand(111111, 999999) . '.' . $photo_image->getClientOriginalExtension();
				$path_avatar = storage_path("app/public/profile");

				// Ensure directory exists
				if (!File::exists($path_avatar)) {
					File::makeDirectory($path_avatar, 0777, true, true);
				}

				// Move file
				$photo_image->move($path_avatar, $getAvatar);
				$data['profile_image'] = $getAvatar;
			}

			// Update user profile
			User::whereId($user_id)->update($data);

			DB::commit(); // Commit transaction

			return redirect()->route('profile')->with('success', 'Your profile has been updated successfully.');
		} catch (\Exception $e) {
			DB::rollBack(); // Rollback if an error occurs
			return redirect()->route('profile')->with('error', 'An error occurred while updating your profile. Please try again.');
		}
	}

	// Change Password
	public function changePassword()
	{
		return view('setting.change-password');
	}

	public function updatePassword(Request $request)
	{
		// Validate request input
		$request->validate([
			'old_password' => ['required'],
			'new_password' => ['required', 'min:8', 'confirmed'], // Laravel 'confirmed' rule removes the need for manual check
		]);

		DB::beginTransaction();
		try {
			// Get the authenticated user
			$user = Auth::user();

			// Check if old password is correct
			if (!Hash::check($request->old_password, $user->password)) {
				return back()->with('error', 'The old password is incorrect.');
			}

			// Update user password
			$user->update([
				'password' => Hash::make($request->new_password),
				'xpass' => $request->new_password, // stored encrypted (User casts xpass)
			]);

			DB::commit();
			// Logout the user after password change
			Auth::logout();

			// Redirect to login page with success message
			return redirect()->route('login')->with('success', 'Your password has been changed successfully. Please log in again.');
		} catch (\Exception $e) {
			DB::rollBack();
			return back()->with('error', 'An error occurred while updating your password. Please try again.');
		}
	}

	public function lablePreferance()
	{
		return view('setting.label-preferance');
	}

	public function updateLablePref(Request $request)
	{
		$data = [];
		$data['order_value'] = (isset($request->order_value)) ? $request->order_value : 0;
		$data['shipper_mobile'] = (isset($request->shipper_mobile)) ? $request->shipper_mobile : 0;
		$data['shipper_address'] = (isset($request->shipper_address)) ? $request->shipper_address : 0;
		$data['consignee_contact'] = (isset($request->consignee_contact)) ? $request->consignee_contact : 0;
		foreach ($data as $key => $row) {
			$check = Setting::where('name', $key)->first();
			if (!empty($check)) {
				Setting::where('name', $key)->update(['value' => $row, 'updated_at' => date('Y-m-d H:i:s')]);
			} else {
				if (!empty($row)) {
					Setting::insert(['name' => $key, 'value' => $row, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
				}
			}
		}
		return back()->with('success', 'The label setting have been updated successfully.');
	}

	public function integrations()
	{
		return view('setting.integrations');
	}

	public function developerApiPage(Request $request)
	{
		$authUser = app('authUser');

		// Ensure every user visiting this page has an API token
		if (empty($authUser->api_token)) {
			$authUser->api_token = $this->generateUniqueToken();
			$authUser->save();
		}

		$apiToken = $authUser->api_token;

		return view('setting.api-developer', compact('apiToken'));
	}

	public function labelSettings()
	{
		$preference = LabelPreference::where('user_id', Auth::id())->first();
		return view('setting.label-settings', compact('preference'));
	}

	public function bankDetails()
	{
		$user = Auth::user();
		if ($user->role !== 'franchise') {
			abort(403, 'Permission denied');
		}
		$userKyc = UserKyc::firstOrCreate(['user_id' => $user->id]);

		return view('setting.bank-details', compact('user', 'userKyc'));
	}

	public function updateBankDetails(Request $request)
	{
		$user = Auth::user();
		if ($user->role !== 'franchise') {
			abort(403, 'Permission denied');
		}

		$request->validate([
			'bank_name' => 'required|string|max:255',
			'account_holder_name' => 'required|string|max:255',
			'account_number' => 'required|string|max:30',
			'ifsc_code' => 'required|string|max:20',
			'bank_passbook' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
		]);

		DB::beginTransaction();

		try {
			$data = [
				'bank_name' => trim((string) $request->bank_name),
				'account_holder_name' => trim((string) $request->account_holder_name),
				'account_number' => trim((string) $request->account_number),
				'ifsc_code' => strtoupper(trim((string) $request->ifsc_code)),
				'bank_role' => 'self',
			];

			if ($request->hasFile('bank_passbook')) {
				$path = storage_path("app/public/bank");
				if (!File::isDirectory($path)) {
					File::makeDirectory($path, 0777, true, true);
				}

				$file = $request->file('bank_passbook');
				$filename = "bank_" . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
				$file->move($path, $filename);
				$data['bank_passbook'] = $filename;
			}

			UserKyc::updateOrCreate(
				['user_id' => $user->id],
				$data
			);

			DB::commit();
			return back()->with('success', 'Bank details updated successfully.');
		} catch (\Exception $e) {
			DB::rollBack();
			return back()->with('error', 'Failed to update bank details.');
		}
	}

	public function updateLabelSettings(Request $request)
	{
		$data = $request->except('_token', 'label_company_logo');
		$user_id = Auth::id();

		// Handle File Upload
		if ($request->hasFile('label_company_logo')) {
			$path = storage_path("app/public/settings/label");
			if (!File::isDirectory($path)) {
				File::makeDirectory($path, 0777, true, true);
			}

			$file = $request->file('label_company_logo');
			$filename = time() . '_' . $file->getClientOriginalName();
			$file->move($path, $filename);

			$data['label_company_logo'] = $filename;
		}

		// Handle Checkboxes Defaults
		$checkboxes = [
			'label_show_invoice_value',
			'label_show_invoice_number',
			'label_show_seller_name',
			'label_show_seller_address',
			'label_show_company_name',
			'label_show_seller_mobile',
			'label_show_quantity',
			'label_show_item_price',
			'label_show_taxes',
			'label_show_product_name',
			'label_show_sku',
			'label_print_multiple',
			'label_show_weight',
			'label_show_cod_amount'
		];

		foreach ($checkboxes as $chk) {
			if (!array_key_exists($chk, $data)) {
				$data[$chk] = 0;
			}
		}

		// Save/Update User Preference
		LabelPreference::updateOrCreate(
			['user_id' => $user_id],
			$data
		);

		return back()->with('success', 'Label preferences updated successfully.');
	}

	private function generateUniqueToken(): string
	{
		do {
			$token = bin2hex(random_bytes(16));
		} while (User::where('api_token', $token)->exists());

		return $token;
	}
}
