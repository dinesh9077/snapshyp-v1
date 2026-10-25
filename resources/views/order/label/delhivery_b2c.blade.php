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
	
	/* Optional: Add input fields */
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
			<button onclick="printLabels()" style="margin-bottom: 15px; padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">
				Print Labels 🖨️
			</button>
			<div class="main-order-page-1" id="printArea">
				<div class="row main-order-001">
					<div class="row col-md-10 m-auto"> 
						<div class="col-6 mb-5">
							<div class="awb-table" >
								<table style="width: 100%; border-collapse: collapse; border: 1px solid black; color: black;">
									<!-- Logo, AWB -->
									<tr>
										<td rowspan="3" class="logo-cell" style="text-align: center; border: 1px solid black; padding: 20px;">
											<img src="{{ url('storage/settings', config('setting.header_logo')) }}" width="200" alt="">
										</td>
										<td class="right-cell" style="border: 1px solid black; padding: 5px;">
											AWB : {{ $order->awb_number }}
										</td>
									</tr>
									
									<!-- Pickup Code -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;">
											Pickup Code : {{ trim($order->warehouse->zip_code ?? '') }}
										</td>
									</tr>
									
									<!-- Date -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;">
											{{ $order->created_at->format('d M, Y') }}
										</td>
									</tr>
									
									<!-- Order Type, Order Prefix -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;text-align: center;">
											<h5 style="font-weight: bold;">{{ $order->order_type }}</h5>
										</td>
										<td style="border: 1px solid black; padding: 5px;text-align: center;">
											<h3 style="margin: 5px 0;">{{ $order->order_prefix }}</h3>
										</td>
									</tr>
									
									<!-- Barcode -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;text-align: center;">
											<img src="https://barcode.tec-it.com/barcode.ashx?data={{ $order->awb_number }}&multiplebarcodes=false&translate-esc=true&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0"
											alt="Barcode" width="200">
										</td>
										
									</tr>
									
									<!-- INR, Destination Pincode -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;text-align: center;">INR {{ $order->total_amount }}</td>
										<td style="border: 1px solid black; padding: 5px;text-align: center;">
											Destination Pincode: {{ trim($order->customerAddress->zip_code ?? '') }}
										</td>
									</tr>
									
									<!-- Consignee Name, Order ID -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Consignee Name:</h5>
											<span>{{ trim($order->customer->first_name ?? '') . ' ' . trim($order->customer->last_name ?? '') }}</span>
										</td>
										<td style="border: 1px solid black; padding: 5px; text-align: center;font-weight: bold;">
											{{ $productNamesString }}
											<br>
											{{ $order->is_fragile_item == 1 ? 'My Package Contain Fragile Item' : '' }}
										</td>
									</tr>
									
									<!-- Shipping Address -->
									<tr>
										<td style="border: 1px solid black; padding: 5px;" colspan="2">
											<h5 style="font-weight: bold;" class="mb-0">Shipping Address:</h5>
											<p style="font-size: small;">
												{{ trim($order->customerAddress->address ?? '') }},
												<b>{{ trim($order->customerAddress->zip_code ?? '') }}</b>,
												{{ trim($order->customerAddress->country ?? '') }}.
												Mobile: {{ $order->customer->mobile ?? '' }}
											</p>
										</td>
									</tr>
									
									<!-- Return Address -->
									<tr>
										<td colspan="2" style="border: 1px solid black; padding: 5px;">
											<h5 style="font-weight: bold;" class="mb-0">Return Address:</h5>
											<p style="font-size: small;">
												{{ trim($order->warehouse->address ?? '') }},
												{{ trim($order->warehouse->city ?? '') }}
												{{ trim($order->warehouse->state ?? '') }}
												{{ trim($order->warehouse->country ?? '') }}
												<b>{{ trim($order->warehouse->zip_code ?? '') }}</b>
											</p>
										</td>
									</tr>
								</table>
							</div>
						</div>
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
	margin: 0; /* Removes browser default margins */
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
