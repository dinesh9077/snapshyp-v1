@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Remittance Summary Detail')
@section('header_title', 'Remittance Summary Detail')
@section('content')

    <style>
        /* .remittance-detail-container {
                                                                                            padding: 0;
                                                                                        } */

        .info-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid #f0f0f0;
            row-gap: 10px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-item {
            flex: 1;
            min-width: 150px;
            padding: 10px 20px;
            border-right: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-right: none;
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .info-value {
            font-size: 15px;
            color: #000;
            font-weight: 600;
        }

        .info-value.green {
            color: #28a745;
        }

        .info-value.red {
            color: #dc3545;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .info-item {
                min-width: 200px;
            }
        }

        @media (max-width: 768px) {
            .info-item {
                min-width: 100%;
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
            }

            .info-item:last-child {
                border-bottom: none;
            }
        }

        .tabs-container {
            overflow-x: auto;
        }

        .nav-tabs-custom {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 0;
            min-width: max-content;
        }

        .nav-tab {
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
            white-space: nowrap;
            border-radius: 8px 8px 0 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tab:hover {
            color: #fff;
            background: var(--new-color-prime);
        }

        .nav-tab.active {
            color: #fff;
            background: var(--new-color-prime);
            border-bottom-color: var(--new-color-prime);
            font-weight: 600;
        }

        .nav-tab .badge-count {
            background: var(--new-color-prime);
            color: #fff;
            /* padding: 4px 9px; */
            width: 25px;
            height: 25px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-tab.active .badge-count {
            background: var(--new-color-prime);
            color: #fff;
        }

        .tab-content-area {
            background: white;
            padding: 20px 0;
            margin: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge-revenue {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-expense {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .amount-positive {
            color: #28a745;
            font-weight: 600;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: 600;
        }

        #shopdeskOrdersTable,
        #codOrdersTable,
        #shippingOrdersTable {
            width: 100% !important;
        }

        @media (max-width: 991px) {
            .nav-tabs-custom {
                gap: 5px;
            }

            .nav-tab {
                padding: 8px 10px;
                font-size: 12px;
            }
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid remittance-detail-container">

                <!-- First Info Card - Basic Details -->
                <div class="main-order-001 mb-3">
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Remittance Settlement Status</div>
                            <div class="info-value">
                                {!! $remittance->settlement_status === 'pending'
        ? "<span class='badge badge-danger'>PENDING</span>"
        : "<span class='badge badge-success'>COMPLETED</span>" !!}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Remittance ID</div>
                            <div class="info-value">{{ $remittance->remittance_id }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">UTR</div>
                            <div class="info-value">{{ $remittance->utr ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Bank Account</div>
                            <div class="info-value">{{ $remittance->bank_account_no ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Remittance Cycle Date</div>
                            <div class="info-value">
                                {{ \Carbon\Carbon::parse($remittance->cycle_from_date)->format('d M Y') }} -
                                {{ \Carbon\Carbon::parse($remittance->cycle_to_date)->format('d M Y') }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Seller Payment Mode</div>
                            <div class="info-value">{{ $remittance->seller_payment_mode ?? 'Bank Wallet' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment Scheduled Date</div>
                            <div class="info-value">
                                {{ $remittance->payment_scheduled_date ? \Carbon\Carbon::parse($remittance->payment_scheduled_date)->format('d M Y') : '-' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment Done</div>
                            <div class="info-value">
                                {{ $remittance->payment_date ? \Carbon\Carbon::parse($remittance->payment_date)->format('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Info Card - Financial Details -->
                <div class="main-order-001 mb-3">
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Total Expenses</div>
                            <div class="info-value red">
                                {{ config('setting.currency') }} {{ number_format($remittance->total_expenses, 2) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> Cod Balance Transfer To Wallet</div>
                            <div class="info-value red">
                                {{ config('setting.currency') }}
                                {{ number_format($codWalletExpenses, 2) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> {{ config('setting.company_name') }} Service Fee</div>
                            <div class="info-value red">
                                {{ config('setting.currency') }}
                                {{ number_format($remittance->orders->sum('remittance_amount'), 2) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total COD Remittance Amount</div>
                            <div class="info-value green">
                                {{ config('setting.currency') }}
                                {{ number_format($remittance->total_cod_remittance_amount, 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Total Delivery Count</div>
                            <div class="info-value">{{ $remittance->total_delivery_count }}</div>
                        </div>
                        @if ($remittance->previous_cycle_pending_net_amount > 0)
                            <div class="info-item">
                                <div class="info-label">Previous Cycle Pending Net Amount</div>
                                <div class="info-value">
                                    -{{ config('setting.currency') }}
                                    {{ number_format($remittance->previous_cycle_pending_net_amount, 2) }}
                                </div>
                            </div>
                        @endif
                        <div class="info-item">
                            <div class="info-label">Net Amount of Current Cycle</div>
                            <div
                                class="info-value {{ $remittance->amount_transferred_next_cycle_or_wallet > 0 ? 'red' : 'green' }}">
                                @if ($remittance->amount_transferred_next_cycle_or_wallet > 0)
                                    -{{ config('setting.currency') }}
                                    {{ number_format($remittance->amount_transferred_next_cycle_or_wallet, 2) }}
                                @else
                                    +{{ config('setting.currency') }}
                                    {{ number_format($remittance->amount_transferred_to_seller, 2) }}
                                @endif
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Amount Transferred To Seller</div>
                            <div class="info-value {{ $remittance->amount_transferred_to_seller > 0 ? 'green' : '' }}">
                                @if ($remittance->amount_transferred_to_seller > 0)
                                    +{{ config('setting.currency') }}
                                    {{ number_format($remittance->amount_transferred_to_seller, 2) }}
                                @else
                                    {{ config('setting.currency') }} 0
                                @endif
                            </div>
                        </div>
                        @if ($remittance->amount_transferred_next_cycle_or_wallet > 0)
                            <div class="info-item">
                                <div class="info-label">Amount Transferred to Next Cycle</div>
                                <div class="info-value">
                                    -{{ config('setting.currency') }}
                                    {{ number_format($remittance->amount_transferred_next_cycle_or_wallet, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="main-order-001 mb-3">
                    <div class="tabs-container">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center;flex-wrap: wrap;row-gap: 10px;">
                            <div class="nav-tabs-custom">
                                <button class="nav-tab active" data-tab="cod" onclick="switchTab('cod')">
                                    COD Remittance <span class="badge-count"
                                        id="cod-count">{{ $remittance->orders->where('order_type', 'cod')->count() }}</span>
                                </button>
                                <button class="nav-tab" data-tab="shipping" onclick="switchTab('shipping')">
                                    Wallet Balance Usage <span class="badge-count"
                                        id="shipping-count">{{ $walletCount }}</span>
                                </button>
                                <button class="nav-tab" data-tab="shopdesk" onclick="switchTab('shopdesk')">
                                    {{ config('setting.company_name') }} Service Fee <span class="badge-count"
                                        id="shopdesk-count">{{ $remittance->orders->count() }}</span>
                                </button>
                            </div>
                            <div class="mr-2">
                                <button class="btn-main-1" id="downloadExcelBtn" style="display: none;">
                                    <i class="mdi mdi-download"></i> Download Excel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content-area">
                        <!-- COD Revenue Table -->
                        <div class="tab-table-container" id="cod-table-container">
                            <div class="table-responsive">
                                <table class="" id="codOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Settlement Date</th>
                                            <th>Order ID</th>
                                            <th>Order Created Date</th>
                                            <th>AWB Number</th>
                                            <th>Transaction Type</th>
                                            <th>Transaction Details</th>
                                            <th>Amount</th>
                                            <th>Trigger State</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via DataTable AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Wallet Balance Usage Table -->
                        <div class="tab-table-container" id="shipping-table-container" style="display: none;">
                            <div class="table-responsive">
                                <table class="" id="shippingOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction Date</th>
                                            <th>Transaction ID</th>
                                            <th>Transaction Type</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via DataTable AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Service Fee Table -->
                        <div class="tab-table-container" id="shopdesk-table-container" style="display: none;">
                            <div class="table-responsive">
                                <table class="" id="shopdeskOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Settlement Date</th>
                                            <th>Order ID</th>
                                            <th>Order Created Date</th>
                                            <th>AWB Number</th>
                                            <th>Transaction Type</th>
                                            <th>Transaction Details</th>
                                            <th>Service Fee</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via DataTable AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        let codTable, shippingTable, shopdeskTable;

        $(document).ready(function () {
            // Initialize all three DataTables
            initializeCodTable();
            // initializeWalletTable();
            // initializeShopdeskTable();
        });

        // COD Revenue Table
        function initializeCodTable() {
            // Check if DataTable already exists and destroy it
            if ($.fn.DataTable.isDataTable('#codOrdersTable')) {
                $('#codOrdersTable').DataTable().destroy();
            }
            codTable = $('#codOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "{{ route('cod-remittance.orders-ajax', $remittance->id) }}",
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.tab = 'cod';
                    }
                },
                columns: [{
                    data: 'settlement_date',
                    name: 'settlement_date'
                },
                {
                    data: 'order_id',
                    name: 'order_id',
                    render: function (data, type, row) {
                        return '<a href="{{ url('/user/order-detail') }}/' + data +
                            '" target="_blank" style="color: #007bff; text-decoration: underline !important;">' +
                            data + '</a>';
                    }
                },
                {
                    data: 'order_created_date',
                    name: 'order_created_date'
                },
                {
                    data: 'awb_number',
                    name: 'awb_number',
                    render: function (data, type, row) {
                        if (data && data !== '-') {
                            return '<a href="{{ url('/user/order-detail') }}/' + row.order_id +
                                '" target="_blank" style="color: #007bff; text-decoration: underline !important;">' +
                                data + '</a>';
                        }
                        return data;
                    }
                },
                {
                    data: 'transaction_type',
                    name: 'transaction_type',
                    render: function (data) {
                        return '<span class="badge-revenue">Revenue</span>';
                    }
                },
                {
                    data: 'transaction_details',
                    name: 'transaction_details'
                },
                {
                    data: 'amount',
                    name: 'amount',
                    render: function (data) {
                        return '<span class="amount-positive">+' + data + '</span>';
                    }
                },
                {
                    data: 'trigger_state',
                    name: 'trigger_state'
                }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                language: {
                    emptyTable: "No COD orders found",
                    zeroRecords: "No matching records found"
                }
            });
        }

        // Wallet Balance Usage Table
        function initializeWalletTable() {
            // Check if DataTable already exists and destroy it
            if ($.fn.DataTable.isDataTable('#shippingOrdersTable')) {
                $('#shippingOrdersTable').DataTable().destroy();
            }
            shippingTable = $('#shippingOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "{{ route('cod-remittance.orders-ajax', $remittance->id) }}",
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.tab = 'shipping';
                    }
                },
                columns: [{
                    data: 'transaction_date',
                    name: 'transaction_date'
                },
                {
                    data: 'transaction_id',
                    name: 'transaction_id'
                },
                {
                    data: 'transaction_type',
                    name: 'transaction_type',
                    render: function (data) {
                        return '<span class="badge-expense">Expense</span>';
                    }
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'payment_mode',
                    name: 'payment_mode'
                },
                {
                    data: 'payment_status',
                    name: 'payment_status',
                    render: function (data) {
                        return '<span class="badge badge-success">' + data + '</span>';
                    }
                },
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                language: {
                    emptyTable: "No wallet transactions found",
                    zeroRecords: "No matching records found"
                }
            });
        }

        // Service Fee Table
        function initializeShopdeskTable() {
            // Check if DataTable already exists and destroy it
            if ($.fn.DataTable.isDataTable('#shopdeskOrdersTable')) {
                $('#shopdeskOrdersTable').DataTable().destroy();
            }
            shopdeskTable = $('#shopdeskOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "{{ route('cod-remittance.orders-ajax', $remittance->id) }}",
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.tab = 'shopdesk';
                    }
                },
                columns: [{
                    data: 'settlement_date',
                    name: 'settlement_date'
                },
                {
                    data: 'order_id',
                    name: 'order_id',
                    render: function (data, type, row) {
                        return '<a href="{{ url('/user/order-detail') }}/' + data +
                            '" target="_blank" style="color: #007bff; text-decoration: underline !important;">' +
                            data + '</a>';
                    }
                },
                {
                    data: 'order_created_date',
                    name: 'order_created_date'
                },
                {
                    data: 'awb_number',
                    name: 'awb_number',
                    render: function (data, type, row) {
                        if (data && data !== '-') {
                            return '<a href="{{ url('/user/order-detail') }}/' + row.order_id +
                                '" target="_blank" style="color: #007bff; text-decoration: underline !important;">' +
                                data + '</a>';
                        }
                        return data;
                    }
                },
                {
                    data: 'transaction_type',
                    name: 'transaction_type',
                    render: function (data) {
                        return '<span class="badge-expense">Expense</span>';
                    }
                },
                {
                    data: 'transaction_details',
                    name: 'transaction_details'
                },
                {
                    data: 'amount',
                    name: 'amount',
                    render: function (data) {
                        return '<span class="amount-negative">-' + data + '</span>';
                    }
                },
                {
                    data: 'trigger_state',
                    name: 'trigger_state'
                }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                language: {
                    emptyTable: "No service fees found",
                    zeroRecords: "No matching records found"
                }
            });
        }

        let currentTab = 'cod';

        function switchTab(tab) {
            currentTab = tab;

            // Update active tab
            $('.nav-tab').removeClass('active');
            $(`.nav-tab[data-tab="${tab}"]`).addClass('active');

            // Hide all table containers
            $('.tab-table-container').hide();

            // Show/hide download button based on tab
            if (tab === 'cod' || tab === 'shopdesk') {
                $('#downloadExcelBtn').show();
            } else {
                $('#downloadExcelBtn').hide();
            }

            // Show the selected table container
            if (tab === 'cod') {
                $('#cod-table-container').show();
                initializeCodTable();
            } else if (tab === 'shipping') {
                $('#shipping-table-container').show();
                initializeWalletTable();
            } else if (tab === 'shopdesk') {
                $('#shopdesk-table-container').show();
                initializeShopdeskTable();
            }
        }

        // Download Excel button handler
        $('#downloadExcelBtn').on('click', function () {
            const remittanceId = '{{ $remittance->id }}';
            const url = `{{ url('/user/cod-remittance') }}/${remittanceId}/export-excel?tab=${currentTab}`;
            window.open(url, '_blank');
        });

        // Show download button on page load for COD tab
        $(document).ready(function () {
            $('#downloadExcelBtn').show();
        });
    </script>
@endpush