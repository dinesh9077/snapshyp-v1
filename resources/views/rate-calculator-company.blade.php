@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipping Rate Calculator')
@section('header_title', 'Shipping Rate Calculator')

@push('css')
<style>
    :root {
        --calc-blue: #193b76;
        --calc-panel-bg: #f3f5f8;
        --calc-panel-border: #d7e2ee;
        --calc-label: #607793;
        --calc-heading: #102847;
        --calc-accent: #f05a28;
    }

    body {
        background: #eef2f7;
    }

    .calc-wrapper {
        background: #ffffff;
        border: 1px solid #d9e3ef;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(14, 34, 66, 0.08);
    }

    .calc-live-header {
        background: var(--calc-blue);
        color: #ffffff;
        text-align: center;
        padding: 18px 20px 16px;
    }

    .calc-live-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
    }

    .calc-live-header p {
        margin: 6px 0 0;
        font-size: 14px;
        color: #d5e3ff;
        font-weight: 500;
    }

    .calc-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(380px, .92fr);
        gap: 8px;
        background: #f1f4f8;
        padding: 14px;
    }

    .shipment-panel {
        background: #f1f4f8;
        border-right: 1px solid #dce5ef;
        padding: 18px 18px 10px;
    }

    .shipment-panel h4 {
        margin: 0 0 14px;
        font-size: 18px;
        font-weight: 800;
        color: var(--calc-heading);
        padding-bottom: 10px;
        border-bottom: 1px solid #d8e1eb;
    }

    .field-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .field-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px;
    }

    .field-box {
        margin-bottom: 14px;
    }

    .field-box label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #5f7391;
        margin-bottom: 6px;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .pin-location-text {
        margin-top: 5px;
        font-size: 12px;
        font-weight: 600;
        color: #5f7391;
        min-height: 18px;
    }

    .field-control {
        width: 100%;
        height: 52px;
        border: 1px solid #cbd7e4;
        border-radius: 8px;
        background: #f8fbff;
        color: #102847;
        font-size: 16px;
        font-weight: 700;
        padding: 8px 12px;
        outline: none;
    }

    .field-control:focus {
        border-color: #8ba8d1;
        background: #ffffff;
    }

    .invoice-cod-row {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: end;
        gap: 16px;
    }

    .cod-switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #1c355f;
        font-weight: 600;
        margin-bottom: 8px;
        white-space: nowrap;
    }

    .cod-switch input {
        width: 22px;
        height: 22px;
        border: 1px solid #9eb2cd;
    }

    .calc-message {
        font-size: 13px;
        color: #6b809f;
        font-weight: 600;
        margin-top: 6px;
    }

    .shipping_cost {
        min-height: 320px;
    }

    @media (max-width: 1200px) {
        .calc-grid {
            grid-template-columns: 1fr;
        }

        .shipment-panel {
            border-right: 0;
            border-bottom: 1px solid #dce5ef;
        }
    }

    @media (max-width: 768px) {
        .calc-live-header h3 {
            font-size: 18px;
        }

        .calc-live-header p {
            font-size: 12px;
        }

        .shipment-panel h4 {
            font-size: 18px;
        }

        .field-box label {
            font-size: 11px;
        }

        .field-control {
            font-size: 15px;
            height: 44px;
        }

        .cod-switch {
            font-size: 14px;
        }

        .field-grid-2,
        .field-grid-3 {
            grid-template-columns: 1fr;
        }

        .invoice-cod-row {
            grid-template-columns: 1fr;
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
@php
    $fromPincodes = collect($activePincodeOptions['from'] ?? []);
    $toPincodes = collect($activePincodeOptions['to'] ?? []);
    $defaultFrom = (string) ($activePincodeOptions['default_from'] ?? '110052');
    $defaultTo = (string) ($activePincodeOptions['default_to'] ?? '400077');
    $hasActivePair = (bool) ($activePincodeOptions['has_active_pair'] ?? false);
    $preferredType = (int) ($preferredPackageType ?? 1);
@endphp
<div class="content-page">
    <div class="container-fluid">
        <div class="calc-wrapper">
            <div class="calc-live-header">
                <h3>Live Freight Calculator &amp; Margin Analysis</h3>
                <p>Calculates admin cost and generates customer quotations with margins based on Rate Cards.</p>
            </div>

            <div class="calc-grid">
                <div class="shipment-panel">
                    <h4>Shipment Details</h4>
                    <form id="rateForm" method="post" action="{{ route('rate.calculator.create') }}">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $selectedShippingCompany->id }}">
                        <input type="hidden" name="type_of_package" value="{{ $preferredType }}">
                        <input type="hidden" name="payment_type" id="payment_type" value="prepaid">
                        <input type="hidden" name="cod_amount" id="cod_amount" value="0">

                        <div class="field-grid-2">
                            <div class="field-box">
                                <label>Origin Pincode</label>
                                <input type="text" class="field-control auto-calc-field" name="pickup_code" id="pickup_code" value="{{ $defaultFrom }}" maxlength="6" list="company_from_pincode_list">
                                <div class="pin-location-text" id="pickup_pin_location"></div>
                            </div>
                            <div class="field-box">
                                <label>Dest Pincode</label>
                                <input type="text" class="field-control auto-calc-field" name="delivery_code" id="delivery_code" value="{{ $defaultTo }}" maxlength="6" list="company_to_pincode_list">
                                <div class="pin-location-text" id="delivery_pin_location"></div>
                            </div>
                        </div>
                        <datalist id="company_from_pincode_list">
                            @foreach($fromPincodes as $pincodeRow)
                                <option value="{{ $pincodeRow['pincode'] }}">{{ $pincodeRow['city'] }}, {{ $pincodeRow['state'] }}</option>
                            @endforeach
                        </datalist>
                        <datalist id="company_to_pincode_list">
                            @foreach($toPincodes as $pincodeRow)
                                <option value="{{ $pincodeRow['pincode'] }}">{{ $pincodeRow['city'] }}, {{ $pincodeRow['state'] }}</option>
                            @endforeach
                        </datalist>

                        <div class="field-box">
                            <label>Actual Weight (Kg)</label>
                            <input type="text" class="field-control auto-calc-field" name="weight" id="weight" value="10">
                        </div>

                        <div class="field-grid-3">
                            <div class="field-box">
                                <label>Length (Cm)</label>
                                <input type="text" class="field-control auto-calc-field" name="length[]" id="length" value="10">
                            </div>
                            <div class="field-box">
                                <label>Width (Cm)</label>
                                <input type="text" class="field-control auto-calc-field" name="width[]" id="width" value="10">
                            </div>
                            <div class="field-box">
                                <label>Height (Cm)</label>
                                <input type="text" class="field-control auto-calc-field" name="height[]" id="height" value="10">
                            </div>
                        </div>

                        <input type="hidden" name="box[]" value="1">
                        <input type="hidden" name="dimension_unit" value="CM">

                        <div class="invoice-cod-row">
                            <div class="field-box mb-0">
                                <label>Invoice Value (&#8377;)</label>
                                <input type="text" class="field-control auto-calc-field" name="invoice_amount" id="invoice_amount" value="1000">
                            </div>
                            <label class="cod-switch">
                                <input type="checkbox" id="cod_toggle">
                                Cash On Delivery
                            </label>
                        </div>
                        <div class="calc-message" id="calc_message">
                            @if(!$hasActivePair)
                                Active pickup/delivery pincode mapping not found for this company. Please configure Manage Pincodes first.
                            @endif
                        </div>
                    </form>
                </div>

                <div class="shipping_cost" id="single_company_result">
                    <div class="calc-message" style="padding: 18px;">Calculating...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(function () {
        const $form = $('#rateForm');
        const $result = $('#single_company_result');
        const $message = $('#calc_message');
        const hasActivePair = @json($hasActivePair);
        const fromPincodes = new Set(@json($fromPincodes->pluck('pincode')->values()->all()));
        const toPincodes = new Set(@json($toPincodes->pluck('pincode')->values()->all()));
        const pincodeMetaMap = @json(
            collect($fromPincodes)
                ->merge($toPincodes)
                ->keyBy('pincode')
                ->map(fn($item) => ['city' => $item['city'] ?? '', 'state' => $item['state'] ?? ''])
                ->all()
        );
        let calculateTimer = null;

        function syncCodState() {
            const isCod = $('#cod_toggle').is(':checked');
            $('#payment_type').val(isCod ? 'cod' : 'prepaid');
            $('#cod_amount').val(isCod ? ($('#invoice_amount').val() || '0') : '0');
        }

        function validateActivePincodes() {
            const pickup = ($('#pickup_code').val() || '').trim();
            const delivery = ($('#delivery_code').val() || '').trim();

            if (!/^\d{6}$/.test(pickup) || !/^\d{6}$/.test(delivery)) {
                return { valid: false, message: 'Please enter valid 6-digit pickup and delivery pincodes.' };
            }

            if (!fromPincodes.has(pickup)) {
                return { valid: false, message: 'Pickup pincode is not active for this company.' };
            }
            if (!toPincodes.has(delivery)) {
                return { valid: false, message: 'Delivery pincode is not active for this company.' };
            }

            return { valid: true, message: '' };
        }

        function setPincodeLocationText(inputSelector, targetSelector) {
            const pin = ($(inputSelector).val() || '').trim();
            if (!/^\d{6}$/.test(pin)) {
                $(targetSelector).text('');
                return;
            }

            if (pincodeMetaMap[pin]) {
                const city = pincodeMetaMap[pin].city || '';
                const state = pincodeMetaMap[pin].state || '';
                $(targetSelector).text([city, state].filter(Boolean).join(', '));
                return;
            }

            $.get("{{ url('rate/pincode-check') }}/" + pin)
                .done(function (res) {
                    if (res.status === 'success') {
                        $(targetSelector).text([res.city || '', res.state || ''].filter(Boolean).join(', '));
                    } else {
                        $(targetSelector).text('');
                    }
                })
                .fail(function () {
                    $(targetSelector).text('');
                });
        }

        function runRateCalculation() {
            if (!hasActivePair) {
                $result.html('');
                return;
            }

            const activeValidation = validateActivePincodes();
            if (!activeValidation.valid) {
                $result.html('');
                $message.text(activeValidation.message);
                return;
            }

            syncCodState();
            $message.text('Calculating...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                success: function (res) {
                    if (res.status === 'success') {
                        $result.html(res.view).show();
                        $message.text('');
                    } else {
                        $result.html('');
                        $message.text(res.msg || 'No rates found.');
                    }
                },
                error: function (xhr) {
                    let msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }
                    $result.html('');
                    $message.text(msg);
                }
            });
        }

        function scheduleCalculation() {
            clearTimeout(calculateTimer);
            calculateTimer = setTimeout(runRateCalculation, 350);
        }

        $('.auto-calc-field').on('input change', scheduleCalculation);
        $('#cod_toggle').on('change', scheduleCalculation);
        $('#pickup_code').on('input change', function () {
            setPincodeLocationText('#pickup_code', '#pickup_pin_location');
        });
        $('#delivery_code').on('input change', function () {
            setPincodeLocationText('#delivery_code', '#delivery_pin_location');
        });

        setPincodeLocationText('#pickup_code', '#pickup_pin_location');
        setPincodeLocationText('#delivery_code', '#delivery_pin_location');

        if (hasActivePair) {
            runRateCalculation();
        }
    });
</script>
@endpush
