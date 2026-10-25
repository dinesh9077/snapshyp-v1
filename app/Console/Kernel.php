<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\LowBalanceCheck::class,
        \App\Console\Commands\UpdateDelhiveryToken::class,
        \App\Console\Commands\KycPendingCheck::class,
        \App\Console\Commands\CheckPendingRemittances::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('billing:generate')->monthlyOn(1, '01:00');
        $schedule->command('weight:accept-discrepancies')->everyMinute()->withoutOverlapping();

        // Generate COD remittance based on T+N logic
        $schedule->command('cod:generate-remittance-new')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        // Pending Pickup: every 1 minute (if necessary). If heavy, increase to every 2-3 minutes. 
        $schedule->command('shipment:update-status "Ready To Ship"')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('shipment:update-status "Pending Pickup"')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // In Transit: every 15 minutes instead of 30 if you need near-realtime; set to everyThirtyMinutes() if lighter.
        $schedule->command('shipment:update-status "In Transit"')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Out For Delivery: uses cron */50 as you had — keep as cron() to maintain that offset if required
        $schedule->command('shipment:update-status "Out For Delivery"')
            ->cron('*/50 * * * *')
            ->withoutOverlapping()
            ->runInBackground();

        // RTO All polling
        $schedule->command('shipment:update-status "RTO All"')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Apply RTO billing/refund near realtime after status turns RTO
        $schedule->command('order:apply-rto-charge')->everyMinute()->withoutOverlapping();
        $schedule->command('queue:work --tries=3')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Check pending COD remittances scheduled for today - runs daily at 9:00 AM
        $schedule->command('remittance:check-pending')
            ->dailyAt('09:00')
            ->withoutOverlapping();

        // Update Delhivery token for ID 5
        $schedule->command('update:delhivery-token')
            ->dailyAt('23:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
