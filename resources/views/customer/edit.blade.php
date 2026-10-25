@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Edit Client')
@section('header_title','Edit Client')
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
        <form id="customerForm" method="post" class="customer_form" action="{{ route('customer.update', ['id' => $customer->id]) }}" enctype="multipart/form-data">
			@csrf
            <div class="container-fluid">
                <div class="main-rowx-1 mt-3">
                    <div class="main-row main-data-teble-1">
                        <div class="main-rowx-1">
                            <div class="main-order-001">
								<div class="main-heading-1 pt-lg-0 pb-lg-0 px-lg-0">
									<h4 class="mb-0 card-title"> Client Details </h4>
								</div> 
                                <div class="row">
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> First Name </label>
                                            <input type="text" autocomplete="off" name="first_name" id="first_name" placeholder="First Name" value="{{ $customer->first_name }}" required> 
        								</div>
        							</div> 
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Last Name </label>
                                            <input type="text" autocomplete="off" name="last_name" id="last_name" placeholder="Last Name" value="{{ $customer->last_name }}" required> 
        								</div>
        							</div>
        							
                                    {{-- <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email" placeholder="Email" value="{{ $customer->email }}" >
        								</div>
        							</div> --}}
        							
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile" placeholder="Mobile" value="{{ $customer->mobile }}" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required> 
        								</div>
        							</div>  
									<div class="col-xl-3 col-md-4 col-sm-6">
										<div class="form-group my-2 d-flex align-items-center h-auto h-sm-100">
											<div class="form-check mt-3">
												<input class="form-check-input" type="checkbox"
													id="toggleContactDetails" {{ $customer->email || $customer->alternate_mobile ? 'checked' : '' }}>
												<label class="form-check-label" for="toggleContactDetails">
													Email & Alternate Mobile
												</label>
											</div>
										</div>
									</div>
									<div class="col-xl-6 col-md-8">
                                           <div id="contact-details-section" class="row" style="display:{{ $customer->email || $customer->alternate_mobile ? '' : 'none' }};margin-left: 0">
										<div class="col-sm-6">
											<div class="from-group my-2">
												<label for="email"> Email </label>
												<input type="email" autocomplete="off" name="email" id="email"
													class="form-control" placeholder="Email" value="{{ $customer->email }}">
											</div>
										</div>

										<div class="col-sm-6">
											<div class="from-group my-2">
												<label for="alternate_mobile"> Alternate Mobile </label>
												<input type="text" autocomplete="off" name="alternate_mobile"
													id="alternate_mobile" class="form-control"
													placeholder="Alternate Mobile" maxlength="10" pattern="\d{10}"
													title="Please enter exactly 10 digits" value="{{ $customer->alternate_mobile }}">
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
                                    <div class="main-heading-1 pt-lg-0 pb-lg-0 px-lg-0">
                                        <h4 class="mb-0 card-title">Client Address </h4>
        							</div>
        							<div class="append_address">
        								@foreach($customerAddresses as $key => $row)
											<div class="row align-items-end remove_address_{{ $key }}">
												@php
													$fields = [ 
														'address' => 'Address',
														'zip_code' => 'Zip Code',
														'city' => 'City', 
														'state' => 'State',
														'country' => 'Country',
														
													];
												@endphp
												
												@foreach($fields as $name => $label)
													@if($name == "address")
														<div class="col-xl-12 col-md-4 col-sm-6">
															<div class="from-group my-2">
																<label>{{ $label }}</label>
																<textarea 
																	type="text" 
																	class="default" 
																	autocomplete="off"  
																	name="{{ $name }}[]" 
																	placeholder="{{ $label }}"  
																	required
																>{{ $row->$name }}</textarea>
															</div>
														</div>
													@else
														<div class="col-xl-3 col-md-4 col-sm-6">
															<div class="from-group my-2">
																<label>{{ $label }} @if($name == 'zip_code') <span id="pincode_serviceability_status_{{ $key }}"></span> @endif</label>
																<input 
																	type="text" 
																	class="default" 
																	autocomplete="off" 
																	data-id="{{ $key }}"
																	maxlength="{{ $name == 'warehouse_name' ? 18 : '' }}" 
																	name="{{ $name }}[]" 
																	placeholder="{{ $label }}" 
																	value="{{ $row->$name }}" 
																	@if($name == 'zip_code')
																		onkeyup="autoFetchCountry(this)"
																	@endif
																	id="{{ $name }}_{{ $key }}"
																	required
																> 
															</div>
														</div>
													@endif
												@endforeach

												<input type="hidden" name="id[]" value="{{ $row->id }}">

												@if($key != 0)
													<div class="col-lg-3 col-md-6">
														<div class="from-group my-2">
															<button type="button" class="btn-light-2" onclick="removeAddress({{ $key }})" >
																<i class="mdi mdi-trash-can-outline"></i> Delete
															</button>
														</div>
													</div>
												@endif
											</div>
        								@endforeach
        							</div>
        						</div>
                                <div class="text-align-left">
                                    <button class="btn-light-1" id="add_new_address" type="button" > + Add another Address </button>
        						</div>
    						</div>
						</div>
						<div class="main-rowx-1"> 
                            <div class="main-order-001">
								<div class="main-heading-1 pt-lg-0 pb-lg-0 px-lg-0">
									<h4 class="mb-0 card-title"> Client Document </h4>
								</div> 
                                <div class="row"> 
									<div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> GST Number </label>
                                            <input type="text" name="gst_number"  id="gst_number"  placeholder="Enter GST Number" pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$" title="Enter a valid 15-character GST Number (e.g., 22AAAAA1234A1Z5)" value="{{ $customer->gst_number }}"> 
        								</div>
        							</div> 
									
									{{-- <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Aadhar Front Image </label>
                                            <input type="file" name="aadhar_front" id="aadhar_front" accept="image/png, image/jpeg, image/jpg, image/webp" > 
        								</div>
										@if($customer->aadhar_front)
											<img src="{{ url('storage/customer/aadhar', $customer->aadhar_front)}}" height=50>
										@endif
        							</div> 
									
									<div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Aadhar Back Image </label>
                                            <input type="file" name="aadhar_back" id="aadhar_back" accept="image/png, image/jpeg, image/jpg, image/webp" > 
        								</div>
										@if($customer->aadhar_front)
											<img src="{{ url('storage/customer/aadhar', $customer->aadhar_back)}}" height=50>
										@endif
        							</div> 
									
									<div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Pancard Image </label>
                                            <input type="file" name="pancard" id="pancard" accept="image/png, image/jpeg, image/jpg, image/webp" > 
        								</div>
										@if($customer->aadhar_front)
											<img src="{{ url('storage/customer/pancard', $customer->pancard)}}" height=50>
										@endif
        							</div>  --}}
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status" > 
                                                <option value="1" {{ $customer->status == 1 ?'selected' : '' }}> Active </option>
                                                <option value="0" {{ $customer->status == 0 ?'selected' : '' }}> In-Active </option>
        									</select>
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
	 
	var i = 100; 
	$('#add_new_address').click(function () {
		var html = `
			<div class="row align-items-end remove_address_${i}"> 
				<div class="col-xl-12 col-md-4 col-sm-6">
					<div class="from-group my-2">
						<label for="address_${i}"> Address </label>
						<input class="default" type="text" data-id="${i}" autocomplete="off" name="address[]" id="address_${i}" placeholder="Address" required>
					</div>
				</div>
				<div class="col-xl-3 col-md-4 col-sm-6">
					<div class="from-group my-2">
						<label for="zip_code_${i}"> Zip Code <span id="pincode_serviceability_status_${i}"></span></label>
						<input class="default" type="text" data-id="${i}" autocomplete="off" name="zip_code[]" id="zip_code_${i}" placeholder="Zip Code" onkeyup="autoFetchCountry(this)" required>
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
						<label for="state_${i}"> State </label>
						<input class="default" type="text" data-id="${i}" autocomplete="off" name="state[]" id="state_${i}" placeholder="State" required>
					</div>
				</div>
				<div class="col-xl-3 col-md-4 col-sm-6">
					<div class="from-group my-2">
						<label for="country_${i}"> Country </label>
						<input class="default" type="text" data-id="${i}" autocomplete="off" name="country[]" id="country_${i}" placeholder="Country" required>
					</div>
				</div>
				 
				<div class="col-lg-3 col-md-6">
					<div class="from-group my-2">
						<button type="button" class="btn-light-2" data-id="${i}" onclick="removeAddress(${i})">
							<i class="mdi mdi-trash-can-outline"></i> Delete
						</button>
					</div>
				</div>
				<input type="hidden" name="id[]" value="">
			</div>`;

		$('.append_address').append(html);
		i++;
	});
 
	function removeAddress(removeId) {
		$('.remove_address_' + removeId).remove();
	}

	$(document).ready(function ()
	{
		const $customerForm = $("#customerForm");
		  
		// Enable button initially
		$customerForm.find('input[type="submit"]').prop("disabled", false);
 
		$customerForm.on("submit", function (event) {
			event.preventDefault();
			let $submitButton = $customerForm.find('button[type="submit"]');
			$submitButton.prop("disabled", true); // Disable button to prevent multiple clicks
 
			// Create FormData object
			let formData = new FormData(this);
			formData.append('_token', "{{ csrf_token() }}");

			// AJAX Request
			$.ajax({
				url: $customerForm.attr("action"),
				type: $customerForm.attr("method"),
				data: formData,
				cache: false,
				processData: false,
				contentType: false, 
				dataType: 'Json',
				success: function (res)
				{
					$submitButton.prop("disabled", false);  
					if (res.status === "success") 
					{
						toastrMsg(res.status,res.msg);
						$customerForm[0].reset(); 
						setTimeout(() => {
							window.location.href = "{{ route('customer') }}";
						}, 1000); 
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
	
	let zipTimeout;
	
	function autoFetchCountry(obj)
	{
		clearTimeout(zipTimeout); // Clear previous timeout
		
		const zip_code = $(obj).val().trim(); // Trim whitespace
		const index = $(obj).data('id'); // Get index from data-id
		
		if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
			// Check serviceability (to/delivery direction)
			checkServiceability(zip_code, 'to', `#pincode_serviceability_status_${index}`);

			zipTimeout = setTimeout(() => {
				$.ajax({
					type: 'GET',
					url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
					success: function (response) {
						if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
							const { District: city, State: state, Country: country } = response[0].PostOffice[0];
							
							$(`#city_${index}`).val(city);
							$(`#state_${index}`).val(state);
							$(`#country_${index}`).val(country);
							} else {
							console.warn("Invalid or missing pincode data.");
						}
					},
					error: function (xhr, status, error) {
						console.error("API Error:", error);
					}
				});
			}, 500); // Debounce API request by 500ms
		} else {
			$(`#pincode_serviceability_status_${index}`).html('');
		}
	}

	function checkServiceability(pincode, direction, targetId) {
		if (!pincode) return;
		
		$(targetId).html('<small class="text-muted ml-2"><i class="fa fa-spinner fa-spin"></i> Checking...</small>');

		$.ajax({
			url: "{{ route('rate.shipping.pincode.serviceability') }}",
			type: "GET",
			data: { pincode: pincode, direction: direction },
			success: function(response) {
				if (response.status === 'success') {
					if (response.is_serviceable) {
						let couriers = response.supporting_couriers && response.supporting_couriers.length ? ' <small class="text-muted">[' + response.supporting_couriers.join(', ') + ']</small>' : '';
						$(targetId).html('<span class="text-success ml-2" style="font-weight:600;"><i class="fa fa-check-circle"></i> Serviceable' + couriers + '</span>');
					} else {
						$(targetId).html('<span class="text-danger ml-2" style="font-weight:600;"><i class="fa fa-times-circle"></i> Not Serviceable</span>');
					}
				} else {
					$(targetId).html('<span class="text-warning ml-2"><i class="fa fa-exclamation-triangle"></i> ' + (response.msg || 'Pincode not found') + '</span>');
				}
			},
			error: function() {
				$(targetId).html('<span class="text-muted ml-2">Check Failed</span>');
			}
		});
	}

	$(document).ready(function() {
		// Trigger serviceability check on load for all existing addresses
		$('input[name="zip_code[]"]').each(function() {
			const zipVal = $(this).val();
			if (zipVal && /^\d{6}$/.test(zipVal.trim())) {
				const index = $(this).data('id');
				checkServiceability(zipVal.trim(), 'to', `#pincode_serviceability_status_${index}`);
			}
		});
	});

	// Toggle contact details on checkbox click
	$("#toggleContactDetails").on("change", function() {
		if ($(this).is(":checked")) {
			$("#contact-details-section").slideDown(150);
		} else {
			// hide and clear fields when unchecked
			$("#contact-details-section").slideUp(150, function() {
				$("#email").val('');
				$("#alternate_mobile").val('');
			});
		}
	});

	// Numeric-only enforcement for mobile inputs (prevents non-digits)
	function allowOnlyDigits(selector) {
		$(document).on('input', selector, function() {
			const clean = $(this).val().replace(/\D+/g, '');
			if ($(this).val() !== clean) {
				$(this).val(clean);
			}
		});
	}
	allowOnlyDigits('#mobile');
	allowOnlyDigits('#alternate_mobile');
</script>
@endpush
