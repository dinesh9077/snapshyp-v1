<?php

namespace App\Console\Commands;

use App\Models\UserWallet;
use App\Models\Billing;
use Illuminate\Console\Command;
use App\Services\MasterService;
use DB;
class UpdateRechargeStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'recharge:update-status';

    /**
     * The console command description.
     */
    protected $description = 'Update the recharge status from API or database.';

    public function __construct(MasterService $masterService)
    {
        parent::__construct();
        $this->masterService = $masterService;
    } 
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recharge status update started...');

        $recharges = UserWallet::whereNotIn('transaction_status', ['Paid', 'captured', 'cancelled', 'Rejected'])
            ->whereDate('created_at', now()->toDateString())
            ->get();

        if ($recharges->isEmpty()) {
            $this->info('No recharges found for today that need status updates.');
            return;
        } 

        foreach($recharges as $recharge)
        { 
            $this->info("Checking status for Recharge ID: {$recharge->id}, Order ID: {$recharge->order_id}"); 
            $statusResponse = $this->masterService->getRechargeStatus($recharge->order_id);
           
            if (!($statusResponse['success'] ?? false)) {
                continue;
            }

            if ((isset($statusResponse['response']['message']) && $statusResponse['response']['message'] === "Failed")) {
                continue;
            }

            if (empty($statusResponse['response']['data'])) {
                continue;
            }  

            $status =  $statusResponse['response']['data']['paymentStatus'] ?? $recharge->transaction_status;

            $txnId = $statusResponse['response']['data']['paymentDetails']['id'] ?? null;
            $utrNo = $statusResponse['response']['data']['paymentDetails']['acquirer_data']['rrn'] ?? null;
           
            if($status === "captured")
            {
                $this->updateRechargeStatus($recharge->order_id, 'Paid', $txnId, $utrNo);
            } 
        }       

        $this->info('Recharge status update completed!');
    }

    public function updateRechargeStatus($orderId, $newStatus, $txnId = null, $utrNo = null)
    {
        DB::beginTransaction();

        try {
            $userWallet = UserWallet::with('user')->where('order_id', $orderId)->firstOrFail();
            $user = $userWallet->user;
          
            // If new status is "Paid" (or equivalent), process recharge
            if (in_array(strtolower($newStatus), ['paid', 'captured'])) {
                
                // Increase user's wallet balance
                $user->increment('wallet_amount', $userWallet->amount);
                
                // Add billing entry
                Billing::create([
                    'user_id' => $user->id,
                    'billing_type' => 'Recharge Wallet',
                    'billing_type_id' => $userWallet->id,
                    'transaction_type' => 'credit',
                    'amount' => $userWallet->amount,
                    'note' => "Payment received through Razorpay with UTR number: {$utrNo}."
                ]);   
            }

            // Update UserWallet transaction details
            $userWallet->update([
                'transaction_status' => ucfirst($newStatus),
                'txn_number' => $txnId,
                'utr_no' => $utrNo ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Recharge status updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
