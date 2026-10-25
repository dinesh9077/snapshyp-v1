@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Passbook Report')
@section('header_title', 'Passbook Report')
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
            flex-wrap: wrap;
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
          .table-custom-serch .input-main {
            min-width:  450px;
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
            line-height: 38px !important;
            background-color: #fff !important;
            border: none !important;
             border: 1px solid #E5E7EB !important;
            text-align: left;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-filter-weight">
                            <div class="row row-re passbookSerachForm mb-3 order-filter">
								<div class="col-lg-2 col-sm-6">
									<div class="main-selet-11">
                                        <label for="user">User</label>
										<select class="select2 form-control" name="user" id="user_id">
											<option value="">Select User</option>
											@foreach ($users as $user)
												<option value="{{ $user->id }}"
													{{ request('user') == $user->id ? 'selected' : '' }}>
													{{ $user->name }}</option>
											@endforeach
										</select>
									</div>
								</div> 
								<!-- Totals on the right -->
								<div class="col-lg-10 ms-lg-auto">
									<div class="d-flex justify-content-end gap-4 totals-wrap">
										<div class="text-end">
											<div class="label">Total Wallet Amount:
											<span id="total_wallet" class="value tots-am"> ₹0.00</span>
                                            </div>
										</div> 
									</div>
								</div> 
                            </div>
							
                        </div>
                        <div class="ordr-main-001">
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
										<input class="input-main" type="search" id="search_table"  placeholder="Search">
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
                                            <th> User Name </th>
                                            <th> User Email </th>
                                            <th> Wallet Balance </th>  
                                            <th> Action </th>
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
        var dataTable = $('#wallet_datatable').DataTable({
            processing: true,
            dom: 'Bfrtip',
          	buttons: [
			{
				extend: 'excelHtml5',
				className: 'd-none',
				text: 'excel',
				exportOptions: {
				columns: ':visible',
				format: {
					body: function (data, row, column, node) {
					// prefer data-export attribute if provided (plain text for export)
					if (node && node.dataset && node.dataset.export) return node.dataset.export;
					if (data === null || data === undefined) return '';
					if (typeof data !== 'string') return String(data);
					data = data.replace(/<\/p\s*>/gi, '\n').replace(/<br\s*\/?>/gi, '\n').replace(/<p[^>]*>/gi, '');
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
					data = data.replace(/<\/p\s*>/gi, '\n').replace(/<br\s*\/?>/gi, '\n').replace(/<p[^>]*>/gi, '');
					var tmp = document.createElement('div');
					tmp.innerHTML = data;
					return (tmp.textContent || tmp.innerText || '').trim();
					}
				}
				},
				customize: function (doc) {
				// Defensive: find the first table node (don't assume index)
				var tableBlock = null;
				for (var i = 0; i < doc.content.length; i++) {
					if (doc.content[i] && doc.content[i].table) {
					tableBlock = doc.content[i];
					break;
					}
				}
				if (!tableBlock || !tableBlock.table || !tableBlock.table.body) {
					// nothing to customize — avoid crash
					return;
				}

				var table = tableBlock.table;
				// Ensure headerRows set
				table.headerRows = table.headerRows || 1;
				table.dontBreakRows = true;
				table.keepWithHeaderRows = 1;

				// Ensure no undefined/null cells in body — replace with empty text objects
				var colCount = (table.body[0] || []).length;
				for (var r = 0; r < table.body.length; r++) {
					if (!Array.isArray(table.body[r])) continue;
					for (var c = 0; c < colCount; c++) {
					var cell = table.body[r][c];
					if (cell === null || cell === undefined) {
						table.body[r][c] = { text: '' };
						continue;
					}
					// Normalize string cells into objects (helps pdfMake)
					if (typeof cell === 'string') {
						table.body[r][c] = { text: cell };
						cell = table.body[r][c];
					}
					}
				}

				// Build proper widths array that matches column count.
				// Use flexible widths '*' for most and small fixed widths for some columns if desired.
				var widths = [];
				for (var i = 0; i < colCount; i++) {
					// example: make first column narrow, last column narrow, others flexible
					if (i === 0) widths.push(40);
					else if (i === colCount - 1) widths.push(80);
					else widths.push('*');
				}
				// Guarantee lengths match
				if (widths.length === colCount) {
					table.widths = widths;
				} else {
					// fallback: let pdfMake auto-size
					table.widths = new Array(colCount).fill('*');
				}

				// Compact layout
				tableBlock.layout = {
					fillColor: function (rowIndex) { return (rowIndex % 2 === 0) ? null : '#f7f7f7'; },
					hLineWidth: function () { return 0.3; },
					vLineWidth: function () { return 0.3; },
					paddingTop: function () { return 4; },
					paddingBottom: function () { return 4; },
					paddingLeft: function () { return 4; },
					paddingRight: function () { return 4; }
				};

				// Optional: global font tweak if needed
				doc.defaultStyle = doc.defaultStyle || {};
				doc.defaultStyle.fontSize = 8;
				doc.styles = doc.styles || {};
				doc.styles.tableHeader = doc.styles.tableHeader || {};
				doc.styles.tableHeader.fontSize = 9;
				}
			}
			],

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
            ], // ðŸ”¥ options shown in dropdown
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            "ajax": {
                "url": "{{ route('report.passbook-user.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.user_id = $('.passbookSerachForm #user_id').val(); 
                },
				// Update totals UI
				dataSrc: function(json) {
					const total_wallet_amount = json?.total_wallet_amount ?? 0; 
					$('#total_wallet').text(total_wallet_amount); 
					return json.aaData || [];
				}
            },
            "columns": [{
                    "data": "id"
                },
                {
                    "data": "name"
                },
                {
                    "data": "email"
                },
                {
                    "data": "balance"
                }, 
                {
                    "data": "action"
                }
            ]
        });

        $("#excelExport").on("click", function() {
            $(".buttons-excel").trigger("click");
        });

        $("#pdfExport").on("click", function() {
            $(".buttons-pdf").trigger("click");
        });

		$(document).on('change', '#user_id', function () {
			dataTable.draw();   // or dataTable.ajax.reload();
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
