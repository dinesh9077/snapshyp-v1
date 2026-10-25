<!-- ============================================================================================================================= -->

<li>
    <a href="{{ url('home') }}" class="{{ request()->is('home*') ? 'active' : '' }}">
        <i class="bi bi-speedometer2 pe-nav-icon"></i>
        <span> Dashboard </span>
    </a>
</li>


<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">SHIPMENT MANAGEMENT</h6>


<li>
    <a href="{{ route('order') }}?weight_order=1&status=New" class="{{ request()->is('order*') && request('weight_order') == 1 ? 'active' : '' }}">
       <i class="bi bi-box"></i>
        <span> Order </span>
    </a>
</li>

{{-- <li>
    <a href="{{ route('order') }}?weight_order=2&status=New"
        class="{{ request()->is('order*') && request('weight_order') == 2 ? 'active' : '' }}">
       <i class="bi bi-box-seam"></i>
        <span> B2B Order </span>
    </a>
</li> --}}

<li>
    <a href="{{ route('order.bulk-create') }}" class="{{ request()->is('order/bulk-create') ? 'active' : '' }}">
       <i class="bi bi-dropbox"></i>
        <span> Bulk Order </span>
    </a>
</li>

<li>
    <a href="{{ route('pickup.request.index') }}" class="{{ request()->is('pickup*') ? 'active' : '' }}">
       <i class="bi bi-truck pe-nav-icon"></i>
        <span> Pickup Request </span>
    </a>
</li>


<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">DELIVERY & EXCEPTIONS</h6>


<li>
    <a href="{{ route('ndr') }}">
        <i class="bi bi-cart pe-nav-icon"></i>
        <span> NDR Order</span>
    </a>
</li>

<li>
    <a href="{{ route('weight.descripencies') }}" class="{{ request()->is('weight/descripencies*') ? 'active' : '' }}">
      <span class="mdi mdi-scale-balance" style="font-size: 20px;"></span>
        <span> Weight Discrepancies </span>
    </a>
</li>


<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">CUSTOMER MANAGEMENT</h6>


<li>
    <a href="{{ route('customer') }}" class="{{ request()->is('customer*') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span> Recipient/Customer </span>
    </a>
</li>

<li>
    <a href="{{ route('users.kyc.edit') }}" class="{{ request()->is('users/kyc/edit') ? 'active' : '' }}">
        <i class="bi bi-person-bounding-box"></i>
        <span> KYC Update </span>
    </a>
</li>

<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">INFRASTRUCTURE</h6>

<li>
    <a href="{{ route('warehouse.index') }}" class="{{ request()->is('warehouse*') ? 'active' : '' }}">
        <i class="bi bi-house-gear"></i>
        <span> Warehouse </span>
    </a>
</li>



<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">PRICING & PAYMENTS</h6>

<li class="{{ request()->is('rate/price-list*') ? 'mm-active' : '' }}">
    <a href="{{ route('rate.price-list') }}" class="{{ request()->is('rate/price-list*') ? 'active' : '' }}">
        <i class="bi bi-credit-card-2-front"></i>
        <span> Rate Card </span>
    </a>
</li>



<li>
    <a href="javascript: void(0);">
        <i class="bi bi-postcard"></i>
        <span> Remittance Management </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li><a href="{{ route('cod-remmitance') }}"> Cod Remittance </a></li>
        <li><a href="{{ route('cod-payout') }}"> COD Payout </a></li>
        <li><a href="{{ route('user.cod-plans.index') }}"> COD Plan Settings </a></li>
    </ul>
</li>


<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">SUPPORT & ESCALATIONS</h6>


<li class="{{ request()->is('ticket*') ? 'mm-active' : '' }}">
    <a href="{{ route('ticket') }}" class="{{ request()->is('ticket*') ? 'active' : '' }}">
        <i class="bi bi-ticket-perforated"></i>
        <span> Ticket Request</span>
    </a>
</li>



<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">ANALYTICS</h6>


<li>
    <a href="javascript: void(0);">
        <i class="bi bi-file-earmark"></i>
        <span> Report </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="{{ route('report.order') }}" class="{{ request()->is('report.order') ? 'active' : '' }}">
                <span>Order Report</span>
            </a>
        </li>
        <li>
            <a href="{{ route('report.passbook', auth()->id()) }}" class="{{ request()->is('passbook*') ? 'active' : '' }}">
                <span> Passbook </span>
            </a>
        </li>
        <li>
            <a href="{{ route('recharge.list') }}" class="{{ request()->is('recharge.list') ? 'active' : '' }}">
                <span> Recharge History </span>
            </a>
        </li>
        <li>
            <a href="{{ route('report.billing-invoice') }}"
                class="{{ request()->is('report.billing-invoice') ? 'active' : '' }}">
                <span> Billing Invoice Report</span>
            </a>
        </li>
    </ul>
</li>


<!-- ============================================================================================================================= -->

<div class="group-lines"></div>
<h6 class="group-title-main ">SYSTEM</h6>


<li>
    <a href="javascript: void(0);">
        <i class="bi bi-gear"></i>
        <span> Setting </span>
        <span class="menu-arrow"></span>
    </a>
    <ul class="nav-second-level" aria-expanded="false">
        <li>
            <a href="{{ route('label.settings') }}" class="{{ request()->is('label.settings') ? 'active' : '' }}">
                <span> Label Settings </span>
            </a>
        </li>
        <li>
            <a href="{{ route('integrations.index') }}"
                class="{{ request()->is('user/whatsapp-services') || request()->is('api/developer') ? 'active' : '' }}">
                <span>Integrations</span>
            </a>
        </li>
    </ul>
</li>


{{-- <li>
    <a href="{{ route('user/cod-remittance', $authUser->id) }}"
class="{{ request()->is('user/cod-remittance*') ? 'active' : '' }}">
<i class="mdi mdi-cash-multiple"></i>
<span> Remittance </span>
</a>
</li> --}}

