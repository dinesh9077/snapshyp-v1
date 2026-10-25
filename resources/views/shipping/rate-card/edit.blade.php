@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Edit Rate Card')
@section('header_title', 'Edit Rate Card')

@section('content')
<style>
      body{
        overflow-y: hidden;
    }
.table td {
    padding: 10px;
}

.table {
    min-width: 1200px;
}

@media (max-width: 1400px) {
    .table td {
        padding: 5px;
    }
}

/* Custom Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 20px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 50%;
}

input:checked+.slider:before {
    -webkit-transform: translateX(14px);
    -ms-transform: translateX(14px);
    transform: translateX(14px);
}

.matrix-input {
    width: 80px;
    text-align: center;
}

.rate-actions {
    gap: 12px;
}

.rate-actions .btn-main-1 {
    white-space: nowrap;
}

.rate-actions .action-note {
    margin-top: 6px;
}

.rate-input-pair {
    gap: 10px;
}

        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            font-weight: 500;
            background: transparent;
            border-radius: 10px 10px 0 0;
        }

        .nav-tabs .nav-link:hover {
            color: var(--new-color-prime);
            border-bottom: 3px solid var(--new-color-prime);
        }

        .nav-tabs .nav-link.active {
             color: #fff;
            border-bottom: 3px solid var(--new-color-prime);
            background: var(--new-color-prime);
        }

        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .btn-tab {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            font-weight: 500;
            background: transparent;
            border-radius: 10px 10px 0 0;
        }

        .btn-tab:hover {
            color: #fff;
            border-bottom: 3px solid  var(--new-color-prime);
        }

        .btn-tab.active {
            color:  #fff !important;
            border-bottom: 3px solid  var(--new-color-prime) !important;
            background:  var(--new-color-prime) !important;
        }

.overhead-grid .form-label {
    font-size: 12px;
    margin-bottom: 4px;
}

.overhead-grid .form-control {
    height: 34px;
    font-size: 13px;
}

/* Other Charges Styling */
:root {
    --prime: #3984FB;
    --border-color: #94a3b8;
}

.other-charges-container {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    margin-top: 20px;
}

.other-charge-item {
    border-bottom: 1px solid #f1f5f9;
    padding: 12px 0;
    transition: background 0.2s;
    padding: 16px;
    margin-bottom: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.other-charge-item:hover {
    border-color: #FB5C08;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    transform: translateY(-2px);
}

.other-charge-item.active {
    border-color: #FB5C08;
    background-color: #fffaf9;
}

.other-charge-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0;
}

.charge-name {
    font-weight: 700;
    font-size: 15px;
    color: #0f172a;
    margin-bottom: 0px;
    line-height: 1.4;
}

.charge-config {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed #e2e8f0;
    display: none;
}

.charge-config.show {
    display: block;
}

.config-input-group {
    flex: 1;
    min-width: 100px;
}

