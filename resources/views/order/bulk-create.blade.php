@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Create Bulk Order')
@section('header_title', 'Create Bulk Order')
@section('content')
	<style>
		button.d-002 {
			padding: 6px 15px;
			border-radius: 4px 4px 4px 4px !important;
			font-size: 20px;
			font-weight: 700;
		}


		@media(max-width: 1600px) {


			.select2-container--default .select2-selection--single .select2-selection__arrow {
				height: 38px !important;
			}


		}

		#select2-customer_address_id-container {
			max-width: 76.5ch;
			/* Limit text to 100 characters */
		}
	</style>
	<style>
		.bulk-sample-btn.active-sample {
			background: #3984FB !important;
			color: #fff !important;
			border-color: #3984FB !important;
		}
	</style>

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <form id="orderForm" method="post" action="{{ route('order.bulk-store') }}" enctype="multipart/form-data">
                <div class="main-create-order">   
					<div class="main-rowx-1">
                        <div class="main-order-001">
							<div class="row">
								<div class="col-lg-12"> 
									<div class="row">
										<div class="from-group col-md-6"> 
											<h5  class="new-title-b2c-order"> Pickup Location <span class="text-danger">*</span></h5>
											<div class="main-rox-input">
												<select name="warehouse_id" class="control-form select2" id="warehouse_id" style="border-radius: 5px 0px 0px 5px" onchange="warehousePickup(this)" required>
													<option value="">Select Pickup Location</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="new-border-details"><div id="warehouse_lable"></div></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="main-rowx-1 mt-4">
						<div class="main-order-001">
							<div class="main-vender d-flex align-items-center justify-content-between">
								<h5 class="new-title-b2c-order mb-0">Upload Excel</h5>

								<div class="d-flex align-items-center" style="gap: 10px;">
									<a href="{{ route('order.bulk-sample') }}?type=1"
										class="btn-main-1 bulk-sample-btn active-sample" data-type="1">
										<i class="mdi mdi-download"></i>
										B2C Sample Format
									</a>
									{{-- <a href="{{ route('order.bulk-sample') }}?type=2"
										class="btn-main-1 bulk-sample-btn" data-type="2">
										<i class="mdi mdi-download"></i>
										B2B Sample Format
									</a> --}}
								</div>
							</div>
								<div class="row">
									{{-- <div class="col-lg-6 col-sm-6 col-md-6">
										<div class="from-group my-2">
											<label for="type_of_package"> Import Type <span
													class="text-danger">*</span></label>
											<select name="type_of_package" class="control-form select2" id="type_of_package"
												style="border-radius: 5px 0px 0px 5px" required>
												<option value="1">B2C</option>
												<option value="2">B2B</option>
											</select>
										</div>
									</div> --}}
									<input type="hidden" name="type_of_package" id="type_of_package" value="1">
									<div class="col-lg-12 col-sm-12 col-md-12">
										<div class="from-group my-2">
											<label for="packaging-type"> Upload Excel <span
													class="text-danger">*</span></label>
											<input type="file" class="form-control" id="bulk_excel" name="bulk_excel"
												accept=".xlsx,.xls" required>
										</div>
									</div>
								</div>
							</div>
							<div class="text-right">
								<button type="submit" class="new-submit-btn mt-3">Submit</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

@endsection
@push('js')
	<script>
		var $orderForm = $('#orderForm');

		// Warehouse pickup location
		warehousePickupLocation()
		function warehousePickupLocation() {
			$.get("{{ route('order.warehouse.list') }}", function (res) {
				$orderForm.find('#warehouse_id').html(res.output);
			});
		}

		function warehousePickup(obj) {
			const $obj = $(obj);
			let warehouse = $obj.find(':selected').attr('data-warehouse');

			if (!warehouse) {
				$('#warehouse_lable').html(''); // Clear if no warehouse is selected
				return;
			}

			let data;
			try {
				data = JSON.parse(warehouse);
			} catch (error) {
				console.error('Invalid JSON in data-warehouse:', warehouse);
				return;
			}

			let addressLabel = `
						<label>Pickup Details:</label> 
						<p>Address: ${data.address}, ${data.city}, ${data.state}, ${data.country}, ${data.zip_code}</p>
					`;

			$('#warehouse_lable').html(addressLabel);
		}

		function syncSelectedSample() {
			const selectedType = $('#type_of_package').val() || '1';
			$('.bulk-sample-btn').removeClass('active-sample');
			$(`.bulk-sample-btn[data-type="${selectedType}"]`).addClass('active-sample');
		}

		syncSelectedSample();
		$('#type_of_package').on('change', syncSelectedSample);

		// List each failing Excel row so the user can fix the file
		function showRowErrors(title, errors) {
			const rows = errors.map(function (e) {
				return `<tr><td style="padding:6px 10px;white-space:nowrap;vertical-align:top;">Row ${e.row}</td><td style="padding:6px 10px;">${$('<div>').text(e.message).html()}</td></tr>`;
			}).join('');

			Swal.fire({
				icon: 'error',
				title: title,
				width: 700,
				html: `<div style="max-height:350px;overflow:auto;text-align:left;"><table class="table table-sm mb-0">${rows}</table></div>`,
			});
		}

		$orderForm.submit(function (event) {
			event.preventDefault();

			// Disable submit button to prevent multiple submissions
			$orderForm.find(':submit').prop('disabled', true);

			// Create FormData object
			var formData = new FormData(this);
			formData.append('_token', "{{ csrf_token() }}"); // Use meta tag for CSRF token

			$.ajax({
				type: $orderForm.attr('method'),
				url: $orderForm.attr('action'),
				data: formData,
				cache: false,
				processData: false,
				contentType: false,
				dataType: 'json', // Use lowercase "json"
				success: function (res) {
					$orderForm.find(':submit').prop('disabled', false); // Re-enable submit button

					if (res.errors && res.errors.length) {
						showRowErrors(res.msg, res.errors);
					} else {
						toastrMsg(res.status, res.msg); // Show notification
					}

					if (res.status === "success") {
						const weightOrder = res.type_of_package ?? 1;
						setTimeout(function () {
							window.location.href = `{{ route('order') }}?weight_order=${weightOrder}&status=New`;
						}, 1000);
					}
				},
				error: function (xhr) {
					$orderForm.find(':submit').prop('disabled', false); // Re-enable button on failure
					console.error("AJAX error:", xhr.responseText);
					const errorMessage = resolveAjaxErrorMessage(xhr, "Bulk order import failed.");
					toastrMsg("error", errorMessage);
				}
			});
		});

	</script>
@endpush