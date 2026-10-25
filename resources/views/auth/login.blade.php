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
    .testimonial-slider {
        bottom: 50px;
    }
}

@media (max-width: 991px) {
    .testimonial-slider {
        bottom: 100px;
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

<body>
    <section class="log-sign-container">
        <div class="row m-0 custom-reverse">
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
                        <div class="fleet-brand mb-4">
                            <img src="{{ asset('storage/settings/' . config('setting.login_logo')) }}"
                                alt="{{ config('setting.company_name') }}">

                        </div>
                        <form method="POST" action="{{ route('login.post') }}" class="mt-3 text-left">

                            @csrf
                            <div class="mb-3">
                                <label for="email"> {{ __('E-Mail Address') }}</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}" required autocomplete="email"
                                    placeholder="Enter {{ __('E-Mail Address') }}" autofocus>
                                @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <input type="hidden" id="shop" name="shop"
                                value="{{ request('shop') ? request('shop') : '' }}">
                            <input type="hidden" id="host" name="host"
                                value="{{ request('host') ? request('host') : '' }}">
                            <div class="mb-3">
                                <label for="password"> {{ __('Password') }} </label>
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required placeholder="Enter {{ __('Password') }}" autocomplete="current-password">
                                @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="forgot">
                                <a href="{{ route('forgot-password') }}">Forgot Password?</a>
                            </div>
                            <div class="d-flex jjustify-content-center">
                                <button type="submit" class="btn-login">{{ __('Login') }}</button>
                            </div>
                        </form>
                    </div>
                    <p class="sin-up text-center">Don't have an account? <a
                            href="{{ request('shop') && request('host') ? route('register', ['shop' => request('shop'), 'host' => request('host')]) : route('register') }}">
                            Sign Up
                        </a></p>
                </div>
            </div>
        </div>


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