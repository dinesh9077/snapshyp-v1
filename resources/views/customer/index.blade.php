@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Recipeint/Customer')
@section('header_title','Recipeint/Customer')
@section('content') 
<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
            <div class="main-order-001">
                    <div class="main-create-order">
						<div class="main-disolay-felx" style="margin-top: 0 !important;">
							@if(config('permission.client.add') || auth()->user()->role === 'user')	
								<div class="main-btn0main-1 ml-auto">
									<a href="{{ route('customer.add') }}"> <button class="btn-main-1"><span class="mdi mdi-plus"></span> Create Client </button> </a>
								</div>
							@endif
        				</div>
        				
                        <div class="main-data-teble-1 table-responsive">
                            <table id="customer-datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th> SR.No </th> 
                                        <th> Client Name </th>
                                        <th> Mobile </th>
                                        <th> Email </th> 
                                        <th> Address </th>  
                                        <th> Created At </th> 
										<th> Action </th> 
        							</tr>
        						</thead> 
        					</table>
        				</div>
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
	
	var dataTable = $('#customer-datatable').DataTable({
		processing:true,
		"language": {
			'loadingRecords': '&nbsp;',
			'processing': 'Loading...'
		},
		serverSide:true,
		bLengthChange: true,
		searching: true,
		bFilter: true,
		bInfo: true,
		iDisplayLength: 25,
		order: [[5, 'desc'] ], // Default sort by Created At
		bAutoWidth: false,			 
		"ajax":{
			"url": "{{ route('customer.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}";
				d.search   = $('input[type="search"]').val();
				d.status   = $('select[name="status"]').val();  
			}
		},
		"columns": [
			{ "data": "id" },  
			{ "data": "customer_name" }, 
			{ "data": "mobile" }, 
			{ "data": "email" }, 
			{ "data": "address" },  
			{ "data": "created_at" },  
			{ "data": "action" },   
		]
	}); 
</script>
@endpush
