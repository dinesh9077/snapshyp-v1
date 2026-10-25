<style>
    .head-li.mail.mob-money .btn.btn-primary.btn-closee-1 {
        width: 100%;
        background: #4351f3;
    }

    .head-li.mail.mob-money {
        margin: 15px 0 !important;
        display: none;
    }

    @media(max-width: 991px) {
        .head-li.mail.mob-money {
            display: block;
        }
    }

    .logo-box .user1 img {
        width: 200px;
    }


    @media (max-width: 1200px) {
        .logo-box .user1 img {
            width: 156px;
        }
    }

    @media(max-width: 991px) {
        .logo-box .user1 {
            height: 62px;
        }

        .logo-box .user1 .sidebar-logo-default {
            height: 36px;
        }

        .left-side-menu:hover .logo-box .user1 .sidebar-logo-default {
            opacity: 1;
            transform: none;
        }

        .left-side-menu:hover .logo-box .user1 .sidebar-logo-hover {
            opacity: 1;
            transform: none;
        }
    }
</style>
<!-- ========== Left Sidebar Start ========== -->
<div class="new_sidebar">

    <div class="">
        <div class="logo-box">
            <a href="{{ url('') }}" class="logo text-center">
                <div class="user1">
                    @php
                        $smallLogo = config('setting.small_header_logo');
                        $headerLogo = config('setting.header_logo');
                        $loginLogo = config('setting.login_logo');

                        // Fallback logic: if desired logo missing, try login logo, else placeholder
                        $smallLogoUrl = !empty($smallLogo) ? asset('storage/settings/' . $smallLogo) : asset('assets/images/logo-placeholder.png');
                        $headerLogoUrl = !empty($headerLogo) ? asset('storage/settings/' . $headerLogo) : (!empty($loginLogo) ? asset('storage/settings/' . $loginLogo) : asset('assets/images/logo-placeholder.png'));
                    @endphp
                    <!-- default small logo shown normally, full logo shown on hover -->
                    <!-- <img src="{{ $smallLogoUrl }}" alt="Small Logo"
                        class="sidebar-logo sidebar-logo-default"> -->
                    <img src="{{ $headerLogoUrl }}" alt="Full Logo" class="sidebar-logo sidebar-logo-hover">
                    <!-- <img src="{{ asset('assets/images/logo1/shipfigo-expand-logo.png') }}"   class="sidebar-logo sidebar-logo-default">
                            <img src="{{ asset('assets/images/logo1/shifigo-logo.png') }}"  class="sidebar-logo sidebar-logo-hover"> -->
                </div>
            </a>
        </div>
        <!--- Sidemenu -->
        <div id="sidebar-menu">

            <ul class="metismenu p-2" id="side-menu">
                <h6 class="group-title-main ">OVERVIEW</h6>
                @if (Auth::user()->role == 'user')
                    @include('layouts.backend.partial.user-sidebar')
                @else
                    @php
                        $hasAdminDashboard = config('permission.admin_dashboard.view') || Auth::user()->isMasterAdmin() || in_array(strtolower((string) Auth::user()->role), ['admin', 'reseller', 'franchise'], true);
                    @endphp
                    @if ($hasAdminDashboard)
                        <li>
                            <a href="{{ url('home') }}" class="{{ request()->is('home*') ? 'active' : '' }}">
                                <i class="mdi mdi-view-dashboard"></i>
                                <span> Dashboard </span>
                            </a>
                        </li>
                    @endif
                    <div class="group-lines"></div>
                    <h6 class="group-title-main ">SHIPMENT MANAGEMENT</h6>
                    @if (config('permission.order.view'))
                        <li>
                            <a href="{{ route('order') }}?weight_order=1&status=New"
                                class="{{ request()->is(['order', 'order/create', 'order/edit*', 'order/details*', 'order/clone*', 'order/label*', 'order/tracking-history*', 'order/shipping-label*']) && request('weight_order') != 2 ? 'active' : '' }}">
                                <i class="mdi mdi-package"></i>
                                <span> Order </span>
                            </a>
                        </li>
                        {{-- <li>
                            <a href="{{ route('order') }}?weight_order=2&status=New"
                                class="{{ request()->is(['order', 'order/create', 'order/edit*', 'order/details*', 'order/clone*', 'order/label*', 'order/tracking-history*', 'order/shipping-label*']) && request('weight_order') == 2 ? 'active' : '' }}">
                                <i class="mdi mdi-package-variant"></i>
                                <span> B2B Order </span>
                            </a>
                        </li> --}}

                        {{-- <li class="{{ request()->is('order*') && request('weight_order') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);"
                                class="{{ request()->is('order*') && request('weight_order') ? 'active' : '' }}">
                                <i class="mdi mdi-package"></i>
                                <span> Order </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level {{ request()->is('order*') && request('weight_order') ? 'mm-collapse mm-show' : '' }}"
                                aria-expanded="false">
                                <li class="{{ request()->is('order*') && request('weight_order') == 1 ? 'mm-active' : '' }}">
                                    <a class="{{ request()->is('order*') && request('weight_order') == 1 ? 'active' : '' }}"
                                        href="{{ route('order') }}?weight_order=1&status=New">
                                        B2C Order
                                    </a>
                                </li>
                                <li class="{{ request()->is('order*') && request('weight_order') == 2 ? 'mm-active' : '' }}">
                                    <a class="{{ request()->is('order*') && request('weight_order') == 2 ? 'active' : '' }}"
                                        href="{{ route('order') }}?weight_order=2&status=New">
                                        B2B Order
                                    </a>
                                </li>
                            </ul>
                        </li> --}}
                    @endif
                    @if (config('permission.bulk_order.view'))
                        <li>
                            <a href="{{ route('order.bulk-create') }}"
                                class="{{ request()->is('order/bulk-create') ? 'active' : '' }}">
                                <i class="mdi mdi-cart"></i>
                                <span> Bulk Order </span>
                            </a>
                        </li>
                    @endif
                    {{-- @if (config('permission.rate_calculator.view'))
                    <li>
                        <a href="{{ route('rate.calculator') }}" class="{{ request()->is('calculator*') ? 'active' : '' }}">
                            <i class="mdi mdi-calculator"></i>
                            <span> Rate Calculator </span>
                        </a>
                    </li>
                    @endif --}}

                    @if (config('permission.rate_calculator.view'))
                        @php
                            $isRateActive =
                                request()->is('rate/calculator*') ||
                                request()->is('rate/zone-setting*') ||
                                request()->is('rate/card') ||
                                request()->is('rate/card/add') ||
                                request()->is('rate/card/edit/*') ||
                                request()->is('rate/price-list*') ||
                                request()->is('rate/other-charges-master*') ||
                                request()->is('rate/shipping-weight*');
                        @endphp

                        @if (Auth::user()->role === 'franchise')
                            <li class="{{ request()->is('rate/price-list*') ? 'mm-active' : '' }}">
                                <a href="{{ route('rate.price-list') }}"
                                    class="{{ request()->is('rate/price-list*') ? 'active' : '' }}">
                                    <i class="mdi mdi-calculator"></i>
                                    <span>Rate Card</span>
                                </a>
                            </li>
                        @else
                            <li class="{{ $isRateActive ? 'mm-active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isRateActive ? 'active' : '' }}">
                                    <i class="mdi mdi-calculator"></i>
                                    <span>Rate Card</span>
                                    <span class="menu-arrow"></span>
                                </a>

                                <ul class="nav-second-level {{ $isRateActive ? 'mm-collapse mm-show' : '' }}"
                                    aria-expanded="{{ $isRateActive ? 'true' : 'false' }}">
                                    <li class="{{ request()->is('rate.zone-setting*') ? 'mm-active' : '' }}">
                                        <a href="{{ route('rate.zone-setting') }}"
                                            class="{{ request()->is('rate.zone-setting*') ? 'active' : '' }}">
                                            Zone Setting
                                        </a>
                                    </li>

                                    <li
                                        class="{{ request()->is('rate/card') || request()->is('rate/card/add') || request()->is('rate/card/edit/*') ? 'mm-active' : '' }}">
                                        <a href="{{ route('rate.card') }}"
                                            class="{{ request()->is('rate/card-setup*') ? 'active' : '' }}">
                                            Rate Card
                                        </a>
                                    </li>

                                    @if (Auth::user()->isMasterAdmin())
                                        {{-- <li class="{{ request()->is('rate/other-charges-master*') ? 'mm-active' : '' }}">
                                            <a href="{{ route('rate.other-charges-master') }}"
                                                class="{{ request()->is('rate/other-charges-master*') ? 'active' : '' }}">
                                                Other Charges Master
                                            </a>
                                        </li> --}}
                                    @endif

                                    <li class="{{ request()->is('rate/shipping-weight*') ? 'mm-active' : '' }}">
                                        <a href="{{ route('rate.shipping-weight') }}"
                                            class="{{ request()->is('rate/shipping-weight*') ? 'active' : '' }}">
                                            Shipping Weight
                                        </a>
                                    </li>

                                    <li class="{{ request()->is('rate/calculator*') ? 'mm-active' : '' }}">
                                        <a href="{{ route('rate.calculator') }}"
                                            class="{{ request()->is('rate/calculator*') ? 'active' : '' }}">
                                            Rate Calculator
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                    @if (config('permission.ndr.view'))
                        <li>
                            <a href="{{ route('ndr') }}">
                                <i class="mdi mdi-alert-circle"></i>
                                <span> NDR Order</span>
                            </a>
                        </li>
                    @endif
                    @if (config('permission.client.view'))
                        <li>
                            <a href="{{ route('customer') }}" class="{{ request()->is('customer*') ? 'active' : '' }}">
                                <i class="mdi mdi-account"></i>
                                <span> Recipient/Customer </span>
                            </a>
                        </li>
                    @endif
                    {{-- @if (config('permission.zipcode.view'))
                    <li>
                        <a href="{{ route('zipcode.index') }}" class="{{ request()->is('zipcode*') ? 'active' : '' }}">
                            <i class="mdi mdi-map-marker-radius"></i>
                            <span> Zipcode </span>
                        </a>
                    </li>
                    @endif --}}
                    @if (config('permission.warehouse.view'))
                        <li>
                            <a href="{{ route('warehouse.index') }}" class="{{ request()->is('warehouse*') ? 'active' : '' }}">
                                <i class="mdi mdi-home-variant"></i>
                                <span> Warehouse </span>
                            </a>
                        </li>
                    @endif
                    @if (config('permission.pickup_request.view'))
                        <li>
                            <a href="{{ route('pickup.request.index') }}"
                                class="{{ request()->is('pickup*') ? 'active' : '' }}">
                                <i class="mdi mdi-truck"></i>
                                <span> Pickup Request </span>
                            </a>
                        </li>
                        <div class="group-lines"></div>
                    @endif
                    @php
                        $showDeliveryExceptions =
                            config('permission.remittance.view') ||
                            config('permission.cod_payout.view') ||
                            (Auth::user()->isMasterAdmin() || Auth::user()->role === 'franchise') ||
                            config('permission.weight_descripencies.view');
                    @endphp
                    @if ($showDeliveryExceptions)
                        <h6 class="group-title-main ">DELIVERY &amp; EXCEPTIONS</h6>
                    @endif
                    @if (config('permission.remittance.view') || config('permission.cod_payout.view'))
                        <li>
                            <a href="javascript: void(0);">
                                <i class="mdi mdi-cash-multiple"></i>
                                <span> Remittance Management </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level" aria-expanded="false">
                                @if (config('permission.remittance.view'))
                                    <li><a href="{{ route('cod-remmitance') }}"> Cod Remittance </a></li>
                                @endif
                                @if (config('permission.cod_payout.view'))
                                    <li><a href="{{ route('cod-payout') }}"> COD Payout </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (config('permission.weight_descripencies.view'))
                        <li class="{{ request()->is('weight/descripencies*') ? 'mm-active' : '' }}">
                            <a href="{{ route('weight.admin.descripencies') }}"
                                class="{{ request()->is('weight/descripencies*') ? 'active' : '' }}">
                                <i class="mdi mdi-weight"></i>
                                <span> Weight Discrepancies </span>
                            </a>
                        </li>
                    @endif
                    @php
                        $showCustomerManagement =
                            config('permission.clients.view') ||
                            config('permission.client_kyc_request.view') ||
                            config('permission.roles.view') ||
                            config('permission.staff.view');
                    @endphp
                    @if ($showCustomerManagement)
                        <div class="group-lines"></div>
                        <h6 class="group-title-main ">CUSTOMER MANAGEMENT</h6>
                    @endif
                    @if (config('permission.clients.view') || config('permission.client_kyc_request.view'))
                        <li class="{{ request()->is('users*') ? 'mm-active' : '' }}">
                            <a href="javascript:void(0);" class="{{ request()->is('users*') ? 'active' : '' }}">
                                <i class="mdi mdi-account-multiple"></i>
                                <span> Clients </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level {{ request()->is('users*') ? 'mm-collapse mm-show' : '' }}"
                                aria-expanded="false">
                                @if (config('permission.clients.view'))
                                    <li
                                        class="{{ (request()->is('users') || request()->is('users/edit*')) && request('kyc_status') == '0' ? 'mm-active' : '' }}">
                                        <a href="{{ route('users') }}?kyc_status=0"
                                            class="{{ (request()->is('users') || request()->is('users/edit*')) && request('kyc_status') == '0' ? 'active' : '' }}">
                                            Pending Clients
                                        </a>
                                    </li>
                                    <li
                                        class="{{ (request()->is('users') || request()->is('users/edit*') || request()->is('users/commission*')) && request('kyc_status') == '1' ? 'mm-active' : '' }}">
                                        <a href="{{ route('users') }}?kyc_status=1"
                                            class="{{ (request()->is('users') || request()->is('users/edit*')) && request('kyc_status') == '1' ? 'active' : '' }}">
                                            Approve Clients
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.client_kyc_request.view'))
                                    <li class="{{ request()->is('users/kyc*') ? 'mm-active' : '' }}">
                                        <a href="{{ route('users.kyc.request') }}"
                                            class="{{ request()->is('users/kyc*') ? 'active' : '' }}">
                                            KYC Request
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if (config('permission.roles.view') || config('permission.staff.view') || auth()->user()->role === 'franchise')
                        <li>
                            <a href="javascript: void(0);">
                                <i class="mdi mdi-account-tie"></i>
                                <span> Staff Management </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level" aria-expanded="false">
                                @if (config('permission.roles.view') || auth()->user()->role === 'franchise')
                                    <li><a href="{{ route('roles') }}"> Roles </a></li>
                                @endif
                                @if (config('permission.staff.view') || auth()->user()->role === 'franchise')
                                    <li><a href="{{ route('staff') }}">Staff </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (config('permission.ticket_request.view'))
                        <div class="group-lines"></div>
                        <h6 class="group-title-main ">SUPPORT &amp; ESCALATIONS</h6>
                    @endif
                    @if (config('permission.ticket_request.view'))
                        <li class="{{ request()->is('ticket*') ? 'mm-active' : '' }}">
                            <a href="{{ route('ticket.admin') }}" class="{{ request()->is('ticket*') ? 'active' : '' }}">
                                <i class="mdi mdi-ticket-confirmation"></i>
                                <span> Ticket Request</span>
                            </a>
                        </li>
                    @endif
                    @php
                        $showAnalytics =
                            config('permission.order_report.view') ||
                            config('permission.recharge_history.view') ||
                            config('permission.income_report.view') ||
                            config('permission.payment_report.view') ||
                            config('permission.passbook_report.view');
                    @endphp
                    @if ($showAnalytics)
                        <div class="group-lines"></div>
                        <h6 class="group-title-main ">ANALYTICS</h6>
                    @endif
                    @if ($showAnalytics)
                        <li>
                            <a href="javascript: void(0);">
                                <i class="mdi mdi-file-chart"></i>
                                <span> Report </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level" aria-expanded="false">
                                @if (config('permission.order_report.view'))
                                    <li>
                                        <a href="{{ route('report.order') }}"
                                            class="{{ request()->is('report.order') ? 'active' : '' }}">
                                            <span>Order Report</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('report.user-wise') }}"
                                            class="{{ request()->is('report/user-wise*') ? 'active' : '' }}">
                                            <span>User Wise Report</span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.passbook_report.view'))
                                    <li>
                                        <a href="{{ route('report.passbook.user') }}"
                                            class="{{ request()->is('passbook-user*') ? 'active' : '' }}">
                                            <span> Passbook </span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.recharge_history.view'))
                                    <li>
                                        <a href="{{ route('recharge.list.history') }}"
                                            class="{{ request()->is('recharge.list.history') ? 'active' : '' }}">
                                            <span> Recharge History </span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.shipping_charge.view'))
                                    <li>
                                        <a href="{{ route('report.shipping-charge') }}"
                                            class="{{ request()->is('report.shipping-charge') ? 'active' : '' }}">
                                            <span> Shipping Charge Report</span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.billing_invoice.view'))
                                    <li>
                                        <a href="{{ route('report.billing-invoice') }}"
                                            class="{{ request()->is('report.billing-invoice') ? 'active' : '' }}">
                                            <span> Billing Invoice Report</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @php
                        $showSystemMenu =
                            config('permission.general_setting.view') ||
                            config('permission.shipping_company.view') ||
                            config('permission.cod_setting.view') ||
                            config('permission.whatsapp_setting.view') ||
                            config('permission.status_mapping.view');
                    @endphp
                    @if ($showSystemMenu)
                        <div class="group-lines"></div>

                        <h6 class="group-title-main ">SYSTEM</h6>
                    @endif
                    @php
                        $isSettingActive =
                            (request()->is('setting*') && !request()->is('setting/bank-details*')) ||
                            request()->is('whatsapp-settings*') ||
                            request()->is('status-mapping*') ||
                            request()->is('weight-dimension*') ||
                            request()->is('shipping/company*');
                    @endphp
                    @if ($showSystemMenu)
                        <li class="{{ $isSettingActive ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="{{ $isSettingActive ? 'active' : '' }}">
                                <i class="mdi mdi-cogs"></i>
                                <span> Setting </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="nav-second-level {{ $isSettingActive ? 'mm-collapse mm-show' : '' }}"
                                aria-expanded="{{ $isSettingActive ? 'true' : 'false' }}">
                                @if (config('permission.general_setting.view'))
                                    <li>
                                        <a href="{{ route('general-setting') }}"
                                            class="{{ request()->is('setting/general*') ? 'active' : '' }}">
                                            General Setting
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('label.settings') }}"
                                            class="{{ request()->is('setting/label-settings*') ? 'active' : '' }}">
                                            Label Preference
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.shipping_company.view'))
                                    <li>
                                        <a href="{{ route('shipping.company') }}"
                                            class="{{ request()->is('shipping/company*') ? 'active' : '' }}">
                                            Shipping Companies
                                        </a>
                                    </li>
                                @endif
                                {{-- @if (config('permission.general_setting.view'))
                                <li>
                                    <a href="{{ route('courier.commission') }}"
                                        class="{{ request()->is('courier-commission*') ? 'active' : '' }}">
                                        Courier Commission
                                    </a>
                                </li> --}}
                                {{-- <li><a href="{{ route('app.banner.index') }}"> App Banner </a>
                                </li> --}}
                                @if (config('permission.cod_setting.view'))
                                    <li>
                                        <a href="{{ route('cod-settings.index') }}"
                                            class="{{ request()->is('cod-settings*') ? 'active' : '' }}">
                                            <span>COD Settings</span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.whatsapp_setting.view'))
                                    <li>
                                        <a href="{{ route('whatsapp.settings') }}"
                                            class="{{ request()->is('whatsapp-settings*') ? 'active' : '' }}">
                                            WhatsApp Settings
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.status_mapping.view'))
                                    <li>
                                        <a href="{{ route('status-mapping.index') }}"
                                            class="{{ request()->is('status-mapping*') ? 'active' : '' }}">
                                            <span>Status Mapping</span>
                                        </a>
                                    </li>
                                @endif
                                @if (config('permission.general_setting.view'))
                                    <li>
                                        <a href="{{ route('weight-dimension.index') }}"
                                            class="{{ request()->is('weight-dimension*') ? 'active' : '' }}">
                                            <span>Weight Dimension</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                @endif
                <li>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault();
					document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        <span> Logout </span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->

</div>


<!-- Left Sidebar End -->
<script>
    function handleSidebarClass() {
        const sidebar = document.querySelector('.new_sidebar');
        if (!sidebar) return;

        // Ensure it always has the base class required for JS hover logic
        if (!sidebar.classList.contains('left-side-menu')) {
            sidebar.classList.add('left-side-menu');
        }
    }

    // Run on load
    window.addEventListener('load', handleSidebarClass);

    // Run on resize
    window.addEventListener('resize', handleSidebarClass);
</script>