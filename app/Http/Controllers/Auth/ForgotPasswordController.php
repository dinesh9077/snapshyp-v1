<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResetOtp;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
	/*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
	use SendsPasswordResetEmails;

	/**
	 * Show the forgot password form
	 */
	public function forgotPassword()
	{
		return view("auth.forgot-password.request");
	}

	/**
	 * Legacy method - kept for backward compatibility
	 * Redirects to new OTP-based flow
	 */
	public function forgotPasswordSend(Request $request)
	{
		return $this->sendOtp($request);
	}

	/**
	 * Send OTP to user's email
	 */
	public function sendOtp(Request $request)
	{
		// Validate input
		$request->validate([
			'email' => 'required|email|exists:users,email',
		]);

		try {
			// Retrieve user
			$user = User::where('email', $request->email)->firstOrFail();

			// Generate 6-digit OTP
			$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

			// Invalidate previous OTPs for this email
			PasswordResetOtp::where('email', $request->email)
				->where('is_used', false)
				->update(['is_used' => true]);

			// Create new OTP record
			PasswordResetOtp::create([
				'email' => $request->email,
				'otp' => $otp,
				'expires_at' => Carbon::now()->addMinutes(10), // OTP valid for 10 minutes
				'is_used' => false,
			]);

			// Send OTP via email
			$data = [
				'name' => $user->name,
				'email' => $user->email,
				'otp' => $otp,
			];

			Mail::to($user->email)->send(new ForgotPasswordMail($data));

			return response()->json([
				'success' => true,
				'message' => 'OTP has been sent to your email address.',
				'email' => $request->email,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to send OTP. Please try again later.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Show OTP verification form
	 */
	public function showVerifyOtp()
	{
		return view("auth.forgot-password.verify-otp");
	}

	/**
	 * Verify OTP
	 */
	public function verifyOtp(Request $request)
	{
		$request->validate([
			'email' => 'required|email|exists:users,email',
			'otp' => 'required|string|size:6',
		]);

		try {
			// Find the OTP record
			$otpRecord = PasswordResetOtp::where('email', $request->email)
				->where('otp', $request->otp)
				->where('is_used', false)
				->latest()
				->first();

			if (!$otpRecord) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid OTP. Please try again.',
				], 422);
			}

			if ($otpRecord->isExpired()) {
				return response()->json([
					'success' => false,
					'message' => 'OTP has expired. Please request a new one.',
				], 422);
			}

			// OTP is valid
			return response()->json([
				'success' => true,
				'message' => 'OTP verified successfully.',
				'email' => $request->email,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to verify OTP. Please try again.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Show reset password form
	 */
	public function showResetPassword()
	{
		return view("auth.forgot-password.reset");
	}

	/**
	 * Reset password
	 */
	public function resetPassword(Request $request)
	{
		$request->validate([
			'email' => 'required|email|exists:users,email',
			'otp' => 'required|string|size:6',
			'password' => 'required|string|min:8|confirmed',
		]);

		DB::beginTransaction();

		try {
			// Verify OTP one more time
			$otpRecord = PasswordResetOtp::where('email', $request->email)
				->where('otp', $request->otp)
				->where('is_used', false)
				->latest()
				->first();

			if (!$otpRecord || !$otpRecord->isValid()) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid or expired OTP.',
				], 422);
			}

			// Update user password
			$user = User::where('email', $request->email)->firstOrFail();
			$user->password = Hash::make($request->password);
			$user->xpass = $request->password; // stored encrypted (User casts xpass)
			$user->password_updated_at = Carbon::now();
			$user->save();

			// Mark OTP as used
			$otpRecord->is_used = true;
			$otpRecord->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Password has been reset successfully. You can now login with your new password.',
			]);
		} catch (\Exception $e) {
			DB::rollBack();

			return response()->json([
				'success' => false,
				'message' => 'Failed to reset password. Please try again.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Resend OTP
	 */
	public function resendOtp(Request $request)
	{
		return $this->sendOtp($request);
	}
}
