<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SellerRemittance;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class CheckPendingRemittances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remittance:check-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for pending COD remittances scheduled for today and send notifications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting pending remittance check...');

        try {
            // Get today's date
            $today = Carbon::now()->format('Y-m-d');

            // Find pending remittances scheduled for today
            $pendingRemittances = SellerRemittance::where('settlement_status', 'pending')
                ->whereDate('payment_scheduled_date', $today)
                ->with('user') // Eager load user relationship
                ->get();

            if ($pendingRemittances->isEmpty()) {
                $this->info('No pending remittances found for today.');
                return 0;
            }

            // Send notification to admin users
            $this->sendNotificationToAdmins($pendingRemittances);

            $this->info('Pending remittance check completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking pending remittances: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Send notification to admin users
     *
     * @param \Illuminate\Database\Eloquent\Collection $remittances
     * @return void
     */
    protected function sendNotificationToAdmins($remittances)
    {
        try {
            // Get all admin/staff users (not regular users)
            $admin = User::where('role', 'admin')
                ->where('status', 1)
                ->first();

            if ($admin == null) {
                $this->warn('No admin users found to send notifications.');
                return;
            }

            $count = $remittances->count();
            $totalAmount = $remittances->sum('amount_transferred_to_seller');
            $formattedAmount = config('setting.currency') . ' ' . number_format($totalAmount, 2);

            // Create database notifications for each admin
            $notificationsCreated = 0;

            foreach ($remittances as $remittance) {
                $userName = $remittance->user ? $remittance->user->name : 'Unknown User';
                $amount = config('setting.currency') . ' ' . number_format($remittance->amount_transferred_to_seller, 2);

                // Create notification text
                $notificationText = "Pending Remittance: {$remittance->remittance_id} for {$userName} - Amount: {$amount} scheduled for today";

                // Create notification in database
                Notification::create([
                    'user_id' => $admin->id,
                    'task_id' => $remittance->id,
                    'role' => $admin->role,
                    'type' => 'pending_remittance',
                    'text' => $notificationText,
                    'read_at' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $notificationsCreated++;
            }
            $this->info("Created {$notificationsCreated} notification(s) for {$admin->name} admin(s). ");
        } catch (\Exception $e) {
            $this->error('Error creating notifications: ' . $e->getMessage());
        }
    }
}
