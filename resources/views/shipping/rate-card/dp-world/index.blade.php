@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Dp World Rate Card')
@section('header_title', 'Dp World Rate Card')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
<style>
	 .rate-table {
      border-collapse: collapse;
      width: 100%;
      min-width: 900px;
      text-align: center;
    }
    .rate-table th, .rate-table td {
      border: 1px solid #999;
      padding: 5px;
      font-size: 13px;
    }
    .rate-table th {
      background-color: #ccc;
    }
    .rate-table input[type="number"] {
      width: 60px;
      text-align: center;
      border: none;
      background: #f8f8f8;
      font-size: 13px;
    }
    .rate-table input[type="number"]:focus {
      outline: none;
      background: #e9f5ff;
      border: 1px solid #007bff;
    }
    .table-wrapper {
      overflow-x: auto;
    }
	.table th, .table td {
    min-width: 120px;
}
</style>
<div class="content-page">
	<div class="content">
		<!-- Start Content-->
		<div class="container-fluid">
			<div class="main-order-page-1">
				<div class="main-order-001"> 
					<div class="inner-page-heading d-flex justify-content-end">
						<div class="order-111-add">
							<a href="{{ route('rate.dp-world.cluster') }}"> <button class="btn-main-1 add-btn"> + Add Cluster </button> </a>
						</div>
					</div>
					<div class="ordr-main-001"> 
						<form action="{{ route('rate.dp-world.store') }}" method="POST">
							@csrf
							<div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
								<table class="table table-bordered text-center align-middle mb-3" style="min-width: 900px;">
									<thead class="table-light">
										<tr>
											<th style="min-width: 150px;">From / To</th>
											@foreach($zones as $zone)
												<th style="min-width: 150px;">{{ $zone->code_name }}</th>
											@endforeach
										</tr>
									</thead>
									<tbody>
										@foreach($zones as $from)
											<tr>
												<th style="min-width: 150px;">{{ $from->code_name }}</th>
												@foreach($zones as $to)
													@php
														$rate = $matrix[$from->id]->firstWhere('to_zone_id', $to->id)->price ?? '';
													@endphp
													<td>
														<input type="number" step="0.01"
															   name="rates[{{ $from->id }}][{{ $to->id }}]"
															   value="{{ $rate }}"
															   class="form-control text-center"
															   style="min-width: 120px;">
													</td>
												@endforeach
											</tr>
										@endforeach 
									</tbody>
								</table>
							</div>
							<button class="btn btn-primary btn-sm">Save Rates</button>
						</form> 
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
	var dataTable = $('#ratecard_datatable').DataTable({
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
			"url": "{{ route('rate.card.group.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function(d) {
				d._token = "{{csrf_token()}}";
				d.search = $('input[type="search"]').val();
				d.weight = $('#weight').val();
				d.rate_card_id = $('#rate_card_id').val();
				d.shipping_company_id = $('#shipping_company_id').val();
			}
		},
		"columns": [
			{
				"data": "id"
			},
			{
				"data": "shipping_company_id"
			},
			{
				"data": "weight"
			},
			{
				"data": "heading"
			},			
			{ 
				"data": "rate" 
			},  
			{
				"data": "extra_rate"
			},
			{
				"data": "cod_charges"
			},
			{
				"data": "percentage"
			}
		]
	});
	
	$('#ratecard_datatable').on('draw.dt', function() {
		$('[data-toggle="tooltip"]').tooltip({
			position: {
				my: "left bottom", // the "anchor point" in the tooltip element
				at: "left top", // the position of that anchor point relative to selected element
			}
		});
	});

	$('.search_data').click(function() { 
		dataTable.draw();
	});
	
	$('#rate_card_id, #weight, #shipping_company_id').change(function() {
		dataTable.draw();
	});
</script>
@endpush
