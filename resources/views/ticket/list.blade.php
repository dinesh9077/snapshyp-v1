@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Ticket')
@section('header_title','Ticket')
@section('content') 
<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
			<div class="main-order-page-1">
                <div class="main-order-001">
            <div class="main-create-order">
                <!-- <div class="main-disolay-felx">
                    <div class="main-btn0main-1">  
					</div>
				</div> -->
				
                <div class="main-data-teble-1 table-responsive">
                    <table id="ticket-datatable" class="ticket-table" class="" style="width:100%">
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
			"url": "{{ route('ticket.admin.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}";
				d.status   = '{{$status}}';  
				d.ticket_id   = '{{$ticket_id}}';  
			}
		},
		"columns": [
		{ "data": "id" },  
		{ "data": "ticket_no" }, 
		{ "data": "awb_number" }, 
		{ "data": "contact_name" }, 
		{ "data": "contact_phone" },   
		{ "data": "status" }, 
		{ "data": "created_at" },   
		{ "data": "action" }
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
	  
	 function closeTicket(obj,event)
	 {
		event.preventDefault();
		Swal.fire({
			title:"Are you sure you want to close ticket?",
			text:"You won't be able to revert this!",
			type:"warning",
			showCancelButton:!0,
			confirmButtonColor:"#31ce77",
			cancelButtonColor:"#f34943",
			confirmButtonText:"Yes, Close it!"
			}).then(function (t) {
			if(t.value)
			{
				location.href = obj;
			}
		}) 
	 }
</script>
@endpush