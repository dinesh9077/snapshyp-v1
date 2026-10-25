<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\RateCardDetail;
use App\Models\RateCardMatrix;
use App\Models\ShippingCompany;
use App\Models\ZoneSetting;
use App\Models\OverheadCharge;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RateCardController extends Controller
{
    use ApiResponse;

    /**
     * Unified Rate Card API for B2C and B2B
     */
    public function rateCard(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $shippingCompanyId = (int) $request->input('shipping_company_id', 0);
        $weight = $request->input('weight');
        $type = $request->input('type', 'all'); // New parameter to filter data

        $response = [
            'rate_cards' => $user->isAdminLevel() 
                ? RateCard::select('id', 'group_name', 'rate_type')->get() 
                : collect(),
            'current_shipping_company_id' => $shippingCompanyId,
        ];

        // B2C logic
        $b2cRateCard = null;
        if (in_array($type, ['all', 'b2c'])) {
            $b2cRateCard = $this->getEffectiveRateCard($user, 'b2c', (int) $request->input('b2c_rate_card_id'));
            $response['b2c'] = $this->getB2CData($b2cRateCard, $shippingCompanyId, $weight);
        }

        // B2B logic
        $b2bRateCard = null;
        if (in_array($type, ['all', 'b2b'])) {
            $b2bRateCard = $this->getEffectiveRateCard($user, 'b2b', (int) $request->input('b2b_rate_card_id'));
            $response['b2b'] = $this->getB2BData($b2bRateCard, $shippingCompanyId);
        }

        // Shipping companies available
        $b2cCompanies = $b2cRateCard ? $this->getRateCardCompanyIds($b2cRateCard->id, 'b2c') : [];
        $b2bCompanies = $b2bRateCard ? $this->getRateCardCompanyIds($b2bRateCard->id, 'b2b') : [];
        $allAvailableCompanyIds = array_unique(array_merge($b2cCompanies, $b2bCompanies));
        
        $response['shipping_companies'] = ShippingCompany::query()
            ->visibleInRateCards()
            ->where('status', 1)
            ->when(!empty($allAvailableCompanyIds), function ($query) use ($allAvailableCompanyIds) {
                $query->whereIn('id', $allAvailableCompanyIds);
            })->get(['id', 'name', 'logo']);

        if ($response['current_shipping_company_id'] === 0) {
            $response['current_shipping_company_id'] = $response['shipping_companies']->first()->id ?? 0;
        }

        return $this->successResponse($response, 'Rate card data fetched successfully.');
    }

    /**
     * Determine the effective rate card based on user assignment and fallback logic
     */
    private function getEffectiveRateCard($user, $type, $requestedId)
    {
        if ($user->isAdminLevel() && $requestedId > 0) {
            $card = RateCard::where('id', $requestedId)->where('rate_type', $type)->first();
            if ($card) return $card;
        }

        $assignedId = $user->getAssignedRateCardIdByType($type);
        if ($assignedId && strtolower((string)$assignedId) !== 'all' && $assignedId != 0) {
            $card = RateCard::where('id', $assignedId)->where('rate_type', $type)->first();
            if ($card) return $card;
        }

        return $this->getFallbackRateCardForType($type);
    }

    /**
     * Get B2C (Single Package) specific data
     */
    private function getB2CData($rateCard, $shippingCompanyId, $weight)
    {
        if (!$rateCard) return null;

        $companies = $this->getRateCardCompanyIds($rateCard->id, 'b2c');
        if (empty($companies)) return null;

        if ($shippingCompanyId > 0 && !in_array((int) $shippingCompanyId, $companies)) {
            return 'no data aviable';
        }

        $currentCompanyId = in_array((int) $shippingCompanyId, $companies) ? (int) $shippingCompanyId : $companies[0];
        $availableWeights = $this->getRateCardWeights($rateCard->id, $currentCompanyId);
        $currentWeight = $availableWeights->contains($weight) ? $weight : $availableWeights->first();

        $rows = RateCardDetail::with(['shippingCompany:id,name', 'zoneSetting:id,heading,sort_order'])
            ->where('rate_card_id', $rateCard->id)
            ->where('shipping_company_id', $currentCompanyId)
            ->where('weight', $currentWeight)
            ->where('status', 1)
            ->orderBy('zone_setting_id', 'asc')
            ->get();

        return [
            'rate_card_id' => $rateCard->id,
            'rate_card_name' => $rateCard->group_name,
            'available_weights' => $availableWeights,
            'current_weight' => $currentWeight,
            'rates' => $rows->map(function($row) {
                return [
                    'shipping_company' => $row->shippingCompany->name ?? 'N/A',
                    'weight' => $row->weight,
                    'heading' => $row->zoneSetting->heading ?? 'N/A',
                    'rate' => (float)$row->rate,
                    'extra_rate' => (float)$row->extra_rate,
                    'cod_charges' => (float)$row->cod_charges,
                    'cod_percentage' => (float)$row->cod_percentage,
                ];
            }),
            'overhead' => $this->getOverheadForCompany($currentCompanyId)
        ];
    }

    /**
     * Get B2B (Multiple Package) specific data (Matrix)
     */
    private function getB2BData($rateCard, $shippingCompanyId)
    {
        if (!$rateCard) return null;

        $companies = $this->getRateCardCompanyIds($rateCard->id, 'b2b');
        if (empty($companies)) return null;

        if ($shippingCompanyId > 0 && !in_array((int) $shippingCompanyId, $companies)) {
            return 'no data aviable';
        }

        $currentCompanyId = in_array((int) $shippingCompanyId, $companies) ? (int) $shippingCompanyId : $companies[0];

        $zones = ZoneSetting::where('shipping_company_id', $currentCompanyId)
            ->where('rate_type', 'b2b')
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'heading', 'slug']);

        $matrix = RateCardMatrix::where('rate_card_id', $rateCard->id)
            ->where('shipping_company_id', $currentCompanyId)
            ->get()
            ->groupBy('from_zone_id');

        $formattedMatrix = [];
        foreach ($zones as $fromZone) {
            $rowData = [];
            foreach ($zones as $toZone) {
                $match = $matrix->get($fromZone->id)?->firstWhere('to_zone_id', $toZone->id);
                $rowData[$toZone->slug ?: $toZone->heading] = $match ? (float)$match->rate_per_kg : 0;
            }
            $formattedMatrix[$fromZone->slug ?: $fromZone->heading] = $rowData;
        }

        return [
            'rate_card_id' => $rateCard->id,
            'rate_card_name' => $rateCard->group_name,
            'zones' => $zones->map(fn($z) => ['id' => $z->id, 'name' => $z->heading, 'slug' => $z->slug]),
            'matrix' => $formattedMatrix
        ];
    }

    // --- Helper Methods (Adapted from ShippingController) ---

    private function getRateCardCompanyIds(?int $rateCardId, string $type = 'b2c'): array
    {
        if (!$rateCardId) return [];
        if ($type === 'b2b') {
            return ShippingCompany::filterVisibleRateCardCompanyIds(
                RateCardMatrix::where('rate_card_id', $rateCardId)->distinct()->pluck('shipping_company_id')->filter()->map(fn($id) => (int)$id)->values()->all()
            );
        }
        return ShippingCompany::filterVisibleRateCardCompanyIds(
            RateCardDetail::where('rate_card_id', $rateCardId)->where('status', 1)->distinct()->pluck('shipping_company_id')->filter()->map(fn($id) => (int)$id)->values()->all()
        );
    }

    private function getRateCardWeights(?int $rateCardId, ?int $shippingCompanyId)
    {
        if (!$rateCardId || !$shippingCompanyId) return collect();
        return RateCardDetail::where('rate_card_id', $rateCardId)->where('shipping_company_id', $shippingCompanyId)->where('status', 1)->select('weight')->distinct()->orderBy('weight', 'asc')->pluck('weight');
    }

    private function getFallbackRateCardForType(string $type = 'b2c')
    {
        $query = RateCard::where('rate_type', $type);
        if ($type === 'b2b') {
            $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('rate_card_matrices')->whereColumn('rate_card_matrices.rate_card_id', 'rate_cards.id'));
        } else {
            $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('rate_card_details')->whereColumn('rate_card_details.rate_card_id', 'rate_cards.id')->where('status', 1));
        }
        return $query->orderBy('id')->first();
    }

    private function getOverheadForCompany($companyId)
    {
        $defaults = [
            'flat_mile_per_kg' => 1, 'flat_mile_min' => 100, 'rov_owner_percent' => 0, 'rov_owner_min' => 50,
            'rov_carrier_percent' => 0.3, 'rov_carrier_min' => 100, 'green_per_kg' => 0, 'green_min' => 50,
            'oda_per_kg' => 4, 'oda_min' => 500, 'cod_percent' => 0.5, 'cod_min' => 150, 'divisor' => 5000,
            'minimum_charge' => 220, 'gst_percent' => 18,
        ];
        $row = OverheadCharge::where('shipping_company_id', $companyId)->first();
        return $row ? array_merge($defaults, $row->only(array_keys($defaults))) : $defaults;
    }
}
