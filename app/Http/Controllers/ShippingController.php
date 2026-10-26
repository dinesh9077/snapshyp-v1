<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ShippingCompany;
use App\Models\ProductCategory;
use App\Models\CourierCommission;
use App\Models\Packaging;
use App\Models\RateCard;
use App\Models\RateCardDetail;
use App\Models\ZoneSetting;
use App\Models\ShippingWeight;
use App\Models\ShippingCompanyPincode;
use App\Models\ShippingCompanyOdaCharge;
use App\Models\ShippingCompanyOpaCharge;
use App\Models\RateCardMatrix;
use App\Models\OverheadCharge;
use App\Models\OtherChargeMaster;
use App\Models\RateCardOtherCharge;
use App\Models\Setting;
use App\Models\Zipcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\ShipMozo;
use App\Services\DelhiveryB2CService;
use App\Services\RateCardService;
use Carbon\Carbon;
use Helper;
use Illuminate\Support\Str;
use App\Exports\RateCardExport;
use App\Exports\RateCardB2BExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class ShippingController extends Controller
{

	protected $delhiveryService;
	protected $delhiveryB2CService;
	protected $shipMozo;
	protected $rateCardService;

	public function __construct()
	{
		$this->middleware('auth');
		$this->shipMozo = new ShipMozo();
		$this->delhiveryB2CService = new DelhiveryB2CService();
		$this->rateCardService = new RateCardService();
	}

	public function index()
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}
		$shippings = ShippingCompany::latest()->where('status', 1)->get();

		return view('shipping.index', compact('shippings'));
	}

	public function companyWorkspace(Request $request, $id)
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}

		$shippingCompany = ShippingCompany::findOrFail($id);

		$allowedTabs = [
			'freight-calculator',
			'zone-matrix',
			'admin-rate-matrix',
			'manage-pincodes',
			'other-charges-master',
		];
		$activeTab = (string) $request->query('tab', 'freight-calculator');
		if (!in_array($activeTab, $allowedTabs, true)) {
			$activeTab = 'freight-calculator';
		}

		$companyMeta = [
			'environment_label' => (int) $shippingCompany->mode === 1 ? 'Live' : 'Test',
			'status_label' => (int) $shippingCompany->status === 1 ? 'Active' : 'Inactive',
		];

		$managePincodeSummary = $this->getManagePincodeSummary((int) $shippingCompany->id);
		$zoneSummary = ZoneSetting::query()
			->where('shipping_company_id', $shippingCompany->id)
			->selectRaw("SUM(CASE WHEN rate_type = 'b2c' THEN 1 ELSE 0 END) as b2c_zone_count")
			->selectRaw("SUM(CASE WHEN rate_type = 'b2b' THEN 1 ELSE 0 END) as b2b_zone_count")
			->first();

		$stats = [
			'active_weight_count' => ShippingWeight::where('shipping_company_id', $shippingCompany->id)->where('status', 1)->count(),
			'b2c_zone_count' => (int) ($zoneSummary->b2c_zone_count ?? 0),
			'b2b_zone_count' => (int) ($zoneSummary->b2b_zone_count ?? 0),
			'b2c_rate_card_count' => RateCardDetail::where('shipping_company_id', $shippingCompany->id)->where('status', 1)->distinct()->count('rate_card_id'),
			'b2b_rate_card_count' => RateCardMatrix::where('shipping_company_id', $shippingCompany->id)->distinct()->count('rate_card_id'),
			'other_charge_count' => OtherChargeMaster::where('shipping_company_id', $shippingCompany->id)->where('is_active', 1)->count(),
			'oda_profile_count' => count($this->getShippingCompanyOdaChargeOptions((int) $shippingCompany->id)),
			'opa_profile_count' => count($this->getShippingCompanyOpaChargeOptions((int) $shippingCompany->id)),
			'overhead_configured' => OverheadCharge::where('shipping_company_id', $shippingCompany->id)->exists(),
		];

		$defaultB2bRateCardId = (int) RateCardMatrix::query()
			->where('shipping_company_id', $shippingCompany->id)
			->orderByDesc('updated_at')
			->value('rate_card_id');

		if ($defaultB2bRateCardId <= 0) {
			$defaultB2bRateCardId = (int) RateCard::query()
				->where('rate_type', 'b2b')
				->orderByDesc('created_at')
				->value('id');
		}

		$defaultB2cRateCardId = (int) RateCardDetail::query()
			->where('shipping_company_id', $shippingCompany->id)
			->where('status', 1)
			->orderByDesc('updated_at')
			->value('rate_card_id');

		if ($defaultB2cRateCardId <= 0) {
			$defaultB2cRateCardId = (int) RateCard::query()
				->where('rate_type', 'b2c')
				->orderByDesc('created_at')
				->value('id');
		}

		$defaultB2cWeight = null;
		if ($defaultB2cRateCardId > 0) {
			$defaultB2cWeight = RateCardDetail::query()
				->where('shipping_company_id', $shippingCompany->id)
				->where('rate_card_id', $defaultB2cRateCardId)
				->where('status', 1)
				->orderBy('weight', 'asc')
				->value('weight');
		}

		return view('shipping.company-workspace', compact(
			'shippingCompany',
			'companyMeta',
			'managePincodeSummary',
			'stats',
			'activeTab',
			'defaultB2bRateCardId',
			'defaultB2cRateCardId',
			'defaultB2cWeight'
		));
	}

	public function managePincode($id)
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}

		$shippingCompany = ShippingCompany::findOrFail($id);
		$odaChargeOptions = $this->getShippingCompanyOdaChargeOptions($shippingCompany->id);
		$opaChargeOptions = $this->getShippingCompanyOpaChargeOptions($shippingCompany->id);

		return view('shipping.manage-pincode', compact('shippingCompany', 'odaChargeOptions', 'opaChargeOptions'));
	}

	public function managePincodeOdaChargesSave(Request $request, $id)
	{
		$shippingCompany = ShippingCompany::findOrFail($id);

		if (!$this->hasManagePincodeOdaChargeSupport()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Please run migration first to enable dynamic ODA charges.',
			], 422);
		}

		$validator = Validator::make($request->all(), [
			'charges' => ['nullable', 'array'],
			'charges.*.id' => ['nullable', 'integer'],
			'charges.*.name' => ['nullable', 'string', 'max:120'],
			'charges.*.per_kg' => ['nullable', 'numeric', 'min:0'],
			'charges.*.min_charge' => ['nullable', 'numeric', 'min:0'],
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$existingCharges = ShippingCompanyOdaCharge::query()
			->where('shipping_company_id', $shippingCompany->id)
			->orderBy('sort_order')
			->get()
			->keyBy('id');

		$inputCharges = collect($request->input('charges', []))
			->map(function ($row) {
				return [
					'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
					'name' => trim((string) ($row['name'] ?? '')),
					'per_kg' => round((float) ($row['per_kg'] ?? 0), 2),
					'min_charge' => round((float) ($row['min_charge'] ?? 0), 2),
				];
			})
			->filter(function (array $row) {
				return $row['name'] !== '' || $row['id'] !== null;
			})
			->values();

		foreach ($inputCharges as $row) {
			if ($row['name'] === '') {
				return response()->json([
					'status' => 'error',
					'msg' => 'ODA charge name is required.',
				], 422);
			}

			if ($row['id'] !== null && !$existingCharges->has($row['id'])) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Invalid ODA charge selected.',
				], 422);
			}
		}

		DB::transaction(function () use ($shippingCompany, $existingCharges, $inputCharges) {
			$keptIds = [];
			$usedCodes = [];
			$sortOrder = 1;

			foreach ($inputCharges as $row) {
				$existingCharge = $row['id'] !== null ? $existingCharges->get($row['id']) : null;
				$code = $existingCharge && $existingCharge->code !== ''
					? $existingCharge->code
					: $this->generateShippingCompanyOdaChargeCode($shippingCompany->id, $row['name'], $usedCodes, $row['id']);

				$payload = [
					'name' => $row['name'],
					'code' => $code,
					'per_kg' => $row['per_kg'],
					'min_charge' => $row['min_charge'],
					'sort_order' => $sortOrder,
				];

				if ($existingCharge) {
					$existingCharge->update($payload);
					$keptIds[] = (int) $existingCharge->id;
				} else {
					$newCharge = ShippingCompanyOdaCharge::create($payload + [
						'shipping_company_id' => $shippingCompany->id,
					]);
					$keptIds[] = (int) $newCharge->id;
				}

				$usedCodes[] = $code;
				$sortOrder++;
			}

			$deleteIds = $existingCharges->keys()
				->map(fn ($value) => (int) $value)
				->diff($keptIds)
				->values();

			if ($deleteIds->isNotEmpty()) {
				ShippingCompanyPincode::query()
					->where('shipping_company_id', $shippingCompany->id)
					->whereIn('oda_charge_id', $deleteIds->all())
					->update([
						'oda_charge_id' => null,
						'oda_category' => 'none',
						'updated_at' => now(),
					]);

				ShippingCompanyOdaCharge::query()
					->where('shipping_company_id', $shippingCompany->id)
					->whereIn('id', $deleteIds->all())
					->delete();
			}
		});

		$this->forgetManagePincodeCache($shippingCompany->id);

		return response()->json([
			'status' => 'success',
			'msg' => 'ODA charges saved successfully.',
			'charges' => $this->getShippingCompanyOdaChargeOptions($shippingCompany->id),
		]);
	}

	public function managePincodeOpaChargesSave(Request $request, $id)
	{
		$shippingCompany = ShippingCompany::findOrFail($id);

		if (!$this->hasManagePincodeOpaChargeSupport()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Please run migration first to enable dynamic OPA charges.',
			], 422);
		}

		$validator = Validator::make($request->all(), [
			'charges' => ['nullable', 'array'],
			'charges.*.id' => ['nullable', 'integer'],
			'charges.*.name' => ['nullable', 'string', 'max:120'],
			'charges.*.per_kg' => ['nullable', 'numeric', 'min:0'],
			'charges.*.min_charge' => ['nullable', 'numeric', 'min:0'],
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$existingCharges = ShippingCompanyOpaCharge::query()
			->where('shipping_company_id', $shippingCompany->id)
			->orderBy('sort_order')
			->get()
			->keyBy('id');

		$inputCharges = collect($request->input('charges', []))
			->map(function ($row) {
				return [
					'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
					'name' => trim((string) ($row['name'] ?? '')),
					'per_kg' => round((float) ($row['per_kg'] ?? 0), 2),
					'min_charge' => round((float) ($row['min_charge'] ?? 0), 2),
				];
			})
			->filter(function (array $row) {
				return $row['name'] !== '' || $row['id'] !== null;
			})
			->values();

		foreach ($inputCharges as $row) {
			if ($row['name'] === '') {
				return response()->json([
					'status' => 'error',
					'msg' => 'OPA charge name is required.',
				], 422);
			}

			if ($row['id'] !== null && !$existingCharges->has($row['id'])) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Invalid OPA charge selected.',
				], 422);
			}
		}

		DB::transaction(function () use ($shippingCompany, $existingCharges, $inputCharges) {
			$keptIds = [];
			$usedCodes = [];
			$sortOrder = 1;

			foreach ($inputCharges as $row) {
				$existingCharge = $row['id'] !== null ? $existingCharges->get($row['id']) : null;
				$code = $existingCharge && $existingCharge->code !== ''
					? $existingCharge->code
					: $this->generateShippingCompanyOpaChargeCode($shippingCompany->id, $row['name'], $usedCodes, $row['id']);

				$payload = [
					'name' => $row['name'],
					'code' => $code,
					'per_kg' => $row['per_kg'],
					'min_charge' => $row['min_charge'],
					'sort_order' => $sortOrder,
				];

				if ($existingCharge) {
					$existingCharge->update($payload);
					$keptIds[] = (int) $existingCharge->id;
				} else {
					$newCharge = ShippingCompanyOpaCharge::create($payload + [
						'shipping_company_id' => $shippingCompany->id,
					]);
					$keptIds[] = (int) $newCharge->id;
				}

				$usedCodes[] = $code;
				$sortOrder++;
			}

			$deleteIds = $existingCharges->keys()
				->map(fn ($value) => (int) $value)
				->diff($keptIds)
				->values();

			if ($deleteIds->isNotEmpty()) {
				ShippingCompanyPincode::query()
					->where('shipping_company_id', $shippingCompany->id)
					->whereIn('opa_charge_id', $deleteIds->all())
					->update([
						'opa_charge_id' => null,
						'opa_category' => 'none',
						'updated_at' => now(),
					]);

				ShippingCompanyOpaCharge::query()
					->where('shipping_company_id', $shippingCompany->id)
					->whereIn('id', $deleteIds->all())
					->delete();
			}
		});

		$this->forgetManagePincodeCache($shippingCompany->id);

		return response()->json([
			'status' => 'success',
			'msg' => 'OPA charges saved successfully.',
			'charges' => $this->getShippingCompanyOpaChargeOptions($shippingCompany->id),
		]);
	}

	public function managePincodeAjax(Request $request, $id)
	{
		$shippingCompany = ShippingCompany::findOrFail($id);
		$odaChargeOptions = $this->getShippingCompanyOdaChargeOptions($shippingCompany->id);
		$opaChargeOptions = $this->getShippingCompanyOpaChargeOptions($shippingCompany->id);
		$draw = (int) $request->input('draw');
		$start = (int) $request->input('start', 0);
		$requestedLimit = (int) $request->input('length', 1000);
		$limit = $requestedLimit < 1 ? 1000 : min($requestedLimit, 1000);
		$orderArr = $request->input('order', []);
		$columnArr = $request->input('columns', []);
		$searchValue = $this->getManagePincodeSearchValue($request);

		$sortableColumns = [
			'location' => 'zipcodes.pincode',
			'overall_status' => 'overall_status_sort',
		];

		$orderColumn = 'zipcodes.pincode';
		$orderDirection = 'asc';
		if (!empty($orderArr) && isset($orderArr[0]['column'], $orderArr[0]['dir'])) {
			$columnIndex = (int) $orderArr[0]['column'];
			$columnKey = $columnArr[$columnIndex]['data'] ?? '';
			$orderColumn = $sortableColumns[$columnKey] ?? $orderColumn;
			$orderDirection = strtolower((string) $orderArr[0]['dir']) === 'desc' ? 'desc' : 'asc';
		}

		$query = $this->buildManagePincodeQuery($shippingCompany->id, $request, $searchValue);

		$totalRecords = Zipcode::count();

		$totalFiltered = (clone $query)->count();
		$summary = $this->getManagePincodeSummary($shippingCompany->id);

		$rows = $query
			->orderBy($orderColumn, $orderDirection)
			->offset($start)
			->limit($limit)
			->get();

		$data = [];
		foreach ($rows as $index => $row) {
			$pickupStatus = (int) $row->pickup_status;
			$deliveryStatus = (int) $row->delivery_status;
			$pickupService = strtolower((string) ($row->pickup_service ?? ''));
			$deliveryService = strtolower((string) ($row->delivery_service ?? ''));
			$odaChargeId = isset($row->oda_charge_id) ? (int) $row->oda_charge_id : null;
			$odaCategoryLabel = trim((string) ($row->oda_category_label ?? $row->oda_category ?? 'NONE'));
			$opaChargeId = isset($row->opa_charge_id) ? (int) $row->opa_charge_id : null;
			$opaCategoryLabel = trim((string) ($row->opa_category_label ?? $row->opa_category ?? 'NONE'));
			$codEnabled = (int) ($row->cod_enabled ?? 1) === 1;

			$data[] = [
				'checkbox' => '<input type="checkbox" class="pincode-checkbox" value="' . $row->id . '" data-pickup-status="' . $pickupStatus . '" data-delivery-status="' . $deliveryStatus . '">',
				'location' => '<div class="manage-pincode-location"><div class="manage-pincode-pin">' . e($row->pincode) . '</div><div class="manage-pincode-place">' . e(trim(($row->city ?? '') . ', ' . ($row->state ?? ''), ', ')) . '</div></div>',
				'pickup_service' => $this->renderManagePincodeServiceModeButtons((int) $row->id, 'pickup', $pickupService),
				'delivery_service' => $this->renderManagePincodeServiceModeButtons((int) $row->id, 'delivery', $deliveryService),
				'oda_category' => $this->renderManagePincodeOdaSelector((int) $row->id, $odaChargeId, $odaCategoryLabel, $odaChargeOptions),
				'opa_category' => $this->renderManagePincodeOpaSelector((int) $row->id, $opaChargeId, $opaCategoryLabel, $opaChargeOptions),
				'cod_enabled' => $this->renderManagePincodeCodToggle((int) $row->id, $codEnabled),
				'overall_status' => $this->renderManagePincodeOverallStatus($pickupStatus, $deliveryStatus),
			];
		}

		return response()->json([
			'draw' => $draw,
			'iTotalRecords' => $totalRecords,
			'iTotalDisplayRecords' => $totalFiltered,
			'aaData' => $data,
			'summary' => $summary,
			'showing_count' => $totalFiltered,
		]);
	}

	public function managePincodeBulkUpdate(Request $request, $id)
	{
		$shippingCompany = ShippingCompany::findOrFail($id);

		if (!Schema::hasTable('shipping_company_pincodes')) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Please run migration first to enable pincode mapping.',
			], 422);
		}

		$applyToAllFiltered = filter_var($request->input('apply_to_all_filtered', false), FILTER_VALIDATE_BOOLEAN);
		$searchValue = $this->getManagePincodeSearchValue($request);

		if ($request->filled('setting')) {
			if (!$this->hasManagePincodeMetaColumns()) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Please run migration first to enable service, ODA, OPA, and COD settings.',
				], 422);
			}

			$validator = Validator::make($request->all(), [
				'setting' => ['required', Rule::in(['pickup_service', 'delivery_service', 'oda_category', 'oda_charge_id', 'opa_category', 'opa_charge_id', 'cod_enabled'])],
				'value' => ['required'],
				'apply_to_all_filtered' => ['nullable', 'boolean'],
				'pincode_ids' => ['required_without:apply_to_all_filtered', 'array'],
				'pincode_ids.*' => ['integer', 'exists:zipcodes,id'],
			], [
				'pincode_ids.required' => 'Please select at least one pincode.',
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => 'error',
					'msg' => $validator->errors()->first(),
				], 422);
			}

			$pincodeIds = $this->resolveManagePincodeIdsForBulkAction($shippingCompany->id, $request, $applyToAllFiltered, $searchValue);
			if ($pincodeIds->isEmpty()) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Please select at least one pincode.',
				], 422);
			}

			$setting = (string) $request->input('setting');
			$value = $request->input('value');
			$now = now();
			$records = [];
			$updateColumns = [];
			$directions = ['from', 'to'];

			if ($setting === 'pickup_service') {
				if (!in_array($value, ['door', 'drop', 'no_pickup'], true)) {
					return response()->json(['status' => 'error', 'msg' => 'Invalid pickup service selected.'], 422);
				}
				$directions = ['from'];
				$updateColumns = ['service_mode', 'updated_at'];
			} elseif ($setting === 'delivery_service') {
				if (!in_array($value, ['door', 'pickup', 'no_delivery'], true)) {
					return response()->json(['status' => 'error', 'msg' => 'Invalid delivery service selected.'], 422);
				}
				$directions = ['to'];
				$updateColumns = ['service_mode', 'updated_at'];
			} elseif (in_array($setting, ['oda_category', 'oda_charge_id', 'opa_category', 'opa_charge_id'], true)) {
				$isOpa = str_starts_with($setting, 'opa_');
				$chargeIdColumn = $isOpa ? 'opa_charge_id' : 'oda_charge_id';
				$categoryColumn = $isOpa ? 'opa_category' : 'oda_category';
				$chargeLabel = $isOpa ? 'OPA' : 'ODA';
				$chargeIdValue = null;
				$categoryValue = 'none';

				if ($isOpa && (!Schema::hasColumn('shipping_company_pincodes', 'opa_category') || !Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id'))) {
					return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic OPA charges.'], 422);
				}

				if ((string) $value !== 'none' && (string) $value !== '') {
					if ($isOpa && !$this->hasManagePincodeOpaChargeSupport()) {
						return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic OPA charges.'], 422);
					}
					if (!$isOpa && !$this->hasManagePincodeOdaChargeSupport()) {
						return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic ODA charges.'], 422);
					}

					$charge = $isOpa
						? ShippingCompanyOpaCharge::query()
							->where('shipping_company_id', $shippingCompany->id)
							->where('id', (int) $value)
							->first()
						: ShippingCompanyOdaCharge::query()
							->where('shipping_company_id', $shippingCompany->id)
							->where('id', (int) $value)
							->first();

					if (!$charge) {
						return response()->json(['status' => 'error', 'msg' => 'Invalid ' . $chargeLabel . ' category selected.'], 422);
					}

					$chargeIdValue = (int) $charge->id;
					$categoryValue = $charge->code;
				}

				$value = [
					$categoryColumn => $categoryValue,
					$chargeIdColumn => $chargeIdValue,
				];
				$updateColumns = [$categoryColumn, $chargeIdColumn, 'updated_at'];
			} else {
				$codEnabled = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				$codEnabled = $codEnabled === null ? ((string) $value === '1') : $codEnabled;
				$value = ['cod_enabled' => $codEnabled ? 1 : 0];
				$updateColumns = ['cod_enabled', 'updated_at'];
			}

			foreach ($pincodeIds as $pincodeId) {
				foreach ($directions as $direction) {
					$row = [
						'shipping_company_id' => $shippingCompany->id,
						'pincode_id' => $pincodeId,
						'direction' => $direction,
						'created_at' => $now,
						'updated_at' => $now,
					];

					if (is_array($value)) {
						$row = array_merge($row, $value);
					} else {
						$row['service_mode'] = $value;
					}

					$records[] = $row;
				}
			}

			ShippingCompanyPincode::upsert(
				$records,
				['shipping_company_id', 'pincode_id', 'direction'],
				$updateColumns
			);

			$this->forgetManagePincodeCache($shippingCompany->id);

			return response()->json([
				'status' => 'success',
				'msg' => $pincodeIds->count() . ' pincode(s) updated successfully.',
			]);
		}

		$validator = Validator::make($request->all(), [
			'target' => ['required', Rule::in(['pickup', 'delivery', 'both'])],
			'status' => ['required', Rule::in(['1', '2', 1, 2])],
			'apply_to_all_filtered' => ['nullable', 'boolean'],
			'pincode_ids' => ['required_without:apply_to_all_filtered', 'array'],
			'pincode_ids.*' => ['integer', 'exists:zipcodes,id'],
		], [
			'pincode_ids.required' => 'Please select at least one pincode.',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$target = $request->input('target');
		$status = (int) $request->input('status');
		$pincodeIds = $this->resolveManagePincodeIdsForBulkAction($shippingCompany->id, $request, $applyToAllFiltered, $searchValue);

		if ($pincodeIds->isEmpty()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Please select at least one pincode.',
			], 422);
		}

		$directions = match ($target) {
			'pickup' => ['from'],
			'delivery' => ['to'],
			default => ['from', 'to'],
		};

		$now = now();
		$upsertData = [];
		foreach ($pincodeIds as $pincodeId) {
			foreach ($directions as $direction) {
				$upsertData[] = [
					'shipping_company_id' => $shippingCompany->id,
					'pincode_id' => $pincodeId,
					'direction' => $direction,
					'status' => $status,
					'created_at' => $now,
					'updated_at' => $now,
				];
			}
		}

		ShippingCompanyPincode::upsert(
			$upsertData,
			['shipping_company_id', 'pincode_id', 'direction'],
			['status', 'updated_at']
		);
		$this->forgetManagePincodeCache($shippingCompany->id);

		$action = $status === 1 ? 'activated' : 'in-activated';
		$targetLabel = match ($target) {
			'pickup' => 'pickup',
			'delivery' => 'delivery',
			default => 'pickup and delivery',
		};

		return response()->json([
			'status' => 'success',
			'msg' => $pincodeIds->count() . ' pincode(s) ' . $action . ' successfully for ' . ucfirst($targetLabel) . '.',
		]);
	}

	private function resolveManagePincodeIdsForBulkAction(int $shippingCompanyId, Request $request, bool $applyToAllFiltered, string $searchValue)
	{
		if ($applyToAllFiltered) {
			return $this->buildManagePincodeQuery($shippingCompanyId, $request, $searchValue)
				->pluck('zipcodes.id')
				->map(fn ($value) => (int) $value)
				->unique()
				->values();
		}

		return collect($request->input('pincode_ids', []))
			->map(fn ($value) => (int) $value)
			->unique()
			->values();
	}

	public function managePincodeRowUpdate(Request $request, $id)
	{
		$shippingCompany = ShippingCompany::findOrFail($id);

		if (!$this->hasManagePincodeMetaColumns()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Please run migration first to enable service, ODA, OPA, and COD settings.',
			], 422);
		}

		$validator = Validator::make($request->all(), [
			'pincode_id' => ['required', 'integer', 'exists:zipcodes,id'],
			'setting' => ['required', Rule::in(['pickup_service', 'delivery_service', 'oda_category', 'oda_charge_id', 'opa_category', 'opa_charge_id', 'cod_enabled'])],
			'value' => ['required'],
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$pincodeId = (int) $request->input('pincode_id');
		$setting = (string) $request->input('setting');
		$value = $request->input('value');
		$now = now();

		switch ($setting) {
			case 'pickup_service':
				if (!in_array($value, ['door', 'drop', 'no_pickup'], true)) {
					return response()->json(['status' => 'error', 'msg' => 'Invalid pickup service selected.'], 422);
				}

				ShippingCompanyPincode::upsert(
					[[
						'shipping_company_id' => $shippingCompany->id,
						'pincode_id' => $pincodeId,
						'direction' => 'from',
						'service_mode' => $value,
						'created_at' => $now,
						'updated_at' => $now,
					]],
					['shipping_company_id', 'pincode_id', 'direction'],
					['service_mode', 'updated_at']
				);
				break;

			case 'delivery_service':
				if (!in_array($value, ['door', 'pickup', 'no_delivery'], true)) {
					return response()->json(['status' => 'error', 'msg' => 'Invalid delivery service selected.'], 422);
				}

				ShippingCompanyPincode::upsert(
					[[
						'shipping_company_id' => $shippingCompany->id,
						'pincode_id' => $pincodeId,
						'direction' => 'to',
						'service_mode' => $value,
						'created_at' => $now,
						'updated_at' => $now,
					]],
					['shipping_company_id', 'pincode_id', 'direction'],
					['service_mode', 'updated_at']
				);
				break;

			case 'oda_category':
			case 'oda_charge_id':
			case 'opa_category':
			case 'opa_charge_id':
				$isOpa = str_starts_with($setting, 'opa_');
				$chargeIdColumn = $isOpa ? 'opa_charge_id' : 'oda_charge_id';
				$categoryColumn = $isOpa ? 'opa_category' : 'oda_category';
				$chargeLabel = $isOpa ? 'OPA' : 'ODA';
				$chargeIdValue = null;
				$categoryValue = 'none';

				if ($isOpa && (!Schema::hasColumn('shipping_company_pincodes', 'opa_category') || !Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id'))) {
					return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic OPA charges.'], 422);
				}

				if ((string) $value !== 'none' && (string) $value !== '') {
					if ($isOpa && !$this->hasManagePincodeOpaChargeSupport()) {
						return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic OPA charges.'], 422);
					}
					if (!$isOpa && !$this->hasManagePincodeOdaChargeSupport()) {
						return response()->json(['status' => 'error', 'msg' => 'Please run migration first to enable dynamic ODA charges.'], 422);
					}

					$charge = $isOpa
						? ShippingCompanyOpaCharge::query()
							->where('shipping_company_id', $shippingCompany->id)
							->where('id', (int) $value)
							->first()
						: ShippingCompanyOdaCharge::query()
							->where('shipping_company_id', $shippingCompany->id)
							->where('id', (int) $value)
							->first();

					if (!$charge) {
						return response()->json(['status' => 'error', 'msg' => 'Invalid ' . $chargeLabel . ' category selected.'], 422);
					}

					$chargeIdValue = (int) $charge->id;
					$categoryValue = $charge->code;
				}

				$records = [];
				foreach (['from', 'to'] as $direction) {
					$records[] = [
						'shipping_company_id' => $shippingCompany->id,
						'pincode_id' => $pincodeId,
						'direction' => $direction,
						$categoryColumn => $categoryValue,
						$chargeIdColumn => $chargeIdValue,
						'created_at' => $now,
						'updated_at' => $now,
					];
				}

				ShippingCompanyPincode::upsert(
					$records,
					['shipping_company_id', 'pincode_id', 'direction'],
					[$categoryColumn, $chargeIdColumn, 'updated_at']
				);
				break;

			case 'cod_enabled':
				$codEnabled = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				$codEnabled = $codEnabled === null ? ((string) $value === '1') : $codEnabled;

				$records = [];
				foreach (['from', 'to'] as $direction) {
					$records[] = [
						'shipping_company_id' => $shippingCompany->id,
						'pincode_id' => $pincodeId,
						'direction' => $direction,
						'cod_enabled' => $codEnabled ? 1 : 0,
						'created_at' => $now,
						'updated_at' => $now,
					];
				}

				ShippingCompanyPincode::upsert(
					$records,
					['shipping_company_id', 'pincode_id', 'direction'],
					['cod_enabled', 'updated_at']
				);
				break;
		}

		$this->forgetManagePincodeCache($shippingCompany->id);

		return response()->json([
			'status' => 'success',
			'msg' => 'Pincode settings updated successfully.',
		]);
	}

	private function buildManagePincodeQuery(int $shippingCompanyId, Request $request, string $searchValue = '')
	{
		if (!Schema::hasTable('shipping_company_pincodes')) {
			$query = Zipcode::query()->select(
				'zipcodes.id',
				'zipcodes.pincode',
				'zipcodes.city',
				'zipcodes.state',
				DB::raw('2 as pickup_status'),
				DB::raw('2 as delivery_status'),
				DB::raw("'' as pickup_service"),
				DB::raw("'' as delivery_service"),
				DB::raw("'none' as oda_category"),
				DB::raw("'none' as opa_category"),
				DB::raw('NULL as opa_charge_id'),
				DB::raw("'NONE' as opa_category_label"),
				DB::raw('1 as cod_enabled'),
				DB::raw('2 as overall_status_sort')
			);

			return $this->applyManagePincodeFilters($query, $request, $searchValue, '2', '2');
		}

		$pickupStatusExpr = "CASE WHEN COALESCE(NULLIF(scp_from.service_mode, ''), 'door') = 'door' THEN 1 ELSE 2 END";
		$deliveryStatusExpr = "CASE WHEN COALESCE(NULLIF(scp_to.service_mode, ''), 'door') = 'door' THEN 1 ELSE 2 END";
		$hasOdaChargeSupport = $this->hasManagePincodeOdaChargeSupport();
		$hasOpaChargeSupport = $this->hasManagePincodeOpaChargeSupport();
		$hasOpaCategoryColumn = Schema::hasColumn('shipping_company_pincodes', 'opa_category');
		$pickupServiceExpr = $this->hasManagePincodeMetaColumns()
			? "scp_from.service_mode"
			: "'door'";
		$deliveryServiceExpr = $this->hasManagePincodeMetaColumns()
			? "scp_to.service_mode"
			: "'door'";
		$legacyOdaValueExpr = "LOWER(COALESCE(NULLIF(scp_from.oda_category, ''), NULLIF(scp_to.oda_category, ''), 'none'))";
		$odaCategoryExpr = $this->hasManagePincodeMetaColumns()
			? $legacyOdaValueExpr
			: "'none'";
		$odaCategoryLabelExpr = $this->hasManagePincodeMetaColumns()
			? ($hasOdaChargeSupport
				? "COALESCE(NULLIF(sco_from.name, ''), NULLIF(sco_to.name, ''), CASE WHEN {$legacyOdaValueExpr} = 'none' THEN 'NONE' ELSE UPPER(REPLACE({$legacyOdaValueExpr}, '_', ' ')) END)"
				: "CASE WHEN {$legacyOdaValueExpr} = 'none' THEN 'NONE' ELSE UPPER(REPLACE({$legacyOdaValueExpr}, '_', ' ')) END")
			: "'NONE'";
		$odaChargeIdExpr = $hasOdaChargeSupport
			? 'COALESCE(scp_from.oda_charge_id, scp_to.oda_charge_id)'
			: 'NULL';
		$legacyOpaValueExpr = $hasOpaCategoryColumn
			? "LOWER(COALESCE(NULLIF(scp_from.opa_category, ''), NULLIF(scp_to.opa_category, ''), 'none'))"
			: "'none'";
		$opaCategoryExpr = $hasOpaCategoryColumn
			? $legacyOpaValueExpr
			: "'none'";
		$opaCategoryLabelExpr = $hasOpaCategoryColumn
			? ($hasOpaChargeSupport
				? "COALESCE(NULLIF(scop_from.name, ''), NULLIF(scop_to.name, ''), CASE WHEN {$legacyOpaValueExpr} = 'none' THEN 'NONE' ELSE UPPER(REPLACE({$legacyOpaValueExpr}, '_', ' ')) END)"
				: "CASE WHEN {$legacyOpaValueExpr} = 'none' THEN 'NONE' ELSE UPPER(REPLACE({$legacyOpaValueExpr}, '_', ' ')) END")
			: "'NONE'";
		$opaChargeIdExpr = $hasOpaChargeSupport
			? 'COALESCE(scp_from.opa_charge_id, scp_to.opa_charge_id)'
			: 'NULL';
		$codEnabledExpr = $this->hasManagePincodeMetaColumns()
			? 'COALESCE(scp_from.cod_enabled, scp_to.cod_enabled, 1)'
			: '1';
		$overallStatusExpr = "CASE
			WHEN {$pickupStatusExpr} = 1 AND {$deliveryStatusExpr} = 1 THEN 1
			WHEN {$pickupStatusExpr} = 1 OR {$deliveryStatusExpr} = 1 THEN 3
			ELSE 2
		END";

		$query = Zipcode::query()
			->leftJoin('shipping_company_pincodes as scp_from', function ($join) use ($shippingCompanyId) {
				$join->on('scp_from.pincode_id', '=', 'zipcodes.id')
					->where('scp_from.shipping_company_id', '=', $shippingCompanyId)
					->where('scp_from.direction', '=', 'from');
			})
			->leftJoin('shipping_company_pincodes as scp_to', function ($join) use ($shippingCompanyId) {
				$join->on('scp_to.pincode_id', '=', 'zipcodes.id')
					->where('scp_to.shipping_company_id', '=', $shippingCompanyId)
					->where('scp_to.direction', '=', 'to');
			});

		if ($hasOdaChargeSupport) {
			$query
				->leftJoin('shipping_company_oda_charges as sco_from', function ($join) use ($shippingCompanyId) {
					$join->on('sco_from.id', '=', 'scp_from.oda_charge_id')
						->where('sco_from.shipping_company_id', '=', $shippingCompanyId);
				})
				->leftJoin('shipping_company_oda_charges as sco_to', function ($join) use ($shippingCompanyId) {
					$join->on('sco_to.id', '=', 'scp_to.oda_charge_id')
						->where('sco_to.shipping_company_id', '=', $shippingCompanyId);
				});
		}

		if ($hasOpaChargeSupport) {
			$query
				->leftJoin('shipping_company_opa_charges as scop_from', function ($join) use ($shippingCompanyId) {
					$join->on('scop_from.id', '=', 'scp_from.opa_charge_id')
						->where('scop_from.shipping_company_id', '=', $shippingCompanyId);
				})
				->leftJoin('shipping_company_opa_charges as scop_to', function ($join) use ($shippingCompanyId) {
					$join->on('scop_to.id', '=', 'scp_to.opa_charge_id')
						->where('scop_to.shipping_company_id', '=', $shippingCompanyId);
				});
		}

		$query
			->select(
				'zipcodes.id',
				'zipcodes.pincode',
				'zipcodes.city',
				'zipcodes.state'
			)
			->selectRaw("{$pickupStatusExpr} as pickup_status")
			->selectRaw("{$deliveryStatusExpr} as delivery_status")
			->selectRaw("{$pickupServiceExpr} as pickup_service")
			->selectRaw("{$deliveryServiceExpr} as delivery_service")
			->selectRaw("{$odaCategoryExpr} as oda_category")
			->selectRaw("{$odaChargeIdExpr} as oda_charge_id")
			->selectRaw("{$odaCategoryLabelExpr} as oda_category_label")
			->selectRaw("{$opaCategoryExpr} as opa_category")
			->selectRaw("{$opaChargeIdExpr} as opa_charge_id")
			->selectRaw("{$opaCategoryLabelExpr} as opa_category_label")
			->selectRaw("{$codEnabledExpr} as cod_enabled")
			->selectRaw("{$overallStatusExpr} as overall_status_sort");

		return $this->applyManagePincodeFilters($query, $request, $searchValue, $pickupStatusExpr, $deliveryStatusExpr);
	}

	private function applyManagePincodeFilters($query, Request $request, string $searchValue, string $pickupStatusExpr, string $deliveryStatusExpr)
	{
		$filterCity = trim((string) $request->input('filter_city', ''));
		$filterState = trim((string) $request->input('filter_state', ''));
		$filterPickup = trim((string) $request->input('filter_pickup', ''));
		$filterDelivery = trim((string) $request->input('filter_delivery', ''));
		$pasteList = $this->getManagePincodePasteList($request);

		if ($filterCity !== '') {
			$query->where('zipcodes.city', $filterCity);
		}

		if ($filterState !== '') {
			$query->where('zipcodes.state', $filterState);
		}

		if (!empty($pasteList)) {
			$query->whereIn('zipcodes.pincode', $pasteList);
		}

		if ($searchValue !== '') {
			$query->where(function ($q) use ($searchValue) {
				$q->where('zipcodes.pincode', 'like', "%{$searchValue}%")
					->orWhere('zipcodes.city', 'like', "%{$searchValue}%")
					->orWhere('zipcodes.state', 'like', "%{$searchValue}%");
			});
		}

		if ($filterPickup !== '') {
			if ($filterPickup === 'not_selected') {
				$query->where(function ($q) {
					$q->whereNull('scp_from.service_mode')
						->orWhere('scp_from.service_mode', '');
				});
			} else {
				$query->where('scp_from.service_mode', $filterPickup);
			}
		}

		if ($filterDelivery !== '') {
			if ($filterDelivery === 'not_selected') {
				$query->where(function ($q) {
					$q->whereNull('scp_to.service_mode')
						->orWhere('scp_to.service_mode', '');
				});
			} else {
				$query->where('scp_to.service_mode', $filterDelivery);
			}
		}

		return $query;
	}

	private function getManagePincodeSummary(int $shippingCompanyId): array
	{
		return Cache::remember(
			$this->getManagePincodeSummaryCacheKey($shippingCompanyId),
			now()->addMinutes(10),
			function () use ($shippingCompanyId) {
				$totalPincodes = Zipcode::count();

				if (!Schema::hasTable('shipping_company_pincodes')) {
					return [
						'total_pincodes' => $totalPincodes,
						'total_active_count' => 0,
						'oda_pincode_count' => 0,
						'opa_pincode_count' => 0,
						'door_pickup_count' => 0,
						'door_delivery_count' => 0,
						'partially_active_count' => 0,
						'fully_inactive_count' => $totalPincodes,
					];
				}

				$hasMetaColumns = $this->hasManagePincodeMetaColumns();
				$hasOpaCategoryColumn = Schema::hasColumn('shipping_company_pincodes', 'opa_category');
				$odaAssignedExpr = $this->hasManagePincodeOdaChargeSupport()
					? "(COALESCE(scp_from.oda_charge_id, scp_to.oda_charge_id) IS NOT NULL OR LOWER(COALESCE(NULLIF(scp_from.oda_category, ''), NULLIF(scp_to.oda_category, ''), 'none')) <> 'none')"
					: "LOWER(COALESCE(NULLIF(scp_from.oda_category, ''), NULLIF(scp_to.oda_category, ''), 'none')) <> 'none'";
				$opaAssignedExpr = $this->hasManagePincodeOpaChargeSupport()
					? "(COALESCE(scp_from.opa_charge_id, scp_to.opa_charge_id) IS NOT NULL OR LOWER(COALESCE(NULLIF(scp_from.opa_category, ''), NULLIF(scp_to.opa_category, ''), 'none')) <> 'none')"
					: "LOWER(COALESCE(NULLIF(scp_from.opa_category, ''), NULLIF(scp_to.opa_category, ''), 'none')) <> 'none'";
				$summary = Zipcode::query()
					->leftJoin('shipping_company_pincodes as scp_from', function ($join) use ($shippingCompanyId) {
						$join->on('scp_from.pincode_id', '=', 'zipcodes.id')
							->where('scp_from.shipping_company_id', '=', $shippingCompanyId)
							->where('scp_from.direction', '=', 'from');
					})
					->leftJoin('shipping_company_pincodes as scp_to', function ($join) use ($shippingCompanyId) {
						$join->on('scp_to.pincode_id', '=', 'zipcodes.id')
							->where('scp_to.shipping_company_id', '=', $shippingCompanyId)
							->where('scp_to.direction', '=', 'to');
					})
					->selectRaw('COUNT(*) as total_pincodes')
					->selectRaw("SUM(CASE WHEN COALESCE(NULLIF(scp_from.service_mode, ''), 'door') = 'door' THEN 1 ELSE 0 END) as pickup_serviceable_count")
					->selectRaw("SUM(CASE WHEN scp_from.service_mode IN ('drop', 'no_pickup') THEN 1 ELSE 0 END) as pickup_not_serviceable_count")
					->selectRaw("SUM(CASE WHEN COALESCE(NULLIF(scp_to.service_mode, ''), 'door') = 'door' THEN 1 ELSE 0 END) as delivery_serviceable_count")
					->selectRaw("SUM(CASE WHEN scp_to.service_mode IN ('pickup', 'no_delivery') THEN 1 ELSE 0 END) as delivery_not_serviceable_count")
					->selectRaw(
						$hasMetaColumns
							? "SUM(CASE WHEN {$odaAssignedExpr} THEN 1 ELSE 0 END) as oda_pincode_count"
							: '0 as oda_pincode_count'
					)
					->selectRaw(
						$hasOpaCategoryColumn
							? "SUM(CASE WHEN {$opaAssignedExpr} THEN 1 ELSE 0 END) as opa_pincode_count"
							: '0 as opa_pincode_count'
					)
					->first();

				return [
					'total_pincodes' => $totalPincodes,
					'pickup_serviceable_count' => (int) ($summary->pickup_serviceable_count ?? 0),
					'pickup_not_serviceable_count' => (int) ($summary->pickup_not_serviceable_count ?? 0),
					'delivery_serviceable_count' => (int) ($summary->delivery_serviceable_count ?? 0),
					'delivery_not_serviceable_count' => (int) ($summary->delivery_not_serviceable_count ?? 0),
					'oda_pincode_count' => (int) ($summary->oda_pincode_count ?? 0),
					'opa_pincode_count' => (int) ($summary->opa_pincode_count ?? 0),
				];
			}
		);
	}

	private function getManagePincodeSummaryCacheKey(int $shippingCompanyId): string
	{
		return 'manage_pincode_summary_' . $shippingCompanyId;
	}

	private function forgetManagePincodeCache(int $shippingCompanyId): void
	{
		Cache::forget($this->getManagePincodeSummaryCacheKey($shippingCompanyId));
	}

	private function getManagePincodePasteList(Request $request): array
	{
		$rawValue = trim((string) $request->input('paste_list', ''));
		if ($rawValue === '') {
			return [];
		}

		return collect(preg_split('/[\s,]+/', $rawValue))
			->map(fn ($value) => trim((string) $value))
			->filter(fn ($value) => $value !== '')
			->unique()
			->values()
			->all();
	}

	private function getManagePincodeSearchValue(Request $request): string
	{
		$searchValue = $request->input('search_value');

		if ($searchValue === null || $searchValue === '') {
			$searchValue = data_get($request->all(), 'search.value', '');
		}

		if (is_array($searchValue)) {
			$searchValue = '';
		}

		return trim((string) $searchValue);
	}

	private function hasManagePincodeMetaColumns(): bool
	{
		if (!Schema::hasTable('shipping_company_pincodes')) {
			return false;
		}

		return Schema::hasColumn('shipping_company_pincodes', 'service_mode')
			&& Schema::hasColumn('shipping_company_pincodes', 'oda_category')
			&& Schema::hasColumn('shipping_company_pincodes', 'cod_enabled');
	}

	private function hasManagePincodeOdaChargeSupport(): bool
	{
		return Schema::hasTable('shipping_company_oda_charges')
			&& Schema::hasTable('shipping_company_pincodes')
			&& Schema::hasColumn('shipping_company_pincodes', 'oda_charge_id');
	}

	private function hasManagePincodeOpaChargeSupport(): bool
	{
		return Schema::hasTable('shipping_company_opa_charges')
			&& Schema::hasTable('shipping_company_pincodes')
			&& Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id')
			&& Schema::hasColumn('shipping_company_pincodes', 'opa_category');
	}

	private function renderManagePincodeServiceModeButtons(int $pincodeId, string $target, ?string $currentMode): string
	{
		$currentMode = (string) $currentMode;
		$options = $target === 'pickup'
			? ['door' => 'Serviceable', 'drop' => 'Not Serviceable']
			: ['door' => 'Serviceable', 'pickup' => 'Not Serviceable'];

		$html = '<div class="service-mode-group ' . ($target === 'pickup' ? 'service-mode-pickup' : 'service-mode-delivery') . '">';
		foreach ($options as $value => $label) {
			$isSelected = $currentMode === $value ? ' is-selected' : '';
			
			// Default to 'door' (Serviceable) if no specific mode is set
			if ($value === 'door' && ($currentMode === '' || $currentMode === 'none' || $currentMode === 'null')) {
				$isSelected = ' is-selected';
			}

			$html .= '<button type="button" class="service-mode-btn quick-setting-update' . $isSelected . '" data-id="' . $pincodeId . '" data-setting="' . $target . '_service" data-value="' . $value . '">' . e($label) . '</button>';
		}
		$html .= '</div>';

		return $html;
	}

	private function renderManagePincodeOdaSelector(int $pincodeId, ?int $odaChargeId, string $odaCategoryLabel, array $odaChargeOptions = []): string
	{
		$isLegacySelection = $odaChargeId === null && $odaCategoryLabel !== '' && strtoupper($odaCategoryLabel) !== 'NONE';
		$html = '<select class="form-control form-control-sm oda-category-select quick-setting-select" data-id="' . $pincodeId . '" data-setting="oda_charge_id">';
		$html .= '<option value="none"' . ($odaChargeId === null && !$isLegacySelection ? ' selected' : '') . '>NONE</option>';
		foreach ($odaChargeOptions as $option) {
			$value = (int) ($option['id'] ?? 0);
			$label = trim((string) ($option['name'] ?? ''));
			if ($value <= 0 || $label === '') {
				continue;
			}
			$selected = $odaChargeId === $value ? ' selected' : '';
			$html .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
		}

		if ($isLegacySelection) {
			$html .= '<option value="none" selected>' . e($odaCategoryLabel) . '</option>';
		}

		$html .= '</select>';

		return $html;
	}

	private function renderManagePincodeOpaSelector(int $pincodeId, ?int $opaChargeId, string $opaCategoryLabel, array $opaChargeOptions = []): string
	{
		$isLegacySelection = $opaChargeId === null && $opaCategoryLabel !== '' && strtoupper($opaCategoryLabel) !== 'NONE';
		$html = '<select class="form-control form-control-sm opa-category-select quick-setting-select" data-id="' . $pincodeId . '" data-setting="opa_charge_id">';
		$html .= '<option value="none"' . ($opaChargeId === null && !$isLegacySelection ? ' selected' : '') . '>NONE</option>';
		foreach ($opaChargeOptions as $option) {
			$value = (int) ($option['id'] ?? 0);
			$label = trim((string) ($option['name'] ?? ''));
			if ($value <= 0 || $label === '') {
				continue;
			}
			$selected = $opaChargeId === $value ? ' selected' : '';
			$html .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
		}

		if ($isLegacySelection) {
			$html .= '<option value="none" selected>' . e($opaCategoryLabel) . '</option>';
		}

		$html .= '</select>';

		return $html;
	}

	private function getShippingCompanyOdaChargeOptions(int $shippingCompanyId): array
	{
		if (!Schema::hasTable('shipping_company_oda_charges')) {
			return [];
		}

		$this->syncAreaChargeProfilesFromOtherChargeMaster($shippingCompanyId, 'oda');

		return ShippingCompanyOdaCharge::query()
			->where('shipping_company_id', $shippingCompanyId)
			->orderBy('sort_order')
			->orderBy('name')
			->get()
			->map(fn (ShippingCompanyOdaCharge $charge) => [
				'id' => (int) $charge->id,
				'name' => (string) $charge->name,
				'code' => (string) $charge->code,
				'per_kg' => (float) $charge->per_kg,
				'min_charge' => (float) $charge->min_charge,
			])
			->all();
	}

	private function getShippingCompanyOpaChargeOptions(int $shippingCompanyId): array
	{
		if (!Schema::hasTable('shipping_company_opa_charges')) {
			return [];
		}

		$this->syncAreaChargeProfilesFromOtherChargeMaster($shippingCompanyId, 'opa');

		return ShippingCompanyOpaCharge::query()
			->where('shipping_company_id', $shippingCompanyId)
			->orderBy('sort_order')
			->orderBy('name')
			->get()
			->map(fn (ShippingCompanyOpaCharge $charge) => [
				'id' => (int) $charge->id,
				'name' => (string) $charge->name,
				'code' => (string) $charge->code,
				'per_kg' => (float) $charge->per_kg,
				'min_charge' => (float) $charge->min_charge,
			])
			->all();
	}

	private function syncAreaChargeProfilesFromOtherChargeMaster(int $shippingCompanyId, string $type): void
	{
		$type = strtolower(trim($type));
		$isOpa = $type === 'opa';
		$modelClass = $isOpa ? ShippingCompanyOpaCharge::class : ShippingCompanyOdaCharge::class;
		$keywords = $isOpa ? ['opa'] : ['oda', 'opd'];
		$fallbackPrefix = $isOpa ? 'OPA_AUTO' : 'ODA_AUTO';

		if (!Schema::hasTable('other_charge_masters')) {
			return;
		}

		$masters = OtherChargeMaster::query()
			->where('shipping_company_id', $shippingCompanyId)
			->where('is_active', 1)
			->where(function ($query) use ($keywords) {
				foreach ($keywords as $idx => $keyword) {
					if ($idx === 0) {
						$query->where('name', 'like', '%' . $keyword . '%')
							->orWhere('code', 'like', '%' . $keyword . '%');
						continue;
					}
					$query->orWhere('name', 'like', '%' . $keyword . '%')
						->orWhere('code', 'like', '%' . $keyword . '%');
				}
			})
			->orderBy('name')
			->get(['name', 'code', 'default_config']);

		if ($masters->isEmpty()) {
			return;
		}

		$existingRows = $modelClass::query()
			->where('shipping_company_id', $shippingCompanyId)
			->orderBy('sort_order')
			->get();

		$existingByCode = [];
		foreach ($existingRows as $row) {
			$codeKey = strtoupper(trim((string) $row->code));
			if ($codeKey !== '') {
				$existingByCode[$codeKey] = $row;
			}
		}

		$usedCodes = array_fill_keys(array_keys($existingByCode), true);
		$maxSortOrder = (int) ($existingRows->max('sort_order') ?? 0);

		foreach ($masters as $master) {
			$config = is_array($master->default_config) ? $master->default_config : [];
			$perKg = (float) ($config['per_kg'] ?? 0);
			$minCharge = (float) ($config['min'] ?? $config['min_charge'] ?? 0);
			$name = trim((string) ($master->name ?? ''));
			if ($name === '') {
				continue;
			}

			$rawCode = strtoupper(trim((string) ($master->code ?? '')));
			$baseCode = preg_replace('/[^A-Z0-9_]/', '_', $rawCode);
			$baseCode = trim((string) $baseCode, '_');
			if ($baseCode === '') {
				$baseCode = $fallbackPrefix;
			}

			$code = $baseCode;
			$suffix = 2;
			while (!isset($existingByCode[$code]) && isset($usedCodes[$code])) {
				$code = Str::limit($baseCode . '_' . $suffix, 80, '');
				$suffix++;
			}

			$payload = [
				'name' => $name,
				'code' => $code,
				'per_kg' => round($perKg, 2),
				'min_charge' => round($minCharge, 2),
			];

			if (isset($existingByCode[$code])) {
				$existingByCode[$code]->update($payload);
			} else {
				$maxSortOrder++;
				$created = $modelClass::query()->create($payload + [
					'shipping_company_id' => $shippingCompanyId,
					'sort_order' => $maxSortOrder,
				]);
				$existingByCode[$code] = $created;
			}

			$usedCodes[$code] = true;
		}
	}

	private function generateShippingCompanyOdaChargeCode(int $shippingCompanyId, string $name, array $reservedCodes = [], ?int $ignoreId = null): string
	{
		$baseCode = Str::limit(str_replace('-', '_', Str::slug($name, '_')), 60, '');
		if ($baseCode === '') {
			$baseCode = 'oda_charge';
		}

		$code = $baseCode;
		$suffix = 2;

		while (
			in_array($code, $reservedCodes, true)
			|| ShippingCompanyOdaCharge::query()
				->where('shipping_company_id', $shippingCompanyId)
				->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
				->where('code', $code)
				->exists()
		) {
			$code = Str::limit($baseCode . '_' . $suffix, 80, '');
			$suffix++;
		}

		return $code;
	}

	private function generateShippingCompanyOpaChargeCode(int $shippingCompanyId, string $name, array $reservedCodes = [], ?int $ignoreId = null): string
	{
		$baseCode = Str::limit(str_replace('-', '_', Str::slug($name, '_')), 60, '');
		if ($baseCode === '') {
			$baseCode = 'opa_charge';
		}

		$code = $baseCode;
		$suffix = 2;

		while (
			in_array($code, $reservedCodes, true)
			|| ShippingCompanyOpaCharge::query()
				->where('shipping_company_id', $shippingCompanyId)
				->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
				->where('code', $code)
				->exists()
		) {
			$code = Str::limit($baseCode . '_' . $suffix, 80, '');
			$suffix++;
		}

		return $code;
	}

	private function renderManagePincodeCodToggle(int $pincodeId, bool $codEnabled): string
	{
		return '<label class="manage-pincode-switch">'
			. '<input type="checkbox" class="quick-cod-toggle" data-id="' . $pincodeId . '" data-setting="cod_enabled"' . ($codEnabled ? ' checked' : '') . '>'
			. '<span class="manage-pincode-slider"></span>'
			. '<span class="manage-pincode-switch-text">' . ($codEnabled ? 'Yes' : 'No') . '</span>'
			. '</label>';
	}

	private function renderManagePincodeOverallStatus(int $pickupStatus, int $deliveryStatus): string
	{
		if ($pickupStatus === 1 && $deliveryStatus === 1) {
			return '<span class="badge badge-success px-3 py-2">Active</span>';
		}

		return '<span class="badge badge-secondary px-3 py-2">Inactive</span>';
	}

	public function storeShipping(Request $request)
	{
		// Courier companies hold the platform's API URLs and keys: admins only
		abort_unless(Auth::user()->isAdminLevel(), 403);
		$user_id = Auth::user()->id;
		$data = $request->except('_token', 'logo');

		$data['user_id'] = $user_id;
		$data['created_at'] = Carbon::now();
		$data['updated_at'] = Carbon::now();

		$path_avatar = "storage/app/public/shipping-logo";
		if (!File::isDirectory($path_avatar)) {
			File::makeDirectory($path_avatar, 0777, true, true);
		}
		DB::beginTransaction();
		try {
			if ($request->hasFile('logo')) {
				$photo_image = $request->file('logo');
				$extension = $photo_image->getClientOriginalExtension();
				if (in_array($extension, ['png', 'jpeg', 'jpg'])) {
					$getAvatar = strtolower($request->name) . '.' . $extension;
					$filename = $path_avatar . "/" . $getAvatar;
					$photo_image->move($path_avatar, $getAvatar);
					$data['logo'] = $getAvatar;
				} else {
					return redirect()->route('shipping.company')->with('error', 'Only png, jpg and jpeg images are allowed.');
				}
			}

			if (ShippingCompany::whereName($request->name)->exists()) {
				return redirect()->route('shipping.company')->with('error', 'The shipping company name already exists.');
			}

			$shippingCompany = ShippingCompany::create($data);
			$this->syncDefaultB2CZonesForShippingCompany($shippingCompany->id);
			$this->syncDefaultB2BZonesForShippingCompany($shippingCompany->id);
			$this->syncDefaultShippingWeightsForShippingCompany($shippingCompany->id);

			// Automatically generate token for ID 5
			if ($shippingCompany->id == 5) {
				$delhiveryService = new \App\Services\DelhiveryService();
				$delhiveryService->getToken($shippingCompany);
			}

			// Only Delhivery (ID=1) and ShipMozo (ID=2) are supported
			if ($shippingCompany->id == 1) {
				$successMsg = 'The shipping company has been successfully added.';
			} elseif ($shippingCompany->id == 2) {
				$successMsg = 'The ShipMozo company has been successfully added.';
			} else {
				$successMsg = 'The shipping company has been successfully added.';
			}

			DB::commit();

			return redirect()->route('shipping.company')->with('success', $successMsg);
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->route('shipping.company')->with('error', $e->getMessage());
		}
	}

	public function updateShipping(Request $request)
	{
		// Courier companies hold the platform's API URLs and keys: admins only
		abort_unless(Auth::user()->isAdminLevel(), 403);
		$id = $request->id;
		$data = $request->except('_token', 'id', 'logo');

		$data['updated_at'] = Carbon::now();

		$path_avatar = "storage/app/public/shipping-logo";
		if (!File::isDirectory($path_avatar)) {
			File::makeDirectory($path_avatar, 0777, true, true);
		}
		DB::beginTransaction();
		try {
			if ($request->hasFile('logo')) {
				$photo_image = $request->file('logo');
				$extension = $photo_image->getClientOriginalExtension();
				if (in_array($extension, ['png', 'jpeg', 'jpg'])) {
					$getAvatar = strtolower($request->name) . '.' . $extension;
					$filename = $path_avatar . "/" . $getAvatar;
					$photo_image->move($path_avatar, $getAvatar);
					$data['logo'] = $getAvatar;
				} else {
					return redirect()->route('shipping.company')->with('error', 'Only png, jpg and jpeg images are allowed.');
				}
			}

			if (ShippingCompany::where('id', '!=', $id)->whereName($request->name)->exists()) {
				return redirect()->route('shipping.company')->with('error', 'The shipping company name already exists.');
			}

			$shippingCompany = ShippingCompany::findOrFail($id);
			$shippingCompany->update($data);
			$this->syncDefaultB2CZonesForShippingCompany($shippingCompany->id);
			$this->syncDefaultB2BZonesForShippingCompany($shippingCompany->id);
			$this->syncDefaultShippingWeightsForShippingCompany($shippingCompany->id);

			// Automatically refresh token for ID 5
			if ($id == 5) {
				$delhiveryService = new \App\Services\DelhiveryService();
				$tokenData = $delhiveryService->getToken($shippingCompany);
				if (isset($tokenData['success']) && $tokenData['success']) {
					$token = $tokenData['response']['data']['jwt'] ?? null;
					if ($token) {
						$shippingCompany->update([
							'api_key' => $token,
							'expired_at' => \Carbon\Carbon::now()->addHours(23)
						]);
					}
				}
			}

			// Only Delhivery (ID=1) and ShipMozo (ID=2) are supported
			if ($id == 1) {
				$successMsg = 'The shipping company has been updated successfully.';
			} elseif ($id == 2) {
				$successMsg = 'The ShipMozo company has been updated successfully.';
			} else {
				$successMsg = 'The shipping company has been updated successfully.';
			}

			DB::commit();

			return redirect()->route('shipping.company')->with('success', $successMsg);
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->route('shipping.company')->with('error', $e->getMessage());
		}
	}

	private function normalizeZoneCode($value): string
	{
		$value = strtoupper(trim((string) $value));
		$value = preg_replace('/[^A-Z0-9_-]/', '', $value);

		return mb_substr($value, 0, 50);
	}

	private function deriveZoneCodeFromHeading(string $heading): string
	{
		$heading = trim($heading);
		if ($heading === '') {
			return '';
		}

		$clean = preg_replace('/[^A-Za-z0-9 ]/', ' ', strtoupper($heading));
		$parts = preg_split('/\s+/', trim((string) $clean)) ?: [];
		$parts = array_values(array_filter($parts, fn($part) => $part !== ''));

		if (count($parts) > 1) {
			$abbr = '';
			foreach ($parts as $part) {
				$abbr .= mb_substr($part, 0, 1);
			}
			return mb_substr($abbr, 0, 10);
		}

		$single = preg_replace('/[^A-Z0-9]/', '', strtoupper($heading));
		return mb_substr((string) $single, 0, 10);
	}

	private function resolveZoneCode(?string $zoneCode, string $heading): string
	{
		$normalized = $this->normalizeZoneCode((string) $zoneCode);
		if ($normalized !== '') {
			return $normalized;
		}

		return $this->deriveZoneCodeFromHeading($heading);
	}

	private function deriveZoneNameFromCode(string $zoneCode): string
	{
		$zoneCode = strtoupper(trim($zoneCode));
		if ($zoneCode === '') {
			return '';
		}

		if (preg_match('/^(NE|N|S|E|W|C)(\d+)$/', $zoneCode, $matches)) {
			$regionMap = [
				'NE' => 'North East',
				'N' => 'North',
				'S' => 'South',
				'E' => 'East',
				'W' => 'West',
				'C' => 'Central',
			];

			$region = $regionMap[$matches[1]] ?? $matches[1];
			return $region . ' ' . $matches[2];
		}

		return $zoneCode;
	}

	private function resolveZoneName(?string $zoneName, string $heading, string $zoneCode = ''): string
	{
		$zoneName = trim((string) $zoneName);
		if ($zoneName !== '') {
			return $zoneName;
		}

		$heading = trim($heading);
		if ($heading !== '') {
			return $heading;
		}

		return $this->deriveZoneNameFromCode($zoneCode);
	}

	private function getMappedPincodeCount(array $zoneValues): int
	{
		$normalizedValues = collect($zoneValues)
			->map(fn($value) => trim((string) $value))
			->filter(fn($value) => $value !== '')
			->values()
			->all();

		if (empty($normalizedValues)) {
			return 0;
		}

		return (int) DB::table('zipcodes')
			->where(function ($query) use ($normalizedValues) {
				$query->whereIn('state', $normalizedValues)
					->orWhereIn('city', $normalizedValues);
			})
			->distinct('pincode')
			->count('pincode');
	}

	private function ensureRateCardSetupForCompanies(array $shippingCompanyIds): void
	{
		foreach ($shippingCompanyIds as $shippingCompanyId) {
			$shippingCompanyId = (int) $shippingCompanyId;
			if ($shippingCompanyId <= 0) {
				continue;
			}

			$this->syncDefaultB2CZonesForShippingCompany($shippingCompanyId);
			$this->syncDefaultB2BZonesForShippingCompany($shippingCompanyId);
			$this->syncDefaultShippingWeightsForShippingCompany($shippingCompanyId);
		}
	}

	private function getB2CZoneTemplateRows()
	{
		$templateCompanies = ShippingCompany::query()
			->visibleInRateCards()
			->where('status', 1)
			->orderByRaw('CASE WHEN id = 2 THEN 0 ELSE 1 END')
			->orderBy('id')
			->pluck('id');

		foreach ($templateCompanies as $templateCompanyId) {
			$zones = ZoneSetting::where('shipping_company_id', (int) $templateCompanyId)
				->where('rate_type', 'b2c')
				->orderBy('sort_order', 'asc')
				->get(['heading', 'zone_code', 'zone_name', 'slug', 'zone', 'sort_order', 'is_default']);

			if ($zones->isNotEmpty()) {
				return $zones->map(function ($zone) {
					$heading = (string) ($zone->heading ?? '');
					$zoneCode = $this->resolveZoneCode((string) ($zone->zone_code ?? ''), $heading);
					$zoneName = $this->resolveZoneName((string) ($zone->zone_name ?? ''), $heading, $zoneCode);

					return [
						'heading' => $zoneName,
						'zone_code' => $zoneCode,
						'zone_name' => $zoneName,
						'slug' => (string) ($zone->slug ?: str_replace('-', '_', Str::slug($zoneName))),
						'zone' => is_array($zone->zone) ? $zone->zone : [],
						'sort_order' => (int) ($zone->sort_order ?? 0),
						'is_default' => (int) ($zone->is_default ?? 0),
					];
				});
			}
		}

		return collect([
			['heading' => 'Within City', 'zone_code' => 'WC', 'zone_name' => 'Within City', 'slug' => 'within_city', 'zone' => [], 'sort_order' => 1, 'is_default' => 0],
			['heading' => 'Within State', 'zone_code' => 'WS', 'zone_name' => 'Within State', 'slug' => 'within_state', 'zone' => [], 'sort_order' => 2, 'is_default' => 0],
			['heading' => 'Metro To Metro', 'zone_code' => 'M2M', 'zone_name' => 'Metro To Metro', 'slug' => 'metro_to_metro', 'zone' => ['Andaman & Nicobar', 'Gujarat', 'Maharashtra'], 'sort_order' => 3, 'is_default' => 0],
			['heading' => 'ROI', 'zone_code' => 'ROI', 'zone_name' => 'ROI', 'slug' => 'roi', 'zone' => ['Delhi', 'Gujarat', 'Maharashtra'], 'sort_order' => 4, 'is_default' => 0],
			['heading' => 'J&K', 'zone_code' => 'JK', 'zone_name' => 'J&K', 'slug' => 'jk', 'zone' => ['Gujarat', 'Maharashtra'], 'sort_order' => 5, 'is_default' => 0],
		]);
	}

	private function syncDefaultB2CZonesForShippingCompany(int $shippingCompanyId): void
	{
		if ($shippingCompanyId <= 0) {
			return;
		}

		$existingCount = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', 'b2c')
			->count();

		if ($existingCount > 0) {
			return;
		}

		$templateZones = $this->getB2CZoneTemplateRows();
		if ($templateZones->isEmpty()) {
			return;
		}

		$insertData = [];
		foreach ($templateZones as $zone) {
			$heading = trim((string) (is_array($zone) ? ($zone['heading'] ?? '') : ($zone->heading ?? '')));
			$zoneCode = $this->resolveZoneCode(
				(string) (is_array($zone) ? ($zone['zone_code'] ?? '') : ($zone->zone_code ?? '')),
				$heading
			);
			$zoneName = $this->resolveZoneName(
				(string) (is_array($zone) ? ($zone['zone_name'] ?? '') : ($zone->zone_name ?? '')),
				$heading,
				$zoneCode
			);
			$slug = is_array($zone)
				? ($zone['slug'] ?? str_replace('-', '_', Str::slug($zoneName !== '' ? $zoneName : $zoneCode)))
				: ($zone->slug ?: str_replace('-', '_', Str::slug($zoneName !== '' ? $zoneName : $zoneCode)));
			$zoneValues = is_array($zone) ? ($zone['zone'] ?? []) : (is_array($zone->zone) ? $zone->zone : []);
			$sortOrder = is_array($zone) ? ($zone['sort_order'] ?? 0) : ($zone->sort_order ?? 0);
			$isDefault = is_array($zone) ? ($zone['is_default'] ?? 0) : ($zone->is_default ?? 0);

			$insertData[] = [
				'heading' => $zoneName,
				'zone_code' => $zoneCode,
				'zone_name' => $zoneName,
				'slug' => $slug,
				'zone' => json_encode($zoneValues),
				'shipping_company_id' => $shippingCompanyId,
				'rate_type' => 'b2c',
				'sort_order' => $sortOrder,
				'is_default' => $isDefault,
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		if (!empty($insertData)) {
			ZoneSetting::insert($insertData);
		}
	}

	private function getDefaultShippingWeightValues(): array
	{
		$templateCompanies = ShippingCompany::query()
			->visibleInRateCards()
			->where('status', 1)
			->orderByRaw('CASE WHEN id = 2 THEN 0 ELSE 1 END')
			->orderBy('id')
			->pluck('id');

		foreach ($templateCompanies as $templateCompanyId) {
			$weights = ShippingWeight::where('shipping_company_id', (int) $templateCompanyId)
				->orderBy('weight', 'asc')
				->pluck('weight')
				->map(fn ($weight) => (float) $weight)
				->filter(fn ($weight) => $weight > 0)
				->values()
				->all();

			if (!empty($weights)) {
				return $weights;
			}
		}

		return [0.5, 1, 2, 3, 5, 10];
	}

	private function syncDefaultShippingWeightsForShippingCompany(int $shippingCompanyId): void
	{
		if ($shippingCompanyId <= 0) {
			return;
		}

		$existingCount = ShippingWeight::where('shipping_company_id', $shippingCompanyId)->count();
		if ($existingCount > 0) {
			return;
		}

		$weights = $this->getDefaultShippingWeightValues();
		$insertData = [];

		foreach ($weights as $weight) {
			$weight = (float) $weight;
			if ($weight <= 0) {
				continue;
			}

			$insertData[] = [
				'shipping_company_id' => $shippingCompanyId,
				'weight' => $weight,
				'status' => 1,
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		if (!empty($insertData)) {
			ShippingWeight::insert($insertData);
		}
	}

	private function getB2BZoneTemplateRows()
	{
		return collect([
			['heading' => 'N1', 'zone_code' => 'N1', 'zone_name' => 'North 1', 'slug' => 'n1', 'zone' => ['Delhi', 'Gurgaon', 'Faridabad', 'Ghaziabad', 'Noida', 'Sahibabad', 'Jaipur'], 'sort_order' => 1, 'is_default' => 0],
			['heading' => 'N2', 'zone_code' => 'N2', 'zone_name' => 'North 2', 'slug' => 'n2', 'zone' => ['Chandigarh', 'Ludhiana', 'Dehradun'], 'sort_order' => 2, 'is_default' => 0],
			['heading' => 'N3', 'zone_code' => 'N3', 'zone_name' => 'North 3', 'slug' => 'n3', 'zone' => ['Haryana', 'Uttar Pradesh', 'Punjab', 'Uttarakhand', 'Rajasthan', 'Baddi', 'Jammu'], 'sort_order' => 3, 'is_default' => 0],
			['heading' => 'N4', 'zone_code' => 'N4', 'zone_name' => 'North 4', 'slug' => 'n4', 'zone' => ['Jammu and Kashmir', 'Himachal Pradesh', 'Ladakh'], 'sort_order' => 4, 'is_default' => 0],
			['heading' => 'C1', 'zone_code' => 'C1', 'zone_name' => 'Central 1', 'slug' => 'c1', 'zone' => ['Indore', 'Bhopal', 'Raipur'], 'sort_order' => 5, 'is_default' => 0],
			['heading' => 'C2', 'zone_code' => 'C2', 'zone_name' => 'Central 2', 'slug' => 'c2', 'zone' => ['Madhya Pradesh', 'Chhattisgarh'], 'sort_order' => 6, 'is_default' => 0],
			['heading' => 'W1', 'zone_code' => 'W1', 'zone_name' => 'West 1', 'slug' => 'w1', 'zone' => ['Gujarat'], 'sort_order' => 7, 'is_default' => 0],
			['heading' => 'W2', 'zone_code' => 'W2', 'zone_name' => 'West 2', 'slug' => 'w2', 'zone' => ['Dadra and Nagar Haveli and Daman and Diu', 'Maharashtra', 'Goa'], 'sort_order' => 8, 'is_default' => 0],
			['heading' => 'S1', 'zone_code' => 'S1', 'zone_name' => 'South 1', 'slug' => 's1', 'zone' => ['Hyderabad', 'Bangalore', 'Banglor', 'Chennai'], 'sort_order' => 9, 'is_default' => 0],
			['heading' => 'S2', 'zone_code' => 'S2', 'zone_name' => 'South 2', 'slug' => 's2', 'zone' => ['Telangana', 'Andhra Pradesh', 'Karnataka'], 'sort_order' => 10, 'is_default' => 0],
			['heading' => 'S3', 'zone_code' => 'S3', 'zone_name' => 'South 3', 'slug' => 's3', 'zone' => ['Tamil Nadu', 'Puducherry'], 'sort_order' => 11, 'is_default' => 0],
			['heading' => 'S4', 'zone_code' => 'S4', 'zone_name' => 'South 4', 'slug' => 's4', 'zone' => ['Kerala'], 'sort_order' => 12, 'is_default' => 0],
			['heading' => 'E1', 'zone_code' => 'E1', 'zone_name' => 'East 1', 'slug' => 'e1', 'zone' => ['Kolkata', 'Siliguri', 'Bhubaneshwar', 'Bhubaneswar', 'Patna', 'Ranchi'], 'sort_order' => 13, 'is_default' => 0],
			['heading' => 'E2', 'zone_code' => 'E2', 'zone_name' => 'East 2', 'slug' => 'e2', 'zone' => ['Odisha', 'West Bengal', 'Bihar', 'Jharkhand'], 'sort_order' => 14, 'is_default' => 0],
			['heading' => 'NE1', 'zone_code' => 'NE1', 'zone_name' => 'North East 1', 'slug' => 'ne1', 'zone' => ['Guwahati'], 'sort_order' => 15, 'is_default' => 0],
			['heading' => 'NE2', 'zone_code' => 'NE2', 'zone_name' => 'North East 2', 'slug' => 'ne2', 'zone' => ['Nagaland', 'Sikkim', 'Assam', 'Arunachal Pradesh', 'Meghalaya', 'Manipur', 'Mizoram', 'Tripura'], 'sort_order' => 16, 'is_default' => 0],
		]);
	}

	private function syncDefaultB2BZonesForShippingCompany(int $shippingCompanyId): void
	{
		if ($shippingCompanyId <= 0) {
			return;
		}

		$templateZones = $this->getB2BZoneTemplateRows();
		if ($templateZones->isEmpty()) {
			return;
		}

		$existingCount = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', 'b2b')
			->count();

		if ($existingCount > 0) {
			return;
		}

		$insertData = [];
		foreach ($templateZones as $zone) {
			$heading = trim((string) (is_array($zone) ? ($zone['heading'] ?? '') : ($zone->heading ?? '')));
			$zoneCode = $this->resolveZoneCode(
				(string) (is_array($zone) ? ($zone['zone_code'] ?? '') : ($zone->zone_code ?? '')),
				$heading
			);
			$zoneName = $this->resolveZoneName(
				(string) (is_array($zone) ? ($zone['zone_name'] ?? '') : ($zone->zone_name ?? '')),
				$heading,
				$zoneCode
			);
			$slug = is_array($zone)
				? ($zone['slug'] ?? str_replace('-', '_', Str::slug($zoneCode !== '' ? $zoneCode : $heading)))
				: ($zone->slug ?: str_replace('-', '_', Str::slug($zoneCode !== '' ? $zoneCode : $heading)));
			$zoneValues = is_array($zone) ? ($zone['zone'] ?? []) : (is_array($zone->zone) ? $zone->zone : []);
			$sortOrder = is_array($zone) ? ($zone['sort_order'] ?? 0) : ($zone->sort_order ?? 0);
			$isDefault = is_array($zone) ? ($zone['is_default'] ?? 0) : ($zone->is_default ?? 0);

			$insertData[] = [
				'heading' => $zoneCode !== '' ? $zoneCode : $heading,
				'zone_code' => $zoneCode,
				'zone_name' => $zoneName,
				'slug' => $slug,
				'zone' => json_encode($zoneValues),
				'shipping_company_id' => $shippingCompanyId,
				'rate_type' => 'b2b',
				'sort_order' => $sortOrder,
				'is_default' => $isDefault,
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		if (!empty($insertData)) {
			ZoneSetting::insert($insertData);
		}
	}

	private function getConflictingZoneValues(?int $shippingCompanyId, string $rateType, array $selectedZones = [], ?int $ignoreZoneId = null): array
	{
		if (!$shippingCompanyId || empty($selectedZones)) {
			return [];
		}

		$selectedMap = [];
		foreach ($selectedZones as $zone) {
			$zone = trim((string) $zone);
			if ($zone !== '') {
				$selectedMap[mb_strtolower($zone)] = $zone;
			}
		}

		if (empty($selectedMap)) {
			return [];
		}

		$existingZones = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', $rateType)
			->when($ignoreZoneId, function ($query) use ($ignoreZoneId) {
				$query->where('id', '!=', $ignoreZoneId);
			})
			->get(['zone']);

		$conflicts = [];
		foreach ($existingZones as $zoneSetting) {
			foreach ((array) $zoneSetting->zone as $zoneValue) {
				$key = mb_strtolower(trim((string) $zoneValue));
				if ($key !== '' && isset($selectedMap[$key])) {
					$conflicts[$key] = $selectedMap[$key];
				}
			}
		}

		return array_values($conflicts);
	}

	private function getAvailableZoneOptions(?int $shippingCompanyId, string $rateType, string $heading = '', ?int $ignoreZoneId = null): array
	{
		$normalizedHeading = strtolower(trim($heading));
		$allStates = DB::table('zipcodes')->select('state')->distinct()->orderBy('state')->pluck('state')->filter()->values()->all();
		$allCities = DB::table('zipcodes')->select('city')->distinct()->orderBy('city')->pluck('city')->filter()->values()->all();

		if ($normalizedHeading === 'within city') {
			$allOptions = $allCities;
		} elseif ($normalizedHeading === 'within state') {
			$allOptions = $allStates;
		} else {
			$allOptions = array_values(array_unique(array_merge($allStates, $allCities)));
			natcasesort($allOptions);
			$allOptions = array_values($allOptions);
		}

		if (!$shippingCompanyId) {
			return $allOptions;
		}

		$used = [];
		$zones = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', $rateType)
			->when($ignoreZoneId, function ($query) use ($ignoreZoneId) {
				$query->where('id', '!=', $ignoreZoneId);
			})
			->get(['zone']);

		foreach ($zones as $zoneSetting) {
			foreach ((array) $zoneSetting->zone as $value) {
				$value = trim((string) $value);
				if ($value !== '') {
					$used[mb_strtolower($value)] = true;
				}
			}
		}

		return array_values(array_filter($allOptions, function ($option) use ($used) {
			return !isset($used[mb_strtolower((string) $option)]);
		}));
	}

	public function productCategory()
	{
		$products = ProductCategory::latest()->get();
		return view('shipping.product-category', compact('products'));
	}

	public function storeProductCategory(Request $request)
	{
		$user_id = Auth::user()->id;
		$data = $request->except('_token');
		$data['user_id'] = $user_id;
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');
		if (ProductCategory::whereName($request->name)->exists()) {
			return response()->json(['status' => 'error', 'msg' => 'The product category name already exists.']);
		}
		try {
			$id = ProductCategory::insertGetId($data);
			return redirect()->route('product-category')->with('success', 'The product category has been successfully added.');
		} catch (\Exception $e) {
			return redirect()->route('product-category')->with('error', $e->getMessage());
		}
	}

	public function updateProductCategory(Request $request)
	{
		$id = $request->id;
		$data = $request->except('_token', 'id');
		$data['updated_at'] = date('Y-m-d H:i:s');
		if (ProductCategory::whereName($request->name)->exists()) {
			return response()->json(['status' => 'error', 'msg' => 'The product category name already exists.']);
		}
		try {
			ProductCategory::whereId($id)->update($data);
			return redirect()->route('product-category')->with('success', 'The product category have been updated successfully.');
		} catch (\Exception $e) {
			return redirect()->route('product-category')->with('error', $e->getMessage());
		}
	}

	public function deleteProductCategory($id)
	{
		try {
			$user = ProductCategory::find($id);
			$user->delete();
			return redirect()->route('product-category')->with('success', 'The product category has been successfully deleted.');
		} catch (\Exception $e) {
			return redirect()->route('product-category')->with('error', 'Something went wrong.');
		}
	}

	public function shipmentPackaging()
	{
		return view('shipping.packaging.index');
	}

	public function shipmentPackagingAjax(Request $request)
	{
		$draw = $request->get('draw');
		$start = $request->get("start");
		$limit = $request->get("length"); // Rows display per page

		$columnIndex_arr = $request->get('order');
		$columnName_arr = $request->get('columns');
		$order_arr = $request->get('order');
		$search_arr = $request->get('search');

		$columnIndex = $columnIndex_arr[0]['column']; // Column index
		$order = $columnName_arr[$columnIndex]['data']; // Column name
		$dir = $order_arr[0]['dir']; // asc or desc

		$role = Auth::user()->role;
		$id = Auth::user()->id;

		$query = Packaging::where('packagings.id', '!=', '');
		$totalData = $query->get()->count();
		$totalFiltered = Packaging::where('packagings.id', '!=', '');
		$values = Packaging::select('packagings.*');
		$values->offset($start)->limit($limit)->orderBy('packagings' . '.' . $order, $dir);

		if (!empty($request->input('search'))) {
			$search = $request->input('search');
			$values = $values->where(function ($query) use ($search) {
				return $query->where('packagings.name', 'LIKE', "%{$search}%")->orWhere('packagings.type', 'LIKE', "%{$search}%")->orWhere('packagings.sku', 'LIKE', "%{$search}%")->orWhere('packagings.created_at', 'LIKE', "%{$search}%");
			});

			$totalFiltered = $totalFiltered->where(function ($query) use ($search) {
				return $query->where('packagings.name', 'LIKE', "%{$search}%")->orWhere('packagings.type', 'LIKE', "%{$search}%")->orWhere('packagings.sku', 'LIKE', "%{$search}%")->orWhere('packagings.created_at', 'LIKE', "%{$search}%");
			});
		}

		$values = $values->get();
		$totalFiltered = $totalFiltered->count();

		$data = array();
		if (!empty($values)) {
			$i = $start + 1;
			foreach ($values as $value) {
				$mainData['id'] = $i;
				$mainData['name'] = $value->name;
				$mainData['package_dimension'] = $value->length . ' x ' . $value->width . ' x ' . $value->height . ' (cm)';
				$mainData['package_type'] = $value->type;
				$mainData['sku'] = $value->sku;
				$mainData['status'] = ($value->status == 1) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In-Active</span>';
				$mainData['created_at'] = date('d M Y', strtotime($value->created_at));
				$mainData['action'] = "";
				$mainData['action'] .= '
					<a href="' . url('shipment/packaging/edit', $value->id) . '" class="btn btn-icon waves-effect waves-light action-icon mr-1"> <i class="mdi mdi-pencil"></i> </a>';

				$mainData['action'] .= '<a href="' . url('shipment/packaging/delete', $value->id) . '" class="btn btn-icon waves-effect waves-light action-icon" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="" onClick="deleteRecord(this,event);"> <i class="mdi mdi-trash-can-outline"></i> </a>';

				$data[] = $mainData;
				$i++;
			}
		}

		$response = array(
			"draw" => intval($draw),
			"iTotalRecords" => $totalData,
			"iTotalDisplayRecords" => $totalFiltered,
			"aaData" => $data
		);

		echo json_encode($response);
		exit;
	}

	public function shipmentPackagingAdd()
	{
		return view('shipping.packaging.add');
	}

	public function shipmentPackagingStore(Request $request)
	{
		$user_id = Auth::user()->id;

		$data = $request->except('_token', 'images');
		$data['user_id'] = $user_id;
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');

		$path_avatar = "storage/app/public/packaging";

		if (!File::isDirectory($path_avatar)) {
			File::makeDirectory($path_avatar, 0777, true, true);
		}

		if (!empty($request->file('images'))) {
			$images = $request->file('images');
			$string = "";
			foreach ($images as $key => $image) {
				$getAvatar = time() . rand(111111, 999999) . '.' . $image->getClientOriginalExtension();
				$filename = $path_avatar . "/" . $getAvatar;
				$image->move($path_avatar, $getAvatar);
				$string .= $getAvatar . ',';
			}
			$data['images'] = rtrim($string, ',');
		}

		try {
			Packaging::insert($data);
			return response()->json(['status' => 'success', 'msg' => 'The packaging has been successfully added.']);
		} catch (\Exception $e) {
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	public function shipmentPackagingEdit($id)
	{
		$package = Packaging::whereId($id)->first();
		return view('shipping.packaging.edit', compact('package'));
	}

	public function shipmentPackagingUpdate(Request $request)
	{
		$user_id = Auth::user()->id;
		$id = $request->id;
		$data = $request->except('_token', 'images', 'id');
		$data['user_id'] = $user_id;
		$data['updated_at'] = date('Y-m-d H:i:s');

		$path_avatar = "storage/app/public/packaging";

		if (!File::isDirectory($path_avatar)) {
			File::makeDirectory($path_avatar, 0777, true, true);
		}

		if (!empty($request->file('images'))) {
			$images = $request->file('images');
			$string = "";
			foreach ($images as $key => $image) {
				$getAvatar = time() . rand(111111, 999999) . '.' . $image->getClientOriginalExtension();
				$filename = $path_avatar . "/" . $getAvatar;
				$image->move($path_avatar, $getAvatar);
				$string .= $getAvatar . ',';
			}
			$data['images'] = rtrim($string, ',');
		}

		try {
			Packaging::whereId($id)->update($data);
			return response()->json(['status' => 'success', 'msg' => 'The packaging has been successfully updated.']);
		} catch (\Exception $e) {
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	public function shipmentPackagingDelete($id)
	{
		try {
			$user = Packaging::find($id);
			$user->delete();
			return redirect()->route('shipment.packaging')->with('success', 'The product category has been successfully deleted.');
		} catch (\Exception $e) {
			return redirect()->route('shipment.packaging')->with('error', 'Something went wrong.');
		}
	}

	public function rateCalculator(Request $request)
	{
		$user = Auth::user();
		if ((int) config('setting.rate_calculator_show_to_user') == 0 && $user->role == "user") {
			abort(403, 'Permission Denied');
		}

		$selectedShippingCompany = null;
		$requestedCompanyId = (int) ($request->query('company_id', $request->query('shipping_company_id', 0)));
		if ($requestedCompanyId > 0) {
			$selectedShippingCompany = ShippingCompany::where('id', $requestedCompanyId)
				->where('status', 1)
				->first();
		}

		if ($selectedShippingCompany) {
			$activePincodeOptions = $this->getActiveCompanyPincodeOptions((int) $selectedShippingCompany->id);
			$hasB2cRates = RateCardDetail::query()
				->where('shipping_company_id', (int) $selectedShippingCompany->id)
				->where('status', 1)
				->exists();

			// Force B2C as B2B is disabled
			$preferredPackageType = 1;

			return view('rate-calculator-company', compact(
				'user',
				'selectedShippingCompany',
				'activePincodeOptions',
				'preferredPackageType'
			));
		}

		return view('rate-calculator', compact('user', 'selectedShippingCompany'));
	}

	public function rateCalculatorShow(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'type_of_package' => 'required|in:1',
			'pickup_code' => 'required|digits:6',
			'delivery_code' => 'required|digits:6',
			'payment_type' => 'required|in:cod,prepaid',
			'invoice_amount' => 'required|numeric|min:0',
			'weight' => 'required|numeric|gt:0',
			'cod_amount' => 'nullable|numeric|min:0',
			'company_id' => 'nullable|integer|exists:shipping_companies,id',
		], [
			'type_of_package.required' => 'Please select package type.',
			'pickup_code.required' => 'Please enter pickup pincode.',
			'pickup_code.digits' => 'Pickup pincode must be 6 digits.',
			'delivery_code.required' => 'Please enter delivery pincode.',
			'delivery_code.digits' => 'Delivery pincode must be 6 digits.',
			'payment_type.required' => 'Please select payment type.',
			'invoice_amount.required' => 'Please enter invoice amount.',
			'weight.required' => 'Please enter total weight.',
			'weight.gt' => 'Total weight must be greater than 0.',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
				'errors' => $validator->errors(),
				'view' => '',
			], 422);
		}

		try {
			$user = Auth::user();
			$requestedCompanyId = (int) ($request->input('company_id', $request->input('shipping_company_id', 0)));
			$singleCompanyMode = $requestedCompanyId > 0;
			$pickupCode = trim((string) $request->pickup_code);
			$deliveryCode = trim((string) $request->delivery_code);

			if ($singleCompanyMode) {
				$pickupAllowed = $this->isActiveCompanyPincode($requestedCompanyId, $pickupCode, 'from');
				$deliveryAllowed = $this->isActiveCompanyPincode($requestedCompanyId, $deliveryCode, 'to');
				if (!$pickupAllowed || !$deliveryAllowed) {
					return response()->json([
						'status' => 'error',
						'msg' => 'Please select active pickup and delivery pincodes configured for this company.',
						'view' => '',
					], 422);
				}
			}

			// $allowedShippingCompanyIds = RateCardDetail::where('rate_card_id', $user->rate_card_id)
			// 	->where('status', 1)
			// 	->pluck('shipping_company_id')
			// 	->unique()
			// 	->toArray();

			$shippingCompanies = ShippingCompany::whereStatus(1)
				->when($requestedCompanyId > 0, function ($query) use ($requestedCompanyId) {
					$query->where('id', $requestedCompanyId);
				})
				// ->whereIn('id', $allowedShippingCompanyIds)
				->get();

			if ($requestedCompanyId > 0 && $shippingCompanies->isEmpty()) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Selected shipping company is not active.',
					'view' => '',
				], 422);
			}

			$extraData = [
				'zipcodes' => Zipcode::whereIn('pincode', [$pickupCode, $deliveryCode])
					->get()
					->keyBy('pincode'),
				'commissions' => CourierCommission::with(['userCommissions' => function ($q) use ($user) {
					$q->where('user_id', $user->id);
				}])->get(),
			];
			$couriers = [];

			foreach ($shippingCompanies as $shippingCompany) {
				// Check if this specific company supports this route
				$isPickupAllowed = $this->isActiveCompanyPincode($shippingCompany->id, $pickupCode, 'from');
				$isDeliveryAllowed = $this->isActiveCompanyPincode($shippingCompany->id, $deliveryCode, 'to');

				if (!$isPickupAllowed || !$isDeliveryAllowed) {
					continue;
				}

				// Get rate using the Rate Card Service (handles all companies with internal rates)
				$response = $this->rateCardService->getRate($user, $shippingCompany, $request, 0, $extraData);
				if ($response && is_array($response)) {
					$couriers = array_merge($couriers, $response);
				}
			}

			if (empty($couriers)) {
				return response()->json([
					'status' => 'error',
					'msg' => $this->rateCardService->getUnavailableRateMessage($user, $request, $extraData),
					'view' => ''
				]);
			}

			if ($singleCompanyMode) {
				$selectedShippingCompany = $shippingCompanies->first();
				$calculationContext = $this->buildRateCalculatorContext($request, (int) $selectedShippingCompany->id);
				$view = view('shipping-cost-single', compact('couriers', 'selectedShippingCompany', 'calculationContext'))->render();
			} else {
				$view = view('shipping-cost', compact('couriers'))->render();
			}
			return response()->json(['status' => 'success', 'view' => $view]);
		} catch (\Throwable $e) {
			Log::error('Rate calculator failed.', [
				'user_id' => Auth::id(),
				'pickup_code' => $request->pickup_code,
				'delivery_code' => $request->delivery_code,
				'type_of_package' => $request->type_of_package,
				'exception' => $e,
			]);

			$message = config('app.debug')
				? $e->getMessage()
				: 'Rate calculation failed. Please contact admin if the issue persists.';

			return response()->json([
				'status' => 'error',
				'msg' => $message,
				'view' => '',
			], 500);
		}
	}

	private function buildRateCalculatorContext(Request $request, int $shippingCompanyId = 0): array
	{
		$pickupCode = trim((string) $request->input('pickup_code', ''));
		$deliveryCode = trim((string) $request->input('delivery_code', ''));

		$pickupZip = $this->getZipcodeByPincode($pickupCode);
		$deliveryZip = $this->getZipcodeByPincode($deliveryCode);
		$pickupZone = $shippingCompanyId > 0 ? $this->resolveZoneHeadingByPincode($shippingCompanyId, $pickupZip?->city, $pickupZip?->state) : null;
		$deliveryZone = $shippingCompanyId > 0 ? $this->resolveZoneHeadingByPincode($shippingCompanyId, $deliveryZip?->city, $deliveryZip?->state) : null;

		return [
			'pickup_code' => $pickupCode,
			'delivery_code' => $deliveryCode,
			'actual_weight' => (float) $request->input('weight', 0),
			'invoice_amount' => (float) $request->input('invoice_amount', 0),
			'volumetric_weight' => $this->calculateCalculatorVolumetricWeight($request),
			'pickup_city' => (string) ($pickupZip?->city ?? ''),
			'pickup_state' => (string) ($pickupZip?->state ?? ''),
			'delivery_city' => (string) ($deliveryZip?->city ?? ''),
			'delivery_state' => (string) ($deliveryZip?->state ?? ''),
			'pickup_zone' => (string) ($pickupZone ?? ''),
			'delivery_zone' => (string) ($deliveryZone ?? ''),
		];
	}

	private function getZipcodeByPincode(string $pincode): ?object
	{
		if (!preg_match('/^\d{6}$/', $pincode)) {
			return null;
		}

		return DB::table('zipcodes')
			->select('pincode', 'city', 'state')
			->where('pincode', $pincode)
			->first();
	}

	private function resolveZoneHeadingByPincode(int $shippingCompanyId, ?string $city, ?string $state): ?string
	{
		$city = trim((string) $city);
		$state = trim((string) $state);

		if ($shippingCompanyId <= 0 || ($city === '' && $state === '')) {
			return null;
		}

		// Try exact match in zone column (JSON)
		$zoneMatch = ZoneSetting::query()
			->where('shipping_company_id', $shippingCompanyId)
			->where(function ($query) use ($city, $state) {
				if ($city !== '') {
					$query->where(function ($q) use ($city) {
						$q->whereJsonContains('zone', $city)
							->orWhere('zone', 'LIKE', '%"' . $city . '"%')
							->orWhere('zone', 'LIKE', '%' . $city . '%');
					});
				}
				if ($state !== '') {
					$query->orWhere(function ($q) use ($state) {
						$q->whereJsonContains('zone', $state)
							->orWhere('zone', 'LIKE', '%"' . $state . '"%')
							->orWhere('zone', 'LIKE', '%' . $state . '%');
					});
				}
			})
			->orderBy('sort_order', 'asc')
			->first();

		if ($zoneMatch) {
			return $zoneMatch->heading ?: $zoneMatch->zone_name;
		}

		// Fallback to ROI/National
		$fallback = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where(function ($q) {
				$q->whereIn('slug', ['roi', 'rest_of_india', 'interstate', 'national', 'outer', 'all', 'any', 'general'])
					->orWhere('zone_name', 'LIKE', '%Rest of India%')
					->orWhere('zone_name', 'LIKE', '%ROI%');
			})
			->first();

		if ($fallback) {
			return $fallback->heading ?: $fallback->zone_name;
		}

		// Last resort for priority couriers (1: Shadowfax, 3: Delhivery)
		if (in_array($shippingCompanyId, [1, 3])) {
			$lastResort = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
				->orderBy('sort_order', 'asc')
				->first();
			if ($lastResort) {
				return $lastResort->heading ?: $lastResort->zone_name;
			}
		}

		return null;
	}

	private function getActiveCompanyPincodeOptions(int $shippingCompanyId): array
	{
		$defaults = [
			'from' => [],
			'to' => [],
			'default_from' => '110052',
			'default_to' => '400077',
			'has_active_pair' => false,
		];

		if ($shippingCompanyId <= 0 || !Schema::hasTable('shipping_company_pincodes')) {
			return $defaults;
		}

		$rows = ShippingCompanyPincode::query()
			->join('zipcodes', 'zipcodes.id', '=', 'shipping_company_pincodes.pincode_id')
			->where('shipping_company_pincodes.shipping_company_id', $shippingCompanyId)
			->where('shipping_company_pincodes.status', 1)
			->whereIn('shipping_company_pincodes.direction', ['from', 'to'])
			->orderBy('zipcodes.pincode', 'asc')
			->get([
				'shipping_company_pincodes.direction',
				'zipcodes.pincode',
				'zipcodes.city',
				'zipcodes.state',
			]);

		$from = $rows
			->where('direction', 'from')
			->map(fn ($row) => [
				'pincode' => (string) $row->pincode,
				'city' => (string) ($row->city ?? ''),
				'state' => (string) ($row->state ?? ''),
			])
			->unique('pincode')
			->values()
			->all();

		$to = $rows
			->where('direction', 'to')
			->map(fn ($row) => [
				'pincode' => (string) $row->pincode,
				'city' => (string) ($row->city ?? ''),
				'state' => (string) ($row->state ?? ''),
			])
			->unique('pincode')
			->values()
			->all();

		$defaults['from'] = $from;
		$defaults['to'] = $to;
		$defaults['default_from'] = $from[0]['pincode'] ?? $defaults['default_from'];
		$defaults['default_to'] = $to[0]['pincode'] ?? $defaults['default_to'];
		$defaults['has_active_pair'] = !empty($from) && !empty($to);

		return $defaults;
	}

	private function isActiveCompanyPincode(int $shippingCompanyId, string $pincode, string $direction): bool
	{
		if ($shippingCompanyId <= 0 || !preg_match('/^\d{6}$/', $pincode)) {
			return false;
		}

		$zip = Zipcode::where('pincode', $pincode)->first();
		if (!$zip) return false;

		// If NO mappings exist for this company/direction, consider it serviceable everywhere (Global)
		$hasMappingsForDirection = ShippingCompanyPincode::where('shipping_company_id', $shippingCompanyId)
			->where('direction', $direction)
			->exists();

		if (!$hasMappingsForDirection) {
			return true;
		}

		$scp = ShippingCompanyPincode::where('shipping_company_id', $shippingCompanyId)
			->where('pincode_id', $zip->id)
			->where('direction', $direction)
			->first();

		// MATCH DASHBOARD LOGIC: Serviceable if no record exists OR record is set to 'door'/empty/none
		if (!$scp || in_array($scp->service_mode, ['', 'none', 'door'])) {
			return true;
		}

		return false;
	}

	private function calculateCalculatorVolumetricWeight(Request $request): float
	{
		$lengths = $request->input('length', []);
		$widths = $request->input('width', []);
		$heights = $request->input('height', []);
		$boxes = $request->input('box', []);
		$unit = strtoupper((string) $request->input('dimension_unit', 'CM'));

		$lengths = is_array($lengths) ? $lengths : [$lengths];
		$widths = is_array($widths) ? $widths : [$widths];
		$heights = is_array($heights) ? $heights : [$heights];
		$boxes = is_array($boxes) ? $boxes : [$boxes];

		$maxRows = max(count($lengths), count($widths), count($heights));
		if ($maxRows <= 0) {
			return 0.0;
		}

		$totalVolumetricWeight = 0.0;
		for ($i = 0; $i < $maxRows; $i++) {
			$length = (float) ($lengths[$i] ?? 0);
			$width = (float) ($widths[$i] ?? 0);
			$height = (float) ($heights[$i] ?? 0);
			$qty = max(1.0, (float) ($boxes[$i] ?? 1));

			if ($length <= 0 || $width <= 0 || $height <= 0) {
				continue;
			}

			$lengthCm = $this->convertDimensionToCm($length, $unit);
			$widthCm = $this->convertDimensionToCm($width, $unit);
			$heightCm = $this->convertDimensionToCm($height, $unit);

			$volumetricWeight = (($lengthCm * $widthCm * $heightCm) / 5000) * $qty;
			$totalVolumetricWeight += $volumetricWeight;
		}

		return round($totalVolumetricWeight, 2);
	}

	private function convertDimensionToCm(float $value, string $unit): float
	{
		if ($unit === 'IN') {
			return $value * 2.54;
		}
		if ($unit === 'FT') {
			return $value * 30.48;
		}

		return $value;
	}

	public function pincodeCheck($pincode)
	{
		$pincode = trim((string) $pincode);
		if (!preg_match('/^\d{6}$/', $pincode)) {
			return response()->json(['status' => 'error', 'msg' => 'Invalid pincode'], 400);
		}

		$zip = Zipcode::select('pincode', 'city', 'state')->where('pincode', $pincode)->first();
		if (!$zip) {
			return response()->json(['status' => 'error', 'msg' => 'Pincode not found'], 404);
		}

		return response()->json([
			'status' => 'success',
			'city' => $zip->city,
			'state' => $zip->state
		]);
	}

	public function checkPincodeServiceability(Request $request)
	{
		$pincodeValue = trim((string) $request->pincode);
		$direction = $request->direction ?? 'from';

		if (!$pincodeValue) {
			return response()->json(['status' => 'error', 'msg' => 'Pincode required'], 400);
		}

		$zip = Zipcode::where('pincode', $pincodeValue)->first();
		if (!$zip) {
			return response()->json(['status' => 'not_found', 'msg' => 'Pincode not found']);
		}

		// Logic: Serviceable if at least one active courier supports it.
		// A courier supports it if:
		// 1. It has NO mappings in shipping_company_pincodes (global support)
		// OR
		// 2. It has a mapping for this pincode/direction with service_mode = 'door'
		$activeCompanies = ShippingCompany::where('status', 1)->get();
		$isServiceable = false;

		$supportingCouriers = [];
		foreach ($activeCompanies as $company) {
			// Check if the specific company manages this specific direction (Pickup vs Delivery)
			$hasMappingsForDirection = ShippingCompanyPincode::where('shipping_company_id', $company->id)
				->where('direction', $direction)
				->exists();

			if (!$hasMappingsForDirection) {
				$isServiceable = true;
				$supportingCouriers[] = $company->name . ' (Global)';
				continue;
			}

			$scp = ShippingCompanyPincode::where('shipping_company_id', $company->id)
				->where('pincode_id', $zip->id)
				->where('direction', $direction)
				->first();

			// MATCH DASHBOARD LOGIC: If no specific mapping exists, or it is set to 'door'/empty/none/null, it is Serviceable.
			// It is only NOT serviceable if it has a record AND that record is set to a restrictive mode (like 'drop' for pickup or 'pickup' for delivery)
			$isRestrictive = false;
			if ($scp && !in_array($scp->service_mode, ['', 'none', 'door'])) {
				$isRestrictive = true;
			}

			if (!$isRestrictive) {
				$isServiceable = true;
				$supportingCouriers[] = $company->name;
			}
		}

		return response()->json([
			'status' => 'success',
			'is_serviceable' => $isServiceable,
			'pincode' => $pincodeValue,
			'supporting_couriers' => $supportingCouriers
		]);
	}

	public function rateFreightBreakup(Request $request)
	{
		$originalData = $request->data ? json_decode($request->data ?? [], true) : [];
		if (empty($originalData)) {
            \Log::error('Rate breakup failed to decode request data', ['raw_data' => $request->data]);
			return response()->json(['status' => 'error', 'msg' => 'Something went wrong.']);
		}

		$responseData = $originalData['responseData'] ?? $originalData;
		$baseFreight = (float) ($responseData['freight_charges'] ?? 0);
		if ($baseFreight <= 0) {
			$baseFreight = (float) ($responseData['base_freight'] ?? ($responseData['rate'] ?? 0));
		}
		$additionalCharges = (float) ($responseData['percentage_amount'] ?? ($responseData['additional_charges'] ?? 0));
		$codCharges = (float) ($responseData['cod_charges'] ?? ($responseData['cod_charge'] ?? 0));
		$taxableAmount = $baseFreight + $additionalCharges + $codCharges;

		// Use provided values if they exist
		$providedTax = array_key_exists('tax', $responseData) ? (float) $responseData['tax'] : null;
		$providedTotal = array_key_exists('total_charges', $responseData) ? (float) $responseData['total_charges'] : null;

		$gstPercent = 18;
		$taxAmount = $providedTax !== null ? $providedTax : round(($taxableAmount * $gstPercent) / 100, 2);
		$totalCharges = $providedTotal !== null ? $providedTotal : round($taxableAmount + $taxAmount, 2);

		$responseData['gst_percent'] = $gstPercent;
		$responseData['base_freight'] = $baseFreight;
		$responseData['additional_charges'] = $additionalCharges;
		$responseData['cod_charges'] = $codCharges;
		$responseData['tax'] = $taxAmount;
		$responseData['total_charges'] = $totalCharges;
		$responseData['type_of_package'] = (int) ($responseData['type_of_package'] ?? ($originalData['type_of_package'] ?? 0));
		$responseData['chargeable_weight'] = (float) ($responseData['chargeable_weight'] ?? ($originalData['chargeable_weight'] ?? ($originalData['applicable_weight'] ?? 0)));
		// Ensure overhead_details are passed if they exist
		if (!isset($responseData['overhead_details']) && isset($originalData['overhead_details'])) {
			$responseData['overhead_details'] = $originalData['overhead_details'];
		}

		$view = view('freight-breakup-modal', compact('responseData'))->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function downloadSampleRateCard(Request $request, $id)
	{
		$rateCard = RateCard::findOrFail($id);
		$type = strtolower((string)$request->query('type', 'b2c'));
		$filename = $type === 'b2b'
			? "rate_card_b2b_" . Str::slug($rateCard->group_name) . ".xlsx"
			: "rate_card_b2c_" . Str::slug($rateCard->group_name) . ".xlsx";

		return $this->downloadRateCardWorkbook($type, $filename, $id);
	}

	public function downloadSampleRateCardTemplate(Request $request)
	{
		$type = strtolower((string)$request->query('type', 'b2c'));
		$filename = $type === 'b2b'
			? 'rate_card_b2b_sample.xlsx'
			: 'rate_card_b2c_sample.xlsx';

		return $this->downloadRateCardWorkbook($type, $filename);
	}

	private function downloadRateCardWorkbook(string $type, string $filename, $rateCardId = null)
	{
		$headers = [
			'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
			'Pragma' => 'public',
		];
		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		if ($type === 'b2b') {
			return Excel::download(new RateCardB2BExport($rateCardId), $filename, ExcelWriter::XLSX, $headers);
		}

		return Excel::download(new RateCardExport($rateCardId), $filename, ExcelWriter::XLSX, $headers);
	}


	public function importRateCard(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|mimes:csv,txt,xlsx,xls'
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
    }

    $type = strtolower((string)$request->query('type', $request->input('type', 'b2c')));
    if (!in_array($type, ['b2c', 'b2b'], true)) {
        return response()->json(['status' => 'error', 'msg' => 'Invalid rate type.']);
    }

    DB::beginTransaction();
    try {
        if ($type === 'b2c') {
            // Clear existing B2C
            DB::table('rate_card_details')->where('rate_card_id', $id)->delete();
            $rows = $this->parseB2CRateImportRows($request->file('file'));

            if (!empty($rows)) {
                $now = now();
                $insertData = array_map(function ($row) use ($id, $now) {
                    $row['rate_card_id'] = $id;
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    return $row;
                }, $rows);
                DB::table('rate_card_details')->insert($insertData);
            }
        } else {
            // --- B2B Import ---
            DB::table('rate_card_matrices')->where('rate_card_id', $id)->delete();
            $rows = $this->parseB2BRateImportRows($request->file('file'));

            if (!empty($rows)) {
                $now = now();
                $matrixData = array_map(function ($row) use ($id, $now) {
                    $row['rate_card_id'] = $id;
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    return $row;
                }, $rows);
                DB::table('rate_card_matrices')->insert($matrixData);
            }
        }

        DB::commit();
        return response()->json(['status' => 'success', 'msg' => 'Rates updated successfully from Excel!']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

    public function previewRateCardImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt,xlsx,xls',
            'type' => 'required|in:b2c,b2b',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()], 422);
        }

        try {
            $type = strtolower((string) $request->input('type', 'b2c'));
            $payload = $type === 'b2b'
                ? $this->buildB2BImportPreviewPayload($request->file('file'))
                : $this->buildB2CImportPreviewPayload($request->file('file'));

            if (!$this->hasImportPreviewData($type, $payload)) {
                $message = $type === 'b2b'
                    ? 'Invalid B2B import format. Please upload the B2B sample file.'
                    : 'Invalid B2C import format. Please upload the B2C sample file.';

                return response()->json([
                    'status' => 'error',
                    'msg' => $message,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'msg' => 'Rates imported into form. Click Save Rate Card to save.',
                'data' => $payload,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
        }
    }

    private function parseB2CRateImportRows($file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];
        $currentCompanyId = null;
        $currentMapping = [];
        $insertData = [];
        $allowedWeights = [];
        $normalizeWeight = function ($val) {
            if ($val === null || $val === '') return '';
            if (!is_numeric($val)) return '';
            $num = (float) $val;
            $str = number_format($num, 3, '.', '');
            return rtrim(rtrim($str, '0'), '.');
        };

        foreach ($rows as $data) {
            if (empty($data) || count($data) == 0 || (count($data) == 1 && empty($data[0]))) {
                $currentCompanyId = null;
                $currentMapping = [];
                $allowedWeights = [];
                continue;
            }

            $col0 = $data[0] ?? '';
            if (strpos((string) $col0, 'CID:') === 0) {
                $currentCompanyId = (int) str_replace('CID:', '', (string) $col0);
                $currentMapping = [];
                $allowedWeights = ShippingWeight::where('shipping_company_id', $currentCompanyId)
                    ->where('status', 1)
                    ->pluck('weight')
                    ->map(function ($w) use ($normalizeWeight) {
                        return $normalizeWeight($w);
                    })
                    ->filter()
                    ->values()
                    ->all();

                for ($i = 1; $i < count($data); $i++) {
                    $val = (string) ($data[$i] ?? '');
                    if (strpos($val, 'ZID:') === 0) {
                        $parts = explode('_', str_replace('ZID:', '', $val), 2);
                        $typeName = $parts[1] ?? 'rate';
                        if ($typeName == 'extra') $typeName = 'extra_rate';

                        $currentMapping[$i] = [
                            'type' => $typeName,
                            'zone_id' => (int) $parts[0],
                        ];
                    } elseif ($val == 'COD_CHARGE') {
                        $currentMapping[$i] = ['type' => 'cod_charges'];
                    } elseif ($val == 'COD_PERCENT') {
                        $currentMapping[$i] = ['type' => 'cod_percentage'];
                    }
                }
                continue;
            }

            if (!$currentCompanyId || empty($currentMapping)) {
                continue;
            }

            $weight = $data[0] ?? null;
            if ($weight === null || $weight === '' || !is_numeric($weight)) continue;
            $normalizedWeight = $normalizeWeight($weight);
            if ($normalizedWeight === '') continue;
            if (!empty($allowedWeights) && !in_array($normalizedWeight, $allowedWeights, true)) continue;

            $rowByZone = [];
            $codCharges = 0;
            $codPercentage = 0;

            foreach ($currentMapping as $index => $map) {
                $cellValue = trim((string) ($data[$index] ?? 0));
                if ($cellValue === '') $cellValue = '0';

                if (isset($map['zone_id'])) {
                    $zid = (int) $map['zone_id'];
                    if (!isset($rowByZone[$zid])) {
                        $rowByZone[$zid] = ['rate' => 0, 'extra_rate' => 0];
                    }
                    $rowByZone[$zid][$map['type']] = $cellValue;
                } else {
                    if ($map['type'] == 'cod_charges') $codCharges = $cellValue;
                    if ($map['type'] == 'cod_percentage') $codPercentage = $cellValue;
                }
            }

            foreach ($rowByZone as $zid => $rates) {
                $zone = ZoneSetting::where('id', $zid)->where('rate_type', 'b2c')->first();
                if (!$zone) continue;

                $insertData[] = [
                    'shipping_company_id' => $currentCompanyId,
                    'weight' => $weight,
                    'zone_setting_id' => $zid,
                    'zone_slug' => $zone->slug,
                    'rate' => $rates['rate'] ?? 0,
                    'extra_rate' => $rates['extra_rate'] ?? 0,
                    'cod_charges' => $codCharges,
                    'cod_percentage' => $codPercentage,
                    'status' => 1,
                ];
            }
        }

        return $insertData;
    }

    private function parseB2BRateImportRows($file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];
        $currentCompanyId = null;
        $destMapping = [];
        $matrixData = [];

        foreach ($rows as $data) {
            if (empty($data) || count($data) == 0 || (count($data) == 1 && empty($data[0]))) {
                $currentCompanyId = null;
                $destMapping = [];
                continue;
            }

            $col0 = $data[0] ?? '';
            if (strpos((string) $col0, 'CID:') === 0) {
                $currentCompanyId = (int) str_replace('CID:', '', (string) $col0);
                $destMapping = [];
                for ($i = 1; $i < count($data); $i++) {
                    $val = (string) ($data[$i] ?? '');
                    if (strpos($val, 'ZID:') === 0) {
                        $destMapping[$i] = (int) str_replace('ZID:', '', $val);
                    }
                }
                continue;
            }

            if (!$currentCompanyId || empty($destMapping)) continue;

            $originCell = (string) ($data[0] ?? '');
            if (strpos($originCell, 'OID:') !== 0) continue;
            $originId = (int) trim(str_replace('OID:', '', $originCell));
            if (!$originId) continue;

            $originZone = ZoneSetting::where('id', $originId)->where('rate_type', 'b2b')->first();
            if (!$originZone) continue;

            foreach ($destMapping as $idx => $destId) {
                $cellValue = trim((string) ($data[$idx] ?? ''));
                if ($cellValue === '' || !is_numeric($cellValue)) continue;

                $destZone = ZoneSetting::where('id', $destId)->where('rate_type', 'b2b')->first();
                if (!$destZone) continue;

                $matrixData[] = [
                    'shipping_company_id' => $currentCompanyId,
                    'from_zone_id' => $originId,
                    'to_zone_id' => $destId,
                    'rate_per_kg' => $cellValue,
                    'status' => 1,
                ];
            }
        }

        return $matrixData;
    }

    private function buildB2CImportPreviewPayload($file): array
    {
        $entries = $this->parseB2CRateImportRows($file);
        $shipping = [];

        foreach ($entries as $entry) {
            $companyId = (int) $entry['shipping_company_id'];
            $weight = (string) $entry['weight'];
            $zoneId = (int) $entry['zone_setting_id'];
            $zoneSlug = (string) $entry['zone_slug'];

            $shipping[$companyId]['status'] = (int) $entry['status'];
            $shipping[$companyId]['weights'][$weight][$zoneSlug . '_' . $zoneId] = $this->formatImportPreviewAmount($entry['rate']);
            $shipping[$companyId]['weights'][$weight][$zoneSlug . '_' . $zoneId . '_extra'] = $this->formatImportPreviewAmount($entry['extra_rate']);
            $shipping[$companyId]['weights'][$weight]['cod_charges'] = $this->formatImportPreviewAmount($entry['cod_charges']);
            $shipping[$companyId]['weights'][$weight]['cod_percentage'] = $this->formatImportPreviewAmount($entry['cod_percentage']);
        }

        return ['shipping' => $shipping];
    }

    private function buildB2BImportPreviewPayload($file): array
    {
        $entries = $this->parseB2BRateImportRows($file);
        $b2b = [];

        foreach ($entries as $entry) {
            $companyId = (int) $entry['shipping_company_id'];
            $fromZoneId = (int) $entry['from_zone_id'];
            $toZoneId = (int) $entry['to_zone_id'];
            $b2b[$companyId]['status'] = (int) $entry['status'];
            $b2b[$companyId]['matrix'][$fromZoneId][$toZoneId] = $this->formatImportPreviewAmount($entry['rate_per_kg']);
        }

        return ['b2b' => $b2b];
    }

    private function formatImportPreviewAmount($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function hasImportPreviewData(string $type, array $payload): bool
    {
        if ($type === 'b2b') {
            return !empty($payload['b2b']);
        }

        return !empty($payload['shipping']);
    }

    public function rateCardCreateForImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string|max:255',
            'rate_type' => 'required|in:b2c,b2b',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
        }

        DB::beginTransaction();
        try {
            $rateCardId = DB::table('rate_cards')->insertGetId([
                'group_name' => $request->input('group_name'),
                'rate_type' => $request->input('rate_type', 'b2c'),
                'is_rto_charge' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'msg' => 'Rate card created.',
                'id' => $rateCardId
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
        }
    }


	//Zone Setting
	public function rateZoneSetting(Request $request)
	{
		$user = Auth::user();

        Helper::userRestrict($user);

		$shippingCompanies = ShippingCompany::query()->visibleInRateCards()->whereStatus(1)->get();

		$states = DB::table('zipcodes')
			->select('state')
			->distinct()
			->orderBy('state')
			->pluck('state')
			->filter()
			->values();

		$cities = DB::table('zipcodes')
			->select('city')
			->distinct()
			->orderBy('city')
			->pluck('city')
			->filter()
			->values();

		return view('shipping.rate-card.zone-setting', compact('states', 'cities', 'shippingCompanies'));
	}

	/**
	 * DataTables ajax list
	 * returns json for datatable
	 */
	public function rateZoneAjaxList(Request $request)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$baseQuery = ZoneSetting::with(['shippingCompany:id,name'])
			->whereIn('shipping_company_id', $visibleCompanyIds);

		if ($request->filled('shipping_company_id')) {
			$baseQuery->where('shipping_company_id', $request->shipping_company_id);
		}

		if ($request->filled('rate_type')) {
			$baseQuery->where('rate_type', $request->rate_type);
		} else {
			$baseQuery->where('rate_type', 'b2c');
		}

		$query = clone $baseQuery;
		$search = trim((string) ($request->input('search.value') ?? $request->input('search') ?? ''));
		if ($search !== '') {
			$query->where(function ($subQuery) use ($search) {
				$subQuery->where('heading', 'like', "%{$search}%")
					->orWhere('zone_code', 'like', "%{$search}%")
					->orWhere('zone_name', 'like', "%{$search}%")
					->orWhere('zone', 'like', "%{$search}%")
					->orWhereHas('shippingCompany', function ($companyQuery) use ($search) {
						$companyQuery->where('name', 'like', "%{$search}%");
					});
			});
		}

		$recordsTotal = (clone $baseQuery)->count();
		$recordsFiltered = (clone $query)->count();
		$isPlainResponse = $request->boolean('plain');

		if ($isPlainResponse) {
			$data = $query
				->orderBy('shipping_company_id', 'asc')
				->orderBy('sort_order', 'asc')
				->get();
		} else {
			$start = (int) $request->input('start', 0);
			$length = (int) $request->input('length', 25);
			$data = $query
				->orderBy('shipping_company_id', 'asc')
				->orderBy('sort_order', 'asc')
				->skip($start)
				->take($length)
				->get();
		}

		$rows = $data->map(function ($row) {
			$heading = (string) ($row->heading ?? '');
			$zoneCode = $this->resolveZoneCode((string) ($row->zone_code ?? ''), $heading);
			$zoneName = $this->resolveZoneName((string) ($row->zone_name ?? ''), $heading, $zoneCode);
			$zoneValues = is_array($row->zone) ? $row->zone : ((array) ($row->zone ?? []));
			$mappedLocations = implode(', ', array_filter(array_map(fn($value) => trim((string) $value), $zoneValues)));
			$mappedPincodes = $this->getMappedPincodeCount($zoneValues);
			$isSystemZone = in_array(strtolower(trim($zoneName)), ['within city', 'within state'], true)
				|| in_array(strtolower(trim($heading)), ['within city', 'within state'], true);
			$canEdit = !$row->is_default && !$isSystemZone;

			return [
				'id' => $row->id,
				'zone_code' => $zoneCode,
				'zone_name' => $zoneName,
				'mapped_states_cities' => $mappedLocations,
				'mapped_pincodes' => $mappedPincodes,
				'heading' => $heading,
				'zone' => $mappedLocations,
				'shipping_company_id' => $row->shippingCompany->name ?? 'N/A',
				'created_at' => $row->created_at->format('Y-m-d h:i A'),
				'can_edit' => $canEdit,
				'action' => $canEdit
					? "<a href='javascript:void(0)' data-id='{$row->id}' class='zone-action btn-edit-zone' title='Edit'><i class='mdi mdi-pencil-outline'></i></a>
					<a href='javascript:void(0)' data-id='{$row->id}' class='zone-action btn-delete-zone' title='Delete'><i class='mdi mdi-trash-can-outline'></i></a>"
					: "",
			];
		});

		if ($isPlainResponse) {
			return response()->json([
				'status' => 'success',
				'data' => $rows,
				'total' => $recordsFiltered,
			]);
		}

		return response()->json([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data' => $rows,
		]);
	}

	public function storeZoneSetting(Request $request)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$zoneNameInput = trim((string) $request->input('zone_name', $request->input('heading', '')));
		$zoneCodeInput = $this->resolveZoneCode((string) $request->input('zone_code', ''), $zoneNameInput);
		if ($zoneNameInput === '') {
			$zoneNameInput = $this->deriveZoneNameFromCode($zoneCodeInput);
		}
		$normalizedZoneName = strtolower(trim($zoneNameInput));
		$isSystemZone = in_array($normalizedZoneName, ['within city', 'within state'], true);
		$headingValue = (string) $request->input('rate_type') === 'b2b'
			? ($zoneCodeInput !== '' ? $zoneCodeInput : $zoneNameInput)
			: $zoneNameInput;

		$payload = array_merge($request->all(), [
			'zone_code' => $zoneCodeInput,
			'zone_name' => $zoneNameInput,
		]);

		$validator = Validator::make($payload, [
			'zone_code' => [
				'required',
				'string',
				'max:50',
				Rule::unique('zone_settings', 'zone_code')->where(function ($query) use ($request) {
					return $query->where('shipping_company_id', $request->shipping_company_id)
						->where('rate_type', $request->rate_type);
				}),
			],
			'zone_name' => [
				'required',
				'string',
				'max:255',
				Rule::unique('zone_settings', 'zone_name')->where(function ($query) use ($request) {
					return $query->where('shipping_company_id', $request->shipping_company_id)
						->where('rate_type', $request->rate_type);
				}),
			],
			'zone' => $isSystemZone ? 'nullable|array' : 'required|array|min:1',
			'shipping_company_id' => ['required', 'integer', Rule::in($visibleCompanyIds)],
			'rate_type' => 'required|in:b2c,b2b'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()], 422);
		}

		$selectedZones = $isSystemZone ? [] : (array) $request->input('zone', []);
		$conflictingZones = $this->getConflictingZoneValues(
			$request->filled('shipping_company_id') ? (int) $request->shipping_company_id : null,
			(string) $request->rate_type,
			$selectedZones
		);

		if (!empty($conflictingZones)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'These values are already used in another zone: ' . implode(', ', $conflictingZones),
			], 422);
		}

		// ✅ Find the current max sort order (optionally per shipping company)
		$maxSort = ZoneSetting::when($request->shipping_company_id, function ($query) use ($request) {
			$query->where('shipping_company_id', $request->shipping_company_id);
		})->where('rate_type', $request->rate_type)->max('sort_order');

		// Default to 0 if no rows exist
		$nextSortOrder = $maxSort ? $maxSort + 1 : 1;

		// ✅ Create new zone with proper sort order
		$slugSeed = $zoneCodeInput !== '' ? $zoneCodeInput : $zoneNameInput;
		$zoneSetting = ZoneSetting::create([
			'heading' => $headingValue,
			'zone_code' => $zoneCodeInput,
			'zone_name' => $zoneNameInput,
			'slug' => str_replace('-', '_', Str::slug($slugSeed)),
			'zone' => $isSystemZone ? [] : ($request->zone ?? []),
			'shipping_company_id' => $request->shipping_company_id,
			'rate_type' => $request->rate_type,
			'sort_order' => $nextSortOrder,
			'is_default' => 0,
		]);

		return response()->json([
			'status' => 'success',
			'msg' => 'Zone created successfully.',
			'object' => $zoneSetting
		]);
	}


	public function getZoneSetting($id)
	{
		$zone = ZoneSetting::findOrFail($id);
		$zone->zone_code = $this->resolveZoneCode((string) ($zone->zone_code ?? ''), (string) ($zone->heading ?? ''));
		$zone->zone_name = $this->resolveZoneName((string) ($zone->zone_name ?? ''), (string) ($zone->heading ?? ''), (string) ($zone->zone_code ?? ''));

		return response()->json(['status' => 'success', 'object' => $zone]);
	}

	public function getZoneOptions(Request $request)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$validator = Validator::make($request->all(), [
			'shipping_company_id' => ['nullable', 'integer', Rule::in($visibleCompanyIds)],
			'rate_type' => 'required|in:b2c,b2b',
			'heading' => 'nullable|string',
			'q' => 'nullable|string|max:120',
			'ignore_zone_id' => 'nullable|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()], 422);
		}

		$search = trim((string) $request->input('q', ''));
		if ($search !== '') {
			$cities = Zipcode::query()
				->where('city', 'like', '%' . $search . '%')
				->orderBy('city')
				->limit(20)
				->pluck('city')
				->filter()
				->map(fn ($value) => trim((string) $value))
				->filter(fn ($value) => $value !== '');

			$states = Zipcode::query()
				->where('state', 'like', '%' . $search . '%')
				->orderBy('state')
				->limit(20)
				->pluck('state')
				->filter()
				->map(fn ($value) => trim((string) $value))
				->filter(fn ($value) => $value !== '');

			$options = $cities
				->merge($states)
				->unique()
				->sort()
				->values()
				->map(fn ($label) => ['id' => $label, 'text' => $label])
				->values();

			return response()->json([
				'status' => 'success',
				'results' => $options,
			]);
		}

		$options = $this->getAvailableZoneOptions(
			$request->filled('shipping_company_id') ? (int) $request->shipping_company_id : null,
			(string) $request->rate_type,
			(string) $request->input('heading', ''),
			$request->filled('ignore_zone_id') ? (int) $request->input('ignore_zone_id') : null
		);

		return response()->json([
			'status' => 'success',
			'options' => $options,
		]);
	}

	public function updateZoneSetting(Request $request, $id)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$zoneSetting = ZoneSetting::findOrFail($id);
		$currentZoneName = strtolower(trim((string) ($zoneSetting->zone_name ?: $zoneSetting->heading)));
		if (in_array($currentZoneName, ['within city', 'within state'], true)) {
			return response()->json(['status' => 'error', 'msg' => 'Edit is not allowed for this zone.'], 403);
		}

		$zoneNameInput = trim((string) $request->input('zone_name', $request->input('heading', '')));
		$zoneCodeInput = $this->resolveZoneCode((string) $request->input('zone_code', ''), $zoneNameInput);
		if ($zoneNameInput === '') {
			$zoneNameInput = $this->deriveZoneNameFromCode($zoneCodeInput);
		}
		$normalizedZoneName = strtolower(trim($zoneNameInput));
		$isSystemZone = in_array($normalizedZoneName, ['within city', 'within state'], true);
		$headingValue = (string) $request->input('rate_type') === 'b2b'
			? ($zoneCodeInput !== '' ? $zoneCodeInput : $zoneNameInput)
			: $zoneNameInput;

		$payload = array_merge($request->all(), [
			'zone_code' => $zoneCodeInput,
			'zone_name' => $zoneNameInput,
		]);

		$validator = Validator::make($payload, [
			'zone_code' => [
				'required',
				'string',
				'max:50',
				Rule::unique('zone_settings', 'zone_code')->ignore($id)->where(function ($query) use ($request) {
					return $query->where('shipping_company_id', $request->shipping_company_id)
						->where('rate_type', $request->rate_type);
				}),
			],
			'zone_name' => [
				'required',
				'string',
				'max:255',
				Rule::unique('zone_settings', 'zone_name')->ignore($id)->where(function ($query) use ($request) {
					return $query->where('shipping_company_id', $request->shipping_company_id)
						->where('rate_type', $request->rate_type);
				}),
			],
			'zone' => $isSystemZone ? 'nullable|array' : 'required|array|min:1',
			'shipping_company_id' => ['required', 'integer', Rule::in($visibleCompanyIds)],
			'rate_type' => 'required|in:b2c,b2b'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()], 422);
		}

		$selectedZones = $isSystemZone ? [] : (array) $request->input('zone', []);
		$conflictingZones = $this->getConflictingZoneValues(
			$request->filled('shipping_company_id') ? (int) $request->shipping_company_id : null,
			(string) $request->rate_type,
			$selectedZones,
			$id
		);

		if (!empty($conflictingZones)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'These values are already used in another zone: ' . implode(', ', $conflictingZones),
			], 422);
		}

		$slugSeed = $zoneCodeInput !== '' ? $zoneCodeInput : $zoneNameInput;
		$zoneSetting->update([
			'heading' => $headingValue,
			'zone_code' => $zoneCodeInput,
			'zone_name' => $zoneNameInput,
			'slug' => str_replace('-', '_', Str::slug($slugSeed)),
			'zone' => $isSystemZone ? [] : ($request->zone ?? []),
			'shipping_company_id' => $request->shipping_company_id,
			'rate_type' => $request->rate_type,
		]);

		return response()->json(['status' => 'success', 'msg' => 'Zone updated', 'object' => $zoneSetting]);
	}

	public function updateZoneSettingSortOrder(Request $request)
	{
		$ids = $request->input('ids'); // array of zone IDs in order

		if (is_array($ids)) {
			foreach ($ids as $index => $id) {
				ZoneSetting::where('id', $id)->update(['sort_order' => $index + 1]);
			}
			return response()->json(['status' => 'success', 'msg' => 'Sort order updated']);
		}

		return response()->json(['status' => 'error', 'msg' => 'Invalid request'], 400);
	}

	public function deleteZoneSetting($id)
	{
		try {
			$zoneSetting = ZoneSetting::findOrFail($id);
			$currentZoneName = strtolower(trim((string) ($zoneSetting->zone_name ?: $zoneSetting->heading)));

			if (in_array($currentZoneName, ['within city', 'within state'], true)) {
				return response()->json(['status' => 'error', 'msg' => 'Delete is not allowed for this zone.'], 403);
			}

			$zoneSetting->delete();

			return response()->json(['status' => 'success', 'msg' => 'Zone deleted successfully.']);
		} catch (\Illuminate\Database\QueryException $e) {
			if ($e->getCode() == 23000) {
				return response()->json(['status' => 'error', 'msg' => 'Cannot delete this zone. It is currently in use (e.g., linked to a Rate Card).']);
			}
			return response()->json(['status' => 'error', 'msg' => 'Failed to delete zone due to a database error.']);
		} catch (\Exception $e) {
			return response()->json(['status' => 'error', 'msg' => 'Failed to delete zone: ' . $e->getMessage()]);
		}
	}

	public function rateCard()
	{
		$user = Auth::user();

        Helper::userRestrict($user);
		return view('shipping.rate-card.index');
	}

	public function rateCardAjax(Request $request)
	{
		$query = RateCard::query();

		// Filter by rate type
		if ($request->filled('rate_type')) {
			$query->where('rate_type', $request->rate_type);
		} else {
			$query->where('rate_type', 'b2c');
		}

		// Server-side pagination & search (basic)
		if ($search = $request->input('search.value')) {
			$query->where('group_name', 'like', "%{$search}%");
		}

		$total = $query->count();

		// ordering & pagination
		$start = $request->input('start', 0);
		$length = $request->input('length', 25);

		$data = $query->orderBy('id', 'desc')->skip($start)->take($length)->get();

		$isEmbedded = $request->boolean('embed');
		$rows = $data->map(function ($row, $idx) use ($isEmbedded) {
			$editUrl = url('rate/card/edit/' . $row->id) . ($isEmbedded ? '?embed=1' : '');
			$previewUrl = route('rate.price-list', ['rate_card_id' => $row->id, 'type' => $row->rate_type] + ($isEmbedded ? ['embed' => 1] : []));

			return [
				'id' => $idx + 1,
				'group_name' => $row->group_name,
				'action' => "<a href=" . $editUrl . " class='btn btn-icon waves-effect waves-light action-icon mr-1'>
				<i class='mdi mdi-pencil'></i>
				</a>
				<a href=" . $previewUrl . " class='btn btn-icon waves-effect waves-light action-icon mr-1'>
				<i class='mdi mdi-eye'></i>
				</a>
				<a href=" . route('rate.card.delete', $row->id) . "  class='btn btn-icon waves-effect waves-light action-icon mr-1 delete-rate-card-btn'>
				<i class='mdi mdi-trash-can-outline'></i>
				</a>"
			];
		});

		return response()->json([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $total,
			'recordsFiltered' => $total,
			'data' => $rows,
		]);
	}

	public function rateCardAdd()
	{
		$user = Auth::user();

        Helper::userRestrict($user);
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$this->ensureRateCardSetupForCompanies($visibleCompanyIds);
		$shippingCompanies = ShippingCompany::query()->visibleInRateCards()->whereStatus(1)->get();
		$zoneShippingSettings = ZoneSetting::with('shippingCompany:id,name')
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->where('rate_type', 'b2c')
			->orderBy('sort_order', 'asc')
			->get()
			->groupBy('shipping_company_id');

		$b2bZones = ZoneSetting::with('shippingCompany:id,name')
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->where('rate_type', 'b2b')
			->orderBy('sort_order', 'asc')
			->get()
			->groupBy('shipping_company_id');

		$shippingWeights = ShippingWeight::whereIn('shipping_company_id', $visibleCompanyIds)
			->where('status', 1)
			->orderBy('weight', 'asc')
			->get()
			->groupBy('shipping_company_id');
		$otherChargeMasters = OtherChargeMaster::where('is_active', 1)
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->orderBy('name')
			->get()
			->groupBy('shipping_company_id');

		return view('shipping.rate-card.add', compact('zoneShippingSettings', 'shippingWeights', 'b2bZones', 'shippingCompanies', 'otherChargeMasters'));
	}

	public function rateCardStore(Request $request)
	{
		DB::beginTransaction();

		try {
			$this->validateRateCardPayload($request);

			// Create Rate Card
			$rateCardId = DB::table('rate_cards')->insertGetId([
				'group_name' => $request->input('group_name'),
				'rate_type' => $request->input('rate_type', 'b2c'),
				'is_rto_charge' => $request->input('is_rto_charge', 0),
				'created_at' => now(),
				'updated_at' => now(),
			]);

			// B2C Rates
			$cardData = [];
			$shippingInputs = $request->input('shipping');
			if (is_array($shippingInputs)) {
				$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
				// Load zone settings grouped by shipping company (filter by B2C)
				$zoneShippingSettings = ZoneSetting::whereIn('shipping_company_id', $visibleCompanyIds)
					->where('rate_type', 'b2c')->orderBy('sort_order', 'asc')
					->get()
					->groupBy('shipping_company_id');

				foreach ($shippingInputs as $shippingCompany) {
					$shippingCompanyId = $shippingCompany['shipping_company_id'] ?? null;
					$status = $shippingCompany['status'] ?? 1;

					if (!$shippingCompanyId) continue;

					foreach ($shippingCompany as $weightKey => $weightRates) {
						if (!is_array($weightRates)) {
							continue;
						}

						// Check if this shipping company has zone settings
						if (!isset($zoneShippingSettings[$shippingCompanyId])) {
							continue;
						}
						// Handle COD separately
						$codCharges = $weightRates['cod_charges'] ?? null;
						$codPercentage = $weightRates['cod_percentage'] ?? null;

						foreach ($zoneShippingSettings[$shippingCompanyId] as $zoneSetting) {
							$zoneSettingId = $zoneSetting->id;
							$zoneSlug = $zoneSetting->slug;

							// Get rate and extra values safely
							$rate = $weightRates[$zoneSlug . '_' . $zoneSettingId] ?? 0;
							$extra = $weightRates[$zoneSlug . '_' . $zoneSettingId . '_extra'] ?? 0;

							$cardData[] = [
								'rate_card_id' => $rateCardId,
								'shipping_company_id' => $shippingCompanyId,
								'weight' => $weightKey,
								'zone_setting_id' => $zoneSettingId,
								'zone_slug' => $zoneSlug,
								'rate' => $rate,
								'extra_rate' => $extra,
								'cod_charges' => $codCharges,
								'cod_percentage' => $codPercentage,
								'status' => $status,
								'created_at' => now(),
								'updated_at' => now(),
							];
						}
					}
				}

				if (!empty($cardData)) {
					DB::table('rate_card_details')->insert($cardData);
				}
			}

            // --- B2B Rate Matrix ---
            $b2bData = $request->input('b2b', []);
            if ($request->has('b2b_json')) {
                $decoded = json_decode($request->input('b2b_json'), true);
                if (is_array($decoded)) {
                    $b2bData = $decoded;
                }
            }

            if (is_array($b2bData)) {
                $matrixData = [];
                foreach ($b2bData as $companyId => $fromZones) {
                    if (!is_array($fromZones)) continue;
                    foreach ($fromZones as $fromZoneId => $toZones) {
                        if (!is_array($toZones)) continue;
                        foreach ($toZones as $toZoneId => $rate) {
                            if ($rate > 0) {
                                $matrixData[] = [
                                    'rate_card_id' => $rateCardId,
                                    'shipping_company_id' => $companyId,
                                    'from_zone_id' => $fromZoneId,
                                    'to_zone_id' => $toZoneId,
                                    'rate_per_kg' => $rate,
                                    'status' => $request->input("b2b_status.$companyId", 1),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    }
                }
                if (!empty($matrixData)) {
                    DB::table('rate_card_matrices')->insert($matrixData);
                }
            }

			// --- Other Charges ---
			$otherChargesInput = $request->input('other_charges', []);
			if ($request->has('other_charges_json')) {
				$decoded = json_decode($request->input('other_charges_json'), true);
				if (is_array($decoded)) {
					$otherChargesInput = $decoded;
				}
			}

			if (is_array($otherChargesInput)) {
				$companyIds = collect(array_keys($otherChargesInput))->map(function ($companyId) {
					return (int) $companyId;
				})->filter(function ($companyId) {
					return $companyId > 0;
				})->unique()->values();

				$allowedMasterIdsByCompany = OtherChargeMaster::where('is_active', 1)
					->whereIn('shipping_company_id', $companyIds->all())
					->get(['id', 'shipping_company_id'])
					->groupBy('shipping_company_id')
					->map(function ($rows) {
						return $rows->pluck('id')->map(function ($id) {
							return (int) $id;
						})->all();
					});

				$otherChargesData = [];
				foreach ($otherChargesInput as $companyId => $masters) {
					if (!is_array($masters)) continue;
					$companyId = (int) $companyId;
					$allowedIds = $allowedMasterIdsByCompany->get($companyId, []);
					foreach ($masters as $masterId => $config) {
						if (isset($config['_enabled']) && $config['_enabled'] == 1) {
							$masterId = (int) $masterId;
							if (!in_array($masterId, $allowedIds, true)) {
								continue;
							}
							unset($config['_enabled']);
							$otherChargesData[] = [
								'rate_card_id' => $rateCardId,
								'shipping_company_id' => $companyId,
								'other_charge_master_id' => $masterId,
								'config' => json_encode($config),
								'created_at' => now(),
								'updated_at' => now(),
							];
						}
					}
				}
				if (!empty($otherChargesData)) {
					DB::table('rate_card_other_charges')->insert($otherChargesData);
				}
			}

			DB::commit();

			return response()->json([
				'status' => 'success',
				'msg' => 'Rate card created successfully!',
			]);
		} catch (ValidationException $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Please fix the highlighted errors.',
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Rate card create failed', [
				'rate_type' => $request->input('rate_type'),
				'group_name' => $request->input('group_name'),
				'message' => $e->getMessage(),
			]);
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
		}
	}

	public function rateCardEdit($id)
	{
		$user = Auth::user();

        Helper::userRestrict($user);

		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$this->ensureRateCardSetupForCompanies($visibleCompanyIds);
		$shippingCompanies = ShippingCompany::query()->visibleInRateCards()->whereStatus(1)->get();
		$zoneShippingSettings = ZoneSetting::with('shippingCompany:id,name')
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->where('rate_type', 'b2c')
			->orderBy('sort_order', 'asc')
			->get()
			->groupBy('shipping_company_id');
		$b2bZones = ZoneSetting::with('shippingCompany:id,name')
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->where('rate_type', 'b2b')
			->orderBy('sort_order', 'asc')
			->get()
			->groupBy('shipping_company_id');
		$rateCard = RateCard::with(['rateCardDetails', 'rateCardMatrices'])->findOrFail($id);
		$shippingWeights = ShippingWeight::whereIn('shipping_company_id', $visibleCompanyIds)
			->where('status', 1)
			->orderBy('weight', 'asc')
			->get()
			->groupBy('shipping_company_id');
		$otherChargeMasters = OtherChargeMaster::where('is_active', 1)
			->whereIn('shipping_company_id', $visibleCompanyIds)
			->orderBy('name')
			->get()
			->groupBy('shipping_company_id');
		return view('shipping.rate-card.edit', compact('rateCard', 'zoneShippingSettings', 'shippingWeights', 'b2bZones', 'shippingCompanies', 'otherChargeMasters'));
	}

	public function rateCardUpdate(Request $request, $id)
	{
		DB::beginTransaction();

		try {
			$this->validateRateCardPayload($request, (int) $id);
			// 1️⃣ Update main rate card
			DB::table('rate_cards')
				->where('id', $id)
				->update([
					'group_name' => $request->input('group_name'),
					'rate_type' => $request->input('rate_type', 'b2c'),
					'is_rto_charge' => $request->input('is_rto_charge', 0),
					'updated_at' => now(),
				]);

			// 2️⃣ Delete old and insert fresh for B2C
			DB::table('rate_card_details')->where('rate_card_id', $id)->delete();

			$shippingInputs = $request->input('shipping', []);
			if (is_array($shippingInputs)) {
				$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
				$zoneShippingSettings = ZoneSetting::whereIn('shipping_company_id', $visibleCompanyIds)
					->where('rate_type', 'b2c')
					->select('id', 'slug', 'shipping_company_id')
					->get()
					->groupBy('shipping_company_id');

				$cardData = [];
				foreach ($shippingInputs as $shippingCompany) {
					$shippingCompanyId = $shippingCompany['shipping_company_id'] ?? null;
					$status = $shippingCompany['status'] ?? 1;

					if (!$shippingCompanyId) continue;

					foreach ($shippingCompany as $weightKey => $weightRates) {
						if (!is_array($weightRates)) continue;

						foreach ($zoneShippingSettings[$shippingCompanyId] ?? [] as $zoneSetting) {
							$zoneSettingId = $zoneSetting->id;
							$slug = $zoneSetting->slug;

							$rate = $weightRates["{$slug}_{$zoneSettingId}"] ?? 0;
							$extra = $weightRates["{$slug}_{$zoneSettingId}_extra"] ?? 0;

							$cardData[] = [
								'rate_card_id' => $id,
								'shipping_company_id' => $shippingCompanyId,
								'weight' => $weightKey,
								'zone_setting_id' => $zoneSettingId,
								'zone_slug' => $slug,
								'rate' => $rate,
								'extra_rate' => $extra,
								'cod_charges' => $weightRates['cod_charges'] ?? 0,
								'cod_percentage' => $weightRates['cod_percentage'] ?? 0,
								'status' => $status,
								'created_at' => now(),
								'updated_at' => now(),
							];
						}
					}
				}

				if (!empty($cardData)) {
					DB::table('rate_card_details')->insert($cardData);
				}
			}

			// 3️⃣ Delete old and insert fresh for B2B Matrix
			DB::table('rate_card_matrices')->where('rate_card_id', $id)->delete();
			$b2bData = $request->input('b2b', []);
			if ($request->has('b2b_json')) {
				$decoded = json_decode($request->input('b2b_json'), true);
				if (is_array($decoded)) {
					$b2bData = $decoded;
				}
			}
			if (is_array($b2bData)) {
				$matrixData = [];
				foreach ($b2bData as $companyId => $fromZones) {
					if (!is_array($fromZones)) continue;
					foreach ($fromZones as $fromZoneId => $toZones) {
						if (!is_array($toZones)) continue;
						foreach ($toZones as $toZoneId => $rate) {
							if ($rate > 0) {
								$matrixData[] = [
									'rate_card_id' => $id,
									'shipping_company_id' => $companyId,
									'from_zone_id' => $fromZoneId,
									'to_zone_id' => $toZoneId,
									'rate_per_kg' => $rate,
									'status' => $request->input("b2b_status.$companyId", 1),
									'created_at' => now(),
									'updated_at' => now(),
								];
							}
						}
					}
				}
				if (!empty($matrixData)) {
					DB::table('rate_card_matrices')->insert($matrixData);
				}
			}

			// 4️⃣ Update Other Charges
			DB::table('rate_card_other_charges')->where('rate_card_id', $id)->delete();
			$otherChargesInput = $request->input('other_charges', []);
			if ($request->has('other_charges_json')) {
				$decoded = json_decode($request->input('other_charges_json'), true);
				if (is_array($decoded)) {
					$otherChargesInput = $decoded;
				}
			}

			if (is_array($otherChargesInput)) {
				$companyIds = collect(array_keys($otherChargesInput))->map(function ($companyId) {
					return (int) $companyId;
				})->filter(function ($companyId) {
					return $companyId > 0;
				})->unique()->values();

				$allowedMasterIdsByCompany = OtherChargeMaster::where('is_active', 1)
					->whereIn('shipping_company_id', $companyIds->all())
					->get(['id', 'shipping_company_id'])
					->groupBy('shipping_company_id')
					->map(function ($rows) {
						return $rows->pluck('id')->map(function ($id) {
							return (int) $id;
						})->all();
					});

				$otherChargesData = [];
				foreach ($otherChargesInput as $companyId => $masters) {
					if (!is_array($masters)) continue;
					$companyId = (int) $companyId;
					$allowedIds = $allowedMasterIdsByCompany->get($companyId, []);
					foreach ($masters as $masterId => $config) {
						if (isset($config['_enabled']) && $config['_enabled'] == 1) {
							$masterId = (int) $masterId;
							if (!in_array($masterId, $allowedIds, true)) {
								continue;
							}
							unset($config['_enabled']);
							$otherChargesData[] = [
								'rate_card_id' => $id,
								'shipping_company_id' => $companyId,
								'other_charge_master_id' => $masterId,
								'config' => json_encode($config),
								'created_at' => now(),
								'updated_at' => now(),
							];
						}
					}
				}
				if (!empty($otherChargesData)) {
					DB::table('rate_card_other_charges')->insert($otherChargesData);
				}
			}

			DB::commit();

			return response()->json([
				'status' => 'success',
				'msg' => 'Rate card updated successfully!',
			]);
		} catch (ValidationException $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Please fix the highlighted errors.',
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Rate card update failed', [
				'rate_card_id' => $id,
				'rate_type' => $request->input('rate_type'),
				'group_name' => $request->input('group_name'),
				'message' => $e->getMessage(),
			]);
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
		}
	}

	public function rateCardDelete($id)
	{
		$user = Auth::user();

        Helper::userRestrict($user);
		$rateCardId = (int) $id;

		DB::beginTransaction();
		try {
			DB::table('users')
				->where('rate_card_id', $rateCardId)
				->orWhere('b2c_rate_card_id', $rateCardId)
				->orWhere('b2b_rate_card_id', $rateCardId)
				->update([
					'rate_card_id' => DB::raw("CASE WHEN rate_card_id = {$rateCardId} THEN 0 ELSE rate_card_id END"),
					'b2c_rate_card_id' => DB::raw("CASE WHEN b2c_rate_card_id = {$rateCardId} THEN 0 ELSE b2c_rate_card_id END"),
					'b2b_rate_card_id' => DB::raw("CASE WHEN b2b_rate_card_id = {$rateCardId} THEN 0 ELSE b2b_rate_card_id END"),
				]);

			// First delete all related rate card details
			DB::table('rate_card_details')->where('rate_card_id', $rateCardId)->delete();
			DB::table('rate_card_matrices')->where('rate_card_id', $rateCardId)->delete();
			DB::table('rate_card_other_charges')->where('rate_card_id', $rateCardId)->delete();

			// Then delete the main rate card
			DB::table('rate_cards')->where('id', $rateCardId)->delete();

			DB::commit();
			return response()->json(['status' => 'success', 'msg' => 'Rate card deleted successfully.']);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()]);
		}
	}

	protected function validateRateCardPayload(Request $request, ?int $rateCardId = null): void
	{
		Validator::make($request->all(), [
			'group_name' => ['required', 'string', 'max:255'],
			'rate_type' => ['required', Rule::in(['b2c', 'b2b'])],
		])->validate();

		$duplicateErrors = [];
		$otherCharges = $request->input('other_charges', []);
		if ($request->has('other_charges_json')) {
			$decoded = json_decode($request->input('other_charges_json'), true);
			if (is_array($decoded)) {
				$otherCharges = $decoded;
			}
		}

		if (is_array($otherCharges)) {
			foreach ($otherCharges as $companyId => $masters) {
				if (!is_array($masters)) {
					continue;
				}

				$selectedMasterIds = [];
				foreach ($masters as $masterId => $config) {
					if (!is_array($config) || (string) ($config['_enabled'] ?? '0') !== '1') {
						continue;
					}

					$selectedMasterIds[] = (string) $masterId;
				}

				$duplicates = array_keys(array_filter(array_count_values($selectedMasterIds), function ($count) {
					return $count > 1;
				}));

				if (!empty($duplicates)) {
					$duplicateNames = OtherChargeMaster::where('shipping_company_id', (int) $companyId)
						->whereIn('id', $duplicates)
						->pluck('name')
						->all();
					$duplicateErrors["other_charges.$companyId"] = [
						'Duplicate other charges are not allowed for the same courier: ' . implode(', ', $duplicateNames),
					];
				}
			}
		}

		if (!empty($duplicateErrors)) {
			throw ValidationException::withMessages($duplicateErrors);
		}
	}

	//card group
	public function rateCardGroup()
	{
		$shippingCompanies = ShippingCompany::query()->visibleInRateCards()->whereStatus(1)->get();
		$authUser = Auth::user();
		$assignedB2cRateCardId = $authUser->getAssignedRateCardIdByType('b2c');

		$rateCards = RateCard::where('rate_type', 'b2c')
			->orderBy('created_at', 'desc');

		if (!$authUser->isMasterAdmin() && !in_array($assignedB2cRateCardId, [0, '0', null, 'all'], true)) {
			$rateCards->where('id', $assignedB2cRateCardId);
		}

		$rateCards = $rateCards->get();
		$weights = ShippingWeight::select('weight')
			->distinct()
			->pluck('weight')
			->toArray();

		return view('shipping.rate-card.group', compact('rateCards', 'shippingCompanies', 'weights'));
	}

	public function priceList(Request $request)
	{
		$user = Auth::user();
		$type = $request->input('type', 'b2c');
		$assignedRateCardId = $user->getAssignedRateCardIdByType($type);
		$requestedCompanyId = (int) $request->input('shipping_company_id', 0);

		if ($user->isAdminLevel()) {
			$shippingCompanies = ShippingCompany::query()
				->visibleInRateCards()
				->where('status', 1)
				->get();
			if ($request->boolean('embed') && $requestedCompanyId > 0) {
				$shippingCompanies = $shippingCompanies->where('id', $requestedCompanyId)->values();
			}
			$currentCompanyId = $shippingCompanies->contains('id', $requestedCompanyId)
				? $requestedCompanyId
				: ($shippingCompanies->first()->id ?? null);

			$rateCards = $this->getRateCardsForCompany($currentCompanyId, $type);
			$requestedRateCardId = (int) $request->input('rate_card_id', 0);
			if ($type === 'b2b') {
				$rateCards = $this->ensureRequestedB2bRateCardInCollection($rateCards, $requestedRateCardId);
			}
			$currentRateCardId = $rateCards->contains('id', $requestedRateCardId)
				? $requestedRateCardId
				: $rateCards->first()?->id;
		} else {
			if ($assignedRateCardId != 0 && strtolower((string) $assignedRateCardId) != 'all') {
				$rateCards = RateCard::where('id', $assignedRateCardId)
					->where('rate_type', $type)
					->get();
			} else {
				$rateCards = collect();
			}

			$currentRateCardId = $rateCards->first()?->id;
		}

		if (!$user->isAdminLevel() && !$this->rateCardHasData($currentRateCardId, $type)) {
			$fallbackRateCard = $this->getFallbackRateCardForType($type);
			if ($fallbackRateCard) {
				$rateCards = collect([$fallbackRateCard]);
				$currentRateCardId = $fallbackRateCard->id;
			}
		}

		$rateCard = $currentRateCardId ? $rateCards->firstWhere('id', $currentRateCardId) : null;
		if ($user->isAdminLevel()) {
			$currentCompanyId = $shippingCompanies->contains('id', $requestedCompanyId)
				? $requestedCompanyId
				: ($shippingCompanies->first()->id ?? null);
		} else {
			$availableCompanyIds = $this->getRateCardCompanyIds($rateCard?->id, $type);
			$shippingCompanies = ShippingCompany::query()
				->visibleInRateCards()
				->where('status', 1)
				->when(!empty($availableCompanyIds), function ($query) use ($availableCompanyIds) {
					$query->whereIn('id', $availableCompanyIds);
				})
				->get();
			$currentCompanyId = $shippingCompanies->contains('id', $requestedCompanyId)
				? $requestedCompanyId
				: ($shippingCompanies->first()->id ?? null);
		}
		$weights = collect();
		if ($type === 'b2c') {
			$weights = ShippingWeight::query()
				->where('shipping_company_id', $currentCompanyId)
				->where('status', 1)
				->select('weight')
				->distinct()
				->orderBy('weight', 'asc')
				->pluck('weight');

			if ($weights->isEmpty()) {
				$weights = $this->getRateCardWeights($rateCard?->id, $currentCompanyId);
			}
		} else {
			$weights = ShippingWeight::where('status', 1)
				->select('weight')
				->distinct()
				->orderBy('weight', 'asc')
				->pluck('weight');
		}
		$requestedWeight = $request->input('weight');
		$currentWeight = $weights->contains($requestedWeight) ? $requestedWeight : $weights->first();
		$overheadData = $this->getOverheadForCompany($currentCompanyId);
		$b2cRows = collect();
		$b2cZones = collect();
		$b2cMatrixRows = collect();
		if ($type === 'b2c' && $rateCard && $currentCompanyId) {
			$b2cZones = ZoneSetting::query()
				->where('shipping_company_id', $currentCompanyId)
				->where('rate_type', 'b2c')
				->orderBy('sort_order', 'asc')
				->get(['id', 'heading', 'slug']);

			$existingB2cRows = RateCardDetail::query()
				->where('rate_card_id', $rateCard->id)
				->where('shipping_company_id', $currentCompanyId)
				->whereIn('zone_setting_id', $b2cZones->pluck('id')->all())
				->where('status', 1)
				->get();

			$existingByWeightZone = $existingB2cRows->keyBy(function ($detail) {
				return (string) $detail->weight . ':' . (int) $detail->zone_setting_id;
			});

			$b2cMatrixRows = $weights->map(function ($weight) use ($b2cZones, $existingByWeightZone) {
				$zones = $b2cZones->map(function ($zone) use ($weight, $existingByWeightZone) {
					$key = (string) $weight . ':' . (int) $zone->id;
					$detail = $existingByWeightZone->get($key);
					return (object) [
						'zone_setting_id' => (int) $zone->id,
						'rate' => $detail->rate ?? 0,
						'extra_rate' => $detail->extra_rate ?? 0,
					];
				});

				$firstZoneDetail = $b2cZones->isNotEmpty()
					? $existingByWeightZone->get((string) $weight . ':' . (int) $b2cZones->first()->id)
					: null;

				return (object) [
					'weight' => $weight,
					'cod_charges' => $firstZoneDetail->cod_charges ?? 0,
					'cod_percentage' => $firstZoneDetail->cod_percentage ?? 0,
					'zones' => $zones,
				];
			});

			$currentCompanyName = optional($shippingCompanies->firstWhere('id', $currentCompanyId))->name ?? 'N/A';
			$b2cRows = $b2cZones->map(function ($zone) use ($existingB2cRows, $currentWeight, $currentCompanyName) {
				$detail = $existingB2cRows->first(function ($row) use ($zone, $currentWeight) {
					return (int) $row->zone_setting_id === (int) $zone->id && (string) $row->weight === (string) $currentWeight;
				});
				return (object) [
					'id' => $detail->id ?? null,
					'zone_setting_id' => (int) $zone->id,
					'heading' => $zone->heading,
					'weight' => $detail->weight ?? $currentWeight,
					'shipping_company_name' => $currentCompanyName,
					'rate' => $detail->rate ?? 0,
					'extra_rate' => $detail->extra_rate ?? 0,
					'cod_charges' => $detail->cod_charges ?? 0,
					'cod_percentage' => $detail->cod_percentage ?? 0,
				];
			});
		}

        // Fetch B2B zones for matrix
        $b2bZones = ZoneSetting::where('shipping_company_id', $currentCompanyId)
            ->where('rate_type', 'b2b')
            ->orderBy('sort_order', 'asc')
            ->get();

        // B2B matrix data - commented out as B2B is disabled
        /*
        $matrixData = collect();
        if ($rateCard) {
            $matrixData = RateCardMatrix::where('rate_card_id', $rateCard->id)
                ->where('shipping_company_id', $currentCompanyId)
                ->get()
                ->groupBy('from_zone_id');
        }
        */
        $matrixData = collect();

        $otherChargeMasters = OtherChargeMaster::where('is_active', 1)
            ->where('shipping_company_id', $currentCompanyId)
            ->orderBy('name')
            ->get();

		return view('shipping.rate-card.price-list', compact('rateCards', 'shippingCompanies', 'currentRateCardId', 'currentCompanyId', 'rateCard', 'user', 'b2bZones', 'matrixData', 'overheadData', 'weights', 'currentWeight', 'otherChargeMasters', 'b2cRows', 'b2cZones', 'b2cMatrixRows'));
	}

	public function priceListAjax(Request $request)
	{
		$user = Auth::user();
		$currentCompanyId = (int) $request->input('shipping_company_id');
		$type = $request->input('type', 'b2c');
		$assignedRateCardId = $user->getAssignedRateCardIdByType($type);

		if ($user->isAdminLevel()) {
			$shippingCompanies = ShippingCompany::query()
				->visibleInRateCards()
				->where('status', 1)
				->get();
			if (!$shippingCompanies->contains('id', $currentCompanyId)) {
				$currentCompanyId = $shippingCompanies->first()->id ?? null;
			}

			$rateCards = $this->getRateCardsForCompany($currentCompanyId, $type);
			$currentRateCardId = (int) $request->input('rate_card_id');
			if ($type === 'b2b') {
				$rateCards = $this->ensureRequestedB2bRateCardInCollection($rateCards, $currentRateCardId);
			}
			if (!$rateCards->contains('id', $currentRateCardId)) {
				$currentRateCardId = $rateCards->first()?->id;
			}
			$rateCard = $currentRateCardId ? $rateCards->firstWhere('id', $currentRateCardId) : null;
		} else {
			$currentRateCardId = ($assignedRateCardId != 0 && strtolower((string) $assignedRateCardId) !== 'all')
				? (int) $assignedRateCardId
				: 0;
			$rateCard = $currentRateCardId
				? RateCard::where('id', $currentRateCardId)->where('rate_type', $type)->first()
				: null;
		}

		if (!$user->isAdminLevel() && !$this->rateCardHasData($currentRateCardId, $type)) {
			$rateCard = $this->getFallbackRateCardForType($type);
			$currentRateCardId = $rateCard?->id ?? 0;
		}

		if (!$user->isAdminLevel()) {
			$availableCompanyIds = $this->getRateCardCompanyIds($rateCard?->id, $type);
			$shippingCompanies = ShippingCompany::query()
				->visibleInRateCards()
				->where('status', 1)
				->when(!empty($availableCompanyIds), function ($query) use ($availableCompanyIds) {
					$query->whereIn('id', $availableCompanyIds);
				})
				->get();
			if (!$shippingCompanies->contains('id', $currentCompanyId)) {
				$currentCompanyId = $shippingCompanies->first()->id ?? null;
			}
		}

		$overheadData = $this->getOverheadForCompany($currentCompanyId);

        $b2bZones = ZoneSetting::where('shipping_company_id', $currentCompanyId)
            ->where('rate_type', 'b2b')
            ->orderBy('sort_order', 'asc')
            ->get();

        $matrixData = collect();
        if ($rateCard) {
            $matrixData = RateCardMatrix::where('rate_card_id', $rateCard->id)
                ->where('shipping_company_id', $currentCompanyId)
                ->get()
                ->groupBy('from_zone_id');
        }

		return response()->json([
            'status' => 'success',
            'view' => view('shipping.rate-card.price-list-partial', compact('rateCard', 'b2bZones', 'matrixData', 'currentCompanyId', 'type', 'shippingCompanies', 'overheadData'))->render()
        ]);
	}

	public function priceListMatrixSave(Request $request)
	{
		$user = Auth::user();
		if (!$user || !$user->isAdminLevel()) {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'rate_card_id' => 'required|integer|exists:rate_cards,id',
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'matrix' => 'required|array',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$rateCardId = (int) $request->input('rate_card_id');
		$shippingCompanyId = (int) $request->input('shipping_company_id');
		$matrix = $request->input('matrix', []);

		$rateCard = RateCard::where('id', $rateCardId)
			->where('rate_type', 'b2b')
			->first();
		if (!$rateCard) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Selected B2B rate card was not found.',
			], 422);
		}

		$zoneIds = ZoneSetting::where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', 'b2b')
			->pluck('id')
			->map(function ($id) {
				return (string) $id;
			})
			->values()
			->all();

		if (empty($zoneIds)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No B2B zones are available for this shipping company.',
			], 422);
		}

		$allowedZoneMap = array_flip($zoneIds);
		$now = now();
		$insertData = [];

		foreach ($matrix as $fromZoneId => $toZoneRates) {
			$fromZoneId = (string) $fromZoneId;
			if (!isset($allowedZoneMap[$fromZoneId]) || !is_array($toZoneRates)) {
				continue;
			}

			foreach ($toZoneRates as $toZoneId => $rateValue) {
				$toZoneId = (string) $toZoneId;
				if (!isset($allowedZoneMap[$toZoneId])) {
					continue;
				}

				if ($rateValue === null || $rateValue === '') {
					continue;
				}

				$rate = (float) $rateValue;
				if ($rate <= 0) {
					continue;
				}

				$insertData[] = [
					'rate_card_id' => $rateCardId,
					'shipping_company_id' => $shippingCompanyId,
					'from_zone_id' => (int) $fromZoneId,
					'to_zone_id' => (int) $toZoneId,
					'rate_per_kg' => $rate,
					'status' => 1,
					'created_at' => $now,
					'updated_at' => $now,
				];
			}
		}

		DB::transaction(function () use ($rateCardId, $shippingCompanyId, $insertData) {
			RateCardMatrix::where('rate_card_id', $rateCardId)
				->where('shipping_company_id', $shippingCompanyId)
				->delete();

			if (!empty($insertData)) {
				DB::table('rate_card_matrices')->insert($insertData);
			}
		});

		return response()->json([
			'status' => 'success',
			'msg' => 'Admin rate matrix saved successfully.',
			'saved_rows' => count($insertData),
		]);
	}

	public function priceListB2cSave(Request $request)
	{
		$user = Auth::user();
		if (!$user || !$user->isAdminLevel()) {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'rate_card_id' => 'required|integer|exists:rate_cards,id',
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'rows' => 'required|array',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first(),
			], 422);
		}

		$rateCardId = (int) $request->input('rate_card_id');
		$shippingCompanyId = (int) $request->input('shipping_company_id');
		$rows = $request->input('rows', []);

		$rateCard = RateCard::where('id', $rateCardId)
			->where('rate_type', 'b2c')
			->first();
		if (!$rateCard) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Selected B2C rate card was not found.',
			], 422);
		}

		$rowsCollection = collect($rows);
		if ($rowsCollection->isEmpty()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No rows were provided to save.',
			], 422);
		}

		$zones = ZoneSetting::query()
			->where('shipping_company_id', $shippingCompanyId)
			->where('rate_type', 'b2c')
			->get(['id', 'slug'])
			->keyBy('id');

		if ($zones->isEmpty()) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No B2C zones are available for this shipping company.',
			], 422);
		}

		$toNumber = function ($value) {
			if ($value === null || $value === '') {
				return 0;
			}
			return max(0, (float) $value);
		};

		$isMatrixPayload = $rowsCollection->contains(function ($row) {
			return is_array($row) && array_key_exists('zones', $row);
		});

		$updates = [];
		if ($isMatrixPayload) {
			foreach ($rowsCollection as $weightValue => $rowData) {
				$weightValue = (string) $weightValue;
				if ($weightValue === '' || !is_array($rowData)) {
					continue;
				}

				$zonesData = $rowData['zones'] ?? [];
				if (!is_array($zonesData)) {
					continue;
				}

				$codCharges = $toNumber($rowData['cod_charges'] ?? 0);
				$codPercentage = $toNumber($rowData['cod_percentage'] ?? 0);

				foreach ($zonesData as $zoneSettingId => $fields) {
					$zoneSettingId = (int) $zoneSettingId;
					if ($zoneSettingId <= 0 || !is_array($fields)) {
						continue;
					}

					$zone = $zones->get($zoneSettingId);
					if (!$zone) {
						continue;
					}

					$updates[] = [
						'weight' => $weightValue,
						'zone_setting_id' => $zoneSettingId,
						'zone_slug' => (string) ($zone->slug ?? ''),
						'rate' => $toNumber($fields['rate'] ?? 0),
						'extra_rate' => $toNumber($fields['extra_rate'] ?? 0),
						'cod_charges' => $codCharges,
						'cod_percentage' => $codPercentage,
					];
				}
			}
		} else {
			$weightValue = (string) $request->input('weight', '');
			if ($weightValue === '') {
				return response()->json([
					'status' => 'error',
					'msg' => 'Weight is required for this save format.',
				], 422);
			}

			foreach ($rowsCollection as $zoneSettingId => $fields) {
				$zoneSettingId = (int) $zoneSettingId;
				if ($zoneSettingId <= 0 || !is_array($fields)) {
					continue;
				}

				$zone = $zones->get($zoneSettingId);
				if (!$zone) {
					continue;
				}

				$updates[] = [
					'weight' => $weightValue,
					'zone_setting_id' => $zoneSettingId,
					'zone_slug' => (string) ($zone->slug ?? ''),
					'rate' => $toNumber($fields['rate'] ?? 0),
					'extra_rate' => $toNumber($fields['extra_rate'] ?? 0),
					'cod_charges' => $toNumber($fields['cod_charges'] ?? 0),
					'cod_percentage' => $toNumber($fields['cod_percentage'] ?? 0),
				];
			}
		}

		if (empty($updates)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'No valid B2C rows found to save.',
			], 422);
		}

		$updatedRows = 0;
		DB::transaction(function () use ($updates, $rateCardId, $shippingCompanyId, &$updatedRows) {
			foreach ($updates as $item) {
				RateCardDetail::query()->updateOrCreate(
					[
						'rate_card_id' => $rateCardId,
						'shipping_company_id' => $shippingCompanyId,
						'weight' => $item['weight'],
						'zone_setting_id' => $item['zone_setting_id'],
					],
					[
						'zone_slug' => $item['zone_slug'],
						'rate' => $item['rate'],
						'extra_rate' => $item['extra_rate'],
						'cod_charges' => $item['cod_charges'],
						'cod_percentage' => $item['cod_percentage'],
						'status' => 1,
					]
				);
				$updatedRows++;
			}
		});

		return response()->json([
			'status' => 'success',
			'msg' => 'B2C rates saved successfully.',
			'saved_rows' => $updatedRows,
		]);
	}

	public function updateOverheadCharges(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'flat_mile_per_kg' => 'nullable|numeric|min:0',
			'flat_mile_min' => 'nullable|numeric|min:0',
			'rov_owner_percent' => 'nullable|numeric|min:0',
			'rov_owner_min' => 'nullable|numeric|min:0',
			'rov_carrier_percent' => 'nullable|numeric|min:0',
			'rov_carrier_min' => 'nullable|numeric|min:0',
			'green_per_kg' => 'nullable|numeric|min:0',
			'green_min' => 'nullable|numeric|min:0',
			'oda_per_kg' => 'nullable|numeric|min:0',
			'oda_min' => 'nullable|numeric|min:0',
			'cod_percent' => 'nullable|numeric|min:0',
			'cod_min' => 'nullable|numeric|min:0',
			'divisor' => 'nullable|integer|min:1',
			'minimum_charge' => 'nullable|numeric|min:0',
			'gst_percent' => 'nullable|numeric|min:0',
		]);

		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}

		$defaults = $this->getOverheadDefaults();
		$payload = [];
		foreach ($defaults as $key => $val) {
			$payload[$key] = $request->input($key, $val);
		}

		OverheadCharge::updateOrCreate(
			['shipping_company_id' => $request->input('shipping_company_id')],
			$payload
		);

		return redirect()->back()->with('success', 'Overhead charges updated successfully.');
	}

	public function overheadChargesShow(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first()
			], 422);
		}

		$defaults = $this->getOverheadDefaults();
		$row = OverheadCharge::where('shipping_company_id', $request->input('shipping_company_id'))->first();
		$data = $row ? array_merge($defaults, $row->only(array_keys($defaults))) : $defaults;

		return response()->json([
			'status' => 'success',
			'has_row' => (bool) $row,
			'data' => $data,
		]);
	}

	public function overheadChargesSave(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'flat_mile_per_kg' => 'nullable|numeric|min:0',
			'flat_mile_min' => 'nullable|numeric|min:0',
			'rov_owner_percent' => 'nullable|numeric|min:0',
			'rov_owner_min' => 'nullable|numeric|min:0',
			'rov_carrier_percent' => 'nullable|numeric|min:0',
			'rov_carrier_min' => 'nullable|numeric|min:0',
			'green_per_kg' => 'nullable|numeric|min:0',
			'green_min' => 'nullable|numeric|min:0',
			'oda_per_kg' => 'nullable|numeric|min:0',
			'oda_min' => 'nullable|numeric|min:0',
			'cod_percent' => 'nullable|numeric|min:0',
			'cod_min' => 'nullable|numeric|min:0',
			'divisor' => 'nullable|integer|min:1',
			'minimum_charge' => 'nullable|numeric|min:0',
			'gst_percent' => 'nullable|numeric|min:0',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first()
			], 422);
		}

		$defaults = $this->getOverheadDefaults();
		$payload = [];
		foreach ($defaults as $key => $val) {
			$payload[$key] = $request->input($key, $val);
		}

		OverheadCharge::updateOrCreate(
			['shipping_company_id' => $request->input('shipping_company_id')],
			$payload
		);

		return response()->json([
			'status' => 'success',
			'msg' => 'Overhead charges saved successfully.',
			'has_row' => true,
			'data' => $payload,
		]);
	}

	// Other Charges Master (Admin)
	public function otherChargesMasterIndex(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$shippingCompanyId = (int) $request->input('shipping_company_id', 0);
		if ($shippingCompanyId <= 0) {
			$shippingCompanyId = (int) ShippingCompany::query()
				->visibleInRateCards()
				->whereStatus(1)
				->orderBy('id')
				->value('id');
		}

		$shippingCompany = $shippingCompanyId > 0 ? ShippingCompany::find($shippingCompanyId) : null;
		$charges = OtherChargeMaster::where('shipping_company_id', $shippingCompanyId)
			->orderBy('name')
			->get();
		$calcTypes = $this->getOtherChargeCalcTypes();

		return view('shipping.rate-card.other-charges-master-modal', compact('charges', 'calcTypes', 'shippingCompany', 'shippingCompanyId'));
	}

	public function otherChargesMasterStore(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}
		$shippingCompanyId = (int) $request->input('shipping_company_id', 0);

		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'name' => 'required|string|max:255',
			'code' => [
				'required',
				'string',
				'max:100',
				Rule::unique('other_charge_masters', 'code')->where(function ($query) use ($shippingCompanyId) {
					$query->where('shipping_company_id', $shippingCompanyId);
				}),
			],
			'calc_type' => ['required', 'string', Rule::in(array_keys($this->getOtherChargeCalcTypes()))],
			'zip_codes' => 'nullable|array',
			'is_active' => 'nullable|boolean',
		]);

		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}

		$defaultConfig = $this->extractOtherChargeConfig($request);
		OtherChargeMaster::create([
			'shipping_company_id' => $shippingCompanyId,
			'name' => $request->input('name'),
			'code' => strtoupper(trim((string) $request->input('code'))),
			'calc_type' => $request->input('calc_type'),
			'default_config' => $defaultConfig,
			'zip_codes' => $request->input('zip_codes', []),
			'is_active' => $request->boolean('is_active', true),
		]);

		return redirect()->back()->with('success', 'Other charge master saved successfully.');
	}

	public function otherChargesMasterUpdate(Request $request, $id)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$shippingCompanyId = (int) $request->input('shipping_company_id', 0);
		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'name' => 'required|string|max:255',
			'code' => [
				'required',
				'string',
				'max:100',
				Rule::unique('other_charge_masters', 'code')
					->where(function ($query) use ($shippingCompanyId) {
						$query->where('shipping_company_id', $shippingCompanyId);
					})
					->ignore((int) $id),
			],
			'calc_type' => ['required', 'string', Rule::in(array_keys($this->getOtherChargeCalcTypes()))],
			'zip_codes' => 'nullable|array',
			'is_active' => 'nullable|boolean',
		]);

		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}

		$charge = OtherChargeMaster::where('id', $id)
			->where('shipping_company_id', $shippingCompanyId)
			->firstOrFail();

		$charge->update([
			'shipping_company_id' => $shippingCompanyId,
			'name' => $request->input('name'),
			'code' => strtoupper(trim((string) $request->input('code'))),
			'calc_type' => $request->input('calc_type'),
			'default_config' => $this->extractOtherChargeConfig($request),
			'zip_codes' => $request->input('zip_codes', []),
			'is_active' => $request->boolean('is_active', true),
		]);

		return redirect()->back()->with('success', 'Other charge master updated successfully.');
	}

	public function otherChargesMasterDelete(Request $request, $id)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
		])->validate();

		OtherChargeMaster::where('id', $id)
			->where('shipping_company_id', (int) $request->input('shipping_company_id'))
			->delete();
		return redirect()->back()->with('success', 'Other charge master deleted successfully.');
	}

	// Rate Card -> Other Charges assignments (B2B)
	public function rateCardOtherChargesShow(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'rate_card_id' => 'required|integer|exists:rate_cards,id',
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()], 422);
		}

		$rows = RateCardOtherCharge::with('master')
			->where('rate_card_id', $request->input('rate_card_id'))
			->where('shipping_company_id', $request->input('shipping_company_id'))
			->whereHas('master', function ($query) use ($request) {
				$query->where('shipping_company_id', (int) $request->input('shipping_company_id'));
			})
			->orderBy('sort_order')
			->get();

		return response()->json([
			'status' => 'success',
			'data' => $rows->map(function ($row) {
				return [
					'id' => $row->id,
					'other_charge_master_id' => $row->other_charge_master_id,
					'name' => $row->master->name ?? '',
					'code' => $row->master->code ?? '',
					'calc_type' => $row->master->calc_type ?? '',
					'config' => $row->config ?? [],
					'default_config' => $row->master->default_config ?? [],
					'sort_order' => $row->sort_order ?? 0,
				];
			}),
		]);
	}

	public function rateCardOtherChargesSave(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'rate_card_id' => 'required|integer|exists:rate_cards,id',
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
			'rows' => 'required|array',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()], 422);
		}

		$rateCardId = (int) $request->input('rate_card_id');
		$companyId = (int) $request->input('shipping_company_id');
		$rows = $request->input('rows', []);
		$validChargeIds = OtherChargeMaster::where('shipping_company_id', $companyId)
			->where('is_active', 1)
			->pluck('id')
			->map(function ($id) {
				return (int) $id;
			})
			->all();

		DB::beginTransaction();
		try {
			RateCardOtherCharge::where('rate_card_id', $rateCardId)
				->where('shipping_company_id', $companyId)
				->delete();

			$insertData = [];
			$sort = 1;
			foreach ($rows as $row) {
				$chargeId = (int) ($row['other_charge_master_id'] ?? 0);
				if ($chargeId <= 0 || !in_array($chargeId, $validChargeIds, true)) {
					continue;
				}
				$config = is_array($row['config'] ?? null) ? $row['config'] : [];
				$insertData[] = [
					'rate_card_id' => $rateCardId,
					'shipping_company_id' => $companyId,
					'other_charge_master_id' => $chargeId,
					'sort_order' => $sort,
					'config' => json_encode($config),
					'created_at' => now(),
					'updated_at' => now(),
				];
				$sort++;
			}

			if (!empty($insertData)) {
				DB::table('rate_card_other_charges')->insert($insertData);
			}

			DB::commit();
			return response()->json(['status' => 'success', 'msg' => 'Overhead charges saved successfully.']);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
		}
	}

	private function extractOtherChargeConfig(Request $request)
	{
		$fields = [
			'flat', 'per_kg', 'percent', 'min', 'max',
			'divisor', 'threshold', 'rate_upto', 'rate_above',
			'max_single_box_weight', 'base',
		];
		$config = [];
		foreach ($fields as $field) {
			if ($request->has($field)) {
				$value = $request->input($field);
				if ($value === '' || $value === null) {
					continue;
				}
				$config[$field] = is_numeric($value) ? (float) $value : $value;
			}
		}
		return $config;
	}

	private function getOtherChargeCalcTypes()
	{
		return [
			'chargeable_weight' => 'Chargeable Weight (Info)',
			'min_weight' => 'Minimum Weight (Info)',
			'freight' => 'Shipping Charges (Calc)',
			'per_kg_or_min' => 'Per Kg OR Minimum',
			'percent_or_min' => 'Percent OR Minimum',
			'per_kg_or_flat' => 'Per Kg OR Flat',
			'flat' => 'Flat',
			'divisor' => 'Volumetric Divisor',
			'min_charge' => 'Minimum Freight',
			'gst_percent' => 'GST Percent',
			'handling_tier' => 'Handling Tier (Per Kg)',
			'max_single_box_weight' => 'Max Single Box Weight (Info)',
		];
	}

	public function overheadChargesDelete(Request $request)
	{
		$user = Auth::user();
		if ($user->role === 'user') {
			abort(403, 'Permission denied');
		}

		$validator = Validator::make($request->all(), [
			'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'error',
				'msg' => $validator->errors()->first()
			], 422);
		}

		OverheadCharge::where('shipping_company_id', $request->input('shipping_company_id'))->delete();

		return response()->json([
			'status' => 'success',
			'msg' => 'Overhead charges deleted successfully.',
			'has_row' => false,
			'data' => $this->getOverheadDefaults(),
		]);
	}

	private function getOverheadDefaults()
	{
		return [
			'flat_mile_per_kg' => 1,
			'flat_mile_min' => 100,
			'rov_owner_percent' => 0,
			'rov_owner_min' => 50,
			'rov_carrier_percent' => 0.3,
			'rov_carrier_min' => 100,
			'green_per_kg' => 0,
			'green_min' => 50,
			'oda_per_kg' => 4,
			'oda_min' => 500,
			'cod_percent' => 0.5,
			'cod_min' => 150,
			'divisor' => 5000,
			'minimum_charge' => 220,
			'gst_percent' => 18,
		];
	}

	private function getOverheadForCompany($companyId)
	{
		$defaults = $this->getOverheadDefaults();
		$row = OverheadCharge::where('shipping_company_id', $companyId)->first();
		if (!$row) {
			return $defaults;
		}
		$data = $row->only(array_keys($defaults));
		return array_merge($defaults, $data);
	}

	private function getRateCardCompanyIds(?int $rateCardId, string $type = 'b2c'): array
	{
		if (!$rateCardId) {
			return [];
		}

		if ($type === 'b2b') {
			return ShippingCompany::filterVisibleRateCardCompanyIds(
				RateCardMatrix::where('rate_card_id', $rateCardId)
				->distinct()
				->pluck('shipping_company_id')
				->filter()
				->map(fn ($id) => (int) $id)
				->values()
				->all()
			);
		}

		return ShippingCompany::filterVisibleRateCardCompanyIds(
			RateCardDetail::where('rate_card_id', $rateCardId)
			->where('status', 1)
			->distinct()
			->pluck('shipping_company_id')
			->filter()
			->map(fn ($id) => (int) $id)
			->values()
			->all()
		);
	}

	private function getRateCardsForCompany(?int $shippingCompanyId, string $type = 'b2c'): \Illuminate\Support\Collection
	{
		if (!$shippingCompanyId) {
			return collect();
		}

		$rateCardIds = $type === 'b2b'
			? RateCardMatrix::where('shipping_company_id', $shippingCompanyId)
				->distinct()
				->pluck('rate_card_id')
			: RateCardDetail::where('shipping_company_id', $shippingCompanyId)
				->where('status', 1)
				->distinct()
				->pluck('rate_card_id');

		if ($rateCardIds->isEmpty()) {
			return collect();
		}

		return RateCard::where('rate_type', $type)
			->whereIn('id', $rateCardIds)
			->orderBy('created_at', 'desc')
			->get();
	}

	private function ensureRequestedB2bRateCardInCollection(\Illuminate\Support\Collection $rateCards, int $requestedRateCardId): \Illuminate\Support\Collection
	{
		if ($requestedRateCardId <= 0 || $rateCards->contains('id', $requestedRateCardId)) {
			return $rateCards;
		}

		$requestedRateCard = RateCard::query()
			->where('id', $requestedRateCardId)
			->where('rate_type', 'b2b')
			->first();

		if (!$requestedRateCard) {
			return $rateCards;
		}

		return $rateCards->prepend($requestedRateCard)->unique('id')->values();
	}

	private function getRateCardWeights(?int $rateCardId, ?int $shippingCompanyId): \Illuminate\Support\Collection
	{
		if (!$rateCardId || !$shippingCompanyId) {
			return collect();
		}

		return RateCardDetail::where('rate_card_id', $rateCardId)
			->where('shipping_company_id', $shippingCompanyId)
			->where('status', 1)
			->select('weight')
			->distinct()
			->orderBy('weight', 'asc')
			->pluck('weight');
	}

	private function rateCardHasData(?int $rateCardId, string $type = 'b2c'): bool
	{
		if (!$rateCardId) {
			return false;
		}

		if ($type === 'b2b') {
			return RateCardMatrix::where('rate_card_id', $rateCardId)->exists();
		}

		return RateCardDetail::where('rate_card_id', $rateCardId)->where('status', 1)->exists();
	}

	private function getFallbackRateCardForType(string $type = 'b2c'): ?RateCard
	{
		$query = RateCard::query()->where('rate_type', $type);

		if ($type === 'b2b') {
			$query->whereExists(function ($subQuery) {
				$subQuery->select(DB::raw(1))
					->from('rate_card_matrices')
					->whereColumn('rate_card_matrices.rate_card_id', 'rate_cards.id');
			});
		} else {
			$query->whereExists(function ($subQuery) {
				$subQuery->select(DB::raw(1))
					->from('rate_card_details')
					->whereColumn('rate_card_details.rate_card_id', 'rate_cards.id')
					->where('rate_card_details.status', 1);
			});
		}

		return $query->orderBy('id')->first();
	}

	public function rateCardGroupAjax(Request $request)
	{
		$user = Auth::user();
		$requestedRateCardId = (int) $request->input('rate_card_id');
		$effectiveRateCardId = $requestedRateCardId;

		if (!$user->isAdminLevel()) {
			$assignedRateCardId = $user->getAssignedRateCardIdByType('b2c');
			if ($assignedRateCardId == 0 || strtolower((string) $assignedRateCardId) === 'all') {
				$effectiveRateCardId = (int) optional($this->getFallbackRateCardForType('b2c'))->id;
			} else {
				$effectiveRateCardId = (int) $assignedRateCardId;
			}
		}

		if (!$this->rateCardHasData($effectiveRateCardId, 'b2c')) {
			$effectiveRateCardId = (int) optional($this->getFallbackRateCardForType('b2c'))->id;
		}

		if (!$effectiveRateCardId) {
			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => 0,
				'recordsFiltered' => 0,
				'data' => [],
			]);
		}

		$availableCompanyIds = $this->getRateCardCompanyIds($effectiveRateCardId, 'b2c');
		$currentCompanyId = (int) $request->input('shipping_company_id');
		if (!in_array($currentCompanyId, $availableCompanyIds, true)) {
			$currentCompanyId = $availableCompanyIds[0] ?? 0;
		}

		$currentWeight = $request->input('weight');
		$availableWeights = $this->getRateCardWeights($effectiveRateCardId, $currentCompanyId);
		if (!$availableWeights->contains($currentWeight)) {
			$currentWeight = $availableWeights->first();
		}

		$query = RateCardDetail::with('rateCard', 'shippingCompany', 'zoneSetting')
			->where('rate_card_id', $effectiveRateCardId)
			->where('weight', $currentWeight)
			->where('status', 1);

		if ($currentCompanyId) {
			$query->where('shipping_company_id', $currentCompanyId);
		}
		// Server-side pagination & search (basic)
		if ($search = ($request->input('search.value') ?? $request->input('search'))) {
			$query->where(function ($q) use ($search) {
				$q->where('rate', 'like', "%{$search}%")
					->orwhere('zone_slug', 'like', "%{$search}%")
					->orWhereHas('rateCard', function ($q2) use ($search) {
						$q2->where('group_name', 'like', "%{$search}%");
					});
			});
		}


		$total = $query->count();

		// ordering & pagination
		$start = $request->input('start', 0);
		$length = $request->input('length', 25);

		$data = $query->orderBy('id', 'desc')->skip($start)->take($length)->get();

		$rows = $data->map(function ($row, $idx) {
			return [
				'id' => $idx + 1,
				'shipping_company_id' => $row->shippingCompany->name ?? 'N/A',
				'weight' => $row->weight,
				'heading' => $row->zoneSetting->heading ?? 'N/A',
				'rate' => $row->rate,
				'extra_rate' => $row->extra_rate,
				'cod_charges' => $row->cod_charges,
				'percentage' => $row->cod_percentage
			];
		});

		return response()->json([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $total,
			'recordsFiltered' => $total,
			'data' => $rows,
		]);
	}

	// Shipping Weight Management
	public function shippingWeight()
	{
		$user = Auth::user();

        Helper::userRestrict($user);
		$shippingCompanies = ShippingCompany::query()->visibleInRateCards()->whereStatus(1)->get();
		return view('shipping.shipping-weight.index', compact('shippingCompanies'));
	}

	public function shippingWeightAjax(Request $request)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$query = ShippingWeight::with(['shippingCompany:id,name'])
			->whereIn('shipping_company_id', $visibleCompanyIds);

		if ($request->filled('shipping_company_id')) {
			$query->where('shipping_company_id', $request->shipping_company_id);
		}

		// Server-side pagination & search
		if ($search = $request->input('search.value')) {
			$query->where(function ($q) use ($search) {
				$q->where('weight', 'like', "%{$search}%")
					->orWhereHas('shippingCompany', function ($query) use ($search) {
						$query->where('name', 'like', "%{$search}%");
					});
			});
		}

		$total = $query->count();

		// ordering & pagination
		$start = $request->input('start', 0);
		$length = $request->input('length', 25);
		$orderRules = $request->input('order', []);
		$columns = $request->input('columns', []);

		$needsJoin = false;
		foreach ($orderRules as $rule) {
			$colIdx = $rule['column'] ?? null;
			$colName = $columns[$colIdx]['data'] ?? null;
			if ($colName === 'shipping_company') {
				$needsJoin = true;
				break;
			}
		}

		if ($needsJoin) {
			$query->join('shipping_companies', 'shipping_weights.shipping_company_id', '=', 'shipping_companies.id')
				->select('shipping_weights.*');
		}

		if (!empty($orderRules)) {
			foreach ($orderRules as $rule) {
				$colIdx = $rule['column'] ?? null;
				$dir = $rule['dir'] ?? 'asc';
				$colName = $columns[$colIdx]['data'] ?? null;

				if ($colName === 'shipping_company') {
					$query->orderBy('shipping_companies.name', $dir);
				} elseif (!empty($colName)) {
					$query->orderBy($colName, $dir);
				}
			}
		} else {
			$query->orderBy('weight', 'asc');
		}

		$data = $query->skip($start)->take($length)->get();
		// <a href='javascript:void(0)' data-id='$row->id' class='btn btn-icon waves-effect waves-light action-icon mr-1 btn-edit-weight'>
		// 			<i class='mdi mdi-pencil'></i>
		// 		</a>
		$rows = $data->map(function ($row, $idx) use ($start) {
			return [
				'id' => $start + $idx + 1,
				'shipping_company' => $row->shippingCompany->name ?? 'N/A',
				'weight' => $row->weight . ' kg',
				'status' => $row->status == 1
					? '<span class="badge badge-success">Active</span>'
					: '<span class="badge badge-danger">Inactive</span>',
				'created_at' => $row->created_at->format('d M Y'),
				'action' => "
				<a href='javascript:void(0)' data-id='$row->id' class='btn btn-icon waves-effect waves-light action-icon btn-delete-weight'>
					<i class='mdi mdi-trash-can-outline'></i>
				</a>"
			];
		});

		return response()->json([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $total,
			'recordsFiltered' => $total,
			'data' => $rows,
		]);
	}

	public function storeShippingWeight(Request $request)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$validator = Validator::make($request->all(), [
			'shipping_company_id' => ['required', 'integer', Rule::in($visibleCompanyIds)],
			'weight' => 'required|numeric|min:0.01',
			'status' => 'required|in:0,1',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()], 422);
		}

		// Check for duplicate
		$exists = ShippingWeight::where('shipping_company_id', $request->shipping_company_id)
			->where('weight', $request->weight)
			->exists();

		if ($exists) {
			return response()->json([
				'status' => 'error',
				'msg' => 'This weight already exists for the selected shipping company.'
			], 422);
		}

		try {
			$shippingWeight = ShippingWeight::create([
				'shipping_company_id' => $request->shipping_company_id,
				'weight' => $request->weight,
				'status' => $request->status,
			]);

			return response()->json([
				'status' => 'success',
				'msg' => 'Shipping weight created successfully.',
				'object' => $shippingWeight
			]);
		} catch (\Exception $e) {
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
		}
	}

	public function getShippingWeight($id)
	{
		$shippingWeight = ShippingWeight::findOrFail($id);
		return response()->json(['status' => 'success', 'object' => $shippingWeight]);
	}

	public function updateShippingWeight(Request $request, $id)
	{
		$visibleCompanyIds = $this->getVisibleRateCardCompanyIds();
		$validator = Validator::make($request->all(), [
			'shipping_company_id' => ['required', 'integer', Rule::in($visibleCompanyIds)],
			'weight' => 'required|numeric|min:0.01',
			'status' => 'required|in:0,1',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()], 422);
		}

		// Check for duplicate (excluding current record)
		$exists = ShippingWeight::where('id', '!=', $id)
			->where('shipping_company_id', $request->shipping_company_id)
			->where('weight', $request->weight)
			->exists();

		if ($exists) {
			return response()->json([
				'status' => 'error',
				'msg' => 'This weight already exists for the selected shipping company.'
			], 422);
		}

		try {
			$shippingWeight = ShippingWeight::findOrFail($id);
			$shippingWeight->update([
				'shipping_company_id' => $request->shipping_company_id,
				'weight' => $request->weight,
				'status' => $request->status,
			]);

			return response()->json([
				'status' => 'success',
				'msg' => 'Shipping weight updated successfully.',
				'object' => $shippingWeight
			]);
		} catch (\Exception $e) {
			return response()->json(['status' => 'error', 'msg' => $e->getMessage()], 500);
		}
	}

	public function deleteShippingWeight($id)
	{
		try {
			\Log::info("Attempting to delete ShippingWeight with ID: " . $id);
			$shippingWeight = ShippingWeight::findOrFail($id);
			$shippingWeight->delete(); // Soft delete

			return response()->json([
				'status' => 'success',
				'msg' => 'Shipping weight deleted successfully.'
			]);
		} catch (\Exception $e) {
			\Log::error("Error deleting ShippingWeight ID $id: " . $e->getMessage());
			\Log::error($e->getTraceAsString());
			return response()->json(['status' => 'error', 'msg' => 'Something went wrong: ' . $e->getMessage()], 500);
		}
	}

	private function getVisibleRateCardCompanyIds(): array
	{
		return ShippingCompany::query()
			->visibleInRateCards()
			->where('status', 1)
			->pluck('id')
			->map(fn($id) => (int) $id)
			->values()
			->all();
	}
}
