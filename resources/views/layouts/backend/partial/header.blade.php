<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500&display=swap" rel="stylesheet">

<style>
    .dropdown-item {
        padding: 10px 15px !important;
    }

    .search-container {
        position: relative;
    }

    .search-results-dropdown {
        position: absolute;
        top: 100%;
        /* appear below the input */
        left: 0;
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        /* ensures it’s above header and other elements */
    }

    .custom-search-input {
        background-color: #F5F6F8;
        width: 100%;
        padding: 11.5px 10px 11.5px  43px;
        border-radius: 6px;
        outline: none;
    }

    .search-result-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        background-color: #fff;
        /* ✅ ensure each item is solid */
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .search-result-item:hover {
        background-color: #f2f2f2;
    }

    .search-result-item .order-id strong {
        color: #007bff;
    }

    .search-result-item .awb-number {
        color: #666;
        font-size: 13px;
        margin-left: 5px;
    }

    .search-result-item .order-details {
        display: flex;
        gap: 10px;
        align-items: center;
        font-size: 13px;
        color: #444;
        margin-top: 4px;
    }

    .no-results {
        padding: 10px 15px;
        color: #888;
        text-align: center;
        background: #fff;
    }
</style>
<!-- Topbar Start -->
<div class="navbar-custom">

    <div class="main-mob-menu">
        <button class="button-menu-mobile waves-effect waves-light">
            <i class="fe-menu" style="color: #000 !important; "></i>
        </button>
    </div>

    <div class="page_head ml-1 ml-md-0">
        <h2>@yield('header_title')</h2>
    </div>

    <ul class="list-unstyled topnav-menu float-right mb-0">
        <li style="position: relative;" class="d-xl-flex d-none">
            <div class="custom-search-wrapper">
                <input type="text" class="custom-search-input" id="orderSearch"
                    placeholder="Search Order By AWB Number & Order Id" autocomplete="off">
                <i class="bi bi-search search-icon"></i> <!-- Font Awesome icon -->
            </div>
            <div id="searchResults" class="search-results-dropdown"></div>
        </li>


        @php
            $hasAdminDashboard = config('permission.admin_dashboard.view') || $authUser->isMasterAdmin() || in_array(strtolower((string) $authUser->role), ['admin', 'reseller'], true);
            $showWallet = !$hasAdminDashboard || $authUser->role === 'franchise';
        @endphp

        @if ($showWallet)
        @php
            $headerWalletBalance = $authUser->role === 'franchise'
                ? (float) \App\Models\FranchiseEarning::where('franchise_user_id', $authUser->id)
                    ->whereNull('withdrawal_request_id')
                    ->where('status', 'available')
                    ->sum('amount')
                : (float) ($authUser->wallet_amount ?? 0);
            if ($authUser->role === 'franchise') {
                $authUser->wallet_amount = $headerWalletBalance;
            }
        @endphp
        <li class="head-li moin-bord" onclick="redirectSimpleUrl('{{ $authUser->role === 'franchise' ? route('franchise.withdrawal.index') : route('recharge.wallet') }}')">
            <div class="balcnce-wa">
                <i class="bi bi-wallet2"
                    style="width: auto;padding:5.5px 12px; font-size : 22px;border-radius: 10px 0px 0px 10px; color : #000;"></i>
                <h6 style="margin-right : 10px; color : #000;"> {{ config('setting.currency') }}
                    {{ Helper::decimal_number($headerWalletBalance) }}
                </h6>
            </div>
        </li>
        @endif

        <li class="notification-list mo-none-991">
            <div class="quick-actions-bar">

                <div class="qa-item" onclick="redirectSimpleUrl('{{ url('order/create') }}?weight_order=1')">
                    <i class="bi bi-plus-lg new-color-not-ic"></i>
                    <span class="qa-tooltip">Add an Order</span>
                </div>

                <!-- <span class="qa-divider"></span> -->

                @if (!in_array(strtolower($authUser->role), ['admin', 'franchise', 'reseller', 'staff']))
                <div class="qa-item" onclick="redirectSimpleUrl('{{ url('rate/calculator') }}')">
                    <i class="bi bi-calculator new-color-not-ic"></i>
                    <span class="qa-tooltip">Rate Calculator</span>
                </div>
                @endif

                @if ($authUser->role === 'user')
                <!-- <span class="qa-divider"></span> -->

                <div class="qa-item" onclick="redirectSimpleUrl('{{ url('ticket/add') }}')">
                    <i class="bi bi-ticket-perforated new-color-not-ic"></i>
                    <span class="qa-tooltip">Create Ticket</span>
                </div>
                @endif

                <!-- <span class="qa-divider"></span> -->

                <div class="qa-item" onclick="redirectSimpleUrl('{{ url('order') }}?weight_order=1&status={{ urlencode(ucwords('ready to ship'))}}')">
                  <i class="bi bi-truck new-color-not-ic"></i>
                    <span class="qa-tooltip">Track Shipments</span>
                </div>

            </div>
        </li>



        <li class="dropdown notification-list mo-none waves-effect " style="    overflow: visible;">
            <a class="head-not-btnsnav-link dropdown-toggle head-not-btns  waves-light  " data-toggle="dropdown" href="#"
                role="button" aria-haspopup="false" aria-expanded="false">
              <i class="bi bi-bell new-color-not-ic"></i>
                <span class="badge badge-danger rounded-circle noti-icon-badge notifycount"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-lg" style="border-radius: 10px;overflow: hidden;">

                <!-- item-->
                <div class="dropdown-item noti-title">
                    <h5 class="m-0">
                        <span class="float-right">
                            <a href="{{ route('notification.clear-all') }}" class="text-dark">
                                <small>Clear All</small>
                            </a>
                        </span>Notification
                    </h5>
                </div>
                <div class="slimscroll noti-scroll notifymsg">
                </div>
                <!-- All-->
                <a href="javascript:void(0);" class="dropdown-item text-center text-primary notify-item notify-all">
                </a>
            </div>
        </li>

        <li class="dropdown notification-list mo-none">
            <a class="head-user-btns nav-link dropdown-toggle nav-user  mr-0 waves-effect waves-light" style="padding: 0px 4px !important;border-radius: 10px !important; background-color: #F5F6F8 !important;" data-toggle="dropdown"
                href="#" role="button" aria-haspopup="false" aria-expanded="false">
                @if ($authUser->profile_image)
                <img src="{{ url('storage/profile/' . $authUser->profile_image) }}">
                @else
                <img src="{{ asset('assets/images/profile-logo.png') }}">
                @endif
                <!-- <h6 style="color:#000;">{{ $authUser->name }}</h6> -->
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                <!-- item-->
                <a href="{{ route('profile') }}" class="dropdown-item notify-item">
                    <i class="fe-user"></i>
                    <span> Profile </span>
                </a>

                <div class="dropdown-divider"></div>

                <!-- item-->
                <a href="{{ route('change-password') }}" class="dropdown-item notify-item">
                    <i class="fe-lock"></i>
                    <span> Change Password </span>
                </a>

                <div class="dropdown-divider"></div>

                <!-- item-->
                <a href="{{ route('logout') }}" class="dropdown-item notify-item"
                    onclick="event.preventDefault();
				document.getElementById('logout-form').submit();">
                    <i class="fe-log-out"></i>
                    <span>{{ __('Logout') }}</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</div>
