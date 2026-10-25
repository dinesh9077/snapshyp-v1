@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Kyc Request Verified')
@section('header_title', 'Kyc Request Verified')
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

        .header-11 {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .header-11 h5 {
            font-size: 16px;
            color: #111827;
        }

        .header-11 .form-select {
            font-size: 14px;
            padding: 4px 8px;
            height: 32px;
        }

        .reason-badge {
            background-color: #fde8e8;
            border: 1px solid #f5c2c7;
            border-radius: 4px;
            color: #842029;
            font-size: 13px;
            line-height: 1.4;
        }
        body{
            overflow-y: hidden
        }
        .select2-container{
            width: auto !important;
        }
    </style>
    <div class="content-page">
        <div class="container-fluid">
            <div class="main-order-page-1 mb-3">
                <div class="main-order-001">
                    <div class="content">
                        <div class="container-fluid">
                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap w-100" style="row-gap:10px">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="m-0 text-uppercase card-title">PANCARD KYC REQUEST</h5>
                                        @if ($userkyc->pancard_status == 2 && !empty($userkyc->pan_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->pan_reason }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm kyc-status-select select2" data-id="{{ $userkyc->id }}" data-field="pancard_status">
                                            <option value="0" {{ $userkyc->pancard_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $userkyc->pancard_status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $userkyc->pancard_status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="main-rowx-1 mb-0">
                                <div class="main-row main-data-teble-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Pancard Number <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="pancard" value="{{ $userkyc->pancard }}" class="form-control" readonly placeholder="Pancard Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Pancard Image <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    @if ($userkyc->pancard_image)
                                                        @php 
                                                            $pImg = $userkyc->pancard_image; 
                                                            $pUrl = (str_contains($pImg, 'http') || str_starts_with($pImg, '/')) ? asset($pImg) : (str_contains($pImg, 'storage/') ? asset($pImg) : asset('storage/pancard/' . $pImg)); 
                                                            $isPdf = str_contains(strtolower($pImg), '.pdf');
                                                        @endphp
                                                        <a href="{{ $pUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $pUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $pUrl }}" alt="Pancard" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Image Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-order-page-1 mb-3">
                <div class="main-order-001">
                    <div class="content">
                        <div class="container-fluid">
                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap w-100"
                                    style="row-gap:10px">

                                    {{-- LEFT: Heading + Reason --}}
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="m-0 text-uppercase card-title">AADHAR KYC REQUEST</h5>

                                        {{-- Show reason beside heading (if rejected) --}}
                                        @if ($userkyc->aadhar_status == 2 && !empty($userkyc->aadhar_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->aadhar_reason }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- RIGHT: Status Dropdown --}}
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm kyc-status-select select2"
                                            data-id="{{ $userkyc->id }}" data-field="aadhar_status">
                                            <option value="0" {{ $userkyc->aadhar_status == 0 ? 'selected' : '' }}>
                                                Pending</option>
                                            <option value="1" {{ $userkyc->aadhar_status == 1 ? 'selected' : '' }}>
                                                Approved</option>
                                            <option value="2" {{ $userkyc->aadhar_status == 2 ? 'selected' : '' }}>
                                                Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="main-rowx-1 mb-0">
                                <div class="main-row main-data-teble-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Aadhar Number <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="aadhar" id="aadhar"
                                                    value="{{ $userkyc->aadhar }}" maxlength="12" class="form-control"
                                                    placeholder="Enter Aadhar Number" style=""
                                                    {{ $userkyc->aadhar_status ? 'readonly' : 'required' }}>
                                            </div>

                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Aadhar Front Image <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    @if ($userkyc->aadhar_front)
                                                        @php 
                                                            $aFront = $userkyc->aadhar_front; 
                                                            $afUrl = (str_contains($aFront, 'http') || str_starts_with($aFront, '/')) ? asset($aFront) : (str_contains($aFront, 'storage/') ? asset($aFront) : asset('storage/aadhar/' . $aFront)); 
                                                            $isPdf = str_contains(strtolower($aFront), '.pdf');
                                                        @endphp
                                                        <a href="{{ $afUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $afUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $afUrl }}" alt="Aadhar Front" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Front Image Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Aadhar Back Image <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    @if ($userkyc->aadhar_back)
                                                        @php 
                                                            $aBack = $userkyc->aadhar_back; 
                                                            $abUrl = (str_contains($aBack, 'http') || str_starts_with($aBack, '/')) ? asset($aBack) : (str_contains($aBack, 'storage/') ? asset($aBack) : asset('storage/aadhar/' . $aBack)); 
                                                            $isPdf = str_contains(strtolower($aBack), '.pdf');
                                                        @endphp
                                                        <a href="{{ $abUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $abUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $abUrl }}" alt="Aadhar Back" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Back Image Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-order-page-1 mb-3">
                <div class="main-order-001">
                    <div class="content">
                        <div class="container-fluid">
                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap w-100"
                                    style="row-gap:10px">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="m-0 text-uppercase card-title">GSTIN REQUEST</h5>
                                        @if ($userkyc->gst_status == 2 && !empty($userkyc->gst_reason))
                                            <span class="reason-badge px-2 py-1 ml-2">
                                                <strong>Reason:</strong> {{ $userkyc->gst_reason }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm kyc-status-select select2"
                                            data-id="{{ $userkyc->id }}" data-field="gst_status">
                                            <option value="0" {{ $userkyc->gst_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $userkyc->gst_status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $userkyc->gst_status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="main-rowx-1 mb-0">
                                <div class="main-row main-data-teble-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>GSTIN Number <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="gst" id="gst"
                                                    value="{{ $userkyc->gst }}" maxlength="15" class="form-control"
                                                    placeholder="Enter GSTIN Number"
                                                    {{ $userkyc->gst_status ? 'readonly' : 'required' }}>
                                            </div>

                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>GST Image <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    @if ($userkyc->gst_image)
                                                        @php 
                                                            $gImg = $userkyc->gst_image; 
                                                            $gUrl = (str_contains($gImg, 'http') || str_starts_with($gImg, '/')) ? asset($gImg) : (str_contains($gImg, 'storage/') ? asset($gImg) : asset('storage/gst/' . $gImg)); 
                                                            $isPdf = str_contains(strtolower($gImg), '.pdf');
                                                        @endphp
                                                        <a href="{{ $gUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $gUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $gUrl }}" alt="GST Image" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Image Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-order-page-1 mb-3">
                <div class="main-order-001">
                    <div class="content">
                        <div class="container-fluid">
                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap w-100"
                                    style="row-gap:10px">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="m-0 text-uppercase card-title">UDYAM AADHAAR REQUEST</h5>
                                        @if ($userkyc->udyam_status == 2 && !empty($userkyc->udyam_reason))
                                            <span class="reason-badge px-2 py-1 ml-2">
                                                <strong>Reason:</strong> {{ $userkyc->udyam_reason }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm kyc-status-select select2"
                                            data-id="{{ $userkyc->id }}" data-field="udyam_status">
                                            <option value="0" {{ $userkyc->udyam_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $userkyc->udyam_status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $userkyc->udyam_status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="main-rowx-1 mb-0">
                                <div class="main-row main-data-teble-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Udyam Aadhaar Number <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="udyam_number" id="udyam_number"
                                                    value="{{ $userkyc->udyam_number }}" class="form-control"
                                                    placeholder="Enter Udyam Number"
                                                    {{ $userkyc->udyam_status ? 'readonly' : 'required' }}>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Udyam Aadhaar Image</label>
                                                <div class="mt-2">
                                                    @if (!empty($userkyc->udyam_image))
                                                        @php 
                                                            $uImg = $userkyc->udyam_image; 
                                                            $uUrl = (str_contains($uImg, 'http') || str_starts_with($uImg, '/')) ? asset($uImg) : (str_contains($uImg, 'storage/') ? asset($uImg) : asset('storage/udyam/' . $uImg)); 
                                                            $isPdf = str_contains(strtolower($uImg), '.pdf');
                                                        @endphp
                                                        <a href="{{ $uUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $uUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $uUrl }}" alt="Udyam Image" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Image Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-order-page-1 mb-3">
                <div class="main-order-001">
                    <div class="content">
                        <div class="container-fluid">
                            <div class="mb-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap w-100" style="row-gap:10px">
                                    {{-- LEFT: Heading + Reason --}}
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="m-0 text-uppercase card-title">BANK ACCOUNT REQUEST</h5>
                                        @if ($userkyc->bank_status == 2 && !empty($userkyc->bank_reason))
                                            <span class="reason-badge px-2 py-1 ml-2 ">
                                                <strong>Reason:</strong> {{ $userkyc->bank_reason }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- RIGHT: Status Dropdown --}}
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm kyc-status-select select2"
                                            data-id="{{ $userkyc->id }}" data-field="bank_status">
                                            <option value="0" {{ $userkyc->bank_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $userkyc->bank_status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $userkyc->bank_status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="main-rowx-1 mb-0">
                                <div class="main-row main-data-teble-1">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Bank Name <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="bank_name" id="bank_name"
                                                    value="{{ $userkyc->bank_name }}" class="form-control"
                                                    placeholder="Bank Name" readonly>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Account Holder Name <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="account_holder_name" id="account_holder_name"
                                                    value="{{ $userkyc->account_holder_name }}" class="form-control"
                                                    placeholder="Holder Name" readonly>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Account Number <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="account_number" id="account_number"
                                                    value="{{ $userkyc->account_number }}" class="form-control"
                                                    placeholder="Account Number"
                                                    {{ $userkyc->bank_status ? 'readonly' : '' }}>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>IFSC Code <span class="text-danger">*</span></label>
                                                <input type="text" autocomplete="off" name="ifsc_code" id="ifsc_code"
                                                    value="{{ $userkyc->ifsc_code }}" class="form-control"
                                                    placeholder="IFSC Code"
                                                    {{ $userkyc->bank_status ? 'readonly' : '' }}>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <div class="form-group my-2">
                                                <label>Bank Passbook <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    @if ($userkyc->bank_passbook)
                                                        @php 
                                                            $bPass = $userkyc->bank_passbook; 
                                                            $bUrl = (str_contains($bPass, 'http') || str_starts_with($bPass, '/')) ? asset($bPass) : (str_contains($bPass, 'storage/') ? asset($bPass) : asset('storage/bank/' . $bPass)); 
                                                            $isPdf = str_contains(strtolower($bPass), '.pdf');
                                                        @endphp
                                                        <a href="{{ $bUrl }}" target="_blank">
                                                            @if($isPdf)
                                                                <iframe src="{{ $bUrl }}#view=FitH&toolbar=0&navpanes=0&scrollbar=0" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 5px; border-radius: 5px; pointer-events: none;" scrolling="no"></iframe>
                                                            @else
                                                                <img src="{{ $bUrl }}" alt="Bank Passbook" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                                                            @endif
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No Passbook Uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelReasonModalLabel">Cancellation Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="cancelReasonForm">
                        <input type="hidden" name="kyc_id" id="cancelKycId">
                        <input type="hidden" name="field" id="cancelField">
                        <input type="hidden" name="status" id="cancelStatus" value="2">
                        <div class="mb-3">
                            <label for="cancelReasonText" class="form-label fw-semibold">Please enter reason for
                                cancellation</label>
                            <textarea class="form-control" id="cancelReasonText" rows="3" placeholder="Enter reason..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="new-submit-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        var userKycId = @json($userkyc->id);
        // Save old value (so if modal closed, dropdown resets)
        $(document).on('focus', '.kyc-status-select', function() {
            $(this).data('old', $(this).val());
        });

        // Handle dropdown change
        $(document).on('change', '.kyc-status-select', function() {
            const el = $(this);
            const id = el.data('id');
            const field = el.data('field');
            const status = el.val();

            // When rejected (status = 2) → show modal
            if (status == 2) {
                $('#cancelKycId').val(id);
                $('#cancelField').val(field);
                $('#cancelStatus').val(status);
                $('#cancelReasonText').val('');
                $('#cancelReasonModal').data('relatedSelect', el);
                $('#cancelReasonModal').modal('show');
                return; // stop direct update
            }

            // Otherwise update immediately
            updateKycStatus(el, id, field, status);
        });

        // If modal closed without submitting → revert dropdown
        $('#cancelReasonModal').on('hidden.bs.modal', function() {
            const el = $(this).data('relatedSelect');
            if (el && el.length) {
                el.val(el.data('old')).trigger('change.select2');
            }
        });

        // Handle modal form submit
        $('#cancelReasonForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#cancelKycId').val();
            const field = $('#cancelField').val();
            const status = $('#cancelStatus').val();
            const reason = $('#cancelReasonText').val().trim();

            if (!reason) {
                toastrMsg('error', 'Please enter a rejection reason.');
                return;
            }

            const el = $('#cancelReasonModal').data('relatedSelect');
            $('#cancelReasonModal').modal('hide');

            updateKycStatus(el, id, field, status, reason);
        });

        // Shared AJAX function
        function updateKycStatus(el, id, field, status, reason = '') {
            // alert(id)
            // alert(field)
            // alert(status)
            // alert(reason)
            // return false;
            el.prop('disabled', true);

            $.ajax({
                url: "{{ route('users.kyc.update-status') }}",
                method: "POST",
                data: {
                    id,
                    field,
                    status,
                    reason,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    window.location.href = "{{ url('users/kyc/verified') }}/" + userKycId
                },
                error: function(xhr) {
                    toastrMsg('error', xhr.responseJSON?.message || 'Update failed');
                    el.val(el.data('old')).trigger('change.select2');
                },
                complete: function() {
                    el.prop('disabled', false);
                }
            });
        }

        function verifyPanApi(kycId) {
            if(!confirm('Are you sure you want to verify PAN via QuickeKYC API?')) return;
            
            $.ajax({
                url: "{{ route('users.kyc.verify-pan-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Verifying PAN...');
                },
                success: function(res) {
                    if(res.status === 'success') {
                        toastrMsg('success', res.msg);
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        toastrMsg('error', res.msg);
                        console.error(res.debug);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        }

        function verifyGstApi(kycId) {
             if(!confirm('Are you sure you want to verify GST via QuickeKYC API?')) return;

             $.ajax({
                url: "{{ route('users.kyc.verify-gst-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Verifying GST...');
                },
                success: function(res) {
                    if(res.status === 'success') {
                        toastrMsg('success', res.msg);
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        toastrMsg('error', res.msg);
                        console.error(res.debug);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        }

        function verifyBankPennyDropApi(kycId) {
             if(!confirm('This will deposit ₹1 to the user bank account via QuickeKYC API. Proceed?')) return;
             
             // Get current values in case they changed
             var acc = $('#account_number').val();
             var ifsc = $('#ifsc_code').val();

             $.ajax({
                url: "{{ route('users.kyc.verify-bank-penny-drop-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    account_number: acc,
                    ifsc_code: ifsc,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Verifying Bank Account via Penny Drop...');
                },
                success: function(res) {
                    if(res.status === 'success') {
                        toastrMsg('success', res.msg);
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        toastrMsg('error', res.msg);
                         if(res.debug) console.error(res.debug);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        }

        function verifyAadhaarApi(kycId) {
             if(!confirm('This will send an OTP to the user linked mobile number. Proceed?')) return;

             $.ajax({
                url: "{{ route('users.kyc.verify-aadhaar-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Sending OTP...');
                },
                success: function(res) {
                    if(res.status === 'otp_sent') {
                        toastrMsg('success', res.msg);
                        // Open OTP Modal
                        $('#aadhaarKycId').val(kycId);
                        $('#aadhaarClientId').val(res.client_id);
                        $('#aadhaarOtpModal').modal('show');
                    } else {
                        toastrMsg('error', res.msg);
                        console.error(res.debug);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        }

        $('#aadhaarOtpForm').on('submit', function(e) {
            e.preventDefault();
            var kycId = $('#aadhaarKycId').val();
            var clientId = $('#aadhaarClientId').val();
            var otp = $('#aadhaarOtp').val();
            
            $.ajax({
                url: "{{ route('users.kyc.verify-aadhaar-otp-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    client_id: clientId,
                    otp: otp,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Verifying OTP...');
                },
                success: function(res) {
                    if(res.status === 'success') {
                        toastrMsg('success', res.msg);
                        $('#aadhaarOtpModal').modal('hide');
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        toastrMsg('error', res.msg);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        });

        function verifyUdyamApi(kycId) {
             if(!confirm('Are you sure you want to verify Udyam Aadhaar via QuickeKYC API?')) return;

             $.ajax({
                url: "{{ route('users.kyc.verify-udyam-api') }}",
                method: "POST",
                data: {
                    kyc_id: kycId,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    toastrMsg('info', 'Verifying Udyam...');
                },
                success: function(res) {
                    if(res.status === 'success') {
                        toastrMsg('success', res.msg);
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        toastrMsg('error', res.msg);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'API Error: ' + (xhr.responseJSON?.msg || 'Unknown error'));
                }
            });
        }
    </script>

    <!-- Aadhaar OTP Modal -->
    <div class="modal fade" id="aadhaarOtpModal" tabindex="-1" aria-labelledby="aadhaarOtpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aadhaarOtpModalLabel">Enter Aadhaar OTP</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="aadhaarOtpForm">
                        <input type="hidden" id="aadhaarKycId">
                        <input type="hidden" id="aadhaarClientId">
                        <div class="mb-3">
                            <label for="aadhaarOtp" class="form-label">OTP sent to mobile</label>
                            <input type="text" class="form-control" id="aadhaarOtp" required placeholder="Enter 6 digit OTP">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Verify OTP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
