<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserKycController extends Controller
{
	use ApiResponse;

	public function details(Request $request)
	{
		$user = $request->user();
		$userKyc = UserKyc::firstOrCreate(['user_id' => $user->id]);

		return $this->successResponse($this->transformUserKyc($userKyc), 'KYC details fetched successfully.');
	}

	public function kycUserPancardUpdate(Request $request)
	{
		$user = Auth::user();
		$currentKyc = UserKyc::where('user_id', $user->id)->first();

		$validator = Validator::make($request->all(), [
			'pancard' => ['required', 'string', 'size:10'],
			'pancard_image' => [empty($currentKyc?->pancard_image) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		try {
			$data = [
				'pancard' => strtoupper(trim($request->input('pancard'))),
				'pancard_status' => 0,
				'pan_full_name' => null,
				'pancard_category' => null,
				'pancard_text' => null,
				'pan_role' => 'user',
				'pan_reason' => null,
			];

			if ($request->hasFile('pancard_image')) {
				$data['pancard_image'] = $this->storeDocument($request->file('pancard_image'), 'pancard', 'pan');
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return $this->successResponse(
				$this->transformUserKyc($userKyc->refresh()),
				'Your PAN details have been submitted successfully. Pending admin verification.'
			);
		} catch (\Throwable $e) {
			Log::error('API User KYC PAN Manual Exception: ' . $e->getMessage());
			return $this->errorResponse('Something went wrong while submitting PAN: ' . $e->getMessage(), 500);
		}
	}

	public function kycUserAadharOtp(Request $request)
	{
		return $this->errorResponse('OTP flow is disabled in manual KYC mode. Please submit your Aadhar details directly.', 400);
	}

	public function kycUserAadharUpdate(Request $request)
	{
		$user = Auth::user();
		$currentKyc = UserKyc::where('user_id', $user->id)->first();

		$validator = Validator::make($request->all(), [
			'aadhar' => ['required', 'string', 'size:12'],
			'aadhar_front' => [empty($currentKyc?->aadhar_front) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
			'aadhar_back' => [empty($currentKyc?->aadhar_back) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		try {
			$data = [
				'aadhar' => trim($request->input('aadhar')),
				'aadhar_status' => 0,
				'aadhar_full_name' => null,
				'aadhar_address' => null,
				'aadhar_dob' => null,
				'aadhar_gender' => null,
				'aadhar_zip' => null,
				'aadhar_text' => null,
				'aadhar_role' => 'user',
				'aadhar_reason' => null,
			];

			if ($request->hasFile('aadhar_front')) {
				$data['aadhar_front'] = $this->storeDocument($request->file('aadhar_front'), 'aadhar', 'aadhar_front');
			}

			if ($request->hasFile('aadhar_back')) {
				$data['aadhar_back'] = $this->storeDocument($request->file('aadhar_back'), 'aadhar', 'aadhar_back');
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return $this->successResponse(
				$this->transformUserKyc($userKyc->refresh()),
				'Your Aadhar details have been submitted successfully. Pending admin verification.'
			);
		} catch (\Throwable $e) {
			Log::error('API User Aadhaar Manual Exception: ' . $e->getMessage());
			return $this->errorResponse('Something went wrong while submitting Aadhar: ' . $e->getMessage(), 500);
		}
	}

	public function kycUserGSTOtp(Request $request)
	{
		return $this->errorResponse('OTP flow is disabled in manual KYC mode. Please submit your GSTIN details directly.', 400);
	}

	public function kycUserGSTUpdate(Request $request)
	{
		$user = Auth::user();
		$currentKyc = UserKyc::where('user_id', $user->id)->first();

		$validator = Validator::make($request->all(), [
			'gst' => ['required', 'string', 'size:15'],
			'gst_image' => [empty($currentKyc?->gst_image) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		try {
			$data = [
				'gst' => strtoupper(trim($request->input('gst'))),
				'gst_status' => 0,
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
				$data['gst_image'] = $this->storeDocument($request->file('gst_image'), 'gst', 'gst');
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return $this->successResponse(
				$this->transformUserKyc($userKyc->refresh()),
				'Your GSTIN details have been submitted successfully. Pending admin verification.'
			);
		} catch (\Throwable $e) {
			Log::error('API User GST Manual Exception: ' . $e->getMessage());
			return $this->errorResponse('Something went wrong while submitting GSTIN: ' . $e->getMessage(), 500);
		}
	}

	public function kycUserBankPennyDropUpdate(Request $request)
	{
		$user = Auth::user();
		$currentKyc = UserKyc::where('user_id', $user->id)->first();

		$validator = Validator::make($request->all(), [
			'bank_name' => ['required', 'string', 'max:255'],
			'account_holder_name' => ['required', 'string', 'max:255'],
			'account_number' => ['required', 'string', 'max:30'],
			'ifsc_code' => ['required_without:ifsc', 'nullable', 'string', 'max:20'],
			'ifsc' => ['required_without:ifsc_code', 'nullable', 'string', 'max:20'],
			'bank_passbook' => [empty($currentKyc?->bank_passbook) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		$account = trim($request->input('account_number'));
		$ifsc = trim($request->input('ifsc_code') ?: $request->input('ifsc'));

		try {
			$data = [
				'bank_name' => trim($request->input('bank_name')),
				'account_holder_name' => trim($request->input('account_holder_name')),
				'account_number' => $account,
				'ifsc_code' => strtoupper($ifsc),
				'bank_status' => 0,
				'amount_deposited' => 0,
				'bank_role' => 'user',
				'bank_text' => null,
				'bank_reason' => null,
			];

			if ($request->hasFile('bank_passbook')) {
				$data['bank_passbook'] = $this->storeDocument($request->file('bank_passbook'), 'bank', 'bank');
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return $this->successResponse(
				$this->transformUserKyc($userKyc->refresh()),
				'Bank details submitted successfully. Pending admin verification.'
			);
		} catch (\Throwable $e) {
			Log::error('API User Bank Manual Exception: ' . $e->getMessage());
			return $this->errorResponse('Something went wrong while submitting bank details: ' . $e->getMessage(), 500);
		}
	}

	public function kycUserUdyamUpdate(Request $request)
	{
		$user = Auth::user();
		$currentKyc = UserKyc::where('user_id', $user->id)->first();

		$validator = Validator::make($request->all(), [
			'udyam_number' => ['required', 'string', 'max:50'],
			'udyam_image' => [empty($currentKyc?->udyam_image) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
		]);

		if ($validator->fails()) {
			return $this->validateResponse('Validation failed', 422, $validator->errors());
		}

		$udyamNumber = trim($request->input('udyam_number'));

		try {
			$data = [
				'udyam_number' => $udyamNumber,
				'udyam_status' => 0,
				'udyam_enterprise_name' => null,
				'udyam_text' => null,
				'udyam_role' => 'user',
				'udyam_reason' => null,
			];

			if ($request->hasFile('udyam_image')) {
				$data['udyam_image'] = $this->storeDocument($request->file('udyam_image'), 'udyam', 'udyam');
			}

			$userKyc = UserKyc::updateOrCreate(['user_id' => $user->id], $data);

			return $this->successResponse(
				$this->transformUserKyc($userKyc->refresh()),
				'Your Udyam Aadhaar details have been submitted successfully. Pending admin verification.'
			);
		} catch (\Throwable $e) {
			Log::error('API User Udyam Manual Exception: ' . $e->getMessage());
			return $this->errorResponse('Something went wrong while submitting Udyam: ' . $e->getMessage(), 500);
		}
	}

	private function storeDocument($file, string $folder, string $prefix): string
	{
		$path = storage_path("app/public/{$folder}");
		if (!File::exists($path)) {
			File::makeDirectory($path, 0777, true, true);
		}

		$filename = $prefix . '_' . time() . rand(111111, 999999) . '.' . $file->getClientOriginalExtension();
		$file->move($path, $filename);

		return $filename;
	}

	private function transformUserKyc(UserKyc $userKyc): array
	{
		$userKyc = $userKyc->fresh() ?: $userKyc;
		$data = $userKyc->toArray();

		$data['pancard_image_url'] = $this->documentUrl($userKyc->pancard_image, 'pancard');
		$data['aadhar_front_url'] = $this->documentUrl($userKyc->aadhar_front, 'aadhar');
		$data['aadhar_back_url'] = $this->documentUrl($userKyc->aadhar_back, 'aadhar');
		$data['gst_image_url'] = $this->documentUrl($userKyc->gst_image, 'gst');
		$data['bank_passbook_url'] = $this->documentUrl($userKyc->bank_passbook, 'bank');
		$data['udyam_image_url'] = $this->documentUrl($userKyc->udyam_image, 'udyam');

		return $data;
	}

	private function documentUrl(?string $path, string $folder): ?string
	{
		if (empty($path)) {
			return null;
		}

		if (str_contains($path, 'storage/')) {
			return asset($path);
		}

		return asset("storage/{$folder}/{$path}");
	}
}
