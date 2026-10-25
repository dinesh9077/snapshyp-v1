<?php

namespace App\Exports;

use App\Models\RateCardMatrix;
use App\Models\ShippingCompany;
use App\Models\ZoneSetting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RateCardB2BExport implements FromCollection, WithStyles, ShouldAutoSize
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
            $zones = ZoneSetting::where('shipping_company_id', $company->id)
                ->where('rate_type', 'b2b')
                ->orderBy('sort_order', 'asc')
                ->get();

            if ($zones->isEmpty()) {
                continue;
            }

            // 1. Company Name Row
            $data[] = [$company->name];

            // 2. Header Row (Human readable)
            $header = ['Origin \\ Dest.'];
            foreach ($zones as $zone) {
                $header[] = $zone->heading;
            }
            $data[] = $header;

            // 3. Technical ID Row
            $tech = ["CID:{$company->id}"];
            foreach ($zones as $zone) {
                $tech[] = "ZID:{$zone->id}";
            }
            $data[] = $tech;

            // 4. Data Rows
            foreach ($zones as $originZone) {
                $row = ["OID:{$originZone->id}"];
                foreach ($zones as $destZone) {
                    $rate = RateCardMatrix::where('rate_card_id', $id)
                        ->where('shipping_company_id', $company->id)
                        ->where('from_zone_id', $originZone->id)
                        ->where('to_zone_id', $destZone->id)
                        ->value('rate_per_kg');
                    $row[] = $this->displayRateValue($rate);
                }
                $data[] = $row;
            }

            // 5. Empty row between companies
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
        return [
            //
        ];
    }
}
