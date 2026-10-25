@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Dashboard')
@section('header_title', 'Dashboard')
@section('content')

    <style>
        /* .new-tbs-white td {
                background-color: white !important;
                border-radius: 10px;
            } */

        /* Chart responsiveness for dashboard */
        .lg-chart {
            width: 89%;
            max-width: 100%;
            height: 263px;
            margin: 17px auto;
            overflow: hidden;
            /* margin-top: 5px; */
            position: relative;
        }

        .chart-scroller {
            width: 100%;
            height: 100%;
            overflow-x: auto;
        }

        .lg-chart canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        @media (max-width: 1280px) {
            .lg-chart {
                height: 260px;
            }
        }

        @media (max-width: 768px) {
            .lg-chart {
                height: 220px;
            }
        }

        @media (max-width: 480px) {
            .lg-chart {
                height: 200px;
            }
        }

        .success {
            color: #28a745 !important;
            font-weight: bold;
        }

        .danger {
            color: #dc3545 !important;
            font-weight: bold;
        }

        .warning {
            color: #ffc107 !important;
            font-weight: bold;
        }

        .dd-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .dd-card-link:focus-visible {
            outline: 2px solid #3984FB;
            outline-offset: 2px;
            border-radius: 14px;
        }

        .list-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .list-card-link:focus-visible {
            outline: 2px solid #3984FB;
            outline-offset: 2px;
            border-radius: 10px;
        }


        .clickable-card {
            transition: all 0.2s ease-in-out;
        }

        .clickable-card:hover {
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
    </style>



    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-dashbordr-22 mt-0 pt-1">

                    <div class="row">
                        <div class="col-xl-5 new-vc-card-width">
                            <div class="WC-card-dashpage">
                                <div class="piy-wcc-1">
                                    <div class="wc-dec-card-t">
                                        <h3> Welcome <span style="color:#3984FB; font-weight:bold;">Snapshyp!</span>
                                            Shipment Processing
                                            Dashboard</h3>
                                        <p style="color:black; margin-bottom:0">our shipment is almost ready. Please verify
                                            the details and
                                            complete dispatch.</p>
                                    </div>
                                    <img class="main-dash-img" src="{{ asset('assets/images/vector/wc-img.svg') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-sm-6 new-mms-575">
                            <a href="{{ url('order') }}?weight_order=1&status=All&is_shopify_order=1">
                                <div class="top-dd-cardd overflow-hidden bbs-nn-1">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h6 style="color: black ; margin : 0px;">Total Orders</h6>
                                                <h4 class="fw-semibold dash-amunts-card">{{ $totalShopifyOrder }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c6">
                                                <img src="{{ asset('assets/images/dash-icon/c-6.png') }}" alt="icon">
                                            </div>

                                        </div>
                                        <h5 class="card-last-title"> Shopify Orders </h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-6-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 new-mms-p">
                            <a href="{{ url('order') }}?status=All">
                                <div class="top-dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">

                                            <div>
                                                <h6 style="color: black ; margin : 0px;">Total Orders</h6>
                                                <h4 class="fw-semibold dash-amunts-card">{{ $totalOrders }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c7">
                                                <img src="{{ asset('assets/images/dash-icon/c-7.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Portal Orders </h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-7-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 new-mms-p">
                            <a href="{{ url('order') }}?weight_order=1&status=All&is_wordpress_order=1">
                                <div class="top-dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">

                                            <div>
                                                <h6 style="color: black ; margin : 0px;">Total Orders</h6>
                                                <h4 class="fw-semibold dash-amunts-card">{{ $totalWordPressOrder }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c8">
                                                <img src="{{ asset('assets/images/dash-icon/c-8.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> WordPress Orders </h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-8-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl col-sm-6 mt-3">
                            <a class="dd-card-link" href="{{ route('recharge.wallet') }}">
                                <div class="dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h4 class="fw-semibold dash-amunts-card">{{ config('setting.currency') }} {{
        number_format($availableWalletAmount ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c1">
                                                <img src="{{ asset('assets/images/dash-icon/c-1.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Available Wallet Balance </h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-1-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 mt-3">
                            <a class="dd-card-link"
                                href="{{ auth()->user()->isAdminLevel() ? route('report.passbook.user') : route('report.passbook', auth()->id()) }}">
                                <div class="dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h4 class="fw-semibold dash-amunts-card"> {{ config('setting.currency') }}
                                                    {{
        number_format($totalWalletAmount ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c2">
                                                <img src="{{ asset('assets/images/dash-icon/c-2.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Total Wallet Amount</h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-2-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 mt-3">
                            <a class="dd-card-link"
                                href="{{ auth()->user()->isAdminLevel() ? route('cod-remmitance') : route('user/cod-remittance', ['userId' => auth()->id()]) }}">
                                <div class="dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h4 class="fw-semibold dash-amunts-card"> {{ config('setting.currency') }}
                                                    {{
        number_format($pendingCodRemittance ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c3">
                                                <img src="{{ asset('assets/images/dash-icon/c-3.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Pending COD Remittance</h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-3-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 mt-3">
                            <a class="dd-card-link" href="{{ route('report.billing-invoice') }}">
                                <div class="dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h4 class="fw-semibold dash-amunts-card">{{ config('setting.currency') }} {{
        number_format($totalInvoiceAmount ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c4">
                                                <img src="{{ asset('assets/images/dash-icon/c-4.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Total Invoice Amount</h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-4-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                        <div class="col-xl col-sm-6 mt-3">
                            <a class="dd-card-link" href="{{ route('recharge.list') }}">
                                <div class="dd-cardd overflow-hidden">
                                    <div class="new-card-phome">
                                        <div class="d-flex gap-2 justify-content-between" style="align-items: center;">
                                            <div>
                                                <h4 class="fw-semibold dash-amunts-card">{{ config('setting.currency') }} {{
        number_format($todayRecharge ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="dash-title-cons dash-i-c5">
                                                <img src="{{ asset('assets/images/dash-icon/c-5.png') }}" alt="icon">
                                            </div>
                                        </div>
                                        <h5 class="card-last-title"> Today's Recharge</h5>
                                    </div>
                                    <img src="{{ asset('assets/images/vector/card-5-vec.png') }}" class="last-vec-card"
                                        alt="icon">
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="row ">
                        <div class="col-md-6 mt-3">
                            <div class="main-order-001-1 h-100">
                                <div class="dash-tits-chart">
                                    <h6>Courier Wise Total Count</h6>
                                    <div class="dropdown">
                                        <button class="btn btn-light chart-drop-btn dropdown-toggle"
                                            id="shipmentDropdownBtn" data-bs-toggle="dropdown">
                                            All Couriers
                                        </button>
                                        <ul class="dropdown-menu"
                                            style="border-radius: 10px;overflow: hidden; max-height: 200px; overflow-y: auto;">
                                            <li><a class="dropdown-item" href="javascript:;"
                                                    onclick="updateChart('', 'All Couriers')">All
                                                    Couriers</a>
                                            </li>
                                            @foreach($userCouriers as $courier)
                                                                                <li><a class="dropdown-item" href="javascript:;"
                                                                                        onclick="updateChart('{{ $courier->shipping_company_id }}', '{{ $courier->courier_name }}')">{{
                                                $courier->courier_name }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="lg-chart">
                                    <div class="chart-scroller">
                                        <canvas id="logisticsBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <div class="main-order-001-1 h-100">
                                <div class="dash-tits">
                                    <h6>Shipment Status</h6>
                                </div>
                                <div class="shipment-card-body">
                                    <a class="list-card-link"
                                        href="{{ route('order') }}?weight_order=1&status=All">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-circles-four"></i>
                                                </div>
                                                <h4>All</h4>
                                            </div>
                                            <h5>{{ $totalOrders }}</h5>
                                        </div>
                                    </a>
                                    <a class="list-card-link"
                                        href="{{ route('order') }}?weight_order=1&status=Ready%20To%20Ship">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-folder"></i>
                                                </div>
                                                <h4> Ready To Ship</h4>
                                            </div>
                                            <h5>{{ $readytoship_b2c }}</h5>
                                        </div>
                                    </a>
                                    <a class="list-card-link"
                                        href="{{ route('order') }}?weight_order=1&status=Pending%20Pickup">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-folder-open"></i>
                                                </div>
                                                <h4>Pending Pickup</h4>
                                            </div>
                                            <h5>{{ $manifested_b2c }}</h5>
                                        </div>
                                    </a>
                                    <a class="list-card-link" href="{{ route('order') }}?weight_order=1&status=Cancelled">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-prohibit"></i>
                                                </div>
                                                <h4>Cancelled</h4>
                                            </div>
                                            <h5>{{ $cancelled_b2c }}</h5>
                                        </div>
                                    </a>
                                    <a class="list-card-link"
                                        href="{{ route('order') }}?weight_order=1&status=In%20Transit">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-truck"></i>
                                                </div>
                                                <h4>In-Transit</h4>
                                            </div>
                                            <h5>{{ $inTransit_b2c }}</h5>
                                        </div>
                                    </a>
                                    <a class="list-card-link" href="{{ route('order') }}?weight_order=1&status=RTO%20All">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-align-right"></i>
                                                </div>
                                                <h4>RTO</h4>
                                            </div>
                                            <h5>{{ $rto_b2c }}</h5>
                                        </div>
                                    </a>

                                    <a class="list-card-link" href="{{ route('order') }}?weight_order=1&status=Delivered">
                                        <div class="list-card">
                                            <div class="list-card-title">
                                                <div class="dash-ic">
                                                    <i class="ph-fill ph-sort-descending"></i>
                                                </div>
                                                <h4>Delivered</h4>
                                            </div>
                                            <h5>{{ $delivered_b2c }}</h5>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="row new-re mbd ">
                        <div class="col-md-4 mt-3">
                            <div class="main-order-001-1 h-100 clickable-card" style="cursor: pointer;"
                                onclick="location.href='{{ auth()->user()->isAdminLevel() ? route('cod-remmitance') : route('user/cod-remittance', ['userId' => auth()->id()]) }}'">
                                <div class="dash-tits">
                                    <h6>Remittance & Payout Snapshot</h6>
                                </div>
                                <div class="data-card-body">
                                    <div class="stat-row">
                                        <span class="stat-label">Total COD Amount</span>
                                        <span class="stat-value">{{ config('setting.currency') }}
                                            {{ number_format($totalCodAmount, 2) }}</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Pending COD Remittance</span>
                                        <span class="stat-value"
                                            style="color: #3984FB !important;">{{ config('setting.currency') }}
                                            {{ number_format($pendingCodRemittance, 2) }}</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Last Payout Date & Amount</span>
                                        <span class="stat-value">
                                            @if ($lastPayout)
                                                {{ \Carbon\Carbon::parse($lastPayout->payment_date)->format('d M') }} –
                                                {{ config('setting.currency') }}
                                                {{ number_format($lastPayout->amount_transferred_to_seller, 2) }}
                                            @else
                                                No payout yet
                                            @endif
                                        </span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Upcoming Payout Amount</span>
                                        <span class="stat-value" style="color: #3984FB !important;">
                                            @if ($upcomingPayout)
                                                {{ \Carbon\Carbon::parse($upcomingPayout->payment_scheduled_date)->format('d
                                                    M') }}
                                                –
                                                {{ config('setting.currency') }}
                                                {{ number_format($upcomingPayout->amount_transferred_to_seller, 2) }}
                                            @else
                                                No pending payout
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <div class="main-order-001-1 h-100 ndr-card clickable-card" style="cursor: pointer;"
                                onclick="location.href='{{ route('ndr') }}'">
                                <div>
                                    <div class="dash-tits">
                                        <h6>NDR Status</h6>
                                    </div>

                                    <!-- Client Status -->
                                    <div class="stat-row">
                                        <span class="stat-label">Total NDR Orders</span>
                                        <span class="stat-value">{{ $totalNdrOrders }}</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Resolved NDR</span>
                                        <span class="stat-value"
                                            style="color: #3984FB !important;">{{ $resolvedNdr }}</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">
                                            Pending NDR
                                        </span>
                                        <span class="stat-value" style="color: #3984FB !important;">{{ $pendingNdr }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <div class="main-order-001-1 h-100 clickable-card" style="cursor: pointer;"
                                onclick="location.href='{{ auth()->user()->isAdminLevel() ? route('ticket.admin') : route('ticket') }}'">
                                <div>
                                    <div class="dash-tits">
                                        <h6>Ticket & Support Overview</h6>
                                    </div>

                                    <!-- Client Status -->
                                    <div class="stat-row">
                                        <span class="stat-label">Total Tickets</span>
                                        <span class="stat-value">{{ $totalTickets }}</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label" style="color: #3984FB !important; font-weight : 600;">Open
                                            Tickets</span>
                                        <span class="stat-value">{{ $totalOpenTickets }}</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label"
                                            style="color: #3984FB !important; font-weight : 600;">Closed Tickets</span>
                                        <span class="stat-value"
                                            style="color: #3984FB !important;">{{ $totalClosedTickets }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($authUser->role != 'user')
                        <div class="row">
                            <div class="col-md-4 mt-3">
                                <div class="main-order-001-1 h-100">
                                    <div class="dash-tits">
                                        <h6>High-Risk Delivery Zones</h6>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table client-table">
                                            <thead>
                                                <tr>
                                                    <th>Pincode</th>
                                                    <th>Total Orders</th>
                                                    <th>RTO</th>
                                                    <th>RTO %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($riskPincodes as $row)
                                                    <tr>
                                                        <td><strong>{{ $row->pincode }}</strong></td>
                                                        <td>{{ $row->total_orders }}</td>
                                                        <td class="danger">{{ $row->rto_count }}</td>
                                                        <td class="danger">{{ $row->rto_percent }}%</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center">No risk zones detected</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="main-order-001-1">
                                    <div class="dash-tits">
                                        <h6><a href="{{ route('report.user-wise') }}"
                                                style="color: inherit; text-decoration: none;">Top 10 Clients</a></h6>
                                    </div>
                                    <!-- <ol class="top-client-list">                                                                                                                                                  </ol> -->
                                    <div class="table-responsive">
                                        <table class="table client-table">
                                            <thead>
                                                <tr>
                                                    <th>Client Name</th>
                                                    <th>Total Orders</th>
                                                    <th>Manifested</th>
                                                    <th>In Transit</th>
                                                    <th>Delivered</th>
                                                    <th>RTO</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topClients as $client)
                                                    <tr>
                                                        <td><strong>{{ $client->user->name ?? 'N/A' }}</strong></td>
                                                        <td>{{ number_format($client->total_orders) }}</td>
                                                        <td>{{ number_format($client->manifested_count) }}</td>
                                                        <td>{{ number_format($client->in_transit_count) }}</td>
                                                        <td>{{ number_format($client->delivered_count) }}</td>
                                                        <td>{{ number_format($client->rto_count) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">No client data available</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @if ($authUser->role != 'user')
                                <div class="col-md-4 mt-3">
                                    <div class="main-order-001-1 h-100">

                                        <div class="dash-tits">
                                            <h6><a href="{{ route('users') }}" style="color: inherit; text-decoration: none;">Client
                                                    Insights Panel</a></h6>
                                        </div>

                                        <div class="stat-row clickable-card" style="cursor: pointer;" onclick="location.href='{{ route('users') }}?kyc_status=1'">
                                            <span class="stat-label">Active Clients</span>
                                            <span class="stat-value">{{ $clientStats->approved_clients ?? 0 }}</span>
                                        </div>

                                        <div class="stat-row clickable-card" style="cursor: pointer;" onclick="location.href='{{ route('users.kyc.request') }}'">
                                            <span class="stat-label">Pending KYC Requests</span>
                                            <span class="stat-value warning">{{ $pendingKycCount ?? 0 }}</span>
                                        </div>

                                        <div class="stat-row clickable-card" style="cursor: pointer;" onclick="location.href='{{ route('users') }}?kyc_status=0'">
                                            <span class="stat-label">Inactive Clients</span>
                                            <span class="stat-value">{{ $clientStats->inactive_users ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif



                </div>
            </div>
        </div>


    </div>
    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="hotelInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">Product Details</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="modal-body" id="infoModalBody">
                    <!-- HTML hotel description appears here -->
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade bd-example-modal-lg main-bg0-021 pickupschedulemodal" data-backdrop="static"
        data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header pb-0" style="border: none;">
                    <h5 class="modal-title pick-up0" id="exampleModalLabel"> Shipment Details </h5>
                </div>

                <div class="modal-body not_pickup">
                    <div class="main-0091-text show_msg"> </div>
                    <div class="main-0921-order-text show_pickup_msg"> </div>
                </div>
                <div class="modal-footer" style="margin: auto;border: none;">
                    <a href="{{ route('order') }}?status=Manifested" id="doitlater" class="btn btn-primary simple-021-btn">
                        close </a>
                </div>
            </div>
        </div>
    </div>

    <div class="customization_popup" role="alert">
        <div class="customization_popup_container">
            <a href="#0" class="customization_popup_close img-replace">X</a>
            <div class="main-count-te-order-1 shipmentChargesByAll" style="height: 100%;">
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $('.customization_popup').on('click', function (event) {
            if ($(event.target).is('.customization_popup_close') || $(event.target).is('.customization_popup')) {
                event.preventDefault();
                $(this).removeClass('is-visible');
            }
        });

        function shipNow(obj, event) {
            event.preventDefault();

            const kyc_status = @json($authUser->kyc_status);
            const role = @json($authUser->role);
            if (!['admin', 'franchise'].includes(role) && kyc_status == 0) {
                toastrMsg('error', 'Your order cannot be placed until your KYC is approved.');
                return;
            }

            const orderId = $(obj).attr('data-id');
            const weightOrder = $(obj).attr('data-weight-order');
            run_waitMe($('body'), 1, 'win8');

            $.ajax({
                url: `{{ url('order/shipment/charge') }}/${orderId}?weight_order=${weightOrder}`,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (res) {
                    $('body').waitMe('hide');

                    if (res.status === "success") {
                        $('.customization_popup').addClass('is-visible');
                        $('.shipmentChargesByAll').html(res.view);
                    } else if (res.status === "lbh") {
                        $('#lbhForm').find('#order_id').val(res.order_id);
                        $('#lbhForm').find('#weight').val(res.weight);
                        $('#lbhForm').find('#height').val(res.height);
                        $('#lbhForm').find('#length').val(res.length);
                        $('#lbhForm').find('#width').val(res.width);
                        $('#lbhForm').find('#warehouse_id').val(res.warehouse_id);
                        $('#lbhModal').modal('show');
                    } else {
                        toastrMsg(res.status || 'error', res.msg || 'Something went wrong.');
                    }
                },
                error: function (xhr) {
                    $('body').waitMe('hide');

                    let message = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'Something went wrong.';
                    if (!xhr.responseJSON && xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            message = parsed.msg || parsed.message || message;
                        } catch (error) {
                            if (!xhr.responseText.trim().startsWith('<')) {
                                message = xhr.responseText;
                            }
                        }
                    }

                    toastrMsg('error', message);
                }
            });
        }

        function shipNowOrder(obj, event) {
            event.preventDefault();
            const $obj = $(obj);
            let courierDetail = $obj.attr('data-courier');
            let data;
            try {
                data = JSON.parse(courierDetail);
            } catch (error) {
                toastrMsg('error', 'Something went wrong.');
                return;
            }

            Swal.fire({
                title: "Do you want to ship order?",
                text: "You can't undo this action.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#31ce77",
                cancelButtonColor: "#f34943",
                confirmButtonText: "Yes"
            }).then((result) => {
                if (result.value) {
                    run_waitMe($('body'), 1, 'win8');
                    $.post("{{ route('order.ship.now') }}", {
                        _token: "{{ csrf_token() }}",
                        data: JSON.stringify(data)
                    },
                        function (res) {
                            $('body').waitMe('hide');
                            if (res.status == "warehouse") {
                                $('#warehouse_error').text(res.msg);
                                $('#ware_shipping_id').val(res.shipping_id);
                                $('#vendor_id').val(res.id);
                                $('#address_id').val(res.address_id);
                                $('.addwarehousemodal').modal('show');
                            } else if (res.status == "error") {
                                if (res.wallet === "1") {
                                    Swal.fire({
                                        title: res.msg,
                                        icon: "warning",
                                        showCancelButton: true,
                                        confirmButtonColor: "#31ce77",
                                        cancelButtonColor: "#f34943",
                                        confirmButtonText: "Recharge"
                                    }).then((walletResult) => {
                                        if (walletResult.value) {
                                            $('#rechargeWalletModal').modal('show');
                                        }
                                    });
                                } else {
                                    toastrMsg(res.status, res.msg);
                                }
                            } else {
                                $('.show_msg').html(res.msg);
                                $('#order_id').val(res.order_id);
                                $('#shipping_id').val(res.shipping_id);
                                $('.show_pickup_msg').html(res.pickup_address);
                                $('.pickupschedulemodal').modal('show');
                            }
                        }, 'Json');
                }
            })
        }

        function cancelNewOrder(obj, e) {
            e.preventDefault();
            Swal.fire({
                title: "Are you sure you want to cancel this order?",
                text: "You can't undo this action.",
                type: "warning",
                showCancelButton: !0,
                confirmButtonColor: "#31ce77",
                cancelButtonColor: "#f34943",
                confirmButtonText: "Yes, Cancel!"
            }).then(function (t) {
                if (t.value) {
                    location.href = obj;
                }
            })
        }

        let index = 0;
        const slides = document.querySelectorAll(".welcome-slide");
        const total = slides.length;
        const slider = document.getElementById("slider");

        setInterval(() => {
            index = (index + 1) % total;
            slider.style.transform = `translateX(-${index * 100}%)`;
        }, 3000); // 3 second auto change

        $(document).on('click', '.show-details-btn', function () {
            var items = $(this).data('order'); // JSON string automatically converted by jQuery
            if (typeof items === 'string') {
                items = JSON.parse(items);
            }

            var totalAmount = 0;
            var html =
                "<table class='table table-bordered table-sm'><thead><tr><th>Category</th><th>Name</th><th>SKU</th><th>HSN</th><th>Amount</th><th>Qty</th></tr></thead><tbody>";

            items.forEach(function (item) {
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
            const DATA = {
                b2c: ['B2C Card 1', 'B2C Card 2', 'B2C Card 3', 'B2C Card 4', 'B2C Card 5', 'B2C Card 6']
            };

            let activeTab = 'b2c';
            let slideIndex = 0;
            let autoSlideInterval = null;

            const track = document.getElementById('track');
            const label = document.getElementById('label');

            const MAX_SLIDES = 1; // 6 cards / 3 visible

            /* ---------------- RENDER ---------------- */
            function render() {
                track.innerHTML = '';
                DATA[activeTab].forEach(text => {
                    const c = document.createElement('div');
                    c.className = 'card';
                    c.innerHTML = `<div class="card-inner">${text}</div>`;
                    track.appendChild(c);
                });
                slideIndex = 0;
                updateSlide();
                resetAutoSlide();
            }
            $('#infoModal').modal('show');
        });
    </script>

    <!-- -------for chart -------- -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('logisticsBarChart').getContext('2d');
        let logisticsChart; // Global reference
        let currentCourierFilter = ''; // Store current courier filter
        const dashboardStatusMappings = @json($statusMappings ?? []);

        // Initialize Chart with click functionality
        function initChart(labels, datasets) {

            datasets.forEach(ds => {
                ds.backgroundColor = '#3984FB'; // bar color
                ds.hoverBackgroundColor = '#050505'; // hover color
                ds.borderRadius = 6; // smooth corners
            });
            if (logisticsChart) {
                logisticsChart.destroy();
            }

            logisticsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (event, activeElements) {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const clickedLabel = labels[index];
                            const normalizedLabel = (clickedLabel || '').trim();
                            const isInternalStatus = dashboardStatusMappings.some(
                                (s) => String(s).trim().toLowerCase() === normalizedLabel.toLowerCase()
                            );

                            // Build redirect URL
                            let url = `{{ url('order') }}?weight_order=1&status=`;

                            if (normalizedLabel === 'Total Shipments') {
                                // Total Shipments - show all orders
                                url += 'All';
                            } else if (isInternalStatus) {
                                // Internal status - highlight proper tab
                                url += encodeURIComponent(normalizedLabel);
                            } else {
                                // Non-mapped status - use order_status filter under All
                                url += 'All';
                                url += '&order_status=' + encodeURIComponent(normalizedLabel);
                            }

                            // Add courier filter if active
                            if (currentCourierFilter) {
                                url += '&shipping_company_id=' + encodeURIComponent(currentCourierFilter);
                            }

                            // Redirect to orders page
                            window.location.href = url;
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#000',
                            titleColor: '#fff',
                            bodyColor: '#fff'
                        }
                    },
                    scales: {
                        y: {
                            display: false
                        },
                        x: {
                            ticks: {
                                color: '#000',
                                font: {
                                    size: function (context) {
                                        const w = context.chart.width;
                                        if (w < 480) return 10;
                                        if (w < 768) return 11;
                                        return 12;
                                    }
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Fetch Data from Server
        function updateChart(shipping_company_id = '', labelText = 'All Couriers') {
            // Store current courier filter for click redirects
            currentCourierFilter = shipping_company_id;

            // Update button text
            $('#shipmentDropdownBtn').text(labelText);

            $.post("{{ route('home.chart-data') }}", {
                _token: "{{ csrf_token() }}",
                shipping_company_id: shipping_company_id
            }, function (response) {
                // response should contain labels and datasets
                initChart(response.labels, response.datasets);
            }).fail(function () {
                console.error("Failed to load chart data");
            });
        }

        // Initial Load
        $(document).ready(function () {
            updateChart();
        });
    </script>
    {{-- <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {

                // active tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // content toggle
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                document.querySelector('.' + tab.dataset.tab).style.display = 'block';

                // label text
                document.getElementById('label').innerText =
                    tab.dataset.tab === 'b2b' ?
                        'Recommended for B2B' :
                        'Recommended for B2C';
            });
        });
    </script> --}}


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://unpkg.com/phosphor-icons"></script>
@endpush