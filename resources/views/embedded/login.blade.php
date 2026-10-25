<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('setting.company_name') }} Shopify App</title>

    <!-- Shopify App Bridge -->
    <meta name="shopify-api-key" content="{{ config('services.shopify_api_key') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/libs/jquery-toast/jquery.toast.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            max-height: 60px;
            margin-bottom: 15px;
        }

        .logo-container h4 {
            font-size: 24px;
            font-weight: 600;
            color: #202223;
            margin: 0;
        }

        .logo-container p {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: #ffffff;
        }

        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            color: #667eea;
            font-size: 14px;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .signup-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            background: #ffffff;
            padding: 0 10px;
            position: relative;
            color: #9ca3af;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}" alt="{{ config('setting.company_name') }}">
                <h4>Welcome Back</h4>
                <p>Login to manage your orders</p>
            </div>

            <form method="POST" id="loginForm">
                <input type="hidden" name="shop" value="{{ request('shop') }}">
                <input type="hidden" name="host" value="{{ request('host') }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required autocomplete="email"
                        placeholder="Enter your email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required placeholder="Enter your password" autocomplete="current-password">
                    @error('password')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                {{-- <div class="forgot-password">
                    <a href="{{ route('forgot-password') }}">Forgot Password?</a>
                </div> --}}

                <div class="mt-4">
                    <button type="submit" class="btn btn-login" id="loginBtn">Login</button>
                </div>
            </form>

            <div class="signup-link">
                Don't have an account? <a
                    href="{{ route('shopify.embedded.register', ['shop' => request('shop'), 'host' => request('host')]) }}">Sign
                    Up</a>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/libs/jquery-toast/jquery.toast.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Handle existing session messages
            @if (session('success'))
                $.toast({
                    heading: "Success!",
                    text: "{{ session('success') }}",
                    position: "top-right",
                    loaderBg: "#5ba035",
                    icon: "success",
                    hideAfter: 3000,
                    stack: 1
                });
            @endif

            @if (session('error'))
                $.toast({
                    heading: "Error!",
                    text: "{{ session('error') }}",
                    position: "top-right",
                    loaderBg: "#bf441d",
                    icon: "error",
                    hideAfter: 3000,
                    stack: 1
                });
            @endif

            // Handle login form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $('#loginBtn');
                const formData = new FormData(this);

                // Disable button
                $btn.prop('disabled', true).text('Logging in...');

                $.ajax({
                    url: "{{ route('shopify.embedded.login.post') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Store session token in localStorage
                            if (response.shopify_session_token) {
                                localStorage.setItem('shopify_session_token', response
                                    .shopify_session_token);
                            }

                            $.toast({
                                heading: "Success!",
                                text: response.msg,
                                position: "top-right",
                                loaderBg: "#5ba035",
                                icon: "success",
                                hideAfter: 2000,
                                stack: 1
                            });

                            // Redirect after short delay
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        } else {
                            $.toast({
                                heading: "Error!",
                                text: response.msg,
                                position: "top-right",
                                loaderBg: "#bf441d",
                                icon: "error",
                                hideAfter: 3000,
                                stack: 1
                            });
                            $btn.prop('disabled', false).text('Login');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        $.toast({
                            heading: "Error!",
                            text: errorMsg,
                            position: "top-right",
                            loaderBg: "#bf441d",
                            icon: "error",
                            hideAfter: 3000,
                            stack: 1
                        });
                        $btn.prop('disabled', false).text('Login');
                    }
                });
            });
        });
    </script>
</body>

</html>
