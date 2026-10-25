@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Client Kyc Request')
@section('header_title', 'Client Kyc Request')
@section('content')
    <style>
        /* Custom wrap class */
        table.dataTable td.wrap-text {
            white-space: normal !important;
            word-break: break-word;
            word-wrap: break-word;
            max-width: 320px;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-filter-weight">
                            <div class="row row-re order-filter m-0 ">
                                <div class="col-xl-3 col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                    <div class="main-selet-11">
                                        <label for="kyc_status">KYC Status</label>
                                        <select name="kyc_status" id="kyc_status" class="form-control">
                                            <option value=""> All Status </option>
                                            <option value="0"> Pending </option>
                                            <option value="1"> Approved </option>
                                            <option value="2"> Rejected </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 pl-0" style="align-self: flex-end;">
                                    <div class="main-selet-11">
                                        <button class="btn-main-1  search-btn-remi">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="main-create-order">
                            <div class="main-disolay-felx">
                                <div class="main-btn0main-1"></div>
                            </div>
                            <div class="main-data-table-1 table-responsive">
                                <table id="kycRequestDatatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>User Name</th>
                                            <th>User Email</th>
                                            <th>PAN Number</th>
                                            <th>Pancard Image</th>
                                            <th>Aadhar Number</th>
                                            <th>Aadhar Front Image</th>
                                            <th>Aadhar Back Image</th>
                                            <th>Udyam Number</th>
                                            <th>Udyam Image</th>
                                            <th>GSTIN Number</th>
                                            <th>GST Image</th>
                                            <th>Bank Passbook</th>
                                            <th>Bank Name</th>
                                            <th>Account Holder Name</th>
                                            <th>Account Number</th>
                                            <th>IFSC Code</th>
                                            <th>PAN Status</th>
                                            <th>Aadhaar Status</th>
                                            <th>GSTIN Status</th>
                                            <th>Udyam Status</th>
                                            <th>Bank Status</th>
                                            <th>KYC Status</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Reason Modal -->
    <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelReasonModalLabel">Cancellation Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="cancelReasonForm">
                        <input type="hidden" name="kyc_id" id="cancelKycId">
                        <input type="hidden" name="field" id="cancelField">
                        <input type="hidden" name="status" id="cancelStatus" value="2">
                        <div class="mb-3">
                            <label for="cancelReasonText" class="form-label fw-semibold">Please enter reason for
                                cancellation</label>
                            <textarea class="form-control" id="cancelReasonText" rows="3" placeholder="Enter reason..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="new-submit-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        var dataTable = $('#kycRequestDatatable').DataTable({
            processing: true,
            "language": {
                'loadingRecords': '&nbsp;',
                'processing': 'Loading...'
            },
            serverSide: true,
            bLengthChange: true,
            searching: true,
            bFilter: true,
            bInfo: true,
            iDisplayLength: 25,
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            "ajax": {
                "url": "{{ route('users.kyc-request.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.kyc_status = $('#kyc_status').val();
                }
            },
            "columns": [{
                    "data": "id"
                },
                {
                    "data": "user_name"
                },
                {
                    "data": "user_email"
                },
                {
                    "data": "pan_number"
                },
                {
                    "data": "pancard_image"
                },
                {
                    "data": "aadhar_number"
                },
                {
                    "data": "aadhar_front"
                },
                {
                    "data": "aadhar_back"
                },
                {
                    "data": "udyam_number"
                },
                {
                    "data": "udyam_image"
                },
                {
                    "data": "gst_number"
                },
                {
                    "data": "gst_image"
                },
                {
                    "data": "bank_passbook"
                },
                {
                    "data": "bank_name"
                },
                {
                    "data": "account_holder_name"
                },
                {
                    "data": "account_number"
                },
                {
                    "data": "ifsc_code"
                },
                {
                    "data": "pancard_status"
                },
                {
                    "data": "aadhar_status"
                },
                {
                    "data": "gst_status"
                },
                {
                    "data": "udyam_status"
                },
                {
                    "data": "bank_status"
                },
                {
                    "data": "kyc_status"
                },
                {
                    "data": "created_at"
                },
                {
                    "data": "action"
                }
            ]
        });

        dataTable.on('draw', function() {
            $('.kyc-status-select').select2({
                minimumResultsForSearch: Infinity, // hide search box
                width: 'style'
            });
        });

        $('.search-btn-remi').click(function() {
            dataTable.draw();
        });

        $(document).on('change', '.kyc-status-select', function() {
            const el = $(this);
            const id = el.data('id');
            const field = el.data('field');
            const status = el.val();

            // 🔹 Case 1: Status == 2 → show reason modal
            if (status == 2) {
                // Store context in modal inputs
                $('#cancelKycId').val(id);
                $('#cancelField').val(field);
                $('#cancelStatus').val(status);
                $('#cancelReasonText').val(''); // reset
                $('#cancelReasonModal').modal('show');

                // Reset dropdown to previous value until confirm
                el.val(el.data('old') ?? '').trigger('change.select2');
                return; // stop normal flow
            }

            // 🔹 Case 2: Normal update
            updateKycStatus(el, id, field, status);
        });

        // 🔹 Save previous value for rollback if cancel modal opens
        $(document).on('focus', '.kyc-status-select', function() {
            $(this).data('old', $(this).val());
        });


        // 🔹 Form submit handler
        $('#cancelReasonForm').on('submit', function(e) {
            e.preventDefault();

            const id = $('#cancelKycId').val();
            const field = $('#cancelField').val();
            const status = $('#cancelStatus').val();
            const reason = $('#cancelReasonText').val();

            if (!reason.trim()) {
                toastrMsg('error', 'Please enter a cancellation reason.');
                return;
            }

            // Hide modal
            $('#cancelReasonModal').modal('hide');

            // Find the matching dropdown in table
            const el = $(`.kyc-status-select[data-id="${id}"][data-field="${field}"]`);

            // Trigger update with reason included
            updateKycStatus(el, id, field, status, reason);
        });

        // 🔹 Shared function for AJAX update
        function updateKycStatus(el, id, field, status, reason = '') {
            el.prop('disabled', true);

            $.ajax({
                url: "{{ route('users.kyc.update-status') }}",
                method: "POST",
                data: {
                    id,
                    field,
                    status,
                    reason,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    toastrMsg('success', res.message || 'Status updated');
                    dataTable.ajax.reload(null, false);
                },
                error: function(xhr) {
                    toastrMsg('error', xhr.responseJSON?.message || 'Failed to update.');
                    dataTable.ajax.reload(null, false);
                },
                complete: function() {
                    el.prop('disabled', false);
                }
            });
        }

        // 🔹 Handle User KYC Status dropdown change (Active/Inactive)
        dataTable.on('draw', function() {
            $('.user-kyc-status-select').select2({
                minimumResultsForSearch: Infinity,
                width: 'style'
            });
        });

        $(document).on('change', '.user-kyc-status-select', function() {
            const el = $(this);
            const userId = el.data('user-id');
            const status = el.val();
            const oldValue = el.data('old') || el.val();

            if (confirm('Are you sure you want to change the user KYC status?')) {
                el.prop('disabled', true);

                $.ajax({
                    url: "{{ route('users.kyc.update-user-status') }}",
                    method: "POST",
                    data: {
                        user_id: userId,
                        status: status,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        toastrMsg('success', res.message || 'User KYC status updated');
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        toastrMsg('error', xhr.responseJSON?.message ||
                            'Failed to update user KYC status.');
                        el.val(oldValue).trigger('change.select2');
                    },
                    complete: function() {
                        el.prop('disabled', false);
                    }
                });
            } else {
                el.val(oldValue).trigger('change.select2');
            }
        });

        $(document).on('focus', '.user-kyc-status-select', function() {
            $(this).data('old', $(this).val());
        });
    </script>
@endpush
