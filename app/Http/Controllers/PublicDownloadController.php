<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ShippingCompany;
use App\Models\RateCard;
use App\Models\RateCardDetail;
use App\Models\ZoneSetting;
use App\Models\RateCardMatrix;
use App\Models\OverheadCharge;
use App\Models\OtherChargeMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDF;

class PublicDownloadController extends Controller
{
    /**
     * Publicly accessible PDF download (via shared link)
     * No 'auth' middleware here!
     */
    public function downloadPriceListPdf(Request $request, $token)
    {
        try {
            // Securely decrypt user ID from token
            $userId = decrypt($token);
            $user = User::findOrFail($userId);
            
            $type = $request->input('type', 'b2b'); 
            $assignedRateCardId = $user->getAssignedRateCardIdByType($type);
            $rateCard = null;

            if ($assignedRateCardId && strtolower((string)$assignedRateCardId) !== 'all') {
                $rateCard = RateCard::where('id', (int)$assignedRateCardId)->where('rate_type', $type)->first();
            }

            if (!$rateCard || !$this->rateCardHasData($rateCard->id, $type)) {
                $rateCard = $this->getFallbackRateCardForType($type);
            }

            if (!$rateCard) {
                $rateCard = RateCard::where('rate_type', $type)->orderBy('id', 'desc')->first();
            }

            if (!$rateCard) {
                return abort(404, 'Rate card not available.');
            }

            $availableCompanyIds = $this->getRateCardCompanyIds($rateCard->id, $type);
            $requestedCompanyId = $request->input('company_id', 'all');
            if ($requestedCompanyId !== 'all') {
                $availableCompanyIds = array_intersect($availableCompanyIds, [(int)$requestedCompanyId]);
            }

            $shippingCompanies = ShippingCompany::query()
                ->visibleInRateCards()
                ->where('status', 1)
                ->whereIn('id', $availableCompanyIds)
                ->get();

            $data = [];
            foreach ($shippingCompanies as $company) {
                $companyId = $company->id;
                $overheadData = $this->getOverheadForCompany($companyId);
                
                $b2bZones = []; $matrixData = collect(); $b2cRows = collect();

                if ($type === 'b2b') {
                    $b2bZones = ZoneSetting::where('shipping_company_id', $companyId)->where('rate_type', 'b2b')->orderBy('sort_order', 'asc')->get();
                    $matrixData = RateCardMatrix::where('rate_card_id', $rateCard->id)->where('shipping_company_id', $companyId)->get()->groupBy('from_zone_id');
                } else {
                    $b2cRows = RateCardDetail::with(['zoneSetting:id,heading,sort_order'])->where('rate_card_id', $rateCard->id)->where('shipping_company_id', $companyId)->where('status', 1)->orderBy('weight', 'asc')->orderBy('zone_setting_id', 'asc')->get()->groupBy('weight');
                }

                $otherChargeMasters = OtherChargeMaster::where('is_active', 1)
                    ->where('shipping_company_id', $companyId)
                    ->orderBy('name')
                    ->get();
                $activeCharges = [];
                foreach($otherChargeMasters as $master) {
                    $result = DB::table('rate_card_other_charges')->where('rate_card_id', $rateCard->id)->where('shipping_company_id', $companyId)->where('other_charge_master_id', $master->id)->first();
                    if ($result) {
                        $activeCharges[] = [ 'master' => $master, 'config' => json_decode($result->config, true) ];
                    }
                }

                $data[] = [
                    'company' => $company, 'overheadData' => $overheadData,
                    'b2bZones' => $b2bZones, 'matrixData' => $matrixData,
                    'b2cRows' => $b2cRows, 'activeCharges' => $activeCharges
                ];
            }

            if (empty($data)) {
                return abort(404, 'No data found for the selection.');
            }

            $pdf = PDF::loadView('shipping.rate-card.price-list-pdf', [
                'rateCard' => $rateCard,
                'type' => $type,
                'data' => $data,
            ]);

            return $pdf->download('price-list-' . Str::slug($rateCard->group_name) . '.pdf');

        } catch (\Exception $e) {
            return abort(404, 'Invalid or expired download link.');
        }
    }

    private function rateCardHasData(?int $rateCardId, string $type = 'b2c'): bool
    {
        if (!$rateCardId) return false;
        if ($type === 'b2b') {
            return RateCardMatrix::where('rate_card_id', $rateCardId)->exists();
        }
        return RateCardDetail::where('rate_card_id', $rateCardId)->where('status', 1)->exists();
    }

    private function getFallbackRateCardForType(string $type = 'b2c'): ?RateCard
    {
        $query = RateCard::query()->where('rate_type', $type);
        if ($type === 'b2b') {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))->from('rate_card_matrices')
                    ->whereColumn('rate_card_matrices.rate_card_id', 'rate_cards.id');
            });
        } else {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))->from('rate_card_details')
                    ->whereColumn('rate_card_details.rate_card_id', 'rate_cards.id')->where('status', 1);
            });
        }
        return $query->orderBy('id')->first();
    }

    private function getRateCardCompanyIds(?int $rateCardId, string $type = 'b2c'): array
    {
        if (!$rateCardId) return [];
        if ($type === 'b2b') {
            return ShippingCompany::filterVisibleRateCardCompanyIds(
                RateCardMatrix::where('rate_card_id', $rateCardId)->distinct()->pluck('shipping_company_id')->filter()->map(fn($id)=>(int)$id)->values()->all()
            );
        }
        return ShippingCompany::filterVisibleRateCardCompanyIds(
            RateCardDetail::where('rate_card_id', $rateCardId)->where('status', 1)->distinct()->pluck('shipping_company_id')->filter()->map(fn($id)=>(int)$id)->values()->all()
        );
    }

    private function getOverheadForCompany($companyId)
    {
        $defaults = [
            'flat_mile_per_kg' => 1, 'flat_mile_min' => 100, 'rov_owner_percent' => 0, 'rov_owner_min' => 50,
            'rov_carrier_percent' => 0.3, 'rov_carrier_min' => 100, 'green_per_kg' => 0, 'green_min' => 50,
            'oda_per_kg' => 4, 'oda_min' => 500, 'cod_percent' => 0.5, 'cod_min' => 150,
            'divisor' => 5000, 'minimum_charge' => 220, 'gst_percent' => 18,
        ];
        $row = OverheadCharge::where('shipping_company_id', $companyId)->first();
        if (!$row) return $defaults;
        return array_merge($defaults, $row->only(array_keys($defaults)));
    }
}
