<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopify Shipping Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .new-submit-btn {
            padding: 9.5px 20px;
            background-color: #000000;
            border: none;
            outline: none;
            color: white;
            border-radius: 0.375rem;
        }

        .btn-back {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background-color: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
        }

        .btn-orders {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            transition: all 0.2s ease;
        }

        .btn-orders:hover {
            background-color: #1e40af;
            color: #ffffff;
            text-decoration: none;
        }
    </style>
</head>

<body class="bg-light">

    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="card shadow-sm w-100" style="max-width: 600px;">

            <!-- Header -->
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="back-button" class="btn-back">← Back</a>
                    <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}" alt="App Logo"
                        height="24">
                </div>
            </div>

            <!-- Body -->
            <div class="card-body">
                <h5 class="card-title text-center mb-4">Shipping Details</h5>
                <div id="form-messages"></div>
                <form id="shipmentBulkForm" action="{{ route('shopify.bulk-shipment.now') }}" method="post"
                    enctype="multipart/form-data">
                    {{-- <div class="row mb-3">
                        <div class="col">
                            <label for="weight" class="form-label">Weight (KG)</label>
                            <input type="text" class="form-control" name="weight"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="weight" placeholder="Weight (KG)" value="0" required>
                            <small class="text-danger error-msg" id="weightError"></small>
                        </div>
                        <div class="col">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" name="height"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="height" placeholder="Height (cm)" value="0" required>
                            <small class="text-danger error-msg" id="heightError"></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label for="length" class="form-label">Length (cm)</label>
                            <input type="number" class="form-control" name="length"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="length" placeholder="Length (cm)" value="0" require>
                            <small class="text-danger error-msg" id="lengthError"></small>
                        </div>
                        <div class="col">
                            <label for="width" class="form-label">Width (cm)</label>
                            <input type="number" class="form-control" name="width"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="width" placeholder="Width (cm)" value="0" required>
                            <small class="text-danger error-msg" id="widthError"></small>
                        </div>
                    </div> --}}

                    <div class="mb-3">
                        <label for="warehouse" class="form-label">Courier Warehouse</label>
                        <select class="form-control" id="warehouse_id" name="warehouse_id">
                            <option value="">Select Warehouse</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }} -
                                    ({{ $warehouse->company_name }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger error-msg" id="warehouseError"></small>
                    </div>

                    <div class="mb-3">
                        <label for="courier" class="form-label">Shipping Mode</label>
                        <select class="form-control" id="shipping_mode" name="shipping_mode">
                            <option value="Surface"> By Surface </option>
                            <option value="Air"> By Air </option>
                        </select>
                        <small class="text-danger error-msg" id="shippingModeError"></small>
                    </div>

                    <div class="mb-3">
                        <label for="courier" class="form-label">Courier</label>
                        <select class="form-control" id="courier_shipment_id" name="courier_shipment_id">
                            <option value="">Select Courier</option>
                            @foreach ($shipments as $shipment)
                                <option value="{{ $shipment->id }}">{{ $shipment->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-danger error-msg" id="courierError"></small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="new-submit-btn mt-2">
                            <span class="btn-text">Submit</span>
                            <span class="btn-loader d-none">
                                <i class="mdi mdi-loading mdi-spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @include('embedded.partials.session-token')
    <script>
        const $shipmentBulkForm = $("#shipmentBulkForm");
        const orderIds = @json($orderIds);
        const userId = @json($userId);
        const loginUrl = "{{ url('login') }}";
        const companyName = "{{ config('setting.company_name') }}";

        // Handle back button click
        $(document).ready(function() {
            $('#back-button').on('click', function(e) {
                e.preventDefault();
                const urlParams = new URLSearchParams(window.location.search);
                const shop = urlParams.get('shop');
                const host = urlParams.get('host');

                if (shop) {
                    let backUrl = "{{ route('shopify.orders.list.page') }}?shop=" + encodeURIComponent(
                        shop);
                    if (host) {
                        backUrl += "&host=" + encodeURIComponent(host);
                    }
                    window.location.href = backUrl;
                } else {
                    window.history.back();
                }
            });
        });

        function startLoading($btn, text = 'Processing...') {
            $btn.prop('disabled', true);
            $btn.find('.btn-text').addClass('d-none');
            $btn.find('.btn-loader').removeClass('d-none')
                .find('span').text(text);
        }

        function stopLoading($btn) {
            $btn.prop('disabled', false);
            $btn.find('.btn-loader').addClass('d-none');
            $btn.find('.btn-text').removeClass('d-none');
        }

        function clearErrors() {
            $('.error-msg').text('');
        }

        function validateDimensions() {
            clearErrors();
            let valid = true;

            // const length = parseFloat($('#length').val());
            // const width = parseFloat($('#width').val());
            // const height = parseFloat($('#height').val());
            // const weight = parseFloat($('#weight').val());

            const warehouse_id = $('#warehouse_id').val();
            const shipping_mode = $('#shipping_mode').val();
            const courier_shipment_id = $('#courier_shipment_id').val();

            // // Dimensions validation
            // if (!length || length <= 0) {
            //     $('#lengthError').text('Length must be greater than 0');
            //     valid = false;
            // }

            // if (!width || width <= 0) {
            //     $('#widthError').text('Width must be greater than 0');
            //     valid = false;
            // }

            // if (!height || height <= 0) {
            //     $('#heightError').text('Height must be greater than 0');
            //     valid = false;
            // }

            // // Weight validation
            // if (isNaN(weight) || weight < 0.5) {
            //     $('#weightError').text('Weight must be at least 0.500 kg');
            //     valid = false;
            // }

            // Warehouse validation
            if (!warehouse_id) {
                $('#warehouseError').text('Please select a warehouse');
                valid = false;
            }

            // Shipping mode validation
            if (!shipping_mode) {
                $('#shippingModeError').text('Please select shipping mode');
                valid = false;
            }

            // Courier validation
            if (!courier_shipment_id) {
                $('#courierError').text('Please select a courier');
                valid = false;
            }

            return valid;
        }

        $shipmentBulkForm.on("submit", function(event) {

            if (!validateDimensions()) {
                event.preventDefault();
                return false;
            }
            event.preventDefault();

            // Collect order IDs
            let selectedOrderIds = [];
            $.each(orderIds, function(_, orderId) {
                selectedOrderIds.push(orderId);
            });

            if (!selectedOrderIds.length) {
                alert("No orders selected.");
                return;
            }

            let $submitButton = $shipmentBulkForm.find('[type="submit"]');
            startLoading($submitButton, 'Uploading...');

            // Get shop and host from URL
            const urlParams = new URLSearchParams(window.location.search);
            const shop = urlParams.get('shop');
            const host = urlParams.get('host');

            // Build URL with shop and host parameters
            let submitUrl = $shipmentBulkForm.attr("action");
            if (shop) {
                submitUrl += "?shop=" + encodeURIComponent(shop);
                if (host) {
                    submitUrl += "&host=" + encodeURIComponent(host);
                }
            }

            let formData = new FormData(this);
            formData.append("order_ids", selectedOrderIds);
            formData.append("weight_order", 1);
            formData.append("user_id", userId);
            formData.append("_token", "{{ csrf_token() }}");

            $.ajax({
                url: submitUrl,
                type: $shipmentBulkForm.attr("method"),
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(res) {
                    stopLoading($submitButton);

                    if (res.status === "success") {
                        const urlParams = new URLSearchParams(window.location.search);
                        const shop = urlParams.get('shop');
                        const host = urlParams.get('host');
                        let ordersListUrl = "{{ route('shopify.orders.list.page') }}?shop=" +
                            encodeURIComponent(shop);
                        if (host) {
                            ordersListUrl += "&host=" + encodeURIComponent(host);
                        }

                        let resultHtml = `
                        <div class="text-center py-5">
                            <h4 class="text-success mb-3">✅ Bulk Shipment Completed</h4>
                            <p class="fw-bold">${res.msg}</p>
                            <p class="text-muted">Check executed orders in <a href="${loginUrl}" target="_blank">${companyName} Panel</a>.</p>
                            <a href="${ordersListUrl}" class="btn-orders">📋 Back to Orders List</a>
                        </div>
                    `;
                        $("#shipmentBulkForm").closest(".card-body").html(resultHtml);

                    } else {
                        let errorHtml = `
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <strong>❌ Error!</strong> ${res.msg || "Something went wrong."}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                        $("#form-messages").html(errorHtml);
                    }
                },
                error: function(xhr) {
                    stopLoading($submitButton);
                    let msg = xhr.responseJSON?.message || "Something went wrong. Please try again.";

                    let errorHtml = `
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <strong>❌ Error!</strong> ${msg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                    $("#form-messages").html(errorHtml);
                }

            });
        });
    </script>
</body>

</html>
