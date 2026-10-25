@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - User Wise Report')
@section('header_title', 'User Wise Report')
@section('content')
<style>
.tooltip .tooltiptext {
    text-align: left !important;
    padding: 5px 0 5px 5px !important;
}

td p {
    margin-bottom: 0;
}

.page-heading-main {
    display: flex;
    align-items: center;
    justify-content: end;
    margin-bottom: 20px;
    gap: 15px;
    flex-wrap: wrap;
}

.left-head-deta {
    display: flex;
    align-items: end;
    gap: 15px;
}

.custom-entry {
    display: flex;
    align-items: center;
    gap: 8px;
}

.right-head-deta {
    display: flex;
    align-items: center;
    gap: 15px;
}

.table-custom-serch .input-main {
    min-width: 450px;
}

.table-custom-serch .input-main {
    border: 1px solid #dcdcdc;
    border-radius: 10px;
    padding: 7px;
    margin-left: 3px;
    font-weight: 400;
    font-size: 14px;
    color: #000;
    background-color: white;
    padding: 9px 20px;
}

@media(max-width : 575px) {
    .right-head-deta {
        flex-direction: column;
    }

    .table-custom-serch .input-main {
        width: 100%;
        min-width: 100%;
    }
}

.custom-entry p {
    margin: 0;
    font-size: 14px;
    color: #0A1629;
    font-weight: 500;
}

#page_length {
    padding: 4px 8px;
    border-radius: var(--default-radius);
    background-color: transparent;
    color: #000;
    appearance: none;
    text-align: center;
    font-weight: 400;
    margin: 0 6px;
    border: 1px solid #BCBCBC;
}

.totals-wrap .label {
    font-weight: 600;
}

