<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('setting.company_name') }} - Shopify Order Management</title>

    <!-- Shopify App Bridge -->
    <meta name="shopify-api-key" content="{{ config('services.shopify_api_key') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            max-width: 180px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            color: #718096;
            margin-bottom: 25px;
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .sync-control {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }

        .sync-control label {
            font-weight: 600;
            font-size: 14px;
        }

        .toggle-button {
            padding: 10px 20px;
            background-color: #ffffff;
            color: #667eea;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toggle-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .ship-now-btn {
            padding: 12px 24px;
            background-color: #ffffff;
            color: #667eea;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .ship-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        /* Status Message */
        .status-message {
            text-align: center;
            padding: 15px;
            background: #edf2f7;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
            color: #2d3748;
            font-weight: 500;
        }

        /* Workflow Section */
        .workflow-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            text-align: center;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .step-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .step-number {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border-radius: 50%;
            font-weight: 700;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
        }

        .step-description {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.6;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }

        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .feature-description {
            font-size: 13px;
            color: #718096;
            line-height: 1.5;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }

        .footer-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
        }

        .footer-content {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.8;
        }

        .footer-content a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-content a:hover {
            text-decoration: underline;
        }

        /* Important Alert */
        .important-alert {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
        }

        .alert-icon {
            font-size: 28px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-size: 16px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
        }

        .alert-message {
            font-size: 14px;
            color: #78350f;
            line-height: 1.6;
        }

        .alert-message strong {
            font-weight: 600;
            color: #92400e;
        }

        /* Icon Colors */
        .fa-rocket {
            color: #667eea;
            margin-right: 10px;
        }

        .fa-box {
            color: #667eea;
        }

        .fa-exclamation-triangle {
            color: #f59e0b;
        }

        .fa-clipboard-list {
            color: #667eea;
            margin-right: 10px;
        }

        .fa-star {
            color: #fbbf24;
            margin-right: 10px;
        }

        .fa-bolt {
            color: #fbbf24;
            font-size: 45px;
        }

        .fa-truck {
            color: #3b82f6;
            font-size: 45px;
        }

        .fa-chart-line {
            color: #10b981;
            font-size: 45px;
        }

        .fa-money-bill-wave {
            color: #22c55e;
            font-size: 45px;
        }

        .fa-mobile-alt {
            color: #8b5cf6;
            font-size: 45px;
        }

        .fa-chart-bar {
            color: #ec4899;
            font-size: 45px;
        }

        .fa-phone {
            color: #667eea;
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }

            .header h1 {
                font-size: 26px;
            }

            .action-bar {
                flex-direction: column;
                text-align: center;
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}" class="logo"
                alt="{{ config('setting.company_name') }}">
            <h1><i class="fas fa-rocket"></i> {{ config('setting.company_name') }} Order Management</h1>
            <p>Streamline your Shopify order fulfillment with automated shipping and tracking</p>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="sync-control">
                <label>Order Sync Mode:</label>
                <button id="toggle-sync" class="toggle-button">Automatic</button>
            </div>
            <a class="ship-now-btn" href="#" id="ship-now-link">
                <i class="fas fa-box"></i>
                <span>Ship Orders Now</span>
            </a>
        </div>

        <!-- Status Message -->
        <div class="status-message" id="status-msg">
            Orders will automatically sync from Shopify to {{ config('setting.company_name') }}
        </div>

        <!-- Important Login Note -->
        <div class="important-alert">
            <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="alert-content">
                <div class="alert-title">Important: Login Required</div>
                <div class="alert-message">
                    <strong>You must be logged in</strong> to your {{ config('setting.company_name') }} account for
                    orders to sync automatically
                    and to create shipments.
                    Please ensure you're logged in before attempting to ship orders. Without authentication, order
                    synchronization and shipping features will not work.
                </div>
            </div>
        </div>

        <!-- Workflow Steps -->
        <div class="workflow-section">
            <h2 class="section-title"><i class="fas fa-clipboard-list"></i> How It Works</h2>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <div class="step-title">Order Sync</div>
                    </div>
                    <div class="step-description">
                        Orders from your Shopify store are automatically synced to {{ config('setting.company_name') }}
                        in real-time. No manual
                        entry required!
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <div class="step-title">Review Orders</div>
                    </div>
                    <div class="step-description">
                        View all your orders in one dashboard. Filter by status, date, or customer to find what you need
                        quickly.
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <div class="step-title">Create Shipment</div>
                    </div>
                    <div class="step-description">
                        Click "Ship Now" to create shipments. Select your preferred courier and generate shipping labels
                        instantly.
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">4</div>
                        <div class="step-title">Generate Labels</div>
                    </div>
                    <div class="step-description">
                        Download and print shipping labels with tracking numbers. Labels are automatically generated for
                        your chosen courier.
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">5</div>
                        <div class="step-title">Track Shipments</div>
                    </div>
                    <div class="step-description">
                        Monitor delivery status in real-time. Customers receive automatic tracking updates via email and
                        SMS.
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-header">
                        <div class="step-number">6</div>
                        <div class="step-title">Manage Returns</div>
                    </div>
                    <div class="step-description">
                        Handle returns and exchanges efficiently. Track return shipments and update order status
                        automatically.
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="workflow-section">
            <h2 class="section-title"><i class="fas fa-star"></i> Key Features</h2>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <div class="feature-title">Automatic Sync</div>
                    <div class="feature-description">
                        Orders sync automatically from Shopify without any manual intervention
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <div class="feature-title">Multi-Courier Support</div>
                    <div class="feature-description">
                        Choose from multiple courier partners for the best rates and delivery times
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="feature-title">Real-Time Tracking</div>
                    <div class="feature-description">
                        Track all shipments in real-time with automatic status updates
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="feature-title">COD Management</div>
                    <div class="feature-description">
                        Manage cash-on-delivery orders with automatic remittance tracking
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div class="feature-title">Customer Notifications</div>
                    <div class="feature-description">
                        Automatic SMS and email notifications keep customers informed
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="feature-title">Analytics & Reports</div>
                    <div class="feature-description">
                        Get insights on shipping performance, costs, and delivery metrics
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-title"><i class="fas fa-phone"></i> Need Help?</div>
            <div class="footer-content">
                <p><strong>{{ config('setting.company_name') }}</strong></p>
                <p>{{ config('setting.company_address') }}</p>
                <p style="margin-top: 15px;">
                    <strong>Phone:</strong> <a
                        href="tel:+{{ config('setting.company_contact') }}">+{{ config('setting.company_contact') }}</a><br>
                    <strong>Email:</strong> <a
                        href="mailto:{{ config('setting.company_email') }}">{{ config('setting.company_email') }}</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @include('embedded.partials.session-token')
    <script>
        var company_name = "{{ config('setting.company_name') }}";
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = $('#toggle-sync');
            const statusMsg = $('#status-msg');

            // Get shop from query string
            const shop = new URLSearchParams(window.location.search).get("shop");
            const host = new URLSearchParams(window.location.search).get("host");

            // Handle Ship Now button click
            $('#ship-now-link').on('click', function(e) {
                e.preventDefault();
                if (shop) {
                    let url = "{{ route('shopify.orders.list.page') }}?shop=" + encodeURIComponent(shop);
                    if (host) {
                        url += "&host=" + encodeURIComponent(host);
                    }
                    window.location.href = url;
                } else {
                    alert('Shop parameter is missing');
                }
            });

            // Load current sync mode from backend
            fetch("{{ route('shopify.sync.get') }}?shop=" + encodeURIComponent(shop), {
                    method: "GET",
                    headers: {
                        "Accept": "application/json",
                    },
                })
                .then((resp) => {
                    if (!resp.ok) {
                        throw new Error("Failed to load current mode: " + resp.status);
                    }
                    return resp.json();
                })
                .then((data) => {
                    // Set initial toggle state
                    let autoSync = data.mode === "automatic";

                    toggleButton.text(autoSync ? "Automatic" : "Manual");
                    statusMsg.html(
                        autoSync ? 
                        "<i class='fas fa-check-circle' style='color: #10b981;'></i> <strong>Automatic Mode:</strong> Orders will automatically sync from Shopify to " +
                        company_name :
                        "<i class='fas fa-cog' style='color: #6b7280;'></i> <strong>Manual Mode:</strong> You need to manually select orders to process"
                    );

                    // Attach click handler to toggle mode
                    toggleButton.off("click").on("click", function() {
                        autoSync = !autoSync;
                        const mode = autoSync ? "automatic" : "manual";

                        toggleButton.text(autoSync ? "Automatic" : "Manual");

                        // Build URL with shop and host parameters
                        let updateUrl = "{{ route('shopify.sync.update') }}?shop=" +
                            encodeURIComponent(
                                shop);
                        if (host) {
                            updateUrl += "&host=" + encodeURIComponent(host);
                        }

                        fetch(updateUrl, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "Accept": "application/json",
                                },
                                body: JSON.stringify({
                                    mode: mode,
                                    shop: shop,
                                }),
                            })
                            .then((resp) => {
                                if (!resp.ok) {
                                    throw new Error("Failed to update sync mode: " + resp.status);
                                }
                                return resp.json();
                            })
                            .then((data) => {
                                if (data.status === "success") {
                                    statusMsg.html(
                                        autoSync ? 
                                        "<i class='fas fa-check-circle' style='color: #10b981;'></i> <strong>Automatic Mode:</strong> Orders will automatically sync from Shopify to " +
                                        company_name :
                                        "<i class='fas fa-cog' style='color: #6b7280;'></i> <strong>Manual Mode:</strong> You need to manually select orders to process"
                                    );
                                    console.log("Sync mode updated for shop:", shop, "Mode:", data
                                        .mode);
                                } else {
                                    console.error("Backend returned error:", data);
                                }
                            })
                            .catch((err) => {
                                console.error("Error updating sync mode:", err);
                            });
                    });
                })
                .catch((err) => {
                    console.error("Failed to load current mode", err);
                });
        });
    </script>
</body>

</html>
