@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Create Vendor')
@section('header_title','Create Vendor')
@section('content') 
<style>
	button.btn-light-2 {
    padding: 7px 12px;
    font-size: 14px;
    border: none;
    background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
    border-radius: 5px;
	}
</style>
<div class="content-page">
    <div class="content">
        <form id="vendorForm" method="post" class="customer_form" action="{{ route('vendor.store') }}" enctype="multipart/form-data">
			@csrf
            <div class="container-fluid">
                <div class="main-rowx-1 mt-3">
                    <div class="main-row main-data-teble-1">
                        <div class="main-rowx-1">
                            <div class="main-order-001">
                                <div class="row">
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Company Name </label>
                                            <input type="text" autocomplete="off" name="company_name" id="company_name" maxlength="18" placeholder="Company Name" required> 
        								</div>
        							</div> 
        							<div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> First Name </label>
                                            <input type="text" autocomplete="off" name="first_name" id="first_name" placeholder="First Name" required> 
        								</div>
        							</div> 
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Last Name </label>
                                            <input type="text" autocomplete="off" name="last_name" id="last_name" placeholder="Last Name" required> 
        								</div>
        							</div>
        							
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email" placeholder="Email" required>
        								</div>
        							</div>
        							
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile" placeholder="Mobile"  maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required> 
        								</div>
        							</div> 
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status" > 
                                                <option value="1"> Active </option>
                                                <option value="0"> In-Active </option>
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
                                       <h4 class="mb-0">
											Address 
											<small class="text-muted d-block">
												Note: If Delhivery API is activated, the address will be sent to the warehouse API to add it to the Delhivery portal.
											</small>
										</h4> 
        							</div>
        							<div class="append_address"> 
        								<div class="row align-items-end">
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="username"> Warehouse Name </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" maxlength="18" name="warehouse_name[]" id="warehouse_name" placeholder="Warehouse Name" required> 
        										</div>
        									</div>
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="username"> Address </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" name="address[]" id="address" placeholder="Address" required> 
        										</div>
        									</div>
        									
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="username"> Country </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" name="country[]" id="country" placeholder="Country" required> 
        										</div>
        									</div>
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="username"> State </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" name="state[]" id="state" placeholder="State" required> 
        										</div>
        									</div>
        									
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="username"> City </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" name="city[]" id="city" placeholder="City" required> 
        										</div>
        									</div>
        									
        									<div class="col-xl-3 col-md-4 col-sm-6">
        										<div class="from-group my-2">
        											<label for="first-name"> Zip code </label>
        											<input class="default" type="text" data-id="0" autocomplete="off" name="zip_code[]" id="zip_code" placeholder="Zip code" required> 
        										</div>
        									</div>  
        								</div>
        							</div>
        						</div>
                                <div class="text-align-left">
                                    <button class="btn-light-1" id="add_new_address" type="button" > + Add another Address </button>
        						</div>
    						</div>
						</div> 
					</div>
				</div> 
                <div class="text-right mb-4">
                    <button class="new-submit-btn" type="submit" id="customer_submit"> Submit </button>
				</div>
			</div>
		</form>
	</div>
</div>
@endsection
@push('js')
<script>
	$(document).ready(function () {
		var i = 1;

		$('#add_new_address').click(function () {
			var html = `
				<div class="row align-items-end remove_address_${i}">
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="warehouse_name_${i}"> Warehouse Name </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" maxlength="18" name="warehouse_name[]" id="warehouse_name_${i}" placeholder="Warehouse Name" required>
						</div>
					</div>
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="address_${i}"> Address </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" name="address[]" id="address_${i}" placeholder="Address" required>
						</div>
					</div>
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="country_${i}"> Country </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" name="country[]" id="country_${i}" placeholder="Country" required>
						</div>
					</div>
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="state_${i}"> State </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" name="state[]" id="state_${i}" placeholder="State" required>
						</div>
					</div>
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="city_${i}"> City </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" name="city[]" id="city_${i}" placeholder="City" required>
						</div>
					</div>
					<div class="col-xl-3 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="zip_code_${i}"> Zip Code </label>
							<input class="default" type="text" data-id="${i}" autocomplete="off" name="zip_code[]" id="zip_code_${i}" placeholder="Zip Code" required>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="from-group my-2">
							<button type="button" class="btn-light-2 remove_address_btn" data-id="${i}">
								<i class="mdi mdi-trash-can-outline"></i> Delete
							</button>
						</div>
					</div>
				</div>`;

			$('.append_address').append(html);
			i++;
		});

		// Remove address dynamically
		$(document).on('click', '.remove_address_btn', function () {
			var removeId = $(this).data('id');
			$('.remove_address_' + removeId).remove();
		});
	});
 
	$(document).ready(function ()
	{
		const $vendorForm = $("#vendorForm");
		  
		// Enable button initially
		$vendorForm.find('input[type="submit"]').prop("disabled", false);
 
		$vendorForm.on("submit", function (event) {
			event.preventDefault();
			let $submitButton = $vendorForm.find('button[type="submit"]');
			$submitButton.prop("disabled", true); // Disable button to prevent multiple clicks
 
			// Create FormData object
			let formData = new FormData(this);
			formData.append('_token', "{{ csrf_token() }}");

			// AJAX Request
			$.ajax({
				url: $vendorForm.attr("action"),
				type: $vendorForm.attr("method"),
				data: formData,
				cache: false,
				processData: false,
				contentType: false, 
				dataType: 'Json',
				success: function (res)
				{
					$submitButton.prop("disabled", false); // Re-enable button
					if (res.status === "success") 
					{
						toastrMsg(res.status,res.msg);
						$vendorForm[0].reset(); 
						setTimeout(() => {
							window.location.href = "{{ route('vendor') }}";
						}, 1000); // Redirect after 1 second
					}
					else if(res.status == "info")
					{  
						toastrMsg('info', res.msg);
					}else {
						toastrMsg(res.status,res.msg);
					} 
				},
				error: function (xhr) { 
					toastrMsg("error","Something went wrong. Please try again.");
					$submitButton.prop("disabled", false); // Re-enable button
				}
			});
		});
	}); 
</script>
@endpush
