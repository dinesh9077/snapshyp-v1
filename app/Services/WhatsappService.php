<?php

namespace App\Services;

use App\Models\WhatsappSetting;
use App\Models\WhatsappNotificationType;
use App\Models\WhatsappNotificationLog;
use App\Models\UserWhatsappService;
use App\Models\Billing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = WhatsappSetting::getSettings();
    }

    /**
     * Send WhatsApp notification for an order.
     *
     * @param Order $order
     * @param string $notificationType
     * @return array
     */
    public function sendOrderNotification(Order $order, $notificationType)
    {
        // Check if WhatsApp is enabled globally
        if (!$this->settings->is_enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp notifications are disabled.'
            ];
        }

        // Get notification type settings
        $notificationConfig = WhatsappNotificationType::getByType($notificationType);

        if (!$notificationConfig || !$notificationConfig->is_enabled) {
            return [
                'success' => false,
                'message' => 'This notification type is disabled by admin.'
            ];
        }

        // Get user
        $user = $order->user;

        if($user->role === 'user')
        {
            // Check if user has purchased this service
            $userService = UserWhatsappService::where('user_id', $user->id)
                ->where('notification_type_id', $notificationConfig->id)
                ->first();

            if (!$userService) {
                return [
                    'success' => false,
                    'message' => 'User has not purchased this WhatsApp service.'
                ];
            }
        } 

       
        // Check if user has activated this service
        // if (!$userService->is_active) {
        //     return [
        //         'success' => false,
        //         'message' => 'User has deactivated this WhatsApp service.'
        //     ];
        // }

        // Get customer phone number
        $customer = $order->customer;
        if (!$customer || !$customer->mobile) {
            return [
                'success' => false,
                'message' => 'Customer phone number not found.'
            ];
        }

        // Prepare message
        $components = $this->prepareComponents($order, $notificationType);
        if(!$components)
        {
            return [
                'success' => false,
                'message' => 'Failed to prepare components for WhatsApp notification.'
            ];
        }
        
        // Check user wallet for paid notifications
        if (!$notificationConfig->is_free && $user->role === 'user') {
            if ($user->wallet_amount < $notificationConfig->price) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance for WhatsApp notification.'
                ];
            }
        }

        // Send WhatsApp message
        $response = $this->sendMessage($customer->mobile, $notificationType, $components);

        // Log the notification
        $log = WhatsappNotificationLog::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'notification_type' => $notificationType,
            'phone_number' => $customer->mobile,
            'message' => json_encode($components),
            'charge' => $notificationConfig->is_free ? 0 : $notificationConfig->price,
            'status' => $response['success'] ? 'sent' : 'failed',
            'response' => json_encode($response),
        ]);
 
        // Deduct wallet balance if paid and successful
        if ($response['success'] && !$notificationConfig->is_free && $user->role === 'user') { 
            User::whereId($user->id)->decrement('wallet_amount', $notificationConfig->price);

            // Create billing record
            Billing::create([
                'user_id' => $user->id,
                'billing_type' => 'WhatsApp',
                'billing_type_id' => $order->id,
                'transaction_type' => 'debit',
                'amount' => $notificationConfig->price,
                'note' => "WhatsApp notification sent for {$notificationConfig->name} — Order No. #{$order->order_manual_id}",
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return $response;
    }

    /**
     * Send WhatsApp message via API.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    protected function sendMessage($phoneNumber, $templateName, $components = [])
    {
        if (!$this->settings->api_url || !$this->settings->api_key) {
            return [
                'success' => false,
                'message' => 'WhatsApp API credentials not configured.'
            ];
        }
        
        try {
            // Format phone number (remove spaces and special characters)
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            $data = [
                "token" => $this->settings->api_key,
                "phone" => "91" . $phoneNumber,
                //"phone" => "916351597669",
                "template_name" => $templateName,
                "template_language" => "en",
                "components" => $components
            ];
                
            // Make API request (adjust according to your WhatsApp API provider)
            $response = Http::withHeaders([ 
                'Content-Type' => 'application/json',
            ])->post($this->settings->api_url.'/api/wpbox/sendtemplatemessage', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully.',
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp message.',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp API Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error sending WhatsApp message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prepare message with dynamic data.
     *
     * @param Order $order
     * @param string $template
     * @return string
     */
    protected function prepareComponents($order, $notificationType)
    {
        // Define notification groups for clarity
        $deliveredTypes = ['delivered', 'manifest_created'];
        $outForDeliveryTypes = ['out_for_delivery', 'in_transit__reached_hub', 'rto_return_to_origin'];

        if (in_array($notificationType, $deliveredTypes, true)) {
            return $this->deliveredComponents($order);
        }

        if (in_array($notificationType, $outForDeliveryTypes, true)) {
            return $this->outforDeliveryComponents($order);
        }
		
        if (in_array($notificationType, ['shipment_picked_up'], true)) {
            return $this->shipmentPickupComponents($order);
        }

        return []; // fallback if type not matched
    }

    private function shipmentPickupComponents($order)
    {
        $customer = $order->customer;
        $user = $order->user;
        $shop = $user?->shopDetail;
        $warehouse = $order->warehouse;

        return [
            [
                'type' => 'BODY',
                'parameters' => [
					['type' => 'text', 'text' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Customer'],
					['type' => 'text', 'text' => $order->order_manual_id ?? 'N/A'],
					['type' => 'text', 'text' => $order->awb_number ?? 'N/A'],
				],
            ],
        ];
    }
	
    private function deliveredComponents($order)
    {
        $customer = $order->customer;
        $user = $order->user;
        $shop = $user?->shopDetail;
        $warehouse = $order->warehouse;

        return [
            [
                'type' => 'BODY',
                'parameters' => [
					['type' => 'text', 'text' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Customer'],
					['type' => 'text', 'text' => $order->order_manual_id ?? 'N/A'],
					['type' => 'text', 'text' => $shop->shop_name ?? $warehouse->warehouse_name ?? 'N/A'],
				],
            ],
        ];
    }

    private function outforDeliveryComponents($order)
    {
        $customer = $order->customer;

        return [
            [
                'type' => 'BODY',
                'parameters' => [
                        ['type' => 'text', 'text' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Customer'],
                        ['type' => 'text', 'text' => $order->order_manual_id ?? 'N/A'],
                    ],
            ],
        ];
    }

}
