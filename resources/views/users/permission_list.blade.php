@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Staff Permission')
@section('header_title', 'Staff Permission')

@section('content')

<style>
     .select2-search__field{
          width: 100% !important;
     }
</style>
    <div class="content-page">
        <div class="content">

            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">                    
                   <div class="main-order-001">  
                        <div class="main-create-order">
                            <div class="main-disolay-felx" style="margin-top: 0 !important;">
                                <div class="main-btn0main-1">
                                    {{-- <a href="{{route('users.addpermission')}}"> <button class="btn-main-1"> Create Permission </button> </a> --}}
                                    <button class="btn-main-1" data-toggle="modal" data-target=".permissionUser">Create
                                        Permission</button>
                                </div>
                            </div>
        
                            <div class="main-data-teble-1 table-responsive">
                                <table id="users-datatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> SR.No </th>
                                            <th> Staff Name </th>
                                            <th> Module </th>
                                            <!--<th> Created At </th>-->
                                            <th> Action </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade permissionUser" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg model-width-1">
            <div class="modal-content">
                <div class="modal-header head-00re pb-0" style="border: none;">
                    <h5 class="modal-title" id="exampleModalLabel"> Staff Permission </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <form id="userpermissionForm" method="post" class="userpermissionForm" action="{{ route('users.permission.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="container-fluid">
                        <div class="main-rowx-1 mt-3">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-12 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Staff Name </label>
                                            <select autocomplete="off" name="user_id" id="status" > 
                                                  <option value=""> Select Staff</option>
                                                  @foreach ($users as $user)
                                                  <option value="{{$user->id}}"> {{$user->name}} </option>
                                                  @endforeach
                                             
                                             </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Permission Type </label>
                                            <select autocomplete="off" name="permission_type" id="permission_type" > 
                                                <option value=""> Select Type</option>
                                                <option value="1"> View </option>
                                                <option value="2"> Edit </option>
                                             </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Module Name </label>
                                            <select autocomplete="off" name="slug[]" id="status" class="js-select2" multiple > 
                                                  <option value=""> Select Model</option>
                                                  @foreach ($permission_list as $list)
                                                       <option value="{{$list->id}}"> {{$list->model}} </option>
                                                  @endforeach
                                             
                                             </select>
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
    </div>
@endsection
@push('js')
<script>
     $(document).ready(function () {
         $('.js-select2').select2();
     });
 </script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        var dataTable = $('#users-datatable').DataTable({
            processing: true,
            "language": {
                'loadingRecords': '&nbsp;',
                'processing': 'Loading...'
            },
            serverSide: true,
            bLengthChange: true,
            searching: true,
            bFilter: true,
            bInfo: true,
            iDisplayLength: 25,
            order: [
                [0, 'desc']
            ],
            bAutoWidth: false,
            "ajax": {
                "url": "{{ route('users.permission.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.status = $('select[name="status"]').val();
                    d.user_id = '{{ $user_id }}';
                }
            },
            "columns": [{
                    "data": "id"
                },
                {
                    "data": "user_id"
                },
                {
                    "data": "slug"
                },
                // {
                    // "data": "created_at"
                // },
                { "data": "action" }
            ]
        });

        $('#users-datatable').on('draw.dt', function() {
            $('[data-toggle="tooltip"]').tooltip({
                position: {
                    my: "left bottom", // the "anchor point" in the tooltip element
                    at: "left top", // the position of that anchor point relative to selected element
                }
            });
        });

        function permissionUser(obj, evt) {
            evt.preventDefault();
            var id = $(obj).attr('data-id');
            var amount = $(obj).attr('data-amount');
            $('#user_id').val(id);
            $('#current_balance').text(amount);
            $('.permissionUser').modal('show');
        }

        function setRechargeAmount(obj, amount) {
            $('button.re-btn').removeClass('active');
            $('#recharge_user_amount').val(amount);
            $('.userpayableamount').text(amount);
            $(obj).addClass('active');
        }

        function userEdit(obj, event) {
            event.preventDefault();
            window.location.href = obj;
        }
    </script>
@endpush
