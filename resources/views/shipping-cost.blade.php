<style>
/* Filters Top Bar */
.results-filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 18px;
}

.filter-select {
    background: #ffffff;
    border: 1.5px solid #dbe5f1;
    border-radius: 6px;
    height: 28px;
    padding: 3px 12px;
    font-size: 10px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    width: fit-content;
}

.filter-empty-state {
    display: none;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    color: #64748b;
    border-radius: 16px;
    padding: 18px;
    text-align: center;
    font-size: 14px;
    font-weight: 700;
}

/* Courier Card */
.courier-result-card {
    background: #ffffff;
    border: 2.5px solid #dbe5f1;
    border-radius: 16px;
    margin-top: 24px;
    padding: 16px;
    margin-bottom: 40px;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 14px;
    align-items: start;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    box-shadow: 0 6px 18px rgba(32, 65, 110, 0.04);
}

.courier-result-card:hover {
    border-color: #bcd0eb;
}

.courier-result-card.selected {
    border-color: #3d6dd8;
    box-shadow: 0 0 0 1px rgba(61, 109, 216, 0.08);
}

.card-radio-circle {
    width: 18px;
    height: 18px;
    border: 2px solid #c8d4e5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 4px;
}

@media(max-width : 575px){
.card-radio-circle {
    margin-top: 2px;
}
}

.courier-result-card.selected .card-radio-circle {
    border-color: #2958c7;
}

.card-radio-circle::after {
    content: '';
    width: 8px;
    height: 8px;
    background: #2958c7;
    border-radius: 50%;
    display: none;
}

.courier-result-card.selected .card-radio-circle::after {
    display: block;
}

.courier-main-info {
    min-width: 0;
}

.name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.courier-brand {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    width: 100%;
}

.meta-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.courier-rating {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    color: #64748B;
}

.courier-mode-info {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #64748B;
    font-size: 10px;
    font-weight: 600;
}

.courier-mode-info i {
    font-size: 13px;
}

.courier-mode-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #74829a;
    font-size: 12px;
    font-weight: 700;
}

.courier-mode-badge i {
    font-size: 13px;
}

.service-flow {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.service-chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 7px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid transparent;
}

.service-labels {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.service-labels::before {
    content: "";
    width: 0;
    height: 0;
}

.shipping_cost{
    background: white;
    border: 1.5px solid var(--panel-border);
        border-radius: 16px;
}

.serv-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid transparent;
}

.service-labels .serv-badge + .serv-badge {
    position: relative;
    margin-left: 18px;
}

.service-labels .serv-badge + .serv-badge::before {
    content: "\F054";
    font-family: "Material Design Icons";
    position: absolute;
    left: -17px;
    top: 50%;
    transform: translateY(-50%);
    color: #c3cfdf;
    font-size: 12px;
}

.service-chip.is-door {
    background: #eefcf1;
    border-color: #bde8c7;
    color: #1c8f45;
}

.service-chip.is-self {
    background: #fff3e8;
    border-color: #ffd3b1;
    color: #e3701a;
}

.serv-blue {
    background: #eefcf1;
    border-color: #bde8c7;
    color: #1c8f45;
}

.serv-violet {
    background: #eefcf1;
    border-color: #bde8c7;
    color: #1c8f45;
}

.serv-badge.is-door {
    background: #eefcf1;
    border-color: #bde8c7;
    color: #1c8f45;
}

.serv-badge.is-self {
    background: #fff3e8;
    border-color: #ffd3b1;
    color: #e3701a;
}

.service-arrow {
    color: #c3cfdf;
    font-size: 14px;
    line-height: 1;
}

.transit-info {
    font-size: 10px;
    font-weight: 500;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 3px;
    letter-spacing: 0.5px;
}
/* Right Side Price */
.price-section {
    text-align: right;
    min-width: 170px;
}

.main-price {
        display: block;
    font-size: 20px;
    font-weight: 600;
    color: #234796;
    margin-bottom: 2px;
}

.per-kg-rate {
        font-size: 10px;
    font-weight: 500;
    color: #64748b;
}

.meta-stats {
    margin-top: 10px;
    display: flex;
    gap: 8px;
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.view-breakup-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #274da2;
    text-decoration: none;
    transition: color 0.2s;
}

.view-breakup-link:hover {
    color: #274da2;
}

.metadata-pill {
    background: #f8fbff;
    border: 1px solid #d7e4f1;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 8px;
    font-weight: 600;
    color: #1e293b;
}

