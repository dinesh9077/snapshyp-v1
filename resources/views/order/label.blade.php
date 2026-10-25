@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipments Label')
@section('header_title', 'Shipments Label')
@section('content')

<div class="content-page">
    <div class="content"> 
        <div class="container-fluid">
            <button onclick="printLabels()" style="margin-bottom: 15px; padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">
                Print Labels 🖨️
            </button>

            <div class="main-order-page-1" id="printArea">
                <div class="row main-order-001">
                    <div class="row col-md-10 m-auto">
                        @foreach($wayBills as $index => $wayBill)
                        <div class="col-6 mb-3">
                            <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                                <tbody>
                                    <!-- Logo Row -->
                                    <tr>
                                        <td colspan="2" style="text-align: center; border: 1px solid black;">
                                            <img src="{{ url('storage/shipping-logo', ($order->shippingCompany->logo ?? '')) }}" width="100" alt="">
                                        </td>
                                        <td colspan="2" style="text-align: center; border: 1px solid black;">
                                            <img src="{{ url('storage/settings', config('setting.header_logo')) }}" width="100" alt="">
                                        </td>
                                    </tr>

                                    <!-- Master, LRN, Order Type, Date -->
                                    <tr>
                                        <td colspan="2" style="border: 1px solid black; padding: 5px;">Master: {{ $order->awb_number }}</td>
                                        <td rowspan="2" style="text-align: center; border: 1px solid black;"><h6>{{ $order->order_type }}</h6></td>
                                        <td rowspan="2" style="text-align: center; border: 1px solid black;"><h6>{{ $order->created_at->format('d M, Y') }}</h6></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="border: 1px solid black; padding: 5px;">LRN: {{ $order->lr_no }}</td>
                                    </tr>

                                    <!-- Barcode -->
                                    <tr>
                                        <td colspan="4" style="text-align: center; border: 1px solid black;">
                                            <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $wayBill['awb_number'] }}&multiplebarcodes=false&translate-esc=true&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0" alt="Barcode">
                                        </td>
                                    </tr>

                                    <!-- Waybill Number -->
                                    <tr>
                                        <td colspan="4" style="text-align: center; border: 1px solid black;">
                                            <h1 style="margin: 5px 0;">{{ $wayBill['awb_number'] }}</h1>
                                        </td>
                                    </tr>

                                    <!-- Order ID, Box Info -->
                                    <tr>
                                        <td colspan="2" style="border: 1px solid black; padding: 5px;">
                                            <p style="font-weight: bold;">Order ID: {{ $wayBill['id'] }}</p>
                                            <p style="font-weight: bold;">Box: {{ $index + 1 }} / {{ count($wayBills) }}</p>
                                        </td>
                                        <td style="text-align: center; border: 1px solid black;">{{ $index == 0 ? 'Master' : 'Child' }}</td>
                                        <td style="text-align: center; border: 1px solid black;">
                                            <p style="font-weight: bold;">Destination Pincode:</p>
                                            <h4 style="font-weight: bold; margin: 5px 0;">{{ trim($order->customerAddress->zip_code ?? '') }}</h4>
                                        </td>
                                    </tr>

                                    <!-- Consignee & Shipping Address -->
                                    <tr>
                                        <td colspan="3" style="border: 1px solid black; padding: 5px;">
                                            <h6 style="font-weight: bold;">Consignee Name:</h6>
                                            <p>{{ trim($order->customer->first_name ?? '') . ' ' . trim($order->customer->last_name ?? '') }}</p>
                                            <h6 style="font-weight: bold;">Shipping Address:</h6>
                                            <p style="font-size: small;">
                                                {{ trim($order->customerAddress->address ?? '') }}, 
                                                <b>{{ trim($order->customerAddress->zip_code ?? '') }}</b>, 
                                                {{ trim($order->customerAddress->country ?? '') }}. 
                                                Mobile: {{ $order->customer->mobile ?? '' }}
                                            </p>
                                        </td>
                                        <td style="text-align: center; border: 1px solid black; font-weight: bold;">{{ $wayBill['product_discription'] }}</td>
                                    </tr>

                                    <!-- Return Address -->
                                    <tr>
                                        <td colspan="4" style="border: 1px solid black; padding: 5px;">
                                            <h6 style="font-weight: bold;">Return Address:</h6>
                                            <p style="font-size: small;">
                                                {{ trim($order->warehouse->address ?? '') }}, 
                                                {{ trim($order->warehouse->city ?? '') }} 
                                                {{ trim($order->warehouse->state ?? '') }} 
                                                {{ trim($order->warehouse->country ?? '') }} 
                                                <b>{{ trim($order->warehouse->zip_code ?? '') }}</b>
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @endforeach 
						@if($docWaybill)
							<div class="col-6 mb-3">
								<table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
									<tbody>
										<!-- Logo Row -->
										<tr>
											<td colspan="2" style="text-align: center; border: 1px solid black;">
												<img src="{{ url('storage/shipping-logo', ($order->shippingCompany->logo ?? '')) }}" width="100" alt="">
											</td>
											<td colspan="2" style="text-align: center; border: 1px solid black;">
												<img src="{{ url('storage/settings', config('setting.header_logo')) }}" width="100" alt="">
											</td>
										</tr>

										<!-- Master, LRN, Order Type, Date -->
										<tr>
											<td colspan="2" style="border: 1px solid black; padding: 5px;">Master: {{ $order->awb_number }}</td>
											<td rowspan="2" style="text-align: center; border: 1px solid black;"><h6>{{ $order->order_type }}</h6></td>
											<td rowspan="2" style="text-align: center; border: 1px solid black;"><h6>{{ $order->created_at->format('d M, Y') }}</h6></td>
										</tr>
										<tr>
											<td colspan="2" style="border: 1px solid black; padding: 5px;">LRN: {{ $order->lr_no }}</td>
										</tr>

										<!-- Barcode -->
										<tr>
											<td colspan="4" style="text-align: center; border: 1px solid black;">
												<img src="https://barcode.tec-it.com/barcode.ashx?data={{ $docWaybill }}&multiplebarcodes=false&translate-esc=true&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0" alt="Barcode">
											</td>
										</tr>

										<!-- Waybill Number -->
										<tr>
											<td colspan="4" style="text-align: center; border: 1px solid black;">
												<h1 style="margin: 5px 0;">{{ $docWaybill }}</h1>
											</td>
										</tr>

										<!-- Order ID, Box Info -->
										<tr>
											<td colspan="2" style="border: 1px solid black; padding: 5px;">
												<p style="font-weight: bold;"></p>
												<p style="font-weight: bold;">MPS: Document</p>
											</td>
											<td style="text-align: center; border: 1px solid black;">Document</td>
											<td style="text-align: center; border: 1px solid black;">
												<p style="font-weight: bold;">Destination Pincode:</p>
												<h4 style="font-weight: bold; margin: 5px 0;">{{ trim($order->customerAddress->zip_code ?? '') }}</h4>
											</td>
										</tr>

										<!-- Consignee & Shipping Address -->
										<tr>
											<td colspan="3" style="border: 1px solid black; padding: 5px;">
												<h6 style="font-weight: bold;">Consignee Name:</h6>
												<p>{{ trim($order->customer->first_name ?? '') . ' ' . trim($order->customer->last_name ?? '') }}</p>
												<h6 style="font-weight: bold;">Shipping Address:</h6>
												<p style="font-size: small;">
													{{ trim($order->customerAddress->address ?? '') }}, 
													<b>{{ trim($order->customerAddress->zip_code ?? '') }}</b>, 
													{{ trim($order->customerAddress->country ?? '') }}. 
													Mobile: {{ $order->customer->mobile ?? '' }}
												</p>
											</td>
											<td style="text-align: center; border: 1px solid black; font-weight: bold;">Document</td>
										</tr>

										<!-- Return Address -->
										<tr>
											<td colspan="4" style="border: 1px solid black; padding: 5px;">
												<h6 style="font-weight: bold;">Return Address:</h6>
												<p style="font-size: small;">
													{{ trim($order->warehouse->address ?? '') }}, 
													{{ trim($order->warehouse->city ?? '') }} 
													{{ trim($order->warehouse->state ?? '') }} 
													{{ trim($order->warehouse->country ?? '') }} 
													<b>{{ trim($order->warehouse->zip_code ?? '') }}</b>
												</p>
											</td>
										</tr>
									</tbody>
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
