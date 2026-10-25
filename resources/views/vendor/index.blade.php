@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Vendor')
@section('header_title','Vendor')
@section('content') 
<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
            <div class="main-order-001">
            <div class="main-create-order">
                <div class="main-disolay-felx" style="margin-top: 0 !important;">
                    <div class="main-btn0main-1">
                        <a href="{{route('vendor.add')}}"> <button class="btn-main-1"> Create Vendor </button> </a>
					</div>
				</div>
				
                <div class="main-data-teble-1 table-responsive">
                    <table id="vendor-datatable" class="" style="width:100%">
                        <thead>
                            <tr>
                                <th> SR.No </th>
                                <th> Company Name</th>
                                <th> Vendor Name </th>
                                <th> Mobile </th>
                                <th> Email </th>
                                <th> status </th>
                                <th> Wallet </th>
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
	
	var dataTable = $('#vendor-datatable').DataTable({
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
		order: [[0, 'desc'] ],
		bAutoWidth: false,			 
		"ajax":{
			"url": "{{ route('vendor.ajax') }}",
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
		{ "data": "company_name" }, 
		{ "data": "vendor_name" }, 
		{ "data": "mobile" }, 
		{ "data": "email" }, 
		{ "data": "status" }, 
		{ "data": "wallet" },
		{ "data": "created_at" },   
		{ "data": "action" }
		]
	}); 
</script>
@endpush