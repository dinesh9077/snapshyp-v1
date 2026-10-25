@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - COD Remittance')
@section('header_title', 'COD Remittance')
@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <style>
        .remittance-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .stat-box {
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #ddd;
            background: #fff;
            height: 100%;
        }

        .stat-box-wrapper {
            position: relative;
            /* margin-top: 32px; */
            height: 100%;
        }

        .stat-label {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #000;
        }

        .stat-value.positive {
            color: #28a745;
        }

        .stat-value.negative {
            color: #dc3545;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .badge-scheduled {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            position: absolute;
            top: -13px;
            right: 20px;
            outline: 6px solid #fff;
        }

        .badge-upcoming {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            position: absolute;
            top: -13px;
            right: 20px;
            outline: 6px solid #fff;
        }

        .info-row-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            /* border-bottom: 1px solid #e9ecef; */
        }

        .info-row-item:last-child {
            border-bottom: none;
        }

        .info-row-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }

        .info-row-value {
            font-size: 14px;
            font-weight: 600;
            color: #000;
            text-align: right;
        }

        .info-row-value.green {
            color: #28a745;
        }

        .info-row-value.red {
            color: #dc3545;
        }

        .card-title-with-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            margin-top: 10px;
        }

        .card-title-center {
            font-size: 16px;
            font-weight: 600;
            color: #000;
        }

        .view-details-link {
            font-size: 12px;
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }

        .view-details-link:hover {
            color: #0056b3;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: #000;
            color: white;
            border-color: #000;
        }

        .t-black {
            color: #000 !important;
        }

        .pay-now-link {
            color: #fff;
            font-weight: 600;
            background: #28a745;
            padding: 4px 10px;
            border-radius: 7px;
            transition: color 0.3s ease;
        }

        .pay-now-link:hover {
            color: #000;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <!-- Overview Section -->
                <div class="main-order-001 mb-3">
                    <div class="main-heading-1 p-0">
                        <h4 class="card-title">Overview</h4>
                    </div>

                    <div class="row" style="row-gap: 30px;">

                        <!-- Previous Payments -->
                        @if (!empty($stats['previous_payments']['start_date']))
                            <div class="col-xl-4 col-md-6">
                                <div class="stat-box-wrapper">
                                    <div class="stat-box">
                                        <div class="card-title-with-links">
                                            <span class="card-title-center">Previous Payments (After
                                                {{ $stats['previous_payments']['start_date'] }})</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total Delivered Count</span>
                                            <span
                                                class="info-row-value">{{ $stats['previous_payments']['delivery_count'] }}</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total COD Remittance Amount</span>
                                            <span class="info-row-value green">{{ config('setting.currency') }}
                                                {{ number_format($stats['previous_payments']['cod_amount'], 2) }}</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total Expenses</span>
                                            <span class="info-row-value red">-{{ config('setting.currency') }}
                                                {{ number_format($stats['previous_payments']['expenses'], 2) }}</span>
                                        </div>

                                        <div class="mt-3">
                                            <strong>Amount Transferred to Seller</strong><br>
                                            <span class="stat-value positive">{{ config('setting.currency') }}
                                                {{ number_format($stats['previous_payments']['amount_transferred'], 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Next Upcoming Settlement -->
                        @if (!empty($stats['next_settlement']['cycle_date']))
                            <div class="col-xl-4 col-md-6">
                                <div class="stat-box-wrapper">
                                    <span class="badge-scheduled">SCHEDULED ON
                                        {{ strtoupper($stats['next_settlement_date']) }}</span>

                                    <div class="stat-box">
                                        <div class="card-title-with-links">
                                            <span class="card-title-center">Next Upcoming Settlement Status</span>
                                            <a href="{{ route('cod-remittance.show', $stats['upcomingRemittance']->id) }}"
                                                class="view-details-link">View Details</a>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Cycle Date</span>
                                            <span
                                                class="info-row-value">{{ $stats['next_settlement']['cycle_date'] }}</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total Delivered Count</span>
                                            <span
                                                class="info-row-value">{{ $stats['next_settlement']['delivery_count'] }}</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total COD Remittance Amount</span>
                                            <span class="info-row-value green">{{ config('setting.currency') }}
                                                {{ number_format($stats['next_settlement']['cod_amount'], 2) }}</span>
                                        </div>

                                        <div class="info-row-item">
                                            <span class="info-row-label">Total Expenses</span>
                                            <span class="info-row-value red">-{{ config('setting.currency') }}
                                                {{ number_format($stats['next_settlement']['expenses'], 2) }}</span>
                                        </div>

                                        <div class="mt-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Amount Transferable to Seller</strong><br>
                                                @if ($stats['next_settlement']['transferable'] < 0)
                                                    <span class="stat-value negative">
                                                        -{{ config('setting.currency') }}
                                                        {{ number_format(abs($stats['next_settlement']['transferable']), 2) }}
                                                    </span>
                                                @else
                                                    <span class="stat-value positive">
                                                        {{ config('setting.currency') }}
                                                        {{ number_format($stats['next_settlement']['transferable'], 2) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($authUser->role != 'user' && $stats['upcomingRemittance']->settlement_status == 'pending')
                                                <a href="javascript:void(0)" class="pay-now-link"
                                                    data-remittance-id="{{ $stats['upcomingRemittance']->id }}"
                                                    data-amount="{{ $stats['next_settlement']['transferable'] }}"
                                                    style="">Pay
                                                    Now</a>
                                            @elseif($stats['upcomingRemittance']->settlement_status == 'completed')
                                                <!-- Settlement Status Badge -->
                                                <div style="margin-bottom: 15px; text-align: center;">
                                                    <span class="badge badge-success"
                                                        style="font-size: 13px; padding: 6px 12px;">
                                                        <i class="mdi mdi-check-circle"></i> COMPLETED
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <!-- Total Outstanding Payments -->

                        <div class="col-xl-4 col-md-6">
                            <div class="stat-box-wrapper">
                                <span class="badge-upcoming">NEXT UPCOMING ON
                                    {{ strtoupper($stats['next_upcoming_settlement_date'] ?? 'N/A') }}</span>

                                <div class="stat-box">
                                    <div class="card-title-with-links">
                                        <span class="card-title-center">Total Outstanding Payments</span>
                                    </div>

                                    <div class="info-row-item">
                                        <span class="info-row-label">Total Delivered Count</span>
                                        <span class="info-row-value">{{ $stats['total_delivered_count'] ?? 0 }}</span>
                                    </div>

                                    <div class="info-row-item">
                                        <span class="info-row-label">Total COD Pending</span>
                                        <span class="info-row-value green">{{ config('setting.currency') }}
                                            {{ number_format($stats['total_cod_pending'] ?? 0, 2) }}</span>
                                    </div>

                                    <div class="info-row-item">
                                        <span class="info-row-label">Unsettled Expenses</span>
                                        <span class="info-row-value red">-{{ config('setting.currency') }}
                                            {{ number_format($stats['unsettled_expenses'] ?? 0, 2) }}</span>
                                    </div>

                                    <div class="mt-3">
                                        <strong>Amount Transferable to Seller</strong><br>
                                        <span class="stat-value positive">{{ config('setting.currency') }}
                                            {{ number_format($stats['amount_transferable'] ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="main-order-001 mb-3">
                    <!-- Previous Payments History -->
                    <div class="section-header main-heading-1 p-0">
                        <h4 class="mb-0 mt-0 card-title">Previous Payments History</h4>
                        <button class="btn-main-1" id="downloadBtn">
                            <i class="mdi mdi-download"></i> Download
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="" id="remittanceTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Cycle Date</th>
                                    <th>Remittance ID</th>
                                    <th>Payment Scheduled Date</th>
                                    <th>Payment Date</th>
                                    <th>Total Delivery Count</th>
                                    <th class="t-black">Total COD Remittance Amt</th>
                                    <th class="t-black">Total Expenses</th>
                                    <th class="t-black">Amount Transferred to Seller</th>
                                    <th>Amount Transferred to Next Cycle Of Wallet</th>
                                    <th>Sattlement Status</th>
                                    <th>Bank Account No.</th>
                                    <th>UTR</th>
                                    <th>Seller Payment Mode</th>
                                    <th>Action</th>
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

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="">
                <div class="modal-header" style="">
                    <h5 class="modal-title" id="paymentModalLabel" style="font-weight: 600;">Process Remittance Payment
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                        <i class="mdi mdi-close"></i>

                    </button>
                </div>
                <form id="paymentForm">
                    @csrf
                    <input type="hidden" id="remittance_id" name="remittance_id">
                    <div class="modal-body" style="padding: 24px;">
                        <div class="form-group">
                            <label for="transfer_amount" style="font-weight: 500; color: #495057;">Transfer Amount</label>
                            <input type="text" class="form-control" id="transfer_amount" name="transfer_amount"
                                readonly style="font-weight: 600; font-size: 18px; color: #28a745;">
                        </div>
                        <div class="form-group">
                            <label for="bank_account_no" style="font-weight: 500; color: #495057;">Bank Account Number
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bank_account_no" name="bank_account_no"
                                required placeholder="Enter bank account number"
                                style="background-color: #fff; border: 1px solid #E5E7EB;border-bottom: 2px solid #E5E7EB;">
                        </div>
                        <div class="form-group">
                            <label for="utr" style="font-weight: 500; color: #495057;">UTR Number <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="utr" name="utr" required
                                placeholder="Enter UTR/Transaction reference number"
                                style="background-color: #fff; border: 1px solid #E5E7EB; border-bottom: 2px solid #E5E7EB;">
                        </div>
                        <div class="form-group">
                            <label for="payment_date" style="font-weight: 500; color: #495057;">Payment Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required
                                value="{{ date('Y-m-d') }}"
                                style="background-color: #fff;border: 1px solid #E5E7EB;border-bottom: 2px solid #E5E7EB;">
                        </div>
                        <div class="form-group">
                            <label for="seller_payment_mode" style="font-weight: 500; color: #495057;">Payment Mode <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="seller_payment_mode" name="seller_payment_mode" required
                                style="background-color: #fff;border: 1px solid #E5E7EB;border-bottom: 2px solid #E5E7EB;">
                                <option value="">Select Payment Mode</option>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="IMPS">IMPS</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 2px solid #dee2e6; padding: 16px 24px;">
                        <button type="button" class="btn btn-light btn-reset" data-dismiss="modal"
                            style="">Cancel</button>
                        <button type="submit" class="new-submit-btn" id="submitPayment" style=" min-width: 120px;">
                            <i class="mdi mdi-check"></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with Buttons extension
            var table = $('#remittanceTable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "{{ route('user.cod-remittance.ajax', $user->id) }}",
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.search = $('input[type="search"]').val();
                        d.user_id = '{{ $user->id }}';
                    }
                },
                dom: 'Blfrtip', // B=Buttons, l=length menu, f=filter, r=processing, t=table, i=info, p=pagination
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="mdi mdi-download"></i> Download Excel',
                    className: 'btn btn-sm btn-outline-primary d-none',
                    filename: 'remittance_history_' + new Date().getTime(),
                    exportOptions: {
                        columns: ':not(:last-child)', // Exclude last column (Action)
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get plain text
                                var tmp = document.createElement("DIV");
                                tmp.innerHTML = data;
                                return tmp.textContent || tmp.innerText || "";
                            }
                        }
                    }
                }],
                columns: [{
                        data: 'cycle_date',
                        name: 'cycle_date'
                    },
                    {
                        data: 'remittance_id',
                        name: 'remittance_id'
                    },
                    {
                        data: 'payment_scheduled_date',
                        name: 'payment_scheduled_date'
                    },
                    {
                        data: 'payment_date',
                        name: 'payment_date'
                    },
                    {
                        data: 'delivery_count',
                        name: 'delivery_count'
                    },
                    {
                        data: 'cod_amount',
                        name: 'cod_amount',
                        class: 'text-success'
                    },
                    {
                        data: 'expenses',
                        name: 'expenses',
                        class: 'text-danger'
                    },
                    {
                        data: 'transferable_amount',
                        name: 'transferable_amount',
                        class: 'text-success'
                    },
                    {
                        data: 'next_cycle_of_wallet',
                        name: 'next_cycle_of_wallet'
                    },
                    {
                        data: 'settlement_status',
                        name: 'settlement_status'
                    },
                    {
                        data: 'bank_account_no',
                        name: 'bank_account_no'
                    },
                    {
                        data: 'utr',
                        name: 'utr'
                    },
                    {
                        data: 'seller_payment_mode',
                        name: 'seller_payment_mode'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No remittance data available",
                    zeroRecords: "No matching records found"
                }
            });

            // Trigger DataTables export button when custom download button is clicked
            $('#downloadBtn').on('click', function() {
                table.button('.buttons-excel').trigger();
            });

            // Tab switching
            $('.tab-btn').on('click', function() {
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');

                var tab = $(this).data('tab');
                // You can add tab content switching logic here
                console.log('Switched to tab:', tab);
            });

            // Pay Now button click handler (for button in card)
            $('.pay-now-btn').on('click', function() {
                openPaymentModal($(this));
            });

            // Pay Now link click handler (for links in table - using event delegation)
            $(document).on('click', '.pay-now-link', function() {
                openPaymentModal($(this));
            });

            // Function to open payment modal
            function openPaymentModal(element) {
                var remittanceId = element.data('remittance-id');
                var amount = element.data('amount');

                if (!remittanceId) {
                    alert('No pending remittance found');
                    return;
                }

                // Set values in modal
                $('#remittance_id').val(remittanceId);
                $('#transfer_amount').val('{{ config('setting.currency') }} ' + parseFloat(amount).toFixed(2));

                // Clear form fields
                $('#bank_account_no').val('');
                $('#utr').val('');
                $('#payment_date').val('{{ date('Y-m-d') }}');
                $('#seller_payment_mode').val('');

                // Show modal
                $('#paymentModal').modal('show');
            }

            // Payment form submission
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                var submitBtn = $('#submitPayment');
                var originalText = submitBtn.html();

                // Disable button and show loading
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Processing...');

                $.ajax({
                    url: '{{ route('cod-remittance.process-payment') }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#paymentModal').modal('hide');
                            toastrMsg(response.status, response.msg);
                            location.reload(); // Reload to show updated data
                        } else {
                            toastrMsg(response.status, response.msg);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'An error occurred while processing payment';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastrMsg(response.status, response.msg);
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endpush
