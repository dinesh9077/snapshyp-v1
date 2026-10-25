@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipping Charge Report')
@section('header_title', 'Shipping Charge Report')
@section('content')
<style>
.tooltip .tooltiptext {
    text-align: left !important;
    padding: 5px 0 5px 5px !important;
}

td p {
    margin-bottom: 0;
}

.dataTables_length {
    margin-top: 5px;
}

.dataTables_length {
    margin-top: 5px;
}

.totals-wrap {
    display: flex;
    gap: 30px;
    justify-content: flex-end;
}

.total-item {
    text-align: right;
}

.total-item .label {
    font-size: 12px;
    color: #888;
    margin-bottom: 2px;
    font-weight: 500;
}

.total-item .value {
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

#payment_datatable thead th {
    background-color: #f8faff;
    color: #4a4a4a;
    font-weight: 600;
    padding: 12px 25px 12px 12px !important;
    border-top: none;
    font-size: 13px;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
}

#payment_datatable tbody td {
    padding: 12px 15px;
    vertical-align: middle;
}

.text-end {
    text-align: end;
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
            <div class="ordr-main-001">
                <div class="main-filter-weight ">
                    <div class="row row-re paymentSearchForm order-filter m-0 mb-3">
                        @if (Auth::user()->role == 'admin')
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="main-selet-11">
                                <label for="user">User</label>
                                <select name="user" class="form-control" id="user_id">
                                    <option value=""> All Users </option>
                                    @foreach ($users as $user)
                                    <option value="{{ $user->id }}"> {{ $user->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="main-selet-11">
                                <label for="filter_type">Filter Type</label>
                                <select name="filter_type" class="form-control" id="filter_type">
                                    <option value="order_date"> Order Date </option>
                                    <option value="delivery_date"> Delivery Date </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="main-selet-11">
                                <label for="fromdate">From Date</label>
                                <input type="text" class="form-control datepicker " name="fromdate" id="fromdate" placeholder="From Date">
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="main-selet-11">
                                <label for="todate">To Date</label>
                                <input type="text" class="form-control datepicker" name="todate" id="todate" placeholder="To Date">
                            </div>
                        </div>
                        <div class="col-xl-1 col-lg-2 col-md-2 col-sm-6" style="align-self: flex-end;">
                            <div class="main-selet-11">
                                <button type="button" class="btn-main-1 search_user search-btn-remi">Search</button>
                            </div>
                        </div>
                        <!-- Totals on the right -->
                        <div class="col-xl-3 ms-auto" style="align-self: flex-start;">
                            <div class="totals-wrap mt-1">
                                <div class="total-item">
                                    <div class="label">Total Shipping Charge</div>
                                    <div id="total_shipping" class="value">0.00</div>
                                </div>
                                <div class="total-item">
                                    <div class="label">Total Commission(Net Profit)</div>
                                    <div id="total_profit" class="value">0.00</div>
                                </div>
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
                                    <a href="javascript:;" class="btn btn-blues" id="pdfExport"> PDF</a>
                                    <a href="javascript:;" class="btn btn-warning" id="excelExport"> XLXS</a>
                                </div>
                            </div>
                        </div>
                        <div class="main-data-teble-1 table-responsive">
                            <table id="payment_datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th> Sr.No</th>
                                        <th> Order Date</th>
                                        <th> Delivery Date</th>
                                        <th> Seller Details </th>
                                        <th> Order details </th>
                                        <th> Shipping details</th>
                                        <th> Aggregator Charge </th>
                                        <th> Commission Charge(Net Profit) </th>
                                        <th> Shipping Charge </th>
                                        <th> Profit %</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection
    @push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

    <!-- JSZip for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- pdfmake for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Buttons HTML5 export -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <!-- Buttons print option (optional) -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script>
    var dataTable = $('#payment_datatable').DataTable({
        processing: true,
        dom: 'Bfrtip',
        buttons: [{
                extend: 'excelHtml5',
                text: 'Excel',
                className: 'd-none',
                exportOptions: {
                    columns: ':visible', // adjust as needed
                    format: {
                        body: function(data, row, column, node) {
                            if (data === null || data === undefined) return '';
                            // If node is provided and has data-export attribute, prefer that
                            if (node && node.dataset && node.dataset.export) return node.dataset.export;

                            // Convert HTML -> plain text, preserve <p> and <br> as new lines
                            if (typeof data === 'string') {
                                // replace paragraph end and <br> with newline, remove opening <p>
                                data = data.replace(/<\/p\s*>/gi, '\n').replace(/<br\s*\/?>/gi, '\n')
                                    .replace(/<p[^>]*>/gi, '');
                                // strip remaining tags
                                var tmp = document.createElement('div');
                                tmp.innerHTML = data;
                                var text = tmp.textContent || tmp.innerText || '';
                                return text.trim();
                            }
                            return data;
                        }
                    }
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                className: 'd-none',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            if (data === null || data === undefined) return '';
                            // Prefer data-export attribute (plain text) if provided on cell
                            if (node && node.dataset && node.dataset.export) return node.dataset.export;
                            // Convert HTML -> plaintext and preserve <p> and <br> as \n
                            if (typeof data === 'string') {
                                data = data.replace(/<\/p\s*>/gi, '\n').replace(/<br\s*\/?>/gi, '\n')
                                    .replace(/<p[^>]*>/gi, '');
                                var tmp = document.createElement('div');
                                tmp.innerHTML = data;
                                return (tmp.textContent || tmp.innerText || '').trim();
                            }
                            return String(data);
                        }
                    }
                },
                customize: function(doc) {
                    // compact page
                    doc.pageMargins = [10, 10, 10, 10];
                    doc.defaultStyle.fontSize = 8;
                    doc.styles.tableHeader.fontSize = 9;
                    // find table node safely
                    var tableBlock = doc.content.find(function(c) {
                        return c && c.table;
                    });
                    if (!tableBlock) return;

                    var table = tableBlock.table;
                    table.headerRows = 1;
                    table.dontBreakRows = true; // try to keep rows intact
                    table.keepWithHeaderRows = 1;

                    // set widths: try to let pdfMake auto-size with some fixed small widths for narrow cols
                    var colCount = table.body[0].length;
                    // build widths with most columns flexible
                    var widths = [];
                    for (var i = 0; i < colCount; i++) {
                        if (i === 0) widths.push(30); // Sr.No
                        else if (i === 1) widths.push(70); // date
                        else if (i === colCount - 1) widths.push(60); // last column small
                        else widths.push('*'); // flexible others
                    }
                    table.widths = widths;

                    // compact layout
                    tableBlock.layout = {
                        hLineWidth: function() {
                            return 0.3;
                        },
                        vLineWidth: function() {
                            return 0.3;
                        },
                        paddingLeft: function() {
                            return 3;
                        },
                        paddingRight: function() {
                            return 3;
                        },
                        paddingTop: function() {
                            return 2;
                        },
                        paddingBottom: function() {
                            return 2;
                        },
                        fillColor: function(rowIndex) {
                            return (rowIndex % 2 === 0) ? null : '#f7f7f7';
                        }
                    };

                    // If some text is too long, reduce global font slightly
                    doc.defaultStyle.fontSize = 8;
                }
            },
        ],

        "language": {
            'loadingRecords': '&nbsp;',
            'processing': 'Loading...'
        },
        "language": {
            'loadingRecords': '&nbsp;',
            'processing': 'Loading...'
        },
        serverSide: true,
        bLengthChange: false,
        searching: false,
        bFilter: true,
        bInfo: true,
        iDisplayLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, 200, 500, 1000000],
            [10, 25, 50, 100, 200, 500, 'All']
        ], // 🔥 options shown in dropdown
        order: [
            [0, 'desc']
        ],
        bAutoWidth: false,
        "ajax": {
            "url": "{{ route('report.shipping-charge.ajax') }}",
            "dataType": "json",
            "type": "POST",
            "data": function(d) {
                d._token = "{{ csrf_token() }}";
                d.search = $('input[type="search"]').val();
                d.user_id = $('.paymentSearchForm #user_id').val();
                d.shipping_company_id = $('.paymentSearchForm #shipping_company_id').val();
                d.filter_type = $('.paymentSearchForm #filter_type').val();
                d.fromdate = $('.paymentSearchForm #fromdate').val();
                d.todate = $('.paymentSearchForm #todate').val();
            },
            // Update totals UI
            dataSrc: function(json) {
                const ship = json?.totals?.total_shipping ?? 0;
                const prof = json?.totals?.total_profit ?? 0;
                $('#total_shipping').text(ship.toFixed(2));
                $('#total_profit').text(prof.toFixed(2));
                return json.data || [];
            }
        },

        "columns": [{
                "data": "id"
            },
            {
                "data": "order_date"
            },
            {
                "data": "delivery_date"
            },
            {
                "data": "seller_details"
            },
            {
                "data": "order_details"
            },
            {
                "data": "shippings"
            },
            {
                "data": "aggregator_charges"
            },
            {
                "data": "commission_charges"
            },
            {
                "data": "shipping_charges"
            },
            {
                "data": "profit_percent"
            }
        ],
        drawCallback: function() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $("#excelExport").on("click", function() {
        $(".buttons-excel").trigger("click");
    });

    $("#pdfExport").on("click", function() {
        $(".buttons-pdf").trigger("click");
    });

    $('.search_user').click(function() {
        dataTable.draw();
    });

    $('#page_length').change(function() {
        dataTable.page.len($(this).val()).draw();
    });

    $('.clear_filters').click(function() {
        $('.paymentSearchForm #user_id').val('').trigger('change');
        $('.paymentSearchForm #shipping_company_id').val('').trigger('change');
        $('.paymentSearchForm #filter_type').val('order_date').trigger('change');
        $('.paymentSearchForm #fromdate').val('');
        $('.paymentSearchForm #todate').val('');
        $('#search_table').val('');
        dataTable.draw();
    });

    var debounceTimer;
    $('#search_table').keyup(function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            dataTable.draw();
        }, 400); // Adjust the debounce delay (in milliseconds) as per your preference
    });

    $(document).on('click', '.show-details-btn', function() {
        var items = $(this).data('order'); // JSON string automatically converted by jQuery
        if (typeof items === 'string') {
            items = JSON.parse(items);
        }

        var totalAmount = 0;
        var html =
            "<table class='table table-bordered table-sm'><thead><tr><th>Category</th><th>Name</th><th>SKU</th><th>HSN</th><th>Amount</th><th>Qty</th></tr></thead><tbody>";

        items.forEach(function(item) {
            html += "<tr>" +
                "<td>" + item.product_category + "</td>" +
                "<td>" + item.product_name + "</td>" +
                "<td>" + item.sku_number + "</td>" +
                "<td>" + item.hsn_number + "</td>" +
                "<td>" + item.amount + "</td>" +
                "<td>" + item.quantity + "</td>" +
                "</tr>";
            totalAmount += parseFloat(item.amount * item.quantity) || 0;
        });

        html += "</tbody>";
        html += "<tfoot><tr><th colspan='4'>Total</th><th colspan='2'>" + totalAmount.toFixed(2) +
            "</th></tr></tfoot>";
        html += "</table>";

        $('#infoModalLabel').html("Product Details");
        $('#infoModalBody').html(html);
        $('#infoModal').modal('show');
    });
    </script>
    @endpush