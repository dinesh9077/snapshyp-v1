@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Pickup Request')
@section('header_title', 'Pickup Request')
@section('content') 
<style>
	#pickupRequestForm input,
#pickupRequestForm select .select2-selection__rendered{
  height: 43px !important;
}
</style>
<div class="content-page">
    <div class="content"> 
        <div class="container-fluid">
            <div class="main-order-page-1">
				<div class="main-order-001">
					<div class="main-create-order">
						<div class="row row-re mb-2 searchAllForm mb-2 order-filter m-0 mb-3">
							<div class="col-xl-2 col-lg-3 col-sm-6">
								<div class="main-selet-11">
									<label for="date_type">Date Type</label>
									<select class="form-control" name="date_type" id="date_type">
										<option value="pickup_date">Pickup Date</option>
										<option value="created_at">Created Date</option>
									</select>
								</div>
							</div>
							<div class="col-xl-2 col-lg-3 col-sm-6">
								<div class="main-selet-11">
									<label for="from_date">From Date</label>
									<input type="text" class="form-control datepicker" name="from_date"
										id="from_date" placeholder="From Date"
										value="{{ request('from_date', '') }}">
								</div>
							</div>
							<div class="col-xl-2 col-lg-3 col-sm-6">
								<div class="main-selet-11">
									<label for="to_date">To Date</label>
									<input type="text" class="form-control datepicker" name="to_date"
										id="to_date" placeholder="To Date"
										value="{{ request('to_date', '') }}">
								</div>
							</div>  

							<div class="col-xl-2 col-lg-3 col-sm-6" style="align-self: flex-end;">
								<div class="main-selet-11">
									<button type="button" id="searchAllFilter" class="btn-main-1 search_data search-btn-remi">Search</button>
								</div>
							</div>
							@if(config('permission.pickup_request.add'))
								<div class="col-xl-4 col-12">
									<div class="main-selet-11 text-right">
										<div class="main-btn0main-1">
											<button class="btn-main-1 add-btn" onclick="createPickupRequest(event)">+ Create Pickup</button>
										</div> 
									</div>
								</div>
							@endif
						</div>  
						 
						
						<div class="main-data-teble-1 table-responsive">
							<table id="pickurequest-datatable" class="" style="width:100%">
								<thead>
									<tr>
										<th> # </th>
										<th> Shipping Company</th>
										<th> Request Id</th>
										<th> Warehouse Location</th>
										<th> Expected Package Count </th> 
										<th> Pickup Date And Time</th>
										<th> Shipper's Contact No </th> 
										<th> Status </th> 
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
<div class="modal fade bd-example-modal-lg" id="pickupRequestModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"> 
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Create New Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="pickupRequestForm" action="{{ route('pickup.request.create') }}" method="post">
                @csrf 
                <div class="modal-body">
                    <div class="form-group">
                        <label>Shipping Company</label>
                        <select name="shipping_company_id" class="form-control select2" required>
							<option value="">Select Shipping Company</option>
							@foreach($shippingCompanies as $shippingCompany)
								<option value="{{ $shippingCompany->id }}">{{ $shippingCompany->name }}</option>
							@endforeach
						</select>
                    </div>
                    <div class="form-group">
                        <label>Warehouse Location</label>
                        <select name="warehouse_id" class="form-control select2" required>
							<option value="">Select Warehouse Name</option>
							@foreach($warehouses as $warehouse)
								<option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }} ({{ $warehouse->address }})</option>
							@endforeach
						</select>
                    </div>
                    <div class="form-group">
                        <label>Expected Package</label>
                        <input type="text" name="expected_package_count" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" id="expected_package_count" class="form-control"  required>
                    </div>
                    <div class="form-group">
                        <label>Pickup Date</label>
                        <input type="text" name="pickup_date" id="pickup_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Pickup Time</label> 
						<select class="form-control select2" name="pickup_start_time"  id="pickup_start_time" required> 
							<option value="">Select Time</option>
							@foreach($timeSlots as $timeSlot)
								@php
									$time = explode(' - ', $timeSlot);
								@endphp 
								<option value="{{ $time[0] }}" data-pickup_end_time="{{ $time[1] }}">{{ $timeSlot }}</option>
							@endforeach
						</select>
                    </div> 
					<input type="hidden" name="pickup_end_time"  id="pickup_end_time" required>
                </div>
                <div class="modal-footer justify-content-right border-0">
                    <button type="button" class="btn btn-light btn-reset" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-apply">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('js') 
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> 

