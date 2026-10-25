<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BulkOrderSampleExport implements FromArray, WithHeadings
{
    private int $typeOfPackage;

    public function __construct(int $typeOfPackage = 1)
    {
        $this->typeOfPackage = $typeOfPackage === 2 ? 2 : 1;
    }

    public function headings(): array
    {
        if ($this->typeOfPackage === 2) {
            return [
                'order_date',
                'order_type',
                'cod_amount',
                'insurance_type',
                'first_name',
                'last_name',
                'email',
                'gst_number',
                'mobile',
                'address',
                'zip_code',
                'city',
                'state',
                'country',
                'product_category',
                'product_name',
                'sku_number',
                'hsn_number',
                'product_amount',
                'product_quantity',
                'invoice_no',
                'invoice_amount',
                'ewaybillno',
                'no_of_box',
                'weight_per_box',
                'length',
                'width',
                'height',
                'total_weight',
                'reference_number',
            ];
        }

        return [
            'order_date',
            'order_type',
            'cod_amount',
            'first_name',
            'last_name',
            'email',
            'gst_number',
            'mobile',
            'address',
            'zip_code',
            'city',
            'state',
            'country',
            'product_category',
            'product_name',
            'sku_number',
            'hsn_number',
            'product_amount',
            'product_quantity',
            'invoice_no',
            'invoice_amount',
            'ewaybillno',
            'weight',
            'length',
            'width',
            'height',
            'reference_number',
        ];
    }

    public function array(): array
    {
        if ($this->typeOfPackage === 2) {
            return [[
                '2026-03-26',
                'prepaid',
                '0',
                '1',
                'Rahul',
                'Sharma',
                'rahul@example.com',
                '24ABCDE1234F1Z5',
                '9876543210',
                'Ring Road, Surat',
                '394221',
                'Surat',
                'Gujarat',
                'India',
                'Apparel,Footwear',
                'T-Shirt,Shoes',
                'TS001,SH001',
                '61091000,64039990',
                '500,1200',
                '2,1',
                'INV-B2B-001',
                '2200',
                '',
                '2,1',
                '8,12',
                '30,35',
                '20,25',
                '15,18',
                '28',
                'REF-123',
            ]];
        }

        return [[
            '2026-03-26',
            'prepaid',
            '0',
            'Rahul',
            'Sharma',
            'rahul@example.com',
            '24ABCDE1234F1Z5',
            '9876543210',
            'Ring Road, Surat',
            '394221',
            'Surat',
            'Gujarat',
            'India',
            'Apparel',
            'T-Shirt',
            'TS001',
            '61091000',
            '500',
            '2',
            'INV-B2C-001',
            '1000',
            '',
            '1.5',
            '30',
            '20',
            '15',
        ]];
    }
}
