@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipments Label')
@section('header_title', 'Shipments Label')
@section('content')
<style>
	.awb-table {
	width: 530px;
	margin: 20px;
	font-family: 'Poppins', sans-serif;
	}
	
	.awb-table table {
	width: 100%;
	border-collapse: collapse;
	border: 2px solid #000;
	}
	
	.awb-table td {
	border: 1px solid #000;
	padding: 8px;
	font-size: 14px;
	}
	
	.logo-cell {
	width: 150px;
	height: 120px;
	vertical-align: top;
	}
	
	.right-cell {
	border-bottom: 1px solid #000;
	}
	
	.awb-table input {
	width: 100%;
	border: none;
	border-bottom: 1px solid #999;
	outline: none;
	padding: 4px 0;
	}
</style>
<div class="content-page">
	<div class="content">
		<div class="container-fluid">
			<button onclick="printLabels()"
			style="margin-bottom: 15px; padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">
				Print Labels 🖨️
			</button>
			
			<div class="main-order-page-1" id="printArea">
				<div class="row main-order-001">
					<div class="row col-md-10 m-auto"> 
						@foreach($wayBills as $index => $wayBill)
							<div class="col-6 mb-5">
								<table
								style="width: 100%; border-collapse: collapse; border: 1px solid black; color: black;">
									<!-- Logo Row -->
									<tr>
										<td rowspan="4" style="text-align: center; border: 1px solid black; padding: 20px;">
											<img src="{{ url('storage/settings', config('setting.header_logo')) }}"
											width="100" alt="">
										</td>
										<td style="text-align: center; border: 1px solid black; padding: 5px;">
											Master : {{ $order->awb_number ?? 'master' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center; border: 1px solid black; padding: 5px;">
											LRN : {{ $order->lr_no ?? 'LRN' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center; border: 1px solid black;padding: 5px;">
											{{ isset($order->created_at) ? $order->created_at->format('d M, Y') : 'N/A' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center;  ">
											<h5>{{ $order->order_type ?? 'N/A' }}</h5>
										</td>
									</tr>
									
									<!-- Waybill Info -->
									<tr>
										<td style="text-align: center; border: 1px solid black;">
											Order ID: {{ $wayBill['id'] }}
										</td>
										<td style="text-align: center; border: 1px solid black;">
											<h5>{{ $wayBill['awb_number'] ?? '' }}</h5>
										</td>
									</tr>
									
									<!-- Barcode -->
									<tr>
										<td colspan="2" style="text-align: center; border: 1px solid black;">
											<img src="https://barcode.tec-it.com/barcode.ashx?data={{ $wayBill['awb_number'] }}&multiplebarcodes=false&translate-esc=true&unit=Fit&dpi=96&imagetype=Gif&rotation=0"
											alt="Barcode">
										</td>
									</tr>
									
									<!-- Type & Pincode -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;"> 
											<h5 style="font-weight: bold; display: inline;">Box:</h5>
											<span>{{ $index + 1 }} / {{ count($wayBills) }}</span>
										</td>
										<td style="text-align: center; border: 1px solid black;">
											<h5 style="font-weight: bold; display: inline;">Destination Pincode :</h5>
											<span>{{ $order->customerAddress->zip_code ?? 'destination pin code' }}</span>
										</td>
									</tr>
									
									<!-- Consignee -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold; display: inline;">Consignee Name :</h5>
											<span>{{ ($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? 'consignee name') }}</span>
										</td>
										<td style="text-align: center; border: 1px solid black; ">
											{{ $index == 0 ? 'Master' : 'Child' }}
											<br>
											{{ $order->is_fragile_item == 1 ? 'My Package Contain Fragile Item' : '' }}
										</td>
									</tr>
									
									<!-- Shipping Address -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Shipping Address :</h5>
											{{ isset($order->customerAddress) ?
											trim($order->customerAddress->address ?? '') . ', ' .
											trim($order->customerAddress->zip_code ?? '') . ', ' .
											trim($order->customerAddress->country ?? '') . '. ' .
											'Mobile: ' . ($order->customer->mobile ?? '')
											: '' }}
										</td>
									</tr>
									
									<!-- Return Address -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Return Address :</h5>
											{{ isset($order->warehouse) ?
											trim($order->warehouse->address ?? '') . ', ' .
											trim($order->warehouse->city ?? '') . ' ' .
											trim($order->warehouse->state ?? '') . ' ' .
											trim($order->warehouse->country ?? '') . ' ' .
											trim($order->warehouse->zip_code ?? '')
											: '' }}
										</td>
									</tr>
								</table>
							</div>
						@endforeach
						
						@if($docWaybill)
							<div class="col-6 mb-5">
								<table
								style="width: 100%; border-collapse: collapse; border: 1px solid black; color: black;">
									<!-- Logo Row -->
									<tr>
										<td rowspan="4" style="text-align: center; border: 1px solid black; padding: 20px;">
											<img src="{{ url('storage/settings', config('setting.header_logo')) }}"
											width="100" alt="">
										</td>
										<td style="text-align: center; border: 1px solid black; padding: 5px;">
											Master : {{ $order->awb_number ?? 'master' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center; border: 1px solid black; padding: 5px;">
											LRN : {{ $order->lr_no ?? 'LRN' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center; border: 1px solid black;padding: 5px;">
											{{ isset($order->created_at) ? $order->created_at->format('d M, Y') : 'N/A' }}
										</td>
									</tr>
									<tr>
										<td style="text-align: center;  ">
											<h5>{{ $order->order_type ?? 'N/A' }}</h5>
										</td>
									</tr>
									
									<!-- Waybill Info -->
									<tr>
										<td style="text-align: center; border: 1px solid black;">
											 Order ID: {{ $order->order_prefix }}
										</td>
										<td style="text-align: center; border: 1px solid black;">
											<h5>{{ $docWaybill ?? '' }}</h5>
										</td>
									</tr>
									
									<!-- Barcode -->
									<tr>
										<td colspan="2" style="text-align: center; border: 1px solid black;">
											<img src="https://barcode.tec-it.com/barcode.ashx?data={{ $docWaybill }}&multiplebarcodes=false&translate-esc=true&unit=Fit&dpi=96&imagetype=Gif&rotation=0"
											alt="Barcode">
										</td>
									</tr>
									
									<!-- Type & Pincode -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;"> 
											<h5 style="font-weight: bold; display: inline;">MPS:</h5>
											<span>Document</span>
										</td>
										<td style="text-align: center; border: 1px solid black;">
											<h5 style="font-weight: bold; display: inline;">Destination Pincode :</h5>
											<span>{{ $order->customerAddress->zip_code ?? 'destination pin code' }}</span>
										</td>
									</tr>
									
									<!-- Consignee -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold; display: inline;">Consignee Name :</h5>
											<span>{{ ($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? 'consignee name') }}</span>
										</td>
										<td style="text-align: center; border: 1px solid black; ">
											Document
											<br>
											{{ $order->is_fragile_item == 1 ? 'My Package Contain Fragile Item' : '' }}
										</td>
									</tr>
									
									<!-- Shipping Address -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Shipping Address :</h5>
											{{ isset($order->customerAddress) ?
											trim($order->customerAddress->address ?? '') . ', ' .
											trim($order->customerAddress->zip_code ?? '') . ', ' .
											trim($order->customerAddress->country ?? '') . '. ' .
											'Mobile: ' . ($order->customer->mobile ?? '')
											: '' }}
										</td>
									</tr>
									
									<!-- Return Address -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Return Address :</h5>
											{{ isset($order->warehouse) ?
											trim($order->warehouse->address ?? '') . ', ' .
											trim($order->warehouse->city ?? '') . ' ' .
											trim($order->warehouse->state ?? '') . ' ' .
											trim($order->warehouse->country ?? '') . ' ' .
											trim($order->warehouse->zip_code ?? '')
											: '' }}
										</td>
									</tr>
								</table>
							</div>
						@endif
					</div>
				</div>
			</div>
			  
		</div>
	</div>
</div>

<!-- JavaScript for Printing -->
<style>
	@page {
	size: auto;
	margin: 0;
	/* Removes browser default margins */
	}
	
	body {
	margin: 0;
	padding: 0;
	}
</style>

<script>
	function printLabels() {
		var printContent = document.getElementById('printArea').innerHTML;
		var originalContent = document.body.innerHTML;
		
		document.body.innerHTML = printContent;
		window.print();
		
		document.body.innerHTML = originalContent;
		window.location.reload(); // Refresh to restore original layout
	}
</script>


@endsection
 