<?php
namespace App\Exports;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CodRemittanceExport implements FromView
{
    protected $fromDate;
    protected $toDate;

    public function __construct($codVoucher)
    {
        $this->codVoucher = $codVoucher; 
    }

    public function view(): View
    {
        $orders = $this->codVoucher->codVoucherOrders ?? collect();  
        return view('exports.cod_remittance', compact('orders'));
    }
}
