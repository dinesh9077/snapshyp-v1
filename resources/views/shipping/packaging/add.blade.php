@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Create Packaging')
@section('header_title','Create Packaging')
@section('content')
<style>
	button.btn-light-2 {
		padding: 7px 12px;
		font-size: 14px;
		border: none;
		background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
		border-radius: 5px;
	}

	.main-heading-1.pt-lg-0.pb-lg-2.px-lg-0 h4 {
		font-size: 18px;
		font-weight: 600;
		color: #000;
		margin: 0;
		margin-bottom: 15px !important;
	}

	.main-heading-1.pt-lg-0.pb-lg-2.px-lg-0 {
		padding: 0 !important;
	}
</style>
<div class="content-page">
	<div class="content">
		<form id="packagingForm" method="post" class="customer_form" action="{{route('shipment.packaging.store')}}" enctype="multipart/form-data">
			@csrf
			<div class="container-fluid">

				<div class="main-rowx-1 mt-3">
					<div class="main-row main-data-teble-1">
						<div class="main-rowx-1">
							<div class="main-order-001">
								<div class="row" style="row-gap: 15px;">
									<div class="col-lg-6 col-md-6">
										<div class="from-group">
											<label for="username"> Package Name </label>
											<input type="text" autocomplete="off" name="name" id="name" placeholder="Package Name" required>
										</div>
									</div>
									<div class="col-lg-6 col-md-6">
										<div class="from-group">
											<label for="first-name"> Package Type </label>
											<select class="form-control" name="type" id="type">
												<option value="Box">Box</option>
												<option value="Courier Bag">Courier Bag</option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="main-rowx-1">
							<div class="main-order-001">
								<div class="address_block">
									<div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
										<h4 class="mb-0"> Package Dimensions </h4>
									</div>
									<div class="append_address">
										<div class="row align-items-end" style="row-gap: 15px;">
											<div class="col-lg-4 col-md-4">
												<div class="from-group">
													<label for="username"> Length (CM)</label>
													<input class="default" type="text" autocomplete="off" name="length" id="length" placeholder="Length" required>
												</div>
											</div>

											<div class="col-lg-4 col-md-4">
												<div class="from-group">
													<label for="username"> Width (CM)</label>
													<input class="default" type="text" autocomplete="off" name="width" id="width" placeholder="Width" required>
												</div>
											</div>
											<div class="col-lg-4 col-md-4">
												<div class="from-group">
													<label for="username"> Height (CM)</label>
													<input class="default" type="text" data-id="0" autocomplete="off" name="height" id="height" placeholder="Height" required>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="main-rowx-1">
							<div class="main-order-001">
								<div class="address_block">
									<div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
										<h4 class="mb-0"> Package Images </h4><span>(Upload L X B X H images)</span>
									</div>
									<div class="append_address">
										<div class="row align-items-end" style="row-gap: 15px;">
											<div class="col-lg-4 col-md-4">
												<div class="from-group">
													<label for="username"> Upload Files</label>
													<input class="default" type="file" autocomplete="off" name="images[]" id="images" multiple required>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="main-rowx-1">
							<div class="main-order-001">
								<div class="address_block">
									<div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
										<h4 class="mb-0"> Add Skus </h4>
									</div>
									<div class="append_address">
										<div class="row align-items-end" style="row-gap: 15px;">
											<div class="col-lg-6 col-md-6">
												<div class="from-group">
													<label for="username"> Add Single SKU </label>
													<input class="default" type="text" name="sku" id="sku" placeholder="Add Single SKU" required>
												</div>
											</div>
											<div class="col-lg-6 col-md-6">
												<div class="from-group">
													<label for="first-name"> Status </label>
													<select class="form-control" name="status" id="status">
														<option value="1">Active</option>
														<option value="0">In-Active</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="text-right mb-4">
					<button class="new-submit-btn" id="customer_submit"> Submit </button>
				</div>
			</div>
		</form>
	</div>
</div>
@endsection
@push('js')
<script>
	$('#packagingForm').submit(function(event) {
		event.preventDefault();
		$(this).find('button').prop('disabled', true);
		var formData = new FormData(this);
		$.ajax({
			async: true,
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: formData,
			cache: false,
			processData: false,
			contentType: false,
			dataType: 'Json',
			success: function(res) {
				$('button').prop('disabled', false);
				if (res.status == "error") {
					toastrMsg(res.status, res.msg);
				} else {
					toastrMsg(res.status, res.msg);
					window.location.href = "{{route('shipment.packaging')}}";
				}
			}
		});
	});
</script>
@endpush