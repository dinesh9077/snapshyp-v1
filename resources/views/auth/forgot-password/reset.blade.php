<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('setting.company_name') }} :: Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="{{ config('setting.company_name') }}" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('storage/settings/' . config('setting.fevicon_icon')) }}">

    <!-- CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">

    <style>
           body {
        background: #f7f7f7;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
        .reset-container {
            max-width: 500px;
            width: 450px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            padding: 50px 40px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header img {
            height: 80px;
            margin-bottom: 20px;
        }

        .reset-header h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .reset-header p {
            color: #6d6d6d;
            font-size: 14px;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            border-color: #000;
            box-shadow: 0 0 0 0.2rem rgba(86, 64, 176, 0.15);
        }

        .password-wrapper {
            position: relative;
        }

        .password-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6d6d6d;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6d6d6d;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #000;
        }

        .password-strength {
            margin-top: 10px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: #dc3545;
        }

        .password-strength-bar.medium {
            width: 66%;
            background: #ffc107;
        }

        .password-strength-bar.strong {
            width: 100%;
            background: #28a745;
        }

        .password-requirements {
            margin-top: 15px;
            font-size: 12px;
            color: #6d6d6d;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .password-requirements li {
            padding: 3px 0;
        }

        .password-requirements li i {
            margin-right: 5px;
            width: 15px;
        }

        .password-requirements li.valid {
            color: #28a745;
        }

        .password-requirements li.invalid {
            color: #dc3545;
        }

         .btn-main-1 {
            background: #000;
    color: #fff;
    font-weight: 600;
    padding: 12px;
    width: 100%;
    border: none;
    border-radius: 30px !important;
    transition: 0.3s;
        }

        .btn-main-1:hover {
            background: #252525ff;
            border: none;
        }

        .btn-main-1:disabled {
            background: #252525ff;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        @media(max-width: 600px) {
            .reset-container {
                margin: 20px;
                padding: 10px 15px;
            }
            .reset-header img{
                    height: 60px;
            }
             .alert{
                    padding: 8px 10px;
            }
             .btn-main-1{
                padding: 8px 12px;
            }
        }
    </style>
</head>

<body class="p-0">
    <div class="reset-container">
        <div class="reset-header">
            <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                alt="{{ config('setting.company_name') }}">
            <h5>Reset Password</h5>
            <p>Create a strong password for your account</p>
        </div>

        <div id="alertContainer"></div>

        <form id="resetPasswordForm">
            @csrf
            <input type="hidden" id="email" name="email">
            <input type="hidden" id="otp" name="otp">

            <div class="mb-3">
                <label for="password">New Password</label>
                <div class="password-wrapper">
                    <i class="fa fa-lock password-icon"></i>
                    <input id="password" type="password" class="form-control" name="password" required
                        placeholder="Enter new password">
                    <i class="fa fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="invalid-feedback" id="passwordError"></div>
            </div>

            <div class="mb-3">
                <label for="password_confirmation">Confirm Password</label>
                <div class="password-wrapper">
                    <i class="fa fa-lock password-icon"></i>
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation"
                        required placeholder="Confirm new password">
                    <i class="fa fa-eye toggle-password" id="togglePasswordConfirm"></i>
                </div>
                <div class="invalid-feedback" id="passwordConfirmError"></div>
            </div>

            <div class="password-requirements">
                <strong>Password must contain:</strong>
                <ul id="requirementsList">
                    <li id="req-length" class="invalid"><i class="fa fa-times-circle"></i> At least 8 characters</li>
                    <li id="req-uppercase" class="invalid"><i class="fa fa-times-circle"></i> One uppercase letter</li>
                    <li id="req-lowercase" class="invalid"><i class="fa fa-times-circle"></i> One lowercase letter</li>
                    <li id="req-number" class="invalid"><i class="fa fa-times-circle"></i> One number</li>
                </ul>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-main-1" id="submitBtn">
                    <span id="btnText">Reset Password</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/css/bootstrap.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Get email and OTP from session storage
            const email = sessionStorage.getItem('reset_email');
            const otp = sessionStorage.getItem('reset_otp');

            if (!email || !otp) {
                window.location.href = '{{ route('forgot-password') }}';
                return;
            }

            $('#email').val(email);
            $('#otp').val(otp);

            // Toggle password visibility
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            $('#togglePasswordConfirm').on('click', function() {
                const passwordField = $('#password_confirmation');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            // Password strength checker
            $('#password').on('input', function() {
                const password = $(this).val();
                let strength = 0;

                // Check requirements
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);

                // Update requirement list
                updateRequirement('req-length', hasLength);
                updateRequirement('req-uppercase', hasUppercase);
                updateRequirement('req-lowercase', hasLowercase);
                updateRequirement('req-number', hasNumber);

                // Calculate strength
                if (hasLength) strength++;
                if (hasUppercase) strength++;
                if (hasLowercase) strength++;
                if (hasNumber) strength++;

                // Update strength bar
                const strengthBar = $('#strengthBar');
                strengthBar.removeClass('weak medium strong');

                if (strength <= 2) {
                    strengthBar.addClass('weak');
                } else if (strength === 3) {
                    strengthBar.addClass('medium');
                } else if (strength === 4) {
                    strengthBar.addClass('strong');
                }
            });

            function updateRequirement(id, isValid) {
                const element = $('#' + id);
                if (isValid) {
                    element.removeClass('invalid').addClass('valid');
                    element.find('i').removeClass('fa-times-circle').addClass('fa-check-circle');
                } else {
                    element.removeClass('valid').addClass('invalid');
                    element.find('i').removeClass('fa-check-circle').addClass('fa-times-circle');
                }
            }

            // Form submission
            $('#resetPasswordForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $('#alertContainer').html('');

                // Validate passwords match
                const password = $('#password').val();
                const passwordConfirm = $('#password_confirmation').val();

                if (password !== passwordConfirm) {
                    $('#password_confirmation').addClass('is-invalid');
                    $('#passwordConfirmError').text('Passwords do not match.');
                    return;
                }

                // Disable button and show spinner
                $('#submitBtn').prop('disabled', true);
                $('#btnText').addClass('d-none');
                $('#btnSpinner').removeClass('d-none');

                $.ajax({
                    url: '{{ route('forgot-password.reset') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: email,
                        otp: otp,
                        password: password,
                        password_confirmation: passwordConfirm
                    },
                    success: function(response) {
                        if (response.success) {
                            // Clear session storage
                            sessionStorage.removeItem('reset_email');
                            sessionStorage.removeItem('reset_otp');

                            // Show success message
                            $('#alertContainer').html(
                                '<div class="alert alert-success">' +
                                '<i class="fa fa-check-circle"></i> ' + response.message +
                                '</div>'
                            );

                            // Redirect to login page
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        // Re-enable button
                        $('#submitBtn').prop('disabled', false);
                        $('#btnText').removeClass('d-none');
                        $('#btnSpinner').addClass('d-none');

                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            if (errors.password) {
                                $('#password').addClass('is-invalid');
                                $('#passwordError').text(errors.password[0]);
                            }
                            if (errors.password_confirmation) {
                                $('#password_confirmation').addClass('is-invalid');
                                $('#passwordConfirmError').text(errors.password_confirmation[
                                0]);
                            }
                        } else {
                            // General error
                            const message = xhr.responseJSON?.message ||
                                'Failed to reset password. Please try again.';
                            $('#alertContainer').html(
                                '<div class="alert alert-danger">' +
                                '<i class="fa fa-exclamation-circle"></i> ' + message +
                                '</div>'
                            );
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