.config-input-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

        .enabled-toggle {
            width: 40px !important;
            height: 20px !important;
            cursor: pointer;
        }

        .enabled-toggle:checked {
            background-color: #FB5C08 !important;
            border-color: #FB5C08 !important;
        }

        /* Dynamic Other Charges Styling */
        .other-charges-table-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .other-charge-row {
            /* background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px; */
            margin-bottom: 10px;
            transition: all 0.2s;
        }

        .other-charge-row:hover {
            border-color: #FB5C08;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn-add-charge {
            background: #fff;
            color: #FB5C08;
            border: 1.5px dashed #FB5C08;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            width: 100%;
            margin-top: 10px;
        }

        .btn-add-charge:hover {
            background: rgba(251, 92, 8, 0.05);
            color: #e04a00;
        }

        .config-input-group label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .remove-charge-row {
            color: white;
            transition: all 0.2s;
            height: 36px;
            width: 35px;
            background: #ef4444;
            border-radius: 5px;
            font-size: 18px;
        }

        .remove-charge-row:hover {
            background: #dc2626;
              color: white;
            transform: scale(1.1);
        }
        .select2-container {
    width: auto !important;
}
    </style>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="main-order-001">
                <form id="rateCardForm" method="POST" action="{{ route('rate.card.update', $rateCard->id) }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="page-title">Edit Rate Card</h4>
                        <a href="{{ route('rate.card') }}?type={{ request('type', $rateCard->rate_type ?? 'b2c') }}{{ request('embed') ? '&embed=' . request('embed') : '' }}" class="btn btn-light btn-back">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>
                    </div>

                    <div class="pt-3">

                        {{-- Group Name --}}
                        <div class="row mb-3">
                            <div class="col-lg-4 col-md-6">
                                <label for="group_name" class="form-label fw-bold">Group Name</label>
                                <input type="text" class="form-control" name="group_name" placeholder="Enter Group Name"
                                    value="{{ $rateCard->group_name }}" required>
                            </div>
                        </div>

                        {{-- Helper Function --}}
                        @php
                        if (!function_exists('getRateValue')) {
                            function getRateValue($rateCard, $shippingCompanyId, $weight, $zoneSettingId, $field = 'rate') {
                                $detail = $rateCard->rateCardDetails
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->where('weight', $weight)
                                    ->where('zone_setting_id', $zoneSettingId)
                                    ->first();
                                return $detail ? $detail->$field : 0;
                            }
                        }

                        if (!function_exists('getCodValue')) {
                            function getCodValue($rateCard, $shippingCompanyId, $weight, $field = 'cod_charges') {
                                $detail = $rateCard->rateCardDetails
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->where('weight', $weight)
                                    ->first();
                                return $detail ? $detail->$field : 0;
                            }
                        }

                        if (!function_exists('getStatus')) {
                            function getStatus($rateCard, $shippingCompanyId) {
                                $detail = $rateCard->rateCardDetails
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->first();
                                return $detail ? $detail->status : 0;
                            }
                        }

                        if (!function_exists('getB2bStatus')) {
                            function getB2bStatus($rateCard, $shippingCompanyId) {
                                $matrix = $rateCard->rateCardMatrices
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->first();
                                return $matrix ? $matrix->status : 0;
                            }
                        }

                        if (!function_exists('getB2BRate')) {
                            function getB2BRate($rateCard, $shippingCompanyId, $fromZoneId, $toZoneId) {
                                $matrix = $rateCard->rateCardMatrices
                                    ->where('shipping_company_id', $shippingCompanyId)
                                    ->where('from_zone_id', $fromZoneId)
                                    ->where('to_zone_id', $toZoneId)
                                    ->first();
                                return $matrix ? $matrix->rate_per_kg : 0;
                            }
                        }

                        if (!function_exists('getOtherChargeData')) {
                            function getOtherChargeData($rateCard, $shippingCompanyId, $masterId) {
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

                        @php
                            $currentRateType = old('rate_type', $rateCard->rate_type ?? 'b2c');
                        @endphp
                        <input type="hidden" name="rate_type" value="{{ $currentRateType }}">
                        <div class="rate-card-content">
                            {{-- B2C Section --}}
                            <div class="tab-content-section" id="b2c-section" style="{{ $currentRateType === 'b2b' ? 'display: none;' : '' }}">
                                <fieldset id="b2c-fieldset" {{ $currentRateType === 'b2b' ? 'disabled' : '' }}>
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <h5 class="fw-bold mb-0">B2C Shipping Details</h5>
                                <div class="d-flex rate-actions align-items-start">
                                <div class="d-flex flex-column align-items-center">
                                <a href="{{ route('card.download-sample', $rateCard->id) }}?type=b2c"
                                class="btn-main-1">
                                <i class="mdi mdi-download"></i> Export B2C
                                </a>
                                <small class="danger fw-bold action-note" style="font-size: 12px;">
                                * Download B2C format
                                </small>
                                </div>
                                <button type="button" class="btn-main-1 btn-import-rate" data-import-type="b2c">
                                <i class="mdi mdi-upload"></i> Import B2C
                                </button>
                                </div>
                                </div>

                                @foreach ($zoneShippingSettings as $index => $zoneSettings)
                                @php
                                $shippingCompanyId = $zoneSettings->first()->shippingCompany->id ?? null;
                                $weights = [];
                                if ($shippingWeights->has($shippingCompanyId)) {
                                $weights = $shippingWeights->get($shippingCompanyId)->pluck('weight');
                                }
                                @endphp

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold text-primary mb-0" style="font-size: 21px; letter-spacing: 1px;">{{ $zoneSettings->first()->shippingCompany->name }}</h6>
                                        <div>
                                            <select class="form-select select2" name="shipping[{{ $index }}][status]">
                                                <option value="1"
                                                    {{ getStatus($rateCard, $shippingCompanyId) == 1 ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="0"
                                                    {{ getStatus($rateCard, $shippingCompanyId) == 0 ? 'selected' : '' }}>
                                                    In-Active</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <input type="hidden" name="shipping[{{ $index }}][shipping_company_id]"
                                            value="{{ $zoneSettings->first()->shippingCompany->id }}">

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Weight (Kg)</th>
                                                        @foreach ($zoneSettings as $zoneSetting)
                                                        <th>{{ $zoneSetting->heading }}</th>
                                                        @endforeach
                                                        <th>COD Charges / Extra</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($weights as $weight)
                                                    <tr>
                                                        <td>
                                                            <input type="number" readonly class="form-control form-control-sm"
                                                                value="{{ $weight }}">
                                                        </td>

                                                        @foreach ($zoneSettings as $zoneSetting)
                                                        @php
                                                        $rate = getRateValue($rateCard, $zoneSetting->shipping_company_id, $weight, $zoneSetting->id, 'rate');
                                                        $extra = getRateValue($rateCard, $zoneSetting->shipping_company_id, $weight, $zoneSetting->id, 'extra_rate');
                                                        @endphp
                                                        <td>
                                                            <div class="d-flex align-items-center rate-input-pair">
                                                                <input type="hidden"
                                                                    name="shipping[{{ $index }}][{{ $weight }}][id_{{ $zoneSetting->id }}]"
                                                                    value="{{ $zoneSetting->id }}">
                                                                <input type="number" class="form-control form-control-sm me-1 w-50"
                                                                    name="shipping[{{ $index }}][{{ $weight }}][{{ $zoneSetting->slug }}_{{ $zoneSetting->id }}]"
                                                                    placeholder="Rate" step="0.01" min="0" value="{{ $rate }}"
                                                                    required>
                                                                <input type="number" class="form-control form-control-sm w-50"
                                                                    name="shipping[{{ $index }}][{{ $weight }}][{{ $zoneSetting->slug }}_{{ $zoneSetting->id }}_extra]"
                                                                    placeholder="Extra" step="0.01" min="0" value="{{ $extra }}"
                                                                    required>
                                                            </div>
                                                        </td>
                                                        @endforeach

                                                        @php
                                                        $codCharge = getCodValue($rateCard, $zoneSettings->first()->shippingCompany->id, $weight, 'cod_charges');
                                                        $codPercent = getCodValue($rateCard, $zoneSettings->first()->shippingCompany->id, $weight, 'cod_percentage');
                                                        @endphp
                                                        <td>
                                                            <div class="d-flex align-items-center rate-input-pair">
                                                                <input type="number" class="form-control form-control-sm me-1 w-50"
                                                                    name="shipping[{{ $index }}][{{ $weight }}][cod_charges]"
                                                                    placeholder="Charge" step="0.01" min="0"
                                                                    value="{{ $codCharge }}" required>
                                                                <input type="number" class="form-control form-control-sm w-50"
                                                                    name="shipping[{{ $index }}][{{ $weight }}][cod_percentage]"
                                                                    placeholder="Extra" step="0.01" min="0"
                                                                    value="{{ $codPercent }}" required>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if ($zoneShippingSettings->isEmpty())
                                <div class="alert alert-warning">Please define B2C Zones first in Zone Settings.</div>
                            @endif
                                </fieldset>
                            </div>

                            {{-- B2B Section --}}
                            {{-- <div class="tab-content-section" id="b2b-section" style="{{ $currentRateType === 'b2c' ? 'display: none;' : '' }}">
                                <fieldset id="b2b-fieldset" {{ $currentRateType === 'b2c' ? 'disabled' : '' }}>
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h5 class="fw-bold mb-0">B2B Shipping Rate Matrix (Per KG)</h5>
                                    <div class="d-flex rate-actions align-items-start">
                                        <div class="d-flex flex-column align-items-center">
                                            <a href="{{ route('card.download-sample', $rateCard->id) }}?type=b2b"
                                                class="btn-main-1">
                                                <i class="mdi mdi-download"></i> Export B2B
                                            </a>
                                            <small class="danger fw-bold action-note" style="font-size: 12px;">
                                                * Download B2B format
                                            </small>
                                        </div>
                                        <button type="button" class="btn-main-1 btn-import-rate" data-import-type="b2b">
                                            <i class="mdi mdi-upload"></i> Import B2B
                                        </button>
                                    </div>
                                </div>

                                @if ($b2bZones->isEmpty())
                                    <div class="alert alert-warning">Please define B2B Zones first in Zone Settings.</div>
                                @else
                                    @foreach ($b2bZones as $shippingCompanyId => $zones)
                                        <div class="mb-5">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="fw-bold text-primary mb-0" style="font-size: 21px; letter-spacing: 1px;">
                                                    {{ $zones->first()->shippingCompany->name ?? 'N/A' }}
                                                </h6>
                                                <div>
                                                    <select class="form-select select2" name="b2b_status[{{ $shippingCompanyId }}]">
                                                        <option value="1"
                                                            {{ getB2bStatus($rateCard, $shippingCompanyId) == 1 ? 'selected' : '' }}>
                                                            Active</option>
                                                        <option value="0"
                                                            {{ getB2bStatus($rateCard, $shippingCompanyId) == 0 ? 'selected' : '' }}>
                                                            In-Active</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="background: #f8f9fa;">Origin \ Dest.</th>
                                                            @foreach ($zones as $destZone)
                                                                <th>{{ $destZone->heading }}</th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($zones as $originZone)
                                                            <tr>
                                                                <td class="fw-bold table-light">{{ $originZone->heading }}</td>
                                                                @foreach ($zones as $destZone)
                                                                    @php
                                                                        $b2bRate = getB2BRate($rateCard, $shippingCompanyId, $originZone->id, $destZone->id);
                                                                    @endphp
                                                                    <td class="p-0">
                                                                        <input type="number" 
                                                                            name="b2b[{{ $shippingCompanyId }}][{{ $originZone->id }}][{{ $destZone->id }}]"
                                                                            class="form-control form-control-sm matrix-input mx-auto p-0" style="border: none;"
                                                                            value="{{ $b2bRate }}" step="0.01" min="0">
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="other-charges-table-container mt-4">
                                                @php
                                                    $companyChargeMasters = $otherChargeMasters[$shippingCompanyId] ?? collect();
                                                @endphp
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2" style="    font-size: 15px;color: black;">
                                                        Other Charges & Additional Fees
                                                    </h6>
                                                </div>
                                                
                                                <div class="other-charges-rows-container" id="other_charges_container_{{ $shippingCompanyId }}">
                                                    @php $hasCharges = false; @endphp
                                                    @foreach($companyChargeMasters as $master)
                                                        @php
                                                            $savedConfig = getOtherChargeData($rateCard, $shippingCompanyId, $master->id);
                                                        @endphp
                                                        @if(!is_null($savedConfig))
                                                            @php $hasCharges = true; @endphp
                                                            <div class="other-charge-row row align-items-center g-2" data-master-id="{{ $master->id }}" data-saved-config='@json($savedConfig)'>
                                                                <div class="col-md-3">
                                                                    <label class="form-label small fw-bold mb-1">Select Charge</label>
                                                                    <select class="form-select other-charge-selector" data-company-id="{{ $shippingCompanyId }}">
                                                                        <option value="">-- Select Charge --</option>
                                                                        @foreach($companyChargeMasters as $m)
                                                                            <option value="{{ $m->id }}" {{ $m->id == $master->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-8 config-fields-row">
                                                                </div>
                                                                <div class="col-md-1 text-center">
                                                                    <button type="button" class="btn btn-link link-danger p-0 mt-3 remove-charge-row" onclick="$(this).closest('.other-charge-row').remove()">
                                                                        <i class="mdi mdi-delete-outline fs-3"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>

                                                <button type="button" class="btn btn-add-charge" onclick="addOtherChargeRow({{ $shippingCompanyId }})">
                                                    <i class="mdi mdi-plus-circle-outline"></i> Add Another Charge
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                                </fieldset>
                            </div> --}}
                        </div>
                    </div>
                    
                    <div class="text-right mt-3">
                        <button type="submit" class="new-submit-btn">
                            <i class="mdi mdi-content-save"></i> Save Rate Card
                        </button>
                    </div>




                    <div class="mt-4">
                        <h5 class="fw-bold mb-3 text-dark">Value Surcharge and Value Discounts</h5>

                        <div class="alert alert-light border d-flex align-items-center" role="alert"
                            style="background-color: #f6f7fb; color: #333; font-size: 13px;">
                            <i class="mdi mdi-information-variant mr-2" style="font-size: 18px;"></i>
                            <span>All additional percentage based shipping charges/discounts will be applied on the
                                Selling Price of shipment except RTO charges. RTO percentage charges are based on
                                base shipping charges.</span>
                        </div>

                        {{-- RTO Based Surcharge --}}
                        <div class="border brd p-2 mb-2 bg-white">
                            <h6 class="fw-bold mb-2 mt-0" style="font-size: 14px;">RTO Based Surcharge</h6>
                            <div class="d-flex align-items-center flex-wrap">
                                <span class="mr-2" style="font-size: 13px;">View:</span>
                                <label class="switch mr-1 mb-0">
                                    <input type="checkbox" name="is_rto_charge" value="1" id="rtoSwitch"
                                        {{ $rateCard->is_rto_charge ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                                <span style="font-size: 13px; color: #555;">RTO Charges Percentages
                                    : {{ $rateCard->is_rto_charge ? '100.00' : '0.00' }} % of
                                    forward leg charges</span>
                            </div>
                        </div>

                        {{-- Volumetric Weight --}}
                        <div class="border brd p-2 mb-2 bg-white">
                            <div class="d-flex align-items-center mb-2">
                                <h6 class="fw-bold mb-0 mt-0" style="font-size: 14px;">Volumetric Weight</h6>
                            </div>
                            <div style="font-size: 13px;">
                                Divisor : <span class="fw-bold">5000</span>
                            </div>
                        </div>
                    </div>
                </form>

            </div>


        </div>
    </div>
</div>
{{-- Hidden File Input for Import --}}
<input type="file" id="import_file" style="display: none;" accept=".csv, .xlsx, .xls">

@endsection

@push('js')
<script>
    const otherChargeMastersByCompany = @json(($otherChargeMasters ?? collect())->map(function ($rows) {
        return collect($rows)->values()->all();
    }));
    const shippingCompanyIds = @json($b2bZones->keys());
    const rateImportUrlBase = "{{ url('card/import') }}";

    function getCompanyMasters(companyId) {
        return otherChargeMastersByCompany[String(companyId)] || [];
    }

    function renderChargeFields($selector, masterId, companyId, savedConfig = null) {
        const $configContainer = $selector.closest('.other-charge-row').find('.config-fields-row');
        const companyMasters = getCompanyMasters(companyId);
        const master = companyMasters.find(m => m.id == masterId);
        if (!masterId || !master) {
            $configContainer.html('<div class="text-muted small px-3 d-flex align-items-center bg-light rounded italic" style="border: 1px dashed #cbd5e0; border-radius: 8px !important; height: 38px;">Select a charge from the left to configure</div>');
            return;
        }

        let fields = {};
        switch (master.calc_type) {
            case 'per_kg_or_min': fields = {per_kg: 'Per KG', min: 'Min Amount'}; break;
            case 'percent_or_min': fields = {percent: 'Percentage (%)', min: 'Min Amount', base: 'Calculation Base'}; break;
            case 'per_kg_or_flat': fields = {per_kg: 'Per KG', flat: 'Flat Rate'}; break;
            case 'flat': fields = {flat: 'Flat Rate'}; break;
            case 'divisor': fields = {divisor: 'Divisor Value'}; break;
            case 'min_charge': fields = {min: 'Min Amount'}; break;
            case 'min_weight': fields = {min: 'Minimum Weight (Kg)'}; break;
            case 'gst_percent': fields = {percent: 'GST Percentage (%)'}; break;
            case 'handling_tier': fields = {threshold: 'Weight Threshold', rate_upto: 'Rate Upto', rate_above: 'Rate Above'}; break;
            case 'max_single_box_weight': fields = {max_single_box_weight: 'Max Box Weight'}; break;
        }

        if (master.calc_type === 'chargeable_weight') {
            $configContainer.html(`
                <div class="text-muted small px-3 d-flex align-items-center bg-light rounded italic" style="border: 1px dashed #cbd5e0; border-radius: 8px !important; height: 38px;">
                    Chargeable Weight = Max(Actual Weight, Volumetric Weight)
                </div>
            `);
            return;
        }

        if (master.calc_type === 'freight') {
            $configContainer.html(`
                <div class="text-muted small px-3 d-flex align-items-center bg-light rounded italic" style="border: 1px dashed #cbd5e0; border-radius: 8px !important; height: 38px;">
                    Shipping Charges = Chargeable weight * per kg zone matrix rate
                </div>
            `);
            return;
        }

        let fieldsHtml = '<div class="row g-2">';
        fieldsHtml += `<input type="hidden" name="other_charges[${companyId}][${masterId}][_enabled]" value="1">`;
        
        Object.keys(fields).forEach(key => {
            let label = fields[key];
            let defaultValue = savedConfig ? (savedConfig[key] || '') : (master.default_config ? (master.default_config[key] || '') : '');
            
            fieldsHtml += `<div class="col">
                <div class="config-input-group">
                    <label class="d-block">${label}</label>`;
            
            if (key === 'base') {
                fieldsHtml += `<select class="form-select config-field py-1 px-2" style="font-size: 13px; height: 38px;" name="other_charges[${companyId}][${masterId}][${key}]">
                    <option value="">Default</option>
                    <option value="freight" ${defaultValue == 'freight' ? 'selected' : ''}>Weight Based</option>
                    <option value="cod" ${defaultValue == 'cod' ? 'selected' : ''}>COD Based</option>
                    <option value="invoice" ${defaultValue == 'invoice' ? 'selected' : ''}>Invoice Value Based</option>
                    <option value="total" ${defaultValue == 'total' ? 'selected' : ''}>Sub-Total Based</option>
                </select>`;
            } else {
                fieldsHtml += `<input type="number" step="0.01" class="form-control config-field py-1 px-2" style="font-size: 13px; height: 38px;"
                    name="other_charges[${companyId}][${masterId}][${key}]" 
                    placeholder="0.00" value="${defaultValue}">`;
            }
            
            fieldsHtml += `</div></div>`;
        });
        fieldsHtml += '</div>';
        $configContainer.html(fieldsHtml);
    }

    function hasDuplicateChargeSelection(companyId, masterId, currentRow) {
        if (!masterId) {
            return false;
        }

        let isDuplicate = false;
        $(`#other_charges_container_${companyId} .other-charge-selector`).each(function() {
            if (this === currentRow.find('.other-charge-selector')[0]) {
                return;
            }

            if (String($(this).val()) === String(masterId)) {
                isDuplicate = true;
                return false;
            }
        });

        return isDuplicate;
    }

    window.addOtherChargeRow = function(companyId, masterId = '', savedConfig = null) {
        const companyMasters = getCompanyMasters(companyId);
        let html = `
            <div class="other-charge-row row align-items-end g-2">
                <div class="col-xl col-md-4">
                    <label class="form-label small fw-bold mb-1">Select Charge</label>
                    <select class="form-select other-charge-selector" data-company-id="${companyId}" style="height: 38px;     padding: 5px 15px;">
                        <option value="">-- Select Charge --</option>
                        ${companyMasters.map(m => `<option value="${m.id}" ${m.id == masterId ? 'selected' : ''}>${m.name}</option>`).join('')}
                    </select>
                </div>
                <div class="col-xl col-md-7 config-fields-row">
                    ${masterId ? '' : '<div class="text-muted small px-3 d-flex align-items-center bg-light rounded italic" style="border: 1px dashed #cbd5e0; border-radius: 8px !important; height: 38px;">Select a charge from the left to configure</div>'}
                </div>
                <div class="col-auto text-center">
                    <button type="button" class="btn btn-link link-danger p-0 remove-charge-row" onclick="$(this).closest('.other-charge-row').remove()">
                        <i class="mdi mdi-delete-outline fs-3"></i>
                    </button>
                </div>
            </div>
        `;
        const $row = $(html);
        $(`#other_charges_container_${companyId}`).append($row);
        
        if (masterId) {
            renderChargeFields($row.find('.other-charge-selector'), masterId, companyId, savedConfig);
        }
    };

    $(document).ready(function() {
        function parseAjaxResponse(xhr) {
            if (xhr.responseJSON) {
                return xhr.responseJSON;
            }

            const raw = xhr.responseText;
            if (!raw || typeof raw !== 'string') {
                return null;
            }

            try {
                return JSON.parse(raw);
            } catch (error) {
                return null;
            }
        }

        function resolveAjaxErrorMessage(xhr, fallback = 'Something went wrong') {
            const response = parseAjaxResponse(xhr);
            if (response?.msg) {
                return response.msg;
            }

            const raw = (xhr.responseText || '').trim();
            if (!raw) {
                return fallback;
            }

            if (raw.startsWith('<')) {
                return `Request failed with status ${xhr.status || 500}.`;
            }

            return raw.length > 250 ? raw.slice(0, 250) : raw;
        }

        function buildRateCardFormData($form) {
            const $heavyInputs = $form.find('[name^="b2b["], [name^="other_charges["]');
            const previousDisabledState = [];

            $heavyInputs.each(function() {
                previousDisabledState.push({
                    element: this,
                    disabled: this.disabled
                });
                this.disabled = true;
            });

            const formData = new FormData($form[0]);

            previousDisabledState.forEach(item => {
                item.element.disabled = item.disabled;
            });

            const b2bData = {};
            $form.find('input[name^="b2b["]').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                const m = name.match(/b2b\[(\d+)\]\[(\d+)\]\[(\d+)\]/);
                if (m) {
                    if (!b2bData[m[1]]) b2bData[m[1]] = {};
                    if (!b2bData[m[1]][m[2]]) b2bData[m[1]][m[2]] = {};
                    b2bData[m[1]][m[2]][m[3]] = val;
                }
            });

            if (Object.keys(b2bData).length > 0) {
                formData.append('b2b_json', JSON.stringify(b2bData));
            }

            const otherChargesData = {};
            $form.find('[name^="other_charges["]').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if ($(this).is(':checkbox') && !$(this).is(':checked')) return;

                const m = name.match(/other_charges\[(\d+)\]\[(\d+)\]\[([^\]]+)\]/);
                if (m) {
                    const cid = m[1], mid = m[2], key = m[3];
                    if (!otherChargesData[cid]) otherChargesData[cid] = {};
                    if (!otherChargesData[cid][mid]) otherChargesData[cid][mid] = {};
                    otherChargesData[cid][mid][key] = val;
                }
            });

            if (Object.keys(otherChargesData).length > 0) {
                formData.append('other_charges_json', JSON.stringify(otherChargesData));
            }

            return formData;
        }

        $('#rateCardForm').on('submit', function(e) {
            e.preventDefault();
            let $form = $(this);
            let $button = $form.find('button[type="submit"]');
            $button.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Saving...');
            const formData = buildRateCardFormData($form);

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(res) {
                    $button.prop('disabled', false).html(
                        '<i class="mdi mdi-content-save"></i> Save Rate Card');
                    toastrMsg(res.status, res.msg);
                    if (res.status === 'success') {
                        const rateType = $form.find('input[name="rate_type"]').val() || 'b2c';
                        setTimeout(() => window.location.href = "{{ route('rate.card') }}" + '?type=' + encodeURIComponent(rateType) + "{{ request('embed') ? '&embed=' . request('embed') : '' }}",
                            800);
                    }
                },
                error: function(xhr) {
                    $button.prop('disabled', false).html(
                        '<i class="mdi mdi-content-save"></i> Save Rate Card');
                    const parsedResponse = parseAjaxResponse(xhr);
                    if (parsedResponse?.status === 'success') {
                        toastrMsg('success', parsedResponse.msg || 'Rate card saved successfully.');
                        const rateType = $form.find('input[name="rate_type"]').val() || 'b2c';
                        setTimeout(() => window.location.href = "{{ route('rate.card') }}" + '?type=' + encodeURIComponent(rateType) + "{{ request('embed') ? '&embed=' . request('embed') : '' }}", 800);
                        return;
                    }

                    if (parsedResponse?.errors) {
                        $.each(parsedResponse.errors, (key, value) => toastrMsg('error',
                            value[0]));
                    } else {
                        toastrMsg('error', resolveAjaxErrorMessage(xhr));
                    }
                }
            });
        });

        $(document).on('change', '.other-charge-selector', function() {
            const $selector = $(this);
            const companyId = $selector.data('company-id');
            const masterId = $selector.val();
            const $row = $selector.closest('.other-charge-row');

            if (hasDuplicateChargeSelection(companyId, masterId, $row)) {
                toastrMsg('error', 'Same other charge cannot be selected twice for the same courier.');
                $selector.val('');
                renderChargeFields($selector, '', companyId);
                return;
            }

            renderChargeFields($selector, masterId, companyId);
        });

        // Initialize existing rows or add one default for each company
        shippingCompanyIds.forEach(id => {
            const $container = $(`#other_charges_container_${id}`);
            const existingRows = $container.find('.other-charge-row');
            
            if (existingRows.length > 0) {
                existingRows.each(function() {
                    const masterId = $(this).data('master-id');
                    const savedConfig = $(this).data('saved-config');
                    renderChargeFields($(this).find('.other-charge-selector'), masterId, id, savedConfig);
                });
            } else {
                window.addOtherChargeRow(id);
            }
        });

        // Handle Other Charges Toggles (if any still exist)
        $(document).on('change', '.enabled-toggle', function() {
            let $config = $(this).closest('.other-charge-item').find('.charge-config');
            if ($(this).is(':checked')) {
                $config.addClass('show');
            } else {
                $config.removeClass('show');
            }
        });

        $('.enabled-toggle').each(function() {
            if ($(this).is(':checked')) {
                $(this).closest('.other-charge-item').find('.charge-config').addClass('show');
            }
        });

        $(document).on('click', '.btn-import-rate', function() {
            const rateCardId = "{{ $rateCard->id }}";
            const importType = $(this).data('import-type') || 'b2c';
            const $btn = $(this);

            $('#import_file').off('change').on('change', function() {
                const file = this.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                const oldHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Importing...');

                $.ajax({
                    type: 'POST',
                    url: `${rateImportUrlBase}/${rateCardId}?type=${importType}`,
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        $btn.prop('disabled', false).html(oldHtml);
                        toastrMsg(res.status, res.msg);
                        if (res.status === 'success') {
                            setTimeout(() => window.location.reload(), 1000);
                        }
                        $('#import_file').val('');
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(oldHtml);
                        toastrMsg('error', xhr.responseJSON?.msg || 'Something went wrong');
                        $('#import_file').val('');
                    }
                });
            }).click();
        });

    });
</script>
@endpush
