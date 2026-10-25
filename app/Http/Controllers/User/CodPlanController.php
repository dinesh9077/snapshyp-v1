<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CodSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CodPlanController extends Controller
{
    /**
     * Display COD plans for user
     */
    public function index()
    {
        $user = Auth::user();
        $codPlans = CodSetting::where('is_active', true)->orderBy('days')->get();
        $currentPlan = $user->cod_plan_active ? $user->cod_plan_id : null;
        $codTerms = config('setting.cod_term', '');
        
        return view('user.cod-plans.index', compact('codPlans', 'currentPlan', 'codTerms', 'user'));
    }

    /**
     * Activate a COD plan for user
     */
    public function activate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|exists:cod_settings,id',
                'terms_accepted' => 'required|accepted'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $plan = CodSetting::where('id', $request->plan_id)
                             ->where('is_active', true)
                             ->first();

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Selected plan is not available'
                ], 404);
            }

            // Update user's COD plan
            $updated = $user->update([
                'cod_plan_id' => $plan->id,
                'cod_plan_active' => true
            ]);

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Failed to activate plan. Please try again.'
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'msg' => 'COD Plan activated successfully!',
                'data' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'percentage' => $plan->percentage
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'msg' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate user's COD plan
     */
    public function deactivate()
    {
        try {
            $user = Auth::user();
            
            if (!$user->cod_plan_active) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'No active COD plan found'
                ], 400);
            }
            
            $updated = $user->update([
                'cod_plan_active' => false
            ]);

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Failed to deactivate plan. Please try again.'
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'msg' => 'COD Plan deactivated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'msg' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's current plan details
     */
    public function getCurrentPlan()
    {
        $user = Auth::user();
        
        if ($user->cod_plan_id && $user->cod_plan_active) {
            $plan = CodSetting::find($user->cod_plan_id);
            if ($plan) {
                return response()->json([
                    'status' => 'success',
                    'data' => $plan,
                    'active' => true,
                    'msg' => 'Current plan retrieved successfully'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => null,
            'active' => false,
            'msg' => 'No active plan found'
        ]);
    }
}
