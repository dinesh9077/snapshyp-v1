<?php

namespace App\Http\Controllers;

use App\Models\CodSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CodSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (auth()->user()->role === "user") {
            return abort(403, "permission denied");
        }
        $search = $request->get('search');
        $codSettings = CodSetting::when($search, function($query, $search) {
                return $query->where('plan_name', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('days')
            ->paginate(10);
            
        $holidays = \App\Models\CodRemittanceHoliday::orderBy('date', 'desc')->get();

        return view('cod-settings.index', compact('codSettings', 'holidays'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cod-settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_name' => 'required|string|max:255',
            'days' => 'required|integer|min:1|max:30',
            'percentage' => 'required|numeric|min:0|max:100',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? true : false;
        $data['is_default'] = $request->has('is_default') ? true : false;

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            CodSetting::where('is_default', true)->update(['is_default' => false]);
        }

        $codSetting = CodSetting::create($data);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'msg' => 'COD Setting created successfully!',
                'data' => $codSetting
            ]);
        }

        return redirect()->route('cod-settings.index')->with('success', 'COD Setting created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(CodSetting $codSetting)
    {
        return view('cod-settings.show', compact('codSetting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CodSetting $codSetting)
    {
        if (request()->ajax()) {
            return response()->json($codSetting);
        }
        return view('cod-settings.edit', compact('codSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CodSetting $codSetting)
    {
        $validator = Validator::make($request->all(), [
            'plan_name' => 'required|string|max:255',
            'days' => 'required|integer|min:1|max:30',
            'percentage' => 'required|numeric|min:0|max:100',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? true : false;
        $data['is_default'] = $request->has('is_default') ? true : false;

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            CodSetting::where('id', '!=', $codSetting->id)
                     ->where('is_default', true)
                     ->update(['is_default' => false]);
        }

        $codSetting->update($data);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'msg' => 'COD Setting updated successfully!',
                'data' => $codSetting
            ]);
        }

        return redirect()->route('cod-settings.index')->with('success', 'COD Setting updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CodSetting $codSetting)
    {
        $codSetting->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'COD Setting deleted successfully!'
        ]);
    }


    /**
     * Toggle active status
     */
    public function toggleStatus(CodSetting $codSetting)
    {
        $codSetting->update(['is_active' => !$codSetting->is_active]);

        return response()->json([
            'status' => 'success',
            'msg' => 'Status updated successfully!',
            'is_active' => $codSetting->is_active
        ]);
    }

    /**
     * Set as default
     */
    public function setDefault(CodSetting $codSetting)
    {
        $codSetting->setAsDefault();

        return response()->json([
            'status' => 'success',
            'msg' => 'Default setting updated successfully!'
        ]);
    }

    /**
     * Get COD Terms
     */
    public function getTerms()
    {
        $codTerm = config('setting.cod_term', '');
        
        return response()->json([
            'status' => 'success',
            'msg' => 'Terms and Conditions fetched successfully!',
            'data' => $codTerm
        ]);
    }

    /**
     * Store/Update COD Terms
     */
    public function storeTerms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cod_term' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if setting exists
            $setting = \DB::table('settings')->where('name', 'cod_term')->first();
            
            if ($setting) {
                // Update existing setting
                \DB::table('settings')
                    ->where('name', 'cod_term')
                    ->update([
                        'value' => $request->cod_term,
                        'updated_at' => now()
                    ]);
            } else {
                // Create new setting
                \DB::table('settings')->insert([
                    'name' => 'cod_term',
                    'value' => $request->cod_term,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
  
            return response()->json([
                'status' => 'success',
                'msg' => 'Terms and Conditions saved successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to save terms and conditions: ' . $e->getMessage()
            ], 500);
        }
    }

    // Holiday Management Methods

    /**
     * Store a newly created holiday in storage.
     */
    public function storeHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|unique:cod_remittance_holidays,date',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $holiday = \App\Models\CodRemittanceHoliday::create([
            'date' => $request->date,
            'description' => $request->description
        ]);

        return response()->json([
            'status' => 'success',
            'msg' => 'Holiday added successfully!',
            'data' => $holiday
        ]);
    }

    /**
     * Remove the specified holiday from storage.
     */
    public function destroyHoliday($id)
    {
        $holiday = \App\Models\CodRemittanceHoliday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Holiday deleted successfully!'
        ]);
    }
}
