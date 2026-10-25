<style>
#freightBreakupModal .modal-content {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

#freightBreakupModal .modal-header {
    background: #fff;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
}

#freightBreakupModal .modal-title {
    font-weight: 600;
    font-size: 16px;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#freightBreakupModal .close {
    background: transparent;
    border: none;
    color: #64748b;
    font-size: 20px;
    opacity: 0.7;
    padding: 0;
    margin: 0;
}

#freightBreakupModal .close:hover {
    opacity: 1;
}

#freightBreakupModal .modal-body {
    padding: 24px;
    background: #fff;
}

.freight-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.freight-item:last-child {
    border-bottom: none;
}

.label-text {
    font-size: 14px;
    color: #64748b;
}

.value-text {
    font-size: 14px;
    color: #0f172a;
    font-weight: 600;
}

.section-head {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 24px 0 8px 0;
}

.total-summary {
    margin-top: 24px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 14px;
    color: #1e293b;
    font-weight: 600;
}

.total-amount {
    font-size: 22px;
    font-weight: 700;
    color: #2563eb;
}

.disclaimer {
    margin-top: 20px;
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.6;
    text-align: center;
}
</style>

<div class="modal fade" id="freightBreakupModal" tabindex="-1" aria-labelledby="freightModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="freightModalLabel">Freight Breakup</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if(!empty($responseData['base_plan']))
                <div class="freight-item">
                    <span class="label-text">Base Plan</span>
                    <span class="value-text">{{ $responseData['base_plan'] }}</span>
                </div>
                @endif

                <div class="freight-item">
                    <span class="label-text">Chargeable Weight</span>
                    <span class="value-text">{{ number_format((float) ($responseData['chargeable_weight'] ?? 0), 2) }} kg(s)</span>
                </div>

                @php
                    $pickupService = strtolower((string) ($responseData['pickup_service'] ?? 'door'));
                    $deliveryService = strtolower((string) ($responseData['delivery_service'] ?? 'door'));
                    $odaCategory = trim((string) ($responseData['oda_charge_name'] ?? $responseData['oda_category'] ?? 'NONE'));
                    $odaCategory = $odaCategory !== '' ? $odaCategory : 'NONE';
                    $codEnabled = (int) ($responseData['cod_enabled'] ?? 1) === 1;
                    $paymentType = strtolower(trim((string) ($responseData['payment_type'] ?? 'prepaid')));
                    $isCodSelected = in_array($paymentType, ['cod', '2'], true);
                    $packageType = (int) ($responseData['type_of_package'] ?? 0);
                    $isB2b = $packageType === 2 || stripos((string) ($responseData['courier_name'] ?? ''), 'B2B') !== false;
                @endphp

                {{-- @if($isB2b)
                    <div class="section-head">Logistics Details</div>
                    <div class="freight-item">
                        <span class="label-text">Pickup Service</span>
                        <span class="value-text">{{ ucfirst($pickupService) }}</span>
                    </div>
                    <div class="freight-item">
                        <span class="label-text">Delivery Service</span>
                        <span class="value-text">{{ $deliveryService === 'pickup' ? 'Pickup' : ucfirst($deliveryService) }}</span>
                    </div>
                    <div class="freight-item">
                        <span class="label-text">ODA Category</span>
                        <span class="value-text">{{ $odaCategory }}</span>
                    </div>
                    <div class="freight-item">
                        <span class="label-text">COD Available</span>
                        <span class="value-text">{{ $codEnabled ? 'Yes' : 'No' }}</span>
                    </div>
                @endif --}}

                <div class="section-head">Charges Breakdown</div>

                @php
                    $baseFreight = (float) ($responseData['base_freight'] ?? ($responseData['freight_charges'] ?? 0));
                    $codCharge = (float) ($responseData['cod_charges'] ?? 0);
                    $taxAmount = (float) ($responseData['tax'] ?? 0);
                    $totalAmount = (float) ($responseData['total_charges'] ?? 0);
                    $visibleOverheadsTotal = 0;

                    if (!empty($responseData['overhead_details']) && is_array($responseData['overhead_details'])) {
                        foreach ($responseData['overhead_details'] as $overhead) {
                            $overheadName = (string) ($overhead['name'] ?? '');
                            $isChargeableWeight = stripos($overheadName, 'chargeable') !== false || stripos($overheadName, 'chargable') !== false;
                            if ($isB2b && !$isChargeableWeight) {
                                $visibleOverheadsTotal += (float) ($overhead['value'] ?? 0);
                            }
                        }
                    }

                    $residualUserCharge = max(0, round($totalAmount - ($baseFreight + $visibleOverheadsTotal + $codCharge + $taxAmount), 2));
                    $shippingChargesDisplay = $baseFreight + $residualUserCharge;
                @endphp

                <div class="freight-item">
                    <span class="label-text">Shipping Charges</span>
                    <span class="value-text">₹{{ number_format($shippingChargesDisplay, 2) }}</span>
                </div>

                {{-- @if($isB2b && !empty($responseData['overhead_details']))
                    @foreach($responseData['overhead_details'] as $overhead)
                        @php
                            $overheadName = (string) ($overhead['name'] ?? '');
                            $isChargeableWeight = stripos($overheadName, 'chargeable') !== false || stripos($overheadName, 'chargable') !== false;
                            $isUserExtraCharge = strcasecmp(trim($overheadName), 'User Extra Charge') === 0;
                        @endphp
                        @if(!$isChargeableWeight && !$isUserExtraCharge)
                        <div class="freight-item">
                            <span class="label-text">{{ $overheadName }}</span>
                            <span class="value-text">₹{{ number_format((float) ($overhead['value'] ?? 0), 2) }}</span>
                        </div>
                        @endif
                    @endforeach
                @endif --}}

                @if($isCodSelected || ((float)($responseData['cod_charges'] ?? 0)) > 0)
                <div class="freight-item">
                    <span class="label-text">COD charge</span>
                    <span class="value-text">₹{{ number_format(((float) ($responseData['cod_charges'] ?? 0)), 2) }}</span>
                </div>
                @endif

                @if(($responseData['tax'] ?? 0) > 0)
                <div class="freight-item">
                    <span class="label-text">GST</span>
                    <span class="value-text">₹{{ number_format(($responseData['tax'] ?? 0), 2) }}</span>
                </div>
                @endif

                <div class="total-summary">
                    <span class="total-label">Total Estimation</span>
                    <span class="total-amount">₹{{ number_format(($responseData['total_charges'] ?? 0), 2) }}</span>
                </div>

                <div class="disclaimer">
                    * These Freight Charges are for estimation only. The actual charges are subject to
                    change in lieu of other factors / special pricing.
                </div>
            </div>
        </div>
    </div>
</div>
