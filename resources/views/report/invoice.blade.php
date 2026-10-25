@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Report')
@section('header_title','Report')
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
    min-width:   450px;
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


.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 44px !important;
    background-color: #f3f3f3 !important;
    border: none !important;
}
</style>

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="">
                    <div class="main-filter-weight">
                        <div class="row row-re reportOrderForm order-filter m-0 mb-3">
                            @if(Auth::user()->role != "user")
                            <div class="col-xl-3 col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                <div class="main-selet-11">
                                    <label for="user">User</label>
                                    <select class="select2" id="user_id">
                                        <option value="">All Users</option>
                                        @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                            {{-- Year Dropdown --}}
                            <div class="col-xl-3 col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                <div class="main-selet-11">
                                    <label for="year">Year</label>
                                    <select name="year" class="form-control" id="year">
                                        @php
                                        $currentYear = date('Y');
                                        $startYear = $currentYear - 5;
                                        $endYear = $currentYear + 1;
                                        $selectedYear = request('year', $currentYear);
                                        @endphp
                                        @for ($y = $startYear; $y <= $endYear; $y++) <option value="{{ $y }}"
                                            {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                    </select>
                                </div>
                            </div>

                            {{-- Month Dropdown --}}
                            <div class="col-xl-3 col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2">
                                <div class="main-selet-11">
                                    <label for="month">Month</label>
                                    <select name="month" class="form-control" id="month">
                                        @php
                                        $selectedMonth = request('month', date('m'));
                                        @endphp
                                        @for ($m = 1; $m <= 12; $m++) <option
                                            value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}"
                                            {{ $selectedMonth == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                                            </option>
                                            @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-4 col-sm-6 pl-0 pr-0 pr-sm-2" style="align-self: flex-end;">
                                <div class="main-selet-11">
                                    <button class="btn-main-1 search_data search-btn-remi">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ordr-main-001">
                        <ul id="tab">
                            <li class="active">
                                <div class="main-calander-11">
                                    <div class="main-data-teble-1 table-responsive mt-3">
                                        <div class="page-heading-main justify-content-between align-items-end  mb-0">
                                            <div class="left-head-deta">
                                                <!--<a href="javascript:;" onclick="redirectUrl()" class="btn btn-warning"> XLXS</a> -->
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
                                                    <input class="input-main" type="search" id="search_table"
                                                        placeholder="Search">
                                                </div>
                                            </div>
                                        </div>
                                        <table id="invoice_datatable" class="" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>User Name</th>
                                                    <th>Shipping Company</th>
                                                    <th>Invoice Number</th>
                                                    <th>Invoice State</th>
                                                    <th>Invoice Date</th>
                                                    <th>Invoice Period</th>
                                                    <th>Invoice Amount</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </li>
                        </ul>
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
$(document).ready(function() {
    $('.select2').select2();
});

$(document).ready(function() {
    $('ul.tabs-001 li').click(function() {
        var tab_id = $(this).attr('data-tab');
        $('ul.tabs-001 li').removeClass('current');
        $('.tab-content11').removeClass('current');
        $(this).addClass('current');
        $("#" + tab_id).addClass('current');
    })
})

var dataTable = $('#invoice_datatable').DataTable({
    processing: true,
    "language": {
        'loadingRecords': '&nbsp;',
        'processing': 'Loading...'
    },
    serverSide: true,
    searching: false,
    bLengthChange: false,
    bFilter: true,
    bInfo: true,
    iDisplayLength: 25,
    //   lengthMenu: [[10, 25, 50, 100, 10000000], [ 10, 25, 50, 100, "All"]],
    order: [
        [0, 'desc']
    ],
    bAutoWidth: false,
    "ajax": {
        "url": "{{ route('report.billing-invoice.ajax') }}",
        "dataType": "json",
        "type": "POST",
        "data": function(d) {
            d._token = "{{csrf_token()}}";
            d.search = $('input[type="search"]').val();
            d.user_id = $('.reportOrderForm #user_id').val();
            d.month = $('.reportOrderForm #month').val();
            d.year = $('.reportOrderForm #year').val();
        }
    },
    "columns": [{
            "data": "id"
        },
        {
            "data": "user_name"
        },
        {
            "data": "shipping_company_name"
        },
        {
            "data": "invoice_number"
        },
        {
            "data": "invoice_state"
        },
        {
            "data": "invoice_date"
        },
        {
            "data": "invoice_period"
        },
        {
            "data": "total_amount"
        },
        {
            "data": "action"
        }
    ]
});

$('.search_data').click(function() {
    dataTable.draw();
});

$('#search_table').keyup(function() {
    dataTable.draw();
});

$('#invoice_datatable').on('draw.dt', function() {
    $('[data-toggle="tooltip"]').tooltip({
        position: {
            my: "left bottom", // the "anchor point" in the tooltip element
            at: "left top", // the position of that anchor point relative to selected element
        }
    });
    @if(Auth::user()->role === "user")
    dataTable.columns(1).visible(false);
    @endif
});
</script>
@endpush