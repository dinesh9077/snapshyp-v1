@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Warehouse')
@section('header_title', 'Warehouse')
@section('content') 
<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
				<div class="main-order-001">
					<div class="main-create-order">
						<div class="main-disolay-felx" style="margin-top: 0 !important;">
							@if(config('permission.warehouse.add') || auth()->user()->role === 'user')	
								<div class="main-btn0main-1 ml-auto">
									<a href="{{ route('warehouse.add') }}"> <button class="btn-main-1">
<span class="mdi mdi-plus"></span> Create Warehouse </button> </a>
								</div>
							@endif
						</div>
						
						<div class="main-data-teble-1 table-responsive">
							<table id="warehouse-datatable" class="" style="width:100%">
								<thead>
									<tr>
										<th> # </th>
										<th> Warehouse </th>
										<th> Company Name</th>
										<th> Contact Person</th>
										<th> Mobile</th>
										<th> address </th>  
										<th> status </th> 
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
	
	var dataTable = $('#warehouse-datatable').DataTable({
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
		order: [[0, 'desc']],
		bAutoWidth: false,			 
		"ajax": {
			"url": "{{ route('warehouse.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{ csrf_token() }}";
				d.search   = $('input[type="search"]').val();   
			}
		},
		"columns": [
		{ "data": "id" }, 
		{ "data": "warehouse_name" }, 
		{ "data": "company_name" }, 
		{ "data": "contact_name" }, 
		{ "data": "contact_number" }, 
		{ "data": "address" },
		{ "data": "warehouse_status" }, 
		{ "data": "created_at" },   
		{ "data": "action" }
		]
	}); 
</script>
@endpush
