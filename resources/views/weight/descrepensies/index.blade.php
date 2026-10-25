@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Weight Descrepencies')
@section('header_title','Weight Descrepencies')
@section('content')
<style>
	.modal-body h5 {
		width: 100%;
	}

	.model-width-1 {
		width: 50% !important;
		max-width: 100%;
	}

	.pagination {
		display: -webkit-box;
		display: -ms-flexbox;
		padding-left: 0;
		list-style: none;
		border-radius: .25rem;
		margin-top: 20px;
		float: right;
	}
</style>
<div class="content-page">
	<div class="content">
		<!-- Start Content-->
		<div class="container-fluid">
			<div class="main-find-weght"> 
				<div class="main-order-page-1">
					<div class="main-order-001">
						<div class="main-filter-weight">
							<form method="get" action="">
								<div class="row row-re order-filter m-0 mb-2">
									<div class="col-lg-2 col-sm-6">
										<div class="main-selet-11">
											<label for="date">Date</label>
											<input type="text" class="form-control new-height-fcs-rmi datepicker" name="date" value="<?= request('date') ?>" id="date" placeholder="Date">
										</div>
									</div>
									<div class="col-lg-2 col-sm-6">
										<div class="main-selet-11">
											<label for="search">Search</label>
											<input type="text" class="form-control new-height-fcs-rmi" name="search" value="<?= request('search') ?>" id="search" placeholder="Search with AWB Number or Order ID">
										</div>
									</div>
									<div class="col-lg-2 col-sm-6">
										<div class="">
											<label for="weight_status">Weight Status</label>
											<select name="weight_status" class="form-control">
												<option value="">All Statuses</option>
												<?php 
													$statuses = ['New', 'Accepted', 'Auto Accepted', 'Remark'];
													foreach ($statuses as $status) {
														$selected = request('weight_status') == $status ? 'selected' : '';
														echo "<option value='$status' $selected>$status</option>";
													}
												?>
											</select>
										</div>
									</div>
									<div class="col-lg-2 col-sm-6" style="align-self: flex-end;">
										<div class="main-selet-11">
											<button class="btn-main-1 search_data search-btn-remi" type="submit">Search</button>
										</div>
									</div>
								</div>
							</form>
						</div> 
						<div class="amin-slip-1">
							<div class="main-tab-11">
								<div class="main-data-teble-1 table-responsive">
									<table id="example" class="dataTable no-footer" style="width:100%">
										<thead>
											<tr>
												<th> Status Updated On </th> 
												<th> User Name </th>
												<th> Shipping Details </th>
												<th> Applied Weight </th>
												<th> Charged Weight </th>
												<th> Excess Weight & Charges </th>
												<th> Courier Images </th>
												<th> Status </th>
												<th> Actions </th>
											</tr>
										</thead>
										<tbody>
											@forelse($orders as $order)
												<tr>
													<td>{{ date('d M Y h:i:s', strtotime($order->weight_update_date ?? $order->created_at)) }}</td>
													<td>{{ $order->user->name ?? 'N/A' }}</td> 
													<td>
														{{ $order->courier_name }}<br>
														<b>AWB:</b> <a href="{{ url('order/details', $order->id) }}">{{ $order->awb_number }}</a> <br>
														<b>Status:</b> {{ $order->status_courier }} <br>
													</td>
													<td>
														<b>{{ $order->applicable_weight }} kg</b><br>
														Dead Weight: {{ $order->weight }} Kg <br>
														Volumetric Weight: {{ round(($order->length * $order->width * $order->height) / 5000, 2) }} Kg <br>
														({{ $order->length }}x{{ $order->width }}x{{ $order->height }} cm)
													</td>
													<td>
														@if($order->excessWeight)
															<b>{{ $order->excessWeight->chargeable_weight }} kg</b> 
														@endif
													</td>
													<td>
														@if($order->excessWeight)
															<span>Excess Weight: <p style="color: red;margin: 0;">{{ $order->excessWeight->excess_weight }} Kg</p></span>  
															<span>Excess Charges: <p style="color: red;margin: 0;">{{ $order->excessWeight->excess_charge }}</p></span>
														@endif
													</td>
													<td>
														@if($order->excessWeight && !empty($order->excessWeight->product_images))
															<div class="row">
																@foreach($order->excessWeight->product_images as $image)
																	<div class="col-md-4">
																		<a href="{{ url('storage/'.$image) }}" target="_blank">
																			<img src="{{ url('storage/'.$image) }}" style="height:50px">
																		</a>
																	</div>
																@endforeach
															</div>
														@endif
													</td>
													<td>
														@if(!empty($order->weight_status))
															<button class="main-gray-b">{{ $order->weight_status }}</button>
														@endif
													</td>
													<td>
														@if(!in_array($order->weight_status, ["Accepted", "Auto Accepted"]))
															<select name="action" id="take_action" class="form-control" onchange="takeAction(this,{{$order->id}})">
																<option value="">Take Action</option>
																<option value="Accepted">Accepted</option>
																<option value="Remark">Remark</option>
															</select>
														@endif
														@if(in_array($order->weight_status, ["Accepted", "Auto Accepted"]))
															<a class="btn-main-1" href="javascript:;" data-order_id="{{ $order->id }}" onclick="viewHistory(this, event)"> View Details </a>
														@endif
													</td>
												</tr> 
											@empty
												<tr>
													<td colspan="9" class="text-center text-muted">No orders found</td>
												</tr>
											@endforelse 
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 d-flex align-items-center">
						<span>
							Showing <b>{{ $orders->firstItem() ?? 0 }}</b> to 
							<b>{{ $orders->lastItem() ?? 0 }}</b> of 
							<b>{{ $orders->total() }}</b> records
						</span>
					</div>

					<div class="col-md-6">
						<div class="d-flex justify-content-end">
							{!! $orders->appends(request()->query())->links() !!} 
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade view_history" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg model-width-1">
		<div class="modal-content">
			<div class="modal-header head-00re pb-0" style="border: none;">
				<h5 class="modal-title" id="header_msg"> </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<div class="modal-body" id="display_view">

			</div>
		</div>
	</div>
