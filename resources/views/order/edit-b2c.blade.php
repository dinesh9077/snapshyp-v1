@extends('layouts.backend.app')
@php
    $formMode = 'edit';
    $isEditMode = $formMode === 'edit';
    $isCloneMode = $formMode === 'clone';
    $isPrefillMode = isset($order) && ($isEditMode || $isCloneMode);
    $formAction = $isEditMode ? route('order.update', ['id' => $order->id]) : route('order.store');
    $pageTitle = $isEditMode ? 'Edit Shipments' : ($isCloneMode ? 'Clone Shipments' : 'Create Shipments');
@endphp
@section('title', config('setting.company_name') . ' - ' . $pageTitle)
@section('header_title', $pageTitle)
@section('content')
<style>
    button.d-002 {
        padding: 6px 15px;
        border-radius: 4px 4px 4px 4px !important;
        font-size: 20px;
        font-weight: 700;
    }

 
	/* #select2-customer_address_id-container { 
		max-width: 76.5ch; 
	} */
    .select2-container {
		width: calc(100% - 50px) !important;
	}

</style>
<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <form id="orderForm" method="post" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                <div class="main-create-order"> 
                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="row">
                                <div class="col-xl col-lg-4 col-sm-6">
                                    <div class="from-group my-2">
                                        <label for="order-id"> Order ID <span class="text-danger">*</span> </label>
                                        <input type="text" placeholder="Order Id" readonly name="order_prefix" value="{{ $isEditMode ? $order->order_prefix : \App\Models\Order::generateOrderNumber($user->id) }}">
                                    </div>
                                </div> 
                                <div class="col-xl col-lg-4 col-sm-6">
                                    <div class="from-group my-2">
                                        <label for="reference_number"> Reference Number <span class="text-danger">*</span> </label>
                                        <input type="text" placeholder="Reference Number" name="reference_number" value="{{ $isEditMode ? $order->reference_number : '' }}" required>
                                    </div>
                                </div> 
								<input type="hidden" name="shipping_mode" value="Surface">
								<input type="hidden" name="weight_order" value="{{ request('weight_order') ?? ($order->weight_order ?? 1) }}">
                                <div class="col-xl col-lg-4 col-sm-6 ">
                                    <div class="from-group my-2">
                                        <label for="packaging-type"> Date <span class="text-danger">*</span></label>
                                        <input type="text" name="order_date" class="datepicker" autocomplete="off" placeholder="Date" value="{{ $order->order_date ?? date('Y-m-d') }}" id="order_date" required>
                                    </div>
                                </div> 
								<div class="col-xl col-lg-4 col-sm-6">
                                    <div class="from-group my-2">
                                        <label for="order-type"> Order Type <span class="text-danger">*</span></label>
                                        <select name="order_type" id="order_type" required>
                                            <option value="cod" {{ ($order->order_type ?? 'cod') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                                            <option value="prepaid" {{ ($order->order_type ?? '') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                        </select>
                                    </div>
                                </div>
								<div class="col-xl col-lg-4 col-sm-6">
                                    <div class="from-group my-2">
                                        <label for="order-id"> Amount To Collect</label>
                                        <input type="text" placeholder="Amount To Collect" id="cod_amount" name="cod_amount" value="{{ $order->cod_amount ?? '' }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
                                    </div>
                                </div>  
                            </div>
                        </div>
                    </div>

					<div class="main-rowx-1">
                        <div class="main-order-001">
							<div class="row">
								<div class="col-lg-12"> 
									
									<div class="row">
										<div class="from-group col-md-6"> 
										<h5 style="    margin-bottom: 10px;"> Pickup Location <span class="text-danger">*</span> </h5>

											<div class="main-rox-input">
												<select name="warehouse_id" class="control-form select2" id="warehouse_id" style="border-radius: 5px 0px 0px 5px" onchange="warehousePickup(this)" required>
													<option value="">Select Pickup Location</option> 
												</select>
												<button type="button" class=" new-height-btn-plus" onclick="createWarehousePickup(this, event)"> <i class="mdi mdi-plus"></i></button>
											</div>
										
											
										</div> 
										<div class="col-md-6 ">
											<div class="new-border-details"><div id="warehouse_lable">  
											</div> 
											</div>

										</div>
									</div> 
								</div>
							</div>
                        </div>
                    </div>
					
                    <div class="main-rowx-1">
                        <div class="main-order-001">
							<div class="row">
								<div class="col-lg-12"> 
									<div class="row">
										<div class="from-group col-md-6"> 
											<label for="order-id"> Recipient/Customer </label>
											<div class="main-rox-input">
												<select name="customer_id" class="control-form select2" id="customer_id" style="border-radius: 5px 0px 0px 5px" onchange="customerAddresList(this)" required>
													<option value="">Select Recipient/Customer</option>
												</select>
												<button type="button" class="new-height-btn-plus" onclick="createCustomer(this, event)"> <i class="mdi mdi-plus"></i> </button>
											</div>
										</div> 
										<div class="from-group col-md-6 new-border-details" >
											<label for="order-id"> Customer Address </label>
											<div class="main-rox-input">
												<select name="customer_address_id" class="control-form select2" id="customer_address_id" style="border-radius: 5px 0px 0px 5px" onchange="customerAddressChange(this)" required>
													<option value="">Select Customer Address</option>
												</select>
												<button type="button" class="new-height-btn-plus" disabled data-toggle="tooltip" data-placement="right" title="Kindly select a customer before adding an address." onclick="createCustomerAddress(this, event)"> <i class="mdi mdi-plus"></i> </button>
											</div>
											<div class=""><div id="customer_address_label"></div></div>
										</div>
									</div>
								</div>
							</div>
						</div>
                    </div>

                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="main-vender">
                                <h5 class="new-title-b2c-order"> Product Information </h5>
                            </div>
                            <div class="add_product_more">
                                <div class="row align-items-end removeProductRows"> 
                                    <div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> Product Category <span class="text-danger">*</span></label>
                                            <input type="hidden" name="item_id[]" value="">
                                            <input type="text" placeholder="Product Category" name="product_category[]" id="product_category" required>
                                        </div>
                                    </div> 
                                    <div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> Product Name <span class="text-danger">*</span></label>
                                            <input type="text" placeholder="Product Name" name="product_name[]" id="product_name" required>
                                        </div>
                                    </div>
									<div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> SKU Number</label>
                                            <input type="text" placeholder="SKU Number" name="sku_number[]" id="sku_number">
                                        </div>
                                    </div>
									<div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> HSN No</label>
                                            <input type="text" placeholder="HSN No" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" name="hsn_number[]" id="hsn_number">
                                        </div>
                                    </div> 
                                    <div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> Amount <span class="text-danger">*</span></label>
                                            <input type="text" data-id="0" id="totalAmount_0" class="totalAmount" placeholder="Total Amount" value="" name="amount[]" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
                                        </div>
                                    </div> 

                                    <div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> Quantity<span class="text-danger">*</span> </label>
                                            <input type="number" data-id="0" id="noofbox_0" name="quantity[]" class="noofbox" id="quantity" value="" placeholder="Quantity" required oninput="allowOnlyNumbers(this)">
                                        </div>
                                    </div>
                                    <div class="col-lg-1 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> </label>
                                            <button type="button" class=" new-height-btn-plus" id="add_more_product"> <i class="mdi mdi-plus"></i> </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
							<div class="row align-items-end mt-3"> 
								<div class="col-lg-4 col-sm-6 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> Invoice No </label>
										<input type="text" placeholder="Invoice No" name="invoice_no" id="invoice_no" value="{{ $order->invoice_no ?? '' }}">
									</div>
								</div> 
								<div class="col-lg-4 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> Invoice Amount <span class="text-danger">*</span></label>
										<input type="text" placeholder="Invoice Amount" value="{{ $order->invoice_amount ?? '' }}" id="invoice_amount" name="invoice_amount" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> E-Waybill No. </label>
										<input type="text" id="ewaybillno" placeholder="E-Waybill No." value="{{ $order->ewaybillno ?? '' }}" name="ewaybillno">
									</div>
								</div> 
							</div>
                        </div>
                    </div>
  
                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="main-vender">
                                <h5 class="new-title-b2c-order"> Package Details </h5>
                            </div>
                            <div class="row" >
								<div class="col-lg-3 col-sm-6 col-md-6">
                                    <div class="from-group my-2">
                                        <label for="packaging-type"> Volumetric Weight <span class="text-danger">*</span></label>
                                        <input type="text" id="volumetric_weight" placeholder="Volumetric Weight." value="0" name="volumetric_weight" readonly>
                                        <input type="hidden" id="total_weight" name="total_weight" value="0">
                                    </div>
                                </div>
								<div class="col-lg-3 col-sm-6 col-md-6" style="display:none;">
                                    <div class="from-group my-2">
                                        <label for="packaging-type"> Dimensions </label>
                                        <select class="form-control select2" id="dimension_type" name="dimension_type">
                                            <option value="cm">Centimeter</option> 
                                        </select>
                                    </div>
                                </div> 
                            </div>
                            <p class="my-2"></p>
                            <div id="dimenstionDetails">
								<div class="row"> 
									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Weight (KG) <span class="text-danger">*</span></label>
                                        <input type="text" name="weight" id="weight" class="weight" placeholder="Weight (KG)" value="{{ $order->weight ?? '' }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
										<label id="volumatric_weight" style="font-weight: 900;"></label>
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Length <span class="text-danger">*</span></label>
											<input type="text" name="length" id="length" placeholder="Length" value="{{ $order->length ?? '' }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Width <span class="text-danger">*</span></label>
											<input type="text" name="width" id="width" placeholder="Width" value="{{ $order->width ?? '' }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Height <span class="text-danger">*</span></label>
											<input type="text" name="height" id="height" placeholder="Height" value="{{ $order->height ?? '' }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>
								</div>
                            </div>
                        </div> 
                    </div> 
					
					<div class="main-rowx-1 text-right"> 
                        <button type="submit" class="new-submit-btn">{{ $isEditMode ? 'Update' : ($isCloneMode ? 'Clone Order' : 'Submit') }}</button>
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
	var weightOrder = @json(request('weight_order') ?? ($order->weight_order ?? 1)) || '';
	var formMode = @json($formMode);
	var orderData = @json($isPrefillMode ? $order : null);
	
	// let zipTimeout; 
	// $('#cust_zip_code').on('input', function () {
	// 	clearTimeout(zipTimeout); // Clear previous timeout

	// 	const zip_code = $(this).val().trim(); // Trim whitespace

	// 	if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
	// 		zipTimeout = setTimeout(() => {
	// 			$.ajax({
	// 				type: 'GET',
	// 				url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
	// 				success: function (response) {
	// 					if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
	// 						const { District: city, State: state, Country: country } = response[0].PostOffice[0];

	// 						$("#cust_city").val(city || '');
	// 						$("#cust_state").val(state || '');
	// 						$("#cust_country").val(country || '');
	// 					} else {
	// 						$("#cust_city").val('');
	// 						$("#cust_state").val('');
	// 						$("#cust_country").val('');
	// 						console.warn("Invalid or missing pincode data.");
	// 					}
	// 				},
	// 				error: function (xhr, status, error) { 
	// 					console.error("API Error:", error);
	// 				}
	// 			});
	// 		}, 500); // Debounce API request by 500ms
	// 	}
	// });

	$('#orderForm #order_type').on('input', function () {
		const orderType = $(this).val().toLowerCase(); // Ensure case consistency
	 
		$orderForm.find('#cod_amount').prop('readonly', false);

		if (orderType === "prepaid") {
			$orderForm.find('#cod_amount').val(0).prop('readonly', true);
		}
	});
 
	// Warehouse pickup location
	warehousePickupLocation()
	function warehousePickupLocation()
	{
		$.get("{{ route('order.warehouse.list') }}", function(res)
		{ 
			$orderForm.find('#warehouse_id').html(res.output);
			if (orderData && orderData.warehouse_id) {
				setTimeout(function () {
					$orderForm.find('#warehouse_id').val(String(orderData.warehouse_id)).trigger('change');
				}, 300);
			}
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
			<p>Address: ${data.address}, ${data.city}, ${data.state}, ${data.country}, ${data.zip_code} <span id="pincode_serviceability_status"></span></p>
		`;

		$('#warehouse_lable').html(addressLabel);

		// Check serviceability
		if (data.zip_code) {
			checkServiceability(data.zip_code, 'from', '#pincode_serviceability_status');
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
	
	function createWarehousePickup(obj, event)
	{
		event.preventDefault();
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal(); 
			$.get("{{ route('order.warehouse.create') }}", function(res)
			{ 
				$('body').find('#modal-view-render').html(res.view);
				$('#createWarehousePickupModal').modal('show');  
			});
		} 
	}
    
	// Customer/Destination Details
    customerDetailsList() 
    function customerDetailsList() 
	{ 
		$orderForm.find('#customer_id').prop('disabled', true);
		
		let $customerAddressElement = $orderForm.find('#customer_address_id'); 
		let $addAddressButton = $customerAddressElement.siblings('button');
		$addAddressButton.prop('disabled', true)
			.attr('data-original-title', 'Kindly select a customer before adding an address.') 
			.tooltip();  
		$customerAddressElement.html('<option>Select Customer Address</option>').prop('disabled', true);
		
        $.get("{{ route('order.customer.list') }}", function(res) {
            $orderForm.find('#customer_id').html(res.output);
			$orderForm.find('#customer_id').prop('disabled', false);
			if (orderData && orderData.customer_id) {
				setTimeout(function () {
					$orderForm.find('#customer_id').val(String(orderData.customer_id)).trigger('change');
					customerAddresList(null, orderData.customer_address_id || null);
				}, 300);
			}
        }, 'Json');
    }
	
	function customerAddresList(obj, isCustomerAddressId = null)
	{
		const customerId = $orderForm.find('#customer_id').val();  
		let $customerAddressElement = $orderForm.find('#customer_address_id');
		let $addAddressButton = $customerAddressElement.siblings('button');

		if (!customerId) { 
			$addAddressButton.prop('disabled', true)
				.attr('data-original-title', 'Kindly select a customer before adding an address.') 
				.tooltip(); 
				   
			$customerAddressElement.html('<option>Select Customer Address</option>')
				.prop('disabled', true);
			$('#customer_address_label').html('');
			return; 
		}
 
		$addAddressButton.prop('disabled', false).attr('data-original-title', '');
 
		$customerAddressElement.prop('disabled', true);
 
		$.ajax({
			url: `{{ url('order/customer/address-list') }}/${customerId}`,
			type: "GET",
			dataType: "json",
			success: function (res) {
				$customerAddressElement.html(res.output).prop('disabled', false);
				if(isCustomerAddressId)
				{
					setTimeout(function(){ 
						$('#orderForm #customer_address_id').val(isCustomerAddressId).trigger('change');
					}, 1000);
				}
			},
			error: function () {
				console.error("Error fetching customer addresses.");
				$customerAddressElement.html('<option>Error loading addresses</option>').prop('disabled', false);
			}
		});
	}

	function customerAddressChange(obj) {
		const $obj = $(obj);
		let addressData = $obj.find(':selected').attr('data-address');

		if (!addressData) {
			$('#customer_address_label').html('');
			return;
		}

		let data;
		try {
			data = JSON.parse(addressData);
		} catch (error) {
			console.error('Invalid JSON in data-address:', addressData);
			return;
		}

		let addressLabel = `
			<label>Delivery Details:</label> 
			<p>Address: ${data.address}, ${data.city}, ${data.state}, ${data.zip_code} <span id="delivery_serviceability_status"></span></p>
		`;

		$('#customer_address_label').html(addressLabel);

		// Check serviceability
		if (data.zip_code) {
			checkServiceability(data.zip_code, 'to', '#delivery_serviceability_status');
		}
	}
	
	function createCustomer(obj, event)
	{
		event.preventDefault();
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal(); 
			$.get("{{ route('order.customer.create') }}", function(res)
			{ 
				$('body').find('#modal-view-render').html(res.view);
				$('#createCustomerModal').modal('show');  
			});
		} 
	}
	
	function createCustomerAddress(obj, event)
	{
		event.preventDefault();
		const customerId = $orderForm.find('#customer_id').val();  
		if (!customerId) {
			toastrMsg('warning', 'recipient/customer selection required.');
			return;
		}
		
		if (!modalOpen)
		{
			modalOpen = true;
			closemodal(); 
			$.get(`{{ url('order/customer-address/create') }}/${customerId}`, function(res)
			{ 
				$('body').find('#modal-view-render').html(res.view);
				$('#createCustomerAddressModal').modal('show');  
			});
		} 
	}
	 
    var i = 1; 
    $(document).on('click', '#add_more_product', function()
	{ 
        var html = `<div class="row align-items-end mt-2 removeProductRows"> 
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Product Category <span class="text-danger">*</span></label>
					<input type="hidden" name="item_id[]" value="">
					<input type="text" placeholder="Product Category" name="product_category[]" id="product_category" required>
				</div>
			</div> 
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Product Name <span class="text-danger">*</span></label>
					<input type="text" placeholder="Product Name" name="product_name[]" id="product_name" required>
				</div>
			</div>
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> SKU Number</label>
					<input type="text" placeholder="SKU Number" name="sku_number[]" id="sku_number" >
				</div>
			</div>
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> HSN No</label>
					<input type="text" placeholder="HSN No" name="hsn_number[]" id="hsn_number" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" >
				</div>
			</div>
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Amount <span class="text-danger">*</span></label>
					<input type="text" data-id="${i}" id="totalAmount_${i}" class="totalAmount" placeholder="Total Amount" value="" name="amount[]" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div>
			</div>  
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type">Quantity <span class="text-danger">*</span></label>
					<input type="number" data-id="${i}" id="noofbox_${i}" name="quantity[]" class="noofbox" id="quantity" value="" placeholder="Quantity" oninput="allowOnlyNumbers(this)" required>
				</div>
			</div>
			<div class="col-lg-1 col-md-6">
				<div class="from-group my-2">
					<label for="packaging-type"> </label>
					<button type="button" class=" new-height-btn-plus-red" data-row-id="${i}" onclick="removeProductRow(this)"> <i class="mdi mdi-trash-can-outline"></i> </button> 
				</div>
			</div>
		</div>`;

        $orderForm.find(`.add_product_more`).append(html); 
        i++;
    });

    function removeProductRow(obj) 
	{ 
		const rowId = $(obj).attr('data-row-id');
        $(obj).closest('.removeProductRows').remove(); 
		$orderForm.find(`#removeDimension${rowId}`).remove();
		ewayBillRequired()
    }
	  
	var ewaybillReqAmount = 50000; // Convert to a number 
	
	function ewayBillRequired()
	{
		let totalAmount = 0;

		$('.totalAmount').each(function () {
			let index = $(this).data('id');
			let qty = parseInt($(`#noofbox_${index}`).val()) || 0;   // ✅ correct selector
			let value = parseFloat($(this).val()) || 0;
			totalAmount += (value * qty);
		}); 
		 
		let ewaybillInput = $orderForm.find('#ewaybillno'); // Use class instead of ID for closest
		$orderForm.find('#invoice_amount').val(totalAmount.toFixed(2));
		if (totalAmount >= ewaybillReqAmount) {
			ewaybillInput.prop('required', true);
		} else {
			ewaybillInput.prop('required', false);
		}
		
		let totalWeight = 0;
		$('.weight').each(function () {  
			let value = parseFloat($(this).val()) || 0;
			totalWeight += value;
		}); 
		$orderForm.find('#total_weight').val(totalWeight);
		calculateVolumetricWeight();
	}
	
	$(document).on('input', '.totalAmount', function()
	{ 
		ewayBillRequired()
	});

	function populateProductRows() {
		if (!orderData || !Array.isArray(orderData.order_items) || !orderData.order_items.length) {
			return;
		}

		let html = '';
		orderData.order_items.forEach(function (item, index) {
			let removeButton = index === 0
				? `<button type="button" class=" new-height-btn-plus" id="add_more_product"> <i class="mdi mdi-plus"></i> </button>`
				: `<button type="button" class=" new-height-btn-plus-red" data-row-id="${index}" onclick="removeProductRow(this)"> <i class="mdi mdi-trash-can-outline"></i> </button>`;

			html += `<div class="row align-items-end ${index > 0 ? 'mt-2' : ''} removeProductRows"> 
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> Product Category <span class="text-danger">*</span></label>
						<input type="hidden" name="item_id[]" value="${item.id ?? ''}">
						<input type="text" placeholder="Product Category" name="product_category[]" value="${item.product_category ?? ''}" required>
					</div>
				</div> 
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> Product Name <span class="text-danger">*</span></label>
						<input type="text" placeholder="Product Name" name="product_name[]" value="${item.product_name ?? ''}" required>
					</div>
				</div>
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> SKU Number</label>
						<input type="text" placeholder="SKU Number" name="sku_number[]" value="${item.sku_number ?? ''}">
					</div>
				</div>
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> HSN No</label>
						<input type="text" placeholder="HSN No" name="hsn_number[]" value="${item.hsn_number ?? ''}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));">
					</div>
				</div> 
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> Amount <span class="text-danger">*</span></label>
						<input type="text" data-id="${index}" id="totalAmount_${index}" class="totalAmount" placeholder="Total Amount" value="${item.amount ?? ''}" name="amount[]" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
					</div>
				</div> 
				<div class="col-xl col-lg-4 col-sm-6">
					<div class="from-group my-2">
						<label for="packaging-type"> Quantity<span class="text-danger">*</span> </label>
						<input type="number" data-id="${index}" id="noofbox_${index}" name="quantity[]" class="noofbox" value="${item.quantity ?? 1}" placeholder="Quantity" required oninput="allowOnlyNumbers(this)">
					</div>
				</div>
				<div class="col-lg-1 col-md-6">
					<div class="from-group my-2">
						<label for="packaging-type"> </label>
						${removeButton}
					</div>
				</div>
			</div>`;
		});

		$orderForm.find('.add_product_more').html(html);
		i = orderData.order_items.length;
		ewayBillRequired();
	}

	if (orderData) {
		populateProductRows();
		setTimeout(function () {
			ewayBillRequired();
			calculateVolumetricWeight();
			$orderForm.find('#order_type').trigger('input');
		}, 300);
	}
	
	$(document).on('input', '.weight', function()
	{ 
		ewayBillRequired()
	});
	
	$(document).on('input', '.noofbox', function()
	{ 
		ewayBillRequired()
	});
  
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

				toastrMsg(res.status, res.msg); // Show notification

				if (res.status === "success") {
					setTimeout(function () {
						window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
					}, 1000);
				}
			},
			error: function (xhr) {
				$orderForm.find(':submit').prop('disabled', false); // Re-enable button on failure
				console.error("AJAX error:", xhr.responseText);
				let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'Something went wrong. Please try again.';
				if (xhr.responseJSON?.errors) {
					const firstError = Object.values(xhr.responseJSON.errors)[0];
					if (Array.isArray(firstError) && firstError.length) {
						errorMsg = firstError[0];
					}
				}
				toastrMsg("error", errorMsg);
			}
		});
	});
	  
	function allowOnlyNumbers(input) {
		input.value = input.value.replace(/\D/g, '');
	}

	function calculateVolumetricWeight() {
		let l = parseFloat($('#length').val()) || 0;
		let w = parseFloat($('#width').val()) || 0;
		let h = parseFloat($('#height').val()) || 0;
		let volWt = (l * w * h) / 5000;
		let roundedVolWt = Math.round((volWt + Number.EPSILON) * 100) / 100;
		$('#volumetric_weight').val(roundedVolWt.toFixed(2));
	}
	$(document).on('input', '#length, #width, #height', function() {
		calculateVolumetricWeight();
	});

	let searchTimeout;
	$(document).on('keyup', '.select2-search__field', function() {
		let $searchField = $(this);
		let value = $searchField.val();
		
		// Check if input contains only digits
		if (/^\d+$/.test(value)) {
			// If only numbers, limit to 10 digits
			if (value.length > 10) {
				$searchField.val(value.substring(0, 10));
				return;
			}
			
			// If exactly 10 digits, trigger mobile search
			if (value.length === 10) {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function() {
					searchCustomerByMobile(value);
				}, 500); // Debounce for 500ms
			}
		}
		// If text (contains letters), no limit - do nothing
	});
	
	function searchCustomerByMobile(mobile) {
		$.ajax({
			url: "{{ route('order.customer.search-by-mobile') }}",
			type: "GET",
			data: { mobile: mobile },
			dataType: "json",
			success: function(res) {
				if (res.status === 'success' && res.customer) {
					// Customer found - add to dropdown list
					let customerId = res.customer.id;
					
					// Check if customer already exists in dropdown
					let $customerSelect = $('#customer_id');
					let optionExists = $customerSelect.find(`option[value="${customerId}"]`).length > 0;
					
					if (!optionExists) {
						// Add customer to dropdown if not exists (but don't auto-select)
						let newOption = new Option(
							res.customer.name + ' - (' + res.customer.mobile + ')',
							customerId,
							false,
							false
						);
						$customerSelect.append(newOption);
					}
					
					// Close and reopen Select2 dropdown to refresh the results immediately
					$customerSelect.select2('close');
					setTimeout(function() {
						$customerSelect.select2('open');
						// Set the search term again to filter the results
						$('.select2-search__field').val(mobile).trigger('input');
					}, 100);
					
					// Show info message if customer belongs to another user
					if (!res.customer.is_own) {
						// toastrMsg('info', 'Customer found in database - now available in list');
					} else {
						// toastrMsg('success', 'Customer found - now available in list');
					}
				} else if (res.status === 'not_found') {
					// toastrMsg('info', 'No customer found with this mobile number');
				}
			},
			error: function(xhr) {
				console.error("Error searching customer:", xhr);
			}
		});
	}

	$(document).on('input', '#weight', function() {
		let weight = $(this).val();
		if (!weight || isNaN(weight)) return;

		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(function() {
			$.ajax({
				url: "{{ route('weight-dimension.get-by-slab') }}",
				type: "GET",
				data: { slab: weight },
				dataType: "json",
				success: function(res) {
					if (res.status === 'success' && res.data) {
						$('#length').val(res.data.length);
						$('#width').val(res.data.width);
						$('#height').val(res.data.height);
						calculateVolumetricWeight();
						toastrMsg('info', 'Dimensions auto-filled for ' + weight + ' KG');
					}
				}
			});
		}, 500);
	});

</script>
@endpush

