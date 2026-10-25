@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Rate Card')
@section('header_title', 'Rate Card')
@section('content')
    <style>
        :root {
            --primary-color: #FB5C08;
            --secondary-color: #FB5C08;
            --success-color: #00c853;
            --light-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --new-color-prime: #3984FB;
        }

        .nav-tabs-custom {
            display: flex;
            justify-content: flex-start;
            gap: 0;
            margin: 0;
            background: transparent;
            padding: 10px 0 0;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            background: transparent;
        }

        .nav-tabs-custom .nav-link.active {
            color: #fff;
            border-bottom-color: var(--new-color-prime);
            background: var(--new-color-prime);
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--new-color-prime);
            border-bottom-color: var(--new-color-prime);
        }

        .admin-inner-tabs {
            margin-top: 10px;
        }

        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .filter-fields {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            flex: 1 1 auto;
            flex-wrap: wrap;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-left: auto;
            flex: 0 0 auto;
        }

        .matrix-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .matrix-header {
            background: #fff;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .matrix-table-container {
            overflow-x: auto;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .matrix-table th {
            background: #fff1ec;
            color: var(--new-color-prime);
            font-weight: 700;
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .matrix-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .matrix-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .matrix-table tr:hover {
            background: #fff1ec;
        }

        .zone-label {
            background: #fff;
            font-weight: 700;
            color: #1e293b;
        }

        .company-logo-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 20px;
        }

        .company-logo {
            max-height: 50px;
            opacity: 0.8;
        }

        /* Overhead Charges Re-styled to match reference exactly */
        .overhead-card {
            background: #fff;
            border: 1.5px solid #edf2f7;
            border-radius: 12px;
            margin-top: 35px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .overhead-header {
            background: #fff;
            color: #0f172a;
            padding: 18px 25px;
            font-weight: 800;
            font-size: 16px;
            border-bottom: 2px solid #f8fafc;
        }

        .overhead-body {
            display: flex;
            background: #fff;
            min-height: 250px;
        }

        .overhead-table {
            width: 100%;
            border-collapse: collapse;
        }

        .overhead-table tr:nth-child(even) {
            background-color: #fffaf9;
            /* Soft orangeish tint for stripes */
        }

        .overhead-table td {
            padding: 16px 25px;
            font-size: 14px;
            color: #475569;
            border: none;
        }

        .overhead-table td:first-child {
            width: 32%;
            font-weight: 700;
            color: #1e293b;
        }

        .company-logo-sidebar {
            width: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: #fff;
            border-left: 2px solid #f8fafc;
        }

        .company-logo-sidebar img {
            max-width: 220px;
            height: auto;
            opacity: 0.95;
            filter: grayscale(0.1);
        }

        .company-logo-sidebar .text-muted {
            font-size: 24px;
            font-weight: 800;
            color: #cbd5e1 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-action-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: var(--secondary-color);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            margin-left: 8px;
        }

        .btn-action-icon:hover {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 92, 8, 0.2);
        }

        .btn-action-icon i {
            font-size: 20px;
        }

        .admin-matrix-card {
            border: 1px solid #d9e4f1;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(14, 34, 66, 0.08);
            overflow: hidden;
            background: #fff;
        }

        .admin-matrix-title-wrap {
            padding: 18px 20px 12px;
            border-bottom: 1px solid #e6edf7;
            background: #ffffff;
        }

        .admin-matrix-title-wrap h4 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            font-weight: 800;
            color: #163a7c;
        }

        .admin-matrix-title-wrap p {
            margin: 6px 0 0;
            font-size: 14px;
            line-height: 1.45;
            color: #5f7394;
        }

        .admin-matrix-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .admin-matrix-title-content {
            min-width: 0;
            flex: 1 1 auto;
        }

        .admin-matrix-actions-wrap {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            flex: 0 0 auto;
        }

        .admin-matrix-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            margin-top: 0;
        }

        .admin-matrix-rate-card-select {
            min-width: 220px;
            height: 38px;
            border: 1px solid #c8d6ea;
            border-radius: 10px;
            background: #fff;
            color: #284162;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 10px;
        }

        .admin-matrix-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid #c8d6ea;
            background: #ffffff;
            color: #667a9b;
            box-sizing: border-box;
            transform: none !important;
            box-shadow: none !important;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease;
            cursor: pointer;
            padding: 0;
        }

        .admin-matrix-icon-btn i {
            font-size: 18px;
            line-height: 1;
        }

        .admin-matrix-icon-btn:focus,
        .admin-matrix-icon-btn:hover,
        .admin-matrix-icon-btn:active {
            transform: none !important;
            box-shadow: none !important;
        }

        .admin-matrix-icon-btn-secondary:hover,
        .admin-matrix-icon-btn-secondary:focus {
            background: #f8fbff;
            border-color: #aebfd8;
            color: #3f567a;
        }

        .admin-matrix-icon-btn-primary {
            background: var(--new-color-prime);
            border-color: var(--new-color-prime);
            color: #ffffff;
        }

        .admin-matrix-icon-btn-primary:hover,
        .admin-matrix-icon-btn-primary:focus {
            background: var(--new-color-prime);
            border-color: var(--new-color-prime);
            color: #ffffff;
        }

        .admin-matrix-icon-btn[disabled] {
            opacity: .6;
            cursor: not-allowed;
        }

        .admin-matrix-icon-btn.is-loading i {
            animation: adminMatrixSpin .8s linear infinite;
        }

        @keyframes adminMatrixSpin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .admin-matrix-save-status {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-align: right;
            min-height: 16px;
            display: block;
        }

        .admin-matrix-save-status.is-success,
        .admin-b2c-save-status.is-success {
            color: #15803d;
        }

        .admin-matrix-save-status.is-error,
        .admin-b2c-save-status.is-error {
            color: #b91c1c;
        }

        .admin-matrix-table .rate-cell {
            min-width: 72px;
        }

        .admin-matrix-input {
            width: 70px;
            height: 30px;
            border: 1px solid #c8d5ea;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #133c7c;
            background: #ffffff;
            padding: 4px 6px;
        }

        .admin-matrix-input:focus {
            outline: none;
            border-color: var(--new-color-prime);
            ;
            box-shadow: 0 0 0 2px rgba(31, 75, 153, 0.14);
        }

        .admin-b2c-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 10px;
        }

        .admin-b2c-save-status {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-right: 4px;
            min-height: 16px;
        }

        .admin-b2c-input {
            width: 88px;
            height: 32px;
            border: 1px solid #c8d5ea;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #133c7c;
            background: #ffffff;
            padding: 4px 6px;
        }

        .admin-b2c-input:focus {
            outline: none;
            border-color: var(--new-color-prime);
            ;
            box-shadow: 0 0 0 2px rgba(31, 75, 153, 0.14);
        }

        .admin-b2c-matrix-table th {
            background: #f8f9fa;
        }

        .admin-b2c-matrix-table td {
            vertical-align: middle;
        }

        .admin-b2c-cell-pair {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 180px;
        }

        .admin-b2c-cell-pair .admin-b2c-input {
            width: 82px;
        }

        .admin-matrix-table-wrap {
            padding: 14px 20px 20px;
            background: #fff;
        }

        .admin-matrix-table {
            width: 100%;
            min-width: 860px;
            border-collapse: collapse;
            font-size: 14px;
            color: #1c3151;
        }

        .admin-matrix-table th,
        .admin-matrix-table td {
            border: 1px solid #d2dbe8;
            text-align: center;
            padding: 14px 12px;
        }

        .admin-matrix-table thead th {
            background: #d2dae6;
            color: #173b7a;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: .03em;
        }

        .admin-matrix-table thead .from-to-cell {
            background: #143f80;
            color: #fff;
        }

        .admin-matrix-table .zone-label-admin {
            background: #d2dae6;
            color: #173b7a;
            font-weight: 800;
        }

        .admin-matrix-table tbody tr:nth-child(even) td {
            background: #f9fbff;
        }

        .admin-matrix-table tbody tr:hover td {
            background: #f3f7ff;
        }

        .content-page.admin-matrix-embed,
        .content-page.admin-matrix-embed .content,
        .content-page.admin-matrix-embed .container-fluid,
        .content-page.admin-matrix-embed .price-list-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        .content-page.admin-matrix-embed .price-list-container.admin-matrix-flat {
            background: transparent;
        }

        .content-page.admin-matrix-embed .price-list-container.admin-matrix-flat .matrix-card {
            margin: 0 !important;
            border: 0 !important;
            border-top: 1px solid #d9e4f1 !important;
            border-radius: 0 0 18px 18px !important;
            box-shadow: none !important;
        }

        .content-page.admin-matrix-embed .price-list-container.admin-matrix-flat .admin-matrix-title-wrap {
            padding: 14px 18px 10px;
        }

        .content-page.admin-matrix-embed .price-list-container.admin-matrix-flat .admin-matrix-table-wrap {
            padding: 10px 18px 12px;
        }

        @media (max-width: 991px) {
            .admin-matrix-header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-matrix-actions-wrap {
                align-items: flex-start;
                margin-left: 0;
            }

            .admin-matrix-actions {
                flex-wrap: wrap;
            }

            .admin-matrix-rate-card-select {
                min-width: 190px;
            }

            .admin-matrix-save-status {
                text-align: left;
            }

            .admin-matrix-title-wrap h4 {
                font-size: 18px;
            }

            .admin-matrix-title-wrap p {
                font-size: 13px;
            }

            .admin-matrix-table {
                font-size: 13px;
            }

            .admin-matrix-table thead th {
                font-size: 13px;
            }
        }

        @media print {
            @page {
                size: auto;
                margin: 12mm;
            }

            html,
            body {
                background: #fff !important;
            }

            body * {
                visibility: hidden;
            }

            .price-list-container,
            .price-list-container * {
                visibility: visible;
            }

            .content-page,
            .content,
            .container-fluid,
            .price-list-container {
                margin: 0 !important;
                padding: 0 !important;
                min-height: auto !important;
                background: #fff !important;
            }

            .nav-tabs-custom,
            .filter-section,
            .company-logo-container,
            .btn-action-icon,
            .left-side-menu,
            .navbar-custom,
            .footer,
            .page-title-box,
            .right-bar,
            .topbar,
            .breadcrumb,
            .alert {
                display: none !important;
            }

            .price-list-container>.matrix-card,
            .price-list-container>.overhead-card {
                display: block !important;
                box-shadow: none !important;
                border: 1px solid #dbe3ef !important;
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 14px !important;
            }

            .matrix-table-container,
            .table-responsive {
                overflow: visible !important;
            }

            .matrix-table {
                font-size: 11px !important;
            }

            .matrix-table th,
            .matrix-table td,
            .overhead-table td {
                padding: 8px 10px !important;
            }

            .company-logo-sidebar {
                width: 180px !important;
                padding: 20px !important;
            }
        }
    </style>

    <div class="content-page {{ request()->boolean('embed') ? 'admin-matrix-embed' : '' }}">
        <div class="content">
            <div class="container-fluid">
                <div
                    class="price-list-container main-order-001 {{ request()->boolean('embed') ? 'admin-matrix-flat' : '' }}">
                    @php
                        $isEmbeddedView = request()->boolean('embed');
                        $isAdminMatrixView = $isEmbeddedView && request('type') === 'b2b';
                        $isEditableB2cView = $isEmbeddedView && request('type', 'b2c') === 'b2c' && !empty($rateCard) && !empty($currentCompanyId) && $user->isAdminLevel();
                        $activeZoneCount = $b2bZones->count();
                        $adminMatrixCanSave = $isAdminMatrixView && !empty($rateCard) && !empty($currentCompanyId) && $user->isAdminLevel();
                    @endphp

                    @php
                        $tabsCompanyId = $currentCompanyId ?: request('shipping_company_id');
                        $b2cTabUrl = route('rate.price-list', [
                            'type' => 'b2c',
                            'shipping_company_id' => $tabsCompanyId,
                            'rate_card_id' => request('rate_card_id', $currentRateCardId),
                            'weight' => request('weight'),
                            'embed' => request('embed'),
                        ]);
                        $b2bTabUrl = route('rate.price-list', [
                            'type' => 'b2b',
                            'shipping_company_id' => $tabsCompanyId,
                            'rate_card_id' => request('rate_card_id', $currentRateCardId),
                            'embed' => request('embed'),
                        ]);
                    @endphp

                    @if($isEmbeddedView)
                        <input type="hidden" id="company_filter" value="{{ $currentCompanyId }}">
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @php
                        if (!function_exists('getOtherChargeData')) {
                            function getOtherChargeData($rateCard, $shippingCompanyId, $masterId)
                            {
                                if (!$rateCard)
                                    return null;
                                $result = DB::table('rate_card_other_charges')
                                    ->where('rate_card_id', $rateCard->id)
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->where('other_charge_master_id', $masterId)
                                    ->first();
                                if ($result) {
                                    return json_decode($result->config, true);
                                }
                                return null;
                            }
                        }
                    @endphp

                    <!-- Filters -->
                    @if(!$isAdminMatrixView && !($isEmbeddedView && request('type', 'b2c') === 'b2c'))
                        <div class="main-disolay-felx order-filter">
                            <div class="filter-fields">
                                @if(!$isEmbeddedView)
                                    <div style="flex: 1; max-width: 300px;">
                                        <label class="form-label fw-bold">Select Service Provider</label>
                                        <select class="form-select select2" id="company_filter" onchange="updateFilters()">
                                            @foreach($shippingCompanies as $company)
                                                <option value="{{ $company->id }}" {{ $currentCompanyId == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if($user->isAdminLevel() || $isEmbeddedView)
                                    <div style="flex: 1; max-width: 300px;">
                                        <label class="form-label fw-bold">Select Rate Card</label>
                                        <select class="form-select select2" id="rate_card_filter" onchange="updateFilters()">
                                            @foreach($rateCards as $card)
                                                <option value="{{ $card->id }}" {{ $currentRateCardId == $card->id ? 'selected' : '' }}>
                                                    {{ $card->group_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if(request('type', 'b2c') == 'b2c')
                                    <div style="flex: 1; max-width: 220px;">
                                        <label class="form-label fw-bold">Weight</label>
                                        <select class="form-select select2" id="weight_filter" onchange="updateFilters()">
                                            @foreach($weights as $weight)
                                                <option value="{{ $weight }}" {{ (string) $currentWeight === (string) $weight ? 'selected' : '' }}>{{ $weight }} Kg</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            <div class="filter-actions">
                                <button class="btn-action-icon" title="Print" onclick="window.print()">
                                    <i class="mdi mdi-printer"></i>
                                </button>
                                <button class="btn-action-icon" title="Refresh" onclick="location.reload()">
                                    <i class="mdi mdi-refresh"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(request('type') == 'b2b')
                        <!-- B2B Matrix View -->
                        <div class="matrix-card {{ $isAdminMatrixView ? 'admin-matrix-card' : '' }}">
                            @if($isAdminMatrixView)
                                <div class="admin-matrix-title-wrap">
                                    <div class="admin-matrix-header-row">
                                        <div class="admin-matrix-title-content">
                                            <h4>Dynamic Admin Base Freight Matrix (&#8377;/kg)</h4>
                                            <p>This table dynamically generated a {{ $activeZoneCount }}x{{ $activeZoneCount }} grid
                                                based on the active zones defined in your Zone Matrix tab.</p>
                                        </div>
                                        <div class="admin-matrix-actions-wrap">
                                            <div class="admin-matrix-actions">
                                                <select class="admin-matrix-rate-card-select" id="rate_card_filter"
                                                    onchange="updateFilters()" title="Select Rate Card">
                                                    @forelse($rateCards as $card)
                                                        <option value="{{ $card->id }}" {{ (int) $currentRateCardId === (int) $card->id ? 'selected' : '' }}>{{ $card->group_name }}</option>
                                                    @empty
                                                        <option value="">No Rate Card Found</option>
                                                    @endforelse
                                                </select>
                                                @if($adminMatrixCanSave)
                                                    <button type="button" class="admin-matrix-icon-btn admin-matrix-icon-btn-secondary"
                                                        id="adminMatrixResetBtn" title="Reset Changes"
                                                        aria-label="Reset matrix changes">
                                                        <i class="mdi mdi-refresh"></i>
                                                    </button>
                                                    <button type="button" class="admin-matrix-icon-btn admin-matrix-icon-btn-primary"
                                                        id="adminMatrixSaveBtn" title="Save Matrix" aria-label="Save matrix">
                                                        <i class="mdi mdi-content-save-outline"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            @else
                                <div class="matrix-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0 fw-bold">
                                            {{ $shippingCompanies->where('id', $currentCompanyId)->first()->name ?? 'Courier' }}
                                        </h5>
                                        <span class="badge bg-success">FORWARD</span>
                                    </div>
                                    <div class="text-muted small">Per 1 kg</div>
                                </div>
                            @endif

                            <div class="matrix-table-container {{ $isAdminMatrixView ? 'admin-matrix-table-wrap' : '' }}">
                                <table class="matrix-table {{ $isAdminMatrixView ? 'admin-matrix-table' : '' }}">
                                    <thead>
                                        <tr>
                                            <th class="{{ $isAdminMatrixView ? 'from-to-cell' : '' }}">
                                                {{ $isAdminMatrixView ? 'From \\ To' : 'Zone' }}
                                            </th>
                                            @foreach($b2bZones as $toZone)
                                                <th>{{ $isAdminMatrixView ? ($toZone->zone_code ?: $toZone->heading) : $toZone->heading }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($b2bZones as $fromZone)
                                            <tr>
                                                <td class="zone-label {{ $isAdminMatrixView ? 'zone-label-admin' : '' }}">
                                                    {{ $isAdminMatrixView ? ($fromZone->zone_code ?: $fromZone->heading) : $fromZone->heading }}
                                                </td>
                                                @foreach($b2bZones as $toZone)
                                                    @php
                                                        $rate = $matrixData->get($fromZone->id)?->where('to_zone_id', $toZone->id)->first();
                                                        $rateValue = $rate ? number_format((float) $rate->rate_per_kg, 2, '.', '') : '';
                                                    @endphp
                                                    <td class="{{ $isAdminMatrixView ? 'rate-cell' : '' }}">
                                                        @if($adminMatrixCanSave)
                                                            <input type="number" min="0" step="0.01" class="admin-matrix-input"
                                                                data-from-zone="{{ $fromZone->id }}" data-to-zone="{{ $toZone->id }}"
                                                                value="{{ $rateValue }}" placeholder="-">
                                                        @else
                                                            {{ $rate ? number_format($rate->rate_per_kg, 2, '.', '') : '-' }}
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <!-- B2C Rate Card List -->
                        <div class="matrix-card {{ $isEmbeddedView ? 'admin-matrix-card' : '' }}">
                            @if($isEmbeddedView)
                                <div class="admin-matrix-title-wrap">
                                    <div class="admin-matrix-header-row">
                                        <div class="admin-matrix-title-content">
                                            <h4>B2C Rate Card Matrix</h4>
                                            <p>Update B2C rates and save them for the selected rate card.</p>
                                        </div>
                                        <div class="admin-matrix-actions-wrap">
                                            <div class="admin-matrix-actions">
                                                <select class="admin-matrix-rate-card-select" id="rate_card_filter"
                                                    onchange="updateFilters()" title="Select Rate Card">
                                                    @forelse($rateCards as $card)
                                                        <option value="{{ $card->id }}" {{ (int) $currentRateCardId === (int) $card->id ? 'selected' : '' }}>{{ $card->group_name }}</option>
                                                    @empty
                                                        <option value="">No Rate Card Found</option>
                                                    @endforelse
                                                </select>
                                                @if($isEditableB2cView)
                                                    <button type="button" class="admin-matrix-icon-btn admin-matrix-icon-btn-secondary"
                                                        id="adminB2cResetBtn" title="Reset Changes" aria-label="Reset B2C changes">
                                                        <i class="mdi mdi-refresh"></i>
                                                    </button>
                                                    <button type="button" class="admin-matrix-icon-btn admin-matrix-icon-btn-primary"
                                                        id="adminB2cSaveBtn" title="Save B2C Rates" aria-label="Save B2C rates">
                                                        <i class="mdi mdi-content-save-outline"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            @endif
                            <div class="p-3">
                                @if($isEmbeddedView)
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle admin-b2c-matrix-table" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Weight (Kg)</th>
                                                    @foreach($b2cZones as $zone)
                                                        <th>{{ $zone->heading }}</th>
                                                    @endforeach
                                                    <th>COD Charges / Extra</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($b2cMatrixRows as $matrixRow)
                                                    <tr>
                                                        <td>
                                                            <input type="number" readonly class="form-control form-control-sm"
                                                                value="{{ $matrixRow->weight }}">
                                                        </td>
                                                        @foreach($matrixRow->zones as $zoneRate)
                                                            <td>
                                                                <div class="admin-b2c-cell-pair">
                                                                    @if($isEditableB2cView)
                                                                        <input type="number" min="0" step="0.01" class="admin-b2c-input"
                                                                            data-weight="{{ $matrixRow->weight }}"
                                                                            data-zone-setting-id="{{ $zoneRate->zone_setting_id }}"
                                                                            data-field="rate"
                                                                            value="{{ number_format((float) $zoneRate->rate, 2, '.', '') }}">
                                                                        <input type="number" min="0" step="0.01" class="admin-b2c-input"
                                                                            data-weight="{{ $matrixRow->weight }}"
                                                                            data-zone-setting-id="{{ $zoneRate->zone_setting_id }}"
                                                                            data-field="extra_rate"
                                                                            value="{{ number_format((float) $zoneRate->extra_rate, 2, '.', '') }}">
                                                                    @else
                                                                        <span>{{ number_format((float) $zoneRate->rate, 2, '.', '') }}</span>
                                                                        <span>{{ number_format((float) $zoneRate->extra_rate, 2, '.', '') }}</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                        <td>
                                                            <div class="admin-b2c-cell-pair">
                                                                @if($isEditableB2cView)
                                                                    <input type="number" min="0" step="0.01" class="admin-b2c-input"
                                                                        data-weight="{{ $matrixRow->weight }}" data-zone-setting-id="cod"
                                                                        data-field="cod_charges"
                                                                        value="{{ number_format((float) $matrixRow->cod_charges, 2, '.', '') }}">
                                                                    <input type="number" min="0" step="0.01" class="admin-b2c-input"
                                                                        data-weight="{{ $matrixRow->weight }}" data-zone-setting-id="cod"
                                                                        data-field="cod_percentage"
                                                                        value="{{ number_format((float) $matrixRow->cod_percentage, 2, '.', '') }}">
                                                                @else
                                                                    <span>{{ number_format((float) $matrixRow->cod_charges, 2, '.', '') }}</span>
                                                                    <span>{{ number_format((float) $matrixRow->cod_percentage, 2, '.', '') }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $b2cZones->count() + 2 }}" class="text-center">No B2C rates
                                                            found for the selected filter.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table id="b2c_ratecard_table" class="table" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Sr.No</th>
                                                    <th>Shipping Company</th>
                                                    <th>Weight</th>
                                                    <th>Heading</th>
                                                    <th>Rate</th>
                                                    <th>Extra Rate</th>
                                                    <th>Cod Charges</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($b2cRows as $index => $row)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $row->shipping_company_name ?? 'N/A' }}</td>
                                                        <td>{{ $row->weight }}</td>
                                                        <td>{{ $row->heading ?? 'N/A' }}</td>
                                                        <td>{{ $row->rate }}</td>
                                                        <td>{{ $row->extra_rate }}</td>
                                                        <td>{{ $row->cod_charges }}</td>
                                                        <td>{{ $row->cod_percentage }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center">No B2C rates found for the selected filter.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Other Charges -->
                    @php
                        $activeCharges = [];
                        if (isset($otherChargeMasters)) {
                            foreach ($otherChargeMasters as $master) {
                                $config = getOtherChargeData($rateCard, $currentCompanyId, $master->id);
                                if ($config) {
                                    $activeCharges[] = ['master' => $master, 'config' => $config];
                                }
                            }
                        }
                    @endphp

                    @if(!empty($activeCharges) && !$isAdminMatrixView)
                        <div class="overhead-card">
                            <div class="overhead-header">{{ strtoupper(request('type', 'b2c')) }} Other Charges</div>
                            <div class="overhead-body">
                                <div class="flex-grow-1">
                                    <table class="overhead-table">
                                        <tbody>
                                            @foreach($activeCharges as $item)
                                                @php
                                                    $config = $item['config'];
                                                    $master = $item['master'];
                                                    $desc = '';

                                                    switch ($master->calc_type) {
                                                        case 'per_kg_or_min':
                                                            $desc = "₹" . ($config['per_kg'] ?? 0) . " Per Kg OR ₹" . ($config['min'] ?? 0) . " Which is Higher";
                                                            break;
                                                        case 'percent_or_min':
                                                            $desc = ($config['percent'] ?? 0) . "% OR ₹" . ($config['min'] ?? 0) . " Which is Higher";
                                                            break;
                                                        case 'per_kg_or_flat':
                                                            $desc = "₹" . ($config['per_kg'] ?? 0) . " Per Kg OR ₹" . ($config['flat'] ?? 0) . " Which is Higher";
                                                            break;
                                                        case 'flat':
                                                            $desc = "₹" . ($config['flat'] ?? 0);
                                                            break;
                                                        case 'divisor':
                                                            $desc = "(Length*Width*Height)/" . ($config['divisor'] ?? 5000);
                                                            break;
                                                        case 'min_charge':
                                                            $desc = "₹" . ($config['min'] ?? 0);
                                                            break;
                                                        case 'gst_percent':
                                                            $desc = ($config['percent'] ?? 0) . "% (As per Govt. rule and regulations)";
                                                            break;
                                                        case 'handling_tier':
                                                            $desc = "Weight Tier: " . ($config['threshold'] ?? 0) . " Kg (Upto: ₹" . ($config['rate_upto'] ?? 0) . ", Above: ₹" . ($config['rate_above'] ?? 0) . ")";
                                                            break;
                                                        case 'max_single_box_weight':
                                                            $desc = ($config['max_single_box_weight'] ?? 0) . " Kg Max Weight per Box";
                                                            break;
                                                        default:
                                                            $parts = [];
                                                            foreach ($config as $k => $v)
                                                                if ($v !== '')
                                                                    $parts[] = ucfirst($k) . ": " . $v;
                                                            $desc = implode(', ', $parts);
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $master->name }}</td>
                                                    <td>{{ $desc }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="company-logo-sidebar">
                                    @php
                                        $currentComp = $shippingCompanies->where('id', $currentCompanyId)->first();
                                    @endphp
                                    @if($currentComp && !empty($currentComp->logo))
                                        <img src="{{ asset('storage/' . $currentComp->logo) }}" alt="{{ $currentComp->name }}"
                                            class="img-fluid" style="max-height: 100px;">
                                    @else
                                        <h3 class="text-muted fw-bold">{{ $currentComp->name ?? 'Courier' }}</h3>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif


                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            if (typeof window.jQuery === 'undefined') {
                document.write('<script src="{{ asset('assets/js/vendor.min.js') }}"><\/script>');
            }
            if (typeof window.jQuery === 'undefined') {
                document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
            }
        </script>
        <script src="{{ asset('assets/libs/datatables/jquery.dataTables.min.js') }}"
            onerror="this.onerror=null;this.src='https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js';"></script>
        <script>
            function updateFilters() {
                const companyElement = document.getElementById('company_filter');
                const companyId = companyElement ? companyElement.value : '{{ (int) ($currentCompanyId ?? 0) }}';
                const rateCardElement = document.getElementById('rate_card_filter');
                const rateCardId = rateCardElement ? rateCardElement.value : '{{ (int) ($currentRateCardId ?? 0) }}';
                const type = '{{ request('type', 'b2c') }}';
                const weight = document.getElementById('weight_filter') ? document.getElementById('weight_filter').value : '';
                const embed = '{{ request('embed') }}';

                if (!companyId) {
                    return;
                }

                let url = `{{ route('rate.price-list') }}?type=${type}&shipping_company_id=${companyId}`;
                if (rateCardId) {
                    url += `&rate_card_id=${rateCardId}`;
                }
                if (weight) {
                    url += `&weight=${weight}`;
                }
                if (embed) {
                    url += `&embed=${embed}`;
                }
                window.location.href = url;
            }

            $(document).ready(function () {
                const isEditableB2cView = @json($isEditableB2cView);
                if ('{{ request('type', 'b2c') }}' === 'b2c' && !isEditableB2cView && $.fn && $.fn.DataTable) {
                    $('#b2c_ratecard_table').DataTable({
                        bLengthChange: true,
                        searching: true,
                        bFilter: true,
                        bInfo: true,
                        iDisplayLength: 25,
                        order: [
                            [0, 'asc']
                        ],
                        bAutoWidth: false
                    });
                }

                const isAdminMatrixView = @json($isAdminMatrixView);
                const adminMatrixCanSave = @json($adminMatrixCanSave);
                const b2cSaveUrl = @json(route('rate.price-list.b2c-save'));
                const matrixSaveUrl = @json(route('rate.price-list.matrix-save'));
                const defaultRateCardId = @json($rateCard->id ?? null);
                const defaultShippingCompanyId = @json($currentCompanyId ?? null);
                const csrfToken = @json(csrf_token());
                const defaultWeight = @json($currentWeight ?? null);

                function getCurrentRateCardId() {
                    const selected = $('#rate_card_filter').val();
                    const id = selected !== undefined && selected !== null && String(selected).trim() !== ''
                        ? Number(selected)
                        : Number(defaultRateCardId);
                    return Number.isFinite(id) && id > 0 ? id : null;
                }

                function getCurrentCompanyId() {
                    const selected = $('#company_filter').val();
                    const id = selected !== undefined && selected !== null && String(selected).trim() !== ''
                        ? Number(selected)
                        : Number(defaultShippingCompanyId);
                    return Number.isFinite(id) && id > 0 ? id : null;
                }

                function getCurrentWeight() {
                    const selected = $('#weight_filter').val();
                    if (selected !== undefined && selected !== null && String(selected).trim() !== '') {
                        return String(selected);
                    }
                    if (defaultWeight === null || defaultWeight === undefined) {
                        return '';
                    }
                    return String(defaultWeight);
                }

                function setStatus($element, message, type) {
                    if (!$element || !$element.length) {
                        return;
                    }

                    $element
                        .removeClass('is-success is-error')
                        .text(message || '');

                    if (type === 'success' || type === 'error') {
                        $element.addClass(type === 'success' ? 'is-success' : 'is-error');
                    }
                }

                function showToast(type, message) {
                    if (typeof window.toastrMsg === 'function' && message) {
                        window.toastrMsg(type, message);
                    }
                }

                if (isAdminMatrixView && adminMatrixCanSave) {
                    const $inputs = $('.admin-matrix-input');
                    const $saveBtn = $('#adminMatrixSaveBtn');
                    const $resetBtn = $('#adminMatrixResetBtn');
                    const $status = $('#adminMatrixSaveStatus');
                    const original = {};

                    function norm(value) {
                        const raw = String(value ?? '').trim();
                        if (!raw) return '';
                        const parsed = Number(raw);
                        if (!Number.isFinite(parsed) || parsed < 0) return '';
                        return parsed.toFixed(2);
                    }

                    function key(fromZoneId, toZoneId) {
                        return `${fromZoneId}:${toZoneId}`;
                    }

                    function hasChanges() {
                        let dirty = false;
                        $inputs.each(function () {
                            const fromZoneId = $(this).data('from-zone');
                            const toZoneId = $(this).data('to-zone');
                            const k = key(fromZoneId, toZoneId);
                            if (norm($(this).val()) !== (original[k] ?? '')) {
                                dirty = true;
                                return false;
                            }
                        });
                        return dirty;
                    }

                    function refreshState() {
                        const dirty = hasChanges();
                        $saveBtn.prop('disabled', !dirty);
                        $resetBtn.prop('disabled', !dirty);
                        if (dirty) {
                            setStatus($status, '');
                        }
                    }

                    $inputs.each(function () {
                        const fromZoneId = $(this).data('from-zone');
                        const toZoneId = $(this).data('to-zone');
                        original[key(fromZoneId, toZoneId)] = norm($(this).val());
                    });

                    $inputs.on('input change', function () {
                        refreshState();
                    });

                    $resetBtn.on('click', function () {
                        $inputs.each(function () {
                            const fromZoneId = $(this).data('from-zone');
                            const toZoneId = $(this).data('to-zone');
                            const k = key(fromZoneId, toZoneId);
                            $(this).val(original[k] ?? '');
                        });
                        setStatus($status, 'Changes reset.');
                        refreshState();
                    });

                    $saveBtn.on('click', function () {
                        const rateCardId = getCurrentRateCardId();
                        const shippingCompanyId = getCurrentCompanyId();
                        if (!rateCardId || !shippingCompanyId) {
                            const message = 'Rate card or shipping company is missing.';
                            setStatus($status, message, 'error');
                            showToast('error', message);
                            return;
                        }

                        const matrixPayload = {};
                        const payload = {
                            rate_card_id: rateCardId,
                            shipping_company_id: shippingCompanyId,
                            matrix: matrixPayload
                        };

                        $inputs.each(function () {
                            const fromZoneId = $(this).data('from-zone');
                            const toZoneId = $(this).data('to-zone');
                            const value = norm($(this).val());
                            if (!matrixPayload[fromZoneId]) {
                                matrixPayload[fromZoneId] = {};
                            }
                            matrixPayload[fromZoneId][toZoneId] = value;
                        });

                        $saveBtn.prop('disabled', true).addClass('is-loading');
                        $resetBtn.prop('disabled', true);
                        setStatus($status, 'Saving matrix...');

                        $.ajax({
                            url: matrixSaveUrl,
                            type: 'POST',
                            data: JSON.stringify(payload),
                            contentType: 'application/json; charset=utf-8',
                            processData: false,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            success: function (response) {
                                $inputs.each(function () {
                                    const fromZoneId = $(this).data('from-zone');
                                    const toZoneId = $(this).data('to-zone');
                                    const k = key(fromZoneId, toZoneId);
                                    original[k] = norm($(this).val());
                                });
                                const message = (response && response.msg) ? response.msg : 'Matrix saved.';
                                setStatus($status, message, 'success');
                                showToast('success', message);
                            },
                            error: function (xhr) {
                                const message = (xhr && xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed to save matrix.';
                                setStatus($status, message, 'error');
                                showToast('error', message);
                            },
                            complete: function () {
                                $saveBtn.removeClass('is-loading');
                                refreshState();
                            }
                        });
                    });

                    refreshState();
                }

                if (isEditableB2cView) {
                    const $inputs = $('.admin-b2c-input');
                    const $saveBtn = $('#adminB2cSaveBtn');
                    const $resetBtn = $('#adminB2cResetBtn');
                    const $status = $('#adminB2cSaveStatus');
                    const original = {};

                    function norm(value) {
                        const raw = String(value ?? '').trim();
                        if (!raw) return '0.00';
                        const parsed = Number(raw);
                        if (!Number.isFinite(parsed) || parsed < 0) return '0.00';
                        return parsed.toFixed(2);
                    }

                    function key(weight, zoneSettingId, field) {
                        return `${weight}:${zoneSettingId}:${field}`;
                    }

                    function hasChanges() {
                        let dirty = false;
                        $inputs.each(function () {
                            const weight = String($(this).data('weight'));
                            const zoneSettingId = String($(this).data('zone-setting-id'));
                            const field = $(this).data('field');
                            const k = key(weight, zoneSettingId, field);
                            if (norm($(this).val()) !== (original[k] ?? '0.00')) {
                                dirty = true;
                                return false;
                            }
                        });
                        return dirty;
                    }

                    function refreshState() {
                        const dirty = hasChanges();
                        $saveBtn.prop('disabled', !dirty);
                        $resetBtn.prop('disabled', !dirty);
                        if (dirty) {
                            setStatus($status, '');
                        }
                    }

                    $inputs.each(function () {
                        const weight = String($(this).data('weight'));
                        const zoneSettingId = String($(this).data('zone-setting-id'));
                        const field = $(this).data('field');
                        original[key(weight, zoneSettingId, field)] = norm($(this).val());
                    });

                    $inputs.on('input change', function () {
                        refreshState();
                    });

                    $resetBtn.on('click', function () {
                        $inputs.each(function () {
                            const weight = String($(this).data('weight'));
                            const zoneSettingId = String($(this).data('zone-setting-id'));
                            const field = $(this).data('field');
                            $(this).val(original[key(weight, zoneSettingId, field)] ?? '0.00');
                        });
                        setStatus($status, 'Changes reset.');
                        refreshState();
                    });

                    $saveBtn.on('click', function () {
                        const rateCardId = getCurrentRateCardId();
                        const shippingCompanyId = getCurrentCompanyId();

                        if (!rateCardId || !shippingCompanyId) {
                            const message = 'Rate card or shipping company is missing.';
                            setStatus($status, message, 'error');
                            showToast('error', message);
                            return;
                        }

                        const rows = {};
                        $inputs.each(function () {
                            const weight = String($(this).data('weight'));
                            const zoneSettingId = String($(this).data('zone-setting-id'));
                            const field = String($(this).data('field'));
                            if (!rows[weight]) {
                                rows[weight] = {
                                    zones: {}
                                };
                            }

                            if (zoneSettingId === 'cod') {
                                rows[weight][field] = norm($(this).val());
                            } else {
                                if (!rows[weight].zones[zoneSettingId]) {
                                    rows[weight].zones[zoneSettingId] = {};
                                }
                                rows[weight].zones[zoneSettingId][field] = norm($(this).val());
                            }
                        });

                        const payload = {
                            rate_card_id: rateCardId,
                            shipping_company_id: shippingCompanyId,
                            rows: rows
                        };

                        $saveBtn.prop('disabled', true).addClass('is-loading');
                        $resetBtn.prop('disabled', true);
                        setStatus($status, 'Saving B2C rates...');

                        $.ajax({
                            url: b2cSaveUrl,
                            type: 'POST',
                            data: JSON.stringify(payload),
                            contentType: 'application/json; charset=utf-8',
                            processData: false,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            success: function (response) {
                                $inputs.each(function () {
                                    const weight = String($(this).data('weight'));
                                    const zoneSettingId = String($(this).data('zone-setting-id'));
                                    const field = $(this).data('field');
                                    original[key(weight, zoneSettingId, field)] = norm($(this).val());
                                });
                                const message = (response && response.msg) ? response.msg : 'B2C rates saved.';
                                setStatus($status, message, 'success');
                                showToast('success', message);
                            },
                            error: function (xhr) {
                                const message = (xhr && xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed to save B2C rates.';
                                setStatus($status, message, 'error');
                                showToast('error', message);
                            },
                            complete: function () {
                                $saveBtn.removeClass('is-loading');
                                refreshState();
                            }
                        });
                    });

                    refreshState();
                }
            });
        </script>
    @endpush
@endsection