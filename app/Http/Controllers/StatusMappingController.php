<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatusMapping;
use App\Models\ShippingCompany;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StatusMappingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the status mappings.
     */
    public function index(Request $request)
    {
        if (auth()->user()->role === "user") {
            return abort(403, "permission denied");
        }
        $statusMappings = StatusMapping::pluck('courier_status', 'internal_status')->toArray(); 
        $internalStatuses = StatusMapping::getInternalStatuses();
        
        return view('setting.status-mapping.index', compact('statusMappings', 'internalStatuses'));
    }

    /**
     * Store status mappings for single internal status
     */
    public function store(Request $request)
    { 
        try {
            DB::beginTransaction();
         
            foreach ($request->internal_status as $key => $internalStatus) {
                $courierStatuses = isset($request->courier_status[$key])
                    ? $request->courier_status[$key]
                    : null;
               
                $courierStatuses = $courierStatuses ? explode(',', $courierStatuses) : [];

                StatusMapping::updateOrCreate(
                    [
                        'internal_status' => $internalStatus,
                    ],
                    [
                        'courier_status' => $courierStatuses 
                    ]
                );
            }

            DB::commit();

            return redirect()
                ->route('status-mapping.index')
                ->with('success', 'Status mappings saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }


    /**
     * Get existing mappings
     */
    public function getMappings(Request $request)
    {
        $mappings = StatusMapping::all()->groupBy('internal_status');
        return response()->json(['mappings' => $mappings]);
    }

    /**
     * Add new internal status
     */
    public function addInternalStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'internal_status' => 'required|string|max:50|unique:status_mappings,internal_status'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status already exists or invalid'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Internal status can be added',
                'internal_status' => $request->internal_status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * Get all unique courier statuses from orders
     */
    public function getCourierStatusSuggestions(Request $request)
    {
        try {
            // Get all unique courier statuses from orders
            $courierStatuses = Order::whereNotNull('status_courier')
                                  ->where('status_courier', '!=', '')
                                  ->distinct()
                                  ->pluck('status_courier')
                                  ->filter()
                                  ->values()
                                  ->toArray();

            // Also get existing mapped courier statuses
            $mappedStatuses = StatusMapping::distinct()
                                         ->pluck('courier_status')
                                         ->filter()
                                         ->values()
                                         ->toArray();

            // Merge and remove duplicates
            $allStatuses = array_unique(array_merge($courierStatuses, $mappedStatuses));
            sort($allStatuses);

            return response()->json([
                'success' => true,
                'statuses' => array_values($allStatuses)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading courier status suggestions',
                'statuses' => []
            ]);
        }
    }

    /**
     * Delete mapping
     */
    public function destroy($id)
    {
        try {
            $mapping = StatusMapping::findOrFail($id);
            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Status mapping deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the status mapping.'
            ]);
        }
    }
}
