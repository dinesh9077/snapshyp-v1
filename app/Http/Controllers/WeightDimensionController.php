<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\WeightDimension;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WeightDimensionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // The seller pages (setting/weight-dimension) are for sellers only: an admin there would
        // otherwise reach the global slabs without the general_setting permission
        $this->middleware(function ($request, $next) {
            abort_if($request->is('setting/weight-dimension*') && !$this->isSeller(), 403);
            return $next($request);
        });
    }

    public function index()
    {
        abort_if($this->isSeller(), 403);
        $sellers = $this->manageableSellers()->get(['id', 'name', 'company_name', 'default_package_weight']);
        $isSellerPage = false;
        return view('weight-dimension.index', compact('sellers', 'isSellerPage'));
    }

    // Seller's own page (Setting > Weight Dimension): only their slabs and default weight
    public function sellerIndex()
    {
        abort_unless($this->isSeller(), 403);
        $sellers = collect();
        $isSellerPage = true;
        $defaultWeight = Auth::user()->default_package_weight;
        return view('weight-dimension.index', compact('sellers', 'isSellerPage', 'defaultWeight'));
    }

    private function isSeller(): bool
    {
        return Auth::user()->role === 'user';
    }

    // Sellers whose slabs this admin may manage: all for the master admin, else their own sellers
    private function manageableSellers()
    {
        $user = Auth::user();
        return User::where('role', 'user')
            ->when(!$user->isMasterAdmin(), fn ($q) => $q->where('staff_id', $user->id))
            ->orderBy('company_name');
    }

    // Requested seller id when it is one this admin manages; null = the global slabs
    private function sellerScope(Request $request): ?int
    {
        // A seller always works on their own slabs, whatever the request says
        if ($this->isSeller()) {
            return (int) Auth::id();
        }

        $sellerId = (int) $request->input('user_id');
        if ($sellerId <= 0) {
            return null;
        }
        abort_unless($this->manageableSellers()->whereKey($sellerId)->exists(), 403);
        return $sellerId;
    }

    // A slab row this admin may change: a global one, or one of their sellers'
    private function findManageable($id): WeightDimension
    {
        $row = WeightDimension::findOrFail($id);
        if ($this->isSeller()) {
            // Sellers can change only their own slabs, never the global ones
            abort_unless((int) $row->user_id === (int) Auth::id(), 403);
        } elseif ($row->user_id) {
            abort_unless($this->manageableSellers()->whereKey($row->user_id)->exists(), 403);
        }
        return $row;
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

        $sellerId = $this->sellerScope($request);
        $query = WeightDimension::query()
            ->when($sellerId, fn ($q) => $q->where('user_id', $sellerId), fn ($q) => $q->whereNull('user_id'));

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
        // An edited slab keeps its owner; a new one belongs to the seller picked on the page (or global)
        $existing = $request->id ? $this->findManageable($request->id) : null;
        $sellerId = $existing ? $existing->user_id : $this->sellerScope($request);

        $validator = Validator::make($request->all(), [
            'slab' => [
                'required', 'numeric', 'gt:0',
                Rule::unique('weight_dimensions', 'slab')
                    ->where(fn ($q) => $sellerId ? $q->where('user_id', $sellerId) : $q->whereNull('user_id'))
                    ->ignore($existing->id ?? null),
            ],
            'length' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
        }

        WeightDimension::updateOrCreate(
            ['id' => $existing->id ?? null],
            [
                'user_id' => $sellerId,
                'slab' => $request->slab,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'status' => $request->status ?? 1
            ]
        );

        return response()->json(['status' => 'success', 'msg' => 'Weight Dimension saved successfully.']);
    }

    // Weight used for a seller's orders that arrive without one (Shopify products with no weight)
    public function saveDefaultWeight(Request $request)
    {
        $sellerId = $this->sellerScope($request);
        if (!$sellerId) {
            return response()->json(['status' => 'error', 'msg' => 'Please select a seller.']);
        }

        $validator = Validator::make($request->all(), [
            'default_package_weight' => 'nullable|numeric|min:0|max:999',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
        }

        $weight = (float) $request->input('default_package_weight');
        User::whereKey($sellerId)->update(['default_package_weight' => $weight > 0 ? $weight : null]);

        return response()->json([
            'status' => 'success',
            'msg' => $weight > 0 ? 'Default weight saved.' : 'Default weight removed.',
        ]);
    }

    public function edit($id)
    {
        $data = $this->findManageable($id);
        return response()->json($data);
    }

    // Order form auto-fill: the seller's own slab first, then the global one
    public function getBySlab(Request $request)
    {
        $slab = $request->get('slab');
        if (!$slab) {
            return response()->json(['status' => 'error', 'msg' => 'Slab is required.']);
        }

        $user = Auth::user();
        $sellerId = $user->role === 'user' ? (int) $user->id : (int) $request->get('user_id');

        $data = WeightDimension::forWeight($sellerId ?: null, $slab, true);
        if ($data) {
            return response()->json(['status' => 'success', 'data' => $data]);
        }

        return response()->json(['status' => 'error', 'msg' => 'No dimension found for this slab.']);
    }

    public function destroy($id)
    {
        $this->findManageable($id)->delete();
        return response()->json(['status' => 'success', 'msg' => 'Weight Dimension deleted successfully.']);
    }
}