.totals-wrap .value {
    font-size: 1.15rem;
}
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="main-order-001">
                    <div class="main-filter-weight ">
                        <div class="row row-re userWiseSearchForm order-filter m-0 mb-3">
                            @if (Auth::user()->role != 'user')
                            <div class="col-lg-2 col-sm-6">
                                <div class="main-selet-11">
                                    <label for="user_id">User</label>
                                    <select name="user_id" class="select2 form-control" id="user_id">
                                        <option value="">All Users</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                            <div class="col-lg-2 col-sm-6">
                                <div class="main-selet-11">
                                    <label for="courier_name">Courier Company</label>
                                    <select name="courier_name" class="select2 form-control" id="courier_name">
                                        <option value="">All Courier</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier }}">{{ $courier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <div class="main-selet-11">
                                    <label for="status_courier">Status</label>
                                    <select name="status_courier" class="select2 form-control" id="status_courier">
                                        <option value="">All Status</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <div class="main-selet-11">
                                    <label for="fromdate">From Date</label>
                                    <input type="text" class="form-control datepicker" name="fromdate"
                                        id="fromdate" value="{{ $defaultFrom }}" placeholder="From Date">
                                </div>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <div class="main-selet-11">
                                    <label for="todate">To Date</label>
                                    <input type="text" class="form-control datepicker" name="todate"
                                        id="todate" value="{{ $defaultTo }}" placeholder="To Date">
                                </div>
                            </div>
                            <div class="col-lg-1 col-sm-6" style="align-self: flex-end;">
                                <div class="main-selet-11">
                                    <button class="btn-main-1 search-btn-remi search_user_wise">Search</button>
                                </div>
                            </div>
                        </div>

                        <div class="main-calander-11 mt-2">
                            <div class="page-heading-main justify-content-between align-items-end  mb-0">
                                <div class="left-head-deta">
                                    <div class="custom-entry">
                                        <p>Show</p>
                                        <select id="page_length">
                                            <option value="10">10</option>
                                            <option value="25" selected>25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                            <option value="1000">1000</option>
                                            <option value="2000">2000</option>
                                            <option value="200000000">All</option>
                                        </select>
                                        <p>entries</p>
                                    </div>
                                </div>
                                <div class="right-head-deta">
                                    <div class="table-custom-serch">
                                        <input class="input-main" type="search" id="search_table" placeholder="Search">
                                    </div>
                                    <div>
                                        <a href="javascript:;" class="btn btn-blues btn-pdf"> PDF</a>
                                        <a href="javascript:;" class="btn btn-warning btn-excel"> XLXS</a>
                                    </div>
                                </div>
                            </div>
                            <div class="main-data-teble-1 table-responsive">
                                <table id="user_wise_datatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> Sr.No</th>
                                            <th> User </th>
                                            <th> Company </th>
                                            <th> Email </th>
                                            <th> Mobile </th>
                                            <th> Total Orders </th>
                                            <th> Wallet Cut </th>
                                            <th> Aggregator Charge </th>
                                            <th> Profit </th>
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
</div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        var dataTable = $('#user_wise_datatable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            bLengthChange: false,
            bInfo: true,
            iDisplayLength: 25,
            order: [[0, 'desc']],
            bAutoWidth: false,
            dom: 'Brtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'd-none',
                    text: 'excel',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function (data, row, column, node) {
                                if (node && node.dataset && node.dataset.export) return node.dataset.export;
                                if (data === null || data === undefined) return '';
                                if (typeof data !== 'string') return String(data);
                                data = data.replace(/<\/p\s*>/gi, '\n')
                                           .replace(/<br\s*\/?>/gi, '\n')
                                           .replace(/<p[^>]*>/gi, '');
                                var tmp = document.createElement('div');
                                tmp.innerHTML = data;
                                return (tmp.textContent || tmp.innerText || '').trim();
                            }
                        }
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'd-none',
                    text: 'pdf',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function (data, row, column, node) {
                                if (node && node.dataset && node.dataset.export) return node.dataset.export;
                                if (data === null || data === undefined) return '';
                                if (typeof data !== 'string') return String(data);
                                data = data.replace(/<\/p\s*>/gi, '\n')
                                           .replace(/<br\s*\/?>/gi, '\n')
                                           .replace(/<p[^>]*>/gi, '');
                                var tmp = document.createElement('div');
                                tmp.innerHTML = data;
                                return (tmp.textContent || tmp.innerText || '').trim();
                            }
                        }
                    },
                    customize: function (doc) {
                        var tableBlock = null;
                        for (var i = 0; i < doc.content.length; i++) {
                            if (doc.content[i] && doc.content[i].table) {
                                tableBlock = doc.content[i];
                                break;
                            }
                        }
                        if (!tableBlock || !tableBlock.table || !tableBlock.table.body) {
                            return;
                        }

                        var table = tableBlock.table;
                        table.headerRows = table.headerRows || 1;
                        table.dontBreakRows = true;
                        table.keepWithHeaderRows = 1;
                    }
                }
            ],
            ajax: {
                url: "{{ route('report.user-wise.ajax') }}",
                type: "POST",
                dataType: "json",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('#search_table').val();
                    d.user_id = $('#user_id').val();
                    d.courier_name = $('#courier_name').val();
                    d.status_courier = $('#status_courier').val();
                    d.fromdate = $('#fromdate').val();
                    d.todate = $('#todate').val();
                },
                dataSrc: function(json) {
                    return (json && json.data) ? json.data : [];
                }
            },
            columns: [
                { data: "id" },
                { data: "user_name" },
                { data: "company_name" },
                { data: "email" },
                { data: "mobile" },
                { data: "total_orders" },
                { data: "wallet_cut" },
                { data: "aggregator_charge" },
                { data: "profit" }
            ]
        });

        $('.search_user_wise').click(function() {
            dataTable.draw();
        });

        $('#page_length').change(function() {
            dataTable.page.len($(this).val()).draw();
        });

        var debounceTimer;
        $('#search_table').keyup(function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                dataTable.draw();
            }, 400);
        });

        $('.btn-excel').on('click', function () {
            dataTable.button('.buttons-excel').trigger();
        });

        $('.btn-pdf').on('click', function () {
            dataTable.button('.buttons-pdf').trigger();
        });
    </script>
@endpush
