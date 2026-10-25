@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - KYC REQUEST')
@section('header_title', 'Kyc Request')
@section('content')
    <style>
        button.btn-light-2 {
            padding: 7px 12px;
            font-size: 14px;
            border: none;
            background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
            border-radius: 5px;
        }

        .header-11 {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sel-main {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reason-badge {
            background-color: #fde8e8;
            border: 1px solid #f5c2c7;
            border-radius: 4px;
            color: #842029;
            font-size: 13px;
            line-height: 1.4;
        }
    </style>
    <div class="content-page">
        <div class="container-fluid">
            <div class="main-create-order">
                @if ($errors->any())
                    <div class="alert alert-danger pb-0">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="main-rowx-1">
                    <div class="main-order-001">
                        <div class="content">
                            <form method="post" class="customer_form" action="{{ route('users.pancard.update') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="">
                                    <div class="header-11">
                                        <div class="sel-main">
                                            <h4 class="p-0 m-0 card-title">PANCARD KYC REQUEST </h4>
                                            @if ($userkyc->pancard_status == 0)
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($userkyc->pancard_status == 1)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-danger">Rejected</span>
                                            @endif
                                        </div>
                                        {{-- Show reason beside heading (if rejected) --}}
                                        @if ($userkyc->pancard_status == 2 && !empty($userkyc->pan_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->pan_reason }}
                                            </span>
                                        @endif

                                    </div>
                                    <div class="main-rowx-1 mt-2 mb-0">
                                        <div class="main-row main-data-teble-1">
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="username"> Pancard Number <span
                                                                class="text-danger">*</span> </label>
                                                        <input type="text" autocomplete="off" name="pancard"
                                                            id="pancard" placeholder="Pancard Number"
                                                            value="{{ $userkyc->pancard }}" class="form-control"
                                                            {{ $userkyc->pancard_status == 1 ? 'readonly' : 'required' }}>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="pancard_image"> Pancard Image <span class="text-danger">*</span> </label>
                                                        <div style="position: relative;">
                                                            <input type="file" name="pancard_image" id="pancard_image" class="form-control" {{ $userkyc->pancard_status == 1 ? 'disabled' : (empty($userkyc->pancard_image) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                           
                                                        </div>
                                                        @if($userkyc->pancard_image)
                                                            <div class="mt-2">
                                                                <a href="{{ (str_contains($userkyc->pancard_image, 'storage/')) ? asset($userkyc->pancard_image) : asset('storage/pancard/'.$userkyc->pancard_image) }}" target="_blank">View Current Image</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if (!$userkyc || in_array($userkyc->pancard_status, [0, 2]))
                                                    <div class="col-lg-12 col-md-12 mt-2 text-right">
                                                        <button type="submit" class="btn-main-1">
                                                            Submit Pancard
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="main-rowx-1">
                    <div class="main-order-001">
                        <div class="content">
                            <form id="aadharForm" method="post" class="customer_form"
                                action="{{ route('users.aadhar.update') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="header-11">
                                    <div class="sel-main">
                                        <h4 class="p-0 m-0 card-title">AADHAR KYC REQUEST</h4>
                                        @if ($userkyc->aadhar_status == 0)
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($userkyc->aadhar_status == 1)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-danger">Rejected</span>
                                        @endif
                                    </div>
                                    {{-- Show reason beside heading (if rejected) --}}
                                    @if ($userkyc->aadhar_status == 2 && !empty($userkyc->aadhar_reason))
                                        <span class="reason-badge px-2 py-1 ml-2 ">
                                            <strong>Reason:</strong> {{ $userkyc->aadhar_reason }}
                                        </span>
                                    @endif
                                </div>

                                <div class="main-rowx-1 mt-2 mb-0">
                                    <div class="main-row main-data-teble-1">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6">
                                                <div class="form-group my-1">
                                                    <label>Aadhar Number <span class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="aadhar" id="aadhar"
                                                        value="{{ $userkyc->aadhar }}" maxlength="12" class="form-control"
                                                        placeholder="Enter Aadhar Number"
                                                        {{ $userkyc->aadhar_status == 1 ? 'readonly' : 'required' }}>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="form-group my-1">
                                                    <label>Aadhar Front Image <span class="text-danger">*</span></label>
                                                    <div style="position: relative;">
                                                        <input type="file" name="aadhar_front" id="aadhar_front" class="form-control" {{ $userkyc->aadhar_status == 1 ? 'disabled' : (empty($userkyc->aadhar_front) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                       
                                                    </div>
                                                    @if($userkyc->aadhar_front)
                                                        <div class="mt-2">
                                                            @php $af = $userkyc->aadhar_front; @endphp
                                                            <a href="{{ (str_contains($af, 'storage/')) ? asset($af) : asset('storage/aadhar/'.$af) }}" target="_blank">View Front Image</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="form-group my-1">
                                                    <label>Aadhar Back Image <span class="text-danger">*</span></label>
                                                    <div style="position: relative;">
                                                        <input type="file" name="aadhar_back" id="aadhar_back" class="form-control" {{ $userkyc->aadhar_status == 1 ? 'disabled' : (empty($userkyc->aadhar_back) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                       
                                                    </div>
                                                    @if($userkyc->aadhar_back)
                                                        <div class="mt-2">
                                                            <a href="{{ (str_contains($userkyc->aadhar_back, 'storage/')) ? asset($userkyc->aadhar_back) : asset('storage/aadhar/'.$userkyc->aadhar_back) }}" target="_blank">View Back Image</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>





                                        {{-- MANUAL KYC: Simple submit button (no OTP required) --}}
                                        @if (!$userkyc || in_array($userkyc->aadhar_status, [0, 2]))
                                            <div class="col-lg-12 col-md-12 mt-2 text-right">
                                                <button type="submit" class="btn-main-1">
                                                    Submit Aadhar
                                                </button>
                                            </div>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="main-rowx-1">
                    <div class="main-order-001">
                        <div class="content">
                            <form id="gstForm" method="post" class="customer_form"
                                action="{{ route('users.gst.update') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="header-11">
                                    <div class="sel-main">
                                        <h4 class="p-0 m-0 card-title">GSTIN REQUEST</h4>
                                        @if ($userkyc->gst_status == 0)
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($userkyc->gst_status == 1)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-danger">Rejected</span>
                                        @endif
                                    </div>
                                    {{-- Show reason beside heading (if rejected) --}}
                                    @if ($userkyc->gst_status == 2 && !empty($userkyc->gst_reason))
                                        <span class="reason-badge px-2 py-1 ml-2 ">
                                            <strong>Reason:</strong> {{ $userkyc->gst_reason }}
                                        </span>
                                    @endif
                                </div>

                                <div class="main-rowx-1 mt-2 mb-0">
                                    <div class="main-row main-data-teble-1">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6">
                                                <div class="form-group my-1">
                                                    <label>GSTIN Number <span class="text-danger">*</span></label>
                                                    <input type="text" autocomplete="off" name="gst" id="gst"
                                                        value="{{ $userkyc->gst }}" maxlength="15" class="form-control"
                                                        placeholder="Enter GSTIN Number"
                                                        {{ $userkyc->gst_status == 1 ? 'readonly' : 'required' }}>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="form-group my-1">
                                                    <label> GST Image <span class="text-danger">*</span> </label>
                                                    <div style="position: relative;">
                                                        <input type="file" name="gst_image" id="gst_image" class="form-control" {{ $userkyc->gst_status == 1 ? 'disabled' : (empty($userkyc->gst_image) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                        
                                                    </div>
                                                    @if($userkyc->gst_image)
                                                        <div class="mt-2">
                                                            <a href="{{ (str_contains($userkyc->gst_image, 'storage/')) ? asset($userkyc->gst_image) : asset('storage/gst/'.$userkyc->gst_image) }}" target="_blank">View Current Image</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>






                                            {{-- MANUAL KYC: Simple submit button (no OTP required) --}}
                                            @if (!$userkyc || in_array($userkyc->gst_status, [0, 2]))
                                                <div class="col-lg-12 col-md-12 mt-2 text-right">
                                                    <button type="submit" class="btn-main-1">
                                                        Submit GSTIN
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="main-rowx-1">
                    <div class="main-order-001">
                        <div class="content">
                            <form method="post" class="customer_form" action="{{ route('users.udyam.update') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="">
                                    <div class="header-11">
                                        <div class="sel-main">
                                            <h4 class="p-0 m-0 card-title">UDYAM AADHAAR KYC REQUEST </h4>
                                            @if (($userkyc->udyam_status ?? 0) == 0)
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif(($userkyc->udyam_status ?? 0) == 1)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-danger">Rejected</span>
                                            @endif
                                        </div>
                                        {{-- Show reason beside heading (if rejected) --}}
                                        @if (($userkyc->udyam_status ?? 0) == 2 && !empty($userkyc->udyam_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->udyam_reason }}
                                            </span>
                                        @endif

                                    </div>
                                    <div class="main-rowx-1 mt-2 mb-0">
                                        <div class="main-row main-data-teble-1">
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="udyam_number"> Udyam Aadhaar Number <span
                                                                class="text-danger">*</span> </label>
                                                        <input type="text" autocomplete="off" name="udyam_number"
                                                            id="udyam_number" placeholder="Udyam Aadhaar Number"
                                                                value="{{ $userkyc->udyam_number ?? '' }}" class="form-control"
                                                            {{ ($userkyc->udyam_status ?? 0) == 1 ? 'readonly' : 'required' }}>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="udyam_image"> Udyam Aadhaar Image <span class="text-danger">*</span> </label>
                                                        <div style="position: relative;">
                                                            <input type="file" name="udyam_image" id="udyam_image" class="form-control" {{ ($userkyc->udyam_status ?? 0) == 1 ? 'disabled' : (empty($userkyc->udyam_image) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                            
                                                        </div>
                                                        @if(!empty($userkyc->udyam_image))
                                                            <div class="mt-2">
                                                                <a href="{{ (str_contains($userkyc->udyam_image, 'storage/')) ? asset($userkyc->udyam_image) : asset('storage/udyam/'.$userkyc->udyam_image) }}" target="_blank">View Current Image</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if (!($userkyc->udyam_status ?? 0) || in_array(($userkyc->udyam_status ?? 0), [0, 2]))
                                                    <div class="col-lg-12 col-md-12 mt-2 text-right">
                                                        <button type="submit" class="btn-main-1">
                                                            Submit Udyam
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="main-rowx-1">
                    <div class="main-order-001">
                        <div class="content">
                            <form method="post" class="customer_form" action="{{ route('users.bank.penny-drop') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="">
                                    <div class="header-11">
                                        <div class="sel-main">
                                            <h4 class="p-0 m-0 card-title">BANK KYC REQUEST </h4>
                                            @if ($userkyc->bank_status == 0)
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($userkyc->bank_status == 1)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-danger">Rejected</span>
                                            @endif
                                        </div>
                                        @if ($userkyc->bank_status == 2 && !empty($userkyc->bank_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->bank_reason }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="main-rowx-1 mt-2 mb-0">
                                        <div class="main-row main-data-teble-1">
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="bank_name"> Bank Name <span class="text-danger">*</span></label>
                                                        <input type="text" autocomplete="off" name="bank_name" id="bank_name" placeholder="Bank Name" required
                                                            value="{{ $userkyc->bank_name }}" class="form-control"
                                                            {{ $userkyc->bank_status == 1 ? 'readonly' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="account_holder_name"> Account Holder Name <span class="text-danger">*</span></label>
                                                        <input type="text" autocomplete="off" name="account_holder_name" id="account_holder_name" placeholder="Account Holder Name" required
                                                            value="{{ $userkyc->account_holder_name }}" class="form-control"
                                                            {{ $userkyc->bank_status == 1 ? 'readonly' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="account_number"> Account Number <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" autocomplete="off" name="account_number"
                                                            id="account_number" placeholder="Account Number" required
                                                            value="{{ $userkyc->account_number }}" class="form-control"
                                                            {{ $userkyc->bank_status == 1 ? 'readonly' : '' }}>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="ifsc_code"> IFSC Code <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" autocomplete="off" name="ifsc_code"
                                                            id="ifsc_code" placeholder="IFSC Code" required
                                                            value="{{ $userkyc->ifsc_code }}" class="form-control"
                                                            {{ $userkyc->bank_status == 1 ? 'readonly' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <div class="form-group my-1">
                                                        <label for="bank_passbook"> Bank Passbook <span class="text-danger">*</span> </label>
                                                        <div style="position: relative;">
                                                            <input type="file" name="bank_passbook" id="bank_passbook" class="form-control" {{ $userkyc->bank_status == 1 ? 'disabled' : (empty($userkyc->bank_passbook) ? 'required' : '') }} onchange="let lbl = this.nextElementSibling; if(lbl && lbl.classList.contains('fake-filename')) { lbl.style.display = this.files.length > 0 ? 'none' : 'flex'; }">
                                                           
                                                        </div>
                                                        @if($userkyc->bank_passbook)
                                                            <div class="mt-2">
                                                                <a href="{{ (str_contains($userkyc->bank_passbook, 'storage/')) ? asset($userkyc->bank_passbook) : asset('storage/bank/'.$userkyc->bank_passbook) }}" target="_blank">View Passbook</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if (!$userkyc || in_array($userkyc->bank_status, [0, 2]))
                                                    <div class="col-lg-12 col-md-12 mt-2 text-right">
                                                        {{-- MANUAL KYC: Submit bank details for admin review --}}
                                                        <button type="submit" class="btn-main-1">
                                                            Submit Bank Details
                                                        </button>
                                                    </div>
                                                @endif
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
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // ---- QUICK KYC OTP SCRIPTS COMMENTED OUT (Manual KYC Mode) ----
            // Aadhar Send OTP and GSTIN Send OTP flows are disabled.
            // The forms now submit directly for manual admin review.
            // -----------------------------------------------------------------

            // /*  ---- BEGIN COMMENTED OUT OTP FLOWS ----
            // // Aadhar OTP Send
            // $('#sendOtpBtn').on('click', function(e) { ... });
            // // GSTIN OTP Send
            // $('#sendGSTINOtpBtn').on('click', function(e) { ... });
            // ---- END COMMENTED OUT OTP FLOWS ---- */


        });
    </script>
@endpush
