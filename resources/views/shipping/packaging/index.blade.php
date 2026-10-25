@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Packaging')
@section('header_title','Packaging')
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
                                <a href="{{route('shipment.packaging.add')}}"> <button class="btn-main-1"> Add Packaging </button> </a>
        					</div>
        				</div>
        				
                        <div class="main-data-teble-1 table-responsive">
                            <table id="packaging-datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Sr.no</th>
                                        <th>Package Name</th>
                                        <th>Package Dimensions	</th>
                                        <th>Package Type</th>
                                        <th>Sku Name</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
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
	var dataTable = $('#packaging-datatable').DataTable({
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
			"url": "{{ route('shipping.packaging.ajax') }}",
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
		{ "data": "name" }, 
		{ "data": "package_dimension" }, 
		{ "data": "package_type" }, 
		{ "data": "sku" }, 
		{ "data": "status" }, 
		{ "data": "created_at" },   
		{ "data": "action" }
		]
	});
</script>
@endpush