/* Inline Breakup */
.inline-breakup-container {
    grid-column: 1 / -1;
    margin-top: 2px;
    padding-top: 12px;
    border-top: 1px solid #edf2f7;
    display: none;
}

.breakup-table {
    width: 100%;
    background: #f8fbff;
    border-radius: 12px;
    padding: 15px;
}

.breakup-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13px;
}

.breakup-label { color: #64748b; font-weight: 600; }
.breakup-value { color: #1a202c; font-weight: 800; }

.gst-row {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed #cbd5e0;
    font-weight: 900;
    font-size: 14px;
}

.inline-breakup-container.is-b2c .primary-breakup-col .extra-breakup-row,
.inline-breakup-container.is-b2c .secondary-breakup-col {
    display: none;
}

.inline-breakup-container.is-b2c .primary-breakup-col {
    flex: 0 0 100%;
    max-width: 100%;
}

/* Quality Label */
.quality-marker-group {
    position: absolute;
    top: -25px;
    right: 13px;
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: flex-end;
}

@media(max-width : 575px){
    .quality-marker-group{
            top: -23px;
    }
}

.quality-marker {
    background: #22C55E;
    color: #fff;
    padding: 5px 10px;
    border-radius: 6px 6px 0px 0px;
    font-size: 9px;
    font-weight: 600;
    text-decoration: none;
    display: none;
    letter-spacing: 0.04em;
}

.quality-marker.smart-select-marker {
    display: inline-flex;
    background: #2958c7;
}

@media (max-width: 991px) {
    .results-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .courier-result-card {
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 3px;
    }

    .price-section {
        grid-column: auto;
        text-align: right;
        min-width: auto;
    }

    .meta-stats {
        justify-content: flex-end;
    }

    .view-breakup-link {
        margin-top: 8px;
    }
}

@media (max-width: 520px) {
    .results-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .filter-select {
        min-height: 36px;
        padding: 7px 10px;
        font-size: 11px;
    }

    .courier-result-card {
        padding: 11px;
    }

    .courier-brand {
        font-size: 13px;
    }

    .main-price {
        font-size: 16px;
    }

    .courier-rating,
    .courier-mode-info,
    .transit-info,
    .per-kg-rate,
    .view-breakup-link {
        font-size: 11px;
    }

    .metadata-pill {
        font-size: 9px;
    }

    .quality-marker {
        font-size: 9px;
        padding: 4px 8px;
    }
}
.new-grid-list{
    padding: 20px;
    border-bottom: 1.5px solid var(--panel-border);
}

.courier-results-list{
    padding: 20px;
}
</style>

@php
    $carrierOptions = collect($couriers)
        ->map(fn ($courier) => trim((string) ($courier['shipping_company_name'] ?? $courier['courier_name'] ?? '')))
        ->filter()
        ->unique(fn ($name) => strtolower($name))
        ->values();

    $modeOptions = collect(['air', 'surface', 'bus', 'railway']);
@endphp

<!-- Filters -->
<div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3 items-center new-grid-list">
    <select class="filter-select" id="sort-filter">
        <option value="lowest_price" selected>Lowest Price</option>
        <option value="highest_price">Highest Price</option>
        <option value="fastest_eta">Fastest ETA</option>
    </select>
    <select class="filter-select" id="carrier-filter">
        <option value="all">All Carriers</option>
        @foreach ($carrierOptions as $carrierOption)
            <option value="{{ strtolower($carrierOption) }}">{{ $carrierOption }}</option>
        @endforeach
    </select>
    <!-- <select class="filter-select" id="mode-filter">
        <option value="all">All Mode</option>
        @foreach ($modeOptions as $modeOption)
            <option value="{{ $modeOption }}">{{ ucfirst($modeOption) }}</option>
        @endforeach
    </select>
    <select class="filter-select" id="ops-filter">
        <option value="all">All Ops</option>
        <option value="door_pickup">Door Pickup</option>
        <option value="self_drop">Self Drop</option>
        <option value="door_delivery">Door Delivery</option>
        <option value="self_pickup">Self Pickup</option>
    </select> -->
</div>

<!-- List -->
<div class="courier-results-list">
    @foreach ($couriers as $index => $courier)
    @php
        $packageType = (int) ($courier['type_of_package'] ?? ($courier['responseData']['type_of_package'] ?? 0));
        $isB2b = $packageType === 2 || stripos((string) ($courier['courier_name'] ?? ''), 'B2B') !== false;
        if ($isB2b) continue;

        $pickupService = strtolower((string) ($courier['pickup_service'] ?? 'door'));
        $deliveryService = strtolower((string) ($courier['delivery_service'] ?? 'door'));
        $freightMode = strtolower((string) ($courier['freight_mode'] ?? 'surface'));
        $freightModeLabel = $freightMode === 'air'
            ? 'Air'
            : ($freightMode === 'bus' ? 'Bus' : ($freightMode === 'railway' ? 'Railway' : 'Surface'));
        $freightModeIcon = $freightMode === 'air'
            ? 'mdi-airplane'
            : ($freightMode === 'bus' ? 'mdi-bus' : ($freightMode === 'railway' ? 'mdi-train' : 'mdi-truck-delivery'));
        $routeStateLabel = trim((string) ($courier['pickup_state'] ?? ($courier['responseData']['pickup_state'] ?? '')));
        $routeDestinationStateLabel = trim((string) ($courier['delivery_state'] ?? ($courier['responseData']['delivery_state'] ?? '')));
        $routeLabel = ($routeStateLabel !== '' && $routeDestinationStateLabel !== '')
            ? ($routeStateLabel . ' to ' . $routeDestinationStateLabel)
            : '';
        $originZoneLabel = trim((string) ($courier['origin_zone'] ?? ($courier['responseData']['origin_zone'] ?? '')));
        $destinationZoneLabel = trim((string) ($courier['destination_zone'] ?? ($courier['responseData']['destination_zone'] ?? '')));
        $zoneLabel = trim((string) ($courier['zone'] ?? ''));
        $zoneDisplayLabel = ($originZoneLabel !== '' && $destinationZoneLabel !== '')
            ? ($originZoneLabel . '-' . $destinationZoneLabel)
            : ($destinationZoneLabel !== '' ? $destinationZoneLabel : ($zoneLabel !== '' ? $zoneLabel : 'N/A'));
        $selectionModeValue = strtolower(str_replace(' ', '_', trim((string) ($courier['selection_mode'] ?? ($courier['responseData']['selection_mode'] ?? '')))));
        $isSmartSelect = $selectionModeValue === 'smart_select';
        $paymentTypeValue = strtolower(trim((string) ($courier['payment_type'] ?? ($courier['responseData']['payment_type'] ?? 'prepaid'))));
        $isCodSelected = in_array($paymentTypeValue, ['cod', '2'], true);
        $shippingTimeText = trim((string) ($courier['shipping_time'] ?? ''));
        $pickupServiceLabel = $pickupService === 'pickup' ? 'Self Drop' : 'Door Pickup';
        $deliveryServiceLabel = $deliveryService === 'pickup' ? 'Self Pickup' : 'Door Delivery';
        $etaSortValue = 999999;
        if (preg_match('/(\d+(?:\.\d+)?)/', $shippingTimeText, $etaMatches)) {
            $etaSortValue = (float) $etaMatches[1];
        }
    @endphp
    <div class="courier-result-card"
            data-name="{{ $courier['courier_name'] }}"
            data-price-value="{{ (float) ($courier['total_charges'] ?? 0) }}"
            data-carrier="{{ strtolower((string) ($courier['shipping_company_name'] ?? $courier['courier_name'] ?? '')) }}"
            data-mode="{{ $freightMode }}"
            data-pickup-service="{{ $pickupService }}"
            data-delivery-service="{{ $deliveryService }}"
            data-shipping-time="{{ strtolower($shippingTimeText) }}"
            data-eta-sort="{{ $etaSortValue }}"
            data-price="₹{{ number_format((float) ($courier['total_charges'] ?? 0), 2) }}"
            onclick="selectCourier(this)">

        <div class="quality-marker-group">
            <div class="quality-marker lowest-price-marker">LOWEST PRICE</div>
            @if($isSmartSelect)
            <div class="quality-marker smart-select-marker">SMART SELECT</div>
            @endif
        </div>



        <div class="card-radio-circle"></div>
        <div class="courier-main-info">
            <div class="name-row">
                <span class="courier-brand">{{ $courier['courier_name'] }}</span>
            </div>

            <div class="meta-row">
                <span class="courier-rating"> <span style="color: #e5a100;"> &#9733; </span> 4.{{ 5 + $index % 5 }}</span>
                <span class="courier-mode-info"><i class="mdi {{ $freightModeIcon }}"></i>{{ $freightModeLabel }}</span>
            </div>

            <div class="service-labels">
                <span class="serv-badge {{ $pickupService === 'pickup' ? 'is-self' : 'is-door' }}">{{ $pickupServiceLabel }}</span>
                <span class="serv-badge {{ $deliveryService === 'pickup' ? 'is-self' : 'is-door' }}">{{ $deliveryServiceLabel }}</span>
            </div>

            <div class="transit-info">
                <i class="mdi mdi-clock-outline"></i> Transit: {{ $courier['shipping_time'] ?? 'Standard service' }}
            </div>
        </div>

        <div class="price-section">
            @php
                $totalChargeAmount = (float) ($courier['total_charges'] ?? 0);
                $freightChargeAmount = (float) ($courier['freight_charges'] ?? 0);
                $chargeableWeight = (float) ($courier['chargeable_weight'] ?? 0);
                $breakupTaxAmount = (float) ($courier['tax'] ?? ($courier['responseData']['tax'] ?? ($courier['responseData']['gst'] ?? 0)));
                if ($breakupTaxAmount <= 0 && $totalChargeAmount > 0) {
                    $breakupTaxAmount = round($totalChargeAmount * 0.18 / 1.18, 2);
                }

                $codChargeAmount = (float) ($courier['cod_charges'] ?? ($courier['responseData']['cod_charges'] ?? ($courier['responseData']['CODCharges'] ?? 0)));
                if ($codChargeAmount <= 0 && $totalChargeAmount > 0) {
                    $codChargeAmount = max(0, round($totalChargeAmount - $freightChargeAmount - $breakupTaxAmount, 2));
                }

                $perKgAmount = $chargeableWeight > 0
                    ? ($freightChargeAmount / $chargeableWeight)
                    : 0;
            @endphp
            <span class="main-price">₹{{ number_format((float) ($courier['total_charges'] ?? 0), 2) }}</span>
            <span class="per-kg-rate">@₹{{ number_format($perKgAmount, 2) }}/Kg</span>

            <div class="meta-stats">
                <span class="metadata-pill">Wt: {{ $courier['chargeable_weight'] ?? '0' }}Kg</span>
                @if($isB2b)
                <span class="metadata-pill">{{ $zoneDisplayLabel }}</span>
                @endif
            </div>

            <a href="javascript:void(0)" class="view-breakup-link" onclick="toggleInlineBreakup(this, event)">
                View Breakup
            </a>
        </div>

        <!-- Inline Breakup Content -->
        <div class="inline-breakup-container w-100 {{ $isB2b ? 'is-b2b' : 'is-b2c' }}">
            <div class="breakup-table">
                <div class="row g-4">
                    <div class="{{ $isB2b ? 'col-6' : 'col-12' }} primary-breakup-col">
                        <div class="breakup-row"><span class="breakup-label">Shipping Charges:</span> <span class="breakup-value">₹{{ number_format($courier['freight_charges'] ?? 0, 2) }}</span></div>
                        @if($isCodSelected || $codChargeAmount > 0)
                        <div class="breakup-row"><span class="breakup-label">COD Charges:</span> <span class="breakup-value">₹{{ number_format($codChargeAmount, 2) }}</span></div>
                        @endif
                        <div class="breakup-row extra-breakup-row"><span class="breakup-label">Rov Owner:</span> <span class="breakup-value">₹70.00</span></div>
                        <div class="breakup-row extra-breakup-row"><span class="breakup-label">AWB Charges:</span> <span class="breakup-value">₹100.00</span></div>
                    </div>
                    <div class="col-6 secondary-breakup-col">
                        <div class="breakup-row"><span class="breakup-label">First Mile Cost:</span> <span class="breakup-value">₹0.00</span></div>
                        <div class="breakup-row"><span class="breakup-label">Fuel Surcharge:</span> <span class="breakup-value">₹{{ number_format(($courier['fuel_surcharge'] ?? 0), 2) }}</span></div>
                    </div>
                </div>
                <div class="gst-row d-flex justify-content-between">
                    <span>GST (18%):</span>
                    <span>₹{{ number_format($breakupTaxAmount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="filter-empty-state" id="courier-filter-empty-state">No courier options match the selected filters.</div>

<script>
function selectCourier(element) {
    $('.courier-result-card').removeClass('selected');
    $(element).addClass('selected');
}

function toggleInlineBreakup(btn, event) {
    event.stopPropagation();
    const $card = $(btn).closest('.courier-result-card');
    const $breakup = $card.find('.inline-breakup-container');
    const isVisible = $breakup.is(':visible');

    // Toggle the breakup content
    $breakup.slideToggle(300);

    // Update button text
    $(btn).text(isVisible ? 'View Breakup' : 'Hide Breakup');
}

function matchesOpsFilter(card, opsValue) {
    const pickupService = (card.dataset.pickupService || '').toLowerCase();
    const deliveryService = (card.dataset.deliveryService || '').toLowerCase();

    if (opsValue === 'door_pickup') {
        return pickupService === 'door';
    }
    if (opsValue === 'self_drop') {
        return pickupService === 'pickup';
    }
    if (opsValue === 'door_delivery') {
        return deliveryService === 'door';
    }
    if (opsValue === 'self_pickup') {
        return deliveryService === 'pickup';
    }

    return true;
}

function applyCourierFilters() {
    const list = document.querySelector('.courier-results-list');
    if (!list) {
        return;
    }

    const sortValue = document.getElementById('sort-filter')?.value || 'lowest_price';
    const carrierValue = document.getElementById('carrier-filter')?.value || 'all';
    const modeValue = document.getElementById('mode-filter')?.value || 'all';
    const opsValue = document.getElementById('ops-filter')?.value || 'all';
    const emptyState = document.getElementById('courier-filter-empty-state');
    const cards = Array.from(list.querySelectorAll('.courier-result-card'));

    const filteredCards = cards.filter((card) => {
        const matchesCarrier = carrierValue === 'all' || (card.dataset.carrier || '') === carrierValue;
        const matchesMode = modeValue === 'all' || (card.dataset.mode || '') === modeValue;
        const matchesOps = opsValue === 'all' || matchesOpsFilter(card, opsValue);

        return matchesCarrier && matchesMode && matchesOps;
    });

    filteredCards.sort((firstCard, secondCard) => {
        const firstPrice = parseFloat(firstCard.dataset.priceValue || '0');
        const secondPrice = parseFloat(secondCard.dataset.priceValue || '0');
        const firstEta = parseFloat(firstCard.dataset.etaSort || '999999');
        const secondEta = parseFloat(secondCard.dataset.etaSort || '999999');

        if (sortValue === 'highest_price') {
            return secondPrice - firstPrice;
        }
        if (sortValue === 'fastest_eta') {
            if (firstEta === secondEta) {
                return firstPrice - secondPrice;
            }
            return firstEta - secondEta;
        }

        return firstPrice - secondPrice;
    });

    cards.forEach((card) => {
        card.style.display = 'none';
    });

    filteredCards.forEach((card) => {
        card.style.display = '';
        list.appendChild(card);
    });

    cards.forEach((card) => {
        const marker = card.querySelector('.lowest-price-marker');
        if (marker) {
            marker.style.display = 'none';
        }
    });

    if (filteredCards.length > 0) {
        const lowestVisiblePrice = Math.min(...filteredCards.map((card) => parseFloat(card.dataset.priceValue || '0')));
        filteredCards.forEach((card) => {
            const marker = card.querySelector('.lowest-price-marker');
            if (marker && parseFloat(card.dataset.priceValue || '0') === lowestVisiblePrice) {
                marker.style.display = 'block';
            }
        });
    }

    $('.courier-result-card.selected').removeClass('selected');
    if (filteredCards.length > 0) {
        $(filteredCards[0]).addClass('selected');
    }

    if (emptyState) {
        emptyState.style.display = filteredCards.length === 0 ? 'block' : 'none';
    }
}

function viewFreightBreakup(obj, event) {
    event.preventDefault();
    const $obj = $(obj);
    let courierDetail = $obj.attr('data-freight-charge');
    let data;
    try {
        data = JSON.parse(courierDetail);
    } catch (error) {
        toastrMsg('error', 'Something went wrong.');
        return;
    }
    $.post("{{ route('rate.freight-breakup') }}", {
            _token: "{{ csrf_token() }}",
            data: JSON.stringify(data)
        },
        function(res) {
            if (res.status == "success") {
                $('body').find('#modal-view-render').html(res.view);
                $('#freightBreakupModal').modal('show');
            } else {
                toastrMsg(res.status, res.msg);
            }
        }, 'Json');
}

document.querySelectorAll('#sort-filter, #carrier-filter, #mode-filter, #ops-filter').forEach((filterElement) => {
    filterElement.addEventListener('change', applyCourierFilters);
});

applyCourierFilters();
</script>
