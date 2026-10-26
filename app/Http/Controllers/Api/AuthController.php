<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use App\Models\User;
	use App\Models\Notification;
	use App\Models\UserOtp;
	use App\Models\PasswordResetOtp;
	use Illuminate\Support\Facades\Hash;
	use Carbon\Carbon; 
	use App\Traits\ApiResponse; 
	use Illuminate\Support\Facades\Mail;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Facades\Http;
	use App\Mail\OtpMail;
	use App\Mail\ForgotPasswordMail;
	use Illuminate\Support\Str;
	use App\Models\CodSetting;
	use App\Support\EmailOtp;
	
	class AuthController extends Controller
	{
		use ApiResponse;

		public function register(Request $request)
	{ 
		try { 	
			$validator = Validator::make($request->all(), [
				'company_name' => 'required|string|max:255',
				'name'         => 'required|string|max:255',
				'mobile'       => 'required|digits_between:8,15|unique:users,mobile',
				'email'        => 'required|email|max:255|unique:users,email',
				'password'     => 'required|string|min:8',
				'gender'       => 'required|in:Male,Female,Other',
				'address'      => 'required|string|max:500',
				'zip_code'     => 'required|digits_between:4,10',
				'city'         => 'required|string|max:255',
				'state'        => 'required|string|max:255',
				'country'      => 'required|string|max:255',
			]);
			
			if ($validator->fails()) { 
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}
			 
			// Check if email already exists
			if (User::where('email', $request->email)->exists()) {
				return $this->errorResponse('The email has already exists.', 400);
			}

			if(!isset($request->is_verify_otp) && $request->is_verify_otp == 0)
			{  
				$otp = rand(100000, 999999);
				EmailOtp::issue($request->email, $otp, 5);

				Mail::to($request->email)->send(new OtpMail($request->name, $otp));

				// Never return the OTP itself; the client verifies it through /verify-otp
				return $this->successResponse([
					'type' => 'mail',
				], 'An OTP has been sent to your provided email.');
			}

			DB::beginTransaction();

			// Registration needs an email OTP that /verify-otp has already confirmed
			if (!EmailOtp::consumeVerified($request->email)) {
				DB::rollBack();
				return $this->errorResponse('Please verify the OTP sent to your email first.', 400);
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
				'otp' => null,
				'address' => $request->address,
				'state' => $request->state,
				'city' => $request->city,
				'country' => $request->country,
				'zip_code' => $request->zip_code,
				'gender' => $request->gender,
				'cod_plan_id' => $defaultCodPlan ? $defaultCodPlan->id : null,
				'cod_plan_active' => $defaultCodPlan ? 1 : 0,
				'api_token' => $this->generateUniqueToken(), // Add API token logic
				'created_at' => now(),
				'updated_at' => now(),
			]);
			
			// Create default label preferences (Match Web Logic)
			\App\Models\LabelPreference::create([
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
			 
			// Generate Passport Access Token
			$tokenResult = $user->createToken('Personal Access Token');
			$token = $tokenResult->accessToken; 
			$user->token = $token;
			$user->type = 'register';

			DB::commit();
			
			return $this->successResponse($user, 'User registered successfully.'); 
		} 
		catch (\Exception $e) 
		{
			DB::rollBack();
			return $this->errorResponse('Failed to send OTP or Register. Please try again: ' . $e->getMessage(), 500); 
		}
	}

	private function generateUniqueToken(): string
	{
		do {
			$token = bin2hex(random_bytes(16));
		} while (User::where('api_token', $token)->exists());

		return $token;
	}
		
		public function resendOtp(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'name' => 'required|string|max:255',
				'email' => 'required|email|max:255',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			try {
				$otp = rand(100000, 999999);

				// Web version doesn't save to DB, it just sends email.
				// However, your API verifyOtp checks the DB UserOtp table. 
				// To keep API stateful and secure, we MUST save to DB.
				// Web relies on client-side or session uuid, API relies on DB.
				// We will DO BOTH to support all clients.

				EmailOtp::issue($request->email, $otp, 10);
				
				// Send Mail
				Mail::to($request->email)->send(new OtpMail($request->name, $otp)); 
				
				return $this->successResponse([], 'New OTP sent successfully.');

			} catch (\Exception $e) {
				Log::error('Resend OTP failed', [
					'email' => $request->email,
					'message' => $e->getMessage(),
				]);
				return $this->errorResponse('Failed to resend OTP. Please try again.', 500, [
					'error' => $e->getMessage(),
				]);
			}
		}
  
		public function verifyOtp(Request $request)
		{  
			$validator = Validator::make($request->all(), [
				'email' => 'required|email|max:255',
				'otp'   => 'required|digits:6',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			// Only the server-side OTP counts; a client-sent "generatedOtp" could be forged
			if (!EmailOtp::verify($request->email, $request->otp)) {
				return $this->errorResponse('Invalid or expired OTP.', 400);
			}

			return $this->successResponse([], 'OTP verified successfully.');
		}
		
		public function forgotPassword(Request $request)
		{  
			$validator = Validator::make($request->all(), [
				'email' => 'required|email|exists:users,email' 
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}
			
			$user = User::where('email', $request->email)->first();
			
			DB::beginTransaction(); 
			try { 
				// Generate 6-digit OTP
				$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

				// Invalidate previous OTPs for this email (Matches Web Logic)
				PasswordResetOtp::where('email', $request->email)
					->where('is_used', false)
					->update(['is_used' => true]);

				// Create new OTP record (Matches Web Logic)
				PasswordResetOtp::create([
					'email' => $request->email,
					'otp' => $otp,
					'expires_at' => Carbon::now()->addMinutes(10), // OTP valid for 10 minutes
					'is_used' => false,
				]);

				$data = [
					'name' => $user->name,
					'email' => $user->email,
					'otp' => $otp,
				];

				Mail::to($user->email)->send(new ForgotPasswordMail($data));
				DB::commit();
				
				// Never return the OTP; /forgot-password/verify-otp and /reset check it server-side
				return $this->successResponse([
					'email' => $request->email,
				], 'OTP has been sent to your email address.');
			} catch (\Exception $e) { 
				DB::rollBack();
				Log::error('Forgot password OTP failed', [
					'email' => $request->email,
					'message' => $e->getMessage(),
				]);
				return $this->errorResponse('Failed to send OTP. Please try again later.', 500, [
					'error' => $e->getMessage(),
				]);	 
			}
		} 

		public function verifyForgotPasswordOtp(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'email' => 'required|email|exists:users,email',
				'otp' => 'required|string|size:6',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			$otpRecord = PasswordResetOtp::where('email', $request->email)
				->where('otp', $request->otp)
				->where('is_used', false)
				->latest()
				->first();

			if (!$otpRecord || !$otpRecord->isValid()) {
				return $this->errorResponse('Invalid or expired OTP.', 422);
			}

			return $this->successResponse([], 'OTP verified successfully.');
		}

		public function resetPassword(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'email' => 'required|email|exists:users,email',
				'otp' => 'required|string|size:6',
				'password' => 'required|string|min:8',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			DB::beginTransaction();

			try {
				$otpRecord = PasswordResetOtp::where('email', $request->email)
					->where('otp', $request->otp)
					->where('is_used', false)
					->latest()
					->first();

				if (!$otpRecord || !$otpRecord->isValid()) {
					return $this->errorResponse('Invalid or expired OTP.', 422);
				}

				$user = User::where('email', $request->email)->first();
				$user->password = Hash::make($request->password);
				$user->xpass = $request->password; // stored encrypted (User casts xpass)
				$user->save();

				$otpRecord->is_used = true;
				$otpRecord->save();

				DB::commit();

				return $this->successResponse([], 'Password has been reset successfully.');
			} catch (\Exception $e) {
				DB::rollBack();
				return $this->errorResponse('Failed to reset password. Please try again.', 500);
			}
		} 
		 
		public function login(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'email' => 'required',
				'password' => 'required',
			]);

			if ($validator->fails()) {
				return $this->validateResponse('Validation failed', 422, $validator->errors());
			}

			$credentials = $request->only('email', 'password');
			$user = User::where('email', $request->email)->first();
			
			if(!$user)
			{
				return $this->errorResponse('Invalid credentials', 401);
			}

			// Match Web Logic: Check if user is active
			if (!$user->isAdminLevel() && $user->status == 0) {
				return $this->errorResponse('This user is not active. Please contact the admin.', 401);
			}

			if($user->role != "user")
			{
				return $this->errorResponse('Invalid credentials', 401);
			}

			if (!Auth::attempt($credentials)) {
				return $this->errorResponse('Invalid credentials', 401);
			}
			
			$user = Auth::user();
			
			// Performance: Removing mass update of tokens to speed up login
			// $user->tokens()->update(['revoked' => true]);
			
			try {
				$tokenResult = $user->createToken('Personal Access Token');
				$token = $tokenResult->accessToken;
				$user->token = $token;
				return $this->successResponse($user, 'User Login successfully.');
			} catch (\Exception $e) {
				return $this->errorResponse('Internal Server Error during token creation: ' . $e->getMessage(), 500);
			}
		}

		public function user(Request $request)
		{
			$user = $request->user();
			$user->load('userKyc');
			if($user->userKyc)
			{
				$userkyc = $user->userKyc;
				
				$formatKycPath = function ($path, $folder) {
					if (!$path) {
						return null;
					}
					if (str_contains($path, 'storage/')) {
						return asset($path);
					}
					return asset('storage/' . $folder . '/' . $path);
				};

				$user->userKyc->pancard_image = $formatKycPath($userkyc->pancard_image, 'pancard');
				$user->userKyc->aadhar_front = $formatKycPath($userkyc->aadhar_front, 'aadhar');
				$user->userKyc->aadhar_back = $formatKycPath($userkyc->aadhar_back, 'aadhar');
				$user->userKyc->gst_image = $formatKycPath($userkyc->gst_image, 'gst');
				$user->userKyc->bank_passbook = $formatKycPath($userkyc->bank_passbook, 'bank');
				$user->userKyc->udyam_image = $formatKycPath($userkyc->udyam_image, 'udyam');
			}
			
			$user->profile_image = $user->profile_image
				? (str_contains($user->profile_image, 'http') ? $user->profile_image : url('storage/profile/' . $user->profile_image))
				: asset('assets/images/profile-logo.png');

			$user->unread_notifications_count = Notification::where('read_at', 0)
				->where('role', $user->role)
				->where(function ($q) use ($user) {
					if ($user->role === 'user') {
						$q->where('user_id', $user->id);
					}
				})
				->count();

			return $this->successResponse($user, 'User detail fetched.');
		}
 
		public function logout(Request $request)
		{
			$user = $request->user();
			 
			$token = $user->token();
			if ($token) { 
				$token->revoke();
				 
				DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $token->id)
                ->update(['revoked' => true]);
			}
			return $this->successResponse([], 'Successfully logged out.'); 
		}
	}
