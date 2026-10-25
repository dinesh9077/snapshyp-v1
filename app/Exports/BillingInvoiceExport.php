<?php

namespace App\Exports;

use App\Services\BillingInvoiceService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BillingInvoiceExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $invoice;
    protected $rows;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
        $this->rows = collect(); // store rows for total calculation
    }

    public function collection()
    {
        $service = app(BillingInvoiceService::class);
        $service->summarizeInvoice($this->invoice, true);
        $this->rows = $service->invoiceOrderRows($this->invoice);

        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Sr.No',
            'Order Details',
            'Customer Details',
            'Customer Address',
            'Seller Details',
            'City',
            'State',
            'Country',
            'Pincode',
            'Package Details',
            'Payment',
            'Pickup Address',
            'Status',
            'Created At',
            'Forward Charge',
            'COD Charge',
            'RTO Charge',
            'Taxable Amount',
            'CGST',
            'SGST',
            'IGST',
            'Invoice Amount',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $rowCount = $this->rows->count() + 1; // +1 for heading row

                // Calculate totals
                $forwardSum = $this->rows->sum('Forward Charge');
                $codSum   = $this->rows->sum('COD Charge');
                $rtoSum   = $this->rows->sum('RTO Charge');
                $baseSum  = $this->rows->sum('Taxable Amount');
                $cgstSum  = $this->rows->sum('CGST');
                $sgstSum  = $this->rows->sum('SGST');
                $igstSum  = $this->rows->sum('IGST');
                $totalSum = $this->rows->sum('Invoice Amount');

                $sheet = $event->sheet;

                // Set total row
                $totalRow = $rowCount + 1;
                $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $sheet->mergeCells("A{$totalRow}:N{$totalRow}");
                $sheet->setCellValue('O' . $totalRow, $forwardSum);
                $sheet->setCellValue('P' . $totalRow, $codSum);
                $sheet->setCellValue('Q' . $totalRow, $rtoSum);
                $sheet->setCellValue('R' . $totalRow, $baseSum);
                $sheet->setCellValue('S' . $totalRow, $cgstSum);
                $sheet->setCellValue('T' . $totalRow, $sgstSum);
                $sheet->setCellValue('U' . $totalRow, $igstSum);
                $sheet->setCellValue('V' . $totalRow, $totalSum);

                // Bold total row
                $sheet->getStyle("A{$totalRow}:V{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
