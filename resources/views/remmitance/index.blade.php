@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Remmitance')
@section('header_title','Remmitance')
@section('content')
<style>
    .tooltip .tooltiptext {
	text-align: left !important;
	padding: 5px 0 5px 5px !important;
    }
	
    td p {
	margin-bottom: 0;
    }
</style>
<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="main-order-001">
                    <div class="main-filter-weight"> 
                        <div class="inner-page-heading">
                             <div class="row row-re order-filter w-100 m-0 mb-3 searchAllForm">
                                @if($authUser->role != "user")
                                    <div class="col-lg col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                        <div class="main-selet-11">
                                            <label for="user_id">User</label>
                                        <select name="user_id" id="user_id" class="form-control select2">
                                                <option value="">Select User</option> 
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" 
                                                        {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                 <div class="col-lg col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                    <div class="main-selet-11">
                                        <label for="shipping_company_id">Shipping Company</label>
                                       <select name="shipping_company_id" class="form-control select2" id="shipping_company_id">
                                            <option value=""> All Shipping Company </option>
                                            @foreach($shippingCompanies as $shippingCompany)
                                            <option value="{{ $shippingCompany->id }}"> {{ $shippingCompany->name }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                    <div class="main-selet-11">
                                        <label for="fromdate">From Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi datepicker" name="fromdate" value="{{ request('from_date', '') }}" id="fromdate" placeholder="From Date">
                                    </div>
                                </div>
                                <div class="col-lg col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                    <div class="main-selet-11">
                                        <label for="todate">To Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi datepicker" name="todate" value="{{ request('to_date', '') }}" id="todate" placeholder="To Date">
                                    </div>
                                </div> 
                                <div class="col-lg col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2" style="align-self: flex-end;">
                                    <div class="main-selet-11">
                                        <button type="button" id="searchAllFilter" class="btn-main-1 search_data search-btn-remi">Search</button>
                                    </div>
                                </div>
                            </div> 
                        </div> 
					</div>
                    <div class="ordr-main-001">
                        <ul id="tab">
                            <li class="active">
                                <div class="main-calander-11">
                                    <div class="main-data-teble-1 table-responsive">
                                        <table id="remmitance_datatable" class="" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th> # </th>
                                                    <th> Order Id </th>
                                                    <th> Seller Details </th>
                                                    <th> Delivery Date </th>
                                                    <th> Amount </th>
                                                    <th> Shipping Details </th>
                                                    <th> Status </th>  
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
@endsection
@push('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script> 
    var dataTable = $('#remmitance_datatable').DataTable({
        processing: true,
        "language": {
            'loadingRecords': '&nbsp;',
            'processing': 'Loading...'
		},
        serverSide: true,
        bLengthChange: true,
        searching: true,
        bFilter: true,
        bInfo: true,
        iDisplayLength: 25,
        order: false,
        bAutoWidth: false,
        "ajax": {
            "url": "{{ route('remmitance.ajax') }}",
            "dataType": "json",
            "type": "POST",
            "data": function(d) {
                d._token = "{{csrf_token()}}";
                d.search = $('input[type="search"]').val(); 
                d.from_date = $('.searchAllForm  #from_date').val();
				d.to_date = $('.searchAllForm  #to_date').val(); 
				d.shipping_company_id = $('.searchAllForm  #shipping_company_id').val();   
				d.user_id = $('.searchAllForm  #user_id').val();   
			}
		},
        "columns": [{
			"data": "id"
		},
		{
			"data": "order_id"
		},
		{
			"data": "seller_details"
		},
		{
			"data": "delivery_date"
		},
		{
			"data": "amount"
		},
		{
			"data": "shipment_details"
		},
		{
			"data": "status_courier"
		}, 
        ],
        "drawCallback": function(settings) {
            $('#totAmt').text(settings.json.totAmt);
		}
	});
	
    $('#searchAllFilter').click(function (e) { 
		dataTable.draw();
	});

	let debounceTimer;
    $('#remmitance_datatable_filter input').off().on('keyup', function () 
    {  
        clearTimeout(debounceTimer); 
        debounceTimer = setTimeout(() => {
            dataTable.search(this.value).draw();
        }, 500); 
    }); 
	  
</script>
@endpush