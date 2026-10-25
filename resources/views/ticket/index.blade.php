@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Ticket Request')
@section('header_title','Ticket')
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
								<a href="{{ route('ticket.add') }}"> <button class="btn-main-1"> <span class="mdi mdi-plus"></span> Create Ticket </button> </a>
							</div>
						</div>

						<div class="main-data-teble-1 table-responsive">
							<table id="ticket-datatable" class="" style="width:100%">
								<thead>
									<tr>
										<th> SR.No </th>
										<th> Ticket No</th>
										<th> AWB Number </th>
										<th> Name </th>
										<th> Mobile </th> 
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
	var dataTable = $('#ticket-datatable').DataTable({
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
		order: [
			[0, 'desc']
		],
		bAutoWidth: false,
		"ajax": {
			"url": "{{ route('ticket.list.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function(d) {
				d._token = "{{csrf_token()}}";
				d.status = '{{ $status }}';
			}
		},
		"columns": [{
				"data": "id"
			},
			{
				"data": "ticket_no"
			},
			{
				"data": "awb_number"
			},
			{
				"data": "contact_name"
			},
			{
				"data": "contact_phone"
			}, 
			{
				"data": "status"
			},
			{
				"data": "created_at"
			},
			{
				"data": "action"
			}
		]
	});

	$('#ticket-datatable').on('draw.dt', function() {
		$('[data-toggle="tooltip"]').tooltip({
			position: {
				my: "left bottom", // the "anchor point" in the tooltip element
				at: "left top", // the position of that anchor point relative to selected element
			}
		});
	});
</script>
@endpush