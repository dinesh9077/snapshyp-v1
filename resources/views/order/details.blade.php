@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Order Details')
@section('header_title','Order Details')
@section('content') 

<style>
    .mani-00-tetxt p{
        font-size: 14px;
    }
    
    @media(max-width: 1600px){
        .mani-00-tetxt p {
          font-size: 12px;
        }
    }
</style>

<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-frist-heder121 mt-2">
				
                <div class="row">
                    <div class="col-lg-8">
                        <div class="main-le-ri-text">
                            <div class="left-order-text">
                                <div class="mnain-frisnol-det m-0">
									@php
										$class = ($order->status_courier == "CANCELED") ? 'background: #80000024;color: #800000;' : 'background: #009F1D;color: #fff;';
									@endphp 
                                    <a href="{{ route('order') }}?weight_order={{ request('weight_order')}}&status={{ request('status')}}">
										<i class="mdi mdi-chevron-left"></i>
										 <b style="font-weight: 500;"> #{{ $order->order_prefix }}</b> </a> 
									<span style="{{ $class }}"> {{ $order->status_courier }} </span>
									@if($epodData && isset($epodData['data'][0]['pod']))
										<a href="{{ $epodData['data'][0]['pod'] }}" target="_blank" class="btn btn-sm btn-success text-white ml-2" style="padding: 2px 8px; font-size: 12px;"> 
											<i class="mdi mdi-file-document"></i> View ePOD
										</a> 
									@elseif($epodData && isset($epodData['pod_url']))
										<a href="{{ $epodData['pod_url'] }}" target="_blank" class="btn btn-sm btn-success text-white ml-2" style="padding: 2px 8px; font-size: 12px;"> 
											<i class="mdi mdi-file-document"></i> View ePOD
										</a> 
									@endif
								</div>
							</div> 
							<div class="right-order-text"> 
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-8">
						<div class="main-ord-1-text">
							<div class="main-hed-order-details">
								<div class="main-headet-det">
									<!-- <img src="{{asset('assets/images/dashbord/vo-1.png')}}"> -->
									<div class="dash1-ic">
										<i class="mdi mdi-file-outline"></i>
									</div>
									<h5> Order Details </h5>
								</div>
								
								<div class="row" style="padding-bottom: 10px;">
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Order created on channel </p>
											<h5> {{ date('Y M d h:i A',strtotime($order->order_date)) }} </h5>
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Payment </p>
											<div class="main-cont1-2" style="display: flex; column-gap: 10px;">
												<p class="{{ strtolower($order->order_type) }}" > {{ $order->order_type }} </p>
												<p style="color: #000;">{{ $order->order_type == "cod" ? $order->cod_amount : $order->invoice_amount }} </p>
											</div>
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Pickup Details </p> 
											<h5> {{ $order->warehouse->contact_name ?? 'N/A' }} </h5>
											<h5> {{ $order->warehouse->contact_number ?? 'N/A' }} </h5> 
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Pickup Address</p>
											<h5> {{ $order->warehouse->address ?? 'N/A' }} {{ $order->warehouse->city ?? '' }} {{ $order->warehouse->state ?? '' }} {{ $order->warehouse->country ?? '' }}, {{ $order->warehouse->zip_code ?? '' }} </h5>
										</div>
									</div>
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Invoice Number</p>
											<h5> {{ $order->invoice_no ?? '' }} </h5>
										</div>
									</div>
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Invoice Amount</p>
											<h5> {{ $order->invoice_amount ?? '' }} </h5>
										</div>
									</div>
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> E-waybill No.</p>
											<h5> {{ $order->ewaybillno ?? '' }} </h5>
										</div>
									</div>
									
								</div>
							</div>
						</div>
						<div class="main-ord-1-text">
							<div class="main-hed-order-details">
								<div class="main-headet-det">
									<div class="dash1-ic">
										<i class="mdi mdi-account-star-outline"></i>
									</div>
									<!-- <img src="{{asset('assets/images/dashbord/vo-2.png')}}"> -->
									<h5> Customer Details </h5>
								</div>
								
								<div class="row" style="padding-bottom: 10px;">
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Name </p>
											<h5> {{ $order->customer->first_name ?? '' }} {{ $order->customer->last_name ?? '' }} </h5>
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Contact No. </p>
											<h5> {{ $order->customer->mobile ?? '' }} </h5>
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Email </p>
											<h5> {{ $order->customer->email ?? '' }} </h5>
										</div>
									</div>
									
									<div class="col-lg-3 col-md-6 ship-col">
										<div class="mani-00-tetxt">
											<p> Customer Address </p>
											<h5> {{ $order->customerAddress->address ?? 'N/A' }} {{ $order->customerAddress->city ?? '' }} {{ $order->customerAddress->state ?? '' }} {{ $order->customerAddress->country ?? '' }}, {{ $order->customerAddress->zip_code ?? '' }} </h5>
										</div>
									</div>
								</div>
							</div>
						</div>
						 
						<div class="main-ord-1-text">
							<div class="main-hed-order-details">
								<div class="main-headet-det" style="border-bottom: none;">
									<div class="dash1-ic">
										<i class="mdi mdi-package-variant-closed"></i>
									</div>
									<!-- <img src="{{asset('assets/images/dashbord/vo-3.png')}}"> -->
									<h5> Product Details </h5>
								</div>
								
								<div class="table-responsive">
									
									<table class="table mb-0">
										<thead class="thead-light">
											<tr> 
												<th style="border: none;"> Product name </th> 
												<th style="border: none;"> Product Category </th> 
												<th style="border: none;"> Sku Number </th> 
												<th style="border: none;"> Hsn Number </th> 
												<th style="border: none;"> Qty (No Of Box)</th> 
												<th style="border: none;"> Amount </th> 
											</tr>
										</thead>
										@if($order->orderItems->isNotEmpty())
											<tbody>
												@foreach($order->orderItems as $orderItem)
												<tr style="border: none !important;"> 
													<td style="border: none;"> {{ $orderItem->product_name }} </td>   
													<td style="border: none;"> {{ $orderItem->product_category }} </td>   
													<td style="border: none;"> {{ $orderItem->sku_number }} </td>   
													<td style="border: none;"> {{ $orderItem->hsn_number }} </td>   
													<td style="border: none;"> {{ $orderItem->quantity }}  </td>
													<td style="border: none;"> {{ $orderItem->amount }} </td>   
												</tr>
												@endforeach 
												<tr style="border: none !important;"> 
													<td  style="border: none;"></td>
													<td  style="border: none;" colspan="4"> <b> Order Total </b> </td>
													<td  style="border: none;"> <b> {{ $order->total_amount }} </b> </td>
												</tr>
											</tbody>
										@endif
									</table>
								</div>
							</div>
						</div>
						
						<div class="main-ord-1-text">
							<div class="main-hed-order-details">
								<div class="main-headet-det">
									<div class="dash1-ic">
										<i class="mdi mdi-backup-restore"></i>
									</div>
									<!-- <img src="{{asset('assets/images/dashbord/vo-4.png')}}"> -->
									<h5> Package Details </h5>
								</div>
								@if($order->weight_order == 1)
									<div class="row" style="padding-bottom: 10px;">
										<div class="col-lg-3 col-md-6 ship-col">
											<div class="mani-00-tetxt">
												<p> Weight (in Kg) </p>
												<h5> {{ $order->weight ?? 0 }} </h5>
											</div>
										</div> 
										<div class="col-lg-3 col-md-6 ship-col">
											<div class="mani-00-tetxt">
												<p> Length (in cm) </p>
												<h5> {{ $order->length ?? 0 }} </h5>
											</div>
										</div>  
										<div class="col-lg-3 col-md-6 ship-col">
											<div class="mani-00-tetxt">
												<p> Height (in cm) </p>
												<h5> {{ $order->height ?? 0 }} </h5>
											</div>
										</div> 
										<div class="col-lg-3 col-md-6 ship-col">
											<div class="mani-00-tetxt">
												<p> Width (in cm) </p>
												<h5> {{ $order->width ?? 0 }} </h5>
											</div>
										</div>  
									</div>
								@else
									@if($order->orderItems->isNotEmpty())
										@foreach($order->orderItems as $orderItem)
											<div class="row" style="padding-bottom: 10px;">
												<div class="col-lg-3 col-md-6 ship-col">
													<div class="mani-00-tetxt">
														<p> Weight (in Kg) </p>
														<h5> {{ $orderItem->dimensions['weight'] ?? 0 }} </h5>
													</div>
												</div> 
												<div class="col-lg-3 col-md-6 ship-col">
													<div class="mani-00-tetxt">
														<p> Length (in cm) </p>
														<h5> {{ $orderItem->dimensions['length'] ?? 0 }} </h5>
													</div>
												</div>  
												<div class="col-lg-3 col-md-6 ship-col">
													<div class="mani-00-tetxt">
														<p> Height (in cm) </p>
														<h5> {{ $orderItem->dimensions['height'] ?? 0 }} </h5>
													</div>
												</div> 
												<div class="col-lg-3 col-md-6 ship-col">
													<div class="mani-00-tetxt">
														<p> Width (in cm) </p>
														<h5> {{ $orderItem->dimensions['width'] ?? 0 }} </h5>
													</div>
												</div>  
											</div>
										@endforeach
									@endif
								@endif
							</div>
						</div> 
					</div>
					
					 <div class="col-lg-4">
                        <div class="main-tba-show-order-1">
                            <div class="tabs effect-3">
                                <!-- tab-title -->
                                <input type="radio" id="tab-1" name="tab-effect-3" checked="checked">
                                <span> Activity Log </span>

                                <input type="radio" id="tab-2" name="tab-effect-3">
                                <span>Tracking Info</span>

                                <div class="line ease"></div>

                                <!-- tab-content -->
                                <div class="tab-content">
                                    <section id="tab-item-1">
                                        <div class="main-content-001">
											@foreach($orderActivities as $key => $row)
												<div class="step {{ $key == 0 ? 'completed' : '' }}  {{ $key == 1 ? 'active' : '' }}">
													<div class="v-stepper">
														<div class="circle"></div>
														<div class="line"></div>
													</div> 
													<div class="content">
														<h4 class="new-tracking-title"> {{ $row->activity_name }} </h4>
														<h6  class="new-tracking-des"> {{ date('d M Y, H:i:s', strtotime($row->created_at)) }} </h6>
													</div>
												</div>
											@endforeach 
                                        </div>
                                    </section>
                                    <section id="tab-item-2">
										@if(!empty($order->awb_number) && !empty($trackingHistories))
											<div class="main-content-001">
												@foreach($trackingHistories as $key => $trackingHistory) 
													@if($order->shipping_company_id == 1)
														<div class="step {{ $key == 0 ? 'completed' : '' }} {{ $key == 1 ? 'active' : '' }}">
															<div class="v-stepper">
																<div class="circle"></div>
																<div class="line"></div>
															</div> 
															<div class="content">
																<h4>{{ $trackingHistory['ScanDetail']['Scan'] ?? '' }}</h4>
																<h4>{{ $trackingHistory['ScanDetail']['Instructions'] ?? '' }}</h4>
																@if(isset($trackingHistory['ScanDetail']['ScanDateTime']))
																	<h6>{{ date('d M Y, h:i a', strtotime($trackingHistory['ScanDetail']['ScanDateTime'])) }}</h6>
																@endif
															</div>
														</div>
													@elseif($order->shipping_company_id == 2)
														<div class="step {{ $key == 0 ? 'completed' : '' }} {{ $key == 1 ? 'active' : '' }}">
															<div class="v-stepper">
																<div class="circle"></div>
																<div class="line"></div>
															</div> 
															<div class="content">
																<h4>{{ $trackingHistory['statusCodeDescription'] ?? ($trackingHistory['status_name'] ?? ($trackingHistory['activity'] ?? '')) }}</h4>
																<h6 class="text-muted">{{ $trackingHistory['currentLocation'] ?? ($trackingHistory['location_name'] ?? ($trackingHistory['location'] ?? '')) }}</h6>
																@php
																	$timestamp = $trackingHistory['statusDateTime'] ?? ($trackingHistory['date_at'] ?? ($trackingHistory['date'] ?? ($trackingHistory['created_at'] ?? ($trackingHistory['scan_date_time'] ?? ''))));
																@endphp
																@if($timestamp)
																	<h6>{{ date('d M Y, h:i a', strtotime($timestamp)) }}</h6>
																@endif
															</div>
														</div>
													@endif  
												@endforeach 
											</div>
										@else	
											<p>No Data Available</p>
										@endif
                                    </section>
                                </div>
                            </div>
                        </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection