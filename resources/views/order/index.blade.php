@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Order')
@section('header_title', 'Order')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
<style>
.tooltip .tooltiptext {
    text-align: left !important;
    padding: 5px 0 5px 5px !important;
}

/* Ensure Tags column wraps text and doesn't break layout or functionality */
th.wrap-text,
#orderDatatable td.wrap-text,
#orderDatatable td:nth-child(8) {
    text-wrap: wrap !important;
}

td p {
    margin-bottom: 0;
}

.inner-page-heading {
    overflow: visible;
    /* Ensure it's not cutting off children */
}

.dropdown-menu {
    z-index: 1050 !important;
    /* Bootstrap default, adjust if needed */
    position: absolute;
    /* Ensure it positions relative to .dropdown */
}

.dropdown {
    position: relative;
}

/* Main popup styling */
.swal2-custom-popup {
    border-radius: 12px !important;
    padding: 18px 20px !important;
    max-width: 480px !important;
    /* mobile-friendly width */
}

/* Title */
.swal2-custom-title {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.75rem !important;
}

/* Container for html */
.swal2-custom-html {
    font-size: 0.95rem !important;
    padding: 0 !important;
}

/* Main message */
.swal-main-msg {
    margin-bottom: 10px;
}

/* Summary section */
.swal-summary-wrapper {
    margin-top: 10px;
    text-align: left;
}

.swal-summary-title {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

/* Summary grid */
.swal-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

/* Card-like items */
.swal-summary-item {
    padding: 8px 10px;
    border-radius: 8px;
    background-color: #f4f4f4;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.swal-summary-item .label {
    font-size: 0.8rem;
    opacity: 0.75;
}

.swal-summary-item .value {
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 2px;
}

/* Colors for different types */
.swal-summary-processed {
    background-color: #e8f5e9;
}

/* light green */
.swal-summary-cancelled {
    background-color: #fff3e0;
}

/* light orange */
.swal-summary-skipped {
    background-color: #e3f2fd;
}

/* light blue */
.swal-summary-errors {
    background-color: #ffebee;
}
.table-responsive {
    white-space: normal !important;
}
#neworder_datatable button.btn-light-1{
    white-space: nowrap !important;
}

/* light red */

/* Confirm button */
.swal2-custom-confirm-btn {
    margin-top: 14px !important;
    padding: 6px 18px !important;
    border-radius: 20px !important;
    border: none !important;
    background-color: #2563eb !important;
    /* blue */
    color: #fff !important;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
    cursor: pointer !important;
}

/* Error confirm button color */
.swal2-custom-confirm-error {
    background-color: #dc2626 !important;
    /* red */
}

/* Responsive tweak for very small screens */
@media (max-width: 420px) {
    .swal-summary-grid {
        grid-template-columns: 1fr;
    }
}

/* Gray out queued rows with marquee border effect */
.processing-row {
    cursor: not-allowed;
    color: #999 !important;
}

