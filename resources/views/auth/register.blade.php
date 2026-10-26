<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('setting.company_name') }} :: Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="{{ config('setting.company_name') }}" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('storage/settings/' . config('setting.fevicon_icon')) }}">

    <!-- CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/jquery-toast/jquery.toast.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">

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

    .btn-login {
        min-width: 50%;
        padding: 10px 20px;
    }

    .main-login-btn button {
        width: 50%;
    }

    @media (max-width: 1199px) {
        .testimonial-slider {
            bottom: 50px;
        }

        /* .row>*{
            padding-right: 5px;
    padding-left: 5px;
    } */
    }

    @media (max-width: 991px) {
        .testimonial-slider {
            bottom: 100px;
        }
    }

    @media (max-width: 768px) {

        .main-login-btn button {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .testimonial p {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .testimonial-slider {
            bottom: 50px;
        }

        .main-login-21 .from-group {
            margin: 2px 0;
        }

        .terms-checkbox {
            margin-top: 10px;
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
</head>

<body class="p-0">
    <section class="log-sign-container">
        <div class="row m-0 custom-reverse">
            <div class="col-lg-5 p-0">
                <div class="image-bg">
                    <div class="left-content">
                        <h1>Create Your Logistics Account Today</h1>
                        <p>
                            Join our platform to manage transportation, warehousing, and distribution effortlessly. Get real-time tracking, faster deliveries, lower operational costs, and complete shipment control — all in one secure dashboard.
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

            <div class="col-lg-7">
                <div class="right-dets row">
                    <div class="col-sm-11 right-info">
                        <div class="fleet-brand mb-4">
                            <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                                alt="{{ config('setting.company_name') }}">
                        </div>
                        <h5 class="text-center tits">{{ __('Register now! ') }}</h5>
                        <form method="post" id="signupForm" action="#" class="text-left">
                            @csrf
                            <div class="main-login-21">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Company Name <span class="text-danger"> *
                                                </span>
                                            </label>
                                            <input type="text" name="company_name" id="company_name"
                                                placeholder="Company Name" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Full Name <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" id="name" name="name" placeholder="Full Name" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Mobile <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" name="mobile" placeholder="Mobile" id="mobile"
                                                maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits"
                                                required="">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Email <span class="text-danger"> *
                                                </span></label>
                                            <input type="email" name="email" id="email" placeholder="Email" required
                                                autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Password <span class="text-danger"> *
                                                </span></label>
                                            <input type="password" placeholder="Password" id="password" name="password"
                                                autocomplete="off" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Gender <span class="text-danger"> *
                                                </span></label>
                                            <select name="gender" id="genderDropdown" class="form-control" required>
                                                <option value=""> Select Gender</option>
                                                <option value="Male"> Male</option>
                                                <option value="Female"> Female</option>
                                                <option value="Other "> Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from-group">
                                            <label for="packaging-type"> Address <span class="text-danger"> *
                                                </span></label>
                                            <textarea name="address" id="address" required></textarea>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> ZipCode <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" id="zip_code" name="zip_code" placeholder="ZipCode"
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> City <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" id="city" name="city" placeholder="city" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> State <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" id="state" name="state" placeholder="State" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="packaging-type"> Country <span class="text-danger"> *
                                                </span></label>
                                            <input type="text" id="country" name="country" placeholder="Country"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="terms-checkbox">
                                <label>
                                    <input type="checkbox" required />
                                    <span class="checkmark"></span>
                                    I agree to the
                                    <a href="#" target="_blank">Terms &
                                        Conditions</a>
                                    and
                                    <a href="#" target="_blank">Privacy
                                        Policy</a>
                                </label>
                            </div>

                            <input type="hidden" id="shop" name="shop"
                                value="{{ request('shop') ? request('shop') : '' }}">
                            <input type="hidden" id="host" name="host"
                                value="{{ request('host') ? request('host') : '' }}">
                            <div class="main-login-btn">
                                <a href="javascript:;"> <button type="submit" id="sign_up" class="btn-login">
                                        Sign Up </button> </a>
                                <button type="resend" id="resend_btn" class="btn btn-primary otp_btn d-none">Resend
                                    OTP</button>
                            </div>
                        </form>
                        <p class="sin-up"> Do you already have an account? <a
                                href="{{ request('shop') && request('host') ? route('login', ['shop' => request('shop'), 'host' => request('host')]) : route('login') }}">
                                Sign in </a> </p>
                    </div>
                </div>
            </div>




            <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="otpModalLabel">Verify OTP</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><i
                                    class="mdi mdi-close"></i></button>
                        </div>
                        <div class="modal-body">
                            <div id="otpAlertBox"></div>
                            <form id="otpForm" action="{{ route('register.verify-otp') }}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="otpInput" class="form-label">Enter OTP</label>
                                    <input type="text" class="form-control" id="otpInput" name="otp"
                                        oninput="this.value = this.value.replace(/\D/g, '')" maxlength="6" required>
                                </div>
                                <button type="submit" class="new-submit-btn">Verify OTP</button>
                                <button type="button" id="resendOtp" class="new-submit-btn">Resend OTP</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>




    </section>


    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>

    <script src="{{ asset('assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <script src="{{ asset('assets/libs/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jquery-toast/jquery.toast.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/waitMe.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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

    $(document).ready(function() {
        const $signupForm = $('#signupForm');
        const $otpForm = $('#otpForm');
        const $resendOtp = $('#resendOtp');

        const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));

        $signupForm.submit(function(event) {
            event.preventDefault();

            // Disable submit button/Add loading
            var $btn = $signupForm.find('#sign_up');
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

            // Create FormData object
            var formData = new FormData(this);
            formData.append('_token', "{{ csrf_token() }}");

            $.ajax({
                type: $signupForm.attr('method'),
                url: $signupForm.attr('action'),
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    $btn.html(originalText).prop('disabled', false);
                    if (res.status == "success") {
                        toastrMsg(res.status, res.msg);
                        setTimeout(function() {
                            window.location.href = "{{ route('login') }}";
                        }, 1000)
                    } else if (res.status == "mail") {
                        showOtpAlert(
                            res.msg,
                            "info",
                            5000
                        );
                        otpModal.show();
                        startResendTimer(30);

                    } else {
                        toastrMsg(res.status, res.msg);
                    }
                },
                error: function(xhr) {
                    $btn.html(originalText).prop('disabled', false);
                    console.error("AJAX error:", xhr.responseText);
                    toastrMsg("error", "Something went wrong. Please try again.");
                }
            });
        });

        function showOtpAlert(message, type = "success", timeout = 3000) {

            let alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                    </div>
                `;
            $("#otpAlertBox").html(alertHtml);

            // ✅ Auto hide after timeout
            setTimeout(() => {
                $(".alert").fadeOut("slow", function() {
                    $(this).remove();
                });
            }, timeout);
        }

        $otpForm.submit(function(event) {
            event.preventDefault();

            // Disable submit button to prevent multiple submissions
            $otpForm.find(':submit').prop('disabled', true);

            // The server looks the OTP up by email; the browser never holds it
            var formData = new FormData(this);
            formData.append('_token', "{{ csrf_token() }}"); // Use meta tag for CSRF token
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
                        }, 1000)
                    } else {
                        toastrMsg(res.status, res.msg);
                    }
                },
                error: function(xhr) {
                    $otpForm.find(':submit').prop('disabled',
                        false); // Re-enable button on failure
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

            // Correctly initialize FormData
            var formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}"); // Get CSRF token from meta tag
            formData.append('name', name);
            formData.append('email', email);

            $.ajax({
                type: 'post',
                url: "{{ route('register.resend-otp') }}",
                data: formData,
                cache: false,
                processData: false, // Necessary for FormData
                contentType: false, // Necessary for FormData
                dataType: 'json',
                success: function(res) {
                    $resendOtp.prop('disabled', false).html('Resend OTP');

                    if (res.status === "mail") {
                        showOtpAlert(
                            res.msg,
                            "info",
                            5000
                        );
                        startResendTimer(30);
                    } else {
                        toastrMsg("error", res.msg);
                    }
                },
                error: function(xhr) {
                    $resendOtp.prop('disabled', false).html('Resend OTP');
                    console.error("AJAX error:", xhr.responseText);
                    toastrMsg("error", "Something went wrong. Please try again.");
                }
            });
        });

        function startResendTimer(duration) {
            let timer = duration;
            $resendOtp.prop('disabled', true);

            let interval = setInterval(function() {
                $resendOtp.html(`Resend OTP (${timer}s)`);

                if (--timer < 0) {
                    clearInterval(interval);
                    $resendOtp.prop('disabled', false).html('Resend OTP');
                }
            }, 1000);
        }

    });

    function toastrMsg(type, msg) {
        if (type == "success") {
            $.toast({
                heading: "Success!",
                text: msg,
                position: "top-right",
                loaderBg: "#5ba035",
                icon: "success",
                hideAfter: 3e3,
                stack: 1
            })
        }
        if (type == "error") {
            $.toast({
                heading: "Error!",
                text: msg,
                position: "top-right",
                loaderBg: "#bf441d",
                icon: "error",
                hideAfter: 3e3,
                stack: 1
            })
        }
    }

    /* // Disable right-click on the entire document
    			document.addEventListener('contextmenu', function(event) {
    				event.preventDefault();  // Prevent right-click context menu
    				return false; // Ensure no other context menu can be triggered
    			});
    			 
    			// Prevent mouse button event that could trigger context menu (for right-click prevention)
    			document.addEventListener('mousedown', function(event) {
    				// Check if it's a right-click (button 2 is the right-click mouse button)
    				if (event.button === 2) {
    					event.preventDefault(); // Prevent right-click
    				}
    			});

    			// Optionally, disable drag event, which could reveal the page source or cause issues
    			document.addEventListener('dragstart', function(event) {
    				event.preventDefault();  // Prevent dragging
    			});

    			// Prevent Developer Tools Access by monitoring focus events (URL bar & Dev Tools)
    			let devToolsOpen = false;
    			Object.defineProperty(document, 'hidden', {
    				get: function() {
    					devToolsOpen = true;
    					return false;
    				}
    			});

    			// Detect if the developer tools are open and prevent right-click while in this state
    			setInterval(function() {
    				if (devToolsOpen) {
    					// Disable right-click on the page while developer tools are open
    					document.body.style.pointerEvents = 'none';  
    				} else {
    					document.body.style.pointerEvents = 'auto';  // Allow interaction once developer tools are closed
    				}
    			}, 1000);

    			document.onkeydown = function(event) {
    				// Preventing common developer tools and inspect element shortcuts
    				if (
    					(event.key === 'F12') || // F12 - Developer Tools
    					(event.ctrlKey && event.shiftKey && event.key === 'I' || event.ctrlKey && event.shiftKey && event.key === 'i') || // Ctrl + Shift + I - Developer Tools
    					(event.ctrlKey && event.shiftKey && event.key === 'J' || event.ctrlKey && event.shiftKey && event.key === 'j') || // Ctrl + Shift + J - Console
    					(event.ctrlKey && event.shiftKey && event.key === 'Z' || event.ctrlKey && event.shiftKey && event.key === 'z') || // Ctrl + Shift + Z - Console
    					(event.ctrlKey && event.shiftKey && event.key === 'K' || event.ctrlKey && event.shiftKey && event.key === 'k') || // Ctrl + Shift + K - Console
    					(event.ctrlKey && event.shiftKey && event.key === 'E' || event.ctrlKey && event.shiftKey && event.key === 'e') || // Ctrl + Shift + E - Console
    					(event.shiftKey && event.key === 'F7') || 
    					(event.shiftKey && event.key === 'F5') || 
    					(event.shiftKey && event.key === 'F9') || 
    					(event.shiftKey && event.key === 'F12') || 
    					(event.shiftKey && event.key === 'F2') || 
    					(event.ctrlKey && event.key === 'U' || event.ctrlKey && event.key === 'u') || // Ctrl + U - View Source
    					(event.ctrlKey && event.key === 'C' || event.ctrlKey && event.key === 'c') || // Ctrl + C - In some browsers, used for copying the inspected code
    					(event.ctrlKey && event.key === 'S' || event.ctrlKey && event.key === 's') || // Ctrl + S - Save Page, can be used for code inspection
    					(event.ctrlKey && event.key === 'P' || event.ctrlKey && event.key === 'p') || // Ctrl + P - Print Page, also can open Developer Tools
    					(event.key === 'F11') || // F11 - Full Screen, can sometimes be used to enter DevTools in some browsers
    					(event.altKey && event.key === 'F12') // Alt + F12 - Developer Tools in some browsers
    				) {
    					event.preventDefault();  
    				}
    		 
    				if (event.button === 2) {
    					event.preventDefault();  
    				}  
    			}; */
    </script>
    </section>
    <script>
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