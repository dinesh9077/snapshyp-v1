<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemittanceTestOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Remittance Test Orders Seeder...');

        // Get a test user (or create one)
        $user = User::where('role', 'user')->first();

        if (!$user) {
            $this->command->error('No user found! Please create a user first.');
            return;
        }

        $this->command->info("Using User ID: {$user->id} ({$user->name})");

        $this->command->info('Creating orders from 10 days before to 15 days after today...');

        $ordersCreated = 0;
        $codOrdersCount = 0;
        $prepaidOrdersCount = 0;
        $today = Carbon::today();

        // Create orders from 10 days before to 15 days after (total 25 days)
        for ($day = -10; $day <= 15; $day++) {
            $deliveryDate = $today->copy()->addDays($day);

            // Create 2-5 random orders per day
            $ordersPerDay = rand(2, 5);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $invoiceAmount = rand(500, 5000);
                $orderPrefix = 'TEST' . time() . rand(1000, 9999);

                // 70% COD orders, 30% Prepaid orders
                $isCod = rand(1, 100) <= 70;
                $orderType = $isCod ? 'cod' : 'prepaid';

                // For future dates, set status as pending/in-transit
                // For past dates, set as delivered
                $statusCourier = 'delivered';

                try {
                    DB::table('orders')->insert([
                        'user_id' => $user->id,
                        'customer_id' => 6, // Default customer ID
                        'customer_address_id' => 6, // Default customer ID
                        'warehouse_id' => 2, // Default customer ID
                        'order_prefix' => $orderPrefix,
                        'order_type' => $orderType,
                        'shipping_mode' => 'surface',
                        'status_courier' => $statusCourier,
                        'invoice_amount' => $invoiceAmount,
                        'cod_amount' => $isCod ? $invoiceAmount : 0,
                        'weight' => rand(1, 10),
                        'length' => rand(10, 50),
                        'width' => rand(10, 50),
                        'height' => rand(10, 50),
                        'weight_order' => 1,
                        'order_date' => $deliveryDate->format('Y-m-d'),
                        'delivery_date' => $deliveryDate->format('Y-m-d'),
                        'awb_number' => 'AWB' . time() . rand(1000, 9999),
                        'courier_name' => 'Test Courier',
                        'status' => 1,
                        'created_at' => $deliveryDate->copy()->subDays(2)->format('Y-m-d H:i:s'),
                        'updated_at' => $deliveryDate->format('Y-m-d H:i:s'),
                    ]);

                    $ordersCreated++;

                    if ($isCod) {
                        $codOrdersCount++;
                    } else {
                        $prepaidOrdersCount++;
                    }
                } catch (\Exception $e) {
                    $this->command->error("Failed to create order: " . $e->getMessage());
                }
            }

            $dateLabel = $day < 0 ? "Day {$day}" : ($day == 0 ? "Today" : "Day +{$day}");
            $this->command->info("{$dateLabel}: Created {$ordersPerDay} orders for {$deliveryDate->format('Y-m-d')}");
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✓ Seeder completed!");
        $this->command->info("Total orders created: {$ordersCreated}");
        $this->command->info("  - COD Orders: {$codOrdersCount}");
        $this->command->info("  - Prepaid Orders: {$prepaidOrdersCount}");
        $this->command->info("User ID: {$user->id}");
        $this->command->info("Date range: {$today->copy()->subDays(10)->format('Y-m-d')} to {$today->copy()->addDays(15)->format('Y-m-d')}");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("\n✅ Past orders (10 days before): Status = 'Delivered'");
        $this->command->info("✅ Future orders (15 days after): Status = 'In Transit' or 'Out For Delivery'");
        $this->command->info("\n📦 Now run: php artisan remittance:generate-payout");
    }
}