</div>
<div class="modal fade view_image" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog ">
		<div class="modal-content">
			<div class="modal-header head-00re pb-0" style="border: none;">
				<h5 class="modal-title" id="header_msg_image"> </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<div class="modal-body" id="display_view_image">

			</div>
		</div>
	</div>
</div>
@endsection
@push('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
	function viewHistory(obj, event) {
		event.preventDefault();
		var order_id = $(obj).attr('data-order_id');
		$.get("{{url('weight/descripencies/history')}}/" + order_id, function(res) {
			$('#header_msg').html(res.header_msg);
			$('#display_view').html(res.view);
			$('.view_history').modal('show');
		}, 'Json');
	}

	function takeAction(obj, order_id) {
		var acton = $(obj).val();
		if(!acton)
		{
			return false;
		}
		if (acton == "Accepted") {
			Swal.fire({
				title: "Are you sure you want to " + acton + "?",
				text: "You won't be able to revert this!",
				type: "warning",
				showCancelButton: !0,
				confirmButtonColor: "#31ce77",
				cancelButtonColor: "#f34943",
				confirmButtonText: "Yes, " + acton + " it!"
			}).then(function(t) {
				if (t.value) {
					$.get("{{url('weight/descripencies/accepted')}}/" + order_id+"?by=seller", function(res) {
						toastrMsg(res.status, res.msg);
						location.reload();
					}, 'Json');
				} else {
					$(obj).val('');
				}
			})
		} else {
			window.location.href = "{{url('weight/descripencies/remark')}}/" + order_id;
		}
	}
  
	function viewImage(obj, event) 
	{
		event.preventDefault();
		var id = $(obj).attr('data-id');
		$.get("{{url('weight/descripencies/view_image')}}/" + id, function(res) {
			$('#header_msg_image').html(res.header_msg);
			$('#display_view_image').html(res.view);
			$('.view_history').modal('hide');
			$('.view_image').modal('show');
		}, 'Json');
	}
</script>
@endpush