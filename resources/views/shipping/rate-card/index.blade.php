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

        .btn-tab {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            font-weight: 500;
            background: transparent;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
        }

        .btn-tab:hover {
            color: var(--new-color-prime);
            border-bottom: 3px solid var(--new-color-prime);
        }

        .btn-tab.active {
            color: #fff;
            border-bottom: 3px solid var(--new-color-prime);
            background: var(--new-color-prime) !important;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        {{-- <div class="row mb-3">
                            <div class="col-12" style="padding : 0px; border-bottom: 1px solid #dee2e6;">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn-tab tab-toggle active" data-type="b2c">B2C Rate
                                        Card</button>
                                    <button type="button" class="btn-tab tab-toggle" data-type="b2b">B2B Rate Card</button>
                                </div>
                            </div>
                        </div> --}}

                        <div class="inner-page-heading d-flex justify-content-end mb-3">
                            <div class="order-111-add">
                                <a href="{{ route('rate.card.add') }}?rate_type=b2c{{ request('embed') ? '&embed=' . request('embed') : '' }}"
                                    class="add-rate-link"> <button class="btn-main-1 add-btn"> + Add Rate </button> </a>
                            </div>
                        </div>
                        <div class="ordr-main-001">
                            <div class="main-calander-11">
                                <div class="main-data-teble-1 table-responsive">
                                    <table id="ratecard_datatable" class="" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th> Sr.No</th>
                                                <th> Group Name</th>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
    <script>
        var current_rate_type = "b2c";
        var addRateBaseUrl = "{{ route('rate.card.add') }}";
        var embedMode = "{{ request('embed', '') }}";
        $('.add-rate-link').attr('href', addRateBaseUrl + '?rate_type=' + current_rate_type + (embedMode ? '&embed=' + encodeURIComponent(embedMode) : ''));
        $('.tab-toggle').removeClass('active');
        $('.tab-toggle[data-type="' + current_rate_type + '"]').addClass('active');
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
                "url": "{{ route('rate.card.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function (d) {
                    d._token = "{{csrf_token()}}";
                    d.rate_type = current_rate_type;
                    d.search = d.search.value;
                    d.embed = embedMode;
                }
            },
            "columns": [{
                "data": "id"
            },
            {
                "data": "group_name"
            },
            {
                "data": "action"
            }
            ]
        });

        $('#ratecard_datatable').on('draw.dt', function () {
            $('[data-toggle="tooltip"]').tooltip({
                position: {
                    my: "left bottom", // the "anchor point" in the tooltip element
                    at: "left top", // the position of that anchor point relative to selected element
                }
            });
        });

        $(document).on('click', '.delete-rate-card-btn', function (e) {
            e.preventDefault(); // prevent navigation
            const url = $(this).attr('href');

            if (!confirm('Are you sure you want to delete this rate card?')) return;

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (res) {
                    if (res.status === 'success') {
                        dataTable.ajax.reload();
                    } else {
                        alert('Error: ' + res.msg);
                    }
                },
                error: function (err) {
                    alert('Something went wrong. Please try again.');
                    console.error(err.responseText);
                }
            });
        });

        // Tab Toggle Logic
        $('.tab-toggle').on('click', function () {
            current_rate_type = $(this).data('type');
            $('.tab-toggle').removeClass('active');
            $(this).addClass('active');
            $('.add-rate-link').attr('href', addRateBaseUrl + '?rate_type=' + current_rate_type + (embedMode ? '&embed=' + encodeURIComponent(embedMode) : ''));
            const nextUrl = new URL(window.location.href);
            nextUrl.searchParams.set('type', current_rate_type);
            if (embedMode) {
                nextUrl.searchParams.set('embed', embedMode);
            }
            window.history.replaceState({}, '', nextUrl.toString());
            dataTable.ajax.reload();
        });

    </script>
@endpush