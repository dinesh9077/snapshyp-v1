<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\WeightDimension;
use Illuminate\Support\Facades\Validator;

class WeightDimensionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('weight-dimension.index');
    }

    public function getWeightDimensionAjax(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $limit = $request->get("length");

        $order_arr = $request->get('order');
        $column_arr = $request->get('columns');
        $search_arr = $request->get('search');
        $searchValue = $search_arr['value'] ?? '';

        $columnIndex = $order_arr[0]['column'];
        $columnName = $column_arr[$columnIndex]['data'];
        $orderColumn = $columnName == 'DT_RowIndex' ? 'id' : $columnName; // Map DT_RowIndex to id or handle sorting
        if ($columnName == 'action') $orderColumn = 'id';

        $dir = $order_arr[0]['dir'];

        $query = WeightDimension::query();

        // Total records
        $totalData = $query->count();

        // Search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('slab', 'LIKE', "%{$searchValue}%")
                    ->orWhere('length', 'LIKE', "%{$searchValue}%")
                    ->orWhere('width', 'LIKE', "%{$searchValue}%")
                    ->orWhere('height', 'LIKE', "%{$searchValue}%");
            });
        }

        $totalFiltered = $query->count();

        // Order
        if ($orderColumn) {
            $query->orderBy($orderColumn, $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        if ($limit != -1) {
            $query->offset($start)->limit($limit);
        }

        $data = $query->get();

        $response_data = [];
        foreach ($data as $index => $row) {

            $status = $row->status == 1
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-danger">Inactive</span>';

            $action = '<a href="javascript:void(0)" data-id="' . $row->id . '" class="edit btn btn-icon waves-effect waves-light action-icon mr-1 editWeightDimension"><i class="mdi mdi-pencil"></i></a>';
            $action .= ' <a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-icon waves-effect waves-light action-icon deleteWeightDimension"><i class="mdi mdi-trash-can-outline"></i></a>';

            $response_data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'slab' => $row->slab . ' KG',
                'length' => $row->length,
                'width' => $row->width,
                'height' => $row->height,
                'status' => $status,
                'action' => $action
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "iTotalRecords" => $totalData,
            "iTotalDisplayRecords" => $totalFiltered,
            "aaData" => $response_data
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slab' => 'required|unique:weight_dimensions,slab,' . $request->id,
            'length' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
        }

        WeightDimension::updateOrCreate(
            ['id' => $request->id],
            [
                'slab' => $request->slab,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'status' => $request->status ?? 1
            ]
        );

        return response()->json(['status' => 'success', 'msg' => 'Weight Dimension saved successfully.']);
    }

    public function edit($id)
    {
        $data = WeightDimension::find($id);
        return response()->json($data);
    }

    public function getBySlab(Request $request)
    {
        $slab = $request->get('slab');
        if (!$slab) {
            return response()->json(['status' => 'error', 'msg' => 'Slab is required.']);
        }

        $data = WeightDimension::where('slab', $slab)->where('status', 1)->first();
        if ($data) {
            return response()->json(['status' => 'success', 'data' => $data]);
        }

        return response()->json(['status' => 'error', 'msg' => 'No dimension found for this slab.']);
    }

    public function destroy($id)
    {
        WeightDimension::find($id)->delete();
        return response()->json(['status' => 'success', 'msg' => 'Weight Dimension deleted successfully.']);
    }
}

