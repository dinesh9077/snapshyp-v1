<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CodSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class CodPlanController extends Controller
{
    use ApiResponse;

    /**
     * Display COD plans for user
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $codPlans = CodSetting::where('is_active', true)->orderBy('days')->get();
            $currentPlanId = $user->cod_plan_active ? $user->cod_plan_id : null;
            $codTerms = config('setting.cod_term', '');

            $currentPlanDetails = null;
            if ($currentPlanId) {
                $currentPlanDetails = $codPlans->firstWhere('id', $currentPlanId);
            }

            $data = [
                'codPlans' => $codPlans,
                'currentPlanId' => $currentPlanId,
                'currentPlanDetails' => $currentPlanDetails,
                'codTerms' => $codTerms
            ];

            return $this->successResponse($data, 'COD plans fetched successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage());
        }
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
                return $this->validateResponse('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $plan = CodSetting::where('id', $request->plan_id)
                             ->where('is_active', true)
                             ->first();

            if (!$plan) {
                return $this->errorResponse('Selected plan is not available', 404);
            }

            // Update user's COD plan
            $updated = $user->update([
                'cod_plan_id' => $plan->id,
                'cod_plan_active' => true
            ]);

            if (!$updated) {
                return $this->errorResponse('Failed to activate plan. Please try again.', 500);
            }

            return $this->successResponse([
                'plan_id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'percentage' => $plan->percentage
            ], 'COD Plan activated successfully!');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage());
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
                return $this->errorResponse('No active COD plan found', 400);
            }
            
            $updated = $user->update([
                'cod_plan_active' => false
            ]);

            if (!$updated) {
                return $this->errorResponse('Failed to deactivate plan. Please try again.', 500);
            }

            return $this->successResponse([], 'COD Plan deactivated successfully!');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Get user's current plan details
     */
    public function getCurrentPlan()
    {
        try {
            $user = Auth::user();
            
            if ($user->cod_plan_id && $user->cod_plan_active) {
                $plan = CodSetting::find($user->cod_plan_id);
                if ($plan) {
                    return $this->successResponse([
                        'data' => $plan,
                        'active' => true
                    ], 'Current plan retrieved successfully');
                }
            }

            return $this->successResponse([
                'data' => null,
                'active' => false
            ], 'No active plan found');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage());
        }
    }
}
