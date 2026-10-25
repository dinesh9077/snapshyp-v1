<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderListingExport implements FromCollection, WithHeadings, WithMapping
{
	private Collection $orders;
	private int $rowNumber = 0;

	public function __construct(Collection $orders)
	{
		$this->orders = $orders;
	}

	public function collection()
	{
		return $this->orders;
	}

	public function headings(): array
	{
		return [
			'Sr No',
			'Order Id',
			'Order Prefix',
			'Seller Name',
			'Seller Company',
			'Seller Mobile',
			'Warehouse Name',
			'Customer Name',
			'Customer Mobile',
			'Customer Address',
			'Payment Type',
			'Amount',
			'Courier',
			'AWB Number',
			'Status',
			'Dead Weight (Kg)',
			'Length (cm)',
			'Width (cm)',
			'Height (cm)',
			'Volumetric Weight (Kg)',
			'Delivery Date',
			'Created At',
		];
	}

	public function map($order): array
	{
		$this->rowNumber++;
		$weight = (float) ($order->weight ?? 0);
		$length = (float) ($order->length ?? 0);
		$width = (float) ($order->width ?? 0);
		$height = (float) ($order->height ?? 0);
		$volumetric = ($length * $width * $height) / 5000;
		$amount = strtolower((string) $order->order_type) === 'cod'
			? (float) ($order->cod_amount ?? 0)
			: (float) ($order->invoice_amount ?? 0);

		return [
			$this->rowNumber,
			$order->id,
			$order->order_prefix,
			optional($order->user)->name,
			optional($order->user)->company_name,
			optional($order->user)->mobile,
			optional($order->warehouse)->warehouse_name,
			trim((optional($order->customer)->first_name ?? '') . ' ' . (optional($order->customer)->last_name ?? '')),
			optional($order->customer)->mobile,
			optional($order->customerAddress)->address,
			strtoupper((string) $order->order_type),
			$amount,
			$order->courier_name,
			$order->awb_number,
			$order->status_courier,
			number_format($weight, 2, '.', ''),
			number_format($length, 2, '.', ''),
			number_format($width, 2, '.', ''),
			number_format($height, 2, '.', ''),
			number_format($volumetric, 2, '.', ''),
			$order->delivery_date,
			optional($order->created_at)->format('Y-m-d H:i:s'),
		];
	}
}
