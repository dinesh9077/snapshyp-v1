@extends('layouts.backend.app')

@section('title', config('setting.company_name') . ' - WordPress Integration')
@section('header_title', 'WordPress Integration')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0">WordPress / WooCommerce Integration Guide</h4>
                            <a href="{{ route('integrations.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to Integrations
                            </a>
                        </div>
<style>
    .api-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid #e3e6f0; }
    .api-section h2 { margin-top: 0; font-size: 1.5rem; color: #333; margin-bottom: 1rem; }
    .token-box { font-size: 16px; background: #f4f4f4; padding: 10px 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .token-box span { color: #858796; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; }
    button.copy-btn { background: #4CAF50; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background 0.3s; }
    button.copy-btn:hover { background: #45a049; }
    .api-section small { color: #858796; font-size: 80%; }
</style>

                        <div class="card-body">
                            
                            <!-- API Token Section (Styled like screenshot) -->
                            <div class="api-section">
                                <h2>Your API Token</h2>
                                <div class="token-box">
                                    <span id="apiToken">{{ $apiToken }}</span>
                                    <button class="copy-btn" onclick="copyToken()">Copy</button>
                                </div>
                                <small>Keep your API token secret. Anyone with this token can access your account.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="text-primary mb-3">Installation Instructions</h5>
                                    
                                    <div class="timeline-box">
                                        <ol class="list-group list-group-numbered list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Download the Plugin</div>
                                                    Download the latest version of the Shipping Express plugin for WordPress.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Log in to WordPress</div>
                                                    Log in to your WordPress Admin Dashboard.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Go to Plugins</div>
                                                    Navigate to <strong>Plugins</strong> > <strong>Add New</strong> in the left sidebar.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Upload Plugin</div>
                                                    Click on the <strong>Upload Plugin</strong> button at the top of the page.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Install & Activate</div>
                                                    Choose the downloaded <code>.zip</code> file and click <strong>Install Now</strong>. After installation, click <strong>Activate Plugin</strong>.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Copy API Key</div>
                                                    Copy your unique API Token from the section above and paste it into the plugin settings.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Configure</div>
                                                    Navigate to the plugin settings (usually under WooCommerce > Settings or a dedicated menu) to connect your account.
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold" style="color: #000;">Connect Account</div>
                                                    You must use the API Token provided above in the plugin settings to successfully connect your account.
                                                </div>
                                            </li>
                                        </ol>
                                    </div>

                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="p-4 bg-white rounded border sticky-top shadow-sm" style="top: 100px;">
                                        <img src="{{ asset('assets/images/wordpress.jpg') }}" alt="WordPress Logo" class="mb-3" style="max-width: 100px;">
                                        <h5>Get Started</h5>
                                        <p class="text-muted small">Download the plugin to start shipping with WooCommerce.</p>
                                        
                                        <div class="alert alert-info border-0" role="alert">
                                            <strong>Version:</strong> 1.0.0
                                        </div>

                                        <a href="{{ asset('public/wordpress_plugin/jdac-logistics.zip') }}" class="btn btn-primary btn-lg w-100 mb-4" download>
                                            <i class="fa fa-download me-2"></i> Download Plugin
                                        </a>
                                    </div>
                                </div>
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
<script>
    function copyToken() {
        const tokenText = document.getElementById("apiToken").innerText;
        navigator.clipboard.writeText(tokenText).then(() => {
            alert("API Token copied to clipboard!"); 
            // Or ideally use a Toast notification if available in your system
            // toastr.success('API Token copied to clipboard!'); 
        }).catch(err => {
            console.error('Failed to copy: ', err);
            alert("Failed to copy API Token.");
        });
    }
</script>
@endpush
