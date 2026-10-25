@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Create Franchise Partner')
@section('header_title', 'Create Client')
@section('content')
    <style>
        button.btn-light-2 {
            padding: 7px 12px;
            font-size: 14px;
            border: none;
            background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
            border-radius: 5px;
        }

        .main-row.main-data-teble-1 .row {
            row-gap: 15px;
        }

        .main-heading-1.pt-lg-0.pb-lg-2.px-lg-0 h4 {
            color: #000;
            font-size: 20px;
        }
    </style>
    <div class="content-page">
        <div class="content">
            <form id="userForm" method="post" class="customer_form" action="{{ route('users.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="container-fluid">
                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="username"> Company Name </label>
                                            <input type="text" autocomplete="off" name="company_name" id="company_name"
                                                placeholder="Company Name" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="username"> Full Name </label>
                                            <input type="text" autocomplete="off" name="name" id="name"
                                                placeholder="Full Name" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile"
                                                placeholder="Mobile" maxlength="10" pattern="\d{10}"
                                                title="Please enter exactly 10 digits" oninput="allowOnlyNumbers(this)"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email"
                                                placeholder="Email" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Password </label>
                                            <input type="text" autocomplete="off" name="password" id="password"
                                                placeholder="Password" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Gender </label>
                                            <select autocomplete="off" name="gender" id="gender" Required>
                                                <option value=""> Select Gender </option>
                                                <option value="Male"> Male </option>
                                                <option value="Female"> Female</option>
                                                <option value="Other"> Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status">
                                                <option value="1"> Active </option>
                                                <option value="0"> In-Active </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Is KYC </label>
                                            <select autocomplete="off" name="kyc_status" id="kyc_status">
                                                <option value="0"> No </option>
                                                <option value="1"> Yes </option>
                                            </select>
                                        </div>
                                    </div>
                                    @if(auth()->user()->role !== 'franchise')
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="first-name"> B2C Rate Card </label>
                                                <select class="form-control" name="b2c_rate_card_id">
                                                    <option value="0">All</option>
                                                    @foreach ($b2cRateCards as $rateCard)
                                                        <option value="{{ $rateCard->id }}">
                                                            {{ $rateCard->group_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        {{-- <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="first-name"> B2B Rate Card </label>
                                                <select class="form-control" name="b2b_rate_card_id">
                                                    <option value="0">All</option>
                                                    @foreach ($b2bRateCards as $rateCard)
                                                        <option value="{{ $rateCard->id }}">
                                                            {{ $rateCard->group_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div> --}}
                                    @endif
                                    @if($canManageCharges ?? false)
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="b2c_charge_type">B2C Charges Type</label>
                                            <select class="form-control" name="b2c_charge_type" id="b2c_charge_type">
                                                <option value="1">Fix</option>
                                                <option value="2">Percentage</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="b2c_charge">B2C Charges</label>
                                            <input type="number" step="0.01" min="0" autocomplete="off" name="b2c_charge" id="b2c_charge"
                                                placeholder="Enter B2C charges" value="0">
                                        </div>
                                    </div>
                                    {{-- <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="b2b_charge_type">B2B Charges Type</label>
                                            <select class="form-control" name="b2b_charge_type" id="b2b_charge_type">
                                                <option value="1">Fix</option>
                                                <option value="2">Percentage</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="b2b_charge">B2B Charges</label>
                                            <input type="number" step="0.01" min="0" autocomplete="off" name="b2b_charge" id="b2b_charge"
                                                placeholder="Enter B2B charges" value="0">
                                        </div>
                                    </div> --}}
                                    @endif
                                </div>

                                <div class="address_block mt-4">
                                    <div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
                                        <h4 class="mb-0 card-title"> Address </h4>
                                    </div>
                                    <div class="append_address">

                                        <div class="row align-items-end">
                                            <div class="col-lg-12 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> Address </label>
                                                    <textarea class="default" type="text" autocomplete="off" name="address" id="address" placeholder="Address"
                                                        required></textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="first-name"> Zip code </label>
                                                    <input class="default" type="text" autocomplete="off"
                                                        name="zip_code" id="zip_code" placeholder="Zip code"
                                                        onkeyup="autoFetchCountry(this)" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> Country </label>
                                                    <input class="default" type="text" autocomplete="off"
                                                        name="country" id="country" placeholder="Country" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> State </label>
                                                    <input class="default" type="text"autocomplete="off"
                                                        name="state" id="state" placeholder="State" required>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> City </label>
                                                    <input class="default" type="text" autocomplete="off"
                                                        name="city" id="city" placeholder="City" required>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-3">
                                <button class="new-submit-btn " id="customer_submit"> Submit </button>
                            </div>
                        </div>
                    </div>


                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script>
        function allowOnlyNumbers(input) {
            input.value = input.value.replace(/\D/g, '');
        }

        // Initialize Select2 for user payout days with max 2 selections
        $(document).ready(function() {
            $('#user_payout_days').select2({
                placeholder: 'Select payout days (max 2)',
                maximumSelectionLength: 2,
                allowClear: true,
                width: '100%'
            }).on('select2:selecting', function(e) {
                var selectedCount = $(this).val() ? $(this).val().length : 0;
                if (selectedCount >= 2) {
                    e.preventDefault();
                    toastrMsg('warning', 'You can select a maximum of 2 payout days.');
                }
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        $('#userForm').submit(function(event) {
            event.preventDefault();
            $(this).find('button').prop('disabled', true);
            var formData = new FormData(this);
            $.ajax({
                async: true,
                type: $(this).attr('method'),
                url: $(this).attr('action'),
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                dataType: 'Json',
                success: function(res) {
                    $('button').prop('disabled', false);
                    if (res.status == "error") {
                        toastrMsg(res.status, res.msg);
                    } else {
                        toastrMsg(res.status, res.msg);
                        window.location.href = "{{ route('users') }}?kyc_status=" + res.kyc_status;
                    }
                }
            });
        });

        let zipTimeout;

        function autoFetchCountry(obj) {
            obj.value = obj.value.replace(/\D/g, '');

            clearTimeout(zipTimeout); // Clear previous timeout

            const zip_code = $(obj).val().trim();

            if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
                zipTimeout = setTimeout(() => {
                    $.ajax({
                        type: 'GET',
                        url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
                        success: function(response) {
                            if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
                                const {
                                    District: city,
                                    State: state,
                                    Country: country
                                } = response[0].PostOffice[0];

                                $(`#city`).val(city);
                                $(`#state`).val(state);
                                $(`#country`).val(country);
                            } else {
                                console.warn("Invalid or missing pincode data.");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("API Error:", error);
                        }
                    });
                }, 500); // Debounce API request by 500ms
            }
        }
    </script>
@endpush
