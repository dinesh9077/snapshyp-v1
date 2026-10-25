<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('setting.company_name') }} :: Forgot Password</title>
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

</head>
<style>
/* TESTIMONIAL */
.testimonial-slider {
    position: absolute;
    bottom: 100px;
    left: 30px;
    right: 30px;
    background: rgba(255, 255, 255, 40%);
    padding: 20px;
    border-radius: 12px;
    color: #fff;
        max-width: 800px;
    margin: 0 auto;
}

.testimonial {
    display: none;
    animation: fade 0.6s ease;
}

.testimonial.active {
    display: block;
}

.testimonial p {
    font-size: 17px;
    margin-bottom: 15px;
}

.user {
    display: flex;
    gap: 10px;
    align-items: center;
}

.user img {
    border-radius: 50%;
}

.user span {
    font-size: 12px;
    opacity: 0.8;
}

.dots {
    text-align: center;
    margin-top: 10px;
}

.dot {
    height: 8px;
    width: 8px;
    background: rgba(255, 255, 255, 0.5);
    display: inline-block;
    border-radius: 50%;
    margin: 0 3px;
}

.dot.active {
    background: #fff;
}
@media (max-width: 1199px) {
    .testimonial-slider{
        bottom: 50px;
    }
}
@media (max-width: 991px) {
     .testimonial-slider{
        bottom: 100px;
     }
}
@media (max-width: 576px){
    .testimonial p{
        font-size: 14px;
        margin-bottom: 5px;
    }
     .testimonial-slider{
        bottom: 50px;
     }
}
@media (max-width: 576px) and (max-height: 600px) {
    .testimonial-slider {
           display: none;
    }
}
@media (min-width: 991px) and (max-height: 646px){
        .left-content {
        padding: 20px 40px 20px;
    }
    .testimonial-slider {
    bottom: 50px;
}
}
</style>


<body class="p-0">
     <section class="log-sign-container">
        <div class="row custom-reverse m-0">
             <div class="col-lg-6 p-0">
                <div class="image-bg">
                    <div class="left-content">
                        <h1>Optimize Your Global <br>Logistics & Supply Chain</h1>
                        <p>
                            Control transportation, warehousing, and distribution with advanced logistics solutions. Gain real-time visibility, quicker deliveries, reduced costs, and seamless coordination across shipments — all through one platform.
                        </p>
                    </div>

                    <!-- TESTIMONIAL SLIDER -->
                    <div class="testimonial-slider">
                        <div class="testimonial active">
                            <div class="user mb-3">
                                <img src="{{ asset('assets/images/login/user1.png') }}">
                                <div>
                                    <strong>Rohit Mehta</strong> <br>
                                    <span>Logistics Head @ RapidTrans</span>
                                </div>
                            </div>
                            <p>
                                Freight coordination and smart route planning improved delivery speed and reduced overall transportation costs.
                            </p>
                        </div>

                        <div class="testimonial">
                            <div class="user mb-3">
                                <img src="{{ asset('assets/images/login/user2.png') }}">
                                <div>
                                    <strong>Neha Kapoor</strong> <br>
                                    <span>Operations Director @ UrbanCart</span>
                                </div>
                            </div>
                            <p>
                                Warehouse optimization and real-time shipment tracking enhanced efficiency and strengthened our supply chain performance.
                            </p>
                        </div>

                        <div class="testimonial">
                            <div class="user mb-3">
                                <img src="{{ asset('assets/images/login/user3.png') }}">
                                <div>
                                    <strong>Arjun Patel</strong> <br>
                                    <span>Ecommerce Manager @ TrendBasket</span>
                                </div>
                            </div>
                            <p>
                                Seamless order fulfillment and automated inventory controls helped scale operations without increasing operational complexity.
                            </p>
                        </div>

                        <div class="dots">
                            <span class="dot active"></span>
                            <span class="dot"></span>
                             <span class="dot"></span>
                        </div>
                    </div>
                    <p class="copy-txt">Powered by<a href="https://softieons.com/"> Softieons</a></p>
                </div>
            </div>
    <div class="col-lg-6">
                <div class="right-dets row">
                    <div class="col-sm-7 right-info">
        <div class="forgot-header text-center">
            <div class="fleet-brand mb-4">
                   <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                alt="{{ config('setting.company_name') }}">
                
            </div>
            <h5>Forgot Password?</h5>
            <p>Enter your email address and we'll send you an OTP to reset your password.</p>
        </div>

        <div id="alertContainer"></div>

        <form id="forgotPasswordForm">
            @csrf
            <div class="mb-3">
                <label for="email">Email Address</label>
                <input id="email" type="email" class="form-control" name="email" required autocomplete="email"
                    placeholder="Enter your email address" autofocus>
                <div class="invalid-feedback" id="emailError"></div>
            </div>

            <div class="">
                <button type="submit" class="btn-login" id="submitBtn">
                    <span id="btnText">Send OTP</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <p class="sin-up text-center">
            Remember your password? <a href="{{ route('login') }}">Sign in</a>
        </p>
    </div>
      </div>
            </div>
        </div>


    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/css/bootstrap.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#forgotPasswordForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('#emailError').text('');
                $('#alertContainer').html('');

                // Disable button and show spinner
                $('#submitBtn').prop('disabled', true);
                $('#btnText').addClass('d-none');
                $('#btnSpinner').removeClass('d-none');

                $.ajax({
                    url: '{{ route('forgot-password.send-otp') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: $('#email').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            // Store email in session storage
                            sessionStorage.setItem('reset_email', response.email);

                            // Show success message
                            $('#alertContainer').html(
                                '<div class="alert alert-success">' +
                                '<i class="fa fa-check-circle"></i> ' + response.message +
                                '</div>'
                            );

                            // Redirect to OTP verification page after 1.5 seconds
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('forgot-password.verify-otp') }}';
                            }, 1500);
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
                            if (errors.email) {
                                $('#email').addClass('is-invalid');
                                $('#emailError').text(errors.email[0]);
                            }
                        } else {
                            // General error
                            const message = xhr.responseJSON?.message ||
                                'An error occurred. Please try again.';
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

         const testimonials = document.querySelectorAll(".testimonial");
    const dots = document.querySelectorAll(".dot");
    let index = 0;

    setInterval(() => {
        testimonials[index].classList.remove("active");
        dots[index].classList.remove("active");

        index = (index + 1) % testimonials.length;

        testimonials[index].classList.add("active");
        dots[index].classList.add("active");
    }, 4000);
    </script>
</body>

</html>