<script> 
	 
	function parseTimeTo24Hour(timeStr) {
		const [time, modifier] = timeStr.trim().split(' ');
		let [hours, minutes] = time.split(':').map(Number);

		if (modifier === 'PM' && hours < 12) hours += 12;
		if (modifier === 'AM' && hours === 12) hours = 0;

		return { hours, minutes };
	}

	$(function () {
		$('#pickup_date').datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: 0,
			onSelect: function (selectedDate) {
				const now = new Date();
				const selected = new Date(selectedDate + 'T00:00:00');

				$('#pickup_start_time option').each(function () {
					const slot = $(this).text(); // e.g. "09:00 AM - 12:00 PM"
					if (!slot || !slot.includes('-')) return;

					const endTimeStr = slot.split(' - ')[1];
					const { hours, minutes } = parseTimeTo24Hour(endTimeStr);

					const slotEndDateTime = new Date(selected);
					slotEndDateTime.setHours(hours, minutes, 0, 0);

					// Disable if current time is beyond the end of this slot AND selected date is today
					if (now.toDateString() === selected.toDateString() && now >= slotEndDateTime) {
						$(this).prop('disabled', true);
					} else {
						$(this).prop('disabled', false);
					}
				});
			}
		});
	});

	$('#pickup_start_time').on('change', function() {
		const pickup_end_time = $(this).find('option:selected').data('pickup_end_time');
		$('#pickup_end_time').val(pickup_end_time);
	});
  
	function createPickupRequest(event) {
		event.preventDefault();  
		$('#pickupRequestForm').trigger('reset'); 
		$('#pickupRequestForm select').val('').trigger('change'); 
		$('#pickupRequestModal').modal('show');
	}
 
	var dataTable = $('#pickurequest-datatable').DataTable({
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
			"url": "{{ route('pickup.request.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{ csrf_token() }}";
				d.search   = $('input[type="search"]').val();  
				d.from_date = $('.searchAllForm  #from_date').val();
				d.to_date = $('.searchAllForm  #to_date').val();  
				d.date_type = $('.searchAllForm  #date_type').val();  
			}
		},
		"columns": [
			{ "data": "id" }, 
			{ "data": "shipping_company_id" }, 
			{ "data": "pickup_id" }, 
			{ "data": "warehouse_id" }, 
			{ "data": "expected_package_count" },
			{ "data": "pickup_date" }, 
			{ "data": "shipper_contact" }, 
			{ "data": "status" }, 
			{ "data": "created_at" },   
			{ "data": "action", visible:false }
		]
	}); 
	
	$('#searchAllFilter').click(function (e) { 
		dataTable.draw();
	});

	let debounceTimer;
    $('#pickurequest-datatable_filter input').off().on('keyup', function () 
    {  
        clearTimeout(debounceTimer); 
        debounceTimer = setTimeout(() => {
            dataTable.search(this.value).draw();
        }, 500); 
    });

	function cancelPickupRequest(obj, event)
	{
		event.preventDefault();
		Swal.fire({
			title:"Are you sure you want to cancel this pickup request?",
			text:"You can't undo this action.",
			type:"warning",
			showCancelButton:!0,
			confirmButtonColor:"#31ce77",
			cancelButtonColor:"#f34943",
			confirmButtonText:"Yes, Cancel!"
			}).then(function (t) {
			if(t.value)
			{
				location.href = obj;
			}
		})
	}
</script>
@endpush