<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AppUninstalledJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;

    /**
     * Create a new job instance.
     *
     * @param string $shopDomain
     */
    public function __construct($shopDomain)
    {
        $this->shopDomain = $shopDomain;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Handling app uninstalled job for shop: ' . $this->shopDomain);

        // Clear session tokens for all users associated with this shop
        User::where('shop', $this->shopDomain)
            ->update(['shopify_session_token' => null, 'shop' => null]);

        // Mark shop inactive
        // Shop::where('shopify_domain', $this->shopDomain)
        //     ->update(['is_active' => 0, 'is_charge' => 0]);

        Shop::where('shopify_domain', $this->shopDomain)->delete();
    }
}
