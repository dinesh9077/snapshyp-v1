@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipping Rate Calculator')
@section('header_title', 'Shipping Rate Calculator')

@push('css')
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6123;
            --secondary-blue: #3984FB;
            --bg-light: #f5f8fd;
            --border-color: #d9e3f0;
            --panel-border: #d7e2ee;
            --text-muted: #7b8aa3;
            --text-dark: #3984FB;
            --soft-tab: #eef3f9;
            --soft-pill: #e8edf5;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), rgba(245, 249, 253, 0.95)),
                linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
            font-family: 'Outfit', 'Inter', sans-serif;
        }

        body.embedded-layout-body .content-page,
        body.embedded-layout-body .container-fluid,
        body.embedded-layout-body .calc-right-content {
            min-height: auto !important;
            height: auto !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        body.embedded-layout-body .shipping_cost {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .calculator-card {
            background: #ffffff;
            padding: 18px 18px 20px;
            border-radius: 16px;
            border: 1.5px solid var(--panel-border);
            box-shadow: 0 18px 50px rgba(26, 57, 105, 0.06);
            max-width: 100%;
        }

        .calculator-shell {
            display: block;
        }

        .calculator-shell.single-company-mode {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 0.92fr);
            gap: 18px;
            align-items: start;
        }

        .shipment-details-heading {
            font-size: 33px;
            color: #102847;
            font-weight: 800;
            margin: 0 0 12px;
        }

        .payment-type-div {
            background: #f8fafc80;
            margin: 0px;
            height : 149px;
            border-radius: 18px;
            border: 1.5px solid var(--border-color);
        }

        .calc-tabs {
            display: flex;
            background: var(--soft-tab);
            padding: 8px;
            border-radius: 10px;
            gap: 8px;
            margin: 0 0 22px;
            border: 1px solid #e3ebf4;
        }

        .calc-tab {
            flex: 1;
            min-height: 45px;
            padding: 8px 16px;
            text-align: center;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            transition: all 0.2s ease;
            color: #6f7f98;
            border: none;
            background: transparent;
        }

        .calc-tab.active {
            background: #ffffff;
            color: var(--secondary-blue);
            box-shadow: 0 2px 10px rgba(29, 78, 216, 0.08);
        }

        .input-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .input-group-custom {
            position: relative;
            min-width: 0;
        }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .input-control {
            width: 100%;
            background: #f8fafc80;
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            height: 46px;
            padding: 10px 16px;
            font-size: 16px;
            font-weight: 600;
            color: #102847;
            transition: all 0.2s;
            outline: none;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.7);
        }

        .input-control:focus {
            background: #ffffff;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 4px rgba(255, 97, 35, 0.12);
        }

        .location-info {
            font-size: 12px;
            color: #2c56a1;
            font-weight: 600;
            margin-top: 7px;
            display: flex;
            align-items: center;
            gap: 4px;
            min-height: 18px;
        }

        .weight-container {
            position: relative;
        }

        .weight-container .input-control {
            padding-right: 54px;
        }

        .unit-label {
            position: absolute;
            right: 16px;
            top: 47px;
            transform: translateY(-50%);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 16px;
            pointer-events: none;
        }

        .invoice-group .options-toggle-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }

        .btn-link-custom {
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 0;
            background: transparent;
            border-radius: 0;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-link-custom:hover {
            opacity: 0.85;
            color: var(--secondary-blue);
        }

        .options-section {
            margin-top: 8px;
        }

        .dimension-card {
            background: #f8fafc80;
            border-radius: 18px;
            padding: 16px;
            border: 1.5px solid var(--border-color);
            margin-bottom: 16px;
        }

        .dim-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .dim-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            color: black;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .dim-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dim-title i {
            font-size: 18px;
        }

        .dim-inputs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) 36px;
            gap: 12px 10px;
            align-items: end;
        }

        .calc-card-b2b .dim-inputs {
            grid-template-columns: repeat(4, minmax(0, 1fr)) 36px;
        }

        .dim-inputs .input-group-custom {
            min-width: 0;
        }

        .dim-inputs .input-label {
            margin-bottom: 6px;
            font-size: 11px;
        }

        .dim-control {
            background: #ffffff;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            height: 40px;
            padding: 12px 12px;
            width: 100%;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            color: #98a4b5;
            outline: none;
        }

        .dim-control:focus {
            border-color: var(--primary-orange);
        }

        .dim-delete-action {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            height: 42px;
        }

        .dim-delete-action .btn {
            min-width: auto;
            padding: 0;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        .btn-add-box-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0;
            border: none;
            background: transparent;
            color: var(--secondary-blue);
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-add-box-inline:hover {
            color: var(--secondary-blue);
            opacity: 0.85;
        }

        .toggle-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            background: #f8fafc80;
            border: 1.5px solid var(--border-color);
            border-radius: 18px;
        }

        .toggle-field {
                padding: 10px;
    padding-bottom: 0px;
    padding-left: 6px;
        }

        .toggle-group {
            display: flex;
            align-items: center;
            background: var(--soft-pill);
            padding: 4px;
            border-radius: 11px;
            gap: 4px;
        }

        .toggle-btn {
            flex: 1;
            min-height: 34px;
            padding: 8px 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: #7b879a;
        }

        .toggle-btn.active {
            background: #ffffff;
            color: black;
            box-shadow: 0 1px 2px rgba(14, 58, 106, 0.08);
        }

        .toggle-group {
            display: flex;
            align-items: center;
            background: var(--soft-pill);
            padding: 4px;
            border-radius: 11px;
            gap: 4px;
        }

        .cm-in-ft-group {
            border-radius: 6px !important;
        }

        .cm-in-ft-btn {
            min-height: 16px !important;
            padding: 0px 10px !important;
            border-radius: 5px !important;
            font-size: 9px !important;
            font-weight: 600 !important;
        }

        .cm-in-ft-btn.active {
            background: #3984FB !important;
            color: white !important;
        }

        .action-row {
            display: grid;
            grid-template-columns: minmax(160px, 500px) minmax(260px, 1fr);
            align-items: center;
            gap: 14px;
            margin-top: 16px;
        }

        .btn-calculate-premium {
            width: 100%;
            min-height: 50px;
            background: #3984FB;
            color: #ffffff;
            border: none;
            border-radius: 16px;
            padding: 8px 28px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            white-space: nowrap;
        }

        .btn-calculate-premium:hover {
            transform: translateY(-2px);
        }

        .btn-reset-premium {
            width: 100%;
            min-height: 50px;
            background: #ffffff;
            color: var(--secondary-blue);
            border: 1.5px solid var(--border-color);
            border-radius: 16px;
            padding: 8px 18px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        @media (max-width: 1400px) {
            .calculator-card {
                padding: 16px;
            }
        }

        @media (max-width: 1200px) {
            .calculator-shell.single-company-mode {
                grid-template-columns: 1fr;
            }

            .input-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .action-row {
                grid-template-columns: 220px minmax(0, 1fr);
            }

            .calc-card-b2b .dim-inputs {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dim-inputs {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991px) {
            .toggle-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .action-row {
                grid-template-columns: minmax(130px, 170px) minmax(0, 1fr);
            }
        }

        @media (max-width: 768px) {
            .calculator-card {
                border-radius: 24px;
                padding: 14px;
            }

            .calc-tabs {
                padding: 5px;
                gap: 6px;
                margin-bottom: 16px;
            }

            .calc-tab {
                min-height: 32px;
                font-size: 12px;
                padding: 10px 8px;
            }

            .input-row,
            .toggle-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .input-label {
                font-size: 10px;
                margin-bottom: 5px;
            }

            .input-control {
                height: 42px;
                font-size: 14px;
                padding: 8px 12px;
                border-radius: 8px;
            }

            .location-info {
                font-size: 10px;
                margin-top: 2px;
            }

            .unit-label {
                top: 41px;
                right: 13px;
                font-size: 14px;
            }

            .invoice-group .options-toggle-row {
                margin-top: 6px;
            }

            .btn-link-custom {
                font-size: 11px;
            }

            .dimension-card,
            .toggle-field {
                border-radius: 16px;
                padding: 12px;
            }

            .dim-header {
                align-items: center;
            }



            .dim-inputs {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px 8px;
            }

            .calc-card-b2b .dim-inputs {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .dim-inputs .input-label {
                font-size: 9px;
            }

            .dim-control {
                height: 38px;
                padding: 8px 6px;
                font-size: 13px;
            }

            .dim-delete-action {
                align-items: center;
                height: 38px;
            }

            .action-row {
                grid-template-columns: 1fr 1.65fr;
                gap: 10px;
            }

            .btn-reset-premium,
            .btn-calculate-premium {
                min-height: 52px;
                font-size: 14px;
            }

            .toggle-group {
                padding: 2px;
                border-radius: 6px;
                font-size: 9px;
            }

            .toggle-btn {
                padding: 0px 0px;
                min-height: 25px;
                font-size: 9px;
                border-radius: 5px;
            }

            .toggle-field {
                padding-left: 4px !important;
                padding-right: 0px;
            }
        }

        @media (max-width: 480px) {
            .dim-header-actions {
                flex-wrap: wrap;
                gap: 5px;
            }


            .calculator-card {
                padding: 12px;
            }

            .input-row,
            .toggle-grid {
                gap: 10px;
            }

            .calc-tab {
                font-size: 10px;
            }

            .input-label {
                font-size: 10px;
            }

            .location-info {
                font-size: 10px;
            }

            .dim-inputs {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px 6px;
            }

            .calc-card-b2b .dim-inputs {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .dim-control {
                font-size: 12px;
            }

            .dim-inputs .input-group-custom.b2b-only-ui .dim-control {
                padding-left: 4px;
                padding-right: 4px;
            }

            .dim-delete-action {
                grid-column: auto;
                justify-content: center;
            }

            .action-row {
                grid-template-columns: 1fr 1.75fr;
            }

            .btn-reset-premium,
            .btn-calculate-premium {
                font-size: 13px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content-page">
        <div class="container-fluid">

            <!-- Right Side: Card & Results -->
            <div class="calc-right-content">
                @if ($user->kyc_status != 1 && $user->role === 'user')
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 text-center"
                        style="border-radius: 20px !important;">
                        <i class="mdi mdi-alert-circle-outline mdi-48px d-block mb-2"></i>
                        <h4 class="fw-bold">KYC Verification Required</h4>
                        <p class="mb-0">Your KYC verification is incomplete. Please complete it to unlock the Rate Calculator.
                        </p>
                        <a href="{{ route('users.kyc.edit') }}" class="btn btn-danger mt-3 px-4 rounded-pill">Complete KYC
                            Now</a>
                    </div>
                @else
                    @php $isSingleCompanyMode = !empty($selectedShippingCompany); @endphp
                    <div class="calculator-shell {{ $isSingleCompanyMode ? 'single-company-mode' : '' }}">
                        <div class="calculator-card">
                            @if($isSingleCompanyMode)
                                <h3 class="shipment-details-heading">Shipment Details</h3>
                            @endif
                            <form id="rateForm" method="post" action="{{ route('rate.calculator.create') }}">
                                @csrf
                                @if(!empty($selectedShippingCompany))
                                    <input type="hidden" name="company_id" value="{{ $selectedShippingCompany->id }}">
                                @endif

                                <!-- Tabs -->
                                @if(!$isSingleCompanyMode)
                                    <div class="calc-tabs">
                                        <button type="button" class="calc-tab active" data-type="1">Courier (Single Box &lt;20Kg)
                                        </button>
                                        {{-- <button type="button" class="calc-tab" data-type="2">B2B Cargo (Multi-Box &gt;20Kg)</button> --}}
                                        <input type="hidden" name="type_of_package" id="type_of_package" value="1">
                                    </div>
                                @else
                                    <input type="hidden" name="type_of_package" id="type_of_package" value="1">
                                @endif

                                <!-- Main Fields -->
                                <div class="input-row">
                                    <div class="input-group-custom">
                                        <label class="input-label">Origin PIN</label>
                                        <input type="text" name="pickup_code" id="pickup_code" class="input-control"
                                            value="400001">
                                        <div class="location-info">
                                            <i class="mdi mdi-map-marker-outline"></i> 
                                            <span id="pickup_city_label">Mumbai, Maharashtra</span>
                                            <span id="pickup_service_status" style="margin-left:8px;"></span>
                                        </div>
                                    </div>
                                    <div class="input-group-custom">
                                        <label class="input-label">Dest PIN</label>
                                        <input type="text" name="delivery_code" id="delivery_code" class="input-control"
                                            value="110001">
                                        <div class="location-info">
                                            <i class="mdi mdi-map-marker-outline"></i> 
                                            <span id="delivery_city_label">New Delhi, Delhi</span>
                                            <span id="delivery_service_status" style="margin-left:8px;"></span>
                                        </div>
                                    </div>

                                    <div class="input-group-custom weight-container">
                                        <label class="input-label">Weight</label>
                                        <input type="text" name="weight" id="weight" class="input-control" value="2">
                                        <span class="unit-label">Kg</span>
                                    </div>
                                    <div class="input-group-custom invoice-group">
                                        <label class="input-label">Invoice Val</label>
                                        <input type="text" name="invoice_amount" id="invoice_amount" class="input-control"
                                            value="1500">
                                        @if(!$isSingleCompanyMode)
                                            <div class="options-toggle-row">
                                                <a href="javascript:void(0)" class="btn-link-custom" id="toggleOptionsBtn">
                                                    <i class="mdi mdi-plus-circle-outline"></i> Add Dimensions &amp; Options
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Options Section (Expandable) -->
                                <div class="row options-section" id="optionsSection"
                                    style="{{ $isSingleCompanyMode ? '' : 'display: none;' }}">

                                    <div class="col-lg-8">
                                         <div class="dimension-card">
                                        <div class="dim-header">
                                            <div class="dim-header-actions">
                                                <div class="dim-title">
                                                    <i class="mdi mdi-cube-outline" style="color: var(--secondary-blue);"></i>
                                                    Dimensions
                                                </div>
                                                <div class="toggle-group cm-in-ft-group" data-input="dimension_unit">
                                                    <button type="button" class="toggle-btn cm-in-ft-btn active"
                                                        data-value="CM">CM</button>
                                                    <button type="button" class="toggle-btn cm-in-ft-btn"
                                                        data-value="IN">IN</button>
                                                    <button type="button" class="toggle-btn cm-in-ft-btn"
                                                        data-value="FT">FT</button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-add-box-inline b2b-only-ui" style="display: none;"
                                                id="addBoxBtn">
                                                <i class="mdi mdi-plus-circle-outline"></i> Add Box
                                            </button>
                                            <input type="hidden" name="dimension_unit" id="dimension_unit" value="CM">
                                        </div>
                                        <div id="dim_rows_container">
                                            <div class="dim-inputs mb-2 base-dim-row">
                                                <div class="input-group-custom">
                                                    <label class="input-label">Length</label>
                                                    <input type="text" name="length[]" class="dim-control dim-val"
                                                        placeholder="0.0">
                                                </div>
                                                <div class="input-group-custom">
                                                    <label class="input-label">Width</label>
                                                    <input type="text" name="width[]" class="dim-control dim-val"
                                                        placeholder="0.0">
                                                </div>
                                                <div class="input-group-custom">
                                                    <label class="input-label">Height</label>
                                                    <input type="text" name="height[]" class="dim-control dim-val"
                                                        placeholder="0.0">
                                                </div>
                                                <div class="input-group-custom b2b-only-ui" style="display: none;">
                                                    <label class="input-label">QTY</label>
                                                    <input type="number" name="box[]" class="dim-control" value="1">
                                                </div>
                                                <!-- <div class="dim-delete-action"></div> -->
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-lg-4">
                                         <div class="row payment-type-div">
                                        <div class="col-6">
                                            <div class="input-group-custom toggle-field">
                                                <label class="input-label">Payment Type</label>
                                                <div class="toggle-group" data-input="payment_type">
                                                    <button type="button" class="toggle-btn active"
                                                        data-value="prepaid">Prepaid</button>
                                                    <button type="button" class="toggle-btn" data-value="cod">COD</button>
                                                </div>
                                                <input type="hidden" name="payment_type" id="payment_type" value="prepaid">
                                            </div>
                                        </div>
                                        {{-- <div class="col-6">
                                            <div class="input-group-custom toggle-field">
                                                <label class="input-label">Appt Delivery</label>
                                                <div class="toggle-group" data-input="appt_delivery">
                                                    <button type="button" class="toggle-btn active" data-value="no">No</button>
                                                    <button type="button" class="toggle-btn" data-value="yes">Yes</button>
                                                </div>
                                                <input type="hidden" name="appt_delivery" id="appt_delivery" value="no">
                                            </div>
                                        </div> --}}
                                        <div class="col-6">
                                            <div class="input-group-custom toggle-field b2b-only-ui" style="display: none;">
                                                <label class="input-label">Freight Mode</label>
                                                <div class="toggle-group" data-input="freight_mode">
                                                    <button type="button" class="toggle-btn active" data-value="on_pickup">On
                                                        Pickup</button>
                                                    <button type="button" class="toggle-btn" data-value="to_pay">To Pay</button>
                                                </div>
                                                <input type="hidden" name="freight_mode" id="freight_mode" value="on_pickup">
                                            </div>
                                        </div>
                                        <div class="col-6" style="display: none;">
                                            <div class="b2b-only-ui input-group-custom toggle-field" >
                                                <div class="input-group-custom">
                                                    <label class="input-label">Insurance By</label>
                                                    <div class="toggle-group" data-input="insurance_by">
                                                        <button type="button" class="toggle-btn active"
                                                            data-value="owner">Owner</button>
                                                        <button type="button" class="toggle-btn"
                                                            data-value="carrier">Carrier</button>
                                                    </div>
                                                    <input type="hidden" name="insurance_by" id="insurance_by" value="owner">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="cod-amount-wrap input-group-custom toggle-field" id="cod_amount_field"
                                                style="display: none;padding: 10px;">
                                                <div class="input-group-custom">
                                                    <input type="text" name="cod_amount" id="cod_amount" class="input-control"
                                                        placeholder="COD Amount">
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    </div>



                                </div>
                                <!-- Actions -->
                                <div class="action-row">
                                    <button type="button" class="btn-reset-premium"
                                        onClick="window.location.reload();">Reset</button>
                                    <button type="submit" class="btn-calculate-premium">
                                        Calculate Rates <i class="mdi mdi-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="{{ $isSingleCompanyMode ? 'shipping_cost' : 'mt-4 shipping_cost' }}" style="display:none;">
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            const isSingleCompanyMode = @json(!empty($selectedShippingCompany));

            function syncPackageTypeUI(type) {
                if (isSingleCompanyMode) {
                    $('.calculator-card').removeClass('calc-card-b2b');
                    $('.b2b-only-ui').hide();
                    $('#addBoxBtn').hide();
                    $('#type_of_package').val('1');
                    return;
                }

                const isB2b = String(type) === '2';

                if (isB2b) {
                    $('.calculator-card').addClass('calc-card-b2b');
                    $('.b2b-only-ui').show();
                    $('#addBoxBtn').show();
                    return;
                }

                $('.calculator-card').removeClass('calc-card-b2b');
                $('.b2b-only-ui').hide();
                $('#addBoxBtn').hide();
                $('#dim_rows_container').find('.dim-inputs').not('.base-dim-row').remove();
                $('#dim_rows_container').find('input[name="box[]"]').val('1');
            }

            // Tab switching
            $('.calc-tab').on('click', function () {
                const type = $(this).data('type');
                $('.calc-tab').removeClass('active');
                $(this).addClass('active');
                $('#type_of_package').val(type);
                syncPackageTypeUI(type);
            });

            syncPackageTypeUI($('#type_of_package').val());

            // Toggle Options
            $('#toggleOptionsBtn').on('click', function () {
                const $section = $('#optionsSection');
                const willOpen = !$section.is(':visible');
                $section.slideToggle(400);
                $(this).html(willOpen ?
                    '<i class="mdi mdi-minus-circle-outline"></i> Hide Options' :
                    '<i class="mdi mdi-plus-circle-outline"></i> Add Dimensions &amp; Options');
            });

            if (isSingleCompanyMode) {
                $('#optionsSection').show();
            }

            // Toggle Buttons (Prepaid/COD, Units, etc)
            let currentUnit = 'CM';
            $('.toggle-btn').on('click', function () {
                const $group = $(this).closest('.toggle-group');
                const $input = $(`#${$group.data('input')}`);
                const val = $(this).data('value');

                $group.find('.toggle-btn').removeClass('active');
                $(this).addClass('active');

                if ($input.length) {
                    $input.val(val);
                }

                // Show/Hide COD field correctamente
                if ($group.data('input') === 'payment_type') {
                    if (val === 'cod') {
                        $('#cod_amount_field').slideDown(300);
                    } else {
                        $('#cod_amount_field').slideUp(300);
                        $('#cod_amount').val('0');
                    }
                }

                // Unit Conversion logic
                if ($group.data('input') === 'dimension_unit') {
                    const newUnit = val;
                    if (newUnit !== currentUnit) {
                        $('.dim-val').each(function () {
                            let currVal = parseFloat($(this).val()) || 0;
                            if (currVal > 0) {
                                // Convert to CM first
                                let cmVal;
                                if (currentUnit === 'IN') cmVal = currVal * 2.54;
                                else if (currentUnit === 'FT') cmVal = currVal * 30.48;
                                else cmVal = currVal;

                                // Convert from CM to new Unit
                                let finalVal;
                                if (newUnit === 'IN') finalVal = cmVal / 2.54;
                                else if (newUnit === 'FT') finalVal = cmVal / 30.48;
                                else finalVal = cmVal;

                                $(this).val(finalVal.toFixed(2));
                            }
                        });
                        currentUnit = newUnit;
                    }
                }
            });

            // Add Box logic
            $('#addBoxBtn').on('click', function () {
                const b2bDisplay = $('#type_of_package').val() == 2 ? '' : 'style="display:none;"';
                const newRow = `
                                                        <div class="dim-inputs mb-2 animate__animated animate__fadeInUp">
                                                            <div class="input-group-custom">
                                                                <label class="input-label">Length</label>
                                                                <input type="text" name="length[]" class="dim-control dim-val" placeholder="0.0">
                                                            </div>
                                                            <div class="input-group-custom">
                                                                <label class="input-label">Width</label>
                                                                <input type="text" name="width[]" class="dim-control dim-val" placeholder="0.0">
                                                            </div>
                                                            <div class="input-group-custom">
                                                                <label class="input-label">Height</label>
                                                                <input type="text" name="height[]" class="dim-control dim-val" placeholder="0.0">
                                                            </div>
                                                            <div class="input-group-custom b2b-only-ui" ${b2bDisplay}>
                                                                <label class="input-label">QTY</label>
                                                                <input type="number" name="box[]" class="dim-control" value="1">
                                                            </div>
                                                            <div class="dim-delete-action">
                                                                 <button type="button" class="btn btn-icon waves-effect waves-light action-icon mr-1 btn-delete-zone text-danger" onclick="$(this).closest('.dim-inputs').remove()">
                                                                    <i class="mdi mdi-delete-outline" style="font-size: 20px;"></i>
                                                                 </button>
                                                            </div>
                                                        </div>`;
                $('#dim_rows_container').append(newRow);
            });

            let pickupServiceable = true;
            let deliveryServiceable = true;

            function updateCalculateButtonState() {
                const isBothServiceable = pickupServiceable && deliveryServiceable;
                const $btn = $('.btn-calculate-premium');
                if (isBothServiceable) {
                    $btn.prop('disabled', false).css('opacity', '1').removeAttr('title');
                    $btn.html('Calculate Rates <i class="mdi mdi-arrow-right"></i>');
                } else {
                    $btn.prop('disabled', true).css('opacity', '0.6').attr('title', 'Calculation disabled: One or more pincodes are not serviceable.');
                    $btn.html('<i class="mdi mdi-close-circle-outline"></i> Not Serviceable');
                }
            }

            function performServiceCheck(pincode, targetId) {
                const direction = targetId === 'pickup_code' ? 'from' : 'to';
                const $statusSpan = targetId === 'pickup_code' ? $('#pickup_service_status') : $('#delivery_service_status');
                const $cityLabel = targetId === 'pickup_code' ? $('#pickup_city_label') : $('#delivery_city_label');

                if (pincode.length !== 6) {
                    $statusSpan.empty();
                    return;
                }

                // 1. Basic Pincode Check (City/State)
                $.get("{{ url('rate/pincode-check') }}/" + pincode)
                    .done(function (res) {
                        if (res.status === 'success') {
                            $cityLabel.text(res.city + ', ' + res.state).parent().css('opacity', '1');
                            
                            // 2. Serviceability Check
                            $.get("{{ route('rate.shipping.pincode.serviceability') }}", { pincode: pincode, direction: direction })
                                .done(function(srv) {
                                    if (targetId === 'pickup_code') pickupServiceable = srv.is_serviceable;
                                    else deliveryServiceable = srv.is_serviceable;

                                    if (srv.is_serviceable) {
                                        $statusSpan.html('<span class="badge badge-soft-success"><i class="mdi mdi-check-circle"></i> Serviceable</span>');
                                    } else {
                                        $statusSpan.html('<span class="badge badge-soft-danger"><i class="mdi mdi-close-circle"></i> Not Serviceable</span>');
                                    }
                                    updateCalculateButtonState();
                                })
                                .fail(function() {
                                    if (targetId === 'pickup_code') pickupServiceable = false;
                                    else deliveryServiceable = false;
                                    $statusSpan.html('<span class="badge badge-soft-warning">Error checking service</span>');
                                    updateCalculateButtonState();
                                });

                        } else {
                            $cityLabel.text('Invalid PIN').parent().css('opacity', '0.5');
                            $statusSpan.empty();
                            if (targetId === 'pickup_code') pickupServiceable = false;
                            else deliveryServiceable = false;
                            updateCalculateButtonState();
                        }
                    });
            }

            // Pincode validation & info
            $('#pickup_code, #delivery_code').on('keyup change', function () {
                performServiceCheck($(this).val(), $(this).attr('id'));
            });

            // Initial check
            performServiceCheck($('#pickup_code').val(), 'pickup_code');
            performServiceCheck($('#delivery_code').val(), 'delivery_code');

            // Form Submit logic
            $('#rateForm').on('submit', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('.btn-calculate-premium');
                const originalHtml = $btn.html();

                $btn.prop('disabled', true).css('opacity', '0.7').html(
                    '<i class="mdi mdi-loading mdi-spin"></i> CALCULATING...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    dataType: 'json',
                    data: $form.serialize(),
                    success: function (res) {
                        $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                        if (res.status === 'error') {
                            $('.shipping_cost').empty().hide();
                            toastrMsg('error', res.msg || 'No rates found');
                            return;
                        }
                        $('.shipping_cost').html(res.view).show();
                        if (!isSingleCompanyMode) {
                            $('html, body').animate({
                                scrollTop: $(".shipping_cost").offset().top - 100
                            }, 500);
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                        $('.shipping_cost').empty().hide();

                        let msg = 'Something went wrong';
                        if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
                        toastrMsg('error', msg);
                    }
                });
            });
        });

        function toastrMsg(type, msg) {
            if (window.toastr) {
                toastr[type](msg);
            } else {
                alert(msg);
            }
        }
    </script>
@endpush
