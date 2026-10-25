@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Rate Card')
@section('header_title', 'Rate Card')
@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
    <style>
        .tooltip .tooltiptext {
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
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="inner-page-heading d-flex justify-content-end">
                            <div class="order-111-add">
                                <a href="{{ route('rate.dp-world.cluster-create') }}"> <button class="btn-main-1 add-btn">Add Cluster </button> </a>
                            </div>
                        </div>
						@if(session('success'))
							<div class="alert alert-success">{{ session('success') }}</div>
						@endif
                        <div class="ordr-main-001">
                            <div class="main-calander-11">
                                <div class="main-data-teble-1 table-responsive">
                                    <table class="table table-bordered">
										<thead>
											<tr>
												<th>Sr.No</th>
												<th>Code</th>
												<th>Zone</th>
												<th>States</th>
												<th>Cities</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody id="sortable-body">
											@foreach($zones as $key => $zone)
												<tr data-id="{{ $zone->id }}">
													<td>{{ ($key + 1) }}</td>
													<td>{{ $zone->code_name }}</td>
													<td>{{ $zone->zone_name }}</td>
													<td>{{ $zone->states ? implode(', ', $zone->states ?? []) : 'N/A' }}</td>
													<td>{{ $zone->cities ? implode(', ', $zone->cities ?? []) : 'N/A' }}</td>
													<td>
														<a href="{{ route('rate.dp-world.cluster-edit', $zone->id) }}" class="btn btn-sm btn-info">Edit</a>
														<form action="{{ route('rate.dp-world.cluster-delete', $zone->id) }}" method="POST" class="d-inline">
															@csrf @method('POST')
															<button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
														</form>
													</td>
												</tr>
											@endforeach
										</tbody>
									</table>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
@endsection 
@push('js')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
/* $(function() {
    $("#sortable-body").sortable({
        update: function(event, ui) {
            var order = [];
            $("#sortable-body tr").each(function() {
                order.push($(this).data("id"));
            });

            $.ajax({
                url: "{{ route('rate.dp-world.cluster-sort') }}",
                type: "POST",
                data: {
                    order: order,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('Sort order updated');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Something went wrong while updating sort order.');
                }
            });
        }
    });
}); */
</script>
@endpush