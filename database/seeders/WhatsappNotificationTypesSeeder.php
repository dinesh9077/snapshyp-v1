<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsappNotificationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'type' => 'order_confirmation',
                'name' => 'Order Confirmation',
                'message_template' => 'Your order has been confirmed and will be processed soon. Preview',
                'price' => 1.00,
                'is_free' => 0,
                'is_enabled' => 1,
                'is_recommended' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'shipment_booked',
                'name' => 'Shipment Booked',
                'message_template' => 'Your shipment has been booked successfully. Preview',
                'price' => 1.00,
                'is_free' => 0,
                'is_enabled' => 1,
                'is_recommended' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'shipment_pickup',
                'name' => 'Shipment Pickup',
                'message_template' => 'Your shipment has been picked up from the origin. Preview',
                'price' => 1.00,
                'is_free' => 0,
                'is_enabled' => 1,
                'is_recommended' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'shipment_arrived',
                'name' => 'Shipment Arrived at Final Destination',
                'message_template' => 'Your shipment has reached the final destination center. Preview',
                'price' => 1.00,
                'is_free' => 0,
                'is_enabled' => 0,
                'is_recommended' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'shipment_out_for_delivery',
                'name' => 'Shipment Out for Delivery',
                'message_template' => 'Your shipment is out for delivery today.',
                'price' => 0.00,
                'is_free' => 1,
                'is_enabled' => 0,
                'is_recommended' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'shipment_delivered',
                'name' => 'Shipment Delivered',
                'message_template' => 'Your shipment has been delivered successfully.',
                'price' => 0.00,
                'is_free' => 1,
                'is_enabled' => 0,
                'is_recommended' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('whatsapp_notification_types')->insert($types);

        // Create default WhatsApp settings
        DB::table('whatsapp_settings')->insert([
            'api_key' => null,
            'api_url' => null,
            'api_phone_number' => null,
            'is_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
