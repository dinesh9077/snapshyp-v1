@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Create Warehouse')
@section('header_title', 'Create Warehouse')
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
            <form id="warehouseForm" method="post" class="customer_form" action="{{ route('warehouse.store') }}"
                enctype="multipart/form-data">
                <div class="container-fluid">
                    <div class="main-rowx-1">
                        <div class="main-row main-data-teble-1">
                            <div class="main-rowx-1">
                                <div class="main-order-001">
                                    <div class="address_block">
                                        <div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
                                            <h4 class="mb-0">
                                                <small style="font-size : 14px; color : #727272; font-weight : 500;">
                                                    Note: If Delhivery API is activated, the address will be sent to the
                                                    warehouse API to add it to the Delhivery portal.
                                                </small>
                                            </h4>
                                        </div>
                                        <div class="row align-items-end mt-3">
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Warehouse Name <span
                                                            class="text-danger">*</span></label>
                                                    <input class="default" type="text" data-id="0" autocomplete="off"
                                                        maxlength="18" name="warehouse_name" id="warehouse_name"
                                                        placeholder="Warehouse Name" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Company Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="company_name"
                                                        id="company_name" maxlength="18" placeholder="Company Name"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Contact Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="contact_name"
                                                        id="contact_name" placeholder="Contact Name" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Contact Email <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="contact_email"
                                                        id="contact_email" placeholder="Contact Name" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="first-name"> Contact Number <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="contact_number"
                                                        id="contact_number" placeholder="Contact Number" maxlength="10"
                                                        pattern="\d{10}" title="Please enter exactly 10 digits" required>
                                                </div>
                                            </div>

                                            <div class="col-xl-9">
                                                <div class="from-group my-2">
                                                    <label for="username"> Address <span
                                                            class="text-danger">*</span></label>
                                                    <textarea class="default" data-id="0" autocomplete="off" name="address" id="address" placeholder="Address"
                                                        required></textarea>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                     <label for="first-name"> Zip code <span
                                                             class="text-danger">*</span> <span id="pincode_serviceability_status"></span></label>
                                                    <input class="default" type="text" data-id="0" autocomplete="off"
                                                        name="zip_code" id="zip_code" placeholder="Zip code"
                                                        onkeyup="autoFetchCountry(this)" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> City <span class="text-danger">*</span></label>
                                                    <input class="default" type="text" data-id="0" autocomplete="off"
                                                        name="city" id="city" placeholder="City" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> State <span
                                                            class="text-danger">*</span></label>
                                                    <input class="default" type="text" data-id="0"
                                                        autocomplete="off" name="state" id="state"
                                                        placeholder="State" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Country <span
                                                            class="text-danger">*</span></label>
                                                    <input class="default" type="text" data-id="0"
                                                        autocomplete="off" name="country" id="country"
                                                        placeholder="Country" required>
                                                </div>
                                            </div>

                                        </div>
                                        {{-- <div class="row align-items-end my-2">
                                            <div class="col-12">
                                                <div class="form-group my-1">
                                                    <input type="checkbox" name="label_options[]"
                                                        id="hide_contact_on_label" value="hide_contact">
                                                    <label for="hide_contact_on_label"
                                                        style="font-weight: 500;color: black;font-size: 14px; margin-left : 5px;">Hide
                                                        End Customer Contact Number in Label</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group my-1">
                                                    <input type="checkbox" name="label_options[]"
                                                        id="hide_address_on_label" value="hide_address">
                                                    <label for="hide_address_on_label"
                                                        style="font-weight: 500;color: black;font-size: 14px; margin-left : 5px;">Hide
                                                        Warehouse Address in Label</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group my-1">
                                                    <input type="checkbox" name="label_options[]"
                                                        id="hide_mobile_on_label" value="hide_mobile">
                                                    <label for="hide_mobile_on_label"
                                                        style="font-weight: 500;color: black;font-size: 14px; margin-left : 5px;">Hide
                                                        Warehouse Mobile Number in Label</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group my-1">
                                                    <input type="checkbox" name="label_options[]"
                                                        id="hide_product_on_label" value="hide_product">
                                                    <label for="hide_product_on_label"
                                                        style="font-weight: 500;color: black;font-size: 14px; margin-left : 5px;">Hide
                                                        Product Details in Label</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group my-1">
                                                    <input type="checkbox" name="label_options[]"
                                                        id="hide_weight_on_label" value="hide_weight">
                                                    <label for="hide_weight_on_label"
                                                        style="font-weight: 500;color: black;font-size: 14px; margin-left : 5px;">Hide
                                                        Weight in Label</label>
                                                </div>
                                            </div>
                                        </div> --}}
                                        <div class="row">
                                            <div class="col-xl-3 col-md-4 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="first-name"> Status </label>
                                                    <select autocomplete="off" name="warehouse_status"
                                                        id="warehouse_status">
                                                        <option value="1"> Active </option>
                                                        <option value="0"> In-Active </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-right">

                                            <button class="new-submit-btn" type="submit" id="customer_submit"> Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script>
         $(document).ready(function() {
            const $warehouseForm = $("#warehouseForm");

            // Enable button initially
            $warehouseForm.find('input[type="submit"]').prop("disabled", false);

            $warehouseForm.on("submit", function(event) {
                event.preventDefault();
                let $submitButton = $warehouseForm.find('button[type="submit"]');
                let originalText = $submitButton.html();
                $submitButton.html('<i class="fa fa-spinner fa-spin"></i> Process...').prop("disabled",
                    true); // Disable button to prevent multiple clicks

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
                    success: function(res) {
                        $submitButton.html(originalText).prop("disabled",
                            false); // Re-enable button
                        if (res.status === "success") {
                            toastrMsg(res.status, res.msg);
                            $warehouseForm[0].reset();
                            setTimeout(() => {
                                window.location.href = "{{ route('warehouse.index') }}";
                            }, 1000); // Redirect after 1 second
                        } else if (res.status == "info") {
                            toastrMsg(res.status, res.msg);
                        } else {
                            toastrMsg(res.status, res.msg);
                        }
                    },
                    error: function(xhr) {
                        toastrMsg("error", "Something went wrong. Please try again.");
                        $submitButton.html(originalText).prop("disabled",
                            false); // Re-enable button
                    }
                });
            });
        });

        let zipTimeout;

        function autoFetchCountry(obj) {
            clearTimeout(zipTimeout); // Clear previous timeout

            const zip_code = $(obj).val().trim();

            if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
                // Check serviceability
                checkServiceability(zip_code, 'from', '#pincode_serviceability_status');

                zipTimeout = setTimeout(() => {
                    $.ajax({
                        type: 'GET',
                        url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
                        success: function(response) {
                            if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
                                const {
                                    District: city,
                                    State: state,
                                    Country: country
                                } = response[0].PostOffice[0];

                                $(`#city`).val(city);
                                $(`#state`).val(state);
                                $(`#country`).val(country);
                            } else {
                                console.warn("Invalid or missing pincode data.");
                            }
                        },
                        error: function(xhr, status, error) {
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
@endpush
