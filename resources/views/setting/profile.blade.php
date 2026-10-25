@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Profile')
@section('header_title', 'Profile')
@section('content')
    <style>
        .form-control {
            border: 1px solid #ced4da !important;
            background-color: white;
        }

        .mnain-frisnol-det h4 {
            color: #000;
            font-size: 16px;
        }

        @media (max-width: 1600px) {
            .main-roow-1 h5 {
                font-size: 18px;
            }

            .mnain-frisnol-det h4 {
                font-size: 13px;
                margin-bottom: 5px;
            }

            .mnain-frisnol-det input {
                padding: 7px !important;
            }

            .mnain-frisnol-det {
                margin: 10px 20px;
            }

            .main-img-edite.reimg {
                margin-bottom: 20px;
            }
        }
    </style>
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-profile-1">
                    <div class="main-order-001">
                        <form action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="main-first-sec">
                                <div class="main-d-flex21">
                                    <div class="main-d-flex-1">
                                        <div class="main-img-edite ">
                                            <div class="main-001">
                                                <h5 class="card-title"> Personal Information </h5>
                                            </div>
                                        </div>
                                        <div class="main-edit"></div>
                                    </div>

                                    <div class="mian-row">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="main-img-edite reimg" style="margin-left: 26px;">
                                                    @if ($user->profile_image)
                                                        <img
                                                            src="{{ url('storage/profile/' . Auth::user()->profile_image) }}">
                                                    @else
                                                        <img src="{{ asset('assets/images/profile-logo.png') }}">
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Profile Image : </h4>
                                                    <input type="file" name="profile_image" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Company Name </h4>
                                                    <input type="text" name="company_name" class="form-control"
                                                        placeholder="Company Name" value="{{ $user->company_name }}">
                                                </div>
                                            </div>
                                            @if ($user->role === 'user')
                                                <div class="col-lg-4">
                                                    <div class="mnain-frisnol-det">
                                                        <h4> Seller ID </h4>
                                                        <input type="text" class="form-control" placeholder="Seller ID"
                                                            value="{{ $user->seller_id }}" readonly>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!in_array($user->role, ['user']))
                                                <div class="col-lg-4">
                                                    <div class="mnain-frisnol-det">
                                                        <h4> Pancard Number </h4>
                                                        <input type="text" name="pancard_number" class="form-control"
                                                            placeholder="Pancard Number" value="{{ $user->pancard_number }}"
                                                            autocomplete="off" required>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Name </h4>
                                                    <input type="text" name="name" class="form-control"
                                                        placeholder="Name" value="{{ $user->name }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Email : </h4>
                                                    <input type="email" name="email" class="form-control"
                                                        placeholder="Email" value="{{ $user->email }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Mobile Number : </h4>
                                                    <input type="text" name="mobile" class="form-control"
                                                        placeholder="Mobile" value="{{ $user->mobile }}" maxlength="10"
                                                        pattern="\d{10}" title="Please enter exactly 10 digits">
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Address </h4>
                                                    <input type="text" name="address" class="form-control"
                                                        placeholder="Address" value="{{ $user->address }}">
                                                    <!-- <h5> - </h5> -->
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> City </h4>
                                                    <input type="text" name="city" class="form-control"
                                                        placeholder="City" value="{{ $user->city }}">
                                                    <!-- <h5> - </h5> -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> State </h4>
                                                    <input type="text" class="form-control" name="state"
                                                        placeholder="State" value="{{ $user->state }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4> Country </h4>
                                                    <input type="text" class="form-control" name="country"
                                                        placeholder="Country" value="{{ $user->country }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mnain-frisnol-det">
                                                    <h4>Zip Code</h4>
                                                    <input type="text" class="form-control" name="zip_code"
                                                        placeholder="Zip Code" value="{{ $user->zip_code }}">
                                                </div>
                                            </div>
                                            @if ($authUser->role != 'user')
                                                <div class="col-lg-4">
                                                    <div class="mnain-frisnol-det">
                                                        <h4>Rate Card</h4>
                                                        <select class="form-control" name="rate_card_id">
                                                            <option value="">Select Rate Card</option>
                                                            <option value="0" {{ $user->rate_card_id == 0 ? 'selected' : '' }}>All</option>
                                                            @foreach ($rateCards as $reteCard)
                                                                <option value="{{ $reteCard->id }}"
                                                                    {{ $user->rate_card_id == $reteCard->id ? 'selected' : '' }}>
                                                                    {{ $reteCard->group_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="col-lg-12 text-right mt-3">
                                                <button class="new-submit-btn" type="submit"> <i class="mdi mdi-square-edit-outline"></i> Update </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
