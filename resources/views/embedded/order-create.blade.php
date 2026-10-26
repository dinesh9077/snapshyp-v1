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

        /* Live bulk-ship progress (same states as the web panel) */
        .bp-stats { display: flex; flex-wrap: wrap; gap: 6px; }
        .bp-stat { border: 1px solid #e3e7ef; background: #f7f9fc; border-radius: 999px; padding: 3px 10px; font-size: 13px; font-weight: 600; color: #4a5763; }
        .bp-list { border: 1px solid #e3e7ef; border-radius: 8px; max-height: 300px; overflow-y: auto; text-align: left; }
        .bp-row { display: flex; gap: 10px; padding: 7px 12px; border-bottom: 1px solid #f0f2f6; font-size: 13px; }
        .bp-row:last-child { border-bottom: 0; }
        .bp-row .bp-num { font-weight: 600; min-width: 64px; }
        .bp-row .bp-msg { color: #4a5763; flex: 1; overflow-wrap: anywhere; }
        .bp-row.st-success .bp-ico { color: #1f9d55; }
        .bp-row.st-failed .bp-ico, .bp-row.st-failed .bp-msg { color: #d64545; }
        .bp-row.st-skipped { opacity: .7; }
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
                                <option value="{{ $shipment->id }}" data-name="{{ $shipment->name }}">{{ $shipment->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1" id="courierQuoteNote"></small>
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
        const quoteUrl = "{{ route('shopify.bulk-shipment.quote') }}";
        const statusBase = "{{ url('shopify/bulk-shipment/status') }}";
        const retryBase = "{{ url('shopify/bulk-shipment/retry') }}";

        // shop / host must ride along on every call (ShopifyEmbeddedAuth needs them)
        function withShop(url, extra = {}) {
            const params = new URLSearchParams(window.location.search);
            const query = new URLSearchParams(extra);
            ['shop', 'host'].forEach(k => { if (params.get(k)) query.set(k, params.get(k)); });
            return url + (url.includes('?') ? '&' : '?') + query.toString();
        }

        function ordersListUrl() {
            return withShop("{{ route('shopify.orders.list.page') }}");
        }

        const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
        const money = n => '₹' + Number(n || 0).toFixed(2);

        // ---- Courier price, like Ship Now on the web panel ------------------------
        let quoteRequestNo = 0;

        function loadQuotes() {
            const requestNo = ++quoteRequestNo; // a slower, older answer must not overwrite a newer one
            const warehouseId = $('#warehouse_id').val();
            const $options = $('#courier_shipment_id option[value!=""]');
            $options.each(function() { $(this).text($(this).data('name')); });
            $('#courierQuoteNote').text('');
            if (!warehouseId) return;

            $('#courierQuoteNote').text('Checking shipping charges…');
            $.ajax({
                url: withShop(quoteUrl, { order_ids: orderIds.join(','), warehouse_id: warehouseId }),
                dataType: 'json',
            }).done(function(res) {
                if (requestNo !== quoteRequestNo) return;
                const notes = [];
                (res.couriers || []).forEach(function(c) {
                    const $opt = $('#courier_shipment_id option[value="' + c.id + '"]');
                    if (!c.priced) {
                        $opt.text(c.name + ' — no rate');
                    } else if (c.orders === 1) {
                        $opt.text(c.name + ' — ' + money(c.total));
                    } else {
                        $opt.text(c.name + ' — ' + money(c.total) + ' for ' + c.priced + ' of ' + c.orders + ' orders');
                    }
                    if (c.missing.length) {
                        notes.push(c.name + ': no rate for ' + c.missing.slice(0, 5).join(', ') + (c.missing.length > 5 ? '…' : '') +
                            ' (check weight, pincode serviceability and rate card)');
                    }
                });
                $('#courierQuoteNote').text(notes.join(' · ') || 'Charges include GST. Final amount is confirmed at booking.');
            }).fail(function() {
                if (requestNo !== quoteRequestNo) return;
                $('#courierQuoteNote').text('Could not load shipping charges.');
            });
        }

        $(function() {
            const $warehouse = $('#warehouse_id');
            if (!$warehouse.val() && $warehouse.find('option[value!=""]').length === 1) {
                $warehouse.val($warehouse.find('option[value!=""]').val());
            }
            $warehouse.on('change', loadQuotes);
            loadQuotes();
        });

        // ---- Live progress after submit, same states as the web panel -------------
        const bp = { id: null, timer: null, errors: 0, startedAt: 0 };

        function showProgress(batchId) {
            clearTimeout(bp.timer);
            Object.assign(bp, { id: batchId, errors: 0, startedAt: Date.now() });
            $('#shipmentBulkForm').closest('.card-body').html(`
                <h5 class="card-title text-center mb-1" id="bpTitle">Shipping orders…</h5>
                <p class="text-center text-muted small mb-3" id="bpSubtitle">Waiting for the queue to pick up your orders…</p>
                <div class="d-flex justify-content-between small mb-1"><span id="bpCounts"></span><b id="bpFraction">0 / 0</b></div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-success" id="bpBarOk" style="width: 0%"></div>
                    <div class="progress-bar bg-danger" id="bpBarFail" style="width: 0%"></div>
                </div>
                <div class="alert alert-warning small py-2 d-none" id="bpStalled">
                    No order has started yet. Your orders stay queued and are booked as soon as the queue runs.
                </div>
                <div class="bp-list mb-3" id="bpList"><div class="text-muted small text-center py-3">Loading…</div></div>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button type="button" class="btn btn-outline-danger d-none" id="bpRetry">Retry failed (<span id="bpRetryCount">0</span>)</button>
                    <a href="${ordersListUrl()}" class="btn-orders mt-0">📋 Back to Orders List</a>
                </div>
            `);
            tick();
        }

        function tick() {
            $.ajax({ url: withShop(statusBase + '/' + encodeURIComponent(bp.id)), dataType: 'json', cache: false })
                .done(function(b) {
                    bp.errors = 0;
                    render(b);
                    if (b.status !== 'done') {
                        bp.timer = setTimeout(tick, ((b.total || 0) <= 20 || Date.now() - bp.startedAt < 30000) ? 1500 : 3000);
                    }
                })
                .fail(function() {
                    bp.errors++;
                    bp.timer = setTimeout(tick, Math.min(10000, 2000 * bp.errors));
                });
        }

        function render(b) {
            const total = b.total || 0, ok = b.success || 0, fail = b.failed || 0, skip = b.skipped || 0;
            const done = ok + fail, proc = Math.max(0, total - done);
            const pct = n => (total ? (n / total * 100) : 0).toFixed(1) + '%';
            const finished = b.status === 'done';

            $('#bpTitle').text(finished ? (fail ? '⚠️ Bulk shipping complete' : '✅ Bulk shipping complete') : 'Shipping orders…');
            $('#bpSubtitle').text(finished
                ? ok + ' shipped, ' + fail + ' failed' + (skip ? ', ' + skip + ' skipped' : '') + '.'
                : (b.started ? 'Booking with the courier — you can leave this page, booking continues in the background.' : 'Waiting for the queue to pick up your orders…'));
            $('#bpFraction').text(done + ' / ' + total);
            $('#bpCounts').html('✅ ' + ok + ' shipped · ❌ ' + fail + ' failed' + (proc ? ' · ⏳ ' + proc + ' in queue' : '') + (skip ? ' · ⏭ ' + skip + ' skipped' : ''));
            $('#bpBarOk').css('width', pct(ok));
            $('#bpBarFail').css('width', pct(fail));
            $('#bpStalled').toggleClass('d-none', !b.stalled);

            $('#bpList').html((b.items || []).map(function(it) {
                let ico, msg;
                switch (it.status) {
                    case 'queued': ico = '🕒'; msg = 'Queued'; break;
                    case 'processing': ico = '⏳'; msg = 'Booking with the courier…'; break;
                    case 'success': ico = '✔'; msg = 'Shipped' + (it.awb ? ' · AWB <b>' + esc(it.awb) + '</b>' : '') + (it.courier ? ' · ' + esc(it.courier) : ''); break;
                    case 'failed': ico = '✖'; msg = esc(it.error || 'Shipment failed'); break;
                    default: ico = '⏭'; msg = 'Skipped — ' + esc(it.error || 'not eligible');
                }
                return '<div class="bp-row st-' + esc(it.status) + '"><span class="bp-ico">' + ico + '</span>' +
                    '<span class="bp-num">#' + esc(String(it.order_id).padStart(5, '0')) + '</span><span class="bp-msg">' + msg + '</span></div>';
            }).join('') || '<div class="text-muted small text-center py-3">Nothing here.</div>');

            const retryable = (b.items || []).filter(it => it.status === 'failed').length;
            $('#bpRetryCount').text(retryable);
            $('#bpRetry').toggleClass('d-none', !(finished && retryable));
        }

        $(document).on('click', '#bpRetry', function() {
            const $btn = $(this).prop('disabled', true);
            $.ajax({ url: withShop(retryBase + '/' + encodeURIComponent(bp.id)), type: 'POST', dataType: 'json', data: { _token: "{{ csrf_token() }}" } })
                .done(function(res) {
                    if (res.batch_id) showProgress(res.batch_id);
                    else { $btn.prop('disabled', false); alert(res.msg || 'No failed orders can be retried.'); }
                })
                .fail(function() { $btn.prop('disabled', false); alert('Could not retry the failed orders. Please try again.'); });
        });

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

                    if (res.status === "success" && res.batch_id) {
                        showProgress(res.batch_id);
                    } else if (res.status === "success") {
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
                            <h4 class="text-success mb-3">✅ Bulk Shipment Queued</h4>
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
