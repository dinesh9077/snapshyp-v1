@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Passbook Report')
@section('header_title', $user->name)
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
            background-color: #fff !important;
            border: none !important;
            border: 1px solid #E5E7EB !important;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-filter-weight">
                            <div class="row row-re passbookSerachForm order-filter m-0 mb-3">

                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="fromdate">Form Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi  datepicker"
                                            name="fromdate" value="{{ $defaultFrom }}" id="fromdate"
                                            placeholder="From Date">
                                    </div>
                                </div>

                                <div class="col-lg-2 col-sm-6">
                                    <div class="main-selet-11">
                                        <label for="todate">To Date</label>
                                        <input type="text" class="form-control new-height-fcs-rmi  datepicker"
                                            name="todate" value="{{ $defaultTo }}" id="todate"
                                            placeholder="To Date">
                                    </div>
                                </div>

                                <div class="col-lg-2 col-sm-6" style="align-self:end;">
                                    <div class="main-selet-11">
                                        <button type="submit" class="btn-main-1 search_user search-btn-remi">Search</button>
                                    </div>
                                </div>

								  
                            </div>
                        </div>
                        <div class="ordr-main-001">
                            <div class="page-heading-main justify-content-between align-items-end  mb-0">
                                <div class="left-head-deta">
                                    {{-- <div class="custom-entry">
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
                                    </div> --}}
                                </div>
                                <div class="right-head-deta">
                                    {{-- <div class="table-custom-serch">
										<input class="input-main" type="search" id="search_table"  placeholder="Search">
									</div>  --}}
                                    <!-- Totals on the right -->
								<div class="me-lg-auto">
									<div class="d-flex justify-content-end gap-4 totals-wrap">
										<div class="text-end">
											<div class="label">Opening Balance
											<span id="total_opening" class="value tots-am suc">₹0.00</span>
                                            </div>
										</div> 
									</div>
								</div>
								<div class="me-lg-auto">
									<div class="d-flex justify-content-start gap-4 totals-wrap">
										<div class="text-start">
											<div class="label">Closing Balance
											<span id="total_closing" class="value tots-am den">₹0.00</span>
                                            </div>
										</div> 
									</div>
								</div>
                                    <div>
                                        <a href="javascript:;" class="btn btn-blues" id="pdfExport"> PDF</a>
                                        <a href="javascript:;" class="btn btn-warning" id="excelExport"> XLXS</a>
                                    </div>
                                </div>
                            </div>
                            <div class="main-data-teble-1 table-responsive">
                                <table id="wallet_datatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> Sr.No</th> 
											<th> Date </th>
                                            <th> Order ID </th>
											<th> AWB Number </th>
											<th> Courier </th>
											<th> Amount </th>
											<th> No Of Box </th>
											<th> SKU </th>
											<th> Description </th> 
                                            <th> Credit </th>
                                            <th> Debit </th>
                                            <th> Balance </th>  
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
		var userId = @json($user->id);
        var dataTable = $('#wallet_datatable').DataTable({
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
            }, {
                extend: 'pdfHtml5',
                className: 'd-none',
                text: 'excel',
                exportOptions: {
                    modifier: {
                        page: 'current'
                    }
                }
            }],
            "language": {
                'loadingRecords': '&nbsp;',
                'processing': 'Loading...'
            },
            serverSide: true,
            bLengthChange: true,
            searching: false,
            bFilter: false,
            bInfo: true,
			paging: true,
            iDisplayLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, 200, 500, 1000000],
                [10, 25, 50, 100, 200, 500, 'All']
            ], // ðŸ”¥ options shown in dropdown
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            "ajax": {
                "url": "{{ route('report.passbook.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.user_id = userId;
                    d.fromdate = $('.passbookSerachForm #fromdate').val();
                    d.todate = $('.passbookSerachForm #todate').val();
                },
				// Update totals UI
				dataSrc: function(json) {
					const opening_balance = json?.opening_balance ?? 0; 
					const closing_balance = json?.closing_balance ?? 0; 
					$('#total_opening').text(opening_balance); 
					$('#total_closing').text(closing_balance); 
					return json.data || json.aaData || [];
				}
            },
            "columns": [{
                    "data": "id"
                },
				{
                    "data": "created_at"
                },
                {
                    "data": "order_id"
                }, 
				{
                    "data": "awb_number"
                },
				{
                    "data": "courier_name"
                },
				{
                    "data": "product_amount"
                },
				{
                    "data": "no_of_box"
                },
				{
                    "data": "sku_number"
                }, 
				{
                    "data": "note"
                },
				{
                    "data": "credit"
                },
                {
                    "data": "debit"
                },
                {
                    "data": "balance"
                } 
            ]
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

        $('.search_user').click(function() {
            dataTable.draw();
        });

        var debounceTimer;
        $('#search_table').keyup(function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                dataTable.draw();
            }, 400); // Adjust the debounce delay (in milliseconds) as per your preference
        });
    </script>
@endpush
