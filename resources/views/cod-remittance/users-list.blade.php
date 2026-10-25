@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - User COD Remittance')
@section('header_title', 'User COD Remittance')
@section('content')

    <style>
        .users-list-card {
            /* background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); */
            /* margin-bottom: 20px; */
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #000;
        }

        .table-responsive {
            margin-top: 20px;
        }


        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .total-payout-display{
            display: flex; 
            row-gap: 5px;
            column-gap: 20px;
            flex-wrap: wrap;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="main-order-001 mb-3">
                    <div class="page-header">
                        <h4 class="page-title">All Users with COD Remittance</h4>
                        <div class="total-payout-display" style="">
                            <div>
                                <span style="font-size: 14px; color: #6c757d; margin-right: 8px;">Total Payout
                                    (Completed):</span>
                                <span style="font-size: 18px; font-weight: 600; color: #28a745;">
                                    {{ config('setting.currency') }} {{ number_format($totalPayoutAmount ?? 0, 2) }}
                                </span>
                            </div>
                            <div>
                                <span style="font-size: 14px; color: #6c757d; margin-right: 8px;">Total Pending
                                    Payout:</span>
                                <span style="font-size: 18px; font-weight: 600; color: #ffc107;">
                                    {{ config('setting.currency') }} {{ number_format($totalPendingPayoutAmount ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section order-filter"
                        style="">
                        <div class="row  row-re align-items-end">
                            <div class="col-lg-3 col-sm-6">
                                <label for="user_id"
                                    style="font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; display: block;">User</label>
                                <select id="user_id" class="form-control select2"
                                    style="">
                                    <option value="">All Users</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="completed_payout_filter"
                                    style="font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; display: block;">Completed
                                    Payout</label>
                                <select id="completed_payout_filter" class="form-control"
                                    style="">
                                    <option value="">All Amounts</option>
                                    <option value="has_completed">Has Completed Payout (> 0)</option>
                                    <option value="no_completed">No Completed Payout (= 0)</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label for="pending_payout_filter"
                                    style="font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; display: block;">Pending
                                    Payout</label>
                                <select id="pending_payout_filter" class="form-control"
                                    style="">
                                    <option value="">All Amounts</option>
                                    <option value="has_pending">Has Pending Payout (> 0)</option>
                                    <option value="no_pending">No Pending Payout (= 0)</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <button id="resetFilters" class="btn btn-main-1 search-btn-remi"
                                    style="">
                                    <i class="mdi mdi-refresh"></i> Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="" id="usersTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Company Name</th>
                                    <th>Status</th>
                                    <th>Total Payout Amount</th>
                                    <th>Total Pending Payout Amount</th>
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
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#usersTable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "{{ route('user.cod-remittance.users-ajax') }}",
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.user_id = $('#user_id').val();
                        d.completed_payout_filter = $('#completed_payout_filter').val();
                        d.pending_payout_filter = $('#pending_payout_filter').val();
                    }
                },
                columns: [{
                        data: 'sr_no',
                        name: 'sr_no',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'mobile',
                        name: 'mobile'
                    },
                    {
                        data: 'company_name',
                        name: 'company_name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false
                    },
                    {
                        data: 'total_payout_amount',
                        name: 'total_payout_amount'
                    },
                    {
                        data: 'total_pending_payout_amount',
                        name: 'total_pending_payout_amount'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [6, 'desc']
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No users found",
                    zeroRecords: "No matching users found",
                    processing: "Loading users..."
                }
            });

            // Filter change handlers
            $('#user_id, #completed_payout_filter, #pending_payout_filter').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#user_id').val('');
                $('#completed_payout_filter').val('');
                $('#pending_payout_filter').val('');
                table.ajax.reload();
            });
        });
    </script>
@endpush
