<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\KycPendingAlert;

class KycPendingCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyc:pending-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check users with pending KYC and send reminder emails';

    /**
     * Execute the console command.
     */
    public function handle()
	{
		$this->info("Starting KYC pending check...");

		// Eager load UserKyc to avoid N+1
		$users = User::where('role', 'user')
			->with('userKyc')
			->whereHas('userKyc') 
			->get();

		$notifications = [];

		foreach ($users as $user) {
			$userKyc = $user->kyc;

			if (!$userKyc) {
				continue; // Skip users without KYC
			}

			// Pending KYC checks
			$kycChecks = [
				'pancard_status' => 'Pan Card',
				'aadhar_status' => 'Aadhar Card',
				'bank_status' => 'Bank Details',
			];

			$pendingKycTypes = [];
			foreach ($kycChecks as $field => $kycType) {
				if ($userKyc->$field == 0) {
					$pendingKycTypes[] = $kycType;
				}
			}

			if (empty($pendingKycTypes)) {
				continue; // Nothing pending
			}

			// Prepare message
			$message = "Dear {$user->name},<br><br>"
				. "Your KYC verification is still incomplete. Please complete the following pending steps:<br>"
				. implode(', ', $pendingKycTypes)
				. "<br><br>Complete your KYC as soon as possible to enjoy our full services.<br>Regards,<br>"
				. config('setting.company_name');

			// Send email (non-blocking)
			try {
				Mail::to($user->email)->send(new KycPendingAlert($user, $message));
			} catch (\Exception $e) {
				$this->error("Failed to send KYC email to {$user->email}: {$e->getMessage()}");
			}

			// Prepare notification for batch insert
			$notifications[] = [
				'user_id' => $user->id,
				'task_id' => $user->id,
				'type' => 'Kyc Pending',
				'role' => 'user',
				'text' => strip_tags($message),
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		// Insert all notifications in one query
		if (!empty($notifications)) {
			Notification::insert($notifications);
		}

		$this->info("KYC pending check completed for " . count($notifications) . " users.");
	}
 
}
