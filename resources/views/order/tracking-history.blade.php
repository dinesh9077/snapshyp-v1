@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Order Tracking History')
@section('content')

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <section class="main-order-sec">
                <div class="custom-container">
                    <div class="main-cls-1">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="main-head-text">
                                    <h4> Order Information </h4>
                                    <ul class="list-ordr-detal">
                                        <li> <i class="mdi mdi-briefcase"></i> Order Number : <span> {{ $order->order_prefix }} </span> </li>
                                        <li> <i class="mdi mdi-cube-send"></i> Courier : <span> {{ $order->courier_name }} </span> </li>
                                        <li> <i class="mdi mdi-cube-send"></i> Awb No : <span> {{ $order->awb_number }} </span> </li>
										@if($order->shipping_company_id == 2 && $order->lr_no)
											<li> <i class="mdi mdi-cube-send"></i> LR No : <span> {{ $order->lr_no }} </span> </li>
										@endif
                                       <li> <i class="mdi mdi-cube-send"></i> Expected Delivery Date : <span> {{ $expectedDeliveryDate }} </span> </li>
									   @if($epodData && isset($epodData['data'][0]['pod']))
									   	<li class="mt-2">
											<a href="{{ $epodData['data'][0]['pod'] }}" target="_blank" class="btn btn-sm btn-success text-white">
												<i class="mdi mdi-file-document"></i> View Delivery Proof (ePOD)
											</a>
										</li>
									   @elseif($epodData && isset($epodData['pod_url']))
									   <li class="mt-2">
											<a href="{{ $epodData['pod_url'] }}" target="_blank" class="btn btn-sm btn-success text-white">
												<i class="mdi mdi-file-document"></i> View Delivery Proof (ePOD)
											</a>
										</li>
									   @endif
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="main-head-text custm-pad ">
                                    <h4> Tracking History </h4>
                                    <div class="main-steper-11">
                                        <div class="stepper d-flex flex-column mt-4 ml-2">
											 @foreach($trackingHistories as $key => $trackingHistory)
												<div class="d-flex mb-1">
													<div class="d-flex flex-column pr-4 align-items-center">
														<div class="rounded-circle bg-primary text-white mb-1 main-12-10"><img src="{{asset('assets/images/order-1/fast-delivery.png')}}"></div>
														<div class="line h-100"></div>
													</div>
													@if($shippingCompany->id == 1)
                                                        <div class="main-flex-raet">
																 <div class="main-cotn-nt">
																<h5 class="text-dark"> Activity : {{ $trackingHistory['status'] }} </h5>
																<p class="lead-custm"> {{ $trackingHistory['location'] }} </p>
																<h6 class="lead-custm"> {{ $trackingHistory['remarks'] }} </h6>
															</div>

															<div class="main-cotn-nt">
																<h6 class="lead-custm"> {{ date('M d, Y  h:i a', strtotime($trackingHistory['created'])) }} </h6>
															</div>
														</div>
													@elseif($shippingCompany->id == 2 && is_array($trackingHistory))
                                                        <div class="main-flex-raet">
															<div class="main-cotn-nt">
																<h5 class="text-dark"> Activity : {{ $trackingHistory['statusCodeDescription'] ?? ($trackingHistory['status_name'] ?? ($trackingHistory['activity'] ?? ($trackingHistory['status'] ?? ($trackingHistory['scan_status'] ?? ($trackingHistory['track_status'] ?? ($trackingHistory['event'] ?? ($trackingHistory['event_name'] ?? ($trackingHistory['statusName'] ?? (is_string($trackingHistory) ? $trackingHistory : 'N/A'))))))))) }} </h5>
																<p class="lead-custm"> {{ $trackingHistory['currentLocation'] ?? ($trackingHistory['location_name'] ?? ($trackingHistory['location'] ?? ($trackingHistory['city'] ?? ($trackingHistory['scanned_location'] ?? ($trackingHistory['hub'] ?? ($trackingHistory['center'] ?? ($trackingHistory['cityName'] ?? ''))))))) }} </p>
																<h6 class="lead-custm"> {{ $trackingHistory['reasonCodeDescription'] ?? ($trackingHistory['remarks'] ?? ($trackingHistory['message'] ?? ($trackingHistory['remark'] ?? ($trackingHistory['reason'] ?? ($trackingHistory['description'] ?? ''))))) }} </h6>
															</div>

															<div class="main-cotn-nt">
																<h6 class="lead-custm">
																	@php
																		$timestamp = $trackingHistory['statusDateTime'] ?? ($trackingHistory['date_at'] ?? ($trackingHistory['date'] ?? ($trackingHistory['created_at'] ?? ($trackingHistory['scan_date_time'] ?? ($trackingHistory['event_at'] ?? '')))));
																	@endphp
																	@if($timestamp)
																		{{ date('M d, Y', strtotime($timestamp)) }}
																		{{ date('h:i a', strtotime($timestamp)) }}
																	@elseif(isset($trackingHistory['time']))
																		{{ $trackingHistory['time'] }}
																	@endif
																</h6>
															</div>
														</div>
													@else
                                                        <div class="main-flex-raet">
															<div class="main-cotn-nt">
																<h5 class="text-dark"> Activity : {{ is_array($trackingHistory) ? ($trackingHistory['order_status'] ?? ($trackingHistory['status'] ?? 'N/A')) : ($trackingHistory->order_status ?? 'N/A') }} </h5>
																<h6 class="lead-custm"> {{ $shippingCompany->name ?? 'Local Courier' }} </h6>
															</div>

															<div class="main-cotn-nt">
																<h6 class="lead-custm">
																	@php
																		$timestamp = is_array($trackingHistory) ? ($trackingHistory['created_at'] ?? null) : ($trackingHistory->created_at ?? null);
																	@endphp
																	@if($timestamp)
																		{{ date('M d, Y  h:i a', strtotime($timestamp)) }}
																	@endif
																</h6>
															</div>
														</div>
													@endif
												</div>
											 @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
