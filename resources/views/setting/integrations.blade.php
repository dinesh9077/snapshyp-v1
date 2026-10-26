@extends('layouts.backend.app')

@section('title', config('setting.company_name') . ' - Integrations')
@section('header_title', 'Integrations')

@section('content')
    <style>
        .intage-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 15px;
            color: #333;
        }

        .intage-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 6px;
        }

        .intage-icon img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="integrations-page mt-3">
                    <div class="row">

                        {{-- Swagger --}}
                        <div class="col-xl-3 col-lg-4 col-sm-6 mb-4">
                            <div class="integration-card h-100">
                                <div class="intage-card-header">
                                    <div class="intage-icon">
                                        <img src="{{ asset('assets/images/material-icon-theme_swagger.png') }}"
                                            alt="Swagger Icon">
                                    </div>
                                    Swagger API
                                </div>
                                <div class="intage-card-body">
                                    <p>
                                        Access developer-friendly API documentation to integrate our platform
                                        programmatically.
                                        You can explore all endpoints, authentication methods, and test API calls instantly.
                                    </p>
                                </div>
                                <div class="intage-card-footer">
                                    <a href="{{ route('api.developer') }}">
                                        View Documentation
                                        <i class="mdi mdi-arrow-top-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="integration-card h-100">
                                <div class="intage-card-header">
                                    <div class="intage-icon">
                                        <img src="{{ asset('assets/images/shopify.png') }}" alt="Shopify Icon">
                                    </div>
                                    Shopify Integration
                                </div>
                                <div class="intage-card-body">
                                    <p>
                                        Connect your Shopify store to automatically sync and fulfill orders directly from
                                        our dashboard.
                                        Manage shipping, tracking, and fulfillment updates.
                                    </p>
                                </div>
                                <div class="intage-card-footer">
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <a href="javascript:void(0)" data-toggle="modal" data-target="#shopifyInstallModal">
                                            Install on my store
                                            <i class="mdi mdi-arrow-top-right"></i>
                                        </a>
                                        <a href="{{ config('services.shopify_app_store_url') }}" target="_blank" rel="noopener"
                                            class="small text-muted" title="Open the Snapshyp listing on the Shopify App Store">
                                            App Store
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- WhatsApp Services --}}
                        <!--<div class="col-xl-3 col-lg-4 col-sm-6 mb-4">-->
                        <!--    <div class="integration-card h-100">-->
                        <!--        <div class="intage-card-header">-->
                        <!--            <div class="intage-icon">-->
                        <!--                <img src="{{ asset('assets/images/image11.png') }}" alt="WhatsApp Icon">-->
                        <!--            </div>-->
                        <!--            WhatsApp Services-->
                        <!--        </div>-->
                        <!--        <div class="intage-card-body">-->
                        <!--            <p>-->
                        <!--                Send automated WhatsApp notifications to your customers for shipment updates,-->
                        <!--                delivery confirmations, and more using our official WhatsApp integration service.-->
                        <!--            </p>-->
                        <!--        </div>-->
                        <!--        <div class="intage-card-footer">-->
                        <!--            <a href="{{ route('user.whatsapp.services') }}">-->
                        <!--                View Documentation-->
                        <!--                <i class="mdi mdi-arrow-top-right"></i>-->
                        <!--            </a>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->

                        {{-- Label Settings --}}
                        {{-- <div class="col-xl-3 col-lg-4 col-sm-6 mb-4">
                            <div class="integration-card h-100">
                                <div class="intage-card-header">
                                    <div class="intage-icon">
                                        <img src="{{ asset('assets/images/label-icon-01.png') }}" alt="Label Icon">
                                    </div>
                                    Label Settings
                                </div>
                                <div class="intage-card-body">
                                    <p>
                                        Configure your shipping labels, customize layout, add logos, and manage printer
                                        settings for thermal or desktop printing.
                                    </p>
                                </div>
                                <div class="intage-card-footer">
                                    <a href="{{ route('label.settings') }}">
                                        Configure Labels
                                        <i class="mdi mdi-arrow-top-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div> --}}
                    </div> {{-- row --}}
                </div>
            </div>
        </div>
    </div>
    {{-- Direct install: our /install route sends the seller to their store's Shopify approval screen --}}
    <div class="modal fade" id="shopifyInstallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Install on your Shopify store</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <form id="shopifyInstallForm" action="{{ route('shopify.install') }}" method="get" target="_blank">
                    <div class="modal-body">
                        <label for="shopify_store" class="mb-1">Store address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shopify_store" name="shop" required
                                placeholder="mystore" value="{{ str_replace('.myshopify.com', '', (string) auth()->user()->shop) }}"
                                autocomplete="off">
                            <div class="input-group-append">
                                <span class="input-group-text">.myshopify.com</span>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Find it in Shopify admin → Settings → Domains (the <b>.myshopify.com</b> address). You will
                            approve the app on Shopify, then log in to {{ config('setting.company_name') }} inside the app.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ config('services.shopify_app_store_url') }}" target="_blank" rel="noopener" class="mr-auto small">
                            Or install from the App Store
                        </a>
                        <button type="submit" class="btn-main-1">Install</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Accept "mystore", "mystore.myshopify.com" or a pasted store URL
        $('#shopifyInstallForm').on('submit', function() {
            let store = $.trim($('#shopify_store').val()).toLowerCase()
                .replace(/^https?:\/\//, '').split('/')[0];
            if (store && store.indexOf('.') === -1) {
                store += '.myshopify.com';
            }
            $('#shopify_store').val(store);
            $('#shopifyInstallModal').modal('hide');
        });
    </script>
@endpush