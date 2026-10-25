<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;
	protected $rowNumber = 0; // Start at 0 or 1 depending on if you use headings
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $currentDate = date('Y-m-d');
        $user = Auth::user();
        $role = strtolower($user->role);
        $userId = $user->id;

        $query = Order::with(['user', 'warehouse', 'customer', 'customerAddress', 'orderItems']);

        // Consistency with other controllers for role-based access
        if (!$user->isMasterAdmin()) {
            if (in_array($role, ['admin', 'reseller', 'staff'], true)) {
                $query->where(function ($q) use ($userId) {
                    $q->where('orders.user_id', $userId)
                      ->orWhereHas('user', function ($subQ) use ($userId) {
                          $subQ->where('staff_id', $userId);
                      });
                });
            } else {
                $query->where('orders.user_id', $userId);
            }
        }

        // Handle parameters (both snake_case and concatenated names)
        $userIdInput = $this->request->input('user_id') ?? $this->request->input('userId');
        if ($userIdInput && $userIdInput != "undefined") {
            $query->where('user_id', $userIdInput);
        }

        $fromDate = $this->request->input('from_date') ?? $this->request->input('fromdate');
        $toDate = $this->request->input('to_date') ?? $this->request->input('todate');

        if ($fromDate && $toDate) {
            $query->whereBetween('order_date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->whereDate('order_date', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('order_date', $toDate);
        }

        if ($this->request->filled('courier_name')) {
            $query->where('orders.courier_name', $this->request->courier_name);
        }

        $search = $this->request->input('search') ?? $this->request->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('created_at', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function($sub) use ($search){
                        $sub->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%")
                          ->orWhere('mobile', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('user', function($sub) use ($search){
                        $sub->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('company_name', 'LIKE', "%{$search}%")
                          ->orWhere('mobile', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('warehouse', function($sub) use ($search){
                        $sub->where('contact_name', 'LIKE', "%{$search}%")
                          ->orWhere('contact_number', 'LIKE', "%{$search}%")
                          ->orWhere('warehouse_name', 'LIKE', "%{$search}%");
                    })
                    ->orWhere('awb_number', 'LIKE', "%{$search}%")
                    ->orWhere('courier_name', 'LIKE', "%{$search}%")
                    ->orWhere('status_courier', 'LIKE', "%{$search}%")
                    ->orWhere('orders.id', 'LIKE', "%{$search}%")
                    ->orWhere('order_prefix', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('orders.id', 'desc')->get();
    }

	public function headings(): array
    {
        return [
            'Sr.No',
            'Seller Details',
            'Order ID',
            'AWB Number',
            'Courier Name',
            'Product Amount',
            'No Of Box',
            'SKU',
            'Customer Name',
            'Customer Address',
            'Customer City',
            'Customer State',
            'Customer Country',
            'Customer Pincode',
            'Payment Type',
            'Pickup Address',
            'Status',
            'Created At',
        ];
    }

    public function map($order): array
    {
		$orderItems = $order->orderItems ?? collect();
		$warehouse = $order->warehouse;
		$this->rowNumber++; // Increment on each map
		$customeraddr = $order->customerAddress;
		$customerName = trim((optional($order->customer)->first_name ?? '') . ' ' . (optional($order->customer)->last_name ?? ''));

		$productAmount = (float) $orderItems->sum(function ($item) {
			$amount = (float) ($item->amount ?? 0);
			$qty = (float) ($item->quantity ?? 1);
			return $amount * $qty;
		});

		$noOfBox = $order->weight_order == 2
			? (int) $orderItems->sum(function ($item) {
				return (int) data_get($item, 'dimensions.no_of_box', 0);
			})
			: (int) $orderItems->sum('quantity');

		$skuNumber = $orderItems
			->pluck('sku_number')
			->filter(fn($sku) => !empty($sku))
			->implode(', ');

		$sellerDetails = trim((optional($order->user)->name ?? '') . ' (' . (optional($order->user)->company_name ?? 'N/A') . ')');

		$pickupAddress = trim(implode(', ', array_filter([
			optional($warehouse)->warehouse_name,
			optional($warehouse)->address,
			optional($warehouse)->city,
			optional($warehouse)->state,
			optional($warehouse)->zip_code,
		])));

		return [
			$this->rowNumber,
			$sellerDetails,
			$order->id,
			$order->awb_number,
			$order->courier_name,
			number_format($productAmount, 2, '.', ''),
			$noOfBox,
			$skuNumber,
			$customerName,
			optional($customeraddr)->address,
			optional($customeraddr)->city,
			optional($customeraddr)->state,
			optional($customeraddr)->country,
			optional($customeraddr)->zip_code,
			strtoupper((string) $order->order_type),
			$pickupAddress,
			$order->status_courier,
			$order->created_at->format('Y-m-d H:i:s'),
		];
    }
}
