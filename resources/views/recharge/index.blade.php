@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Recharge History')
@section('header_title', 'Recharge History')
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

        @media(max-width : 575px) {
            .right-head-deta {
                flex-direction: column;
            }
        }

        .table-custom-serch .input-main {
            min-width:  450px;
        }

        @media(max-width : 991px) {
            .table-custom-serch .input-main {
                min-width: 200px;
            }
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
            padding: 10px 20px;
            height: 41px;
        }

        .custom-entry p {
            margin: 0;
            font-size: 14px;
            color: #0A1629;
            font-weight: 500;
        }
           .custom-entry select{
                border-radius: var(--default-radius);
                background-color: transparent;
                color: #000;
                padding: 4px 9px;
                appearance: none;
                text-align: center;
                font-weight: 400;
                margin: 0 4px;
                border: 1px solid #bcbcbc;
           }
    #recharge-datatable thead th {
    padding-right: 25px !important;
    white-space: nowrap;
}
</style>
    <div class="content-page">
        <div class="content"> 
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001 mb-2">
                        <div class="main-filter-weight">
                            <div class="row row-re reportOrderForm order-filter m-0 mb-2">
                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                            <option value="">All Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Rejected">Rejected</option>
                                            <option value="Paid">Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="payment_mode">Payment Mode</label>
                                        <select name="payment_mode" id="payment_mode" class="form-control select2">
                                            <option value="">All Pyament Mode</option>
                                            <option value="Manual">Manual</option>
                                            <option value="QR Code (Intent)">QR Code (Intent)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="fromdate">From Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi datepicker"
                                            name="fromdate" value="{{ request('fromdate') }}" id="fromdate"
                                            placeholder="From Date">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="todate">To Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi datepicker"
                                            name="todate" value="{{ request('todate') }}" id="todate"
                                            placeholder="To Date">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6" style="align-self: flex-end;">
                                    <div class="main-selet-11">
                                        <button class="btn-main-1 search_data search-btn-remi">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="main-create-order ">
                            <div class="main-data-teble-1 table-responsive">
                                <div class="page-heading-main justify-content-between align-items-end mt-2">
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
                                <table id="recharge-datatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> SR.No </th>
                                            <th> Date </th>
                                            <th> Transaction Type </th>
                                            <th> Amount </th>
                                            <th> Order Id </th>
                                            <th> Txn No. </th>
                                            <th> Utr No </th>
                                            <th> Payment Mode </th>
                                            <th> PG Name </th>
                                            <th> Payment status </th>
                                            {{-- <th> Notes </th>   --}}
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

@endsection
@push('js')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
 
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- Buttons + deps -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> <!-- Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script> <!-- PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script>
        var dataTable = $('#recharge-datatable').DataTable({
            processing: true,
            dom: 'Bfrtip',
            buttons: [{
                    extend: 'excelHtml5',
                    className: 'd-none',
                    text: 'excel',
                    exportOptions: {
                        modifier: {
                            page: 'current'
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
						columns: ':visible:not(:last-child)', // ✅ exclude action column
						format: {
						body: function (data) {
							if (data === null || data === undefined) return '';
							if (typeof data !== 'string') return String(data);
							return data.replace(/<[^>]*>/g, '').replace(/\r?\n|\r/g, ' ').trim();
						}
						}
					},
					customize: function (doc) {
						doc.pageMargins = [12, 12, 12, 12];
						doc.defaultStyle.fontSize = 8;
						doc.styles.tableHeader.fontSize = 9;

						// Find the table node safely
						var tableBlock = doc.content.find(c => c && c.table);
						if (!tableBlock) return;

						var table = tableBlock.table;
						if (!table.body || !table.body.length) return;

						table.headerRows = 1;
						table.dontBreakRows = true;
						table.keepWithHeaderRows = 1;

						// ✅ Adjusted compact widths (no Action column)
						// [id, created_at, amount_type, amount, order_id, txn_number, utr_no, payment_mode, pg_name, transaction_status]
						var compact = [35, 70, 65, 45, 100, 90, 80, 65, 60, 70];
						var colCount = table.body[0].length;
						if (compact.length !== colCount) {
						compact = compact.slice(0, colCount);
						while (compact.length < colCount) compact.push('*');
						}
						table.widths = compact;

						// No-wrap for IDs
						for (var r = 0; r < table.body.length; r++) {
						for (var c = 0; c < colCount; c++) {
							var cell = table.body[r][c];
							if (cell === null || cell === undefined)
							table.body[r][c] = { text: '' };
							if (typeof table.body[r][c] === 'string')
							table.body[r][c] = { text: table.body[r][c] };

							// Prevent wrapping for IDs
							if (r > 0 && (c === 4 || c === 5 || c === 6)) {
							table.body[r][c].noWrap = true;
							table.body[r][c].alignment = 'left';
							}
						}
						}

						// Compact layout
						tableBlock.layout = {
						fillColor: i => (i % 2 === 0 ? null : '#f7f7f7'),
						hLineWidth: () => 0.3,
						vLineWidth: () => 0.3,
						paddingTop: () => 2,
						paddingBottom: () => 2,
						paddingLeft: () => 3,
						paddingRight: () => 3
						};
					}
					}
            ],
            language: {
                loadingRecords: '&nbsp;',
                processing: 'Loading...'
            },
            serverSide: true,
            bLengthChange: false,
            searching: false,
            bFilter: true,
            bInfo: true,
            iDisplayLength: 25,
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            ajax: {
                url: "{{ route('recharge.list.ajax') }}",
                dataType: "json",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.status = $('.reportOrderForm #status').val();
                    d.fromdate = $('.reportOrderForm #fromdate').val();
                    d.todate = $('.reportOrderForm #todate').val();
                    d.payment_mode = $('.reportOrderForm #payment_mode').val();
                }
            },
            columns: [{
                    data: "id"
                },
                {
                    data: "created_at"
                },
                {
                    data: "amount_type"
                },
                {
                    data: "amount"
                },
                {
                    data: "order_id"
                },
                {
                    data: "txn_number"
                },
                {
                    data: "utr_no"
                },
                {
                    data: "payment_mode"
                },
                {
                    data: "pg_name"
                },
                {
                    data: "transaction_status"
                }
            ]
        });




        $('.search_data').click(function() {
            dataTable.draw();
        });

        $("#excelExport").on("click", function() {
            $(".buttons-excel").trigger("click");
        });

        $("#pdfExport").on("click", function() {
            $(".buttons-pdf").trigger("click");
        });

        $('#page_length').change(function() {
            dataTable.page.len($(this).val()).draw();
        })

        var debounceTimer;
        $('#search_table').keyup(function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                dataTable.draw();
            }, 400); // Adjust the debounce delay (in milliseconds) as per your preference
        });
    </script>
@endpush
