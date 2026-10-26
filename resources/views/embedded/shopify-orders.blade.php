<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopify Orders - {{ config('setting.company_name') }}</title>

    <!-- Shopify App Bridge -->
    <meta name="shopify-api-key" content="{{ config('services.shopify_api_key') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 20px;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-body {
            padding: 25px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #202223;
            margin: 0;
        }

        .badge {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        .badge-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .badge-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .badge-delivered {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 12px 15px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-ship {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
        }

        .btn-ship:hover {
            background-color: #1e40af;
            color: #ffffff;
        }

        .btn-view {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-view:hover {
            background-color: #e5e7eb;
        }

        .btn-download {
            background-color: #10b981;
            color: #ffffff;
            border: none;
        }

        .btn-download:hover {
            background-color: #059669;
            color: #ffffff;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .btn-back {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
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

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        {{-- <a href="#" id="back-button" class="btn-back">
                            ← Back
                        </a> --}}
                        <h1 class="page-title mb-0">Shopify Orders</h1>
                    </div>
                    <div class="header-actions">
                        <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}" alt="{{ config('setting.company_name') }}"
                            height="32">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="loading-container" class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading orders from Shopify...</p>
                </div>

                <div id="table-container" style="display: none;">
                    <table id="ordersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment Status</th>
                                <th>Fulfillment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @include('embedded.partials.session-token')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get shop from query string
            const urlParams = new URLSearchParams(window.location.search);
            const shop = urlParams.get('shop');
            const host = urlParams.get('host');

            if (!shop) {
                alert('Shop parameter is missing');
                return;
            }

            // Handle back button click
            $('#back-button').on('click', function(e) {
                e.preventDefault();
                let backUrl = "{{ route('embedded.home') }}?shop=" + encodeURIComponent(shop);
                if (host) {
                    backUrl += "&host=" + encodeURIComponent(host);
                }
                window.location.href = backUrl;
            });

            // Initialize App Bridge
            const apiKey = document.querySelector('meta[name="shopify-api-key"]').content;

            // Fetch orders from Shopify
            fetchShopifyOrders(shop);

            function fetchShopifyOrders(shopDomain) {
                $.ajax({
                    url: "{{ route('shopify.orders.list') }}",
                    type: "GET",
                    data: {
                        shop: shopDomain
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === 'success' && response.orders) {
                            populateOrdersTable(response.orders, shopDomain, host);
                        } else {
                            showError(response.message || 'Failed to load orders');
                        }
                    },
                    error: function(xhr) {
                        showError('Error loading orders: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });
            }

            function populateOrdersTable(orders, shopDomain, host) {
                $('#loading-container').hide();
                $('#table-container').show();

                const table = $('#ordersTable').DataTable({
                    data: orders,
                    columns: [{
                            data: 'order_number',
                            render: function(data, type, row) {
                                return '<strong>#' + data + '</strong>';
                            }
                        },
                        {
                            data: 'customer',
                            render: function(data, type, row) {
                                if (data && data.first_name) {
                                    return data.first_name + ' ' + (data.last_name || '');
                                }
                                return 'Guest';
                            }
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                const date = new Date(data);
                                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                            }
                        },
                        {
                            data: 'total_price',
                            render: function(data, type, row) {
                                return row.currency + ' ' + parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'financial_status',
                            render: function(data) {
                                const statusMap = {
                                    'paid': 'badge-shipped',
                                    'pending': 'badge-pending',
                                    'authorized': 'badge-new',
                                    'partially_paid': 'badge-pending',
                                    'refunded': 'badge-delivered',
                                    'voided': 'badge-delivered'
                                };
                                const badgeClass = statusMap[data] || 'badge-new';
                                return '<span class="badge ' + badgeClass + '">' + (data || 'N/A')
                                    .toUpperCase() + '</span>';
                            }
                        },
                        {
                            data: 'fulfillment_status',
                            render: function(data) {
                                if (!data) {
                                    return '<span class="badge badge-new">UNFULFILLED</span>';
                                }
                                const statusMap = {
                                    'fulfilled': 'badge-shipped',
                                    'partial': 'badge-pending',
                                    'restocked': 'badge-delivered'
                                };
                                const badgeClass = statusMap[data] || 'badge-new';
                                return '<span class="badge ' + badgeClass + '">' + data
                                    .toUpperCase() + '</span>';
                            }
                        },
                        {
                            data: 'id',
                            render: function(data, type, row) {
                                const viewUrl = 'https://' + shopDomain + '/admin/orders/' + data;
                                const shipUrl = "{{ url('shopify/orders/action') }}?id=" + data +
                                    "&shop=" + shopDomain +
                                    (host ? "&host=" + host : "");

                                let buttons = '<div class="btn-group btn-group-sm" role="group">';

                                // Only show Ship Now button if order is not fulfilled
                                if (!row.fulfillment_status || row.fulfillment_status ===
                                    'partial') {
                                    buttons += '<a href="' + shipUrl +
                                        '" class="btn btn-ship btn-action">🚚 Ship Now</a>';
                                }

                                // Show Download Label button if order is fulfilled
                                if (row.fulfillment_status === 'fulfilled') {
                                    buttons +=
                                        '<button class="btn btn-download btn-action download-label-btn" data-shopify-order-id="' +
                                        data + '">📄 Download Label</button>';
                                }

                                buttons += '<a href="' + viewUrl +
                                    '" target="_blank" class="btn btn-view btn-action">👁️ View</a>';
                                buttons += '</div>';

                                return buttons;
                            }
                        }
                    ],
                    order: [
                        [0, 'desc']
                    ], // Sort by order number descending
                    pageLength: 25,
                    responsive: true,
                    language: {
                        emptyTable: "No orders found",
                        info: "Showing _START_ to _END_ of _TOTAL_ orders",
                        infoEmpty: "Showing 0 to 0 of 0 orders",
                        infoFiltered: "(filtered from _MAX_ total orders)",
                        lengthMenu: "Show _MENU_ orders",
                        search: "Search orders:",
                        zeroRecords: "No matching orders found"
                    }
                });

                // Handle Download Label button click
                $(document).on('click', '.download-label-btn', function(e) {
                    e.preventDefault();
                    const $btn = $(this);
                    const shopifyOrderId = $btn.data('shopify-order-id');

                    // Disable button and show loading
                    $btn.prop('disabled', true).html('⏳ Loading...');

                    // Get internal order ID from Shopify order ID
                    $.ajax({
                        url: "{{ route('shopify.orders.get-order-id') }}",
                        type: "GET",
                        data: {
                            shopify_order_id: shopifyOrderId,
                            shop: shop
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === 'success' && response.order_id) {
                                // Download the label
                                const downloadUrl = "{{ url('order/download-label') }}/" +
                                    response.order_id;
                                window.open(downloadUrl, '_blank');

                                // Reset button
                                $btn.prop('disabled', false).html('📄 Download Label');
                            } else {
                                alert('Order not found in system');
                                $btn.prop('disabled', false).html('📄 Download Label');
                            }
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.message ||
                                'Failed to get order details';
                            alert('Error: ' + msg);
                            $btn.prop('disabled', false).html('📄 Download Label');
                        }
                    });
                });
            }

            function showError(message) {
                $('#loading-container').html(
                    '<div class="alert alert-danger" role="alert">' +
                    '<strong>Error!</strong> ' + message +
                    '</div>'
                );
            }
        });
    </script>
</body>

</html>
