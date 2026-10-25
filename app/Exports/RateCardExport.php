<?php

namespace App\Exports;

use App\Models\RateCard;
use App\Models\ShippingCompany;
use App\Models\ZoneSetting;
use App\Models\ShippingWeight;
use App\Models\RateCardDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RateCardExport implements FromCollection, WithStyles, ShouldAutoSize
{
    protected $rateCardId;

    public function __construct($rateCardId)
    {
        $this->rateCardId = $rateCardId;
    }

    public function collection()
    {
        $id = $this->rateCardId;
        $shippingCompanies = ShippingCompany::where('status', 1)->get();
        $data = [];

        foreach ($shippingCompanies as $company) {
            // 2. Header Labels Row
            $zones = ZoneSetting::where('shipping_company_id', $company->id)
                ->where('rate_type', 'b2c')
                ->orderBy('sort_order', 'asc')
                ->get();

            $weights = ShippingWeight::where('shipping_company_id', $company->id)
                ->where('status', 1)
                ->orderBy('weight', 'asc')
                ->get();

            if ($zones->isEmpty() || $weights->isEmpty()) {
                continue;
            }

            // 1. Company Name Row
            $data[] = [$company->name];

            $header = ['Weight (Kg)'];
            foreach ($zones as $zone) {
                $header[] = $zone->heading;
                $header[] = ""; // Extra rate column space (will be empty label but mapped)
            }
            $header[] = "COD Charges (%)";
            $header[] = "";
            $data[] = $header;

            // 3. Technical ID Row
            $technicalHeader = ["CID:{$company->id}"];
            foreach ($zones as $zone) {
                $technicalHeader[] = "ZID:{$zone->id}_rate";
                $technicalHeader[] = "ZID:{$zone->id}_extra_rate";
            }
            $technicalHeader[] = "COD_CHARGE";
            $technicalHeader[] = "COD_PERCENT";
            $data[] = $technicalHeader;

            // 4. Data Rows
            foreach ($weights as $sw) {
                $weight = $sw->weight;
                $row = [$weight];

                foreach ($zones as $zone) {
                    $detail = RateCardDetail::where('rate_card_id', $id)
                        ->where('shipping_company_id', $company->id)
                        ->where('weight', $weight)
                        ->where('zone_setting_id', $zone->id)
                        ->first();

                    $row[] = $this->displayRateValue($detail->rate ?? null);
                    $row[] = $this->displayRateValue($detail->extra_rate ?? null);
                }

                $codDetail = RateCardDetail::where('rate_card_id', $id)
                    ->where('shipping_company_id', $company->id)
                    ->where('weight', $weight)
                    ->first();

                $row[] = $this->displayRateValue($codDetail->cod_charges ?? null);
                $row[] = $this->displayRateValue($codDetail->cod_percentage ?? null);

                $data[] = $row;
            }

            // 5. Empty Row between companies
            $data[] = [];
        }

        return collect($data);
    }

    private function displayRateValue($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return (string) $value;
    }

    public function styles(Worksheet $sheet)
    {
        // Styling logic could go here, but let's keep it simple for now
        // Maybe make company names bold
        return [
            // 
        ];
    }
}
