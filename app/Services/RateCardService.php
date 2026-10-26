<?php

namespace App\Services;

use App\Models\RateCardDetail;
use App\Models\RateCardMatrix;
use App\Models\ZoneSetting;
use App\Models\RateCard;
use App\Models\RateCardOtherCharge;
use App\Models\OtherChargeMaster;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyOdaCharge;
use App\Models\ShippingCompanyOpaCharge;
use App\Models\ShippingCompanyPincode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\CourierCommission;
use App\Models\UserCourierCommission;


class RateCardService
{
    private array $managedDirectionCache = [];

    private const GST_PERCENT = 18;

    private function getRequestWeight($request, $isOrder = 0): float
    {
        $weight = $request->weight ?? 0;
        if ($isOrder == 1)
            return (float) $weight;
        if (is_array($weight)) {
            $sum = 0;
            foreach ($weight as $w)
                $sum += (float) $w;
            return $sum;
        }
        return (float) $weight;
    }

    private function normalizePincodeValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim(trim((string) $value), "\"'");
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d+(\.0+)?$/', $value)) {
            $value = preg_replace('/\.0+$/', '', $value);
        }

        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function resolveRateCardId($user, int $typeOfPackage = 1)
    {
        $resolvedRateCardId = null;

        if (is_object($user) && method_exists($user, 'getAssignedRateCardId')) {
            $resolvedRateCardId = $user->getAssignedRateCardId($typeOfPackage);
        } else {
            $resolvedRateCardId = $user->rate_card_id ?? 0;
        }

        $normalizedRateCardId = is_numeric($resolvedRateCardId) ? (int) $resolvedRateCardId : $resolvedRateCardId;
        if ($normalizedRateCardId === 0 || $normalizedRateCardId === '0' || strtolower((string) $normalizedRateCardId) === 'all') {
            return 0;
        }

        $expectedType = $typeOfPackage === 2 ? 'b2b' : 'b2c';
        $rateCard = RateCard::query()
            ->select('id', 'rate_type')
            ->find($normalizedRateCardId);

        if (!$rateCard || $rateCard->rate_type !== $expectedType) {
            Log::warning('Falling back to all rate cards because assigned rate card is missing or mismatched.', [
                'user_id' => is_object($user) ? ($user->id ?? null) : null,
                'requested_rate_card_id' => $normalizedRateCardId,
                'expected_rate_type' => $expectedType,
                'actual_rate_type' => $rateCard->rate_type ?? null,
            ]);

            return 0;
        }

        return $normalizedRateCardId;
    }

    private function getChargeableWeight($request, int $isOrder = 0, array $otherCharges = []): float
    {
        $actualWeight = $this->getRequestWeight($request, $isOrder);
        $divisor = $this->getDivisorFromCharges($otherCharges, 5000);
        $volumetricWeight = $this->calculateVolumetricWeight($request, $divisor, $isOrder);
        $chargeableWeight = max((float) $actualWeight, (float) $volumetricWeight);

        $minWeight = null;
        foreach ($otherCharges as $charge) {
            $master = $charge->master;
            if (!$master)
                continue;
            $calcType = $master->calc_type ?? '';
            $masterName = strtolower((string) ($master->name ?? ''));
            if ($calcType === 'min_weight' || str_contains($masterName, 'minimum weight') || str_contains($masterName, 'min weight')) {
                $config = $charge->config ?? [];
                $candidate = $config['min'] ?? $config['value'] ?? $config['flat'] ?? null;
                if ($candidate !== null) {
                    $minWeight = max((float) $minWeight, (float) $candidate);
                }
            }
        }

        $isB2b = ($request->weight_order == 2 || $request->type_of_package == 2);
        $requestShippingCompanyId = (int) ($request->shipping_company_id ?? 0);

        if ($minWeight === null && $isB2b && $requestShippingCompanyId > 0) {
            $globalMin = OtherChargeMaster::where('is_active', 1)
                ->where('shipping_company_id', $requestShippingCompanyId)
                ->where(function ($q) {
                    $q->where('calc_type', 'min_weight')
                        ->orWhere('name', 'LIKE', '%minimum weight%')
                        ->orWhere('name', 'LIKE', '%min weight%');
                })
                ->get();

            foreach ($globalMin as $master) {
                $cfg = $master->default_config ?? [];
                $candidate = $cfg['min'] ?? $cfg['value'] ?? $cfg['flat'] ?? null;
                if ($candidate !== null) {
                    $minWeight = max((float) $minWeight, (float) $candidate);
                }
            }
        }

        if ($minWeight !== null && $chargeableWeight < $minWeight) {
            $chargeableWeight = (float) $minWeight;
        }

        return $chargeableWeight;
    }

    public function getRate($user, $shippingCompany, $request, $isOrder = 0, $extraData = [])
    {
        if (!$this->userCanAccessShippingCompany($user, $shippingCompany)) {
            return null;
        }


        $typeOfPackage = 1; // Force B2C as B2B is disabled
        $rateCardId = $this->resolveRateCardId($user, (int) $typeOfPackage);
        $isAll = ($rateCardId === 0 || $rateCardId === '0' || strtolower((string) $rateCardId) === 'all');
        $extraData = $this->withManagedPincodeMetadata($extraData, (int) $shippingCompany->id, $request);

        $results = [];

        if (!$this->passesManagedPincodeFilter((int) $shippingCompany->id, $request, $extraData)) {
            return null;
        }

        // Check active status in rate card
        if (!$isAll) {
            if ($typeOfPackage == 1) { // B2C
                $isActive = \App\Models\RateCardDetail::where('rate_card_id', $rateCardId)
                    ->where('shipping_company_id', $shippingCompany->id)
                    ->where('status', 1)
                    ->exists();
                if (!$isActive)
                    return null;
            } elseif ($typeOfPackage == 2) { // B2B
                $isActive = \App\Models\RateCardMatrix::where('rate_card_id', $rateCardId)
                    ->where('shipping_company_id', $shippingCompany->id)
                    ->where('status', 1)
                    ->exists();
                if (!$isActive)
                    return null;
            }
        }



        // Other Specialized API Rates (Usually B2C)
        if ($typeOfPackage == 1) {
            if ($shippingCompany->isShadowfax()) {
                $sf = $this->getShadowfaxRate($user, $shippingCompany, $request, $isOrder, $extraData);
                if ($sf)
                    $results = array_merge($results, $sf);
                return $results ?: null;
            }
        }

        /* B2B / Multiple Package Rates disabled
        if ($typeOfPackage == 2) {
            if ($isAll) {
                // Find all active B2B rate cards
                $b2bCards = RateCard::where('rate_type', 'b2b')->get();
                foreach ($b2bCards as $b2bCard) {
                    $b2bRates = $this->getB2BRate($user, $shippingCompany, $request, $b2bCard, $isOrder, $extraData);
                    if ($b2bRates)
                        $results = array_merge($results, $b2bRates);
                }
            } else {
                $rateCard = RateCard::find($rateCardId);
                if ($rateCard && $rateCard->rate_type === 'b2b') {
                    $b2bRates = $this->getB2BRate($user, $shippingCompany, $request, $rateCard, $isOrder, $extraData);
                    if ($b2bRates)
                        $results = array_merge($results, $b2bRates);
                }
            }
        }
        */

        // 3. Standard B2C Matrix Rates
        if ($typeOfPackage == 1) {
            $b2cRates = $this->getStandardMatrixRate($user, $shippingCompany, $request, $isOrder, $extraData);
            if ($b2cRates)
                $results = array_merge($results, $b2cRates);
        }

        if (empty($results)) {
            return null;
        }

        $results = $this->normalizeCourierResults($results, $request);

        return $this->enrichCourierResultsWithManagedPincodeData($results, (int) $shippingCompany->id, $request, $extraData);
    }

    public function getUnavailableRateMessage($user, $request, array $extraData = []): string
    {
        $typeOfPackage = (int) ($request->type_of_package ?? 1);
        $requestedCompanyId = (int) ($request->company_id ?? 0);
        // Explain only the accounts this user can actually quote with
        $shippingCompanies = ($user && $user->role === 'user')
            ? ShippingCompany::availableFor($user)
            : ShippingCompany::query()->whereStatus(1)->where('provider', '!=', 'other')->get(['id', 'name', 'provider']);
        if ($requestedCompanyId > 0) {
            $shippingCompanies = $shippingCompanies->where('id', $requestedCompanyId)->values();
        }

        $detailedReasons = [];
        $blockedPincodeMessages = [
            'pickup' => 'Pickup pincode ' . trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? '')) . ' is not serviceable.',
            'delivery' => 'Delivery pincode ' . trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? '')) . ' is not serviceable.',
            'cod' => 'COD is not available on delivery pincode ' . trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? '')) . '.',
        ];
        $blockReasons = [];

        if ($typeOfPackage !== 2) {
            $pickupCode = trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? ''));
            $deliveryCode = trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? ''));
            $minimumWeight = $this->getMinimumWeight($this->getRequestWeight($request, 0));
            $rateCardIds = $this->getRelevantRateCardIds($user, 'b2c');
            $hasManagedPincodeMatch = false;
            $hasZoneMatch = false;
            $pincodeInactive = true;

            foreach ($shippingCompanies as $shippingCompany) {
                $shippingCompanyId = (int) $shippingCompany->id;
                $shippingCompanyLabel = $shippingCompany->name ?: (string) $shippingCompanyId;
                $blockReason = $this->managedPincodeBlockReason($shippingCompanyId, $request, $extraData);
                if ($blockReason !== null) {
                    $blockReasons[] = $blockReason;
                    $detailedReasons[] = "Courier {$shippingCompanyLabel}: " . $blockedPincodeMessages[$blockReason];
                    continue;
                }

                $pincodeInactive = false;
                $hasManagedPincodeMatch = true;
                $zoneSettingId = $this->getZoneSettingId($pickupCode, $deliveryCode, $shippingCompanyId, $extraData);
                if (!$zoneSettingId) {
                    $detailedReasons[] = "Courier {$shippingCompanyLabel}: No zone match";
                    continue;
                }

                $hasZoneMatch = true;

                $rateExists = RateCardDetail::query()
                    ->where('shipping_company_id', $shippingCompanyId)
                    ->where('zone_setting_id', $zoneSettingId)
                    ->where('weight', $minimumWeight)
                    ->where('status', 1)
                    ->when(!empty($rateCardIds), function ($query) use ($rateCardIds) {
                        $query->whereIn('rate_card_id', $rateCardIds);
                    })
                    ->exists();

                if (!$rateExists) {
                    $detailedReasons[] = "Courier {$shippingCompanyLabel}: No rates for {$minimumWeight}kg";
                }
            }

            if ($pincodeInactive) {
                return !empty($blockReasons)
                    ? $blockedPincodeMessages[$blockReasons[0]]
                    : 'No courier is available for this route.';
            }
            if ($hasZoneMatch) {
                return !empty($detailedReasons)
                    ? 'Pincodes serviceable but no B2C rates configured. Details: ' . implode('; ', $detailedReasons)
                    : 'No rate found for this route and weight.';
            }
            if ($hasManagedPincodeMatch) {
                return 'Pincodes active but no B2C zone mapped. Details: ' . implode('; ', $detailedReasons);
            }

            return implode('; ', $detailedReasons) ?: 'This pincode is not serviceable.';
        }

        // B2B logic (similar enhancement)
        $hasManagedPincodeMatch = false;
        $hasB2bZoneMatch = false;
        $hasB2bRouteRate = false;

        foreach ($shippingCompanies as $shippingCompany) {
            $shippingCompanyLabel = $shippingCompany->name ?: (string) $shippingCompany->id;
            if (!$this->passesManagedPincodeFilter((int) $shippingCompany->id, $request, $extraData)) {
                $detailedReasons[] = "Courier {$shippingCompanyLabel}: Pincode inactive";
                continue;
            }

            $hasManagedPincodeMatch = true;

            $pickupCode = trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? ''));
            $deliveryCode = trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? ''));

            $originZoneId = $this->getB2BZoneId($pickupCode, (int) $shippingCompany->id, $deliveryCode, $extraData);
            $destZoneId = $this->getB2BZoneId($deliveryCode, (int) $shippingCompany->id, $pickupCode, $extraData);

            if (!$originZoneId || !$destZoneId) {
                $detailedReasons[] = "Courier {$shippingCompanyLabel}: No B2B zone match";
                continue;
            }

            $hasB2bZoneMatch = true;

            $rateCardIds = RateCard::query()
                ->where('rate_type', 'b2b')
                ->pluck('id');

            $routeRateExists = RateCardMatrix::query()
                ->whereIn('rate_card_id', $rateCardIds)
                ->where('shipping_company_id', $shippingCompany->id)
                ->where('from_zone_id', $originZoneId)
                ->where('to_zone_id', $destZoneId)
                ->where('status', 1)
                ->exists();

            if (!$routeRateExists) {
                $detailedReasons[] = "Courier {$shippingCompanyLabel}: No B2B route rate";
            } else {
                $hasB2bRouteRate = true;
            }
        }

        if ($hasB2bZoneMatch && !$hasB2bRouteRate) {
            return implode('; ', $detailedReasons) ?: 'Pincodes serviceable but no B2B rates.';
        }

        if ($hasManagedPincodeMatch && !$hasB2bZoneMatch) {
            return implode('; ', $detailedReasons) ?: 'Pincodes active but no B2B zone.';
        }

        return implode('; ', $detailedReasons) ?: 'This pincode is not serviceable.';

        if ($typeOfPackage !== 2) {
            $pickupCode = trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? ''));
            $deliveryCode = trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? ''));
            $minimumWeight = $this->getMinimumWeight($this->getRequestWeight($request, 0));
            $rateCardIds = $this->getRelevantRateCardIds($user, 'b2c');
            $hasManagedPincodeMatch = false;
            $hasZoneMatch = false;

            foreach ($shippingCompanies as $shippingCompany) {
                $shippingCompanyId = (int) $shippingCompany->id;
                if (!$this->passesManagedPincodeFilter($shippingCompanyId, $request, $extraData)) {
                    continue;
                }

                $hasManagedPincodeMatch = true;
                $zoneSettingId = $this->getZoneSettingId($pickupCode, $deliveryCode, $shippingCompanyId, $extraData);
                if (!$zoneSettingId) {
                    continue;
                }

                $hasZoneMatch = true;

                $rateExists = RateCardDetail::query()
                    ->where('shipping_company_id', $shippingCompanyId)
                    ->where('zone_setting_id', $zoneSettingId)
                    ->where('weight', $minimumWeight)
                    ->where('status', 1)
                    ->when(!empty($rateCardIds), function ($query) use ($rateCardIds) {
                        $query->whereIn('rate_card_id', $rateCardIds);
                    })
                    ->exists();

                if ($rateExists) {
                    return 'Rates should be available for this route. Please try again.';
                }
            }

            if ($hasZoneMatch) {
                return 'Pincodes are serviceable, but no B2C rate card is configured for this route or weight.';
            }

            if ($hasManagedPincodeMatch) {
                return 'Pincodes are active, but no B2C zone is mapped for this route.';
            }

            return 'This pincode is not serviceable.';
        }

        $hasManagedPincodeMatch = false;
        $hasB2bZoneMatch = false;
        $hasB2bRouteRate = false;

        foreach ($shippingCompanies as $shippingCompany) {
            if (!$this->passesManagedPincodeFilter((int) $shippingCompany->id, $request, $extraData)) {
                continue;
            }

            $hasManagedPincodeMatch = true;

            $pickupCode = trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? ''));
            $deliveryCode = trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? ''));

            $originZoneId = $this->getB2BZoneId($pickupCode, (int) $shippingCompany->id, $deliveryCode, $extraData);
            $destZoneId = $this->getB2BZoneId($deliveryCode, (int) $shippingCompany->id, $pickupCode, $extraData);

            if (!$originZoneId || !$destZoneId) {
                continue;
            }

            $hasB2bZoneMatch = true;

            $rateCardIds = RateCard::query()
                ->where('rate_type', 'b2b')
                ->pluck('id');

            $routeRateExists = RateCardMatrix::query()
                ->whereIn('rate_card_id', $rateCardIds)
                ->where('shipping_company_id', $shippingCompany->id)
                ->where('from_zone_id', $originZoneId)
                ->where('to_zone_id', $destZoneId)
                ->where('status', 1)
                ->exists();

            if ($routeRateExists) {
                $hasB2bRouteRate = true;
                break;
            }
        }

        if ($hasB2bZoneMatch && !$hasB2bRouteRate) {
            return 'Pincodes are serviceable, but no B2B rate card is configured for this route.';
        }

        if ($hasManagedPincodeMatch && !$hasB2bZoneMatch) {
            return 'Pincodes are active, but no B2B zone is mapped for this route.';
        }

        return 'This pincode is not serviceable.';
    }

    private function getRelevantRateCardIds($user, string $type): array
    {
        $normalizedType = strtolower(trim($type)) === 'b2b' ? 'b2b' : 'b2c';
        $typeOfPackage = $normalizedType === 'b2b' ? 2 : 1;
        $resolvedRateCardId = $this->resolveRateCardId($user, $typeOfPackage);

        if ($resolvedRateCardId === 0 || $resolvedRateCardId === '0' || strtolower((string) $resolvedRateCardId) === 'all') {
            return RateCard::query()
                ->where('rate_type', $normalizedType)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return [(int) $resolvedRateCardId];
    }

    private function passesManagedPincodeFilter(int $shippingCompanyId, $request, array $extraData = []): bool
    {
        return $this->managedPincodeBlockReason($shippingCompanyId, $request, $extraData) === null;
    }

    /**
     * Why Manage Pincode rules out this route for the account: 'pickup', 'delivery' or 'cod'; null when allowed.
     * Same rule as the Manage Pincode page and rate calculator.
     */
    private function managedPincodeBlockReason(int $shippingCompanyId, $request, array $extraData = []): ?string
    {
        if (!Schema::hasTable('shipping_company_pincodes')) {
            return null;
        }

        [$pickupCode, $deliveryCode] = $this->routePincodes($request);
        if ($pickupCode === '' || $deliveryCode === '') {
            return null;
        }

        if (!$this->isPincodeServiceable($shippingCompanyId, $pickupCode, 'from', $extraData)) {
            return 'pickup';
        }
        if (!$this->isPincodeServiceable($shippingCompanyId, $deliveryCode, 'to', $extraData)) {
            return 'delivery';
        }

        $paymentType = strtolower((string) ($request->payment_type ?? $request->order_type ?? ''));
        $isCodOrder = in_array($paymentType, ['cod', '2'], true);
        if ($isCodOrder && Schema::hasColumn('shipping_company_pincodes', 'cod_enabled')
            && !$this->isManagedPincodeCodAllowed($shippingCompanyId, $deliveryCode, $extraData)) {
            return 'cod';
        }

        return null;
    }

    private function routePincodes($request): array
    {
        $pickupCode = trim((string) (
            $request->pickup_code
            ?? $request->pickup_pincode
            ?? (is_object($request->warehouse ?? null) ? ($request->warehouse->zip_code ?? null) : null)
            ?? ''
        ));

        $deliveryCode = trim((string) (
            $request->delivery_code
            ?? $request->delivery_pincode
            ?? (is_object($request->customerAddress ?? null) ? ($request->customerAddress->zip_code ?? null) : null)
            ?? ''
        ));

        return [$pickupCode, $deliveryCode];
    }

    /**
     * Manage Pincode rule: an account with no rows for a direction serves every pincode; otherwise a pincode
     * is serviceable unless its row is set to a not-serviceable mode (drop / no_pickup, pickup / no_delivery).
     */
    public function isPincodeServiceable(int $shippingCompanyId, string $pincode, string $direction, array $extraData = []): bool
    {
        if ($shippingCompanyId <= 0 || !Schema::hasTable('shipping_company_pincodes')) {
            return $shippingCompanyId > 0;
        }

        $zipcode = $this->resolveManagedPincodeZipcode($pincode, $extraData);
        if (!$zipcode) {
            return false;
        }

        $cacheKey = $shippingCompanyId . ':' . $direction;
        if (!array_key_exists($cacheKey, $this->managedDirectionCache)) {
            $this->managedDirectionCache[$cacheKey] = ShippingCompanyPincode::where('shipping_company_id', $shippingCompanyId)
                ->where('direction', $direction)
                ->exists();
        }
        if (!$this->managedDirectionCache[$cacheKey]) {
            return true;
        }

        $serviceMode = ShippingCompanyPincode::where('shipping_company_id', $shippingCompanyId)
            ->where('pincode_id', $zipcode->id)
            ->where('direction', $direction)
            ->value('service_mode');

        return in_array((string) $serviceMode, ['', 'none', 'door'], true);
    }
    private function enrichCourierResultsWithManagedPincodeData(array $results, int $shippingCompanyId, $request, array $extraData = []): array
    {
        if (empty($results) || !$this->hasManagedPincodeMetaColumns()) {
            return $this->enrichCourierResultsWithShippingCompanyMeta($results, $shippingCompanyId);
        }

        $metadata = $this->getManagedPincodeMetadataFromContext($shippingCompanyId, $request, $extraData);

        foreach ($results as &$result) {
            if (!is_array($result)) {
                continue;
            }

            $result['pickup_service'] = $metadata['pickup_service'];
            $result['delivery_service'] = $metadata['delivery_service'];
            $result['pickup_state'] = $metadata['pickup_state'];
            $result['delivery_state'] = $metadata['delivery_state'];
            $result['origin_zone'] = $metadata['origin_zone'];
            $result['destination_zone'] = $metadata['destination_zone'];
            $result['oda_category'] = $metadata['oda_category'];
            $result['oda_charge_id'] = $metadata['oda_charge_id'];
            $result['oda_charge_name'] = $metadata['oda_charge_name'];
            $result['opa_category'] = $metadata['opa_category'];
            $result['opa_charge_id'] = $metadata['opa_charge_id'];
            $result['opa_charge_name'] = $metadata['opa_charge_name'];
            $result['cod_enabled'] = $metadata['cod_enabled'];
            $result['zone'] = $metadata['zone'];

            $responseData = is_array($result['responseData'] ?? null) ? $result['responseData'] : [];
            $responseData['pickup_service'] = $metadata['pickup_service'];
            $responseData['delivery_service'] = $metadata['delivery_service'];
            $responseData['pickup_state'] = $metadata['pickup_state'];
            $responseData['delivery_state'] = $metadata['delivery_state'];
            $responseData['origin_zone'] = $metadata['origin_zone'];
            $responseData['destination_zone'] = $metadata['destination_zone'];
            $responseData['oda_category'] = $metadata['oda_category'];
            $responseData['oda_charge_id'] = $metadata['oda_charge_id'];
            $responseData['oda_charge_name'] = $metadata['oda_charge_name'];
            $responseData['opa_category'] = $metadata['opa_category'];
            $responseData['opa_charge_id'] = $metadata['opa_charge_id'];
            $responseData['opa_charge_name'] = $metadata['opa_charge_name'];
            $responseData['cod_enabled'] = $metadata['cod_enabled'];
            $responseData['zone'] = $metadata['zone'];
            $result['responseData'] = $responseData;
        }

        return $this->enrichCourierResultsWithShippingCompanyMeta($results, $shippingCompanyId);
    }

    private function enrichCourierResultsWithShippingCompanyMeta(array $results, int $shippingCompanyId): array
    {
        $company = ShippingCompany::query()
            ->select('id')
            ->find($shippingCompanyId);

        $freightMode = 'surface';
        $carrierType = 'national';
        $selectionMode = 'smart_select';

        foreach ($results as &$result) {
            if (!is_array($result)) {
                continue;
            }

            $result['freight_mode'] = $freightMode;
            $result['carrier_type'] = $carrierType;
            $result['selection_mode'] = $selectionMode;

            $responseData = is_array($result['responseData'] ?? null) ? $result['responseData'] : [];
            $responseData['freight_mode'] = $freightMode;
            $responseData['carrier_type'] = $carrierType;
            $responseData['selection_mode'] = $selectionMode;
            $result['responseData'] = $responseData;
        }

        return $results;
    }

    private function userCanAccessShippingCompany($user, $shippingCompany): bool
    {
        if (!$user || !$shippingCompany) {
            return false;
        }

        return true;
    }

    private function getManagedPincodeRequestCodes($request): array
    {
        $pickupCode = $this->normalizePincodeValue(
            $request->pickup_code
            ?? $request->pickup_pincode
            ?? (is_object($request->warehouse ?? null) ? ($request->warehouse->zip_code ?? null) : null)
            ?? ''
        );

        $deliveryCode = $this->normalizePincodeValue(
            $request->delivery_code
            ?? $request->delivery_pincode
            ?? (is_object($request->customerAddress ?? null) ? ($request->customerAddress->zip_code ?? null) : null)
            ?? ''
        );

        return [$pickupCode, $deliveryCode];
    }

    private function getManagedPincodeMetadata(int $shippingCompanyId, string $pickupCode, string $deliveryCode, $request, array $extraData = []): array
    {
        $defaults = [
            'pickup_service' => 'door',
            'delivery_service' => 'door',
            'pickup_state' => null,
            'delivery_state' => null,
            'origin_zone' => null,
            'destination_zone' => null,
            'oda_category' => 'none',
            'oda_charge_id' => null,
            'oda_charge_name' => 'NONE',
            'oda_charge_per_kg' => 0,
            'oda_charge_min_charge' => 0,
            'opa_category' => 'none',
            'opa_charge_id' => null,
            'opa_charge_name' => 'NONE',
            'opa_charge_per_kg' => 0,
            'opa_charge_min_charge' => 0,
            'cod_enabled' => 1,
            'zone' => null,
        ];

        $pickupZipcode = $this->resolveManagedPincodeZipcode($pickupCode, $extraData);
        $deliveryZipcode = $this->resolveManagedPincodeZipcode($deliveryCode, $extraData);
        $pincodeSelectColumns = ['service_mode', 'oda_category', 'oda_charge_id', 'cod_enabled'];
        if (Schema::hasColumn('shipping_company_pincodes', 'opa_category')) {
            $pincodeSelectColumns[] = 'opa_category';
        }
        if (Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id')) {
            $pincodeSelectColumns[] = 'opa_charge_id';
        }

        $pickupRow = null;
        if ($pickupZipcode) {
            $pickupRow = ShippingCompanyPincode::query()
                ->select($pincodeSelectColumns)
                ->where('shipping_company_id', $shippingCompanyId)
                ->where('pincode_id', $pickupZipcode->id)
                ->where('direction', 'from')
                ->first();
        }

        $deliveryRow = null;
        if ($deliveryZipcode) {
            $deliveryRow = ShippingCompanyPincode::query()
                ->select($pincodeSelectColumns)
                ->where('shipping_company_id', $shippingCompanyId)
                ->where('pincode_id', $deliveryZipcode->id)
                ->where('direction', 'to')
                ->first();
        }

        $odaChargeId = null;
        if ($this->hasManagedPincodeOdaChargeSupport()) {
            $odaChargeId = (int) ($pickupRow->oda_charge_id ?? $deliveryRow->oda_charge_id ?? 0);
            $odaChargeId = $odaChargeId > 0 ? $odaChargeId : null;
        }

        $odaCharge = null;
        if ($odaChargeId) {
            $odaCharge = ShippingCompanyOdaCharge::query()
                ->select('id', 'name', 'per_kg', 'min_charge')
                ->where('shipping_company_id', $shippingCompanyId)
                ->where('id', $odaChargeId)
                ->first();
        }

        $legacyOdaCategory = strtolower((string) ($pickupRow->oda_category ?? $deliveryRow->oda_category ?? $defaults['oda_category']));
        $odaCategoryLabel = $odaCharge
            ? (string) $odaCharge->name
            : ($legacyOdaCategory === 'none' ? 'NONE' : strtoupper(str_replace('_', ' ', $legacyOdaCategory)));

        $opaChargeId = null;
        if ($this->hasManagedPincodeOpaChargeSupport()) {
            $opaChargeId = (int) ($pickupRow->opa_charge_id ?? $deliveryRow->opa_charge_id ?? 0);
            $opaChargeId = $opaChargeId > 0 ? $opaChargeId : null;
        }

        $opaCharge = null;
        if ($opaChargeId) {
            $opaCharge = ShippingCompanyOpaCharge::query()
                ->select('id', 'name', 'per_kg', 'min_charge')
                ->where('shipping_company_id', $shippingCompanyId)
                ->where('id', $opaChargeId)
                ->first();
        }

        $legacyOpaCategory = strtolower((string) ($pickupRow->opa_category ?? $deliveryRow->opa_category ?? $defaults['opa_category']));
        $opaCategoryLabel = $opaCharge
            ? (string) $opaCharge->name
            : ($legacyOpaCategory === 'none' ? 'NONE' : strtoupper(str_replace('_', ' ', $legacyOpaCategory)));
        $typeOfPackage = (int) ($request->type_of_package ?? $request->weight_order ?? 1);
        $originZoneHeading = null;
        $destinationZoneHeading = null;

        if ($typeOfPackage === 2) {
            $originZoneId = $this->getB2BZoneId($pickupCode, $shippingCompanyId, $deliveryCode, $extraData);
            $destinationZoneId = $this->getB2BZoneId($deliveryCode, $shippingCompanyId, $pickupCode, $extraData);

            if ($originZoneId) {
                $originZoneHeading = ZoneSetting::where('id', $originZoneId)->value('heading');
            }

            if ($destinationZoneId) {
                $destinationZoneHeading = ZoneSetting::where('id', $destinationZoneId)->value('heading');
            }
        } else {
            $destinationZoneHeading = $this->resolveManagedZoneHeading($shippingCompanyId, $pickupCode, $deliveryCode, $request ?? null, $extraData);
        }

        return [
            'pickup_service' => strtolower((string) ($pickupRow->service_mode ?? $defaults['pickup_service'])),
            'delivery_service' => strtolower((string) ($deliveryRow->service_mode ?? $defaults['delivery_service'])),
            'pickup_state' => $pickupZipcode->state ?? $defaults['pickup_state'],
            'delivery_state' => $deliveryZipcode->state ?? $defaults['delivery_state'],
            'origin_zone' => $originZoneHeading ?? $defaults['origin_zone'],
            'destination_zone' => $destinationZoneHeading ?? $defaults['destination_zone'],
            'oda_category' => $odaCategoryLabel,
            'oda_charge_id' => $odaChargeId,
            'oda_charge_name' => $odaCharge ? (string) $odaCharge->name : $odaCategoryLabel,
            'oda_charge_per_kg' => (float) ($odaCharge->per_kg ?? $defaults['oda_charge_per_kg']),
            'oda_charge_min_charge' => (float) ($odaCharge->min_charge ?? $defaults['oda_charge_min_charge']),
            'opa_category' => $opaCategoryLabel,
            'opa_charge_id' => $opaChargeId,
            'opa_charge_name' => $opaCharge ? (string) $opaCharge->name : $opaCategoryLabel,
            'opa_charge_per_kg' => (float) ($opaCharge->per_kg ?? $defaults['opa_charge_per_kg']),
            'opa_charge_min_charge' => (float) ($opaCharge->min_charge ?? $defaults['opa_charge_min_charge']),
            'cod_enabled' => (int) ($deliveryRow->cod_enabled ?? $pickupRow->cod_enabled ?? $defaults['cod_enabled']),
            'zone' => $this->resolveManagedZoneHeading($shippingCompanyId, $pickupCode, $deliveryCode, $request ?? null, $extraData) ?? $defaults['zone'],
        ];
    }

    private function withManagedPincodeMetadata(array $extraData, int $shippingCompanyId, $request): array
    {
        if (!isset($extraData['managed_pincode_metadata']) || !is_array($extraData['managed_pincode_metadata'])) {
            $extraData['managed_pincode_metadata'] = [];
        }

        if (isset($extraData['managed_pincode_metadata'][$shippingCompanyId])) {
            return $extraData;
        }

        [$pickupCode, $deliveryCode] = $this->getManagedPincodeRequestCodes($request);
        $extraData['managed_pincode_metadata'][$shippingCompanyId] = $this->getManagedPincodeMetadata(
            $shippingCompanyId,
            $pickupCode,
            $deliveryCode,
            $request,
            $extraData
        );

        return $extraData;
    }

    private function getManagedPincodeMetadataFromContext(int $shippingCompanyId, $request, array $extraData = []): array
    {
        $metadata = $extraData['managed_pincode_metadata'][$shippingCompanyId] ?? null;
        if (is_array($metadata)) {
            return $metadata;
        }

        [$pickupCode, $deliveryCode] = $this->getManagedPincodeRequestCodes($request);

        return $this->getManagedPincodeMetadata($shippingCompanyId, $pickupCode, $deliveryCode, $request, $extraData);
    }

    private function resolveManagedZoneHeading(int $shippingCompanyId, string $pickupCode, string $deliveryCode, $request = null, array $extraData = []): ?string
    {
        $typeOfPackage = (int) ($request->type_of_package ?? $request->weight_order ?? 1);

        if ($typeOfPackage === 2) {
            $zoneId = $this->getB2BZoneId($deliveryCode, $shippingCompanyId, $pickupCode, $extraData);
        } else {
            $zoneId = $this->getZoneSettingId($pickupCode, $deliveryCode, $shippingCompanyId, $extraData);
        }

        if (!$zoneId) {
            return null;
        }

        return ZoneSetting::where('id', $zoneId)->value('heading');
    }

    private function resolveManagedPincodeZipcode(string $pincode, array $extraData = [])
    {
        $pincode = $this->normalizePincodeValue($pincode);
        if ($pincode === '') {
            return null;
        }

        $zipcodes = $extraData['zipcodes'] ?? collect();
        $zipcode = $zipcodes[$pincode] ?? null;

        if ($zipcode) {
            return $zipcode;
        }

        return DB::table('zipcodes')
            ->select('id', 'pincode', 'city', 'state')
            ->where('pincode', $pincode)
            ->first();
    }

    private function hasManagedPincodeMetaColumns(): bool
    {
        return Schema::hasTable('shipping_company_pincodes')
            && Schema::hasColumn('shipping_company_pincodes', 'service_mode')
            && Schema::hasColumn('shipping_company_pincodes', 'oda_category')
            && Schema::hasColumn('shipping_company_pincodes', 'cod_enabled');
    }

    private function hasManagedPincodeOdaChargeSupport(): bool
    {
        return Schema::hasTable('shipping_company_oda_charges')
            && Schema::hasTable('shipping_company_pincodes')
            && Schema::hasColumn('shipping_company_pincodes', 'oda_charge_id');
    }

    private function hasManagedPincodeOpaChargeSupport(): bool
    {
        return Schema::hasTable('shipping_company_opa_charges')
            && Schema::hasTable('shipping_company_pincodes')
            && Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id');
    }

    // COD is off only where the delivery row explicitly disables it
    private function isManagedPincodeCodAllowed(int $shippingCompanyId, string $pincode, array $extraData = []): bool
    {
        $zipcode = $this->resolveManagedPincodeZipcode($pincode, $extraData);
        if (!$zipcode) {
            return false;
        }

        return !ShippingCompanyPincode::where('shipping_company_id', $shippingCompanyId)
            ->where('pincode_id', $zipcode->id)
            ->where('direction', 'to')
            ->where('cod_enabled', 0)
            ->exists();
    }
    private function normalizeCourierResults(array $results, $request): array
    {
        $defaultOrderId = $request->order_id ?? null;
        $paymentType = strtolower(trim((string) ($request->payment_type ?? 'prepaid')));
        $codAmount = (float) ($request->cod_amount ?? 0);
        static $activeCompanyIds = null;

        if ($activeCompanyIds === null) {
            $activeCompanyIds = ShippingCompany::where('status', 1)
                ->pluck('id')
                ->mapWithKeys(fn($id) => [(int) $id => true])
                ->all();
        }

        $normalizedResults = [];

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            if (!array_key_exists('order_id', $result)) {
                $result['order_id'] = $defaultOrderId;
            }

            $result['payment_type'] = $paymentType;
            $result['cod_amount'] = $codAmount;

            $responseData = is_array($result['responseData'] ?? null) ? $result['responseData'] : [];
            $responseData['payment_type'] = $paymentType;
            $responseData['cod_amount'] = $codAmount;
            $result['responseData'] = $responseData;

            $shippingCompanyId = (int) ($result['shipping_company_id'] ?? 0);
            if ($shippingCompanyId > 0 && !isset($activeCompanyIds[$shippingCompanyId])) {
                continue;
            }

            $totalCharges = (float) ($result['total_charges'] ?? ($responseData['total_charges'] ?? 0));
            if ($totalCharges <= 0) {
                continue;
            }

            $normalizedResults[] = $result;
        }

        return array_values($normalizedResults);
    }

    private function getStandardMatrixRate($user, $shippingCompany, $request, $isOrder = 0, $extraData = [])
    {
        $rateCardId = $this->resolveRateCardId($user, 1);
        $pickupCode = $request->pickup_code ?? null;
        $deliveryCode = $request->delivery_code ?? null;
        $requestedWeight = $this->getRequestWeight($request, $isOrder);
        $paymentType = $request->payment_type ?? null;
        $codAmount = $request->cod_amount ?? 0;

        $otherCharges = $this->getOtherChargesForRateCard((int) $rateCardId, (int) $shippingCompany->id);
        $managedPincodeMetadata = $this->getManagedPincodeMetadataFromContext((int) $shippingCompany->id, $request, $extraData);
        $divisor = $this->getDivisorFromCharges($otherCharges, 5000);

        $totalVolumetricWeight = $this->calculateVolumetricWeight($request, $divisor, $isOrder);

        $maxWeight = max([(float) $requestedWeight, (float) $totalVolumetricWeight]);
        $zoneSettingId = $this->getZoneSettingId($pickupCode, $deliveryCode, $shippingCompany->id, $extraData);
        $minimumWeight = $this->getMinimumWeight($maxWeight);

        // If no zone found for the priority courier (1: Shadowfax), try to get any available zone
        if (!$zoneSettingId && $shippingCompany->isShadowfax()) {
            $fallbackZone = ZoneSetting::where('shipping_company_id', $shippingCompany->id)
                ->where('rate_type', 'b2c')
                ->orderBy('sort_order', 'asc')
                ->first();
            if ($fallbackZone) {
                $zoneSettingId = $fallbackZone->id;
            }
        }

        // If still no zone, return null
        if (!$zoneSettingId) {
            return null;
        }

        $query = RateCardDetail::with('rateCard')
            ->where('shipping_company_id', $shippingCompany->id)
            ->where('zone_setting_id', $zoneSettingId)
            ->where('weight', $minimumWeight)
            ->where('status', 1)
            ->whereHas('rateCard', function ($q) {
                $q->where('group_name', '!=', 'Lyle Snyder');
            });

        if ($rateCardId === 0 || $rateCardId === '0' || strtolower((string) $rateCardId) === 'all') {
            // No filter
        } else {
            $query->where('rate_card_id', $rateCardId);
        }

        $rateCardDetails = $query->get();
        if ($rateCardDetails->isEmpty()) {
            return null;
        }

        $results = [];
        foreach ($rateCardDetails as $rateCardDetail) {
            $minimumWeightFloat = (float) $minimumWeight;
            $totalWeightDifference = 0;

            if ($minimumWeightFloat == 0.5 && $maxWeight <= 0.5) {
                $totalWeightDifference = 0;
            } elseif ($minimumWeightFloat == 0.5 && $maxWeight > 0.5) {
                $totalWeightDifference = ceil(($maxWeight - $minimumWeightFloat) / 0.5);
            } elseif ($minimumWeightFloat != 0.5 && $maxWeight > $minimumWeightFloat) {
                $adjustedWeight = ceil($maxWeight);
                $totalWeightDifference = abs($adjustedWeight - $minimumWeightFloat);
            } else {
                $totalWeightDifference = 0;
            }

            $initialRate = (float) $rateCardDetail->rate;
            $extraRate = (float) $rateCardDetail->extra_rate;
            $finalRate = $extraRate * $totalWeightDifference;

            $calculatedRate = $initialRate + $finalRate;
            $totalCharges = $calculatedRate;

            if ($totalCharges <= 0)
                continue;

            $codCharges = 0;
            $fixCod = 0;
            $extraCod = 0;
            $codExtraTotal = 0;
            if (strtolower((string) $paymentType) === 'cod' || (string) $paymentType === '2') {
                $fixCod = (float) ($rateCardDetail->cod_charges ?? 0);
                $extraCod = (float) ($rateCardDetail->cod_percentage ?? 0);
                $codExtraTotal = $extraCod * $totalWeightDifference;
                $codCharges = $fixCod + $codExtraTotal;
                $totalCharges += $codCharges;
            }

            $rateCardChargeData = $this->prepareB2cRateCardCharges($otherCharges, $calculatedRate, $maxWeight, (float) $codAmount, $request, $managedPincodeMetadata);
            $calculatedRate = $rateCardChargeData['freight'];
            $codCharges += $rateCardChargeData['cod_charges'];
            $rateCardOverheads = $rateCardChargeData['overhead_total'];
            $overheadDetails = $rateCardChargeData['overhead_details'];
            $gstPercent = $rateCardChargeData['gst_percent'];

            $commissions = $extraData['commissions'] ?? null;
            $commissionData = $this->applyCourierCommission($user, $shippingCompany, $calculatedRate, null, $commissions);
            $b2cCharges = $this->applyB2cUserChargeBreakup($commissionData, $calculatedRate, $codCharges, $paymentType);
            $shippingCharges = $b2cCharges['freight_charges'];
            $codCharges = $b2cCharges['cod_charges'];

            $taxableAmount = $shippingCharges + $codCharges + $rateCardOverheads;
            $tax = ($taxableAmount * $gstPercent) / 100;
            $totalWithTax = $taxableAmount + $tax;
            $roundedTotalCharges = round((float) $totalWithTax, 2);
            $roundOffAmount = $roundedTotalCharges - $totalWithTax;

            $basePlan = null;
            $groupName = $rateCardDetail->rateCard ? $rateCardDetail->rateCard->group_name : "";
            $displayName = ($basePlan ?: $shippingCompany->name) . ($groupName ? " (" . str_ireplace(' plan', '', $groupName) . ")" : "");

            $results[] = [
                'direct_rate' => $calculatedRate,
                'order_id' => $request->order_id ?? null,
                'tax' => $tax,
                'round_off' => $roundOffAmount,
                'shipping_company_id' => $shippingCompany->id,
                'shipping_company_name' => $shippingCompany->name,
                'shipping_company_logo' => asset('storage/shipping-logo/' . $shippingCompany->logo),
                'courier_name' => $displayName,
                'freight_charges' => $shippingCharges,
                'cod_charges' => $codCharges,
                'total_charges' => $roundedTotalCharges,
                'min_weight' => $minimumWeight,
                'chargeable_weight' => $maxWeight,
                'applicable_weight' => $maxWeight ?? 0,
                'percentage_amount' => $rateCardOverheads,
                'responseData' => array_merge($rateCardDetail->toArray(), [
                    'base_plan' => $basePlan,
                    'courier_name' => $displayName,
                    'base_freight' => $shippingCharges,
                    'cod_charges' => $codCharges,
                    'tax' => $tax,
                    'total_charges' => $roundedTotalCharges,
                    'additional_charges' => $rateCardOverheads,
                    'freight_charges' => $shippingCharges,
                    'chargeable_weight' => $maxWeight,
                    'overhead_details' => $overheadDetails,
                    'gst_percent' => $gstPercent,
                    'shipping_base_charge' => $initialRate,
                    'shipping_extra_charge' => $finalRate,
                    'shipping_extra_rate' => $extraRate,
                    'shipping_extra_units' => $totalWeightDifference,
                    'cod_base_charge' => $fixCod,
                    'cod_extra_charge' => $codExtraTotal,
                    'cod_extra_rate' => $extraCod
                ])
            ];
        }

        return $results;
    }

    private function logCommissionDebug($step, $data)
    {
        Log::info("RateCardDebug [$step]: " . json_encode($data));
    }

    public function applyCourierCommission($user, $shippingCompany, $totalCharges, $courierId = null, $preLoadedCommissions = null)
    {
        $commissionAmount = 0;
        $courierCommission = null;
        $searchId = $courierId ?: $shippingCompany->name;
        $isFranchiseSeller = is_object($user)
            && method_exists($user, 'isFranchiseSeller')
            && $user->isFranchiseSeller();

        if ($preLoadedCommissions && $preLoadedCommissions->isNotEmpty()) {
            if ($searchId) {
                $courierCommission = $preLoadedCommissions->first(function ($commission) use ($searchId) {
                    return $commission->courier_id == $searchId ||
                        stripos($commission->courier_name, $searchId) !== false;
                });
            }
            if (!$courierCommission) {
                $courierCommission = $preLoadedCommissions->first(function ($commission) use ($shippingCompany) {
                    return (empty($commission->courier_id) && empty($commission->courier_name)) ||
                        (stripos($commission->courier_name, $shippingCompany->name) !== false && (empty($commission->courier_id) || $commission->courier_id == $shippingCompany->id));
                });
            }
        } else {
            if ($searchId) {
                $courierCommission = CourierCommission::with([
                    'userCommissions' => function ($q) use ($user) {
                        $q->where('user_id', $user['id'] ?? $user->id);
                    }
                ])
                    ->where('shipping_company', $shippingCompany->id)
                    ->where(function ($q) use ($searchId) {
                        $q->where('courier_id', $searchId)
                            ->orWhere('courier_name', $searchId)
                            ->orWhere('courier_name', 'LIKE', '%' . $searchId . '%');
                    })
                    ->first();
            }

            if (!$courierCommission) {
                $courierCommission = CourierCommission::with([
                    'userCommissions' => function ($q) use ($user) {
                        $q->where('user_id', $user['id'] ?? $user->id);
                    }
                ])
                    ->where('shipping_company', $shippingCompany->id)
                    ->where(function ($q) use ($shippingCompany) {
                        $q->where(function ($sq) {
                            $sq->whereNull('courier_id')->orWhere('courier_id', '');
                        })->where(function ($sq) {
                            $sq->whereNull('courier_name')->orWhere('courier_name', '');
                        });
                        $q->orWhere('courier_name', 'LIKE', '%' . $shippingCompany->name . '%');
                    })
                    ->orderByRaw("CASE WHEN courier_name IS NULL OR courier_name = '' THEN 1 ELSE 0 END")
                    ->first();
            }
        }

        $masterCommissionAmount = 0;
        $userCommissionAmount = 0;

        if ($courierCommission) {
            $commissionType = $courierCommission->type ?? 'fix';
            $commissionValue = (float) ($courierCommission->value ?? 0);
            $masterCommissionAmount = ($commissionType === "fix") ? $commissionValue : ($totalCharges * $commissionValue) / 100;
        }

        $userCommission = $isFranchiseSeller ? null : ($courierCommission ? ($courierCommission->userCommissions->first() ?? null) : null);
        $userChargeConfig = $isFranchiseSeller
            ? ['type' => 2, 'value' => 0]
            : $this->resolveUserChargeConfig($user);
        $userChargeType = (int) ($userChargeConfig['type'] ?? 1);
        $userChargeValue = (float) ($userChargeConfig['value'] ?? 0);

        if ($userCommission) {
            $commissionType = $userCommission->type ?? 'fix';
            $commissionValue = (float) ($userCommission->value ?? 0);
            $userCommissionAmount = ($commissionType === "fix") ? $commissionValue : ($totalCharges * $commissionValue) / 100;

            if ($userCommissionAmount == 0 && $userChargeValue > 0) {
                $userCommissionAmount = ($userChargeType == 1)
                    ? $userChargeValue
                    : ($totalCharges * $userChargeValue) / 100;
            }
        } elseif ($userChargeValue > 0) {
            $userCommissionAmount = ($userChargeType == 1)
                ? $userChargeValue
                : ($totalCharges * $userChargeValue) / 100;
        }

        $commissionAmount = $masterCommissionAmount + $userCommissionAmount;

        Log::info("Commission Applied: User=" . ($user['id'] ?? $user->id) . ", Company=" . $shippingCompany->id . ", MasterComm=" . $masterCommissionAmount . ", UserComm=" . $userCommissionAmount);

        return [
            'commission_amount' => round($commissionAmount, 2),
            'master_commission_amount' => round($masterCommissionAmount, 2),
            'user_commission_amount' => round($userCommissionAmount, 2),
            'total_charges' => round($totalCharges + $commissionAmount, 2)
        ];
    }

    private function resolveUserChargeConfig($user): array
    {
        if (is_object($user) && method_exists($user, 'isFranchiseSeller') && $user->isFranchiseSeller()) {
            return [
                'type' => 2,
                'value' => 0,
            ];
        }

        $request = request();
        $isB2b = false;

        if ($request) {
            $isB2b = ((int) ($request->weight_order ?? $request->type_of_package ?? 1) === 2)
                || strtolower((string) ($request->type ?? 'b2c')) === 'b2b';
        }

        if ($isB2b) {
            $type = $user['b2b_charge_type'] ?? $user->b2b_charge_type ?? null;
            $value = $user['b2b_charge'] ?? $user->b2b_charge ?? null;
        } else {
            $type = $user['b2c_charge_type'] ?? $user->b2c_charge_type ?? null;
            $value = $user['b2c_charge'] ?? $user->b2c_charge ?? null;
        }

        if ($value === null) {
            $type = $user['charge_type'] ?? $user->charge_type ?? 1;
            $value = $user['charge'] ?? $user->charge ?? 0;
        }

        return [
            'type' => (int) ($type ?? 1),
            'value' => (float) ($value ?? 0),
        ];
    }

    private function applyB2cUserChargeBreakup(array $commissionData, float $freightCharges, float $codCharges, $paymentType): array
    {
        $masterCommissionAmount = (float) ($commissionData['master_commission_amount'] ?? 0);
        $userCommissionAmount = (float) ($commissionData['user_commission_amount'] ?? 0);
        $isCodOrder = in_array(strtolower((string) $paymentType), ['cod', '2'], true);

        return [
            'freight_charges' => round($freightCharges + $masterCommissionAmount + $userCommissionAmount, 2),
            'cod_charges' => round($codCharges + ($isCodOrder ? $userCommissionAmount : 0), 2),
            'applied_charge_amount' => round($masterCommissionAmount + $userCommissionAmount + ($isCodOrder ? $userCommissionAmount : 0), 2),
        ];
    }

    private function isCodChargeConfig(array $config = [], ?string $name = null): bool
    {
        $base = strtolower((string) ($config['base'] ?? ''));
        $chargeName = strtolower(trim((string) $name));

        return $base === 'cod' || str_contains($chargeName, 'cod');
    }

    private function isCodChargeDetail(array $detail = []): bool
    {
        return $this->isCodChargeConfig((array) ($detail['config'] ?? []), (string) ($detail['name'] ?? ''));
    }

    private function prepareB2cRateCardCharges(array $otherCharges, float $freight, float $weight, float $codAmount, $request, array $managedPincodeMetadata = []): array
    {
        if (empty($otherCharges)) {
            $result = [
                'freight' => $freight,
                'cod_charges' => 0,
                'overhead_total' => 0,
                'overhead_details' => [],
                'gst_percent' => self::GST_PERCENT,
            ];

            return $this->applyManagedPincodeOdaChargeToTotals($weight, $managedPincodeMetadata, $result);
        }

        $calc = $this->applyOtherCharges($otherCharges, $freight, $weight, $codAmount, $request, false);
        $rateCardCodCharges = 0;
        $rateCardOverheads = 0;
        $overheadDetails = [];

        foreach ($calc['details'] as $detail) {
            if (!empty($detail['is_info'])) {
                $overheadDetails[] = $detail;
                continue;
            }

            if ($this->isCodChargeDetail($detail)) {
                $rateCardCodCharges += (float) ($detail['value'] ?? 0);
                continue;
            }

            $rateCardOverheads += (float) ($detail['value'] ?? 0);
            $overheadDetails[] = $detail;
        }

        return $this->applyManagedPincodeOdaChargeToTotals($weight, $managedPincodeMetadata, [
            'freight' => (float) ($calc['freight'] ?? $freight),
            'cod_charges' => round($rateCardCodCharges, 2),
            'overhead_total' => round($rateCardOverheads, 2),
            'overhead_details' => $overheadDetails,
            'gst_percent' => (float) ($calc['gst_percent'] ?? self::GST_PERCENT),
        ]);
    }

    private function applyManagedPincodeOdaChargeToTotals(float $weight, array $managedPincodeMetadata, array $chargeData): array
    {
        $chargeData = $this->applyManagedPincodeAreaChargeToTotals($weight, $managedPincodeMetadata, $chargeData, 'oda', 'ODA');
        return $this->applyManagedPincodeAreaChargeToTotals($weight, $managedPincodeMetadata, $chargeData, 'opa', 'OPA');
    }

    private function applyManagedPincodeAreaChargeToTotals(
        float $weight,
        array $managedPincodeMetadata,
        array $chargeData,
        string $prefix,
        string $label
    ): array {
        $chargeId = $managedPincodeMetadata[$prefix . '_charge_id'] ?? null;
        $chargeName = trim((string) ($managedPincodeMetadata[$prefix . '_charge_name'] ?? $managedPincodeMetadata[$prefix . '_category'] ?? ''));
        $perKg = (float) ($managedPincodeMetadata[$prefix . '_charge_per_kg'] ?? 0);
        $minCharge = (float) ($managedPincodeMetadata[$prefix . '_charge_min_charge'] ?? 0);

        if (($chargeId === null && ($chargeName === '' || strtolower($chargeName) === 'none')) || ($perKg <= 0 && $minCharge <= 0)) {
            return $chargeData;
        }

        $amount = max(($perKg * $weight), $minCharge);
        if ($amount <= 0) {
            return $chargeData;
        }

        $chargeData['overhead_total'] = round((float) ($chargeData['overhead_total'] ?? 0) + $amount, 2);
        $chargeData['overhead_details'] = is_array($chargeData['overhead_details'] ?? null) ? $chargeData['overhead_details'] : [];
        $chargeData['overhead_details'][] = [
            'name' => $label . ' Charge - ' . ($chargeName !== '' ? $chargeName : $label),
            'value' => round($amount, 2),
            'config' => [
                'per_kg' => $perKg,
                'min' => $minCharge,
            ],
        ];

        return $chargeData;
    }

    public function getMinimumWeight(float $maxWeight)
    {
        if ($maxWeight < 1) {
            return 0.5;
        } elseif ($maxWeight <= 1.5) {
            return 1;
        } elseif ($maxWeight <= 4) {
            return 2;
        } elseif ($maxWeight <= 9) {
            return 5;
        } elseif ($maxWeight <= 19) {
            return 10;
        }
        return 20;
    }

    public function getZoneSettingId(?string $pickupCode, ?string $deliveryCode, int $companyId, $extraData = [])
    {
        $pickupCode = $this->normalizePincodeValue($pickupCode);
        $deliveryCode = $this->normalizePincodeValue($deliveryCode);

        if (!$pickupCode || !$deliveryCode) {
            return null;
        }

        $pickupZipCode = ($extraData['zipcodes'][$pickupCode] ?? null) ?: DB::table('zipcodes')->where('pincode', $pickupCode)->first();
        $deliverZipCode = ($extraData['zipcodes'][$deliveryCode] ?? null) ?: DB::table('zipcodes')->where('pincode', $deliveryCode)->first();

        if (!$pickupZipCode || !$deliverZipCode)
            return null;

        $isSameState = $pickupZipCode->state === $deliverZipCode->state;
        $isSameCity = $pickupZipCode->city === $deliverZipCode->city;


        if ($isSameState) {
            $slug = $isSameCity ? 'within_city' : 'within_state';
            $zone = ZoneSetting::where('shipping_company_id', $companyId)
                ->where('slug', $slug)
                ->where('rate_type', 'b2c')
                ->first();

            if ($zone) {
                return $zone->id;
            }
        }

        $cityMatch = ZoneSetting::where('shipping_company_id', $companyId)
            ->where('rate_type', 'b2c')
            ->orderBy('sort_order', 'asc')
            ->where(function ($query) use ($deliverZipCode) {
                $query->whereJsonContains('zone', $deliverZipCode->city)
                    ->orWhere('zone', 'LIKE', '%"' . $deliverZipCode->city . '"%');
            })
            ->first();

        if ($cityMatch) {
            return $cityMatch->id;
        }

        $stateMatch = ZoneSetting::where('shipping_company_id', $companyId)
            ->where('rate_type', 'b2c')
            ->orderBy('sort_order', 'asc')
            ->where(function ($query) use ($deliverZipCode) {
                $deliveryState = $deliverZipCode->state;
                $query->whereJsonContains('zone', $deliveryState)
                    ->orWhere('zone', 'LIKE', '%"' . $deliveryState . '"%')
                    ->orWhere('zone', 'LIKE', '%' . $deliveryState . '%')
                    // Also match partial state names (e.g., "Gujarat" matches "Gujarat State")
                    ->orWhere('zone_name', 'LIKE', '%' . $deliveryState . '%');
            })
            ->first();

        if ($stateMatch) {
            return $stateMatch->id;
        }

        // Additional fallback: Try to match by iterating all zones and checking state
        $deliveryState = strtolower(trim($deliverZipCode->state));
        if ($deliveryState) {
            $allZonesForState = ZoneSetting::where('shipping_company_id', $companyId)
                ->where('rate_type', 'b2c')
                ->orderBy('sort_order', 'asc')
                ->get();

            foreach ($allZonesForState as $zone) {
                $zoneArray = is_string($zone->zone) ? json_decode($zone->zone, true) : $zone->zone;
                if (is_array($zoneArray)) {
                    foreach ($zoneArray as $entry) {
                        if (is_string($entry) && stripos(strtolower($entry), $deliveryState) !== false) {
                            return $zone->id;
                        }
                    }
                }
            }
        }

        // Fallback to MTM (Metro to Metro) if it's a known metro city and slug'mtm' exists
        $isMetro = in_array(strtolower(trim($deliverZipCode->city)), ['delhi', 'mumbai', 'bangalore', 'chennai', 'kolkata', 'hyderabad', 'pune', 'ahmedabad'], true);
        if ($isMetro) {
            $mtmMatch = ZoneSetting::where('shipping_company_id', $companyId)
                ->where('rate_type', 'b2c')
                ->whereIn('slug', ['mtm', 'metro_to_metro', 'metro'])
                ->first();
            if ($mtmMatch) {
                return $mtmMatch->id;
            }
        }

        // Final Fallback to ROI (Rest of India) or National
        $roiMatch = ZoneSetting::where('shipping_company_id', $companyId)
            ->where('rate_type', 'b2c')
            ->where(function ($q) {
                $q->whereIn('slug', ['roi', 'rest_of_india', 'interstate', 'national', 'outer', 'all', 'any', 'general'])
                    ->orWhere('zone_name', 'LIKE', '%Rest of India%')
                    ->orWhere('zone_name', 'LIKE', '%ROI%');
            })
            ->first();

        if ($roiMatch) {
            return $roiMatch->id;
        }

        // State-based fallback: If pickup and delivery states are different,
        // try to find any zone that includes the delivery state
        if (!$isSameState && $deliverZipCode->state) {
            $deliveryState = trim($deliverZipCode->state);
            $allZones = ZoneSetting::where('shipping_company_id', $companyId)
                ->where('rate_type', 'b2c')
                ->orderBy('sort_order', 'asc')
                ->get();

            foreach ($allZones as $zone) {
                $zoneData = is_string($zone->zone) ? json_decode($zone->zone, true) : $zone->zone;
                if (is_array($zoneData)) {
                    foreach ($zoneData as $zoneEntry) {
                        $entry = is_string($zoneEntry) ? trim($zoneEntry) : '';
                        // Match by state name or state code
                        if (strcasecmp($entry, $deliveryState) === 0 ||
                            strcasecmp($entry, $deliveryState . ' state') === 0 ||
                            stripos($entry, $deliveryState) !== false) {
                            return $zone->id;
                        }
                    }
                }
            }
        }

        // Last resort for the priority courier (1: Shadowfax)
        if (\App\Models\ShippingCompany::isShadowfaxId($companyId)) {
            $lastResort = ZoneSetting::where('shipping_company_id', $companyId)
                ->where('rate_type', 'b2c')
                ->orderBy('sort_order', 'asc')
                ->first();
            if ($lastResort) {
                return $lastResort->id;
            }
        }

        return null;
    }

    public function getShadowfaxRate($user, $shippingCompany, $request, $isOrder = 0, $extraData = [])
    {
        try {
            // Try API serviceability check first
            if ($this->verifyShadowfaxServiceability($shippingCompany, $request)) {
                // API says serviceable, use internal matrix rate
                $results = $this->getStandardMatrixRate($user, $shippingCompany, $request, $isOrder, $extraData);
                if ($results) {
                    return $results;
                }
            }

            // Fallback: Try internal matrix rate even if API check fails
            // This ensures rates show when pincodes are active in our system
            Log::info('Shadowfax API serviceability failed or returned empty, trying internal matrix rate', [
                'pickup' => $request->pickup_code ?? null,
                'delivery' => $request->delivery_code ?? null
            ]);

            $results = $this->getStandardMatrixRate($user, $shippingCompany, $request, $isOrder, $extraData);
            return $results;
        } catch (\Exception $e) {
            Log::error('Shadowfax Rate Error', ['msg' => $e->getMessage()]);
            // Last resort: try matrix rate
            try {
                return $this->getStandardMatrixRate($user, $shippingCompany, $request, $isOrder, $extraData);
            } catch (\Exception $innerEx) {
                return null;
            }
        }
    }

    private function verifyShadowfaxServiceability($shippingCompany, $request): bool
    {
        try {
            $shadowfaxService = app(ShadowfaxService::class);
            $deliveryCode = trim((string) ($request->delivery_code ?? $request->delivery_pincode ?? ''));
            $pickupCode = trim((string) ($request->pickup_code ?? $request->pickup_pincode ?? ''));

            // 1. Delivery Check
            if ($deliveryCode) {
                $serviceability = $shadowfaxService->checkPincode($deliveryCode, $shippingCompany, 'customer_delivery');
                Log::info("Shadowfax Delivery Check [{$deliveryCode}]", ['res' => $serviceability]);
                if (!($serviceability['success'] ?? false)) {
                    Log::info("Shadowfax: Delivery Pincode {$deliveryCode} not serviceable.");
                    return false;
                }
            }

            // 2. Pickup Check
            if ($pickupCode) {
                $pickupServ = $shadowfaxService->checkPincode($pickupCode, $shippingCompany, 'warehouse_pickup');
                Log::info("Shadowfax Pickup Check (Warehouse) [{$pickupCode}]", ['res' => $pickupServ]);
                if (!($pickupServ['success'] ?? false)) {
                    $pickupServ = $shadowfaxService->checkPincode($pickupCode, $shippingCompany, 'seller_pickup');
                    Log::info("Shadowfax Pickup Check (Seller) [{$pickupCode}]", ['res' => $pickupServ]);
                }
                if (!($pickupServ['success'] ?? false)) {
                    Log::info("Shadowfax: Pickup Pincode {$pickupCode} not serviceable.");
                    return false;
                }
            } else {
                // Try to check for default warehouse if no pickup code is provided (e.g. from Calculator page)
                $defaultWarehouse = \App\Models\CourierWarehouse::where('user_id', auth()->id())
                    ->where('warehouse_status', 1)
                    ->first();
                if ($defaultWarehouse && $defaultWarehouse->zip_code) {
                    $defPickup = $shadowfaxService->checkPincode($defaultWarehouse->zip_code, $shippingCompany, 'warehouse_pickup');
                    Log::info("Shadowfax Default Warehouse Pickup Check [{$defaultWarehouse->zip_code}]", ['res' => $defPickup]);
                    if (!($defPickup['success'] ?? false)) {
                        $defPickup = $shadowfaxService->checkPincode($defaultWarehouse->zip_code, $shippingCompany, 'seller_pickup');
                        Log::info("Shadowfax Default Warehouse Pickup Check (Seller) [{$defaultWarehouse->zip_code}]", ['res' => $defPickup]);
                    }
                    if (!($defPickup['success'] ?? false)) {
                        Log::info("Shadowfax: Default Warehouse Pincode {$defaultWarehouse->zip_code} not serviceable for pickup.");
                        return false;
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Shadowfax Serviceability Error in RateCardService: ' . $e->getMessage());
            return false;
        }
    }

    public function getB2BRate($user, $shippingCompany, $request, $rateCard, $isOrder = 0, $extraData = [])
    {
        $pickupCode = $request->pickup_code ?? null;
        $deliveryCode = $request->delivery_code ?? null;
        $requestedWeight = (float) ($request->weight ?? 0);
        $codAmount = (float) ($request->cod_amount ?? 0);

        $originZoneId = $this->getB2BZoneId($pickupCode, $shippingCompany->id, $deliveryCode, $extraData);
        $destZoneId = $this->getB2BZoneId($deliveryCode, $shippingCompany->id, $pickupCode, $extraData);

        if (!$originZoneId || !$destZoneId)
            return null;

        $matrix = DB::table('rate_card_matrices')
            ->where('rate_card_id', $rateCard->id)
            ->where('shipping_company_id', $shippingCompany->id)
            ->where('from_zone_id', $originZoneId)
            ->where('to_zone_id', $destZoneId)
            ->where('status', 1)
            ->first();

        if (!$matrix)
            return null;

        $ratePerKg = (float) $matrix->rate_per_kg;
        $otherCharges = $this->getOtherChargesForRateCard($rateCard->id, $shippingCompany->id);

        $chargeableWeight = $this->getChargeableWeight($request, $isOrder, $otherCharges);
        $managedPincodeMetadata = $this->getManagedPincodeMetadataFromContext((int) $shippingCompany->id, $request, $extraData);

        $freight = $ratePerKg * $chargeableWeight;

        $codB2bCharges = 0;
        $overheadCalculatedTotal = 0;
        $finalOverheads = [];
        $gstPercent = self::GST_PERCENT;

        if (!empty($otherCharges)) {

            $calc = $this->applyOtherCharges($otherCharges, $freight, $chargeableWeight, $codAmount, $request, true);

            $freight = $calc['freight'];
            $gstPercent = $calc['gst_percent'] ?? self::GST_PERCENT;

            foreach ($calc['details'] as $detail) {
                if (!empty($detail['is_info'])) {
                    $finalOverheads[] = $detail;
                    continue;
                }
                if ($this->isCodChargeDetail($detail)) {
                    $codB2bCharges += $detail['value'];
                } else {
                    $finalOverheads[] = $detail;
                    $overheadCalculatedTotal += $detail['value'];
                }
            }
        }

        $odaAppliedData = $this->applyManagedPincodeOdaChargeToTotals($chargeableWeight, $managedPincodeMetadata, [
            'overhead_total' => $overheadCalculatedTotal,
            'overhead_details' => $finalOverheads,
        ]);
        $overheadCalculatedTotal = (float) ($odaAppliedData['overhead_total'] ?? $overheadCalculatedTotal);
        $finalOverheads = $odaAppliedData['overhead_details'] ?? $finalOverheads;

        $subtotal = $freight + $overheadCalculatedTotal + $codB2bCharges;
        $gstAmount = ($subtotal * $gstPercent) / 100;
        $grandTotal = $subtotal + $gstAmount;
        $overheadDetails = $finalOverheads;

        return [
            [
                'direct_rate' => $freight,
                'order_id' => $request->order_id ?? null,
                'shipping_company_id' => $shippingCompany->id,
                'shipping_company_name' => $shippingCompany->name,
                'shipping_company_logo' => asset('storage/shipping-logo/' . $shippingCompany->logo),
                'courier_name' => $shippingCompany->name . " (B2B - " . $rateCard->group_name . ")",
                'courier_id' => 'LOCAL-' . $shippingCompany->id,
                'freight_charges' => $freight,
                'cod_charges' => $codB2bCharges,
                'tax' => $gstAmount,
                'total_charges' => round($grandTotal, 2),
                'percentage_amount' => $overheadCalculatedTotal,
                'overhead_charges' => $overheadCalculatedTotal,
                'overhead_details' => $overheadDetails,
                'min_weight' => 0,
                'chargeable_weight' => $chargeableWeight,
                'applicable_weight' => $chargeableWeight,
                'responseData' => [
                    'order_id' => $request->order_id ?? null,
                    'shipping_company_id' => $shippingCompany->id,
                    'courier_id' => 'LOCAL-' . $shippingCompany->id,
                    'chargeable_weight' => $chargeableWeight,
                    'base_freight' => $freight,
                    'freight_charges' => $freight,
                    'additional_charges' => $overheadCalculatedTotal,
                    'cod_charges' => $codB2bCharges,
                    'tax' => $gstAmount,
                    'total_charges' => round($grandTotal, 2),
                    'overhead_details' => $overheadDetails
                ]
            ]
        ];
    }

    private function getOtherChargesForRateCard(int $rateCardId, int $companyId): array
    {
        return RateCardOtherCharge::with('master')
            ->where('rate_card_id', $rateCardId)
            ->where('shipping_company_id', $companyId)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    private function getDivisorFromCharges(array $otherCharges, int $default = 5000): int
    {
        foreach ($otherCharges as $charge) {
            $master = $charge->master;
            $masterName = strtolower((string) ($master->name ?? ''));
            $calcType = $master->calc_type ?? '';
            if ($calcType === 'divisor' || str_contains($masterName, 'formula') || str_contains($masterName, 'divisor')) {
                $config = $charge->config ?? [];
                if (!empty($config['divisor']))
                    return (int) $config['divisor'];
                if (!empty($config['value']))
                    return (int) $config['value'];
                if (!empty($config['flat']))
                    return (int) $config['flat'];
            }
        }
        return $default;
    }

    private function calculateVolumetricWeight($request, int $divisor = 5000, int $isOrder = 0): float
    {
        $totalVolumetricWeight = 0;
        if ($isOrder == 1) {
            $volumdata = $request->volumdata ?? [];
            foreach ($volumdata as $parcel) {
                $qty = (float) ($parcel['qty'] ?? $parcel['box'] ?? 1);
                $totalVolumetricWeight += (((float) ($parcel['length'] ?? 0) * (float) ($parcel['width'] ?? 0) * (float) ($parcel['height'] ?? 0)) / $divisor) * $qty;
            }
        } else {
            $lengths = $request->length ?? [];
            if (is_array($lengths)) {
                $boxes = $request->box ?? $request->qty ?? [];
                foreach ($lengths as $key => $l) {
                    $qty = (float) ($boxes[$key] ?? 1);
                    $totalVolumetricWeight += (((float) $l * (float) ($request->width[$key] ?? 0) * (float) ($request->height[$key] ?? 0)) / $divisor) * $qty;
                }
            } else {
                $qty = (float) ($request->box ?? $request->qty ?? 1);
                $totalVolumetricWeight = (((float) ($request->length ?? 0) * (float) ($request->width ?? 0) * (float) ($request->height ?? 0)) / $divisor) * $qty;
            }
        }
        return (float) $totalVolumetricWeight;
    }

    private function getTotalBoxes($request, int $isOrder = 0): float
    {
        $totalBoxes = 0;
        if ($isOrder == 1) {
            $volumdata = $request->volumdata ?? [];
            foreach ($volumdata as $parcel) {
                $totalBoxes += (float) ($parcel['qty'] ?? $parcel['box'] ?? 1);
            }
        } else {
            $boxes = $request->box ?? $request->qty ?? 1;
            if (is_array($boxes)) {
                foreach ($boxes as $b) {
                    $totalBoxes += (float) ($b ?? 1);
                }
            } else {
                $totalBoxes = (float) $boxes;
            }
        }
        if ($totalBoxes <= 0)
            $totalBoxes = 1;
        return $totalBoxes;
    }

    private function applyOtherCharges(array $otherCharges, float $freight, float $weight, float $codAmount, $request, bool $isB2B = false): array
    {
        $overheadTotal = 0;
        $overheadDetails = [];
        $gstPercent = self::GST_PERCENT;
        $deliveryState = null;
        $totalBoxes = null;

        foreach ($otherCharges as $charge) {
            $master = $charge->master;
            if (!$master)
                continue;

            $config = $charge->config ?? [];
            $masterName = strtolower((string) $master->name);
            $calcType = $master->calc_type ?? '';

            // Check if restricted by zip codes
            $zipCodes = $master->zip_codes ?? [];
            if (!empty($zipCodes) && is_array($zipCodes)) {
                $deliveryCode = $request->delivery_code ?? $request->delivery_pincode ?? null;
                if (!$deliveryCode || !in_array($deliveryCode, $zipCodes)) {
                    continue;
                }
            }

            if (str_contains($masterName, 'formula') || str_contains($masterName, 'divisor') || $calcType === 'chargeable_weight' || $calcType === 'freight' || $calcType === 'min_weight') {
                if ($calcType === 'chargeable_weight') {
                    $overheadDetails[] = [
                        'name' => $master->name,
                        'value' => round($weight, 2),
                        'config' => $config,
                        'is_info' => true
                    ];
                } elseif ($calcType === 'freight') {
                    $overheadDetails[] = [
                        'name' => $master->name,
                        'value' => round($freight, 2),
                        'config' => $config,
                        'is_info' => true
                    ];
                }
                continue;
            }

            if (str_contains($masterName, 'gst')) {
                $gstPercent = (float) ($config['percent'] ?? $config['value'] ?? $gstPercent);
                continue;
            }

            if ($isB2B && str_contains($masterName, 'green')) {
                $hasDelhi = false;
                $pickupCode = $request->pickup_code ?? $request->pickup_pincode ?? null;
                $deliveryCode = $request->delivery_code ?? $request->delivery_pincode ?? null;

                if ($pickupCode) {
                    $pickupState = DB::table('zipcodes')->where('pincode', $pickupCode)->value('state');
                    if (str_contains(strtolower((string) $pickupState), 'delhi')) {
                        $hasDelhi = true;
                    }
                }

                if (!$hasDelhi && $deliveryCode) {
                    $deliveryState = DB::table('zipcodes')->where('pincode', $deliveryCode)->value('state');
                    if (str_contains(strtolower((string) $deliveryState), 'delhi')) {
                        $hasDelhi = true;
                    }
                }

                if (!$hasDelhi) {
                    continue;
                }
            }

            $rovType = strtolower((string) ($request->rov_type ?? ''));
            if ($rovType === 'rov_owner' && str_contains($masterName, 'rov carrier')) {
                continue;
            }
            if ($rovType === 'rov_carrier' && str_contains($masterName, 'rov owner')) {
                continue;
            }

            $paymentType = strtolower((string) ($request->payment_type ?? '1'));
            $isPrepaid = in_array($paymentType, ['prepaid', '1']);
            if ($isPrepaid && $this->isCodChargeConfig($config, $master->name ?? '')) {
                continue;
            }

            $amount = 0;
            $calcType = $master->calc_type ?? '';
            $skipMinCheck = false;
            ;
            if (str_contains($masterName, 'minimum charge')) {
                $minChargeValue = (float) ($config['flat'] ?? $config['value'] ?? $config['min'] ?? 0);
                $currentSubtotal = $freight + $overheadTotal;
                Log::info("overhead==>");
                Log::info($overheadTotal);
                Log::info("==>current");
                Log::info($currentSubtotal);
                Log::info("==>min");
                Log::info($minChargeValue);
                if ($currentSubtotal >= $minChargeValue) {
                    continue;
                }

                // Only add the delta needed to bring the subtotal up to the configured minimum.
                $amount = max(0, $minChargeValue - $currentSubtotal);

                $skipMinCheck = true;
                $calcType = 'skip_evaluation';
            }

            $baseForPercent = $freight;
            if (isset($config['base']) && $config['base'] == 'cod') {
                $baseForPercent = $codAmount;
            } else if (isset($config['base']) && $config['base'] == 'invoice') {
                $baseForPercent = (float) ($request->invoice_amount ?? $request->inv_amount ?? $request->order_amount ?? 0);
            } else if (str_contains($masterName, 'cod')) {
                $baseForPercent = $codAmount;
            }

            switch ($calcType) {
                case 'skip_evaluation':
                    break;
                case 'per_kg_or_min':
                    $perKg = (float) ($config['per_kg'] ?? 0);
                    $min = (float) ($config['min'] ?? 0);
                    $amount = $perKg * $weight;
                    if ($min > 0)
                        $amount = max($amount, $min);
                    $skipMinCheck = true;
                    break;
                case 'per_kg_or_flat':
                    $perKg = (float) ($config['per_kg'] ?? 0);
                    $flat = (float) ($config['flat'] ?? 0);
                    $amount = $perKg * $weight;
                    if ($flat > 0)
                        $amount = max($amount, $flat);
                    $skipMinCheck = true;
                    break;
                case 'percent_or_min':
                    $percent = (float) ($config['percent'] ?? 0);
                    $min = (float) ($config['min'] ?? 0);

                    if ($min > 0 && $baseForPercent < $min && (in_array($config['base'] ?? '', ['cod', 'invoice']) || str_contains($masterName, 'cod') || str_contains($masterName, 'rov'))) {
                        $amount = $baseForPercent;
                    } else {
                        $calculated = ($baseForPercent * $percent) / 100;
                        if ($min > 0) {
                            $amount = max($calculated, $min);
                        } else {
                            $amount = $calculated;
                        }
                    }
                    $skipMinCheck = true;
                    break;
                case 'handling_tier':
                    if ($totalBoxes === null) {
                        $totalBoxes = $this->getTotalBoxes($request);
                    }
                    $singleBoxWeight = $totalBoxes > 0 ? ($weight / $totalBoxes) : $weight;
                    $threshold = (float) ($config['threshold'] ?? 0);
                    $rateUpto = (float) ($config['rate_upto'] ?? 0);
                    $rateAbove = (float) ($config['rate_above'] ?? 0);
                    $rate = ($singleBoxWeight <= $threshold) ? $rateUpto : $rateAbove;
                    $amount = $rate * $weight;
                    $skipMinCheck = true;
                    break;
                default:
                    if (!empty($config['percent'])) {
                        $amount = ($baseForPercent * (float) $config['percent']) / 100;
                    } elseif (!empty($config['per_kg'])) {
                        $amount = (float) $config['per_kg'] * $weight;
                    } elseif (!empty($config['flat'])) {
                        $amount = (float) $config['flat'];
                    } elseif (!empty($config['value'])) {
                        $amount = (float) $config['value'];
                    }
                    break;
            }

            // Apply Minimum Charge if specified (generic types)
            if (!$skipMinCheck && !empty($config['min']) && $amount < (float) $config['min']) {
                $minLimit = (float) $config['min'];
                if ($minLimit > 0 && $baseForPercent < $minLimit && (in_array($config['base'] ?? '', ['cod', 'invoice']) || str_contains($masterName, 'cod') || str_contains($masterName, 'rov'))) {
                    $amount = $baseForPercent;
                } else {
                    $amount = $minLimit;
                }
            }

            if ($amount > 0) {
                $overheadTotal += $amount;
                $overheadDetails[] = [
                    'name' => $master->name,
                    'value' => round($amount, 2),
                    'config' => $config
                ];
            }
        }

        return [
            'freight' => $freight,
            'overhead_total' => $overheadTotal,
            'gst_percent' => $gstPercent,
            'subtotal' => $freight + $overheadTotal,
            'details' => $overheadDetails
        ];
    }

    private function getB2BZoneId($pincode, $companyId, $otherPincode = null, $extraData = [])
    {
        $pincode = $this->normalizePincodeValue($pincode);
        if ($pincode === '') {
            return null;
        }

        $zip = ($extraData['zipcodes'][$pincode] ?? null) ?: DB::table('zipcodes')->where('pincode', $pincode)->first();
        if (!$zip)
            return null;

        // For B2B, we find the zone by checking if the city or state is in the zone's JSON array
        $zoneId = ZoneSetting::where('shipping_company_id', $companyId)
            ->where('rate_type', 'b2b')
            ->where(function ($query) use ($zip) {
                $query->whereJsonContains('zone', $zip->city)
                    ->orWhereJsonContains('zone', $zip->state)
                    ->orWhere('zone', 'LIKE', '%"' . $zip->city . '"%')
                    ->orWhere('zone', 'LIKE', '%"' . $zip->state . '"%');
            })
            ->orderBy('sort_order', 'asc')
            ->value('id');

        if ($zoneId) {
            return $zoneId;
        }

        // Fallback for B2B
        $fallbackId = ZoneSetting::where('shipping_company_id', $companyId)
            ->where('rate_type', 'b2b')
            ->where(function ($q) {
                $q->whereIn('slug', ['roi', 'rest_of_india', 'interstate', 'national', 'all', 'any', 'general'])
                    ->orWhere('zone_name', 'LIKE', '%Rest of India%')
                    ->orWhere('zone_name', 'LIKE', '%ROI%');
            })
            ->orderBy('sort_order', 'asc')
            ->value('id');

        if ($fallbackId) {
            return $fallbackId;
        }

        // Last resort for the priority courier (1: Shadowfax)
        if (\App\Models\ShippingCompany::isShadowfaxId($companyId)) {
            return ZoneSetting::where('shipping_company_id', $companyId)
                ->where('rate_type', 'b2b')
                ->orderBy('sort_order', 'asc')
                ->value('id');
        }

        return null;
    }
}
