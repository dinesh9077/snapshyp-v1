@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - General Setting')
@section('header_title', 'General Setting')
@section('content')

    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            font-weight: 500;
            background: transparent;
            border-radius: 10px 10px 0 0;
        }
         .nav-tabs .nav-link:hover{
            color: var(--new-color-prime);
            border-bottom: 3px solid var(--new-color-prime);
         }

        .nav-tabs .nav-link.active {
            color: #fff;
            border-bottom: 3px solid var(--new-color-prime);
            background: var(--new-color-prime);
        }

        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }


        .main-data-teble-1 {
            min-height: auto !important;
            padding-bottom: 20px !important;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs mb-1" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="portal-tab" data-toggle="tab" data-target="#portal"
                                    type="button" role="tab">
                                    <i class="mdi mdi-domain"></i> Portal Settings
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="business-tab" data-toggle="tab" data-target="#business"
                                    type="button" role="tab">
                                    <i class="mdi mdi-briefcase"></i> Business Settings
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payment-tab" data-toggle="tab" data-target="#payment"
                                    type="button" role="tab">
                                    <i class="mdi mdi-credit-card"></i> Payment Gateway
                                </button>
                            </li>
                        </ul>

                        <form class="generalForm" id="settingsForm" action="{{ route('setting.update') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf

                            <!-- Tab Content -->
                            <div class="tab-content" id="settingsTabContent">

                                <!-- Portal Settings Tab -->
                                <div class="tab-pane fade show active" id="portal" role="tabpanel">
                                    <div class="main-rowx-1">
                                        <div class="main-row main-data-teble-1">
                                            <h5 class="setting-label">Company Information & Branding</h5>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="company_name">Company Name</label>
                                                        <input type="text" name="company_name"
                                                            value="{{ config('setting.company_name') }}"
                                                            placeholder="Company Name">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="company_contact">Company Contact Number</label>
                                                        <input type="text" name="company_contact"
                                                            value="{{ config('setting.company_contact') }}"
                                                            placeholder="Company Contact Number">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="company_email">Company Email</label>
                                                        <input type="email" name="company_email"
                                                            value="{{ config('setting.company_email') }}"
                                                            placeholder="Company Email">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="company_website">Company Website</label>
                                                        <input type="text" name="company_website"
                                                            value="{{ config('setting.company_website') }}"
                                                            placeholder="Company Website">
                                                    </div>
                                                </div>
                                                <div class="col-lg-8 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="company_address">Company Address</label>
                                                        <input type="text" name="company_address"
                                                            value="{{ config('setting.company_address') }}"
                                                            placeholder="Company Address">
                                                    </div>
                                                </div>

                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="login_logo">Login Logo</label>
                                                        <input class="form-control" type="file" name="login_logo"
                                                            id="login_logo">
                                                        @if (!empty(config('setting.login_logo')))
                                                            <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                                                                style="width: 25%; margin: 10px 0 0 0; border-radius: 12px; border: 1px solid #ddd;">
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="header_logo">Header Logo</label>
                                                        <input class="form-control" type="file" name="header_logo"
                                                            id="header_logo">
                                                        @if (!empty(config('setting.header_logo')))
                                                            <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}"
                                                                style="width: 25%; margin: 10px 0 0 0; border-radius: 12px; border: 1px solid #ddd;">
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="header_logo">Small Header Logo</label>
                                                        <input class="form-control" type="file"
                                                            name="small_header_logo" id="small_header_logo">
                                                        @if (!empty(config('setting.small_header_logo')))
                                                            <img src="{{ asset('storage/settings/' . config('setting.small_header_logo')) }}"
                                                                style="width: 25%; margin: 10px 0 0 0; border-radius: 12px; border: 1px solid #ddd;">
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="fevicon_icon">Fevicon Icon</label>
                                                        <input class="form-control gt-txt" type="file"
                                                            name="fevicon_icon" id="fevicon_icon">
                                                        @if (!empty(config('setting.fevicon_icon')))
                                                            <img src="{{ asset('storage/settings/' . config('setting.fevicon_icon')) }}"
                                                                style="width: 25%; height: 10%; margin: 10px 0 0 0; border-radius: 12px; border: 1px solid #ddd;">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Portal Settings Tab -->

                                <!-- Business Settings Tab -->
                                <div class="tab-pane fade" id="business" role="tabpanel">
                                    <div class="main-rowx-1">
                                        <div class="main-row main-data-teble-1">
                                            <h5 class="setting-label">Business Configuration</h5>
                                            <div class="row align-items-end">
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="currency">Business Currency</label>
                                                        <select class="form-control" id="currency" name="currency">
                                                            <option value="₹">INDIA - INR - ₹</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="form-group my-2">
                                                        <label for="rate_calculator_show_to_user">
                                                            Show Rate Calculator to User
                                                        </label>
                                                        <select class="form-control" id="rate_calculator_show_to_user"
                                                            name="rate_calculator_show_to_user">
                                                            <option value="1"
                                                                {{ config('setting.rate_calculator_show_to_user', 1) == 1 ? 'selected' : '' }}>
                                                                Yes
                                                            </option>
                                                            <option value="0"
                                                                {{ config('setting.rate_calculator_show_to_user', 1) == 0 ? 'selected' : '' }}>
                                                                No
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="recharge_min_amount">Recharge Minimum Amount</label>
                                                        <input type="number" name="recharge_min_amount"
                                                            value="{{ config('setting.recharge_min_amount', 200) }}"
                                                            placeholder="Recharge Minimum Amount">
                                                    </div>
                                                </div>

                                                <div class="col-lg-3 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="recharge_max_amount">Recharge Maximum Amount</label>
                                                        <input type="number" name="recharge_max_amount"
                                                            value="{{ config('setting.recharge_max_amount', 10000) }}"
                                                            placeholder="Recharge Maximum Amount">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Business Settings Tab -->

                                <!-- Payment Gateway Tab -->
                                <div class="tab-pane fade" id="payment" role="tabpanel">
                                    <div class="main-rowx-1">
                                        <div class="main-row main-data-teble-1">
                                            <h5 class="setting-label">Razorpay Configuration</h5>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="razorpay_status">
                                                            Razorpay Status
                                                        </label>
                                                        <select class="form-control" id="razorpay_status"
                                                            name="razorpay_status">
                                                            <option value="1"
                                                                {{ config('setting.razorpay_status', 0) == 1 ? 'selected' : '' }}>
                                                                Active
                                                            </option>
                                                            <option value="0"
                                                                {{ config('setting.razorpay_status', 0) == 0 ? 'selected' : '' }}>
                                                                Inactive
                                                            </option>
                                                        </select>
                                                        <small class="text-muted">Enable or disable Razorpay payment
                                                            gateway</small>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="razorpay_key_id">
                                                            Razorpay API Key ID
                                                        </label>
                                                        <input type="text" class="form-control" id="razorpay_key_id"
                                                            name="razorpay_key_id"
                                                            value="{{ config('setting.razorpay_key_id') }}"
                                                            placeholder="Enter Razorpay API Key ID">
                                                        <small class="text-muted">Your Razorpay API Key ID</small>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6">
                                                    <div class="from-group my-2">
                                                        <label for="razorpay_key_secret">
                                                            Razorpay Secret Key
                                                        </label>
                                                        <input type="password" class="form-control"
                                                            id="razorpay_key_secret" name="razorpay_key_secret"
                                                            value="{{ config('setting.razorpay_key_secret') }}"
                                                            placeholder="Enter Razorpay Secret Key">
                                                        <small class="text-muted">Your Razorpay Secret Key (keep it
                                                            secure)</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info mt-3" role="alert">
                                                <i class="mdi mdi-information"></i>
                                                <strong>Note:</strong> To get your Razorpay credentials:
                                                <ol class="mb-0 mt-2">
                                                    <li>Login to your <a href="https://dashboard.razorpay.com/"
                                                            target="_blank">Razorpay Dashboard</a></li>
                                                    <li>Go to Settings → API Keys</li>
                                                    <li>Generate or copy your Key ID and Secret Key</li>
                                                    <li>Paste them here and set status to Active</li>
                                                </ol>
                                            </div>
                                    </div>
                                </div>
                                <hr class="my-3">
                                {{-- Zoho Payment Gateway Configuration (commented/disabled)
                                <div class="main-rowx-1">
                                    <div class="main-row main-data-teble-1">
                                        <h5 class="setting-label">Zoho Payment Gateway Configuration</h5>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6">
                                                <div class="from-group my-2">
                                                    <label for="zoho_status">
                                                        Zoho Status
                                                    </label>
                                                    <select class="form-control" id="zoho_status"
                                                        name="zoho_status">
                                                        <option value="1"
                                                            {{ config('setting.zoho_status', 0) == 1 ? 'selected' : '' }}>
                                                            Active
                                                        </option>
                                                        <option value="0"
                                                            {{ config('setting.zoho_status', 0) == 0 ? 'selected' : '' }}>
                                                            Inactive
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div class="from-group my-2">
                                                    <label for="zoho_client_id">
                                                        Client ID <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="zoho_client_id"
                                                        name="zoho_client_id"
                                                        value="{{ config('setting.zoho_client_id') }}"
                                                        placeholder="Enter Zoho Client ID">
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div class="from-group my-2">
                                                    <label for="zoho_client_secret">
                                                        Client Secret <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="password" class="form-control"
                                                        id="zoho_client_secret" name="zoho_client_secret"
                                                        value="{{ config('setting.zoho_client_secret') }}"
                                                        placeholder="Enter Zoho Client Secret">
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div class="from-group my-2">
                                                    <label for="zoho_account_id">
                                                        Account ID <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="zoho_account_id"
                                                        name="zoho_account_id"
                                                        value="{{ config('setting.zoho_account_id') }}"
                                                        placeholder="Enter Account ID">
                                                </div>
                                            </div>

                                            <div class="col-lg-8 col-md-6">
                                                <div class="form-group my-2">
                                                    <label for="zoho_redirect_url">
                                                        Redirect URL <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="zoho_redirect_url"
                                                        name="zoho_redirect_url"
                                                        value="{{ config('setting.zoho_redirect_url') }}"
                                                        placeholder="Enter Redirect URL (e.g., https://yourdomain.com/zoho/payment/callback)">
                                                    <small class="text-muted">Must match the Redirect URI in Zoho Developer Console.</small>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label for="zoho_refresh_token">
                                                        Refresh Token
                                                    </label>
                                                    <input type="text" class="form-control" id="zoho_refresh_token"
                                                        name="zoho_refresh_token"
                                                        value="{{ config('setting.zoho_refresh_token') }}"
                                                        placeholder="Enter Zoho Refresh Token">
                                                </div>
                                            </div>

                                            <div class="col-lg-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label for="zoho_access_token">
                                                        Access Token (Automatically updated)
                                                    </label>
                                                    <input type="text" class="form-control" id="zoho_access_token"
                                                        name="zoho_access_token"
                                                        value="{{ config('setting.zoho_access_token') }}"
                                                        placeholder="Access Token will be generated automatically" readonly>
                                                    <small class="text-muted">This token is automatically refreshed using the Refresh Token.</small>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                --}}
                                </div> 
                                <!-- End Payment Gateway Tab -->

                            </div>
                            <!-- End Tab Content -->

                            @if (config('permission.general_setting.edit'))
                                <div class="text-right mt-4">
                                    <button type="submit" class="new-submit-btn" id="submitBtn">
                                        <span class="btn-text">
                                            <i class="mdi mdi-content-save"></i> Save Settings
                                        </span>
                                        <span class="btn-loader d-none">
                                            <i class="mdi mdi-loading mdi-spin"></i> Saving...
                                        </span>
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>


        // AJAX Form Submission
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();

            const $submitBtn = $('#submitBtn');
            const $btnText = $submitBtn.find('.btn-text');
            const $btnLoader = $submitBtn.find('.btn-loader');

            // Disable button and show loader
            $submitBtn.prop('disabled', true);
            $btnText.addClass('d-none');
            $btnLoader.removeClass('d-none');

            // Create FormData object to handle file uploads
            const formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Re-enable button
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnLoader.addClass('d-none');

                    if (response.status === 'success') {
                        toastrMsg('success', response.msg || response.message || 'Settings updated successfully');
                    } else {
                        toastrMsg('error', response.msg || response.message || 'Failed to update settings');
                    }
                },
                error: function(xhr) {
                    // Re-enable button
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnLoader.addClass('d-none');

                    let errorMsg = 'Failed to update settings';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        errorMsg = xhr.responseJSON.msg;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Handle validation errors
                        const errors = Object.values(xhr.responseJSON.errors);
                        errorMsg = errors[0][0];
                    }

                    toastrMsg('error', errorMsg);
                }
            });
        });
    </script>
@endpush
