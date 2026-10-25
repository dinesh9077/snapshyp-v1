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
                                    <a href="">
                                        Connect Store
                                        <i class="mdi mdi-arrow-top-right"></i>
                                    </a>
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
@endsection