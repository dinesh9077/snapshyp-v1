@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Other Charges Master')
@section('header_title', 'Other Charges Master')

@section('content')
    <style>
        :root {
            --new-color-prime: #3984FB;
        }

        .ocm-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
            padding: 18px 0 12px;
            border-bottom: 1px solid #e6edf7;
            flex-wrap: wrap;
        }

        .ocm-head h4 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            font-weight: 800;
            color: #163a7c;
        }

        .ocm-head p {
            margin: 6px 0 0;
            color: #5f7394;
            font-size: 14px;
            line-height: 1.45;
        }

        .ocm-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .ocm-card-head {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ocm-card-head h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .ocm-card-head span {
            font-size: 13px;
            color: #64748b;
        }

        .ocm-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .ocm-table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-top: 1px solid #edf2f7;
            font-size: 14px;
            color: #334155;
        }

        .ocm-name {
            font-weight: 700;
            color: #0f172a;
        }

        .ocm-hint {
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
        }

        .code-tag {
            display: inline-flex;
            padding: 3px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 12px;
        }

        .chip-active,
        .chip-inactive {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .chip-active {
            color: #2f855a;
            background: #f0fff4;
        }

        .chip-inactive {
            color: #c53030;
            background: #fff5f5;
        }

        .ocm-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .ocm-action-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .ocm-edit-btn {
            color: #FB5C08;
            background: rgba(251, 92, 8, .08);
        }

        .ocm-edit-btn:hover {
            color: #fff;
            background: #FB5C08;
        }

        .ocm-delete-btn {
            color: #e11d48;
            background: rgba(225, 29, 72, .08);
        }

        .ocm-delete-btn:hover {
            color: #fff;
            background: #e11d48;
        }

        .ocm-empty {
            text-align: center;
            padding: 48px 24px !important;
            color: #64748b;
        }

        #chargeMasterModal .modal-content {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.2);
        }

        #chargeMasterModal .modal-dialog {
            max-width: 920px;
        }

        #chargeMasterModal .modal-header {
            padding: 20px 22px 18px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            align-items: flex-start;
        }

        #chargeMasterModal .modal-body {
            padding: 22px;
            background: #ffffff;
        }

        #chargeMasterModal .modal-footer {
            padding: 16px 22px 22px;
            border-top: 1px solid #eef2f7;
            background: #fcfdff;
            gap: 10px;
        }

        #chargeMasterModal .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        #chargeMasterModal .modal-title {
            color: #133f91;
            font-size: 17px;
            font-weight: 800;
        }

        #chargeMasterModal .close {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: none;
            background: var(--new-color-prime);
            color: #fff;
            opacity: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        #chargeMasterModal .close:hover {
            background: #173b7a;
            transform: scale(1.03);
        }

        #chargeMasterModal .form-control,
        #chargeMasterModal .form-select,
        #chargeMasterModal .select2-container--default .select2-selection--multiple {
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            min-height: 42px;
            font-size: 14px;
            background: #fff !important;
        }

        #chargeMasterModal .form-control:focus,
        #chargeMasterModal .form-select:focus {
            border-color: #FB5C08 !important;
            box-shadow: 0 0 0 3px rgba(251, 92, 8, .12) !important;
        }

        #chargeMasterModal .select2-container--default .select2-selection--multiple {
            padding: 6px 8px !important;
            min-height: 48px !important;
            display: flex !important;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 6px;
        }

        #chargeMasterModal .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #FB5C08 !important;
            box-shadow: 0 0 0 3px rgba(251, 92, 8, .12) !important;
        }

        #chargeMasterModal .select2-container {
            width: 100% !important;
        }

        #chargeMasterModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #eef4ff !important;
            border: 1px solid #cfe0ff !important;
            color: #1e3a8a !important;
            border-radius: 999px !important;
            padding: 5px 10px !important;
            margin: 3px 6px 3px 0 !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
        }

        #chargeMasterModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #1e3a8a !important;
            margin-right: 6px !important;
            font-weight: 700 !important;
            border-right: none !important;
        }

        #chargeMasterModal .select2-search--inline {
            display: flex !important;
            align-items: center;
            min-height: 30px;
        }

        #chargeMasterModal .select2-search__field {
            margin-top: 0 !important;
            height: 28px !important;
            font-size: 13px !important;
        }

        #chargeMasterModal .text-muted {
            color: #94a3b8 !important;
        }

        #chargeMasterModal .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        #chargeMasterModal .row>[class*="col-"] {
            padding-left: 10px;
            padding-right: 10px;
        }

        #chargeMasterModal .btn-light {
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            color: #334155;
        }

        #chargeMasterModal .btn-light:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        #chargeMasterModal .new-submit-btn {
            min-width: 150px;
        }

        .ocm-field-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .ocm-field-head .form-label {
            margin-bottom: 0 !important;
        }

        .ocm-import-btn {
            border: 1px solid #cfe0ff;
            background: #eef4ff;
            color: var(--new-color-prime);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ocm-import-btn:hover {
            background: #dce8ff;
            color: #173b7a;
        }

        .ocm-import-panel {
            display: none;
            margin-top: 12px;
            border: 1px solid #dbe7f8;
            border-radius: 14px;
            background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
            padding: 14px;
        }

        .ocm-import-panel p {
            margin-bottom: 10px;
            color: #64748b;
            font-size: 13px;
        }

        .ocm-import-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .config-grid .config-col {
            display: none;
        }

        .config-grid .config-col.show {
            display: block;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="ocm-head">
                            <div>
                                <h4>Other Charges Master</h4>
                                <p>Create and manage charge rules used in shipping setup. Add opens the form and edit opens
                                    the same form.</p>
                            </div>
                            <button type="button" class="btn btn-main-1" id="btnAddCharge">
                                <i class="mdi mdi-plus"></i> Add Other Charge
                            </button>
                        </div>

                        <div class="ocm-card">
                            <div class="table-responsive ocm-table">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Calc Type</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end" style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($charges as $charge)
                                            @php
                                                $cfg = $charge->default_config ?? [];
                                                $type = $charge->calc_type;
                                                $baseLabel = '';
                                                if (!empty($cfg['base'])) {
                                                    $baseOptions = [
                                                        'freight' => 'Shipping Charges',
                                                        'cod' => 'COD Amount',
                                                        'invoice' => 'Invoice Value',
                                                        'total' => 'Total Before GST'
                                                    ];
                                                    $baseLabel = $baseOptions[$cfg['base']] ?? '';
                                                }

                                                $hints = [
                                                    'per_kg_or_min' => 'Per Kg OR Min (kg:' . ($cfg['per_kg'] ?? '-') . ', min:' . ($cfg['min'] ?? '-') . ')',
                                                    'percent_or_min' => 'Percent OR Min (%:' . ($cfg['percent'] ?? '-') . ', min:' . ($cfg['min'] ?? '-') . ')' . ($baseLabel ? ' | ' . $baseLabel : ''),
                                                    'per_kg_or_flat' => 'Per Kg OR Flat (kg:' . ($cfg['per_kg'] ?? '-') . ', flat:' . ($cfg['flat'] ?? '-') . ')',
                                                    'flat' => 'Flat (' . ($cfg['flat'] ?? '-') . ')',
                                                    'divisor' => 'Divisor (' . ($cfg['divisor'] ?? '-') . ')',
                                                    'min_charge' => 'Min Freight (' . ($cfg['min'] ?? '-') . ')',
                                                    'gst_percent' => 'GST Percent (' . ($cfg['percent'] ?? '-') . '%)',
                                                    'handling_tier' => 'Handling Tier (<= ' . ($cfg['threshold'] ?? '-') . ': ' . ($cfg['rate_upto'] ?? '-') . ', >: ' . ($cfg['rate_above'] ?? '-') . ')',
                                                    'chargeable_weight' => 'Max(Volumetric, Actual)',
                                                    'min_weight' => 'Minimum Weight (' . ($cfg['min'] ?? '-') . ' kg)',
                                                    'freight' => 'Shipping Charges (Base)',
                                                    'max_single_box_weight' => 'Max Single Box (' . ($cfg['max_single_box_weight'] ?? '-') . ' kg)',
                                                ];
                                                $hint = $hints[$type] ?? '';
                                            @endphp
                                            <tr data-row='@json($charge)'>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="ocm-name">{{ $charge->name }}</div>
                                                    @if ($hint)
                                                        <div class="ocm-hint">{{ $hint }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="code-tag">{{ $charge->code }}</span>
                                                    @if(!empty($charge->zip_codes) && is_array($charge->zip_codes))
                                                        <div class="mt-1">
                                                            <span class="badge bg-secondary"
                                                                style="font-size: 10px;">{{ count($charge->zip_codes) }} Zip
                                                                Codes</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td><small
                                                        class="text-muted text-uppercase font-weight-bold">{{ str_replace('_', ' ', $charge->calc_type) }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if($charge->is_active)
                                                        <span class="chip-active">Active</span>
                                                    @else
                                                        <span class="chip-inactive">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="ocm-actions">
                                                        <button type="button" class="ocm-action-btn ocm-edit-btn edit-charge"
                                                            title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('rate.other-charges-master.delete', $charge->id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="shipping_company_id"
                                                                value="{{ old('shipping_company_id', $shippingCompanyId ?? optional($shippingCompany)->id) }}">
                                                            <button type="submit" class="ocm-action-btn ocm-delete-btn"
                                                                title="Delete" onclick="return confirm('Are you sure?')">
                                                                <i class="mdi mdi-delete"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="ocm-empty">
                                                    <i class="mdi mdi-database-off-outline d-block mb-2"
                                                        style="font-size: 32px; opacity: .5;"></i>
                                                    No charges found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="chargeMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('rate.other-charges-master.store') }}" id="chargeMasterForm"
                class="modal-content">
                @csrf
                <input type="hidden" name="shipping_company_id" id="charge_shipping_company_id"
                    value="{{ old('shipping_company_id', $shippingCompanyId ?? optional($shippingCompany)->id) }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="chargeModalTitle">Add Other Charge</h5>
                        <p class="text-muted mb-0" style="font-size: 13px;">Create or update a master charge rule.</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="charge_id" value="{{ old('id') }}">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="charge_name" class="form-control" value="{{ old('name') }}"
                                placeholder="Enter charge name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="charge_code" class="form-control" value="{{ old('code') }}"
                                placeholder="e.g. AWB_CHARGES" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Calculation Type</label>
                            <select name="calc_type" id="charge_calc_type" class="form-select" required>
                                @foreach($calcTypes as $type => $label)
                                    <option value="{{ $type }}" {{ old('calc_type') === $type ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="charge_active" class="form-select">
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row config-grid">
                        <div class="col-md-12 mb-3 d-none" id="zip_code_container">
                            <div class="ocm-field-head">
                                <label class="form-label">Zip Codes</label>
                                <button type="button" class="ocm-import-btn" id="openZipImportModal">
                                    <i class="mdi mdi-file-import-outline"></i>
                                    Import Pincodes
                                </button>
                            </div>
                            <select name="zip_codes[]" id="charge_zip_codes" class="form-control select2"
                                multiple="multiple" data-placeholder="Search and select zip codes..."></select>
                            <div class="ocm-import-panel" id="zipImportPanel">
                                <p>Paste comma, space, or line-separated pincodes. All valid values will be added
                                    automatically.</p>
                                <textarea id="zip_import_input" class="form-control" rows="5"
                                    placeholder="814155, 394230&#10;273012&#10;413005"></textarea>
                                <div class="ocm-import-actions">
                                    <button type="button" class="btn btn-light" id="cancelZipImport">Cancel</button>
                                    <button type="button" class="new-submit-btn" id="applyZipImport">Apply Pincodes</button>
                                </div>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">If zip codes are selected, this ODA/OPA
                                charge will only apply to those delivery zip codes.</small>
                        </div>

                        <div class="col-md-4 mb-3 config-col" data-field="per_kg">
                            <label class="form-label">Per Kg</label>
                            <input type="number" step="0.01" min="0" name="per_kg" id="cfg_per_kg" class="form-control"
                                value="{{ old('per_kg') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="percent">
                            <label class="form-label">Percent</label>
                            <input type="number" step="0.01" min="0" name="percent" id="cfg_percent" class="form-control"
                                value="{{ old('percent') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="flat">
                            <label class="form-label">Flat Amount</label>
                            <input type="number" step="0.01" min="0" name="flat" id="cfg_flat" class="form-control"
                                value="{{ old('flat') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="min">
                            <label class="form-label">Minimum</label>
                            <input type="number" step="0.01" min="0" name="min" id="cfg_min" class="form-control"
                                value="{{ old('min') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="divisor">
                            <label class="form-label">Divisor</label>
                            <input type="number" step="1" min="1" name="divisor" id="cfg_divisor" class="form-control"
                                value="{{ old('divisor') }}" placeholder="5000">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="threshold">
                            <label class="form-label">Threshold</label>
                            <input type="number" step="0.01" min="0" name="threshold" id="cfg_threshold"
                                class="form-control" value="{{ old('threshold') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="rate_upto">
                            <label class="form-label">Rate Upto</label>
                            <input type="number" step="0.01" min="0" name="rate_upto" id="cfg_rate_upto"
                                class="form-control" value="{{ old('rate_upto') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="rate_above">
                            <label class="form-label">Rate Above</label>
                            <input type="number" step="0.01" min="0" name="rate_above" id="cfg_rate_above"
                                class="form-control" value="{{ old('rate_above') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="max_single_box_weight">
                            <label class="form-label">Max Box Wt</label>
                            <input type="number" step="0.01" min="0" name="max_single_box_weight"
                                id="cfg_max_single_box_weight" class="form-control"
                                value="{{ old('max_single_box_weight') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3 config-col" data-field="base">
                            <label class="form-label">Calc. Base</label>
                            <select name="base" id="cfg_base" class="form-select">
                                <option value="">Default</option>
                                <option value="freight" {{ old('base') === 'freight' ? 'selected' : '' }}>Shipping Charges
                                </option>
                                <option value="cod" {{ old('base') === 'cod' ? 'selected' : '' }}>COD Amount</option>
                                <option value="invoice" {{ old('base') === 'invoice' ? 'selected' : '' }}>Invoice Value
                                </option>
                                <option value="total" {{ old('base') === 'total' ? 'selected' : '' }}>Total Before GST
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-reset" id="charge_reset_btn"
                        data-dismiss="modal">Cancel</button>
                    <button type="submit" class="new-submit-btn" id="charge_submit_btn">Save Charge</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('js')
    @php
        $oldChargeFormData = [
            'id' => old('id'),
            'shipping_company_id' => old('shipping_company_id', $shippingCompanyId ?? optional($shippingCompany)->id),
            'name' => old('name'),
            'code' => old('code'),
            'calc_type' => old('calc_type'),
            'is_active' => old('is_active', '1'),
            'zip_codes' => old('zip_codes', []),
            'default_config' => [
                'per_kg' => old('per_kg'),
                'percent' => old('percent'),
                'flat' => old('flat'),
                'min' => old('min'),
                'divisor' => old('divisor'),
                'threshold' => old('threshold'),
                'rate_upto' => old('rate_upto'),
                'rate_above' => old('rate_above'),
                'max_single_box_weight' => old('max_single_box_weight'),
                'base' => old('base'),
            ],
        ];
    @endphp
    <script>
        const calcTypeFieldMap = {
            chargeable_weight: [],
            min_weight: ['min'],
            freight: [],
            per_kg_or_min: ['per_kg', 'min'],
            percent_or_min: ['percent', 'min', 'base'],
            per_kg_or_flat: ['per_kg', 'flat'],
            flat: ['flat'],
            divisor: ['divisor'],
            min_charge: ['min'],
            gst_percent: ['percent'],
            handling_tier: ['threshold', 'rate_upto', 'rate_above'],
            max_single_box_weight: ['max_single_box_weight']
        };

        const oldChargeForm = @json($oldChargeFormData);
        const currentShippingCompanyId = "{{ old('shipping_company_id', $shippingCompanyId ?? optional($shippingCompany)->id) }}";

        function toggleConfigFields(type) {
            const allowed = calcTypeFieldMap[type] || [];
            document.querySelectorAll('.config-col').forEach((col) => {
                col.classList.toggle('show', allowed.includes(col.dataset.field));
            });
        }

        function checkAreaChargeVisibility() {
            const name = ($('#charge_name').val() || '').toLowerCase();
            const code = ($('#charge_code').val() || '').toLowerCase();
            const hasAreaChargeKeyword = name.includes('oda') || code.includes('oda') || name.includes('opa') || code.includes('opa');
            $('#zip_code_container').toggleClass('d-none', !hasAreaChargeKeyword);
        }

        function setModalMode(mode) {
            if (mode === 'edit') {
                $('#chargeModalTitle').text('Edit Other Charge');
                $('#charge_submit_btn').text('Update Charge');
            } else {
                $('#chargeModalTitle').text('Add Other Charge');
                $('#charge_submit_btn').text('Save Charge');
            }
        }

        function appendZipcodeOptions(zipcodes) {
            const zipSelect = $('#charge_zip_codes');
            const existingValues = zipSelect.val() || [];

            zipcodes.forEach(function (zip) {
                if (!zip || existingValues.includes(zip)) {
                    return;
                }

                zipSelect.append(new Option(zip, zip, true, true));
                existingValues.push(zip);
            });

            zipSelect.val(existingValues).trigger('change');
        }

        function hideZipImportPanel() {
            $('#zip_import_input').val('');
            $('#zipImportPanel').stop(true, true).slideUp(160);
        }

        function resetChargeForm() {
            document.getElementById('chargeMasterForm').reset();
            document.getElementById('chargeMasterForm').action = "{{ route('rate.other-charges-master.store') }}";
            $('#charge_id').val('');
            $('#charge_shipping_company_id').val(currentShippingCompanyId);
            $('#charge_active').val('1');
            $('#cfg_base').val('');
            $('#charge_zip_codes').empty().val(null).trigger('change');
            hideZipImportPanel();
            setModalMode('add');
            toggleConfigFields($('#charge_calc_type').val());
            checkAreaChargeVisibility();
        }

        function populateChargeForm(data) {
            const cfg = data.default_config || {};
            document.getElementById('chargeMasterForm').action = "{{ route('rate.other-charges-master.update', ':id') }}".replace(':id', data.id);
            $('#charge_id').val(data.id || '');
            $('#charge_shipping_company_id').val(data.shipping_company_id || currentShippingCompanyId);
            $('#charge_name').val(data.name || '');
            $('#charge_code').val(data.code || '');
            $('#charge_calc_type').val(data.calc_type || Object.keys(calcTypeFieldMap)[0]);
            $('#charge_active').val(data.is_active ? '1' : '0');
            $('#cfg_per_kg').val(cfg.per_kg ?? '');
            $('#cfg_percent').val(cfg.percent ?? '');
            $('#cfg_flat').val(cfg.flat ?? '');
            $('#cfg_min').val(cfg.min ?? '');
            $('#cfg_divisor').val(cfg.divisor ?? '');
            $('#cfg_threshold').val(cfg.threshold ?? '');
            $('#cfg_rate_upto').val(cfg.rate_upto ?? '');
            $('#cfg_rate_above').val(cfg.rate_above ?? '');
            $('#cfg_max_single_box_weight').val(cfg.max_single_box_weight ?? '');
            $('#cfg_base').val(cfg.base ?? '');

            const zipSelect = $('#charge_zip_codes');
            zipSelect.empty();
            if (Array.isArray(data.zip_codes)) {
                data.zip_codes.forEach(function (zip) {
                    zipSelect.append(new Option(zip, zip, true, true));
                });
            }
            zipSelect.trigger('change');

            hideZipImportPanel();
            setModalMode('edit');
            toggleConfigFields(data.calc_type);
            checkAreaChargeVisibility();
        }

        $(document).ready(function () {
            $('#charge_zip_codes').select2({
                ajax: {
                    url: "{{ route('zipcode.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                width: '100%',
                dropdownParent: $('#chargeMasterModal')
            });

            $('#btnAddCharge').on('click', function () {
                resetChargeForm();
                $('#chargeMasterModal').modal('show');
            });

            $('#openZipImportModal').on('click', function () {
                $('#zipImportPanel').stop(true, true).slideToggle(160);
                if ($('#zipImportPanel').is(':visible')) {
                    $('#zip_import_input').focus();
                }
            });

            $('#applyZipImport').on('click', function () {
                const rawValue = ($('#zip_import_input').val() || '').trim();
                if (!rawValue) {
                    hideZipImportPanel();
                    return;
                }

                const zipcodes = rawValue
                    .split(/[\s,]+/)
                    .map(function (value) { return value.trim(); })
                    .filter(function (value) { return /^[0-9]{6}$/.test(value); });

                if (!zipcodes.length) {
                    alert('Please paste valid 6-digit pincodes.');
                    return;
                }

                appendZipcodeOptions([...new Set(zipcodes)]);
                hideZipImportPanel();
            });

            $('#cancelZipImport').on('click', function () {
                hideZipImportPanel();
            });

            $('#charge_calc_type').on('change', function () {
                toggleConfigFields(this.value);
            });

            $('#charge_name').on('input', function () {
                checkAreaChargeVisibility();
                if ($('#charge_id').val() !== '') {
                    return;
                }
                $('#charge_code').val((($(this).val() || '').trim().toUpperCase().replace(/\s+/g, '_').replace(/[^A-Z0-9_]/g, '')));
            });

            $('#charge_code').on('input', function () {
                $(this).val((($(this).val() || '').toUpperCase().replace(/\s+/g, '_').replace(/[^A-Z0-9_]/g, '')));
                checkAreaChargeVisibility();
            });

            $(document).on('click', '.edit-charge', function () {
                const data = $(this).closest('tr').data('row') || {};
                populateChargeForm(data);
                $('#chargeMasterModal').modal('show');
            });

            $('#chargeMasterModal').on('hidden.bs.modal', function () {
                if (!@json($errors->any())) {
                    resetChargeForm();
                }
            });

            toggleConfigFields($('#charge_calc_type').val());
            checkAreaChargeVisibility();

            if (@json($errors->any())) {
                if (oldChargeForm && oldChargeForm.name) {
                    populateChargeForm(oldChargeForm);
                    if (!oldChargeForm.id) {
                        document.getElementById('chargeMasterForm').action = "{{ route('rate.other-charges-master.store') }}";
                        setModalMode('add');
                    }
                }
                $('#chargeMasterModal').modal('show');
            }
        });
    </script>
@endpush