/* Standard Cells: Top & Bottom Borders only */
.processing-row td {
    background-color: #f0f0f0 !important;
    opacity: 0.8;
    border: none !important;

    /* Background Images (Gradient Dashes) */
    background-image:
        linear-gradient(90deg, #888 50%, transparent 50%),
        linear-gradient(90deg, #888 50%, transparent 50%) !important;

    /* Sizes and Repeats */
    background-size: 20px 2px, 20px 2px !important;
    background-repeat: repeat-x, repeat-x !important;

    /* Initial Position (No !important to allow animation) */
    background-position: 0 0, 0 100%;

    /* Animation */
    animation: marqueeTB 1s linear infinite;
}

/* First Cell: Top, Bottom, Left Borders */
.processing-row td:first-child {
    background-image:
        linear-gradient(90deg, #888 50%, transparent 50%),
        linear-gradient(90deg, #888 50%, transparent 50%),
        linear-gradient(180deg, #888 50%, transparent 50%) !important;
    background-size: 20px 2px, 20px 2px, 2px 20px !important;
    background-repeat: repeat-x, repeat-x, repeat-y !important;
    background-position: 0 0, 0 100%, 0 0;
    animation: marqueeTBL 1s linear infinite;
}

/* Last Cell: Top, Bottom, Right Borders */
.processing-row td:last-child {
    background-image:
        linear-gradient(90deg, #888 50%, transparent 50%),
        linear-gradient(90deg, #888 50%, transparent 50%),
        linear-gradient(180deg, #888 50%, transparent 50%) !important;
    background-size: 20px 2px, 20px 2px, 2px 20px !important;
    background-repeat: repeat-x, repeat-x, repeat-y !important;
    background-position: 0 0, 0 100%, 100% 0;
    animation: marqueeTBR 1s linear infinite;
}

@keyframes marqueeTB {
    0% {
        background-position: 0 0, 0 100%;
    }

    100% {
        background-position: 20px 0, -20px 100%;
    }
}

@keyframes marqueeTBL {
    0% {
        background-position: 0 0, 0 100%, 0 0;
    }

    100% {
        background-position: 20px 0, -20px 100%, 0 -20px;
    }
}

@keyframes marqueeTBR {
    0% {
        background-position: 0 0, 0 100%, 100% 0;
    }

    100% {
        background-position: 20px 0, -20px 100%, 100% 20px;
    }
}

/* Loader Animation */
.btn-loader {
    display: inline-flex;
    /* Ensure flex alignment */
    align-items: center;
    /* Center icon and text */
    gap: 5px;
    /* Space between icon and text */
}

.mdi-loading {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="">
                    <div class="order-111-add">
                        @if (!in_array($status, ['All', 'New']))
                        <button id="downloadAllLable" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-file-download-outline me-1"></i> Bulk Label
                        </button>
                        @endif

                        @if (in_array($status, ['New', 'Ready To Ship', 'Pending Pickup']))
                        @if ($status === 'New')
                        <button id="bulkUpdateOrder" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-playlist-edit me-1"></i> Bulk Update
                        </button>

                        <button id="bulkSyncChannel" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-sync me-1"></i> Sync Channel
                        </button>

                        <button id="bulkShipmentOrder" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-truck me-1"></i> Bulk Ship Now
                        </button>
                        @endif

                        <button id="bulkShipmentCancel" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-close-circle-outline me-1"></i> Bulk Cancel
                        </button>
                        @if ($status === 'Ready To Ship')
                        <button id="bulkSchedulePickup" class="btn-main-1" style="display:none;">
                            <i class="mdi mdi-calendar-clock me-1"></i> Schedule Pickup
                        </button>
                        @endif
                        @endif

                        @if(in_array(auth()->user()->role, ['user', 'master_admin', 'franchise']) || config('permission.admin_dashboard.view'))
                        <a href="{{ route('order.create') }}?weight_order=1">
                            <button class="btn-main-1"> + Add Order </button>
                        </a>
                        @endif

                        <button type="button" class="btn-main-1" id="orderExportExcel">
                            <i class="mdi mdi-file-excel-outline me-1"></i> Export
                        </button>

                        <button type="button" class="btn-main-1 search-btn-remi" id="orderFilterModal">
                            Filter <i class="mdi mdi-filter-outline"></i>
                        </button>
                    </div>
                    <div class="inner-page-heading order-tab mb-2 mt-3">


                        {{-- Shipment Booking --}}
                        <div class="tab-group">
                            <!-- <div class="tab-group-title">Shipment Booking</div> -->
                            <ul class="tabs" id="tabs">
                                @foreach (array_slice($statusMappings, 0, 4) as $key => $label)
                                <li class="{{ $status == $label ? 'active' : '' }}"
                                    onclick="redirectUrl('{{ $label }}')">
                                    {{ $label }} <span class="order-tab-count">0</span>
                                </li>
                                @endforeach


                                {{-- Shipment Journey --}}

                                @foreach (array_slice($statusMappings, 3, 3) as $key => $label)
                                <li class="{{ $status == $label ? 'active' : '' }}"
                                    onclick="redirectUrl('{{ $label }}')">
                                    {{ $label }} <span class="order-tab-count">0</span>
                                </li>
                                @endforeach



                                {{-- Others --}}
                                @php
                                if (!in_array('All', $statusMappings)) {
                                    $statusMappings[] = 'All';
                                }
                                @endphp

                                {{-- <div class="tab-group-title">Others</div> --}}
                                <div class="tabs" id="tabs" style="overflow: visible">
                                    <li
                                        class="{{ in_array($status, array_slice($statusMappings, 6)) ? 'active' : '' }}" style="padding-right: 16px;">
                                        <div class="dropdown d-inline-block">
                                            <div class="dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                @if (in_array($status, array_slice($statusMappings, 0, 6)))
                                                More ▼
                                                @else
                                                {{ strtoupper($status) }} ▼
                                                @endif
                                            </div>
                                            <div class="dropdown-menu">
                                                @foreach (array_slice($statusMappings, 6) as $key => $label)
                                                <a onclick="redirectUrl('{{ $label }}')" class="dropdown-item"
                                                    style="cursor:pointer;">{{ $label }} <span
                                                        class="order-tab-count">0</span></a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </li>
                                </div>
                            </ul>
                        </div>



                    </div>

                    <div class="main-order-001">
                        <ul id="tab">
                            @foreach (collect($statusMappings) as $key => $statusMapping)
                            @if ($status === $statusMapping || ($statusMapping === 'New' && $status == ''))
                            <li class="active">
                                <div class="main-calander-11">
                                    <div class="main-data-teble-1 table-responsive">
                                        <table id="neworder_datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    @if (in_array($status, ['All']))
                                                    <th>Sr.No</th>
                                                    @else
                                                    <th> <input type="checkbox" id="checkAll"> </th>
                                                    @endif
                                                    <th style="min-width: 90px;">Order Id</th>
                                                    <th style="min-width: 250px;">Seller Details</th>
                                                    <th>Customer Details</th>
                                                    @if ($status !== 'New')
                                                    <th>Shipping Details</th>
                                                    @endif
                                                    <th>Package Details</th>
                                                    <th style="min-width: 80px">Status</th>
                                                    @if ($statusMapping === 'All' || $statusMapping === 'Delivered')
                                                    <th>Delivery Date</th>
                                                    @endif
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="orderExportForm" action="{{ route('order.export-excel') }}" method="POST" class="d-none">
    @csrf
</form>


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
                <a href="{{ route('order') }}?weight_order=1&status=New" id="doitlater" class="btn btn-primary simple-021-btn">
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

<div class="modal fade bd-example-modal-lg main-bg0-021 addwarehousemodal" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header pb-0" style="border: none;">
                <h5 class="modal-title pick-up0" id="exampleModalLabel"> Add Warehouse </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="warehouseForm" action="{{ route('order.warehouse.address') }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="address_id" id="address_id">
                    <input type="hidden" name="id" id="vendor_id">
                    <input type="hidden" name="shipping_id" id="ware_shipping_id">
                    <div class="from-group my-2">
                        <label for="order-id"> WareHouse Name </label>
                        <input type="text" placeholder="Enter WareHouse Name" name="warehouse_name" maxlength="18"
                            required>
                        <span style="color:red" id="warehouse_error"></span>
                    </div>
                </div>
                <div class="modal-footer" style="margin: auto;border: none;">
                    <button type="sumbit" class="btn btn-primary btn-main-1"> Submit </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="lbhModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Package Dimension Update </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="lbhForm" action="{{ route('order.package-dimension.update') }}" method="post"
                enctype="multipart/form-data">
                <input type="hidden" name="order_id" id="order_id">
                <div class="modal-body row">
                    <div class="col-xl-12 col-md-12 col-sm-12">
                        <h4 class="mb-0">
                            <small class="text-muted d-block">
                                Note: Please update them to proceed with delivery.
                            </small>
                        </h4>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="from-group my-2">
                            <label for="packaging-type">
                                Warehouse
                                <span>
                                    (If the warehouse is not listed, you can
                                    <a href="{{ url('warehouse/add') }}" target="_blank"
                                        style="color: #007bff; text-decoration: underline;">
                                        add it from the Warehouse menu
                                    </a>)
                                </span>
                            </label>

                            <select class="form-control" id="warehouse_id" name="warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }} -
                                    ({{ $warehouse->company_name }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Weight (KG) </label>
                            <input type="text" name="weight"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="weight" placeholder="Weight (KG)" value="">
                        </div>
                        <label id="volumatric_weight" style="font-weight: 900;"></label>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Length (cm) </label>
                            <input type="number" name="length" id="length"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="length" placeholder="Length (cm)" value="">
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Width (cm) </label>
                            <input type="number" name="width" id="width"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="width" placeholder="Width (cm)" value="">
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Height (cm) </label>
                            <input type="number" name="height" id="height"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="height" placeholder="Height (cm)" value="">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="new-submit-btn mt-2">
                        <span class="btn-text">Update</span>
                        <span class="btn-loader d-none">
                            <i class="mdi mdi-loading mdi-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="lbhBulkModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Bulk Package Dimension Update </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="lbhBulkForm" action="{{ route('order.package-dimension.bulk-update') }}" method="post"
                enctype="multipart/form-data">
                <div class="modal-body row">
                    <div class="col-xl-12 col-md-12 col-sm-12">
                        <h4 class="mb-0">
                            <small class="text-muted d-block">
                                Note: Please update them to proceed with delivery.
                            </small>
                        </h4>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="from-group my-2">
                            <label for="packaging-type">
                                Warehouse
                                <span>
                                    (If the warehouse is not listed, you can
                                    <a href="{{ url('warehouse/add') }}" target="_blank"
                                        style="color: #007bff; text-decoration: underline;">
                                        add it from the Warehouse menu
                                    </a>)
                                </span>
                            </label>

                            <select class="form-control" id="warehouse_id" name="warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }} -
                                    ({{ $warehouse->company_name }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Weight (KG) </label>
                            <input type="text" name="weight"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="weight" placeholder="Weight (KG)" value="">
                        </div>
                        <label id="volumatric_weight" style="font-weight: 900;"></label>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Length (cm) </label>
                            <input type="number" name="length"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="length" placeholder="Length (cm)" value="">
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Width (cm) </label>
                            <input type="number" name="width"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="width" placeholder="Width (cm)" value="">
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-md-6">
                        <div class="from-group my-2">
                            <label for="packaging-type"> Height (cm) </label>
                            <input type="number" name="height"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                id="height" placeholder="Height (cm)" value="">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="new-submit-btn mt-2">
                        <span class="btn-text">Update</span>
                        <span class="btn-loader d-none">
                            <i class="mdi mdi-loading mdi-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="shipBulkModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Bulk Ship Now </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="shipmentBulkForm" action="{{ route('order.bulk-shipment.now') }}" method="post"
                enctype="multipart/form-data">
                <div class="modal-body row">
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="from-group my-2">
                            <select class="form-control" id="shipping_mode" name="shipping_mode" required>
                                <option value="Surface"> By Surface </option>
                                <option value="Air"> By Air </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="from-group my-2">
                            <select class="form-control" id="courier_shipment_id" name="courier_shipment_id" required>
                                <option value="">Select Courier</option>
                                @foreach ($shipments as $shipment)
                                <option value="{{ $shipment->id }}">{{ $shipment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="weight_order" value="1">
                <div class="modal-footer">
                    <button type="submit" class="new-submit-btn mt-2">
                        <span class="btn-text">Submit</span>
                        <span class="btn-loader d-none">
                            <i class="mdi mdi-loading mdi-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="hotelInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoModalLabel">Product Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="modal-body" id="infoModalBody">
                <!-- HTML hotel description appears here -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade order-filter-modal" id="orderFilterModalShow" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Filters</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="modal-body overflow-auto" id="orderFilterModalBody">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-reset" id="resetOrderFilters" data-dismiss="modal">Reset</button>
                <button class="btn btn-apply" id="applyOrderFilters">Apply</button>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isMasterAdmin())
<div class="modal fade" id="manualStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Status Update</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="manualStatusForm" method="POST">
                @csrf
                <input type="hidden" name="order_id" id="manual_status_order_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Status</label>
                        <select class="form-control" name="status" id="manual_status_select" required>
                            <option value="">Select Status</option>
                            @foreach($internalStatuses as $statusVal)
                                <option value="{{ $statusVal }}">{{ $statusVal }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-main-1">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="bulkSchedulePickupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Pickup</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="bulkSchedulePickupForm" action="{{ route('pickup.request.create') }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Shipping Company</label>
                        <select name="shipping_company_id" class="form-control select2" required>
                            <option value="">Select Shipping Company</option>
                            @foreach ($shipments as $shippingCompany)
                            @if($shippingCompany->id == 1 || $shippingCompany->id == 2)
                            <option value="{{ $shippingCompany->id }}">{{ $shippingCompany->name }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Warehouse Location</label>
                        <select name="warehouse_id" class="form-control select2" required>
                            <option value="">Select Warehouse Name</option>
                            @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}
                                ({{ $warehouse->address }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pickup Date</label>
                        <input type="text" name="pickup_date" id="bulk_pickup_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Pickup Time</label>
                        <select class="form-control select2" name="pickup_start_time" id="bulk_pickup_start_time"
                            required>
                            <option value="">Select Time</option>
                            @foreach ($timeSlots as $timeSlot)
                            @php $time = explode(' - ', $timeSlot); @endphp
                            <option value="{{ $time[0] }}" data-pickup_end_time="{{ $time[1] }}">{{ $timeSlot }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="pickup_end_time" id="bulk_pickup_end_time" required>
                    <input type="hidden" name="order_ids" id="selected_order_ids">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Schedule Pickup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="creditLostOrderModal" tabindex="-1" role="dialog"
    aria-labelledby="creditLostOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="creditLostOrderModalLabel">Credit Lost Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="creditLostOrderForm" method="POST" action="{{ route('order.credit-lost-order') }}">
                @csrf
                <input type="hidden" name="order_id" id="credit_order_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Order ID</label>
                            <input type="text" class="form-control" id="credit_order_prefix" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Invoice Amount</label>
                            <input type="text" class="form-control" id="credit_invoice_amount" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Credit Amount (Max: <span id="credit_max_limit"></span>)</label>
                        <input type="number" name="amount" id="credit_amount" class="form-control" step="0.01" min="1"
                            required>
                        <small class="text-muted">Max credit allowed is either Invoice Amount or ₹2000 (whichever is
                            less).</small>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="3"
                            placeholder="Reason for credit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Credit Amount</button>
                </div>
            </form>
        </div>
    </div>
</div>
@php
$columns = [
['data' => 'id', 'orderable' => false],
['data' => 'order_id'],
['data' => 'seller_details'],
['data' => 'customer_details'],
];

if ($status !== 'New') {
$columns[] = ['data' => 'shipment_details'];
}

$columns[] = ['data' => 'package_details'];
$columns[] = ['data' => 'status_courier'];

if ($status === 'All' || $status === 'Delivered') {
$columns[] = ['data' => 'delivery_date'];
}

$columns[] = ['data' => 'action'];
@endphp
@endsection
@push('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js"></script>

<!-- Load the Roboto font from Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

<script>
var weightOrder = @json(request('weight_order')) || 1;
var courierName = @json(request('courier_name', '')) || '';
var shippingCompanyId = @json(request('shipping_company_id', '')) || '';
var is_shopify_order = @json(request('is_shopify_order') ?? '');
var is_portal_order = @json(request('is_portal_order') ?? '');
var is_wordpress_order = @json(request('is_wordpress_order') ?? '');

$('.customization_popup').on('click', function(event) {
    if ($(event.target).is('.customization_popup_close') || $(event.target).is('.customization_popup')) {
        event.preventDefault();
        $(this).removeClass('is-visible');
    }
});

function shipNow(obj, event) {
    event.preventDefault();

    const kyc_status = @json($authUser -> kyc_status);
    const role = @json($authUser -> role);
    if (!['admin', 'franchise'].includes(role) && kyc_status == 0) {
        toastrMsg('error', 'Your order cannot be placed until your KYC is approved.');
        return;
    }

    const orderId = $(obj).attr('data-id');
    run_waitMe($('body'), 1, 'win8');

    $.ajax({
        url: `{{ url('order/shipment/charge') }}/${orderId}?weight_order=${weightOrder}&courier_name=${courierName}&shipping_company_id=${shippingCompanyId}`,
        type: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(res) {
            $('body').waitMe('hide');

            if (res.status === "success") {
                $('.shipmentChargesByAll').html(res.view);
                $('.customization_popup').addClass('is-visible');
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
        error: function(xhr) {
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



function openCreditModal(obj) {
    var orderId = $(obj).data('id');
    var invoiceAmount = parseFloat($(obj).data('invoice-amount'));
    var orderPrefix = $(obj).data('order-prefix');

    // Logic: min(invoiceAmount, 2000)
    var maxCredit = Math.min(invoiceAmount, 2000);

    $('#credit_order_id').val(orderId);
    $('#credit_order_prefix').val(orderPrefix);
    $('#credit_invoice_amount').val(invoiceAmount);
    $('#credit_max_limit').text('₹' + maxCredit);
    $('#credit_amount').attr('max', maxCredit);
    $('#credit_amount').val(''); // Reset amount

    $('#creditLostOrderModal').modal('show');
}

$('#creditLostOrderForm').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var btn = form.find('button[type="submit"]');
    var max = parseFloat($('#credit_amount').attr('max'));
    var amt = parseFloat($('#credit_amount').val());

    if (amt > max) {
        toastrMsg('error', 'Amount cannot exceed ₹' + max);
        return;
    }

    btn.prop('disabled', true);

    $.ajax({
        url: form.attr('action'),
        method: form.attr('method'),
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false);
            if (response.status == 'success') {
                toastrMsg('success', response.msg);
                $('#creditLostOrderModal').modal('hide');
                $('#neworder_datatable').DataTable().ajax.reload(); // Reload table
            } else {
                toastrMsg('error', response.msg);
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false);
            var msg = 'Something went wrong.';
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            toastrMsg('error', msg);
        }
    });
});

$(document).ready(function() {

    function startLoading($btn, text = 'Processing...') {
        $btn.prop('disabled', true);
        $btn.find('.btn-text').addClass('d-none');
        $btn.find('.btn-loader').removeClass('d-none')
            .find('span').text(text);
    }

    function stopLoading($btn) {
        $btn.prop('disabled', false);
        $btn.find('.btn-loader').addClass('d-none');
        $btn.find('.btn-text').removeClass('d-none');
    }

    const $lbhForm = $("#lbhForm");

    // Enable button initially
    $lbhForm.find('input[type="submit"]').prop("disabled", false);

    $lbhForm.on("submit", function(event) {
        event.preventDefault();
        let $submitButton = $lbhForm.find('button[type="submit"]');
        startLoading($submitButton, 'Uploading...');

        // Create FormData object
        let formData = new FormData(this);
        formData.append('_token', "{{ csrf_token() }}");

        // AJAX Request
        $.ajax({
            url: $lbhForm.attr("action"),
            type: $lbhForm.attr("method"),
            data: formData,
            cache: false,
            processData: false,
            contentType: false,
            dataType: 'Json',
            success: function(res) {
                stopLoading($submitButton);
                if (res.status === "success") {
                    toastrMsg(res.status, res.msg);
                    $('#lbhModal').modal('hide');
                    setTimeout(function() {
                        window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                    }, 500);

                } else {
                    toastrMsg(res.status, res.msg);
                }
            },
            error: function(xhr) {
                toastrMsg("error", "Something went wrong. Please try again.");
                stopLoading($submitButton);
            }
        });
    });

    // Global handler so we can add/remove it
    function lbhBeforeUnloadHandler(e) {
        e.preventDefault();
        // Modern browsers ignore custom text, but this is still required:
        e.returnValue = "Your bulk process is still running. If you leave this page, it may not complete.";
        return e.returnValue;
    }


    const $lbhBulkForm = $("#lbhBulkForm");
    const $submitButton = $lbhBulkForm.find('button[type="submit"]');

    // Enable button initially
    $submitButton.prop("disabled", false);

    $lbhBulkForm.on("submit", function(event) {
        event.preventDefault();

        // Collect selected order IDs
        let selectedOrderIds = [];
        $('.order-checkbox:checked').each(function() {
            selectedOrderIds.push($(this).val()); // data-order-id
        });

        // If nothing selected, stop here
        if (selectedOrderIds.length === 0) {
            alert('No orders selected.');
            return;
        }

        // ✅ Enable beforeunload warning while processing
        window.addEventListener("beforeunload", lbhBeforeUnloadHandler);
        startLoading($submitButton, 'Uploading...');
        // Create FormData object
        let formData = new FormData(this);
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('order_ids', selectedOrderIds); // or JSON.stringify(selectedOrderIds)

        // AJAX Request
        $.ajax({
            url: $lbhBulkForm.attr("action"),
            type: $lbhBulkForm.attr("method"),
            data: formData,
            cache: false,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                // ✅ Processing finished → remove beforeunload handler
                window.removeEventListener("beforeunload", lbhBeforeUnloadHandler);

                stopLoading($submitButton);

                if (res.status === "success") {
                    $('#lbhBulkModal').modal('hide');
                    toastrMsg(res.status, res.msg);

                    setTimeout(function() {
                        window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                    }, 500);
                } else {
                    toastrMsg(res.status, res.msg);
                }
            },
            error: function(xhr) {
                // ✅ Error also means stop warning
                window.removeEventListener("beforeunload", lbhBeforeUnloadHandler);

                toastrMsg("error", "Something went wrong. Please try again.");
                stopLoading($submitButton);
            }
        });
    });

    const $shipmentBulkForm = $("#shipmentBulkForm");
    const $submitBulkButton = $shipmentBulkForm.find('button[type="submit"]');

    // Enable submit button initially
    $submitBulkButton.prop("disabled", false);
    // Before-unload handler function
    function shipmentBeforeUnload(e) {
        e.preventDefault();
        e.returnValue = "Shipment process is running. Leaving the page may interrupt it.";
        return e.returnValue;
    }

    $shipmentBulkForm.on("submit", function(event) {
        event.preventDefault();

        // Collect selected order IDs
        let selectedOrderIds = [];
        $('.order-checkbox:checked').each(function() {
            selectedOrderIds.push($(this).val());
        });

        // Stop if no orders selected
        if (selectedOrderIds.length === 0) {
            alert('No orders selected.');
            return;
        }

        // 🛑 Activate before-unload warning while processing
        window.addEventListener("beforeunload", shipmentBeforeUnload);

        startLoading($submitBulkButton, 'Uploading...');

        let formData = new FormData(this);
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('order_ids', selectedOrderIds);

        // AJAX Request
        $.ajax({
            url: $shipmentBulkForm.attr("action"),
            type: $shipmentBulkForm.attr("method"),
            data: formData,
            cache: false,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {

                // ⛔ Remove refresh-blocker (process completed)
                window.removeEventListener("beforeunload", shipmentBeforeUnload);

                stopLoading($submitBulkButton);

                if (res.status === "success") {
                    $('#shipBulkModal').modal('hide');
                    toastrMsg(res.status, res.msg);

                    setTimeout(function() {
                        window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                    }, 500); // increased so user can see success
                } else {
                    toastrMsg(res.status, res.msg);
                }
            },
            error: function() {

                // Remove block if failed
                window.removeEventListener("beforeunload", shipmentBeforeUnload);

                toastrMsg("error", "Something went wrong. Please try again.");
                stopLoading($submitBulkButton);
            }
        });
    });
});


$(document).ready(function() {
    $('ul.tabs-001 li').click(function() {
        var tab_id = $(this).attr('data-tab');
        $('ul.tabs-001 li').removeClass('current');
        $('.tab-content11').removeClass('current');
        $(this).addClass('current');
        $("#" + tab_id).addClass('current');
    })
})

// Order listing
$(document).ready(function() {
    function initializeDataTable(selector, ajaxUrl, additionalData = {}, columns = []) {
        if ($(selector).length === 0) {
            return null;
        }
        return $(selector).DataTable({
            processing: true,
            serverSide: true,
            destroy: true, // Prevent duplicate initialization
            bLengthChange: true,
            searching: true,
            bFilter: true,
            bInfo: true,
            iDisplayLength: 25,
            searchDelay: 1000,
            lengthMenu: [
                [10, 25, 50, 100, 250, 500, 1000],
                [10, 25, 50, 100, 250, 500, 1000]
            ],
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            language: {
                loadingRecords: '&nbsp;',
                processing: 'Loading...',
            },
            ajax: {
                url: ajaxUrl,
                type: "POST",
                dataType: "json",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    Object.assign(d, additionalData);
                }
            },
            columns: columns,
            drawCallback: function() {
                $('[data-toggle="tooltip"]').tooltip();
                $('[data-fancybox="images"]').fancybox({
                    loop: true
                });
            }
        });
    }

    // Define table columns
    let OrderColumns = [
        { data: 'id', name: 'id', orderable: true },
        { data: 'order_id', name: 'order_id', orderable: false },
        { data: 'seller_details', name: 'seller_details', orderable: false },
        { data: 'customer_details', name: 'customer_details', orderable: false }
    ];

    if ("{{ $status }}" !== 'New') {
        OrderColumns.push({ data: 'shipment_details', name: 'shipment_details', orderable: false });
    }

    OrderColumns.push(
        { data: 'package_details', name: 'package_details', orderable: false },
        { data: 'status_courier', name: 'status_courier', orderable: true }
    );

    if ("{{ $status }}" === 'All' || "{{ $status }}" === 'Delivered') {
        OrderColumns.push({ data: 'delivery_date', name: 'delivery_date', orderable: true });
    }

    OrderColumns.push({ data: 'action', name: 'action', orderable: false, searchable: false });

    var statusCourier = "{{ request('order_status') ?? '' }}";
    // Use global filter flags defined above

    // Initialize DataTables for different statuses
    let newOrderTable = initializeDataTable('#neworder_datatable', "{{ route('order.ajax') }}", {
        status: @json($status),
        weightOrder: weightOrder,
        courier_name: courierName,
        is_shopify_order: is_shopify_order,
        is_portal_order: is_portal_order,
        is_wordpress_order: is_wordpress_order,
        status_courier: statusCourier,
        shippingCompanyId: shippingCompanyId
    }, OrderColumns);

    $('#applyOrderFilters').on('click', function(e) {
        e.preventDefault();
        $('#orderFilterModalShow').modal('hide');
        const status = @json($status);
        var statusCourier = "{{ request('order_status') ?? '' }}";
        var is_shopify_order = @json(request('is_shopify_order') ?? '');
        var weightOrder = "{{ request('weight_order') ?? '' }}";
        var courierName = "{{ request('courier_name') ?? '' }}";
        var shippingCompanyId = "{{ request('shipping_company_id') ?? '' }}";
        // 🔹 Collect ALL filters
        let filters = {
            status: status,
            search_by: $('#orderFilterModalBody').find('input[name="search_by"]:checked').val(),
            search_value: $('#orderFilterModalBody').find('#search_value').val(),

            order_tag: $('#orderFilterModalBody').find('#order_tag').val(),
            warehouse_id: $('#orderFilterModalBody').find('#warehouse_id').val(),
            payment_type: $('#orderFilterModalBody').find('#payment_type').val(),

                min_weight: $('#orderFilterModalBody').find('#min_weight').val(),
                max_weight: $('#orderFilterModalBody').find('#max_weight').val(),

                state: $('#orderFilterModalBody').find('#state').val(),
                city: $('#orderFilterModalBody').find('#city').val(),

            order_search: $('#orderFilterModalBody').find('input[name="order_search"]:checked')
                .val(),
            order_search_value: $('#orderFilterModalBody').find('#order_search_value').val(),

            customer_search: $('#orderFilterModalBody').find(
                'input[name="customer_search"]:checked').val(),
            customer_search_value: $('#orderFilterModalBody').find('#customer_search_value')
                .val(),

            date_by: $('#orderFilterModalBody').find('#date_by').val(),
            from_date: $('#orderFilterModalBody').find('#from_date').val(),
            to_date: $('#orderFilterModalBody').find('#to_date').val(),

            user_id: $('#orderFilterModalBody').find('#user_id').val(),

            weightOrder: weightOrder,
            courier_name: courierName,
            shipping_company_id: shippingCompanyId,
            is_shopify_order: is_shopify_order,
            is_portal_order: is_portal_order,
            is_wordpress_order: is_wordpress_order,
            status_courier: statusCourier || $('#orderFilterModalBody').find('#status_courier')
                .val(),
        };

        let tableId = '#neworder_datatable';
        let tableKey = 'new';

        // 🔹 Initialize once, reload after
        initializeDataTable(
            tableId,
            "{{ route('order.ajax') }}",
            filters,
            OrderColumns
        );
    });

    $('#resetOrderFilters').on('click', function(e) {
        e.preventDefault();
        $('#orderFilterModalShow').modal('hide');
        const status = @json($status);
        var statusCourier = "{{ request('order_status') ?? '' }}";
        var is_shopify_order = @json(request('is_shopify_order') ?? '');
        var weightOrder = "{{ request('weight_order') ?? '' }}";
        var courierName = "{{ request('courier_name') ?? '' }}";
        var shippingCompanyId = "{{ request('shipping_company_id') ?? '' }}";
        // 🔹 Collect ALL filters
        let filters = {
            status: status,
            weightOrder: weightOrder,
            courier_name: courierName,
            shipping_company_id: shippingCompanyId,
            is_shopify_order: is_shopify_order,
            is_portal_order: is_portal_order,
            is_wordpress_order: is_wordpress_order,
            status_courier: statusCourier,
        };

        let tableId = '#neworder_datatable';
        let tableKey = 'new';
        // 🔹 Initialize once, reload after
        initializeDataTable(
            tableId,
            "{{ route('order.ajax') }}",
            filters,
            OrderColumns
        );
    });

    // Export Excel with current filters & search text
    $(document).on('click', '#orderExportExcel', function(e) {
        e.preventDefault();
        const $form = $('#orderExportForm');
        const searchText = $('#neworder_datatable_filter input[type="search"]').val() || '';

        const payload = {
            status: @json($status),
            weightOrder: weightOrder,
            courier_name: courierName,
            shippingCompanyId: shippingCompanyId,
            is_shopify_order: is_shopify_order,
            is_portal_order: is_portal_order,
            is_wordpress_order: is_wordpress_order,
            status_courier: statusCourier,
            search: searchText
        };

        $form.find('input.export-dynamic').remove();

        Object.keys(payload).forEach(function(key) {
            if (payload[key] === null || payload[key] === undefined || payload[key] === '') {
                return;
            }

            $('<input>', {
                type: 'hidden',
                name: key,
                value: payload[key],
                class: 'export-dynamic'
            }).appendTo($form);
        });

        $form.trigger('submit');
    });
});
//Order End Listing

function cancelNewOrder(obj, e) {
    e.preventDefault();
    let orderPrefix = $(obj).attr('data-order_prefix') || '';
    Swal.fire({
        title: "Are you sure you want to cancel this order?",
        html: `<div style="font-weight:bold; margin-bottom:10px; font-size: 1.2rem;">${orderPrefix}</div>
                    <div class="checkbox checkbox-purple" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <input type="checkbox" id="cancel_on_channel" style="width: 18px; height: 18px;">
                        <label for="cancel_on_channel" style="margin-bottom: 0; font-size: 1rem;"> Cancel order on channel </label>
                    </div>`,
        type: "warning",
        showCancelButton: !0,
        confirmButtonColor: "#31ce77",
        cancelButtonColor: "#f34943",
        confirmButtonText: "Yes, Cancel!"
    }).then(function(t) {
        if (t.value) {
            var isChecked = Swal.getPopup().querySelector('#cancel_on_channel').checked;
            var url = $(obj).attr('href');
            if (isChecked) {
                url += "&cancel_on_channel=1";
            }
            location.href = url;
        }
    })
}

function deleteOrder(obj, e) {
    e.preventDefault();
    Swal.fire({
        title: "Are you sure you want to permanently delete this order?",
        text: "You can't undo this action.",
        type: "warning",
        showCancelButton: !0,
        confirmButtonColor: "#31ce77",
        cancelButtonColor: "#f34943",
        confirmButtonText: "Yes, Delete!"
    }).then(function(t) {
        if (t.value) {
            location.href = obj;
        }
    })
}

function cancelOrderApi(obj, e) {
    e.preventDefault();
    let orderPrefix = $(obj).attr('data-order_prefix') || '';
    Swal.fire({
        title: "Do you want to cancel the Order or Shipment?",
        html: `<div style="font-weight:bold; margin-bottom:10px; font-size: 1.2rem;">${orderPrefix}</div>
                       <div class="checkbox checkbox-purple" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <input type="checkbox" id="api_cancel_on_channel" style="width: 18px; height: 18px;">
                            <label for="api_cancel_on_channel" style="margin-bottom: 0; font-size: 1rem;"> Cancel order on channel </label>
                       </div>`,
        type: "warning",
        showCancelButton: !0,
        confirmButtonColor: "#31ce77",
        cancelButtonColor: "#f34943",
        confirmButtonText: "Yes, Canceled!"
    }).then(function(t) {
        if (t.value) {
            var isChecked = Swal.getPopup().querySelector('#api_cancel_on_channel').checked ? 1 : 0;
            var url = $(obj).attr('href');
            if (isChecked) {
                url += "&cancel_on_channel=1";
            }
            location.href = url;
        }
    })
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
                function(res) {
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

$('#warehouseForm').submit(function(event) {
    event.preventDefault();
    $(this).find('button').prop('disabled', true);
    var formData = new FormData(this);
    formData.append('_token', "{{ csrf_token() }}");
    $.ajax({
        async: true,
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        dataType: 'Json',
        success: function(res) {
            $('button').prop('disabled', false);
            if (res.status == "error") {
                $('#warehouse_error').text(res.msg)
            } else {
                toastrMsg(res.status, res.msg);
                $('.addwarehousemodal').modal('hide');
            }
        }
    });
});

$(document).ready(function() {
    $('#downloadAllLable').hide();
    $('#bulkShipmentOrder').hide();
    $('#bulkUpdateOrder').hide();
    $('#bulkShipmentCancel').hide();
    $('#bulkSyncChannel').hide();
    $('#bulkSchedulePickup').hide();

    // Master Checkbox Click Event
    $('#checkAll').on('change', function() {
        let isChecked = $(this).prop('checked');
        $(".order-checkbox").prop('checked', isChecked);
        $('#downloadAllLable').toggle(isChecked);
        $('#bulkShipmentOrder').toggle(isChecked);
        $('#bulkUpdateOrder').toggle(isChecked);
        $('#bulkShipmentCancel').toggle(isChecked);
        $('#bulkSyncChannel').toggle(isChecked);
        $('#bulkSchedulePickup').toggle(isChecked);
    });

    // Individual Checkbox Click Event
    $(document).on('change', '.order-checkbox', function() {
        let totalCheckboxes = $('.order-checkbox').length;
        let checkedCheckboxes = $('.order-checkbox:checked').length;

        $('#checkAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        $('#downloadAllLable').toggle(checkedCheckboxes > 0);
        $('#bulkShipmentOrder').toggle(checkedCheckboxes > 0);
        $('#bulkUpdateOrder').toggle(checkedCheckboxes > 0);
        $('#bulkShipmentCancel').toggle(checkedCheckboxes > 0);
        $('#bulkSyncChannel').toggle(checkedCheckboxes > 0);
        $('#bulkSchedulePickup').toggle(checkedCheckboxes > 0);
    });

    $('#downloadAllLable').on('click', function() {
        let orderIds = $(".order-checkbox:checked").map(function() {
            return $(this).val();
        }).get();

        if (orderIds.length === 0) {
            toastrMsg('error', 'Choose at least one item to download.');
            return;
        }

        // Send as array: order_ids[]=1&order_ids[]=2
        window.location.href = "{{ route('order.download-all-lable') }}" + "?" +
            $.param({
                order_ids: orderIds
            });
    });
});

$(document).on('click', '#bulkUpdateOrder', function() {
    $('#lbhBulkModal').modal('show');
});

$(document).on('click', '#bulkShipmentOrder', function() {
    $('#shipBulkModal').modal('show');
});

$(document).on('click', '#bulkSyncChannel', function(e) {
    e.preventDefault();

    // Collect selected order IDs
    let selectedOrderIds = [];
    $('.order-checkbox:checked').each(function() {
        selectedOrderIds.push($(this).val());
    });

    // No selection check
    if (selectedOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Orders Selected!',
            text: 'Please select at least one order to sync.',
        });
        return;
    }

    Swal.fire({
        title: 'Sync Orders',
        text: "Do you want to sync selected orders with the channel?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, sync them!'
    }).then((result) => {
        if (result.value) {
            run_waitMe($('body'), 1, 'win8');
            $.ajax({
                url: "{{ route('order.bulk-sync-channel') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    order_ids: selectedOrderIds
                },
                success: function(response) {
                    $('body').waitMe('hide');
                    if (response.status === 'success') {
                        Swal.fire(
                            'Synced!',
                            response.msg || 'Orders have been synced successfully.',
                            'success'
                        ).then(() => {
                            window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.msg || 'Something went wrong.',
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    $('body').waitMe('hide');
                    Swal.fire(
                        'Error!',
                        'An error occurred while syncing orders.',
                        'error'
                    );
                }
            });
        }
    });
});

$(document).on('click', '#bulkShipmentCancel', function(e) {
    e.preventDefault();

    // Collect selected order IDs
    let selectedOrderIds = [];
    $('.order-checkbox:checked').each(function() {
        selectedOrderIds.push($(this).val());
    });
    // No selection
    if (selectedOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Orders Selected!',
            text: 'Please select at least one order to cancel.',
        });
        return;
    }

    // SweetAlert confirmation
    Swal.fire({
        title: 'Are you sure?',
        html: `<div>Do you want to cancel selected order/shipments?</div>
                       <div class="checkbox checkbox-purple" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;">
                            <input type="checkbox" id="bulk_cancel_on_channel" style="width: 18px; height: 18px;">
                            <label for="bulk_cancel_on_channel" style="margin-bottom: 0; font-size: 1rem;"> Cancel order on channel </label>
                       </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.value) {
            var isChecked = Swal.getPopup().querySelector('#bulk_cancel_on_channel').checked ? 1 :
                0;

            run_waitMe($('body'), 1, 'win8');
            // Disable button to avoid double click
            $("#bulkShipmentCancel").prop("disabled", true);

            // Make AJAX request without form
            $.ajax({
                url: "{{ url('order/bulk-shipment-cancel') }}", // ← YOUR CANCEL URL
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    order_ids: selectedOrderIds,
                    status: @json($status),
                    cancel_on_channel: isChecked
                },
                dataType: 'json',

                success: function(res) {
                    $('body').waitMe('hide');
                    $("#bulkShipmentCancel").prop("disabled", false);

                    if (res.status === "success") {
                        let htmlMsg = `
								<div class="swal-main-msg">
									${res.msg || 'Operation completed.'}
								</div>
							`;

                        if (res.summary) {
                            htmlMsg += `
									<div class="swal-summary-wrapper">
										<div class="swal-summary-title">Summary</div>
										<div class="swal-summary-grid">
											<div class="swal-summary-item swal-summary-processed">
												<span class="label">Processed</span>
												<span class="value">${res.summary.processed ?? res.summary.attempted ?? 0}</span>
											</div>
											<div class="swal-summary-item swal-summary-cancelled">
												<span class="label">Cancelled</span>
												<span class="value">${res.summary.cancelled ?? 0}</span>
											</div>
											<div class="swal-summary-item swal-summary-skipped">
												<span class="label">Skipped</span>
												<span class="value">${res.summary.skipped ?? 0}</span>
											</div>
											<div class="swal-summary-item swal-summary-errors">
												<span class="label">Errors</span>
												<span class="value">${res.summary.errors ?? 0}</span>
											</div>
										</div>
									</div>
								`;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Completed',
                            html: htmlMsg,
                            confirmButtonText: "OK",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            buttonsStyling: false,
                            customClass: {
                                popup: 'swal2-custom-popup',
                                title: 'swal2-custom-title',
                                htmlContainer: 'swal2-custom-html',
                                confirmButton: 'swal2-custom-confirm-btn'
                            }
                        }).then(() => {
                            window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: res.msg || 'Something went wrong.',
                            confirmButtonText: "OK",
                            buttonsStyling: false,
                            customClass: {
                                popup: 'swal2-custom-popup',
                                title: 'swal2-custom-title',
                                confirmButton: 'swal2-custom-confirm-btn swal2-custom-confirm-error'
                            }
                        });
                    }

                },

                error: function() {
                    $('body').waitMe('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.'
                    });

                    $("#bulkShipmentCancel").prop("disabled", false);
                }
            });
        }
    });
});

$(document).on('click', '#bulkSchedulePickup', function(e) {
    e.preventDefault();
    let selectedOrderIds = [];
    let nonDelhiverySelected = false;

    let firstValidCompanyId = null;
    let currentCompanyId = null;

    $('.order-checkbox:checked').each(function() {
        let companyId = $(this).attr('data-shipping-company-id');
        if (companyId == '1' || companyId == '2') {
            if (!firstValidCompanyId) firstValidCompanyId = companyId;
            selectedOrderIds.push($(this).val());
        } else {
            nonDelhiverySelected = true;
        }
    });

    if (selectedOrderIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Selection!',
            text: nonDelhiverySelected ?
                'Pickup scheduling is only supported for Delhivery and ShipMozo orders. Please select Delhivery or ShipMozo orders only.' :
                'Please select at least one order to schedule pickup.',
        });
        return;
    }

    if (nonDelhiverySelected) {
        Swal.fire({
            icon: 'info',
            title: 'Partial Selection',
            text: 'Some selected orders are not from Delhivery/ShipMozo and will be excluded from the pickup request. Proceed with ' +
                selectedOrderIds.length + ' eligible orders?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.value) {
                showPickupModal(selectedOrderIds, firstValidCompanyId);
            }
        });
    } else {
        showPickupModal(selectedOrderIds, firstValidCompanyId);
    }
});

function showPickupModal(ids, defaultCompanyId) {
    $('#selected_order_ids').val(ids.join(','));
    // Pre-select based on first valid order, default to Delhivery (1)
    let companyIdToSelect = defaultCompanyId || '1';
    $('#bulkSchedulePickupModal select[name="shipping_company_id"]').val(companyIdToSelect).trigger('change');
    $('#bulkSchedulePickupModal').modal('show');
}

$('#bulkSchedulePickupForm').submit(function(event) {
    event.preventDefault();
    run_waitMe($('body'), 1, 'win8');
    var formData = new FormData(this);
    $.ajax({
        async: true,
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        dataType: 'Json',
        success: function(res) {
            $('body').waitMe('hide');
            if (res.status == "error") {
                toastrMsg('error', res.msg);
            } else {
                $('#bulkSchedulePickupModal').modal('hide');
                toastrMsg(res.status, res.msg || 'Pickup scheduled successfully.');
                setTimeout(function() {
                    window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                }, 1000);
            }
        },
        error: function() {
            $('body').waitMe('hide');
            toastrMsg('error', 'Something went wrong. Please try again.');
        }
    });
});

$('#bulk_pickup_start_time').on('change', function() {
    const pickup_end_time = $(this).find('option:selected').data('pickup_end_time');
    $('#bulk_pickup_end_time').val(pickup_end_time);
});

$('#bulk_pickup_date').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: 0,
    onSelect: function(selectedDate) {
        const now = new Date();
        const selected = new Date(selectedDate + 'T00:00:00');

        $('#bulk_pickup_start_time option').each(function() {
            const slot = $(this).text();
            if (!slot || !slot.includes('-')) return;

            const endTimeStr = slot.split(' - ')[1];
            const {
                hours,
                minutes
            } = parseTimeTo24Hour(endTimeStr);

            const slotEndDateTime = new Date(selected);
            slotEndDateTime.setHours(hours, minutes, 0, 0);

            if (now.toDateString() === selected.toDateString() && now >= slotEndDateTime) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false);
            }
        });
    }
});

function parseTimeTo24Hour(timeStr) {
    const [time, modifier] = timeStr.trim().split(' ');
    let [hours, minutes] = time.split(':').map(Number);
    if (modifier === 'PM' && hours < 12) hours += 12;
    if (modifier === 'AM' && hours === 12) hours = 0;
    return {
        hours,
        minutes
    };
}

$('.orderAll').on('click', function(e) {
    var studentIdArr = [];
    $(".order-checkbox:checked").each(function() {
        studentIdArr.push($(this).attr('data-order_id'));
    });
    if (studentIdArr.length <= 0) {
        toastrMsg('error', 'Choose min one item to remove.');
    } else {
        Swal.fire({
            title: "Are you sure you want to pickup all order?",
            type: "warning",
            showCancelButton: !0,
            confirmButtonColor: "#31ce77",
            cancelButtonColor: "#f34943",
            confirmButtonText: "Yes, Pickup it!"
        }).then(function(t) {
            if (t.value) {
                var stuId = studentIdArr.join(",");
                $('#order_id_all').val(stuId);
                $('.pickupscheduleall').modal('show');
            }
        })
    }
});

$('#pickupScheduleAllForm').submit(function(event) {
    event.preventDefault();
    run_waitMe($('body'), 1, 'win8')
    var formData = new FormData(this);
    formData.append('_token', '{{ csrf_token() }}');
    $.ajax({
        async: true,
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        dataType: 'Json',
        success: function(res) {
            $('body').waitMe('hide');
            if (res.status == "error") {
                $('#schdule_all_error').text(res.msg)
            } else {
                $('.pickupscheduleall').modal('hide');
                toastrMsg(res.status, res.msg);
                setTimeout(function() {
                    window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                }, 1000)
            }
        }
    });
});

$('#pickupScheduleForm').submit(function(event) {
    event.preventDefault();
    //	$(this).find('button').prop('disabled',true);
    run_waitMe($('body'), 1, 'win8')
    var formData = new FormData(this);
    formData.append('_token', '{{ csrf_token() }}');
    $.ajax({
        async: true,
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        dataType: 'Json',
        success: function(res) {
            $('body').waitMe('hide');
            if (res.status == "error") {
                $('#schdule_error').text(res.msg)
            } else {
                $('.pickupschedulemodal').modal('hide');
                toastrMsg(res.status, res.msg);
                setTimeout(function() {
                    window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
                }, 1000)
            }
        }
    });
});

function schedulePickup(obj, event) {
    event.preventDefault();
    var order_id = $(obj).attr('data-order_id');
    var shipping_id = $(obj).attr('data-shipping_id');
    var address = $(obj).attr('data-address');
    var msg = $(obj).attr('data-msg');
    $('.show_msg').html(msg);
    $('#order_id').val(order_id);
    $('#shipping_id').val(shipping_id);
    $('.show_pickup_msg').html(address);
    $('.pickupschedulemodal').modal('show');
}

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

// Order Filter Modal - Dynamic Backend Loading
$('#orderFilterModal').on('click', function() {
    loadOrderFilters();
});

function loadOrderFilters() {
    if ($('#orderFilterModalBody').html().trim()) {
        $('#orderFilterModalShow').modal('show');
        return false;
    }

    $.ajax({
        url: "{{ route('order.filter-options') }}",
        type: "GET",
        dataType: "json",
        success: function(response) {
            $('#orderFilterModalBody').html(response.view);
            $('#orderFilterModalShow').modal('show');
        }
    });
}

function redirectUrl(status = null) {
    const urlParams = new URLSearchParams(window.location.search);
    if (status !== null) {
        urlParams.set('status', status);
    }
    window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
}

// Load Status Counts Dynamically
function loadStatusCounts() {
    $.ajax({
        url: "{{ route('order.status-counts') }}",
        type: "GET",
        data: {
            weight_order: weightOrder,
            is_shopify_order: is_shopify_order || '',
            is_portal_order: is_portal_order || '',
            is_wordpress_order: is_wordpress_order || ''
        },
        dataType: "json",
        success: function(response) {
            if (response.status === 'success' && response.counts) {
                console.log('Status counts received:', response.counts);
                // Update each status count
                $.each(response.counts, function(status, count) {
                    // Find all elements with this status and update the count
                    $('li').filter(function() {
                        return $(this).text().trim().startsWith(status);
                    }).find('.order-tab-count').text(count);

                    // Also update dropdown items
                    $('.dropdown-item').filter(function() {
                        return $(this).text().trim().startsWith(status);
                    }).find('.order-tab-count').text(count);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load status counts:', error);
        }
    });
}

// Load counts on page load
loadStatusCounts();

// Optionally reload counts when datatable reloads
if (typeof table !== 'undefined') {
    table.on('draw', function() {
        loadStatusCounts();
    });
}

$(document).on('click', '.manual-status-update-trigger', function(e) {
    e.preventDefault();
    e.stopPropagation();
    let orderId = $(this).data('id');
    let rawStatus = $(this).data('raw-status');

    $('#manual_status_order_id').val(orderId);
    $('#manual_status_select option[data-temp="true"]').remove();

    let $select = $('#manual_status_select');
    let $exists = $select.find('option').filter(function() {
        return $(this).val().toLowerCase() === String(rawStatus).toLowerCase();
    });

    if ($exists.length > 0) {
        $select.val($exists.val());
    } else if (rawStatus) {
        $select.append(`<option value="${rawStatus}" data-temp="true">${rawStatus}</option>`);
        $select.val(rawStatus);
    } else {
        $select.val('');
    }

    $(this).closest('.dropdown-menu').removeClass('show');
    $(this).closest('.btn-group').find('.dropbtn').attr('aria-expanded', 'false');
    setTimeout(function() {
        $('#manualStatusModal').modal('show');
    }, 150);
});

$('#manualStatusForm').submit(function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    run_waitMe($('body'), 1, 'win8');

    $.post("{{ route('order.manual-status-update') }}", formData, function(res) {
        $('body').waitMe('hide');
        if (res.status === 'success') {
            toastrMsg('success', res.msg);
            $('#manualStatusModal').modal('hide');
            setTimeout(function() {
                window.location.href = "{{ route('order') }}?weight_order=" + weightOrder + "&status=New";
            }, 800);
        } else {
            toastrMsg('error', res.msg || 'Something went wrong!');
        }
    }).fail(function(xhr) {
        $('body').waitMe('hide');
        toastrMsg('error', xhr.responseJSON?.message || 'Something went wrong!');
    });
});
</script>
@endpush



