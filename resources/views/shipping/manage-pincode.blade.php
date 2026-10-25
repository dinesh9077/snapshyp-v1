@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Manage Pincode')
@section('header_title', 'Manage Pincode')
@section('content')
    <style>
        :root {
            --new-color-prime: #3984FB;
        }

        .manage-pincode-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px
        }

        .manage-pincode-subtitle {
            color: #64748b;
            margin-bottom: 0;
            max-width: 780px
        }

        .manage-pincode-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px
        }

        .manage-pincode-summary-card {
            background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
            border: 1px solid #e2eaf5;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
            position: relative;
            overflow: hidden
        }

        .manage-pincode-summary-card:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px
        }

        .summary-total:before {
            background: var(--new-color-prime);
        }

        .summary-both:before {
            background: #16a34a
        }

        .summary-opa:before {
            background: #0ea5e9
        }

        .summary-pickup:before {
            background: #2563eb
        }

        .summary-delivery:before {
            background: #9333ea
        }

        .manage-pincode-summary-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px
        }

        .manage-pincode-summary-value {
            font-size: 30px;
            line-height: 1;
            font-weight: 700;
            color: #0f172a
        }

        .manage-pincode-summary-help {
            margin-top: 8px;
            font-size: 13px;
            color: #94a3b8
        }

        .manage-pincode-card {
            background: #fff;
            border: 1px solid #dbe7f8;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .05)
        }

        .manage-pincode-toolbar {
            padding: 18px 18px 14px;
            border-bottom: 1px solid #e8eef8;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fcfdff
        }

        .manage-pincode-toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap
        }

        .manage-pincode-filter-group,
        .manage-pincode-bulk-group,
        .manage-pincode-status-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap
        }

        .manage-pincode-search {
            width: 280px;
            max-width: 100%;
            position: relative;
            flex: 0 0 auto
        }

        .manage-pincode-search .form-control {
            padding-left: 40px;
            border-radius: 14px;
            height: 42px;
            border-color: #d6dfec;
            background: #fff
        }

        .manage-pincode-search i {
            position: absolute;
            left: 14px;
            top: 12px;
            color: #94a3b8;
            font-size: 18px
        }

        .manage-pincode-select {
            width: 180px;
            max-width: 100%;
            height: 42px;
            border-radius: 14px;
            border-color: #d6dfec;
            background: #fff
        }

        .manage-pincode-btn {
            height: 42px;
            border-radius: 14px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600
        }

        .manage-pincode-muted {
            color: #64748b;
            font-size: 14px
        }

        .manage-pincode-selection {
            font-size: 14px;
            color: #334155;
            font-weight: 500
        }

        .manage-pincode-table {
            width: 100%
        }

        .manage-pincode-table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #577196;
            border-bottom: 1px solid #e8eef8;
            padding: 13px 14px;
            background: #fbfdff
        }

        .manage-pincode-table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            border-top: 1px solid #eef3fa
        }

        .manage-pincode-location {
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .manage-pincode-pin {
            font-size: 18px;
            line-height: 1.1;
            font-weight: 700;
            color: #0f172a
        }

        .manage-pincode-place {
            font-size: 12px;
            color: #64748b
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600
        }

        .chip-partial {
            background: #fff7e8;
            color: #b45309
        }

        .chip-inactive {
            background: #f1f5f9;
            color: #64748b
        }

        .manage-pincode-paste-list {
            min-height: 180px;
            resize: vertical
        }

        .manage-pincode-filter-group .select2-container {
            width: 180px !important;
            min-width: 180px
        }

        .manage-pincode-filter-group .select2-selection--single {
            height: 42px;
            border: 1px solid #d6dfec;
            border-radius: 14px;
            background: #fff
        }

        .manage-pincode-filter-group .select2-selection__rendered {
            line-height: 40px !important;
            padding-left: 14px !important;
            color: #334155 !important
        }

        .manage-pincode-filter-group .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important
        }

        .dataTables_wrapper .dataTables_paginate {
            padding: 14px 18px
        }

        .dataTables_wrapper .dataTables_info {
            padding: 14px 18px;
            color: #64748b
        }

        .dataTables_wrapper .dataTables_length {
            padding: 14px 18px 0 18px
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 10px !important
        }

        .bulk-action.btn-primary {
            background: var(--new-color-prime);
            ;
            border-color: var(--new-color-prime);
        }

        .bulk-action.btn-success {
            background: #16a34a;
            border-color: #16a34a
        }

        .bulk-action.btn-outline-secondary,
        .bulk-action.btn-outline-danger {
            background: #fff
        }

        @media (max-width:1199px) {
            .manage-pincode-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .manage-pincode-search {
                width: 240px
            }

            .manage-pincode-filter-group .select2-container,
            .manage-pincode-select {
                width: 160px !important;
                min-width: 160px
            }
        }

        @media (max-width:767px) {
            .manage-pincode-header {
                flex-direction: column;
                align-items: stretch
            }

            .manage-pincode-summary-grid {
                grid-template-columns: 1fr
            }

            .manage-pincode-search,
            .manage-pincode-select,
            .manage-pincode-btn,
            .manage-pincode-filter-group,
            .manage-pincode-bulk-group,
            .manage-pincode-filter-group .select2-container {
                width: 100% !important;
                min-width: 100% !important
            }

            .manage-pincode-pin {
                font-size: 16px
            }
        }

        .service-mode-group {
            display: inline-flex;
            align-items: center;
            background: #eef2f7;
            border-radius: 14px;
            padding: 4px;
            gap: 4px;
            min-width: 260px
        }

        .service-mode-btn {
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            height: 32px;
            min-width: 74px;
            padding: 0 10px;
            border-radius: 10px;
            white-space: nowrap;
            transition: all .2s ease
        }

        .service-mode-btn.is-selected {
            color: #fff
        }

        .service-mode-pickup .service-mode-btn.is-selected {
            background: #2563eb;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .22)
        }

        .service-mode-delivery .service-mode-btn.is-selected {
            background: #9333ea;
            box-shadow: 0 8px 18px rgba(147, 51, 234, .22)
        }

        .service-mode-group .service-mode-btn:nth-child(2).is-selected {
            background: #64748b;
            box-shadow: 0 8px 18px rgba(100, 116, 139, .22)
        }

        .oda-category-select,
        .opa-category-select {
            min-width: 110px;
            height: 36px;
            border-radius: 999px;
            border-color: #d6dfec;
            font-weight: 600;
            font-size: 12px;
            padding: 0 12px
        }

        .manage-pincode-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0
        }

        .manage-pincode-switch input {
            display: none
        }

        .manage-pincode-slider {
            width: 46px;
            height: 26px;
            border-radius: 999px;
            background: #cbd5e1;
            position: relative;
            transition: all .2s ease
        }

        .manage-pincode-slider:after {
            content: "";
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            position: absolute;
            left: 3px;
            top: 3px;
            transition: all .2s ease;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .18)
        }

        .manage-pincode-switch input:checked+.manage-pincode-slider {
            background: #10b981
        }

        .manage-pincode-switch input:checked+.manage-pincode-slider:after {
            left: 23px
        }

        .manage-pincode-switch-text {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            min-width: 24px
        }

        .manage-pincode-table .oda-col,
        .manage-pincode-table .opa-col,
        .manage-pincode-table .cod-col {
            text-align: center;
            white-space: nowrap
        }

        .manage-pincode-selection-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            width: 100%
        }

        .manage-pincode-selection-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2563eb;
            font-weight: 700
        }

        .manage-pincode-selection-left i {
            font-size: 18px
        }

        .manage-pincode-selection-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap
        }

        .manage-pincode-bulk-select {
            min-width: 120px;
            height: 38px;
            border: 1px solid #bdd1ee;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            font-size: 14px;
            padding: 0 10px
        }

        .manage-pincode-clear-btn {
            background: none;
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            padding: 0 4px
        }

        .manage-pincode-bulk-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            color: #64748b;
            text-transform: uppercase
        }

        .manage-pincode-header {
            background: #ffffff;
            color: #1e2f48;
            text-align: left;
            padding: 18px 20px 12px;
            border-bottom: 1px solid #e6edf7;
            margin: 0 0 12px;
        }

        .manage-pincode-header .workspace-section-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #163a7c;
            line-height: 1.2;
        }

        .manage-pincode-header .manage-pincode-subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            color: #5f7394;
            font-weight: 500;
            line-height: 1.45;
            max-width: none;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-create-order">
                            <div class="manage-pincode-header">
                                <div>
                                    <h3 class="workspace-section-title">Manage Pincodes</h3>
                                    <p class="manage-pincode-subtitle">Manage pickup and delivery serviceable pincodes in
                                        one list. Any pincode activated here is used directly in rate calculator and
                                        shipping serviceability checks.</p>
                                </div>
                            </div>

                            <div class="manage-pincode-summary-grid">
                                <div class="manage-pincode-summary-card summary-total">
                                    <div class="manage-pincode-summary-label">Pickup Servicable</div>
                                    <div class="manage-pincode-summary-value" id="summary-pickup-serviceable">0</div>
                                </div>
                                <div class="manage-pincode-summary-card summary-total">
                                    <div class="manage-pincode-summary-label">Pickup Not Servicable</div>
                                    <div class="manage-pincode-summary-value" id="summary-pickup-not-serviceable">0</div>
                                </div>
                                <div class="manage-pincode-summary-card summary-delivery">
                                    <div class="manage-pincode-summary-label">Delivery Servicable</div>
                                    <div class="manage-pincode-summary-value" id="summary-delivery-serviceable">0</div>
                                </div>
                                <div class="manage-pincode-summary-card summary-delivery">
                                    <div class="manage-pincode-summary-label">Delivery Not Servicable</div>
                                    <div class="manage-pincode-summary-value" id="summary-delivery-not-serviceable">0</div>
                                </div>
                            </div>

                            <div class="manage-pincode-card">
                                <div class="manage-pincode-toolbar">
                                    <div class="manage-pincode-toolbar-row">
                                        <div class="manage-pincode-filter-group">
                                            <div class="manage-pincode-search">
                                                <i class="mdi mdi-magnify"></i>
                                                <input type="text" class="form-control" id="filter_search"
                                                    placeholder="Search pincode, city or state">
                                            </div>
                                            <button type="button" class="btn btn-outline-primary manage-pincode-btn"
                                                data-toggle="modal" data-target="#pasteListModal" id="paste-list-trigger">
                                                <i class="mdi mdi-clipboard-text-outline"></i>
                                                Paste List
                                            </button>
                                            <select name="filter_state" id="filter_state"
                                                class="form-control manage-pincode-select">
                                                <option value="">All States</option>
                                            </select>
                                            <select name="filter_city" id="filter_city"
                                                class="form-control manage-pincode-select">
                                                <option value="">All Cities</option>
                                            </select>
                                             <select name="filter_pickup" id="filter_pickup"
                                                 class="form-control manage-pincode-select">
                                                 <option value="">Pickup: All</option>
                                                 <option value="door">Serviceable</option>
                                                 <option value="drop">Not Serviceable</option>
                                             </select>
                                             <select name="filter_delivery" id="filter_delivery"
                                                 class="form-control manage-pincode-select">
                                                 <option value="">Delivery: All</option>
                                                 <option value="door">Serviceable</option>
                                                 <option value="pickup">Not Serviceable</option>
                                             </select>
                                        </div>
                                        <div class="manage-pincode-muted">
                                            <span id="showing-count-label">Showing 0 Pincodes</span>
                                        </div>
                                    </div>

                                    <div class="manage-pincode-toolbar-row d-none" id="bulk-update-row">
                                        <div class="manage-pincode-selection-bar">
                                            <div class="manage-pincode-selection-left">
                                                <i class="mdi mdi-check-circle-outline"></i>
                                                <span id="selected-count-label">0 Pincodes Selected</span>
                                            </div>
                                            <div class="manage-pincode-selection-right">
                                                <span class="manage-pincode-bulk-title">Bulk Update:</span>
                                                <select class="manage-pincode-bulk-select bulk-dropdown"
                                                    id="bulk-pickup-action">
                                                    <option value="">Set Pickup...</option>
                                                    <option value="door">Serviceable</option>
                                                    <option value="drop">Not Serviceable</option>
                                                </select>
                                                <select class="manage-pincode-bulk-select bulk-dropdown"
                                                    id="bulk-delivery-action">
                                                    <option value="">Set Delivery...</option>
                                                    <option value="door">Serviceable</option>
                                                    <option value="pickup">Not Serviceable</option>
                                                </select>
                                                <button type="button" class="manage-pincode-clear-btn"
                                                    id="clear-selection">Clear Selection</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="main-data-teble-1 table-responsive">
                                    <table id="manage-pincode-datatable" class="manage-pincode-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 44px;"><input type="checkbox" id="select-all-pincodes">
                                                </th>
                                                <th>Location</th>
                                                <th>Pickup Service</th>
                                                <th>Delivery Service</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pasteListModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Paste Pincode List</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                            class="mdi mdi-close"></i></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">Paste comma, space, or line separated pincodes. The table and bulk action
                        will work only on this pasted list.</p>
                    <textarea class="form-control manage-pincode-paste-list" id="paste_list_input"
                        placeholder="400001, 400002&#10;110001&#10;560001"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" id="clear-paste-list">Clear</button>
                    <button type="button" class="btn btn-primary" id="apply-paste-list">Apply List</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="odaChargesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Manage ODA Charges</h5>
                        <p class="text-muted mb-0" style="font-size: 13px;">Create as many ODA charge profiles as needed.
                            The selected ODA on a pincode will use this per-kg or minimum charge in rate calculator and
                            ship-now pricing.</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                            class="mdi mdi-close"></i></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 38%;">Charge Name</th>
                                    <th style="width: 22%;">Per Kg</th>
                                    <th style="width: 22%;">Minimum Charge</th>
                                    <th style="width: 18%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="oda-charge-rows"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-3" id="add-oda-charge-row">
                        <i class="mdi mdi-plus"></i>
                        Add ODA Charge
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-oda-charges">Save ODA Charges</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="opaChargesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Manage OPA Charges</h5>
                        <p class="text-muted mb-0" style="font-size: 13px;">Create as many OPA charge profiles as needed.
                            The selected OPA on a pincode will use this per-kg or minimum charge in rate calculator and
                            ship-now pricing.</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                            class="mdi mdi-close"></i></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 38%;">Charge Name</th>
                                    <th style="width: 22%;">Per Kg</th>
                                    <th style="width: 22%;">Minimum Charge</th>
                                    <th style="width: 18%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="opa-charge-rows"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-3" id="add-opa-charge-row">
                        <i class="mdi mdi-plus"></i>
                        Add OPA Charge
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-opa-charges">Save OPA Charges</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        let headerSelectActive = false;
        let allFilteredSelected = false;
        let filteredPincodeCount = 0;
        let pastedListValue = '';
        let searchDebounceTimer = null;
        let odaChargeOptions = @json($odaChargeOptions ?? []);
        let opaChargeOptions = @json($opaChargeOptions ?? []);

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function getSelectedPincodeIds() {
            return $('.pincode-checkbox:checked').map(function () {
                return $(this).val();
            }).get();
        }

        function updateSelectedCount() {
            const selectedCountValue = allFilteredSelected ? filteredPincodeCount : getSelectedPincodeIds().length;
            const selectedCount = selectedCountValue + ' Pincodes Selected' + (allFilteredSelected ? ' (All Filtered)' : '');
            $('#selected-count-label').text(selectedCount);
        }

        function updateBulkActionState() {
            const hasSelection = allFilteredSelected ? filteredPincodeCount > 0 : getSelectedPincodeIds().length > 0;
            $('.bulk-dropdown').prop('disabled', !hasSelection);
            $('#bulk-update-row').toggleClass('d-none', !hasSelection);
            updateSelectedCount();
        }

        function resetSelections() {
            headerSelectActive = false;
            allFilteredSelected = false;
            $('#select-all-pincodes').prop('checked', false);
            $('.pincode-checkbox').prop('checked', false);
            updateBulkActionState();
        }

        function updateSummaryCards(summary) {
            const safeSummary = summary || {};
            $('#summary-pickup-serviceable').text(Number(safeSummary.pickup_serviceable_count || 0));
            $('#summary-delivery-serviceable').text(Number(safeSummary.delivery_serviceable_count || 0));
            $('#summary-pickup-not-selected').text(Number(safeSummary.pickup_not_selected_count || 0));
            $('#summary-delivery-not-selected').text(Number(safeSummary.delivery_not_selected_count || 0));
            $('#summary-pickup-not-serviceable').text(Number(safeSummary.pickup_not_serviceable_count || 0));
            $('#summary-delivery-not-serviceable').text(Number(safeSummary.delivery_not_serviceable_count || 0));
        }

        function updateShowingCount(count) {
            filteredPincodeCount = Number(count || 0);
            $('#showing-count-label').text('Showing ' + filteredPincodeCount + ' Pincodes');
        }

        function getBulkSelectionPayload() {
            return allFilteredSelected ? {
                pincode_ids: [],
                apply_to_all_filtered: 1
            } : {
                pincode_ids: getSelectedPincodeIds(),
                apply_to_all_filtered: 0
            };
        }

        function updatePasteButtonLabel() {
            const list = pastedListValue.trim();
            if (!list) {
                $('#paste-list-trigger').html('<i class="mdi mdi-clipboard-text-outline"></i> Paste List');
                return;
            }

            const count = list.split(/[\s,]+/).filter(Boolean).length;
            $('#paste-list-trigger').html('<i class="mdi mdi-clipboard-check-outline"></i> Paste List (' + count + ')');
        }

        function buildOdaChargeRow(charge) {
            const row = charge || {};
            return `
                    <tr class="oda-charge-row" data-id="${row.id || ''}">
                        <td><input type="text" class="form-control oda-charge-name" value="${escapeHtml(row.name || '')}" placeholder="e.g. ODA Charge 1"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control oda-charge-per-kg" value="${row.per_kg != null ? escapeHtml(row.per_kg) : ''}" placeholder="0.00"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control oda-charge-min-charge" value="${row.min_charge != null ? escapeHtml(row.min_charge) : ''}" placeholder="0.00"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm remove-oda-charge-row">Remove</button></td>
                    </tr>
                `;
        }

        function renderOdaChargeRows() {
            const $tbody = $('#oda-charge-rows');
            $tbody.empty();

            if (!odaChargeOptions.length) {
                $tbody.append(buildOdaChargeRow({}));
                return;
            }

            odaChargeOptions.forEach(function (charge) {
                $tbody.append(buildOdaChargeRow(charge));
            });
        }

        function renderBulkOdaOptions() {
            const currentValue = $('#bulk-oda-action').val();
            let html = '<option value="">Set ODA...</option><option value="none">NONE</option>';

            odaChargeOptions.forEach(function (charge) {
                html += '<option value="' + charge.id + '">' + escapeHtml(charge.name) + '</option>';
            });

            $('#bulk-oda-action').html(html);
            if (currentValue) {
                $('#bulk-oda-action').val(currentValue);
            }
        }

        function collectOdaChargeRows() {
            return $('#oda-charge-rows').find('.oda-charge-row').map(function () {
                const $row = $(this);
                return {
                    id: $row.data('id') || '',
                    name: $row.find('.oda-charge-name').val(),
                    per_kg: $row.find('.oda-charge-per-kg').val(),
                    min_charge: $row.find('.oda-charge-min-charge').val()
                };
            }).get();
        }

        function buildOpaChargeRow(charge) {
            const row = charge || {};
            return `
                    <tr class="opa-charge-row" data-id="${row.id || ''}">
                        <td><input type="text" class="form-control opa-charge-name" value="${escapeHtml(row.name || '')}" placeholder="e.g. OPA Charge 1"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control opa-charge-per-kg" value="${row.per_kg != null ? escapeHtml(row.per_kg) : ''}" placeholder="0.00"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control opa-charge-min-charge" value="${row.min_charge != null ? escapeHtml(row.min_charge) : ''}" placeholder="0.00"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm remove-opa-charge-row">Remove</button></td>
                    </tr>
                `;
        }

        function renderOpaChargeRows() {
            const $tbody = $('#opa-charge-rows');
            $tbody.empty();

            if (!opaChargeOptions.length) {
                $tbody.append(buildOpaChargeRow({}));
                return;
            }

            opaChargeOptions.forEach(function (charge) {
                $tbody.append(buildOpaChargeRow(charge));
            });
        }

        function renderBulkOpaOptions() {
            const currentValue = $('#bulk-opa-action').val();
            let html = '<option value="">Set OPA...</option><option value="none">NONE</option>';

            opaChargeOptions.forEach(function (charge) {
                html += '<option value="' + charge.id + '">' + escapeHtml(charge.name) + '</option>';
            });

            $('#bulk-opa-action').html(html);
            if (currentValue) {
                $('#bulk-opa-action').val(currentValue);
            }
        }

        function collectOpaChargeRows() {
            return $('#opa-charge-rows').find('.opa-charge-row').map(function () {
                const $row = $(this);
                return {
                    id: $row.data('id') || '',
                    name: $row.find('.opa-charge-name').val(),
                    per_kg: $row.find('.opa-charge-per-kg').val(),
                    min_charge: $row.find('.opa-charge-min-charge').val()
                };
            }).get();
        }

        function getAjaxFilters() {
            return {
                _token: "{{ csrf_token() }}",
                search_value: $('#filter_search').val(),
                filter_city: $('#filter_city').val(),
                filter_state: $('#filter_state').val(),
                filter_pickup: $('#filter_pickup').val(),
                filter_delivery: $('#filter_delivery').val(),
                paste_list: pastedListValue
            };
        }

        function runBulkUpdate(payload) {
            $.ajax({
                url: "{{ route('shipping.company.manage-pincode.bulk-update', $shippingCompany->id) }}",
                type: 'POST',
                dataType: 'json',
                data: $.extend({}, getAjaxFilters(), payload),
                success: function (res) {
                    toastrMsg(res.status, res.msg);
                    pincodeTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Something went wrong.';
                    toastrMsg('error', msg);
                }
            });
        }

        function runBulkRowSettingUpdate(setting, value) {
            const selectedIds = getSelectedPincodeIds();
            if (!allFilteredSelected && !selectedIds.length) {
                toastrMsg('error', 'Please select at least one pincode.');
                return;
            }

            $.ajax({
                url: "{{ route('shipping.company.manage-pincode.bulk-update', $shippingCompany->id) }}",
                type: 'POST',
                dataType: 'json',
                data: $.extend({}, getAjaxFilters(), getBulkSelectionPayload(), {
                    setting: setting,
                    value: value
                }),
                success: function (res) {
                    toastrMsg(res.status, res.msg);
                    pincodeTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Something went wrong.';
                    toastrMsg('error', msg);
                }
            });
        }

        function runRowSettingUpdate(payload, onSuccess) {
            $.ajax({
                url: "{{ route('shipping.company.manage-pincode.row-update', $shippingCompany->id) }}",
                type: 'POST',
                dataType: 'json',
                data: $.extend({ _token: "{{ csrf_token() }}" }, payload),
                success: function (res) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(res);
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Something went wrong.';
                    toastrMsg('error', msg);
                    pincodeTable.ajax.reload(null, false);
                }
            });
        }

        const responsiveCellLabels = ['Select', 'Location', 'Pickup Service', 'Delivery Service', 'Status'];

        const pincodeTable = $('#manage-pincode-datatable').DataTable({
            processing: true,
            language: { loadingRecords: '&nbsp;', processing: 'Loading...' },
            serverSide: true,
            lengthChange: true,
            lengthMenu: [[25, 100, 250, 500, 1000], [25, 100, 250, 500, 1000]],
            searching: false,
            info: true,
            pageLength: 1000,
            order: [[1, 'asc']],
            autoWidth: false,
            dom: 'rt<"manage-pincode-table-footer d-flex flex-wrap align-items-center justify-content-between"lip>',
            ajax: {
                url: "{{ route('shipping.company.manage-pincode.ajax', $shippingCompany->id) }}",
                dataType: 'json',
                type: 'POST',
                data: function (d) {
                    return $.extend({}, d, getAjaxFilters());
                },
                dataSrc: function (json) {
                    updateSummaryCards(json.summary || {});
                    updateShowingCount(json.showing_count || 0);
                    resetSelections();
                    return json.aaData || [];
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'location' },
                { data: 'pickup_service', orderable: false, searchable: false },
                { data: 'delivery_service', orderable: false, searchable: false },
            ],
            createdRow: function (row) {
                $(row).children('td').each(function (index) {
                    $(this).attr('data-label', responsiveCellLabels[index] || '');
                });
            }
        });

        function reloadPincodeTable() {
            pincodeTable.ajax.reload();
        }

        function loadFilterOptions() {
            $.ajax({
                url: "{{ route('zipcode.filter-options') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    include_cities: 0
                },
                success: function (response) {
                    const states = Array.isArray(response && response.states) ? response.states : [];

                    $('#filter_state').empty().append('<option value="">All States</option>');

                    states.forEach(function (state) {
                        $('#filter_state').append('<option value="' + state + '">' + state + '</option>');
                    });

                    if ($.fn.select2) {
                        $('#filter_city, #filter_state, #filter_service').select2({ width: 'resolve' });
                    }
                }
            });
        }

        function loadCityOptions(stateValue) {
            $('#filter_city').empty().append('<option value="">All Cities</option>');

            if (!stateValue) {
                if ($.fn.select2 && $('#filter_city').hasClass('select2-hidden-accessible')) {
                    $('#filter_city').val('').trigger('change.select2');
                }
                return;
            }

            $.ajax({
                url: "{{ route('zipcode.filter-options') }}",
                type: 'GET',
                dataType: 'json',
                data: {
                    state: stateValue
                },
                success: function (response) {
                    const cities = Array.isArray(response && response.cities) ? response.cities : [];
                    cities.forEach(function (city) {
                        $('#filter_city').append('<option value="' + city + '">' + city + '</option>');
                    });
                }
            });
        }

        $(document).on('change', '#filter_state, #filter_city, #filter_pickup, #filter_delivery', function () {
            resetSelections();
            reloadPincodeTable();
            if ($(this).attr('id') === 'filter_state') {
                loadFilterOptions();
            }
        });

        $('#filter_search').on('input', function () {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () {
                reloadPincodeTable();
            }, 350);
        });

        $('#select-all-pincodes').on('change', function () {
            headerSelectActive = $(this).is(':checked');
            allFilteredSelected = headerSelectActive && filteredPincodeCount > 0;
            $('.pincode-checkbox').prop('checked', headerSelectActive);
            updateBulkActionState();
        });

        $(document).on('change', '.pincode-checkbox', function () {
            if (allFilteredSelected && !$(this).is(':checked')) {
                allFilteredSelected = false;
            }

            const total = $('.pincode-checkbox').length;
            const checked = $('.pincode-checkbox:checked').length;
            headerSelectActive = total > 0 && checked === total;
            $('#select-all-pincodes').prop('checked', headerSelectActive);
            updateBulkActionState();
        });

        $('#bulk-pickup-action').on('change', function () {
            const value = $(this).val();
            if (!value) {
                return;
            }

            runBulkRowSettingUpdate('pickup_service', value);
            $(this).val('');
        });

        $('#bulk-delivery-action').on('change', function () {
            const value = $(this).val();
            if (!value) {
                return;
            }

            runBulkRowSettingUpdate('delivery_service', value);
            $(this).val('');
        });

        $('#clear-selection').on('click', function () {
            resetSelections();
        });

        $(document).on('click', '.quick-setting-update', function () {
            const $button = $(this);
            if ($button.hasClass('is-selected')) {
                return;
            }

            runRowSettingUpdate({
                pincode_id: $button.data('id'),
                setting: $button.data('setting'),
                value: $button.data('value')
            }, function () {
                $button.closest('.service-mode-group').find('.service-mode-btn').removeClass('is-selected');
                $button.addClass('is-selected');
            });
        });

        $(document).on('change', '.quick-setting-select', function () {
            const $select = $(this);
            runRowSettingUpdate({
                pincode_id: $select.data('id'),
                setting: $select.data('setting'),
                value: $select.val()
            });
        });

        $(document).on('change', '.quick-cod-toggle', function () {
            const $toggle = $(this);
            runRowSettingUpdate({
                pincode_id: $toggle.data('id'),
                setting: $toggle.data('setting'),
                value: $toggle.is(':checked') ? 1 : 0
            }, function () {
                $toggle.closest('.manage-pincode-switch').find('.manage-pincode-switch-text').text($toggle.is(':checked') ? 'Yes' : 'No');
            });
        });

        $('#apply-paste-list').on('click', function () {
            pastedListValue = $('#paste_list_input').val();
            updatePasteButtonLabel();
            $('#pasteListModal').modal('hide');
            reloadPincodeTable();
        });

        $('#clear-paste-list').on('click', function () {
            pastedListValue = '';
            $('#paste_list_input').val('');
            updatePasteButtonLabel();
            $('#pasteListModal').modal('hide');
            reloadPincodeTable();
        });

        $('#open-oda-charges-modal').on('click', function () {
            renderOdaChargeRows();
            $('#odaChargesModal').modal('show');
        });

        $('#open-opa-charges-modal').on('click', function () {
            renderOpaChargeRows();
            $('#opaChargesModal').modal('show');
        });

        $('#add-oda-charge-row').on('click', function () {
            $('#oda-charge-rows').append(buildOdaChargeRow({}));
        });

        $('#add-opa-charge-row').on('click', function () {
            $('#opa-charge-rows').append(buildOpaChargeRow({}));
        });

        $(document).on('click', '.remove-oda-charge-row', function () {
            $(this).closest('.oda-charge-row').remove();
            if (!$('#oda-charge-rows').find('.oda-charge-row').length) {
                $('#oda-charge-rows').append(buildOdaChargeRow({}));
            }
        });

        $(document).on('click', '.remove-opa-charge-row', function () {
            $(this).closest('.opa-charge-row').remove();
            if (!$('#opa-charge-rows').find('.opa-charge-row').length) {
                $('#opa-charge-rows').append(buildOpaChargeRow({}));
            }
        });

        $('#save-oda-charges').on('click', function () {
            $.ajax({
                url: "{{ route('shipping.company.manage-pincode.oda-charges.save', $shippingCompany->id) }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    charges: collectOdaChargeRows()
                },
                success: function (res) {
                    odaChargeOptions = Array.isArray(res.charges) ? res.charges : [];
                    renderBulkOdaOptions();
                    $('#odaChargesModal').modal('hide');
                    toastrMsg(res.status, res.msg);
                    pincodeTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Something went wrong.';
                    toastrMsg('error', msg);
                }
            });
        });

        $('#save-opa-charges').on('click', function () {
            $.ajax({
                url: "{{ route('shipping.company.manage-pincode.opa-charges.save', $shippingCompany->id) }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    charges: collectOpaChargeRows()
                },
                success: function (res) {
                    opaChargeOptions = Array.isArray(res.charges) ? res.charges : [];
                    renderBulkOpaOptions();
                    $('#opaChargesModal').modal('hide');
                    toastrMsg(res.status, res.msg);
                    pincodeTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Something went wrong.';
                    toastrMsg('error', msg);
                }
            });
        });

        $(document).ready(function () {
            loadFilterOptions();
            updatePasteButtonLabel();
            updateBulkActionState();
            renderBulkOdaOptions();
            renderBulkOpaOptions();
        });
    </script>
@endpush
