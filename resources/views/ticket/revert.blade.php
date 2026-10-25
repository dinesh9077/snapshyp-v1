@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Revert Ticket')
@section('header_title', 'Revert Ticket')
@section('content')
<style>
    button.btn-light-2 {
        padding: 7px 12px;
        font-size: 14px;
        border: none;
        background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
        border-radius: 5px;
    }
</style>
<div class="content-page">
    <div class="content">
        <form id="customerForm" method="post" class="customer_form" action="{{ route('ticket.admin.update_revert',$tickets->id) }}" enctype="multipart/form-data">
            <input type="hidden" name="id" value="{{$tickets->id}}">
            @csrf
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-rowx-1">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Awb Number </label>
                                            <input type="text" autocomplete="off" name="awb_number" id="awb_number" value="{{ $tickets->awb_number }}" placeholder="Awb Number" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Contact Name </label>
                                            <input type="text" autocomplete="off" name="contact_name" id="contact_name" placeholder="Contact Name" value="{{ $tickets->contact_name }}" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Contact Phone </label>
                                            <input type="text" autocomplete="off" name="contact_phone" id="contact_phone" placeholder=" Contact Phone" value="{{ $tickets->contact_phone }}" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Description </label>
                                            <textarea autocomplete="off" name="text" id="text" placeholder="Description" value="{{ $tickets->text }}" required>{{ $tickets->text }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="first-name"> Revert </label>
                                            <textarea autocomplete="off" name="revert" id="revert" placeholder="Revert" value="{{ $tickets->revert }}" required>{{ $tickets->revert }}</textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="text-align-center">
                            <button class="btn-main-1" id="customer_submit"> Submit </button>
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
    $('#customerForm').submit(function(event) {
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
                    window.location.href = "{{ route('ticket.admin') }}";
                }
            }
        });
    });
</script>
@endpush