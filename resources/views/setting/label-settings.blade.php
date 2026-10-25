@extends('layouts.backend.app')

@section('title', config('setting.company_name') . ' - Label Settings')
@section('header_title', 'Label Settings')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <!-- Page Title -->
                {{-- <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Shipping Label Config</h4>
                            <p class="text-muted">Configure label size, content that you want to show in the shipping label
                            </p>
                        </div>
                    </div>
                </div> --}}

                <form action="{{ route('label.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Config Column -->
                        <div class="col-lg-12">
                            <div class="">
                                <div class="main-order-001">

                                    <!-- Label Size -->
                                    <h5 class="header-title mb-3">LABEL SIZE</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="label_size_thermal" name="label_size"
                                                    value="thermal" class="custom-control-input"
                                                    {{ (optional($preference)->label_size ?? 'thermal') == 'thermal' ? 'checked' : '' }}>
                                                <label class="custom-control-label w-100" for="label_size_thermal">
                                                    <div class="card p-3 border brd text-center mb-0 mt-2" style="box-shadow: none;">
                                                        <i class="mdi mdi-printer-settings mb-2"
                                                            style="font-size: 24px;"></i>
                                                        <div class="font-weight-bold">Thermal Printer</div>
                                                        <small>4 in x 6 in</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="label_size_desktop" name="label_size"
                                                    value="desktop" class="custom-control-input"
                                                    {{ (optional($preference)->label_size ?? 'thermal') == 'desktop' ? 'checked' : '' }}>
                                                <label class="custom-control-label w-100" for="label_size_desktop">
                                                    <div class="card p-3 border brd text-center mb-0 mt-2" style="box-shadow: none;">
                                                        <i class="mdi mdi-file-document-outline mb-2"
                                                            style="font-size: 24px;"></i>
                                                        <div class="font-weight-bold">Desktop Printer</div>
                                                        <small>8 in x 11 in</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Company Details -->
                                    <h5 class="header-title mb-3">COMPANY DETAILS</h5>
                                    <div class="form-group mb-4">
                                        <!-- Using hidden input to handle 'Company Logo' toggle if needed, or simply label it -->
                                        <label class="font-weight-bold">Company Logo</label>
                                        <p class="text-muted font-13">Brand Name "{{ config('setting.company_name') }}" will
                                            be printed. If you want to print Logo, please select Company Logo and upload it.
                                        </p>

                                        <div class="mt-2 text-center p-4 border border-light brd" style="background-color: #f2f6fab0;">
                                            @if (optional($preference)->label_company_logo)
                                                <div class="mb-3">
                                                    <img src="{{ asset('storage/settings/label/' . $preference->label_company_logo) }}"
                                                        alt="Current Logo" style="max-height: 80px;">
                                                </div>
                                            @else
                                                <div class="text-muted mb-2"><i class="mdi mdi-image-outline display-4"></i>
                                                </div>
                                            @endif

                                            <div class="custom-file" style="max-width: 300px; margin: 0 auto;">
                                                <input type="file" class="custom-file-input" id="label_company_logo"
                                                    name="label_company_logo" accept="image/png, image/jpeg">
                                                <label class="custom-file-label" for="label_company_logo">Upload Company
                                                    Logo</label>
                                            </div>
                                            <div class="mt-2"><small class="text-muted">PNG, JPEG. Max 250KB</small></div>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Payment Details -->
                                    <h5 class="header-title mb-3">PAYMENT DETAILS</h5>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input"
                                                id="label_show_invoice_value" name="label_show_invoice_value" value="1"
                                                {{ optional($preference)->label_show_invoice_value ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="label_show_invoice_value">Show Invoice
                                                value for Prepaid orders</label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input"
                                                id="label_show_invoice_number" name="label_show_invoice_number"
                                                value="1"
                                                {{ optional($preference)->label_show_invoice_number ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="label_show_invoice_number">Show invoice
                                                number</label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input" id="label_show_weight"
                                                name="label_show_weight" value="1"
                                                {{ optional($preference)->label_show_weight ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="label_show_weight">Show weight</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="label_show_cod_amount"
                                                name="label_show_cod_amount" value="1"
                                                {{ optional($preference)->label_show_cod_amount ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="label_show_cod_amount">Show COD
                                                amount</label>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Seller & Return Details -->
                                    <h5 class="header-title mb-3">SELLER & RETURN DETAILS</h5>
                                    <p class="text-muted font-13">If Seller address is not given during manifestation, we
                                        will print Pickup Location Name and Address.</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_seller_name" name="label_show_seller_name"
                                                    value="1"
                                                    {{ optional($preference)->label_show_seller_name ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_seller_name">Seller
                                                    Name</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_company_name" name="label_show_company_name"
                                                    value="1"
                                                    {{ optional($preference)->label_show_company_name ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_company_name">Company
                                                    Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_seller_address" name="label_show_seller_address"
                                                    value="1"
                                                    {{ optional($preference)->label_show_seller_address ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_seller_address">Seller
                                                    Address</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_seller_mobile" name="label_show_seller_mobile"
                                                    value="1"
                                                    {{ optional($preference)->label_show_seller_mobile ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_seller_mobile">Seller
                                                    Mobile</label>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Product Details -->
                                    <h5 class="header-title mb-3">PRODUCT DETAILS</h5>
                                    <p class="text-muted font-13">Directly manifested shipments via bulk CSV Upload or API
                                        manifestation, Product Description will be displayed instead of Product Name & SKU
                                        Code.</p>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_quantity" name="label_show_quantity" value="1"
                                                    {{ optional($preference)->label_show_quantity ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_quantity">Show
                                                    Quantity</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_item_price" name="label_show_item_price"
                                                    value="1"
                                                    {{ optional($preference)->label_show_item_price ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_item_price">Show Item
                                                    Price</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="label_show_taxes"
                                                name="label_show_taxes" value="1"
                                                {{ optional($preference)->label_show_taxes ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="label_show_taxes">Show Taxes &
                                                Taxable Amount</label>
                                        </div>
                                    </div>

                                    <div class="px-sm-2 py-2 mb-2">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="custom-control custom-switch mr-3">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_show_product_name" name="label_show_product_name"
                                                    value="1"
                                                    {{ optional($preference)->label_show_product_name ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_product_name">Show
                                                    Product Name</label>
                                            </div>
                                            <div class="ml-auto d-flex align-items-center">
                                                <span class="mr-2">Trim product name upto</span>
                                                <input type="number" name="label_product_name_limit"
                                                    class="form-control form-control-sm text-center" style="width: 70px;"
                                                    value="{{ optional($preference)->label_product_name_limit ?? 128 }}">
                                                <span class="ml-2">Characters</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-sm-2 py-2 mb-2">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="custom-control custom-switch mr-3">
                                                <input type="checkbox" class="custom-control-input" id="label_show_sku"
                                                    name="label_show_sku" value="1"
                                                    {{ optional($preference)->label_show_sku ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_show_sku">Show SKU
                                                    Code</label>
                                            </div>
                                            <div class="ml-auto d-flex align-items-center">
                                                <span class="mr-2">Trim SKU code upto</span>
                                                <input type="number" name="label_sku_limit"
                                                    class="form-control form-control-sm text-center" style="width: 70px;"
                                                    value="{{ optional($preference)->label_sku_limit ?? 32 }}">
                                                <span class="ml-2">Characters</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-sm-2 py-2 mb-2">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="custom-control custom-switch mr-3">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="label_print_multiple" name="label_print_multiple" value="1"
                                                    {{ optional($preference)->label_print_multiple ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="label_print_multiple">Print
                                                    multiple Pages</label>
                                            </div>
                                            <div class="ml-auto d-flex align-items-center">
                                                <span class="mr-2">Show upto</span>
                                                <input type="number" name="label_print_limit"
                                                    class="form-control form-control-sm text-center" style="width: 70px;"
                                                    value="{{ optional($preference)->label_print_limit ?? 2 }}">
                                                <span class="ml-2">Products</span>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 d-block">Details of upto 2 products will be listed
                                            upfront and remaining products will be listed as '+xx more SKUs'</small>
                                    </div>

                                    <div class="mt-4 text-right">
                                        <button type="submit" class="new-submit-btn ">Save Changes</button>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

@endsection
