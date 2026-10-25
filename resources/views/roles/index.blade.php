@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Role')
@section('header_title','Role')
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
</style>
<div class="content-page">
     <div class="content">
          <!-- Start Content-->
          <div class="container-fluid">
               <div class="main-order-001">   
                   <div class="main-disolay-felx" style="margin-top: 0 !important;">
				   @if(config('permission.roles.add') || auth()->user()->isMasterAdmin() || auth()->user()->role === 'franchise')
					   <div class="main-btn0main-1 ml-auto"> 
						   <button class="btn-main-1" onclick="addRoles(this, event)"><span class="mdi mdi-plus"></span> Create New Role</button>
					   </div>
				   @endif
                   </div>
						<div class="main-calander-11"> 
							<div class="main-data-teble-1 table-responsive">
								 <table id="roleDatatable" class="" style="width:100%">
									  <thead>
										   <tr>
												<th> Sr.No</th>
												<th> Name </th>
												<th> Status </th>
												<th> Created </th>
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
	var dataTable = $('#roleDatatable').DataTable({ 
		processing:true,
		"language": {
			'loadingRecords': '&nbsp;',
			'processing': 'Loading...'
		},
		serverSide:true,
		bLengthChange: true,
		searching: true,
		bFilter: true,
		responsive:false,
		bInfo: true,
		iDisplayLength: 10,
		order: [[0, 'desc'] ],
		bAutoWidth: false,			 
		"ajax":{
			"url": "{{ route('roles.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}"; 
			}
		},
		"columns": [
		{ "data": "id" },    
		{ "data": "name" },   
		{ "data": "status" },  
		{ "data": "created_at" }, 
		{ "data": "action" }
		]
	}); 
	
	function handleModalResponse(res, modalId) {
		if (res && res.view) {
			$('body').find('#modal-view-render').html(res.view);
			$(modalId).modal('show');
			return;
		}
		if (typeof res === 'string') {
			const html = res.toLowerCase();
			if (html.includes('<html') || html.includes('<!doctype') || html.includes('login')) {
				window.location.reload();
				return;
			}
		}
		const msg = 'Unable to open. Please refresh and try again.';
		if (typeof toastrMsg === 'function') toastrMsg('error', msg);
		else alert(msg);
	}

	function addRoles(obj, event)
	{
		event.preventDefault();
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal(); 
			$.get("{{ route('roles.create') }}")
				.done(function(res)
				{ 
					modalOpen = false;
					handleModalResponse(res, '#addRoleModal');
				})
				.fail(function(xhr)
				{
					modalOpen = false;
					let msg = 'Unable to open. Please refresh and try again.';
					if (xhr && xhr.status == 403) msg = 'Permission denied.';
					if (xhr && xhr.status == 419) msg = 'Session expired. Please login again.';
					if (typeof toastrMsg === 'function') toastrMsg('error', msg);
					else alert(msg);
				});
		} 
	}
	
	function editRoles(obj, event)
	{
		event.preventDefault();
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal(); 
			$.get(obj)
				.done(function(res)
				{ 
					modalOpen = false;
					handleModalResponse(res, '#editRoleModal');
				})
				.fail(function(xhr)
				{
					modalOpen = false;
					let msg = 'Unable to open. Please refresh and try again.';
					if (xhr && xhr.status == 403) msg = 'Permission denied.';
					if (xhr && xhr.status == 419) msg = 'Session expired. Please login again.';
					if (typeof toastrMsg === 'function') toastrMsg('error', msg);
					else alert(msg);
				});
		} 
	} 
</script>
@endpush				
