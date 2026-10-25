<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappSetting;
use App\Models\WhatsappNotificationType;
use App\Models\WhatsappNotificationLog;
use App\Models\UserWhatsappService;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class WhatsappSettingController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        $this->middleware('auth');   
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display WhatsApp settings page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    { 
        if(auth()->user()->role === "user"){
            return abort(403, "permission denied");
        }
        $settings = WhatsappSetting::getSettings();
        $notificationTypes = WhatsappNotificationType::all();

        return view('setting.whatsapp', compact('settings', 'notificationTypes'));
    }

    /**
     * Update WhatsApp API settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'nullable|string|max:255',
            'api_url' => 'nullable|string|max:255',
            'api_phone_number' => 'nullable|string|max:20',
            'is_enabled' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $settings = WhatsappSetting::getSettings();
        $settings->update([
            'api_key' => $request->api_key,
            'api_url' => $request->api_url,
            'api_phone_number' => $request->api_phone_number,
            'is_enabled' => $request->is_enabled ? 1 : 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'WhatsApp settings updated successfully.'
        ]);
    }

    /**
     * Get notification type details.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationType($id)
    {
        $notificationType = WhatsappNotificationType::findOrFail($id);
        return response()->json($notificationType);
    }

    /**
     * Update notification type settings.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationType(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|max:255',
            'name' => 'required|string|max:255', 
            'message_template' => 'required|string',
            'pricing_type' => 'required|in:paid,free',
            'price' => 'nullable|numeric|min:0',
            'is_free' => 'nullable',
            'is_enabled' => 'nullable',
            'is_recommended' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Determine if it's free based on pricing type
        $isFree = ($request->pricing_type === 'free') ? 1 : 0;
        $price = $isFree ? 0 : ($request->price ?? 0);

        // Validate price for paid notifications
        if ($request->pricing_type === 'paid' && (!$request->has('price') || $request->price <= 0)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Price is required for paid notifications and must be greater than 0.'
            ], 422);
        }

        $notificationType = WhatsappNotificationType::findOrFail($id);
        $notificationType->update([ 
            'name' => $request->name,
            'message_template' => $request->message_template,
            'price' => $price,
            'is_free' => $isFree,
            'is_enabled' => $request->is_enabled ? 1 : 0,
            'is_recommended' => $request->is_recommended ? 1 : 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification type updated successfully.',
            'data' => [
                'id' => $notificationType->id,
                'price' => $notificationType->price,
                'is_free' => $notificationType->is_free,
                'is_enabled' => $notificationType->is_enabled,
            ]
        ]);
    }

    /**
     * Toggle notification type status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleNotificationType(Request $request, $id)
    {
        $notificationType = WhatsappNotificationType::findOrFail($id);
        $notificationType->is_enabled = !$notificationType->is_enabled;
        $notificationType->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification type ' . ($notificationType->is_enabled ? 'enabled' : 'disabled') . ' successfully.',
            'is_enabled' => $notificationType->is_enabled
        ]);
    }

    /**
     * Test WhatsApp API connection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $result = $this->whatsappService->testConnection($request->phone_number);

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message']
        ]);
    }

    /**
     * Get notification logs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request)
    {
        $logs = WhatsappNotificationLog::with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'logs' => $logs
        ]);
    }

    /**
     * Display user WhatsApp services page.
     *
     * @return \Illuminate\View\View
     */
    public function userIndex()
    {
        $userId = Auth::id();
        
        // Get all forward shipment notification types
        $notificationTypes = WhatsappNotificationType::where('is_enabled', 1)->get();
        
        // Get user's purchased services
        $purchasedServices = UserWhatsappService::where('user_id', $userId)
            ->pluck('notification_type_id')
            ->toArray();
            
        $activeServices = UserWhatsappService::where('user_id', $userId)
            ->where('is_active', 1)
            ->pluck('notification_type_id')
            ->toArray();
        
        // Add purchase status to each notification type
        $notificationTypes->map(function ($type) use ($purchasedServices, $activeServices) {
            $type->isPurchased = in_array($type->id, $purchasedServices);
            $type->isActive = in_array($type->id, $activeServices);
            return $type;
        });
        
        // Calculate statistics
        $totalPurchased = count($purchasedServices);
        $activePurchased = count($activeServices);
        $availableServices = $notificationTypes->count() - $totalPurchased;
        
        return view('setting.whatsapp-user', compact(
            'notificationTypes',
            'totalPurchased',
            'activePurchased',
            'availableServices'
        ));
    }

    /**
     * Add service for user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_type_id' => 'required|exists:whatsapp_notification_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $userId = Auth::id();
        $notificationTypeId = $request->notification_type_id;

        // Check if already purchased
        $exists = UserWhatsappService::where('user_id', $userId)
            ->where('notification_type_id', $notificationTypeId)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already purchased this service.'
            ], 422);
        }

        // Add service
        UserWhatsappService::create([
            'user_id' => $userId,
            'notification_type_id' => $notificationTypeId,
            'is_active' => 1,
            'purchased_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'WhatsApp service added successfully.'
        ]);
    }

    /**
     * Remove service for user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeService($id)
    {
        $userId = Auth::id();

        $service = UserWhatsappService::where('user_id', $userId)
            ->where('notification_type_id', $id)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found.'
            ], 404);
        }

        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'WhatsApp service removed successfully.'
        ]);
    }

    /**
     * Toggle user service active status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleUserService($id)
    {
        $userId = Auth::id();

        $service = UserWhatsappService::where('user_id', $userId)
            ->where('notification_type_id', $id)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found.'
            ], 404);
        }

        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Service ' . ($service->is_active ? 'activated' : 'deactivated') . ' successfully.',
            'is_active' => $service->is_active
        ]);
    }
}
