 @extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Staff Users')
@section('header_title',' Staff Users')
@section('content')
<div class="content-page">
    <div class="content">

        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
               <div class="main-order-001">
                    <div class="main-create-order">
                        <div class="main-disolay-felx" style="margin-top: 0 !important;">
							@if(config('permission.staff.add') || auth()->user()->isMasterAdmin() )
								<div class="main-btn0main-1 ml-auto">
									<button class="btn-main-1" onclick="addStaff(this, event)"><span class="mdi mdi-plus"></span> Create staff </button>
								</div>
							@endif
        				</div>

                        <div class="main-data-teble-1 table-responsive">
                            <table id="staff-datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th> SR.No </th>
                                        <th> Company Name</th>
                                        <th> User Name </th>
                                        <th> Mobile </th>
                                        <th> Email </th>
										<th> Role</th>
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
	var dataTable = $('#staff-datatable').DataTable({
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
			"url": "{{ route('staff.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}";
			}
		},
		"columns": [
			{ "data": "id" },
			{ "data": "company_name" },
			{ "data": "name" },
			{ "data": "mobile" },
			{ "data": "email" },
			{ "data": "role" },
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

	function addStaff(obj, event)
	{
		event.preventDefault();
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal();
			$.get("{{route('staff.create')}}")
				.done(function(res)
				{
					modalOpen = false;
					handleModalResponse(res, '#addStaffModal');
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

	function editStaff(obj, event)
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
					handleModalResponse(res, '#editStaffModal');
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

	function editPermission(obj, event)
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
					handleModalResponse(res, '#editPermissionModal');
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
