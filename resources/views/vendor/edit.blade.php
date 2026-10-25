@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Edit Vendor')
@section('header_title','Edit Vendor')
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
        <form id="vendorForm" method="post" class="customer_form" action="{{ route('vendor.update', ['id' => $vendor->id]) }}" enctype="multipart/form-data">
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
                                            <input type="text" autocomplete="off" name="company_name" id="company_name" maxlength="18" placeholder="Company Name" value="{{ $vendor->company_name }}" required> 
        								</div>
        							</div>  
        							<div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> First Name </label>
                                            <input type="text" autocomplete="off" name="first_name" id="first_name" placeholder="First Name" value="{{ $vendor->first_name }}" required> 
        								</div>
        							</div> 
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Last Name </label>
                                            <input type="text" autocomplete="off" name="last_name" id="last_name" placeholder="Last Name" value="{{ $vendor->last_name }}" required> 
        								</div>
        							</div>
        							
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email" placeholder="Email" value="{{ $vendor->email }}" required>
        								</div>
        							</div>
        							
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile" placeholder="Mobile" value="{{ $vendor->mobile }}" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required> 
        								</div>
        							</div> 
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status" > 
                                                <option value="1" {{ $vendor->status == 1 ? 'selected' : '' }}> Active </option>
                                                <option value="0" {{ $vendor->status == 0 ? 'selected' : '' }}> In-Active </option>
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
												Note: If Delhivery API is activated, the address will be sent to the warehouse Update API to add it to the Delhivery portal.
											</small>
										</h4> 
        							</div>
        							<div class="append_address">
										@foreach($vendorAddresses as $key => $row)
											<div class="row align-items-end remove_address_{{ $key }}">
												@php
													$fields = [
														'warehouse_name' => 'Warehouse Name',
														'address' => 'Address',
														'country' => 'Country',
														'state' => 'State',
														'city' => 'City',
														'zip_code' => 'Zip Code',
													];
												@endphp
												
												@foreach($fields as $name => $label)
													<div class="col-xl-3 col-md-4 col-sm-6">
														<div class="from-group my-2">
															<label>{{ $label }}</label>
															<input 
																type="text" 
																class="default" 
																autocomplete="off" 
																maxlength="{{ $name == 'warehouse_name' ? 18 : '' }}" 
																name="{{ $name }}[]" 
																placeholder="{{ $label }}" 
																value="{{ $row->$name }}" 
																required
															> 
														</div>
													</div>
												@endforeach

												<input type="hidden" name="id[]" value="{{ $row->id }}">

												@if($key != 0)
													<div class="col-lg-3 col-md-6">
														<div class="from-group my-2">
															<button type="button" class="btn-light-2" onclick="removeAddress({{ $key }})">
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
		
	$('#vendorForm').submit(function(event) {
		event.preventDefault();  
		$(this).find('button').prop('disabled',true); 
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
			success: function (res) 
			{
				$('button').prop('disabled',false);  
				if(res.status == "error")
				{
					toastrMsg(res.status,res.msg);
				}
				else
				{ 
					toastrMsg(res.status,res.msg); 
					window.location.href = "{{route('vendor')}}";
				}
			} 
		});
	});
</script>
@endpush
