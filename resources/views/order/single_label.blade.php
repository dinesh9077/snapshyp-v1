<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Shipping Label</title>
	</head>
	<style>
		@page{
			margin: 20px !important;
		}
	</style>
	<body>
		<table style="width: 50%; margin: ; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; border: 1px solid black;">
			<tr>
				<td colspan="4" style="padding: 10px 10px; width: 80%; vertical-align: middle; font-size: 25px; font-weight: bold; border: 1px solid black;">
					@if (isset($preference) && $preference->label_company_logo)
						<img src="{{ public_path('storage/settings/label/' . $preference->label_company_logo) }}" width="150" alt="">
					@endif
				</td>
				<td colspan="1" style="padding: 0; text-align: center ; width: 20%; center; border: 1px solid black; padding: 0px 20px;">
					<div style="height: 50px; width: 100%;">
						@php
							$logoPath = public_path('storage/shipping-logo/' . $shipping->logo);
							if (!empty($order->courier_logo)) {
                                if (filter_var($order->courier_logo, FILTER_VALIDATE_URL)) {
                                    $logoPath = $order->courier_logo; 
                                } elseif (file_exists(public_path('storage/courier-logo/' . $order->courier_logo))) {
                                    $logoPath = public_path('storage/courier-logo/' . $order->courier_logo);
                                }
                            }
						@endphp
						<img src="{{ $logoPath }}" alt="LOGO"
						style="max-height: 100%; max-width: 100%; width: auto; height: auto; display: block; margin-left: auto; margin-right: auto;">	
					</div>
					<p style="font-weight: bold; margin-top: 5px; font-size: 11px;">{{ $order->shipping_mode }}</p>
				</td>
			</tr>
			

			<?php
				$pickupAddress = '';
                if ($order->warehouse) {
                    $pickupAddress = trim(
					$order->warehouse->address . ' ' .
					$order->warehouse->city . ' ' .
					$order->warehouse->state . ' ' .
					$order->warehouse->zip_code . ', ' .
					$order->warehouse->country
                    );
				}
				
				$customerAddress = $customerAddr->address.' '.$customerAddr->city.' '.$customerAddr->state.' '.$customerAddr->zip_code.','.$customerAddr->country;
			?>
			<tr>
				<td colspan="3" style="padding: 5px; font-size: 11px; border: 1px solid black; vertical-align: top;">
					<p style="margin-top: 5px;"> <span style="font-weight: bold;">Deliver To :</span> {{ $customer->first_name.' '.$customer->last_name }}</p>
					@if(empty($hideLabel['hide_contact']))
						<p style="margin-top: 5px;"><span style="font-weight: bold;">Contact :</span> {{ $customer->mobile }}</p>
					@endif
					<p style="margin-top: 5px;"><span style="font-weight: bold;">Address :</span> {{ $customerAddress }}</p>
					<p style="margin-top: 5px;"><span style="font-weight: bold;">Pin :</span> {{ $customerAddr->zip_code }}</p>
					@if ($order->order_type == 'cod')
						<p style="margin-top: 5px;"><span style="font-weight: bold;">COD Amount :</span> Rs. {{ $order->cod_amount }}</p>
					@else
						<p style="margin-top: 5px;"><span style="font-weight: bold;">Payment Type :</span> Prepaid</p>
					@endif
				</td>
				
				<td colspan="2" style="padding: 5px; font-size: 11px; border: 1px solid black; vertical-align: top;">
					<p style="margin-top: 7px;"><span style="font-weight: bold;">Order Id :</span> {{ $order->order_prefix }}</p>
					@if($order->invoice_no)
						<p style="margin-top: 7px;"><span style="font-weight: bold;">Ref./Invoice# :</span> {{ $order->invoice_no }}</p>
					@endif
					<p style="margin-top: 7px;"><span style="font-weight: bold;">Date :</span> {{ $order->order_date }}</p>
					<p style="margin-top: 7px;"><span style="font-weight: bold;">Invoice Value :</span> Rs. {{ $order->total_amount }}</p>
					
					@if(empty($hideLabel['hide_weight']))
						<p style="margin-top: 7px;">
							<span style="font-weight: bold;">Weights :</span> {{ $order->weight }} KG
						</p> 
					@endif
					@if($order->shopify_order_id)
						<p style="margin-top: 7px;">
							<span style="font-weight: bold;">Shopify OrderId :</span> {{ $order->shopify_order_id }}
						</p> 
					@endif
				</td>
			</tr> 
			@if(empty($hideLabel['hide_product']))
				<tr>
					<th style="padding: 5px; border: 1px solid black; vertical-align: top;">Product category</th>
					<th style="padding: 5px; border: 1px solid black; vertical-align: top;">Product Name</th>
					<th style="padding: 5px; border: 1px solid black; vertical-align: top;">Sku</th>
					<th style="padding: 5px; width: 70px; border: 1px solid black; vertical-align: top;">Qty</th>
					<th style="padding: 5px; width: 100px; border: 1px solid black; vertical-align: top;">Price</th>
				</tr>
				@php 
					$totalQuntity = 0;
					$totalAmount = $order->total_amount;
				@endphp
				@foreach($products as $product)
					@php 
						$totalQuntity += $product->quantity; 
					@endphp
					<tr>
						<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $product->product_category }}</td>
						<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $product->product_name }}</td>
						<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $product->sku_number }}</td>
						<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $product->quantity }}</td>
						<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $product->amount }}</td>
					</tr>
				@endforeach
				<tr>
					<td colspan="3" style="text-align: right; font-weight: bold; padding: 5px; border: 1px solid black; vertical-align: top;">Total</td>
					<td style="padding: 5px; border: 1px solid black; vertical-align: top;">{{ $totalQuntity }}</td>
					<td style="padding: 5px; border: 1px solid black; vertical-align: top;">Rs. {{ $totalAmount }}</td>
				</tr>
			@endif
			
			<tr>
				<td colspan="5" style="padding: 10px 0px; text-align: center; border: 1px solid black;">
					<div style="height: 50px; width: 100%;">
						@if(!empty($order->shipment_id))  
							<img src="data:image/png;base64,{{ $orderIdBarcodePng }}" alt="Barcode" style="display: block; height:50px; width:70%; margin: 0 auto;" /> 
						@endif
					</div>
					<p style="font-size: 18px;margin:0">{{ $order->awb_number }}</p> 
				</td> 
			</tr>
			
			@if(empty($hideLabel['hide_address']) || empty($hideLabel['hide_mobile']))
				<tr>
					<td colspan="5" style="padding: 0PX 5px; border: 1px solid black; vertical-align: top;">
						<p style="font-weight: bold; ">If not delivered, Return to:</p>
						@if(empty($hideLabel['hide_address']))
							<p>{{ $pickupAddress }}</p>
						@endif
						<p>Contact Name: <span>{{ $order->warehouse->contact_name ?? '' }}</span></p>
						@if(empty($hideLabel['hide_mobile']))
							<p>Phone: {{ $order->warehouse->contact_number ?? '' }}</p>
						@endif
					</td>
				</tr>
			@endif
			<tr>
                <td colspan="5" style="border: 1px solid black; padding: 5px; text-align: right; vertical-align: middle;">
                    <span style="font-size: 10px; font-weight: bold; vertical-align: middle; font-family: Arial, sans-serif;">Powered by </span>
                    <img src="{{ public_path('storage/settings/' . config('setting.header_logo')) }}" width="80" alt="ShipFiGO" style="vertical-align: middle;">
                </td>
            </tr>
		</table>
	</body>
</html>