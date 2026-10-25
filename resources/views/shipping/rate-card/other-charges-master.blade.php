@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Other Charges Master')
@section('header_title', 'Other Charges Master')
@section('content')
    <style>
        :root {
            --prime: #3984FB;
            --second: #3984FB;
            --light-bg: #f8fafd;
            --border-color: #94a3b8;
            /* Darker, more visible border */
        }

        .master-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .master-card.edit-mode {
            border-color: #FB5C08;
            box-shadow: 0 4px 25px rgba(251, 92, 8, 0.15);
            background-color: #fff9f6;
            /* Soft orange/theme background for edit mode */
        }

        .master-card h5 {
            font-size: 16px;
            color: #1a202c;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .master-form .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            padding: 9px 14px;
            /* Slightly tighter padding */
            font-size: 14px;
            color: #2d3748;
            transition: all 0.2s;
            background-color: #fff;
        }

        /* Primary Save Button - Matching JDAC Theme */
        .btn-prime {
            background: #FB5C08;
            color: #fff;
            border: none;
            padding: 10px 32px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(251, 92, 8, 0.2);
        }

        .btn-prime:hover {
            background: #e04a00;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(251, 92, 8, 0.3);
        }

        /* Cancel Button - Matching Theme */
        .btn-cancel {
            background: #fff;
            color: #64748b;
            border: 1.5px solid #cbd5e0;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #1e293b;
            border-color: #94a3b8;
        }

        .config-grid .config-col {
            display: none;
        }

        .config-grid .config-col.show {
            display: block;
        }

        /* .table-container {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table thead th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 14px;
            color: #2d3748;
            border-bottom: 1px solid #edf2f7;
        } */

        .form-control:focus,
        .form-select:focus {
            border-color: #FB5C08 !important;
            box-shadow: 0 0 0 3px rgba(251, 92, 8, 0.1) !important;
            outline: none;
        }

        .action-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: 1px solid transparent;
            background: transparent;
            font-size: 16px;
        }

        .btn-edit {
            color: #FB5C08;
            background: rgba(251, 92, 8, 0.05);
        }

        .btn-edit:hover {
            background: #FB5C08;
            color: #fff;
            transform: scale(1.1);
        }

        .btn-delete {
            color: #e11d48;
            background: rgba(225, 29, 72, 0.05);
        }

        .btn-delete:hover {
            background: #e11d48;
            color: #fff;
            transform: scale(1.1);
        }

        .chip-active {
            color: #2f855a;
            background: #f0fff4;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .chip-inactive {
            color: #c53030;
            background: #fff5f5;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .code-tag {
            font-family: 'Monaco', 'Consolas', monospace;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid #e2e8f0;
        }

        /* Select2 UI Fixes */
        .select2-container--default .select2-selection--multiple {
            border: 1.5px solid var(--border-color) !important;
            border-radius: 8px !important;
            min-height: 40px !important;
            background-color: #fff !important;
            padding: 2px 8px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #FB5C08 !important;
            box-shadow: 0 0 0 3px rgba(251, 92, 8, 0.1) !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            color: #1e293b !important;
            padding: 4px 8px !important;
            margin-top: 5px !important;
            font-weight: 500 !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #e11d48 !important;
            margin-right: 6px !important;
            font-weight: bold !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background-color: transparent !important;
            color: #be123c !important;
        }

        .select2-search__field {
            margin-top: 7px !important;
            color: #2d3748 !important;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="main-order-001">
                    <!-- Form Card -->
                    <div class="" id="form-card">
                        <h5 id="form-title" class="card-title d-inline-block">
                            <!-- <i class="mdi mdi-plus-circle-outline"></i>  -->
                            <span>Add New Other Charge</span>
                        </h5>
                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 8px;">
                                @foreach ($errors->all() as $error)
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="mdi mdi-alert-circle"></i>
                                        {{ $error }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('rate.other-charges-master.store') }}" id="chargeMasterForm"
                            class="master-form">
                            @csrf
                            <input type="hidden" name="id" id="charge_id">
                            <div class="row row-re reportOrderForm order-filter border-bottom-0 m-0">
                                <div class="col-md-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" id="charge_name" class="form-control"
                                        placeholder="Enter charge name (e.g. AWB Charges)" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" id="charge_code" class="form-control"
                                        placeholder="e.g. AWB_CHARGES" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Calculation Type</label>
                                    <select name="calc_type" id="charge_calc_type" class="form-select" required>
                                        @foreach($calcTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="is_active" id="charge_active" class="form-select">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Config Fields -->
                            <div class="row row-re reportOrderForm order-filter m-0 mb-3 config-grid">
                                <div class="col-md-12 mb-2 d-none" id="zip_code_container">
                                    <label class="form-label">Zip Codes</label>
                                    <select name="zip_codes[]" id="charge_zip_codes" class="form-control select2"
                                        multiple="multiple" data-placeholder="Search and select zip codes...">
                                    </select>
                                    <small class="text-muted" style="font-size: 11px;">If zip codes are selected, this ODA
                                        charge will ONLY apply to these specific delivery zip codes.</small>
                                </div>
                                <!-- Fields hidden/shown by JS -->
                                <div class="col-md-2 config-col" data-field="per_kg">
                                    <label class="form-label">Per Kg</label>
                                    <input type="number" step="0.01" min="0" name="per_kg" id="cfg_per_kg"
                                        class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="percent">
                                    <label class="form-label">Percent</label>
                                    <input type="number" step="0.01" min="0" name="percent" id="cfg_percent"
                                        class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="flat">
                                    <label class="form-label">Flat Amount</label>
                                    <input type="number" step="0.01" min="0" name="flat" id="cfg_flat" class="form-control"
                                        placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="min">
                                    <label class="form-label">Minimum</label>
                                    <input type="number" step="0.01" min="0" name="min" id="cfg_min" class="form-control"
                                        placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="divisor">
                                    <label class="form-label">Divisor</label>
                                    <input type="number" step="1" min="1" name="divisor" id="cfg_divisor"
                                        class="form-control" placeholder="5000">
                                </div>
                                <div class="col-md-2 config-col" data-field="threshold">
                                    <label class="form-label">Threshold</label>
                                    <input type="number" step="0.01" min="0" name="threshold" id="cfg_threshold"
                                        class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="rate_upto">
                                    <label class="form-label">Rate Upto</label>
                                    <input type="number" step="0.01" min="0" name="rate_upto" id="cfg_rate_upto"
                                        class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="rate_above">
                                    <label class="form-label">Rate Above</label>
                                    <input type="number" step="0.01" min="0" name="rate_above" id="cfg_rate_above"
                                        class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="max_single_box_weight">
                                    <label class="form-label">Max Box Wt</label>
                                    <input type="number" step="0.01" min="0" name="max_single_box_weight"
                                        id="cfg_max_single_box_weight" class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-2 config-col" data-field="base">
                                    <label class="form-label">Calc. Base</label>
                                    <select name="base" id="cfg_base" class="form-select">
                                        <option value="">Default</option>
                                        <option value="freight">Shipping Charges</option>
                                        <option value="cod">COD Amount</option>
                                        <option value="invoice">Invoice Value</option>
                                        <option value="total">Total Before GST</option>
                                    </select>
                                </div>

                                <!-- Form Action Buttons - Positioned on new line for prominence -->
                                <div class="col-md-12 pt-2 text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <button type="submit" class="new-submit-btn" id="charge_submit_btn">
                                            <i class="mdi mdi-content-save-check"></i> <span>Save Charge</span>
                                        </button>
                                        <button type="button" class="btn btn-light btn-reset d-none ml-2"
                                            id="charge_reset_btn">
                                            <i class="mdi mdi-refresh"></i> <span>Cancel</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- List Card -->
                    <div class="">
                        <h5 class="card-title d-inline-block">
                            <!-- <i class="mdi mdi-format-list-bulleted"></i>  -->
                            <span>Other Charges List</span>
                        </h5>
                        <div class="">
                            <div class="table-responsive">
                                <table class="dataTable no-footer">
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
                                                $hint = '';
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
                                                    'percent_or_min' => 'Percent OR Min (%:' . ($cfg['percent'] ?? '-') . ', min:' . ($cfg['min'] ?? '-') . ')' . ($baseLabel ? ' • ' . $baseLabel : ''),
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
                                                <td><span class="text-secondary fw-600">{{ $loop->iteration }}</span></td>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $charge->name }}</div>
                                                    @if ($hint)
                                                        <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                                                            {{ $hint }}</div>
                                                    @endif
                                                </td>
                                                <td><span class="code-tag">{{ $charge->code }}</span>
                                                    @if(!empty($charge->zip_codes) && is_array($charge->zip_codes))
                                                        <div class="mt-1">
                                                            <span class="badge bg-secondary"
                                                                style="font-size: 10px;">{{ count($charge->zip_codes) }} Zip
                                                                Codes</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td><small
                                                        class="text-muted text-uppercase fw-500">{{ str_replace('_', ' ', $charge->calc_type) }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if($charge->is_active)
                                                        <span class="chip-active">Active</span>
                                                    @else
                                                        <span class="chip-inactive">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="action-container">
                                                        <button type="button"
                                                            class="btn btn-icon waves-effect waves-light action-icon mr-1 edit-charge"
                                                            title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('rate.other-charges-master.delete', $charge->id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-icon waves-effect waves-light action-icon  btn-delete"
                                                                title="Delete" onclick="return confirm('Are you sure?')">
                                                                <i class="mdi mdi-delete"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="mdi mdi-database-off-outline d-block mb-2"
                                                        style="font-size: 32px; opacity: 0.5;"></i>
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
@endsection

@push('js')
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

        function toggleConfigFields(type) {
            const allowed = calcTypeFieldMap[type] || [];
            document.querySelectorAll('.config-col').forEach((col) => {
                const field = col.dataset.field;
                col.classList.toggle('show', allowed.includes(field));
            });
        }

        function checkOdaVisibility() {
            const name = (document.getElementById('charge_name').value || '').toLowerCase();
            const code = (document.getElementById('charge_code').value || '').toLowerCase();

            if (name.includes('oda') || code.includes('oda')) {
                document.getElementById('zip_code_container').classList.remove('d-none');
            } else {
                document.getElementById('zip_code_container').classList.add('d-none');
            }
        }

        function resetChargeForm() {
            document.getElementById('charge_id').value = '';
            document.getElementById('charge_name').value = '';
            document.getElementById('charge_code').value = '';
            document.getElementById('charge_calc_type').selectedIndex = 0;
            document.getElementById('charge_active').value = '1';
            $('#charge_zip_codes').val(null).trigger('change');
            document.querySelectorAll('.config-grid input').forEach(i => i.value = '');
            document.getElementById('cfg_base').value = '';

            // Reset Labels & Styles
            const form = document.getElementById('chargeMasterForm');
            form.action = "{{ route('rate.other-charges-master.store') }}";
            document.getElementById('charge_submit_btn').innerHTML = 'Save Charge';
            document.getElementById('charge_reset_btn').classList.add('d-none');
            document.getElementById('form-card').classList.remove('edit-mode');
            document.getElementById('form-title').innerHTML = '<span>Add New Other Charge</span>';

            toggleConfigFields(document.getElementById('charge_calc_type').value);
            checkOdaVisibility();
        }

        document.getElementById('charge_calc_type').addEventListener('change', function () {
            toggleConfigFields(this.value);
        });

        document.getElementById('charge_name').addEventListener('input', function () {
            checkOdaVisibility();
            if (document.getElementById('charge_id').value !== '') return;
            const codeInput = document.getElementById('charge_code');
            codeInput.value = this.value.trim().toUpperCase().replace(/\s+/g, '_').replace(/[^A-Z0-9_]/g, '');
        });

        document.getElementById('charge_code').addEventListener('input', function () {
            checkOdaVisibility();
            this.value = this.value.toUpperCase().replace(/\s+/g, '_').replace(/[^A-Z0-9_]/g, '');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.edit-charge')) return;
            const btn = e.target.closest('.edit-charge');
            const row = btn.closest('tr');
            if (!row) return;
            const data = JSON.parse(row.dataset.row || '{}');

            document.getElementById('charge_id').value = data.id || '';
            document.getElementById('charge_name').value = data.name || '';
            document.getElementById('charge_code').value = data.code || '';
            document.getElementById('charge_calc_type').value = data.calc_type || '';
            document.getElementById('charge_active').value = data.is_active ? '1' : '0';

            if (data.zip_codes && Array.isArray(data.zip_codes)) {
                const select = $('#charge_zip_codes');
                select.empty();
                data.zip_codes.forEach(zip => {
                    select.append(new Option(zip, zip, true, true));
                });
                select.trigger('change');
            } else {
                $('#charge_zip_codes').val(null).trigger('change');
            }

            const cfg = data.default_config || {};
            const fields = ['per_kg', 'percent', 'flat', 'min', 'max', 'divisor', 'threshold', 'rate_upto', 'rate_above', 'max_single_box_weight'];
            fields.forEach(f => {
                const el = document.getElementById('cfg_' + f);
                if (el) el.value = cfg[f] ?? '';
            });
            document.getElementById('cfg_base').value = cfg.base ?? '';

            // Update Form State
            const formCard = document.getElementById('form-card');
            formCard.classList.add('edit-mode');
            document.getElementById('form-title').innerHTML = '<span>Editing Charge: <strong style="color: #FB5C08;">' + data.name + '</strong></span>';

            const form = document.getElementById('chargeMasterForm');
            form.action = "{{ route('rate.other-charges-master.update', ':id') }}".replace(':id', data.id);
            document.getElementById('charge_submit_btn').innerHTML = '<span>Update Charge</span>';
            document.getElementById('charge_reset_btn').classList.remove('d-none');
            document.getElementById('charge_reset_btn').innerHTML = '<span>Cancel</span>';

            toggleConfigFields(data.calc_type);
            checkOdaVisibility();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.getElementById('charge_reset_btn').addEventListener('click', resetChargeForm);

        toggleConfigFields(document.getElementById('charge_calc_type').value);
        checkOdaVisibility();

        $(document).ready(function () {
            $('#charge_zip_codes').select2({
                ajax: {
                    url: "{{ route('zipcode.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                width: '100%',
                dropdownParent: $('#form-card')
            });
        });
    </script>
@endpush