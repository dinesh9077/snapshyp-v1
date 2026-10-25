@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Edit Staff Users')
@section('header_title', 'Edit Staff Users')
@section('content')
<style>
    button.btn-light-2 {
        padding: 7px 12px;
        font-size: 14px;
        border: none;
        background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
        border-radius: 5px;
    }

    .main-heading-1 h4 {
        color: #000;
        margin-top: 0 !important;
    }
</style>
<div class="content-page">
    <div class="content">
        <form id="userForm" method="post" class="customer_form" action="{{ route('staffuser.update', ['id' => $user->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="container-fluid">

                <div class="main-rowx-1 mt-3">
                    <div class="main-row main-data-teble-1">
                        <div class="main-order-page-1">
                            <div class="main-order-001">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Company Name </label>
                                            <input type="text" autocomplete="off" name="company_name" id="company_name" placeholder="Company Name" value="{{ $user->company_name }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Full Name </label>
                                            <input type="text" autocomplete="off" name="name" id="name" placeholder="Full Name" value="{{ $user->name }}" required>
                                        </div>
                                    </div>
        
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile" placeholder="Mobile" value="{{ $user->mobile }}" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required>
                                        </div>
                                    </div>
        
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email" placeholder="Email" value="{{ $user->email }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Password </label>
                                            <input type="password" autocomplete="off" name="password" id="password" placeholder="Password">
                                        </div>
                                    </div>
                                    {{-- <div class="col-lg-3 col-sm-6">
                                            <div class="from-group my-2">
                                                <label for="first-name"> Role </label>
                                                <select autocomplete="off" name="status" id="status">
                                                    <option value="user" < ?php echo $user->role == 'user' ? 'selected' : ''; ?>> User </option>
                                                    <option value="admin" < ?php echo $user->role == 'admin' ? 'selected' : ''; ?>> Admin </option>
                                                </select>
                                            </div>
                                        </div> --}}
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Gender </label>
                                            <select autocomplete="off" name="gender" id="gender" Required>
                                                <option value=""> Select Gender </option>
                                                <option value="Male" <?php echo $user->gender == 'Male' ? 'selected' : ''; ?>> Male </option>
                                                <option value="Female" <?php echo $user->gender == 'Female' ? 'selected' : ''; ?>> Female</option>
                                                <option value="Other" <?php echo $user->gender == 'Other' ? 'selected' : ''; ?>> Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!--<div class="col-lg-3 col-sm-6">-->
                                    <!--    <div class="from-group my-2">-->
                                    <!--        <label for="first-name"> Shipping Charge Type </label>-->
                                    <!--        <select autocomplete="off" name="charge_type" id="status">-->
                                    <!--            <option value="1" < ?php echo $user->charge_type == '1' ? 'selected' : ''; ?>> Fixed </option>-->
                                    <!--            <option value="2" < ?php echo $user->charge_type == '2' ? 'selected' : ''; ?>> Percentage </option>-->
                                    <!--        </select>-->
                                    <!--    </div>-->
                                    <!--</div>-->
                                    <!--<div class="col-lg-3 col-sm-6">-->
                                    <!--    <div class="from-group my-2">-->
                                    <!--        <label for="first-name"> Shipping Charge </label>-->
                                    <!--        <input type="text" autocomplete="off" name="charge" id="charge"-->
                                    <!--            placeholder="Shipping Charge" value="{{ $user->charge }}">-->
                                    <!--    </div>-->
                                    <!--</div>-->
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status">
                                                <option value="1" <?php echo $user->status == 1 ? 'selected' : ''; ?>> Active </option>
                                                <option value="0" <?php echo $user->status == 0 ? 'selected' : ''; ?>> In-Active </option>
                                            </select>
                                        </div>
                                    </div>
                                    <!--<div class="col-lg-3 col-sm-6">-->
                                    <!--    <div class="from-group my-2">-->
                                    <!--        <label for="first-name"> Is KYC </label>-->
                                    <!--        <select autocomplete="off" name="kyc_status" id="kyc_status">-->
                                    <!--            <option value="0" < ?php echo $user->kyc_status == 1 ? 'selected' : ''; ?>> Pending </option>-->
                                    <!--            <option value="1" < ?php echo $user->kyc_status == 1 ? 'selected' : ''; ?>> Approved </option>-->
                                    <!--        </select>-->
                                    <!--    </div>-->
                                    <!--</div>-->
                                </div>
                            </div>
                        </div>
                        <div class="main-order-page-1">
                            <div class="main-order-001">
                                <div class="address_block">
                                    <div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
                                        <h4 class="mb-0 card-title"> Address </h4>
                                    </div>
                                    <div class="append_address">
                                        <div class="row align-items-end">
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Address </label>
                                                    <input class="default" type="text" autocomplete="off" name="address" id="address" placeholder="Address" value="{{ $user->address }}" required>
                                                </div>
                                            </div>
        
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> Country </label>
                                                    <input class="default" type="text" autocomplete="off" name="country" id="country" placeholder="Country" value="{{ $user->country }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> State </label>
                                                    <input class="default" type="text" autocomplete="off" name="state" id="state" placeholder="State" value="{{ $user->state }}" required>
                                                </div>
                                            </div>
        
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="username"> City </label>
                                                    <input class="default" type="text" autocomplete="off" name="city" id="state" placeholder="City" value="{{ $user->city }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group my-2">
                                                    <label for="first-name"> Zip code </label>
                                                    <input class="default" type="text" autocomplete="off" name="zip_code" id="zip_code" placeholder="Zip code" value="{{ $user->zip_code }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right mb-4">
                    <button class="new-submit-btn" id="customer_submit"> Submit </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@push('js')
<script>
    $(document).ready(function() {
        var i = 100;
        $('#add_new_address').click(function() {
            var html = '';
            html += '<div class="row align-items-end remove_address_' + i +
                '"><div class="col-lg-3 col-sm-6"><div class="from-group my-2"><label for="username"> Address </label><input class="default" type="text" autocomplete="off" name="address[]" id="address" placeholder="Address" required></div></div><div class="col-lg-3 col-sm-6"><div class="from-group my-2"><label for="username"> Country </label><input class="default" type="text" autocomplete="off" name="country[]" id="country" placeholder="Country" required></div></div><div class="col-lg-3 col-sm-6"><div class="from-group my-2"><label for="username"> State </label><input class="default" type="text" autocomplete="off" name="state[]" id="state" placeholder="State" required></div></div><div class="col-lg-3 col-sm-6"><div class="from-group my-2"><label for="username"> City </label><input class="default" type="text"autocomplete="off" name="city[]" id="state" placeholder="State" required> </div></div><div class="col-lg- col-md-6"><div class="from-group my-2"><label for="first-name"> Zip code </label><input class="default" type="text" autocomplete="off" name="zip_code[]" id="zip_code" placeholder="Zip code" required></div></div><div class="col-lg- col-md-6 "><div class="from-group my-2"><button type="button" class="btn-light-2" onclick="removeAddress(' +
                i + ')">❌ Delete </button></div></div> </div>';
            $('.append_address').append(html);
            i++;
        })
    })

    function removeAddress(id) {
        $('.remove_address_' + id).remove();
    }

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
                    window.location.href = "{{ route('staffuser') }}";
                }
            }
        });
    });
</script>
@endpush