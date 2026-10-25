@php
    $primaryCourier = collect($couriers ?? [])
        ->filter(fn ($row) => is_array($row))
        ->sortBy(fn ($row) => (float) ($row['total_charges'] ?? PHP_FLOAT_MAX))
        ->first();
@endphp

@if(!$primaryCourier)
    <div class="single-courier-empty">No rate found for selected company.</div>
@else
    @php
        $responseData = is_array($primaryCourier['responseData'] ?? null) ? $primaryCourier['responseData'] : [];

        $originZone = trim((string) ($primaryCourier['origin_zone'] ?? ($responseData['origin_zone'] ?? '')));
        $destinationZone = trim((string) ($primaryCourier['destination_zone'] ?? ($responseData['destination_zone'] ?? '')));
        $zoneLabel = trim((string) ($primaryCourier['zone'] ?? ($responseData['zone'] ?? '')));
        $pickupZone = trim((string) ($calculationContext['pickup_zone'] ?? ''));
        $deliveryZone = trim((string) ($calculationContext['delivery_zone'] ?? ''));

        $isGenericZoneLabel = function (?string $value): bool {
            $normalized = strtolower(trim((string) $value));
            if ($normalized === '') {
                return true;
            }

            return in_array($normalized, [
                'within city',
                'within_city',
                'within-city',
                'within state',
                'within_state',
                'within-state',
                'metro to metro',
                'metro_to_metro',
                'rest of india',
                'roi',
            ], true);
        };

        $pickZoneValue = function (array $candidates) use ($isGenericZoneLabel): string {
            foreach ($candidates as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '' && !$isGenericZoneLabel($candidate)) {
                    return $candidate;
                }
            }
            foreach ($candidates as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
            return 'N/A';
        };

        $routingFrom = $pickZoneValue([$pickupZone, $originZone, $zoneLabel]);
        $routingTo = $pickZoneValue([$deliveryZone, $destinationZone, $zoneLabel, $pickupZone]);

        if ($isGenericZoneLabel($routingTo) && $routingFrom !== 'N/A') {
            $routingTo = $routingFrom;
        }

        $adminBaseFreight = (float) ($primaryCourier['freight_charges'] ?? ($responseData['base_freight'] ?? 0));
        $chargeableWeight = (float) ($primaryCourier['chargeable_weight'] ?? ($responseData['chargeable_weight'] ?? 0));
        $actualWeight = (float) ($calculationContext['actual_weight'] ?? 0);
        $volumetricWeight = (float) ($calculationContext['volumetric_weight'] ?? 0);
        $baseRatePerKg = $chargeableWeight > 0 ? ($adminBaseFreight / $chargeableWeight) : 0;

        $codCharges = (float) ($primaryCourier['cod_charges'] ?? ($responseData['cod_charges'] ?? 0));
        $taxAmount = (float) ($primaryCourier['tax'] ?? ($responseData['tax'] ?? 0));
        $grandTotal = (float) ($primaryCourier['total_charges'] ?? ($responseData['total_charges'] ?? 0));
        $adminSubtotal = max(0, $grandTotal - $taxAmount);

        $overheads = collect($responseData['overhead_details'] ?? [])
            ->filter(fn ($charge) => is_array($charge) && empty($charge['is_info']))
            ->values();

        $usedOverheadIndexes = [];
        $extractCharge = function (array $keywords) use ($overheads, &$usedOverheadIndexes): float {
            foreach ($overheads as $idx => $charge) {
                if (in_array($idx, $usedOverheadIndexes, true)) {
                    continue;
                }
                $name = strtolower((string) ($charge['name'] ?? ''));
                foreach ($keywords as $keyword) {
                    if ($keyword !== '' && strpos($name, strtolower($keyword)) !== false) {
                        $usedOverheadIndexes[] = $idx;
                        return round((float) ($charge['value'] ?? 0), 2);
                    }
                }
            }
            return 0.0;
        };

        $awbCharges = $extractCharge(['awb']);
        $firstMileCost = $extractCharge(['first mile']);
        $greenCharges = $extractCharge(['green']);
        $fuelSurcharge = $extractCharge(['fuel']);
        $rovCarrier = $extractCharge(['rov carrier', 'insurance carrier', 'carrier ins']);

        $otherCharges = $overheads
            ->filter(function ($charge, $idx) use ($usedOverheadIndexes) {
                return !in_array($idx, $usedOverheadIndexes, true);
            })
            ->values();
    @endphp

    <style>
        .single-admin-card {
            background: var(--calc-panel-bg, #f3f5f8);
            border: 1px solid var(--calc-panel-border, #d7e2ee);
            border-radius: 12px;
            padding: 16px 18px;
            font-family: 'Outfit', 'Inter', sans-serif;
        }
        .single-admin-card h4 {
            margin: 0 0 12px;
            font-size: 20px;
            font-weight: 800;
            color: #102847;
            padding-bottom: 8px;
            border-bottom: 1px solid #d9e3ef;
        }
        .single-admin-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            padding: 7px 0;
            border-top: 1px solid #dde6f0;
        }
        .single-admin-row:first-of-type {
            border-top: none;
            padding-top: 0;
        }
        .single-admin-row .label {
            color: #617894;
            font-weight: 600;
        }
        .single-admin-row .value {
            color: #103468;
            font-weight: 700;
            text-align: right;
        }
        .single-admin-row .value.emphasis {
            color: #f05a28;
        }
        .single-route-hint {
            color: #7488a3;
            font-size: 11px;
            font-weight: 600;
            margin-top: -2px;
            margin-bottom: 6px;
        }
        .single-admin-group-divider {
            border-top: 1px solid #d3dfec;
            margin: 8px 0;
        }
        .single-admin-row.total .label,
        .single-admin-row.total .value {
            font-weight: 800;
        }
        .single-admin-grand {
            margin-top: 10px;
            background: #dce3ec;
            border: 1px solid #ced8e5;
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            color: #102847;
        }
        .single-admin-grand .amount {
            font-size: 35px;
            color: #102847;
            font-weight: 900;
        }
        .single-courier-empty {
            border: 1px dashed #c7d4e6;
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            color: #5f7394;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .single-admin-card h4 { font-size: 18px; }
            .single-admin-grand .amount { font-size: 24px; }
        }
    </style>

    <div class="single-admin-card">
            <h4>Admin Cost Calculation (Base)</h4>

            <div class="single-admin-row">
                <span class="label">Routing:</span>
                <span class="value">{{ $routingFrom }}-{{ $routingTo }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">Base Rate:</span>
                <span class="value">&#8377;{{ number_format($baseRatePerKg, 2) }} /kg</span>
            </div>
            <div class="single-admin-row">
                <span class="label">Volumetric Wt:</span>
                <span class="value">{{ number_format($volumetricWeight, 2) }} kg</span>
            </div>
            <div class="single-admin-row">
                <span class="label">Chargeable Wt:</span>
                <span class="value emphasis">{{ number_format(max($chargeableWeight, $actualWeight), 2) }} kg</span>
            </div>

            <div class="single-admin-group-divider"></div>

            <div class="single-admin-row">
                <span class="label">Admin Base Freight:</span>
                <span class="value">&#8377;{{ number_format($adminBaseFreight, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">AWB Charges:</span>
                <span class="value">&#8377;{{ number_format($awbCharges, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">First Mile Cost:</span>
                <span class="value">&#8377;{{ number_format($firstMileCost, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">Green Charges:</span>
                <span class="value">&#8377;{{ number_format($greenCharges, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">Fuel Surcharge:</span>
                <span class="value">&#8377;{{ number_format($fuelSurcharge, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">ROV Carrier (Ins.):</span>
                <span class="value">&#8377;{{ number_format($rovCarrier, 2) }}</span>
            </div>
            @if($codCharges > 0)
                <div class="single-admin-row">
                    <span class="label">COD Charges:</span>
                    <span class="value">&#8377;{{ number_format($codCharges, 2) }}</span>
                </div>
            @endif
            @foreach($otherCharges as $charge)
                <div class="single-admin-row">
                    <span class="label">{{ $charge['name'] ?? 'Other Charge' }}:</span>
                    <span class="value">&#8377;{{ number_format((float) ($charge['value'] ?? 0), 2) }}</span>
                </div>
            @endforeach

            <div class="single-admin-group-divider"></div>

            <div class="single-admin-row total">
                <span class="label">Admin Subtotal:</span>
                <span class="value">&#8377;{{ number_format($adminSubtotal, 2) }}</span>
            </div>
            <div class="single-admin-row">
                <span class="label">GST:</span>
                <span class="value">&#8377;{{ number_format($taxAmount, 2) }}</span>
            </div>

            <div class="single-admin-grand">
                <span>ADMIN GRAND TOTAL</span>
                <span class="amount">&#8377;{{ number_format($grandTotal, 2) }}</span>
            </div>
    </div>
@endif

