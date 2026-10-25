<div class="modal fade" id="createCustomerModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Add Recipient/Customer </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <form id="customerForm" method="post" action="{{ route('customer.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="username"> First Name <span class="text-danger">*</span></label>
								<input type="text" autocomplete="off" name="first_name" id="first_name" placeholder="First Name" required> 
							</div>
						</div> 
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Last Name <span class="text-danger">*</span></label>
								<input type="text" autocomplete="off" name="last_name" id="last_name" placeholder="Last Name" required> 
							</div>
						</div>
						
						{{-- <div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Email </label>
								<input type="email" autocomplete="off" name="email" id="email" placeholder="Email">
							</div>
						</div> --}}
						
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Mobile <span class="text-danger">*</span></label>
								<input type="text" autocomplete="off" name="mobile" id="mobile" placeholder="Mobile"  maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required> 
							</div>
						</div> 
						
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> GST Number </label>
								<input type="text" name="gst_number"  id="gst_number"  placeholder="Enter GST Number" pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$" title="Enter a valid 15-character GST Number (e.g., 22AAAAA1234A1Z5)"> 
							</div>
						</div> 
						
						{{-- <div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Aadhar Front Image </label>
								<input type="file" name="aadhar_front" id="aadhar_front" accept="image/png, image/jpeg, image/jpg, image/webp" > 
							</div> 
						</div> 
						
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Aadhar Back Image </label>
								<input type="file" name="aadhar_back" id="aadhar_back" accept="image/png, image/jpeg, image/jpg, image/webp" > 
							</div>
						</div> 
						
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Pancard Image </label>
								<input type="file" name="pancard" id="pancard" accept="image/png, image/jpeg, image/jpg, image/webp" > 
							</div>
						</div>  --}}
						 
						<!-- STATUS + Toggle Checkbox -->
						<div class="col-xl-6 col-md-6 col-sm-12">
							<div class="form-group my-2">
								<label for="status">Status</label>
								<select autocomplete="off" name="status" id="status" >
									<option value="1">Active</option>
									<option value="0">In-Active</option>
								</select>
							</div>
						</div>

						<div class="col-xl-6 col-md-6 col-sm-12">
							<div class="form-group my-2 d-flex align-items-center" style="height:100%;">
								<div class="form-check mt-3">
									<input class="form-check-input" type="checkbox" id="toggleContactDetails">
									<label class="form-check-label" for="toggleContactDetails">
										Email & Alternate Mobile
									</label>
								</div>
							</div>
						</div> 
                    </div> 
					<div id="contact-details-section" class="row" style="display:none;">
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="email"> Email </label>
								<input type="email" autocomplete="off" name="email" id="email" class="form-control" placeholder="Email" >
							</div>
						</div>

						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="alternate_mobile"> Alternate Mobile </label>
								<input type="text" autocomplete="off" name="alternate_mobile" id="alternate_mobile" class="form-control"
									placeholder="Alternate Mobile" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits">
							</div>
						</div> 
					</div>
                    <hr /> 
                    <div class="row">
                        <div class="col-xl-12 col-md-12 col-sm-12">
							<div class="from-group my-2">
								<label for="username"> Address <span class="text-danger">*</span></label>
								<textarea class="default" data-id="0" autocomplete="off" name="address[]" id="address" placeholder="Address" required></textarea> 
							</div>
						</div>
						 
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="first-name"> Zip code <span class="text-danger">*</span></label>
								<input class="default" type="text" data-id="0" autocomplete="off" name="zip_code[]" id="zip_code" placeholder="Zip code" onkeyup="autoFetchCountry(this)" required> 
							</div>
						</div>  
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="username"> City <span class="text-danger">*</span></label>
								<input class="default" type="text" data-id="0" autocomplete="off" name="city[]" id="city" placeholder="City" required> 
							</div>
						</div> 
						
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="username"> State <span class="text-danger">*</span></label>
								<input class="default" type="text" data-id="0" autocomplete="off" name="state[]" id="state" placeholder="State" required> 
							</div>
						</div> 
						<div class="col-xl-6 col-md-4 col-sm-12">
							<div class="from-group my-2">
								<label for="username"> Country <span class="text-danger">*</span></label>
								<input class="default" type="text" data-id="0" autocomplete="off" name="country[]" id="country" placeholder="Country" required> 
							</div>
						</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="new-submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>
	<script>
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
							customerDetailsList() 
							setTimeout(function(){ 
								$('#orderForm #customer_id').val(res.customer_id).trigger('change');
								customerAddresList(this, res.customer_address_id);
							}, 1000);
							$('#createCustomerModal').modal('hide');
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
			
			const zip_code = $(obj).val().trim();  
			
			if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
				zipTimeout = setTimeout(() => {
					$.ajax({
						type: 'GET',
						url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
						success: function (response) {
							if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
								const { District: city, State: state, Country: country } = response[0].PostOffice[0];
								
								$(`#city`).val(city);
								$(`#state`).val(state);
								$(`#country`).val(country);
								} else {
								console.warn("Invalid or missing pincode data.");
							}
						},
						error: function (xhr, status, error) {
							console.error("API Error:", error);
						}
					});
				}, 500); // Debounce API request by 500ms
			}
		}

		// Toggle contact details on checkbox click
		$("#toggleContactDetails").on("change", function () {
			if ($(this).is(":checked")) {
				$("#contact-details-section").slideDown(150);
			} else {
				// hide and clear fields when unchecked
				$("#contact-details-section").slideUp(150, function(){
					$("#email").val('');
					$("#alternate_mobile").val('');
				});
			}
		});

		// Numeric-only enforcement for mobile inputs (prevents non-digits)
		function allowOnlyDigits(selector) {
			$(document).on('input', selector, function () {
				const clean = $(this).val().replace(/\D+/g, '');
				if ($(this).val() !== clean) {
					$(this).val(clean);
				}
			});
		}
		allowOnlyDigits('#mobile');
		allowOnlyDigits('#alternate_mobile');
	</script>
</div>