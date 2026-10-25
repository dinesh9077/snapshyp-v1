<?php

namespace App\Http\Controllers;

use App\Models\Zipcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ZipcodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('zipcode.index');
    }

    public function ajaxZipcode(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $limit = $request->get("length");

        $order_arr = $request->get('order');
        $column_arr = $request->get('columns');
        $search = $request->get('search') ?? $request->get('search.value') ?? '';

        $columnIndex = $order_arr[0]['column'];
        $orderColumn = $column_arr[$columnIndex]['data'];
        $dir = $order_arr[0]['dir'];

        // Get filter values
        $filter_city = $request->get('filter_city');
        $filter_state = $request->get('filter_state');

        // Base Query
        $query = Zipcode::query();

        $totalData = $query->count();

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pincode', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('state', 'LIKE', "%{$search}%");
            });
        }

        // Apply city filter
        if (!empty($filter_city)) {
            $query->where('city', $filter_city);
        }

        // Apply state filter
        if (!empty($filter_state)) {
            $query->where('state', $filter_state);
        }

        $totalFiltered = $query->count();

        // Fetch paginated records
        $zipcodes = $query->offset($start)
            ->limit($limit)
            ->orderBy($orderColumn, $dir)
            ->get();

        $data = [];
        foreach ($zipcodes as $index => $zipcode) {
            $actionButtons = '';

            if (config('permission.zipcode.edit')) {
                $actionButtons .= '<a href="javascript:void(0);" data-id="' . $zipcode->id . '" 
                    class="btn btn-icon waves-effect waves-light action-icon mr-1 edit-zipcode">
                    <i class="mdi mdi-pencil"></i>
                </a>';
            }

            if (config('permission.zipcode.delete')) {
                $actionButtons .= '<a href="' . route('zipcode.delete', $zipcode->id) . '" 
                    class="btn btn-icon waves-effect waves-light action-icon" 
                    data-toggle="tooltip" data-placement="bottom" title="Delete" 
                    onClick="deleteRecord(this, event);"> 
                    <i class="mdi mdi-trash-can-outline"></i> 
                </a>';
            }

            $data[] = [
                'id' => $start + $index + 1,
                'pincode' => $zipcode->pincode,
                'city' => $zipcode->city,
                'state' => $zipcode->state,
                'action' => $actionButtons
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "iTotalRecords" => $totalData,
            "iTotalDisplayRecords" => $totalFiltered,
            "aaData" => $data
        ]);
    }

    public function getFilterOptions(Request $request)
    {
        $state = trim((string) $request->input('state', ''));
        $includeCities = filter_var($request->input('include_cities', true), FILTER_VALIDATE_BOOLEAN);

        $states = Cache::remember('zipcode_filter_states', now()->addHours(6), function () {
            return Zipcode::distinct()->pluck('state')->filter()->sort()->values();
        });

        $cities = collect();
        if ($includeCities) {
            $cacheKey = 'zipcode_filter_cities_' . md5($state);
            $cities = Cache::remember($cacheKey, now()->addHours(6), function () use ($state) {
                $query = Zipcode::query();

                if ($state !== '') {
                    $query->where('state', $state);
                }

                return $query->distinct()->pluck('city')->filter()->sort()->values();
            });
        }

        return response()->json([
            'cities' => $cities,
            'states' => $states
        ]);
    }
    
    public function select2Ajax(Request $request) {
        $search = $request->get('q');
        $query = Zipcode::query();
        if ($search) {
            $query->where('pincode', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
        }
        $zipcodes = $query->limit(50)->get();
        $results = [];
        foreach ($zipcodes as $z) {
            $results[] = ['id' => $z->pincode, 'text' => $z->pincode . ' - ' . $z->city . ' (' . $z->state . ')'];
        }
        return response()->json(['results' => $results]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pincode' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);

        try {
            Zipcode::create($request->all());
            return response()->json(['status' => 'success', 'msg' => 'Zipcode added successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        try {
            $zipcode = Zipcode::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $zipcode
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => 'Zipcode not found.']);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pincode' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);

        try {
            $zipcode = Zipcode::findOrFail($id);
            $zipcode->update($request->all());
            return response()->json(['status' => 'success', 'msg' => 'Zipcode updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $zipcode = Zipcode::findOrFail($id);
            $zipcode->delete();
            return response()->json(['status' => 'success', 'msg' => 'Zipcode deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => 'Something went wrong.']);
        }
    }
}
