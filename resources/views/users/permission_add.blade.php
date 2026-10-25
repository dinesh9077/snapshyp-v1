@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Create Staff Permission')
@section('header_title', 'Create Staff Permission')
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
            <form id="userForm" method="post" class="customer_form" action="{{ route('users.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="container-fluid">
                    <div class="main-rowx-1 mt-3">
                        <div class="main-row main-data-teble-1">
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <div class="from-group my-2">
                                        <label for="username"> Staff Name </label>
                                        <select autocomplete="off" name="status" id="status" > 
                                             <option value="1"> Active </option>
                                             <option value="0"> In-Active </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="from-group my-2">
                                        <label for="username"> Module Name </label>
                                        <input type="text" autocomplete="off" name="name" id="name"
                                            placeholder="Full Name" required>
                                    </div>
                                </div>
                                <!-- <div class="text-align-left">
                                <button class="btn-light-1" id="add_new_address" type="button" > + Add another Address </button>
          </div>-->
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
                        window.location.href = "{{ route('users') }}";
                    }
                }
            });
        });
    </script>
@endpush
