<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ config('setting.company_name') }} :: Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Star Express" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('storage/settings/' . config('setting.fevicon_icon')) }}">

    <!-- App css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">

</head>

<body class="p-0">
     <section class="main-logout-login">
          <div class="container">
               <div class="main-text-input-filld wrapper1">
                    <div class="main-logo-121">
                          <img src="{{ asset('storage/settings/'.config('setting.login_logo')) }}" style="height: 90px"> 
                         <h5>{{ __('Forget Password') }} Using {{config('setting.company_name')}}</h5>
                    </div>
                    <form method="POST" action="{{ route('forgot-password.post') }}">
                   @csrf 
                         <div class="main-login-21">
                              <div class="from-group">
                                   <label for="packaging-type"> {{ __('E-Mail Address') }}</label>
                                   <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Enter {{ __('E-Mail Address') }}" autofocus>
                                   @error('email')
                                   <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                   </span>
                                   @enderror
                              </div>
                              
                         </div>
                         
                         <div class="main-login-btn">
                              <a href="javascript:;"> <button type="submit" class="btn-main-1">  {{ __('Send Mail') }} </button> </a>
                         </div>
                    </form>
                    <p class="sin-up"> Do you already have an account? <a href="{{ route('login') }}" style="color: #5640B0;"> Sign in </a> </p>
                   
               </div>
          </div>
     </section>
</body>

</html>
