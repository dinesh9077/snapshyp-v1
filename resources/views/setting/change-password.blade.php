@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Change Password')
@section('header_title','Change Password')
@section('content')

<style>
	.form-control {
    background-color: white;
    border: 1px solid #dcdcdc;
    border-radius: 10px;
}
</style>

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-profile-1">
				
                <div class="main-order-001">
                    <div class="main-row">
                        <div class="main-001">
                            <h5 class="card-title"> Set New Password </h5>
						</div>
						<form method="post" action="{{ route('update.change-password') }}">
							@csrf
							<div class="main-change-pass">
								<div class="row justify-content-center">
									<div class="col-lg-6 col-sm-12" style="display: flex;align-items: center;justify-content: center;">
										<img src="{{asset('assets/images/dashbord/pass-new.png')}}"alt="">
									</div>
									<div class="col-lg-6 col-sm-12">
										<div class="form-group my-2">
											<label>Old Password</label>
											<input type="password" name="old_password" placeholder="Old Password" class="form-control @error('old_password') is-invalid @enderror">
											@error('old_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
										</div>

										<div class="form-group my-2">
											<label>New Password</label>
											<input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" placeholder="New Password">
											@error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
										</div>

										<div class="form-group my-2">
											<label>Confirm Password</label>
											<input type="password" name="new_password_confirmation" class="form-control" placeholder="Confirm Password">
										</div>

										
									</div>
								</div>
								<div class="text-right">
											<button class="new-submit-btn">Submit</button>
										</div>
							</div>
						</form> 
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection