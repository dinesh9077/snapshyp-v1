@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Order Report')
@section('header_title','Order Report')
@section('content') 
<style>
	.tooltip .tooltiptext 
	{ 
	text-align: left !important; 
	padding: 5px 0 5px 5px !important;
	}	
	td p {
	margin-bottom: 0;
    }
	.page-heading-main {
	display: flex;
	align-items: center;
	justify-content: end;
	margin-bottom: 20px;
	gap: 15px;
	flex-wrap: wrap;
}

.left-head-deta {
	display: flex;
	align-items: end;
	gap: 15px;
}
.custom-entry {
	display: flex;
	align-items: center;
	gap: 8px;
}
.right-head-deta {
	display: flex;
	align-items: center;
	gap: 15px;
	flex-wrap: wrap;
}

.table-custom-serch .input-main {
	min-width:   450px;
}
.table-custom-serch .input-main { 
	border: 1px solid #dcdcdc;
	border-radius: 10px;
	padding: 7px;
	margin-left: 3px;
	font-weight: 400;
	font-size: 14px;
	color: #000;
	background-color: white;
	padding: 9px 20px;
}
.custom-entry p {
	margin: 0;
	font-size: 14px;
	color: #0A1629;
	font-weight: 500;
}
.tooltip-inner {
    max-width: 500px; /* wide tooltip */
    background-color: #000; /* black background */
    color: #fff; /* white text */
    font-size: 12px;
    padding: 10px;
}

.tooltip-table {
    border-collapse: collapse;
    width: 100%;
}

.tooltip-table th,
.tooltip-table td {
    border: 1px solid #fff;
    padding: 4px 6px;
    text-align: left;
}

.tooltip-table th {
    background-color: #333;
}

 #page_length{
	padding: 4px 8px;
	border-radius: var(--default-radius);
    background-color: transparent;
    color: #000;
    appearance: none;
    text-align: center;
    font-weight: 400;
    margin: 0 6px;
    border: 1px solid #BCBCBC;
} 
@media (max-width: 576px) {
	.table-custom-serch .input-main{
		width: 100%;
		min-width: 100%;
	}
}
/* .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 44px !important;
	background-color: #f3f3f3 !important;
	border: none !important;
} */
</style>

<div class="content-page">
	<div class="content">
		<!-- Start Content-->
		<div class="container-fluid">
			<div class="main-order-page-1">
			    <div class="main-order-001">
    				<div class="main-filter-weight">
    					<div class="row row-re reportOrderForm order-filter m-0 mb-3">  
    						@if(Auth::user()->role != "user")                  
								<div class="col-lg-2 col-sm-6 pl-0 pr-0 pr-sm-2">
									<div class="main-selet-11">
										<label for="user_id">User</label>
										<select name="user" class="select2" id="user_id"  >
											<option value="" >All Users</option> 
											@foreach ($users as $user)
											<option value="{{ $user->id }}">{{ $user->name }}</option>
											@endforeach
										</select>
									</div>
								</div>
    						@endif
							<div class="col-lg-2 col-sm-6 pl-0 pr-0 pr-sm-2">
								<div class="main-selet-11">
									<label for="courier_name">Courier Name</label>
									<select name="courier_name" class="select2" id="courier_name"  >
										<option value="" >Select Courier Name</option> 
										@foreach ($couriers as $courier)
											<option value="{{ $courier }}">{{ $courier }}</option>
										@endforeach
									</select>
								</div>
							</div>
    						<div class="col-lg-2 col-sm-6 pl-0 pr-0 pr-sm-2">
    							<div class="main-selet-11">
									<label for="fromdate">From Date</label>
    								<input type="text" class="form-control new-height-fcs-rmi datepicker" name="fromdate" value="{{ $defaultFrom }}" id="fromdate" placeholder="From Date">
								</div>
							</div>
    						<div class="col-lg-2 col-sm-6 pl-0 pr-0 pr-sm-2">
    							<div class="main-selet-11">
									<label for="todate">To Date</label>
    								<input type="text" class="form-control new-height-fcs-rmi datepicker" name="todate" value="{{ $defaultTo }}" id="todate" placeholder="To Date">
								</div>
							</div>
    						<div class="col-lg-2 col-sm-6 pl-0 pr-0 pr-sm-2" style="align-self: flex-end;">
    							<div class="main-selet-11">
    								<button class="btn-main-1 search_data search-btn-remi">Search</button>
								</div>
							</div>
						</div>
					</div>
    				<div class="ordr-main-001">
        				<ul id="tab">
        					<li class="active">
        						<div class="main-calander-11"> 
										<div class="page-heading-main justify-content-between align-items-end  mb-0">
											<div class="left-head-deta">
												<div class="custom-entry">
													<p>Show</p>
													<select id="page_length">
														<option value="10">10</option>
														<option value="25" selected>25</option>
														<option value="50">50</option>
														<option value="100">100</option>
														<option value="500">500</option>
														<option value="1000">1000</option>
														<option value="2000">2000</option>
														<option value="200000000">All</option>
													</select>
													<p>entries</p>
												</div>
											</div>
											<div class="right-head-deta">
												<div class="table-custom-serch">
													<input class="input-main" type="search" id="search_table"  placeholder="Search">
												</div> 
												<a href="javascript:;" onclick="redirectUrl()" class="btn btn-warning"> XLXS</a> 

											</div>
										</div> 
        							<div class="main-data-teble-1 table-responsive mt-3">
									
        								<table id="orderreport_datatable" class="" style="width:100%">
        									<thead>
        										<tr>
        											<th>Sr.No</th>
        											<th>Seller Details</th>
        											<th>Order Details</th>
        											<th>Customer Details</th>
        											<th style="min-width:250px;">Address</th>
        											<th>City</th>
        											<th>state</th>
        											<th>Country</th>
        											<th>Pincode</th>
        											<th>Package Details</th>
        											<th>Payment</th>
        											<th>Pickup Address</th>
        											<th>Status</th> 
        											<th>Created At</th> 
												</tr>
											</thead> 
										</table>
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="hotelInfoModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-scrollable modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="infoModalLabel">Product Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div> 
			<div class="modal-body" id="infoModalBody">
				<!-- HTML hotel description appears here -->
			</div>
		</div>
	</div>
</div>

@endsection
@push('js')  
<!-- DataTables Buttons extension -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> 
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script> 

<script> 
	var dataTable = $('#orderreport_datatable').DataTable({
    	processing: true,
    	serverSide: true,
    	searching: false,
    	bLengthChange: false, 
    	lengthMenu: [ [10, 25, 50, 100, 200, 500, 1000000], [10, 25, 50, 100, 200, 500, 'All'] ], // ðŸ”¥ options shown in dropdown
    	bFilter: true,
    	bInfo: true,
    	iDisplayLength: 25,
    	order: [[0, 'desc']],
    	bAutoWidth: true,
    	  
    	ajax: {
    		url: "{{ route('report.order.ajax') }}",
    		type: "POST",
    		dataType: "json",
    		data: function (d) {
    			d._token = "{{ csrf_token() }}";
    			d.search = $('input[type="search"]').val();
    			d.user_id = $('.reportOrderForm #user_id').val();
    			d.fromdate = $('.reportOrderForm #fromdate').val();
    			d.todate = $('.reportOrderForm #todate').val();
    			d.courier_name = $('.reportOrderForm #courier_name').val();
    		}
    	},
    
    	columns: [
    		{ data: "id" },
    		{ data: "seller_details" },
    		{ data: "order_details" },
    		{ data: "customer_details" },
    		{ data: "customer_address" },
    		{ data: "customer_city" },
    		{ data: "customer_state" },
    		{ data: "customer_country" },
    		{ data: "customer_pincode" },
    		{ data: "package_details" },
    		{ data: "total_amount" },
    		{ data: "pickup_address" },
    		{ data: "status_courier" },
    		{ data: "created_at" },
    	],
    
    	drawCallback: function () { 
			$('[data-toggle="tooltip"]').tooltip({
				html: true
			});
    	}
    });
    
	$('#page_length').change(function(){
		dataTable.page.len($(this).val()).draw();
	})
	
	var debounceTimer; 
	$('#search_table').keyup(function() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function() {
			dataTable.draw(); 
		}, 400); // Adjust the debounce delay (in milliseconds) as per your preference
	}); 
	
	$('.search_data').click(function() { 
		dataTable.draw();  
	});
	
	
	function redirectUrl() {
		let user_id = $('.reportOrderForm #user_id').val();
		let fromdate = $('.reportOrderForm #fromdate').val();
		let todate = $('.reportOrderForm #todate').val();
		let courier_name = $('.reportOrderForm #courier_name').val();
		let search = $('#search_table').val();

		let url = "{{ url('report/export-orders') }}";
		url += `?user_id=${encodeURIComponent(user_id)}&fromdate=${encodeURIComponent(fromdate)}&todate=${encodeURIComponent(todate)}&courier_name=${encodeURIComponent(courier_name)}&search=${encodeURIComponent(search)}`;

		window.location.href = url;
	}
	
	$(document).on('click', '.show-details-btn', function () {
		var items = $(this).data('order'); // JSON string automatically converted by jQuery
		if (typeof items === 'string') {
			items = JSON.parse(items);
		}

		var totalAmount = 0;
		var html = "<table class='table table-bordered table-sm'><thead><tr><th>Category</th><th>Name</th><th>SKU</th><th>HSN</th><th>Amount</th><th>Qty</th></tr></thead><tbody>";

		items.forEach(function(item) {
			html += "<tr>" +
						"<td>" + item.product_category + "</td>" +
						"<td>" + item.product_name + "</td>" +
						"<td>" + item.sku_number + "</td>" +
						"<td>" + item.hsn_number + "</td>" +
						"<td>" + item.amount + "</td>" +
						"<td>" + item.quantity + "</td>" +
					"</tr>";
			totalAmount += parseFloat(item.amount * item.quantity) || 0;
		});

		html += "</tbody>";
		html += "<tfoot><tr><th colspan='4'>Total</th><th colspan='2'>" + totalAmount.toFixed(2) + "</th></tr></tfoot>";
		html += "</table>";

		$('#infoModalLabel').html("Product Details");
		$('#infoModalBody').html(html);
		$('#infoModal').modal('show');
	});


</script>
@endpush
