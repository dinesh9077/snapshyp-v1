<?php

namespace App\Http\Controllers;

use App\Models\CourierCommission;
use App\Models\ShippingCompany; 

use App\Models\UserCourierCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CourierCommissionController extends Controller
{
    // Admin-only screens; routes add the specific permission
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $commissions = CourierCommission::all();
        $shippingCompanies = ShippingCompany::select('id', 'name')->where('status',1)->get();
        return view('courier_commission.index', compact('commissions', 'shippingCompanies'));
    }

    public function verifyPasskey(Request $request)
    {
        $request->validate([
            'passkey' => 'required|string'
        ]);

        $user = auth()->user();

        // check hashed password
        if (Hash::check($request->passkey, $user->password)) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error']);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'commissions' => 'nullable|array',
            'commissions.*.id' => 'nullable|exists:courier_commissions,id',
            'commissions.*.shipping_company' => 'required|string',
            'commissions.*.courier_id' => 'required|string',
            'commissions.*.courier_name' => 'required|string',
            'commissions.*.type' => 'required|in:fix,percentage',
            'commissions.*.value' => 'required|numeric',
            'removed_commission_ids' => 'nullable|array',
            'removed_commission_ids.*' => 'integer|exists:courier_commissions,id',
        ]);

        $commissions = $data['commissions'] ?? [];
        $ids = array_filter(array_column($commissions, 'id'));
        $removedIds = collect($data['removed_commission_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Explicit deletes from removed rows
        if (!empty($removedIds)) {
            UserCourierCommission::whereIn('courier_commission_id', $removedIds)->delete();
            CourierCommission::whereIn('id', $removedIds)->delete();
        }

        // Keep legacy "diff" delete behavior as fallback
        CourierCommission::whereNotIn('id', $ids)->delete();
        foreach ($commissions as $commission) {
          
            if (!empty($commission['id'])) {
                // update existing
                CourierCommission::where('id', $commission['id'])->update([
                    'shipping_company' => $commission['shipping_company'],
                    'courier_id' => $commission['courier_id'],
                    'courier_name' => $commission['courier_name'],
                    'type' => $commission['type'],
                    'value' => $commission['value'],
                ]);
            } else {
                // create new
                CourierCommission::create([
                    'shipping_company' => $commission['shipping_company'],
                    'courier_id' => $commission['courier_id'],
                    'courier_name' => $commission['courier_name'],
                    'type' => $commission['type'],
                    'value' => $commission['value'],
                ]);
            }
        }

        return redirect()->route('courier.commission')
            ->with('success', 'Courier commissions updated successfully.');
    }

    public function userCommission($userId)
    {
        $commissions = CourierCommission::with(['userCommissions'=>function($q) use ($userId){
            $q->where('user_id', $userId);
        }])->get();
        
        $shippingCompanies = ShippingCompany::select('id', 'name')->where('status', 1)->get();
        return view('courier_commission.user_commission', compact('commissions', 'shippingCompanies', 'userId'));
    }

    public function userCommissionUpdate(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'commissions' => 'nullable|array',
            'commissions.*.id' => 'nullable|exists:courier_commissions,id',
            'commissions.*.shipping_company' => 'required|string',
            'commissions.*.courier_id' => 'required|string',
            'commissions.*.courier_name' => 'required|string',
            'commissions.*.type' => 'required|in:fix,percentage',
            'commissions.*.value' => 'required|numeric',
            'removed_commission_ids' => 'nullable|array',
            'removed_commission_ids.*' => 'integer|exists:courier_commissions,id',
        ]);

        $userId = (int) $data['user_id'];
        $commissions = $data['commissions'] ?? [];
        $submittedIds = collect($commissions)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Diff delete: anything missing from submitted rows should be removed from master table
        if ($submittedIds->isEmpty()) {
            UserCourierCommission::query()->delete();
            CourierCommission::query()->delete();
        } else {
            $ids = $submittedIds->all();
            UserCourierCommission::whereNotIn('courier_commission_id', $ids)->delete();
            CourierCommission::whereNotIn('id', $ids)->delete();
        }

        $removedIds = collect($data['removed_commission_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($removedIds->isNotEmpty()) {
            // Remove all user-level overrides tied to the removed master commission rows
            UserCourierCommission::whereIn('courier_commission_id', $removedIds->all())
                ->delete();

            // Remove master commission rows as requested
            CourierCommission::whereIn('id', $removedIds->all())
                ->delete();

            // Safety cleanup for the current user scope as well
            UserCourierCommission::where('user_id', $userId)
                ->whereIn('courier_commission_id', $removedIds->all())
                ->delete();
        }

        foreach ($commissions as $commission) {
            if (empty($commission['id'])) {
                continue;
            }

            UserCourierCommission::updateOrCreate(
                [
                    'user_id' => $userId,
                    'courier_commission_id' => $commission['id']
                ],
                [
                    'type' => $commission['type'],
                    'value' => $commission['value'],
                ]
            );
        }

        return redirect()->back()
            ->with('success', 'Courier commissions updated successfully.');
    }

}
