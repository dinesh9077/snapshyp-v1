@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Create Shipments')
@section('header_title', 'Create Shipments')
@section('content')
<style>
    button.d-002 {
        padding: 6px 15px;
        border-radius: 4px 4px 4px 4px !important;
        font-size: 20px;
        font-weight: 700;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 30px !important;
        font-size: 14px !important;
        padding: 7px 15px;
    }

    @media(max-width: 1600px) {
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1 !important;
            padding: 11px 15px !important;
            font-size: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 35px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 22px !important;
            font-size: 12px !important;
             padding: 8px 10px !important;
        }
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
            <form id="orderForm" method="post" action="{{ route('order.store') }}" enctype="multipart/form-data">
                <div class="main-create-order"> 
                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="row align-items-end">
                                <div class="col-xl-3 col-lg-4 col-sm-6">
                                    <div class="from-group my-2">
                                        <label for="order-id"> Order ID <span class="text-danger">*</span></label>
                                        <input type="text" placeholder="Order Id" readonly name="order_prefix" value="{{ \App\Models\Order::generateOrderNumber($user->id) }}">
                                    </div>
                                </div> 
								<input type="hidden" name="shipping_mode" value="Surface">
								<input type="hidden" name="weight_order" value="{{ request('weight_order') }}">
                                <div class="col-xl-3 col-lg-4 col-sm-6 ">
                                    <div class="from-group my-2">
                                        <label for="packaging-type"> Date <span class="text-danger">*</span></label>
                                        <input type="text" name="order_date" class="datepicker" autocomplete="off" placeholder="Date" value="{{ date('Y-m-d') }}" id="order_date" required>
                                    </div>
                                </div>
								
								<!--<div class="col-lg-3 col-sm-6 ">
                                    <div class="from-group my-2">
                                        <label for="order-type"> Freight (paid/to pay) </label>
                                        <select name="freight_mode" id="freight_mode" required>
                                            <option value="FOP">Paid</option>
                                            <option value="FOD">To Pay </option>
                                        </select>
                                    </div>
                                </div>-->
                                <div class="col-xl-3 col-lg-4 col-sm-6 mt-2">
									<div class="from-group my-2">
										<label for="insurance-type"> Insurance Type <span class="text-danger">*</span></label>
										<select name="insurance_type" id="insurance_type" required>
											<option value="1">Owner Risk</option>
											<option value="2">Carrier Risk </option>
										</select>
									</div>
								</div>
								<div class="col-xl-3 col-lg-4 col-sm-6 mt-2">
                                    <div class="from-group my-2">
                                        <label for="order-type"> Order Type <span class="text-danger">*</span></label>
                                        <select name="order_type" id="order_type" required>
                                            <option value="cod">Cash on Delivery</option>
                                            <option value="prepaid">Prepaid</option>
                                        </select>
                                    </div>
                                </div>
								<div class="col-xl-3 col-lg-4 col-sm-6 mt-2">
                                    <div class="from-group my-2">
                                        <label for="order-id"> Amount To Collect</label>
                                        <input type="text" placeholder="Amount To Collect" id="cod_amount" name="cod_amount" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
                                    </div>
                                </div> 
								 
								<div class="col-xl-3 col-lg-4 col-sm-6 mt-2 ml-3" >
									<div class="form-group my-2">
										<!-- <label class="form-label d-block">.</label> -->
										<div class="d-flex align-items-center">
											<input type="checkbox" id="is_fragile_item" name="is_fragile_item" value="1" class="form-check-input me-2 mt-0">
											<label for="is_fragile_item" class="form-check-label mb-0" style="font-weight: 500;">My Package Contains Fragile Items</label>
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
										<h5> Pickup Location <span class="text-danger">*</span></h5>

											<div class="main-rox-input">
												<select name="warehouse_id" class="control-form select2" id="warehouse_id" style="border-radius: 5px 0px 0px 5px" onchange="warehousePickup(this)" required>
													<option value="">Select Pickup Location</option> 
												</select>
												<button type="button" class="new-height-btn-plus" onclick="createWarehousePickup(this, event)"> <i class="mdi mdi-plus"></i></button>
											</div>
										
										
										</div> 
										<div class="col-md-6">
											<div class="new-border-details">
												<div id="warehouse_lable">  
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
											<label for="order-id"> Recipeint/Customer </label>
											<div class="main-rox-input">
												<select name="customer_id" class="control-form select2" id="customer_id" style="border-radius: 5px 0px 0px 5px" onchange="customerAddresList(this)" required>
													<option value="">Select Recipeint/Customer</option>
												</select>
												<button type="button" class="new-height-btn-plus" onclick="createCustomer(this, event)"> <i class="mdi mdi-plus"></i> </button>
											</div>
										</div> 
										<div class="from-group col-md-6">
											<label for="order-id"> Customer Address </label>
											<div class="main-rox-input">
												<select name="customer_address_id" class="control-form select2" id="customer_address_id" style="border-radius: 5px 0px 0px 5px" required>
													<option>Select Customer Address</option>
												</select>
												<button type="button" class="new-height-btn-plus" disabled data-toggle="tooltip" data-placement="right" title="Kindly select a customer before adding an address." onclick="createCustomerAddress(this, event)"> <i class="mdi mdi-plus"></i> </button>
											</div>
											<div id="warehouse_lable"></div> 
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
                                            <label for="packaging-type"> SKU Number <span class="text-danger">*</span></label>
                                            <input type="text" placeholder="SKU Number" name="sku_number[]" id="sku_number" required>
                                        </div>
                                    </div>
									<div class="col-xl col-lg-4 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> HSN No <span class="text-danger">*</span></label>
                                            <input type="text" placeholder="HSN No" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" name="hsn_number[]" id="hsn_number" required>
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
                                            <label for="packaging-type"> Quantity <span class="text-danger">*</span></label>
                                            <input type="number" data-id="0" id="quantity_0" name="quantity[]" class="quantity" value="" placeholder="Quantity" required oninput="allowOnlyNumbers(this)">
                                        </div>
                                    </div>
                                    <div class="col-lg-1 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="packaging-type"> </label>
                                            <button type="button" class="new-height-btn-plus" id="add_more_product"> <i class="mdi mdi-plus"></i> </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
							<div class="row align-items-end mt-3"> 
								<div class="col-lg-4 col-sm-6 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> Invoice No </label>
										<input type="text" placeholder="Invoice No" name="invoice_no" id="invoice_no">
									</div>
								</div> 
								<div class="col-lg-4 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> Invoice Amount <span class="text-danger">*</span></label>
										<input type="text" placeholder="Invoice Amount" value="" id="invoice_amount" name="invoice_amount" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="from-group my-2">
										<label for="packaging-type"> E-Waybill No. </label>
										<input type="text" id="ewaybillno" placeholder="E-Waybill No." value="" name="ewaybillno">
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
                                        <label for="packaging-type"> Total Weight <span class="text-danger">*</span></label>
                                        <input type="text" id="total_weight" placeholder="Total Weight." value="0" name="total_weight" readonly>
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
											<label for="packaging-type"> No Of Box <span class="text-danger">*</span></label>
											<input type="text" data-id="0" name="no_of_box[]" id="no_of_box_0" class="no_of_box" placeholder="No Of Box" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div> 
									</div>
									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Weight Per Box(KG) <span class="text-danger">*</span></label>
											<input type="text" data-id="0" name="weight[]" id="weight_0" class="weight" placeholder="Weight (KG)" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div> 
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Length <span class="text-danger">*</span></label>
											<input type="text" name="length[]" id="length" placeholder="Length" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Width <span class="text-danger">*</span></label>
											<input type="text" name="width[]" id="width" placeholder="Width" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>

									<div class="col-xl col-lg-4 col-sm-6">
										<div class="from-group my-2">
											<label for="packaging-type"> Height <span class="text-danger">*</span></label>
											<input type="text" name="height[]" id="height" placeholder="Height" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
										</div>
									</div>
								</div>
                            </div>
                        </div> 
                    </div> 
					
					<div class="main-rowx-1 text-right">
                        <!--<div class="main-order-001">
                            <div class="main-vender">
                                <h5> Upload Documents </h5>
                            </div>
                            <div class="row"> 
                                <div class="col-lg-6 col-sm-6 col-md-6">
                                    <div class="from-group my-2">
                                        <label for="packaging-type"> Invoice Documents </label>
                                        <input type="file" class="form-control" id="invoice_document" name="invoice_document[]" multiple> 
                                    </div>
                                </div> 
								<div class="col-lg-6 col-sm-6 col-md-6">
                                    <div class="from-group my-2">
                                        <label for="packaging-type">Other Documents </label>
                                        <input type="file" class="form-control" id="order_image" name="order_image[]" multiple > 
                                    </div>
                                </div> 
                            </div> 
                        </div>-->
                        <button type="submit" class="new-submit-btn">Submit</button>
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
	var weightOrder = @json(request('weight_order')) || '';
	
	$('#orderForm #order_type').on('input', function () {
		const orderType = $(this).val().toLowerCase(); // Ensure case consistency
	 
		$orderForm.find('#cod_amount').prop('readonly', false);

		if (orderType === "prepaid") {
			$orderForm.find('#cod_amount').val(0).prop('readonly', true);
		}
	});
	
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

	// Warehouse pickup location
	warehousePickupLocation()
	function warehousePickupLocation()
	{
		$.get("{{ route('order.warehouse.list') }}", function(res)
		{ 
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
    $(`#add_more_product`).click(function()
	{ 
        var html = `<div class="row align-items-end mt-2 removeProductRows"> 
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Product Category <span class="text-danger">*</span></label>
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
					<label for="packaging-type"> SKU Number <span class="text-danger">*</span></label>
					<input type="text" placeholder="SKU Number" name="sku_number[]" id="sku_number" required>
				</div>
			</div>
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> HSN No <span class="text-danger">*</span></label>
					<input type="text" placeholder="HSN No" name="hsn_number[]" id="hsn_number" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
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
					<label for="packaging-type"> Quantity <span class="text-danger">*</span></label>
					<input type="number" data-id="${i}" id="quantity_${i}" name="quantity[]" class="quantity" id="quantity" value="" placeholder="Quantity" oninput="allowOnlyNumbers(this)" required>
				</div>
			</div>
			<div class="col-lg-1 col-md-6">
				<div class="from-group my-2">
					<label for="packaging-type"> </label>
					<button type="button" class=" new-height-btn-plus" data-row-id="${i}" onclick="removeProductRow(this)"> <i class="mdi mdi-trash-can-outline"></i> </button> 
				</div>
			</div>
		</div>`;

        $orderForm.find(`.add_product_more`).append(html);
		noOfBoxDimenstion(i);
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
			let qty = parseInt($(`#quantity_${index}`).val()) || 0;   // ✅ correct selector
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
			let index = $(this).data('id'); 
			let no_of_box = parseInt($(`#no_of_box_${index}`).val()) || 0; 
			let value = parseFloat($(this).val()) || 0;
			totalWeight += (value * no_of_box); 
		}); 
		
		$orderForm.find('#total_weight').val(totalWeight);
	}
	
	$(document).on('input', '.totalAmount', function()
	{ 
		ewayBillRequired()
	});
	
	$(document).on('input', '.weight', function()
	{ 
		ewayBillRequired()
	});
	$(document).on('input', '.no_of_box', function()
	{ 
		ewayBillRequired()
	});
	
	$(document).on('input', '.quantity', function()
	{ 
		ewayBillRequired()
	});
 
	function noOfBoxDimenstion(rowId)
	{ 
		let html = `<div class="row mt-2" id="removeDimension${rowId}">
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> No Of Box <span class="text-danger">*</span></label>
					<input type="text" data-id="${rowId}" name="no_of_box[]" id="no_of_box_${rowId}" class="no_of_box" placeholder="No Of Box" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div> 
			</div>
			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Weight Per Box(KG) <span class="text-danger">*</span></label>
					<input type="text" data-id="${rowId}" name="weight[]" id="weight_${rowId}" class="weight" placeholder="Weight (KG)" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div> 
			</div>

			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Length <span class="text-danger">*</span></label>
					<input type="text" name="length[]" id="length" placeholder="Length" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div>
			</div>

			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Width <span class="text-danger">*</span></label>
					<input type="text" name="width[]" id="width" placeholder="Width" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div>
			</div>

			<div class="col-xl col-lg-4 col-sm-6">
				<div class="from-group my-2">
					<label for="packaging-type"> Height <span class="text-danger">*</span></label>
					<input type="text" name="height[]" id="height" placeholder="Height" value="" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
				</div>
			</div>
		</div>`;
 
		$orderForm.find(`#dimenstionDetails`).append(html);
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
				toastrMsg("error", "Something went wrong. Please try again.");
			}
		});
	});
	  
	function allowOnlyNumbers(input) {
		input.value = input.value.replace(/\D/g, '');
	}

	// Limit Select2 search input: 10 digits for numbers, unlimited for text
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

</script>
@endpush

