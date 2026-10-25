<div class="modal fade" id="createWarehousePickupModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Add Warehouse </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
				</button>
			</div> 
            <form id="warehouseForm" action="{{ route('warehouse.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body row">
					
					<div class="col-xl-12 col-md-12 col-sm-12">
						<h4 class="mb-0"> 
							<small class="text-muted d-block">
								Note: If Delhivery API is activated, the address will be sent to the warehouse API to add it to the Delhivery portal.
							</small>
						</h4> 
					</div> 
                
                    <div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> Company Name </label>
							<input type="text" autocomplete="off" name="company_name" id="company_name" maxlength="18" placeholder="Company Name" required> 
						</div>
					</div> 
					
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> Contact Name </label>
							<input type="text" autocomplete="off" name="contact_name" id="contact_name" placeholder="Contact Name" required> 
						</div>
					</div>
					<div class="col-xl-4 col-md-4 col-sm-6">
						<div class="from-group my-2">
							<label for="username"> Contact Email </label>
							<input type="text" autocomplete="off" name="contact_email" id="contact_email" placeholder="Contact Name" required> 
						</div>
					</div>
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="first-name"> Contact Number </label>
							<input type="text" autocomplete="off" name="contact_number" id="contact_number" placeholder="Contact Number"  maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required> 
						</div>
					</div> 
			
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> Warehouse Name </label>
							<input class="default" type="text" data-id="0" autocomplete="off" maxlength="18" name="warehouse_name" id="warehouse_name" placeholder="Warehouse Name" required> 
						</div>
					</div>
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> Address </label>
							<textarea class="default" type="text" data-id="0" autocomplete="off" style="height: 48px;" name="address" id="address" placeholder="Address" required></textarea>
						</div>
					</div> 
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="first-name"> Zip code <span id="pincode_serviceability_status"></span></label>
							<input class="default" type="text" data-id="0" autocomplete="off" name="zip_code" id="zip_code" placeholder="Zip code" onkeyup="autoFetchCountry(this)" required> 
						</div>
					</div>  

					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> City </label>
							<input class="default" type="text" data-id="0" autocomplete="off" name="city" id="city" placeholder="City" required> 
						</div>
					</div>
				
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> State </label>
							<input class="default" type="text" data-id="0" autocomplete="off" name="state" id="state" placeholder="State" required> 
						</div>
					</div>
					
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="username"> Country </label>
							<input class="default" type="text" data-id="0" autocomplete="off" name="country" id="country" placeholder="Country" required> 
						</div>
					</div> 	
										
					<div class="col-xl-4 col-md-4 col-sm-12">
						<div class="from-group my-2">
							<label for="first-name"> Status </label>
							<select autocomplete="off" name="warehouse_status" id="warehouse_status" > 
								<option value="1"> Active </option>
								<option value="0"> In-Active </option>
							</select>
						</div>
					</div>  
				</div>
                <div class="modal-footer" style="border-top: 0px; padding-top: 0px;">
                    <button type="submit" class="new-submit-btn">Submit</button>
				</div>
			</form>
		</div>
	</div> 
	<script>  
		$(document).ready(function ()
		{
			const $warehouseForm = $("#warehouseForm");
			  
			// Enable button initially
			$warehouseForm.find('input[type="submit"]').prop("disabled", false);
	 
			$warehouseForm.on("submit", function (event) {
				event.preventDefault();
				let $submitButton = $warehouseForm.find('button[type="submit"]');
				$submitButton.prop("disabled", true); // Disable button to prevent multiple clicks
	 
				// Create FormData object
				let formData = new FormData(this);
				formData.append('_token', "{{ csrf_token() }}");

				// AJAX Request
				$.ajax({
					url: $warehouseForm.attr("action"),
					type: $warehouseForm.attr("method"),
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
							toastrMsg(res.status, res.msg); 
							warehousePickupLocation()
							setTimeout(function (){
								$('#orderForm #warehouse_id').val(res.warehouse_id).trigger('change');
							}, 1000); 
							$('#createWarehousePickupModal').modal('hide');
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
		
		var zipTimeouts;
	
		function autoFetchCountry(obj)
		{
			clearTimeout(zipTimeouts); // Clear previous timeout
			
			const zip_code = $(obj).val().trim();  
			
			if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
				// Check serviceability
				checkServiceability(zip_code, 'from', '#pincode_serviceability_status');

				zipTimeouts = setTimeout(() => {
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
			} else {
				$('#pincode_serviceability_status').html('');
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
	</script>
</div>