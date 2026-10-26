<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - {{ config('setting.company_name') }} Shopify App</title>

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
            padding: 40px 20px;
        }

        .register-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .register-card {
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
            font-size: 14px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: #ffffff;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .text-danger {
            color: #dc2626;
        }

        .btn-resend {
            background: #10b981;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            margin-left: 10px;
        }

        .btn-resend:hover {
            background: #059669;
            color: #ffffff;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-container">
                <img src="{{ asset('storage/settings/' . config('setting.header_logo')) }}"
                    alt="{{ config('setting.company_name') }}">
                <h4>Create Your Account</h4>
                <p>Start managing your Shopify orders with {{ config('setting.company_name') }}</p>
            </div>

            <form method="POST" id="signupForm" action="{{ route('shopify.embedded.register.post') }}">
                <input type="hidden" name="shop" value="{{ request('shop') }}">
                <input type="hidden" name="host" value="{{ request('host') }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company_name" class="form-label">Company Name <span
                                class="text-danger">*</span></label>
                        <input type="text" name="company_name" id="company_name" class="form-control"
                            placeholder="Enter company name" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                            placeholder="Enter full name" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control"
                            placeholder="Enter email address" required autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" id="mobile" class="form-control"
                            placeholder="Enter mobile" maxlength="10" pattern="\d{10}"
                            title="Please enter exactly 10 digits" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" placeholder="Enter password" id="password" name="password"
                            class="form-control" autocomplete="off" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" id="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" id="address" class="form-control" placeholder="Enter full address" required></textarea>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="zip_code" class="form-label">Zip Code <span class="text-danger">*</span></label>
                        <input type="text" id="zip_code" name="zip_code" class="form-control"
                            placeholder="Zip Code" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                        <input type="text" id="city" name="city" class="form-control" placeholder="City"
                            required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                        <input type="text" id="state" name="state" class="form-control"
                            placeholder="State" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                        <input type="text" id="country" name="country" class="form-control"
                            placeholder="Country" required>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" id="sign_up" class="btn btn-register">Sign Up</button>
                    <button type="button" id="resend_btn" class="btn btn-resend d-none">Resend OTP</button>
                </div>
            </form>

            <div class="login-link">
                Already have an account? <a
                    href="{{ route('shopify.embedded.login', ['shop' => request('shop'), 'host' => request('host')]) }}">Sign
                    In</a>
            </div>
        </div>
    </div>

    <!-- OTP Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">Verify OTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="otpForm" action="{{ route('register.verify-otp') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label for="otpInput" class="form-label">Enter OTP</label>
                            <input type="text" class="form-control" id="otpInput" name="otp"
                                oninput="this.value = this.value.replace(/\D/g, '')" maxlength="6" required>
                            <small class="text-muted">Please check your email for the OTP</small>
                        </div>
                        <button type="submit" class="btn btn-register">Verify OTP</button>
                        <button type="button" id="resendOtp" class="btn btn-resend">Resend OTP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/libs/jquery-toast/jquery.toast.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            const $signupForm = $('#signupForm');
            const $otpForm = $('#otpForm');
            const $resendOtp = $('#resendOtp');

            const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));

            $signupForm.submit(function(event) {
                event.preventDefault();

                // Disable submit button to prevent multiple submissions
                $signupForm.find(':submit').prop('disabled', true);

                // Create FormData object
                var formData = new FormData(this);

                $.ajax({
                    type: 'POST',
                    url: $signupForm.attr('action'),
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        $signupForm.find(':submit').prop('disabled', false);
                        if (res.status == "success") {
                            toastrMsg(res.status, res.msg);
                            setTimeout(function() {
                                window.location.href = res.redirect;
                            }, 1000);
                        } else if (res.status == "mail") {
                            otpModal.show();
                        } else {
                            toastrMsg(res.status, res.msg);
                        }
                    },
                    error: function(xhr) {
                        $signupForm.find(':submit').prop('disabled', false);
                        console.error("AJAX error:", xhr.responseText);
                        toastrMsg("error", "Something went wrong. Please try again.");
                    }
                });
            });

            $otpForm.submit(function(event) {
                event.preventDefault();

                // Disable submit button to prevent multiple submissions
                $otpForm.find(':submit').prop('disabled', true);

                // The server looks the OTP up by email; the browser never holds it
                var formData = new FormData(this);
                formData.append('email', $signupForm.find('#email').val() || '');

                $.ajax({
                    type: $otpForm.attr('method'),
                    url: $otpForm.attr('action'),
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        $otpForm.find(':submit').prop('disabled', false);
                        $signupForm.find('#is_verify_otp').remove();
                        if (res.status == "success") {
                            $signupForm.append(
                                '<input type="hidden" id="is_verify_otp" name="is_verify_otp" value="1">'
                            );
                            toastrMsg(res.status, res.msg);
                            setTimeout(function() {
                                otpModal.hide();
                                $signupForm.submit();
                            }, 1000);
                        } else {
                            toastrMsg(res.status, res.msg);
                        }
                    },
                    error: function(xhr) {
                        $otpForm.find(':submit').prop('disabled', false);
                        console.error("AJAX error:", xhr.responseText);
                        toastrMsg("error", "Something went wrong. Please try again.");
                    }
                });
            });

            $resendOtp.click(function(event) {
                event.preventDefault();
                $resendOtp.prop('disabled', true);

                const name = $signupForm.find('#name').val() || '';
                const email = $signupForm.find('#email').val() || '';

                var formData = new FormData();
                formData.append('_token', "{{ csrf_token() }}");
                formData.append('name', name);
                formData.append('email', email);

                $.ajax({
                    type: 'post',
                    url: "{{ route('register.resend-otp') }}",
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        $resendOtp.prop('disabled', false);

                        if (res.status === "mail") {
                            toastrMsg("success", res.msg);
                        } else {
                            toastrMsg("error", res.msg);
                        }
                    },
                    error: function(xhr) {
                        $resendOtp.prop('disabled', false);
                        console.error("AJAX error:", xhr.responseText);
                        toastrMsg("error", "Something went wrong. Please try again.");
                    }
                });
            });
        });

        function toastrMsg(type, msg) {
            if (type == "success") {
                $.toast({
                    heading: "Success!",
                    text: msg,
                    position: "top-right",
                    loaderBg: "#5ba035",
                    icon: "success",
                    hideAfter: 3000,
                    stack: 1
                });
            }
            if (type == "error") {
                $.toast({
                    heading: "Error!",
                    text: msg,
                    position: "top-right",
                    loaderBg: "#bf441d",
                    icon: "error",
                    hideAfter: 3000,
                    stack: 1
                });
            }
        }

        $('#zip_code').on('keyup', function() {
            var zip_code = $(this).val();
            if (zip_code.length === 6) { // Use '===' for comparison
                $.ajax({
                    type: 'GET',
                    url: 'https://api.postalpincode.in/pincode/' + zip_code, // Fix concatenation syntax

                    success: function(response) {
                        if (response[0].Status === "Success") {
                            console.log(response[0].Message);

                            if (response[0].PostOffice) {
                                console.log(response[0].PostOffice[0])

                                var city = response[0].PostOffice[0].District;
                                var state = response[0].PostOffice[0].State;
                                var country = response[0].PostOffice[0].Country;
                                $("#city").val(city);
                                $("#state").val(state);
                                $("#country").val(country);
                                // response[0].PostOffice.forEach(function(postOffice) {
                                //     console.log("City: " + postOffice.Name);
                                //     console.log("Branch Type: " + postOffice.BranchType);
                                //     console.log("Delivery Status: " + postOffice.DeliveryStatus);
                                //     console.log("Circle: " + postOffice.Circle);
                                //     // Add more log statements or use this data as needed
                                // });
                            } else {
                                console.log("No Post Offices found.");
                            }
                        } else {
                            console.log("Error: Unsuccessful response");
                        }

                    },
                    error: function(xhr, status, error) {
                        console.log(error); // Log error message for debugging
                    }
                });
            }
        });
    </script>
</body>

</html>
