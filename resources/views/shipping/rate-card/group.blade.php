@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Rate Card')
@section('header_title', 'Rate Card')
@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
    <style>
        .tooltip .tooltiptext {
            text-align: left !important;
            padding: 5px 0 5px 5px !important;
        }

        td p {
            margin-bottom: 0;
        }

        /* Custom Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 34px;
            height: 20px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked+.slider:before {
            -webkit-transform: translateX(14px);
            -ms-transform: translateX(14px);
            transform: translateX(14px);
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="inner-page-heading mb-3">
                            <form method="get" action="" style="width:100%;" class="order-filter">
                                <div class="row row-re">
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="main-selet-11">
                                            <label for="weight">Weight</label>
                                            <select name="weight" class="select2" id="weight">
                                                @foreach ($weights as $weight)
                                                    <option value="{{ $weight }}">{{ $weight }} Kg </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @if (Auth::user()->role !== 'user' && $rateCards)
                                        <div class="col-xl-3 col-md-4 col-sm-6">
                                            <div class="main-selet-11">
                                                <label for="rate_card_id">Rate Card</label>
                                                <select name="rate_card_id" class="select2" id="rate_card_id"
                                                    style="width: 100%;">
                                                    @foreach ($rateCards as $rateCard)
                                                        <option value="{{ $rateCard->id }}">{{ $rateCard->group_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-xl-3 col-md-4 col-sm-6">
                                        <div class="main-selet-11">
                                            <label for="shipping_company_id">Shipping Company</label>
												<select name="shipping_company_id" class="select2" id="shipping_company_id"
													style="width: 100%;">
													<option value="">All</option>
													@foreach ($shippingCompanies as $shippingCompany)
														<option value="{{ $shippingCompany->id }}" {{ (string) request('shipping_company_id') === (string) $shippingCompany->id ? 'selected' : '' }}>{{ $shippingCompany->name }}
														</option>
													@endforeach
												</select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="ordr-main-001">
                            <div class="main-calander-11">
                                <div class="main-data-teble-1 table-responsive">
                                    <table id="ratecard_datatable" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th> Sr.No </th>
                                                <th> Shipping Company </th>
                                                <th> Weight </th>
                                                <th> Heading </th>
                                                <th> Rate </th>
                                                <th> Extra Rate </th>
                                                <th> Cod Charges </th>
                                                <th> Percentage </th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php
                    $assignedB2cRateCardId = auth()->user()->getAssignedRateCardIdByType('b2c');
                    $rateCard = $rateCards->first();
                    if (!in_array($assignedB2cRateCardId, [0, '0', null, 'all'], true)) {
                        $rateCard = $rateCards->where('id', $assignedB2cRateCardId)->first() ?? $rateCard;
                    }
                @endphp
                @if ($rateCard)
                    <div class="main-order-page-1 mt-2">
                        <div class="main-order-001">
                            <div class="mt-4">
                                <h5 class="fw-bold mb-3 text-dark">Value Surcharge and Value Discounts</h5>

                                <div class="alert alert-light border d-flex align-items-center" role="alert"
                                    style="background-color: #f6f7fb; color: #333; font-size: 13px;">
                                    <i class="mdi mdi-information-variant mr-2" style="font-size: 18px;"></i>
                                    <span>All additional percentage based shipping charges/discounts will be applied on the
                                        Selling Price of shipment except RTO charges. RTO percentage charges are based on
                                        base shipping charges.</span>
                                </div>

                                {{-- RTO Based Surcharge --}}
                                <div class="border brd p-2 mb-2 bg-white">
                                    <h6 class="fw-bold mb-2 mt-0" style="font-size: 14px;">RTO Based Surcharge</h6>
                                    <div class="d-flex align-items-center">
                                        <span style="font-size: 13px; color: #555;" id="rto_charge_html">RTO Charges
                                            Percentages
                                            : {{ $rateCard->is_rto_charge ? '100.00' : '0.00' }} % of
                                            forward leg charges</span>
                                    </div>
                                </div>

                                {{-- Volumetric Weight --}}
                                <div class="border brd p-2 mb-2 bg-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 mt-0" style="font-size: 14px;">Volumetric Weight</h6>
                                        {{-- <i class="mdi mdi-information-outline cursor-pointer text-muted" title="Info"></i> --}}
                                    </div>
                                    <div style="font-size: 13px;">
                                        Divisor : <span class="fw-bold">5000</span>
                                    </div>
                                </div>

                                {{-- Prepaid & COD Surcharge/Discount --}}
                                <div class="border brd p-2 mb-2 bg-white">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 mt-0" style="font-size: 14px;">Prepaid & COD
                                            Surcharge</h6>
                                        {{-- <i class="mdi mdi-information-outline cursor-pointer text-muted" title="Info"></i> --}}
                                    </div>
                                    <div class="row" style="font-size: 13px;">
                                        <div class="col-md-12">
                                            <div class="text-muted">COD Charge as mentioned above</div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        var rateCards = @json($rateCards);
        var authUserRateCardId = {{ auth()->user()->getAssignedRateCardIdByType('b2c') ?? 0 }};
        var dataTable = $('#ratecard_datatable').DataTable({
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
                "url": "{{ route('rate.card.group.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.weight = $('#weight').val();
                    d.rate_card_id = $('#rate_card_id').val() ?? authUserRateCardId;
                    d.shipping_company_id = $('#shipping_company_id').val();
                }
            },
            "columns": [{
                    "data": "id"
                },
                {
                    "data": "shipping_company_id"
                },
                {
                    "data": "weight"
                },
                {
                    "data": "heading"
                },
                {
                    "data": "rate"
                },
                {
                    "data": "extra_rate"
                },
                {
                    "data": "cod_charges"
                },
                {
                    "data": "percentage"
                }
            ]
        });

        $('#ratecard_datatable').on('draw.dt', function() {
            $('[data-toggle="tooltip"]').tooltip({
                position: {
                    my: "left bottom", // the "anchor point" in the tooltip element
                    at: "left top", // the position of that anchor point relative to selected element
                }
            });
        });

        $('.search_data').click(function() {
            dataTable.draw();
        });

        $('#rate_card_id, #weight, #shipping_company_id').change(function() {
            dataTable.draw();

            // Filter and check is_rto_charge
            var selectedId = $('#rate_card_id').val();
            if (selectedId && typeof rateCards !== 'undefined') {
                var selectedRateCard = rateCards.find(rc => rc.id == selectedId);
                if (selectedRateCard) {
                    var percentage = selectedRateCard.is_rto_charge == 1 ? '100.00' : '0.00';
                    var html = 'RTO Charges Percentages : ' + percentage + ' % of forward leg charges';
                    $('#rto_charge_html').text(html);
                }
            }
        });
    </script>
@endpush
