<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Mail\OtpMail;
use App\Models\Shop;
use App\Models\LabelPreference;
use App\Models\CodSetting;
use App\Support\EmailOtp;

class RegisterController extends Controller
{
	/*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

	use RegistersUsers;

	/**
	 * Where to redirect users after registration.
	 *
	 * @var string
	 */
	protected $redirectTo = RouteServiceProvider::HOME;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}

	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param  array  $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function validator(array $data)
	{
		return Validator::make($data, [
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
			'password' => ['required', 'string', 'min:8', 'confirmed'],
		]);
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return \App\Models\User
	 */
	protected function create(array $data)
	{
		return User::create([
			'name' => $data['name'],
			'email' => $data['email'],
			'password' => Hash::make($data['password']),
		]);
	}

	public function postRegister(Request $request)
	{
		// Generate OTP
		$generatedOTP = rand(100000, 999999); // 6-digit OTP

		try {

			$shop = $request->shop ? base64_decode($request->shop) : null;
			$host = $request->host ? base64_decode($request->host) : null;

			if ($shop && $host) {
				$registeredShop = Shop::where('shopify_domain', $shop)->first();
				if (!$registeredShop) {
					return response()->json([
						'status' => 'error',
						'msg' => 'Please uninstall our app from Shopify and reinstall it to register correctly.'
					]);
				}

				if (User::where("shop", $shop)->exists()) {
					return response()->json([
						'status' => 'error',
						'msg' => 'This Shopify store is already registered. Please contact the administrator for assistance.'
					]);
				}
			}

			if (User::where("email", $request->email)->exists()) {
				return response()->json(['status' => 'error', 'msg' => 'The email has already exists.']);
			}

			// if (User::where("mobile", $request->mobile)->exists()) {
			// 	return response()->json(['status' => 'error', 'msg' => 'The mobile has already exists.']);
			// }

			if (!isset($request->is_verify_otp) && $request->is_verify_otp == 0) {
				Mail::to($request->email)->send(new OtpMail($request->name, $generatedOTP));
				EmailOtp::issue($request->email, $generatedOTP);

				return response()->json([
					'status' => 'mail',
					'msg'    => 'We have sent an OTP to your registered email address. Please check your inbox (and spam folder) and enter the OTP to continue.',
				]);
			}

			DB::beginTransaction();

			// The account is created only after the OTP for this email was verified
			if (!EmailOtp::consumeVerified($request->email)) {
				DB::rollBack();
				return response()->json(['status' => 'error', 'msg' => 'Please verify the OTP sent to your email first.']);
			}

			// Calculate next seller_id
			$maxSellerId = User::max('seller_id');
			$nextSellerId = $maxSellerId ? $maxSellerId + 1 : 1001;
			$defaultCodPlan = CodSetting::where('is_active', true)
				->where('is_default', true)
				->first();

			// Create the user
			$user = User::create([
				'seller_id' => $nextSellerId,
				'company_name' => $request->company_name,
				'name' => $request->name,
				'email' => $request->email,
				'mobile' => $request->mobile,
				'password' => Hash::make($request->password),
				'xpass' => $request->password, // stored encrypted (User casts xpass)
				'status' => 1,
				'charge_type' => 1,
				'charge' => 0,
				'otp' => $generatedOTP,
				'address' => $request->address,
				'state' => $request->state,
				'city' => $request->city,
				'country' => $request->country,
				'zip_code' => $request->zip_code,
				'gender' => $request->gender,
				'cod_plan_id' => $defaultCodPlan ? $defaultCodPlan->id : null,
				'cod_plan_active' => $defaultCodPlan ? 1 : 0,
				'shop' => $shop,
				'api_token' => $this->generateUniqueToken(),
				'created_at' => now(),
				'updated_at' => now(),
			]);

			// Create default label preferences
			LabelPreference::create([
				'user_id' => $user->id,
				'created_at' => now(),
				'updated_at' => now()
			]);

			Notification::create([
				'task_id' => $user->id,
				'type' => 'New User',
				'role' => 'admin',
				'text' => $request->name . ' is a new user who has successfully registered.',
				'created_at' => now(),
				'updated_at' => now()
			]);

			Notification::create([
				'user_id' => $user->id,
				'task_id' => $user->id,
				'type' => 'New User',
				'role' => 'user',
				'text' => 'Your registration is now complete. Thank you.',
				'created_at' => now(),
				'updated_at' => now()
			]);

			DB::commit();

			return response()->json(['status' => 'success', 'msg' => 'The registration has been successfully created.']);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to send OTP. Please try again.' . $e->getMessage()]);
		}
	}
	private function generateUniqueToken(): string
	{
		do {
			$token = bin2hex(random_bytes(16));
		} while (User::where('api_token', $token)->exists());

		return $token;
	}

	// Checked against user_otps; the browser never receives the OTP
	public function verifyOTP(Request $request)
	{
		if (!EmailOtp::verify((string) $request->input('email'), $request->input('otp'))) {
			return response()->json(['status' => 'error', 'msg' => 'Invalid OTP, Please Try again.']);
		}

		return response()->json(['status' => 'success', 'msg' => 'OTP verified successfully']);
	}

	public function resend(Request $request)
	{
		$generatedOTP = rand(100000, 999999); // 6-digit OTP

		try {
			Mail::to($request->email)->send(new OtpMail($request->name, $generatedOTP));
			EmailOtp::issue($request->email, $generatedOTP);

			return response()->json([
				'status' => 'mail',
				'msg'    => 'A new OTP has been sent to your email address. Please check your inbox (and spam folder) and enter the OTP to continue.',
			]);
		} catch (\Exception $e) {

			return response()->json([
				'status' => 'error',
				'msg'    => 'Unable to send OTP at the moment. Please try again after some time.'
			]);
		}
	}
}
