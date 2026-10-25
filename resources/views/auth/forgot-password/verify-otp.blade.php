<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('setting.company_name') }} :: Verify OTP</title>
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

        .otp-container {
            max-width: 500px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            padding: 50px 40px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }

        .otp-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .otp-header img {
            height: 80px;
            margin-bottom: 20px;
        }

        .otp-header h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .otp-header p {
            color: #6d6d6d;
            font-size: 14px;
        }

        .otp-header .email-display {
            color: #000;
            font-weight: 600;
        }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }

        .otp-input {
            width: 50px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .otp-input:focus {
            border-color: #000;
            box-shadow: 0 0 0 0.2rem #2525252f;
            outline: none;
        }

        .otp-input.is-invalid {
            border-color: #dc3545;
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

        .resend-section {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6d6d6d;
        }

        .resend-link {
            color: #5640B0;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .resend-link:hover {
            color: #000;
        }

        .resend-link.disabled {
            color: #9b8fd6;
            cursor: not-allowed;
        }

        .timer {
            color: #000;
            font-weight: 600;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .back-link a {
            color: #000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: #252525ff;
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
            .otp-container {
                margin: 20px;
                padding: 10px 15px;
            }

            .otp-input {
                       width: 30px;
        height: 36px;
        font-size: 16px;
            }
            .otp-header img{
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
    <div class="otp-container">
        <div class="otp-header">
            <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                alt="{{ config('setting.company_name') }}">
            <h5>Verify OTP</h5>
            <p>We've sent a 6-digit code to<br><span class="email-display" id="emailDisplay"></span></p>
        </div>

        <div id="alertContainer"></div>

        <form id="verifyOtpForm">
            @csrf
            <input type="hidden" id="email" name="email">

            <div class="otp-inputs">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="0">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="1">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="2">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="3">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="4">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"
                    data-index="5">
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-main-1" id="submitBtn">
                    <span id="btnText">Verify OTP</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <div class="resend-section">
            <span id="resendText">Didn't receive the code? </span>
            <a class="resend-link disabled" id="resendLink">Resend OTP <span class="timer"
                    id="timer">(60s)</span></a>
        </div>

        <p class="back-link">
            <a href="{{ route('forgot-password') }}">← Back to forgot password</a>
        </p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/css/bootstrap.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Get email from session storage
            const email = sessionStorage.getItem('reset_email');
            if (!email) {
                window.location.href = '{{ route('forgot-password') }}';
                return;
            }

            $('#email').val(email);
            $('#emailDisplay').text(email);

            // OTP Input handling
            const otpInputs = $('.otp-input');

            otpInputs.on('input', function(e) {
                const input = $(this);
                const value = input.val();

                // Only allow numbers
                if (!/^\d*$/.test(value)) {
                    input.val('');
                    return;
                }

                // Move to next input
                if (value.length === 1) {
                    const nextIndex = parseInt(input.data('index')) + 1;
                    if (nextIndex < 6) {
                        $(`[data-index="${nextIndex}"]`).focus();
                    }
                }
            });

            otpInputs.on('keydown', function(e) {
                const input = $(this);
                const index = parseInt(input.data('index'));

                // Handle backspace
                if (e.key === 'Backspace' && !input.val() && index > 0) {
                    $(`[data-index="${index - 1}"]`).focus();
                }
            });

            // Paste handling
            otpInputs.first().on('paste', function(e) {
                e.preventDefault();
                const pastedData = e.originalEvent.clipboardData.getData('text').trim();
                if (/^\d{6}$/.test(pastedData)) {
                    pastedData.split('').forEach((char, index) => {
                        $(`[data-index="${index}"]`).val(char);
                    });
                    otpInputs.last().focus();
                }
            });

            // Form submission
            $('#verifyOtpForm').on('submit', function(e) {
                e.preventDefault();

                // Get OTP value
                let otp = '';
                otpInputs.each(function() {
                    otp += $(this).val();
                });

                if (otp.length !== 6) {
                    $('#alertContainer').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fa fa-exclamation-circle"></i> Please enter all 6 digits.' +
                        '</div>'
                    );
                    return;
                }

                // Clear previous errors
                otpInputs.removeClass('is-invalid');
                $('#alertContainer').html('');

                // Disable button and show spinner
                $('#submitBtn').prop('disabled', true);
                $('#btnText').addClass('d-none');
                $('#btnSpinner').removeClass('d-none');

                $.ajax({
                    url: '{{ route('forgot-password.verify-otp') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: email,
                        otp: otp
                    },
                    success: function(response) {
                        if (response.success) {
                            // Store OTP in session storage
                            sessionStorage.setItem('reset_otp', otp);

                            // Show success message
                            $('#alertContainer').html(
                                '<div class="alert alert-success">' +
                                '<i class="fa fa-check-circle"></i> ' + response.message +
                                '</div>'
                            );

                            // Redirect to reset password page
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('forgot-password.reset') }}';
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        // Re-enable button
                        $('#submitBtn').prop('disabled', false);
                        $('#btnText').removeClass('d-none');
                        $('#btnSpinner').addClass('d-none');

                        // Mark inputs as invalid
                        otpInputs.addClass('is-invalid');

                        const message = xhr.responseJSON?.message ||
                            'Invalid OTP. Please try again.';
                        $('#alertContainer').html(
                            '<div class="alert alert-danger">' +
                            '<i class="fa fa-exclamation-circle"></i> ' + message +
                            '</div>'
                        );

                        // Clear OTP inputs
                        otpInputs.val('');
                        otpInputs.first().focus();
                    }
                });
            });

            // Resend OTP timer
            let timeLeft = 60;
            const timerInterval = setInterval(function() {
                timeLeft--;
                $('#timer').text(`(${timeLeft}s)`);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    $('#resendLink').removeClass('disabled');
                    $('#timer').text('');
                }
            }, 1000);

            // Resend OTP
            $('#resendLink').on('click', function(e) {
                e.preventDefault();

                if ($(this).hasClass('disabled')) {
                    return;
                }

                $(this).addClass('disabled');
                $('#alertContainer').html('');

                $.ajax({
                    url: '{{ route('forgot-password.resend-otp') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: email
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#alertContainer').html(
                                '<div class="alert alert-success">' +
                                '<i class="fa fa-check-circle"></i> OTP has been resent to your email.' +
                                '</div>'
                            );

                            // Reset timer
                            timeLeft = 60;
                            const newTimerInterval = setInterval(function() {
                                timeLeft--;
                                $('#timer').text(`(${timeLeft}s)`);

                                if (timeLeft <= 0) {
                                    clearInterval(newTimerInterval);
                                    $('#resendLink').removeClass('disabled');
                                    $('#timer').text('');
                                }
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        $('#resendLink').removeClass('disabled');
                        const message = xhr.responseJSON?.message ||
                            'Failed to resend OTP. Please try again.';
                        $('#alertContainer').html(
                            '<div class="alert alert-danger">' +
                            '<i class="fa fa-exclamation-circle"></i> ' + message +
                            '</div>'
                        );
                    }
                });
            });
        });
    </script>
</body>

</html>
