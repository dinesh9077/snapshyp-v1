<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use DB;
use App\Mail\LowBalanceAlert;

class LowBalanceCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lowbalance:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user wallet balance and send alerts if it is low';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now();

        // Get all users with low balance and haven't been notified today
        $users = User::where('role', 'user')
            ->where('wallet_amount', '<=', 100)
            ->where(function ($query) use ($today) {
                $query->whereNull('sms_date')
                      ->orWhereDate('sms_date', '<', $today->format('Y-m-d'));
            })
            ->get(['id', 'name', 'email', 'mobile', 'sms_date']);
		if(!$users){
			return;
		}
        foreach ($users as $user) {
            try {
                DB::beginTransaction();

                // Email Message Content
                $message = "Alert! Low wallet balance. Top-up needed for continued service. Thank you. Regards, " . config('setting.company_name');
				
                // Send Email Notification
                try {
                    Mail::to($user->email)->send(new LowBalanceAlert($user, $message));
                } catch (\Exception $e) { 
					$this->info("Error processing low balance check: " . $e->getMessage());
                }
					
                // Update SMS date to prevent duplicate notifications
                $user->update(['sms_date' => $today->format('Y-m-d')]);
				
				Notification::insert([
					'user_id' => $user->id,
					'task_id' => $user->id,
					'type' => 'Low Balnace', 
					'role' => 'user', 
					'text' => $message, 
					'created_at' => now(), 
					'updated_at' => now()
				]);
 
 
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack(); 
            }
        }

        $this->info("Low balance check completed.");
    }
}