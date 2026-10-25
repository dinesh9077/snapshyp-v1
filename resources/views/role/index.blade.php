@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Role')
@section('header_title','Role')
@section('content') 
<style>
	.tooltip .tooltiptext 
	{ 
	text-align: left !important; 
	padding: 5px 0 5px 5px !important;
	}	
	td p {
	margin-bottom: 0;
    }
</style>
<div class="content-page">
     <div class="content">
          <!-- Start Content-->
          <div class="container-fluid">
               <div class="ordr-main-001">   
                   <div class="main-disolay-felx" style="margin-top: 0 !important;">
                       <div class="main-btn0main-1">
                           {{-- <a href="{{route('users.addpermission')}}"> <button class="btn-main-1"> Create Permission </button> </a> --}}
                           <button class="btn-main-1" data-toggle="modal" data-target=".roleUser"><span class="mdi mdi-plus"></span> Create New Role</button>
                       </div>
                   </div>
                    <ul id="tab">                             
                              <li class="active">
                                   <div class="main-calander-11"> 
                                        <div class="main-data-teble-1 table-responsive">
                                             <table id="neworder_datatable" class="" style="width:100%">
                                                  <thead>
                                                       <tr>
                                                            <th> Sr.No</th>
                                                            <th> Role </th>
                                                            <th> Action </th>
                                                       </tr>
                                                  </thead> 
                                             </table>
                                        </div>
                                   </div>
                              </li> 
                         </ul>
                    </div>
               </div>
          </div>
     </div>
</div>
<div class="modal fade roleUser" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg model-width-1">
            <div class="modal-content">
                <div class="modal-header head-00re pb-0" style="border: none;">
                    <h5 class="modal-title" id="exampleModalLabel"> New Role  </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <form id="userpermissionForm" method="post" class="userpermissionForm" action="{{ route('newuser.storeRoleUser') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="container-fluid">
                        <div class="main-rowx-1 mt-3">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-12 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Role Name </label>
                                            <input type="text" autocomplete="off" name="name" id="name" placeholder="Role Name" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mb-4">
                                <button class="new-submit-btn" id="role_submit"> Submit </button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade roleUserEdit" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg model-width-1">
            <div class="modal-content">
                <div class="modal-header head-00re pb-0" style="border: none;">
                    <h5 class="modal-title" id="exampleModalLabel"> New Role  </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <form id="userpermissionForm" method="post" class="userpermissionForm" action="{{ route('newuser.storeRoleUser') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="container-fluid">
                        <div class="main-rowx-1 mt-3">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-12 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="username"> Role Name </label>
                                            <input type="text" autocomplete="off" name="name" id="name"  required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mb-4">
                                <button class="new-submit-btn" id="role_submit"> Submit </button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('js')  
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> 
<script>
$(document).ready(function() {
     $('ul.tabs-001 li').click(function() {
         var tab_id = $(this).attr('data-tab');
         $('ul.tabs-001 li').removeClass('current');
         $('.tab-content11').removeClass('current');
         $(this).addClass('current');
         $("#" + tab_id).addClass('current');
       })
  })
  
  
  
  var dataTable = $('#neworder_datatable').DataTable({
       processing:true,
       "language": {
            'loadingRecords': '&nbsp;',
            'processing': 'Loading...'
       },
       serverSide:true,
       bLengthChange: true,
       searching: true,
       bFilter: true,
       bInfo: true,
       iDisplayLength: 25,
       order: [[0, 'desc'] ],
       bAutoWidth: false,			 
       "ajax":{
            "url": "{{ route('userrole.roleajax') }}",
            "dataType": "json",
            "type": "POST",
            "data": function (d) {
                 d._token   = "{{csrf_token()}}";
                 d.search   = $('input[type="search"]').val();

            }
       },
       "columns": [
       { "data": "id" }, 
       { "data": "role" }, 
       { "data": "action" }, 
       ]
  }); 
  
  $('#neworder_datatable').on('draw.dt', function() {
       $('[data-toggle="tooltip"]').tooltip({
            position: {
                 my: "left bottom", // the "anchor point" in the tooltip element
                 at: "left top", // the position of that anchor point relative to selected element
            }
       });
	@if(Auth::user()->role != "admin") 
	dataTable.columns(1).visible(false); 
	@endif 
  });
</script>
@endpush