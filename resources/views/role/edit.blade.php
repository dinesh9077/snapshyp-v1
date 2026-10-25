@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Edit Role')
@section('header_title', 'Edit Role')
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
               <form id="roleForm" method="post" class="role_form" action="{{ route('userrole.updateajax', ['id' => $role->id]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="container-fluid">
                        <div class="ordr-main-001">   
                         <div class="main-rowx-1 m-0">
                            <div class="main-row main-data-teble-1">
                                   <div class="row">
                                        <div class="col-lg-4 col-md-6">
                                             <div class="from-group my-2">
                                                  <label for="username"> Role Name </label>
                                                  <input type="text" autocomplete="off" name="name" id="role_name" placeholder="Role Name" value="{{ $role->name }}" required>
                                             </div>
                                        </div>
                                   </div>
                            </div>
                                   <div class="text-align-center mb-0">
                                        <button class="btn-main-1" id="role_submit"> Submit </button>
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
     //    $(document).ready(function() {
     //        var i = 100;
     //        $('#add_new_address').click(function() {
     //            var html = '';
     //            html += '<div class="row align-items-end remove_address_' + i +
     //                '"><div class="col-lg-6 col-md-6"><div class="from-group my-2"><label for="username"> Address </label><input class="default" type="text" autocomplete="off" name="address[]" id="address" placeholder="Address" required></div></div><div class="col-lg-6 col-md-6"><div class="from-group my-2"><label for="username"> Country </label><input class="default" type="text" autocomplete="off" name="country[]" id="country" placeholder="Country" required></div></div><div class="col-lg-6 col-md-6"><div class="from-group my-2"><label for="username"> State </label><input class="default" type="text" autocomplete="off" name="state[]" id="state" placeholder="State" required></div></div><div class="col-lg-6 col-md-6"><div class="from-group my-2"><label for="username"> City </label><input class="default" type="text"autocomplete="off" name="city[]" id="state" placeholder="State" required> </div></div><div class="col-lg- col-md-6"><div class="from-group my-2"><label for="first-name"> Zip code </label><input class="default" type="text" autocomplete="off" name="zip_code[]" id="zip_code" placeholder="Zip code" required></div></div><div class="col-lg- col-md-6 "><div class="from-group my-2"><button type="button" class="btn-light-2" onclick="removeAddress(' +
     //                i + ')">❌ Delete </button></div></div> </div>';
     //            $('.append_address').append(html);
     //            i++;
     //        })
     //    })

     //    function removeAddress(id) {
     //        $('.remove_address_' + id).remove();
     //    }

        $('#roleForm').submit(function(event) {
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
                        window.location.href = "{{ route('userrole') }}";
                    }
                }
            });
        });
    </script>
@endpush
