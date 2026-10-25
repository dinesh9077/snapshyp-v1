@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - New Users')
@section('header_title','New Users')
@section('content') 
<style>
    .dt-buttons {
	margin-bottom: 20px;
}
</style>
<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-create-order">
                <div class="main-disolay-felx">
                    <div class="main-btn0main-1">
                        <a href="{{route('users.add')}}"> <button class="btn-main-1"> Create Users </button> </a>
					</div>
				</div>
				<div class="main-order-page-1">                    
				<div class="main-order-001"> 
				<div class="main-filter-weight">                    
                    <div class="row row-re">
                         @if(Auth::user()->role == "admin")
                         <div class="col-lg-2 col-sm-6">
                              <div class="main-selet-11">
                                   <select name="user_id" id="user_idsss">
                                        <option value="">Select User</option>
                                        @foreach ($users as $user)
                                             <option value="{{$user->id}}"> {{$user->name}}  </option>
                                        @endforeach
                                   </select>
                              </div>
                         </div>
                         @endif
                         <div class="col-lg-2 col-sm-6">
                              <div class="main-selet-11">
                                   <input type="text" class="form-control datepicker" name="fromdate" <?php echo (isset($_GET['fromdate']))?$_GET['fromdate']:''; ?> id="fromdate" placeholder="From Date">
                              </div>
                         </div>
                         <div class="col-lg-2 col-sm-6">
                              <div class="main-selet-11">
                                   <input type="text" class="form-control datepicker" name="todate" <?php echo (isset($_GET['todate']))?$_GET['todate']:''; ?> id="todate" placeholder="To Date">
                              </div>
                         </div>
                         @if(Auth::user()->role == "admin")
                         <div class="col-lg-2 col-sm-6">
                              <div class="main-selet-11">
                                   <select name="staff_id" id="staff_id">
                                        <option value="">Select Staff</option>
                                        @foreach ($staffs as $staff)
                                             <option value="{{$staff->id}}"> {{$staff->name}}  </option>
                                        @endforeach
                                   </select>
                              </div>
                         </div>
                         @endif
                         <div class="col-lg-2 col-sm-6">
                              <div class="main-selet-11">
                                   <button class="btn-main-1 search_user">Search</button>
                              </div>
                         </div>
                    </div>                    
               </div>
                <div class="main-data-teble-1 table-responsive">
                    <table id="users-datatable" class="" style="width:100%">
                        <thead>
                            <tr>
                                <th> SR.No </th> 
                                <th> Company Name</th>
                                <th> User Name </th>
                                <th> Mobile </th>
                                <th> Email </th>
                                <th> Wallet Amount </th>
                                <th> KYC status </th>
                                <th> status </th>
                                <th> Staff Member</th>
                                <th> Created At </th>
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

<div class="modal fade rechargeUser" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg model-width-1">
		<div class="modal-content">
			<div class="modal-header head-00re pb-0" style="border: none;">
				<h5 class="modal-title" id="exampleModalLabel"> Recharge Your Wallet </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form  method="post" action="{{url('users/recharge/offline')}}" enctype="multipart/form-data">
				@csrf
				<div class="modal-body pt-0">
					<div class="main-01">
						<h5>Current Wallet Amount <span> {{config('setting.currency')}}<span id="current_balance"> </span></span></h5>
					</div>
					<div class="man-01rech">
						<input type="hidden" name="transaction_type" value="Offline">
						<input type="hidden" name="user_id" id="user_id" value="">
						<h5> Enter Amount </h5>
						<div class="from-group rech-re-form">
							<span class="position-absolute custom-rupee-position"> {{config('setting.currency')}}</span>
							<input type="number" placeholder="500" value="500" name="amount" id="recharge_user_amount" required> 
							<p class="mt-1"> Min value:{{config('setting.currency')}}500 </p>
						</div>
						<h6> Or Select From Below </h6>
						<div class="main-21-33">
							<button type="button" class="re-btn active" onclick="setRechargeAmount(this,500)"> {{config('setting.currency')}}500 </button>
							<button type="button" class="re-btn"  onclick="setRechargeAmount(this,1000)"> {{config('setting.currency')}}1000 </button>
							<button type="button" class="re-btn"  onclick="setRechargeAmount(this,2500)"> {{config('setting.currency')}}2500 </button>
							<button type="button" class="re-btn"  onclick="setRechargeAmount(this,5000)"> {{config('setting.currency')}}5000 </button>
							<button type="button" class="re-btn"  onclick="setRechargeAmount(this,10000)"> {{config('setting.currency')}}10000 </button>
						</div> 
						<div class="offline_param"> 
							<h5> Note </h5>
							<div class="from-group rech-re-form"> 
								<textarea name="note" id="note"></textarea>
							</div>
						</div>
					</div> 
					<div class="class-main-count">
						<div class="main-justify-space">
							<div class="left-rech">
								<h5> Recharge Amount </h5>
							</div>
							
							<div class="right-rech">
								<h5> {{config('setting.currency')}}<span class="userpayableamount">500</span> </h5>
							</div>
						</div>
						
						<div class="main-justify-space">
							<div class="left-rech main-recha">
								<h5> Payable Amount </h5>
							</div>
							
							<div class="right-rech main-recha">
								<h5> {{config('setting.currency')}}<span class="userpayableamount">500</span> </h5>
							</div>
						</div>
						
					</div>
				</div>
				<div class="modal-footer" style="justify-content: center;"> 
					<button type="submit" class="btn btn-primary btn-main-1"> Continue to Payment </button>
				</div>
			</form>
		</div>
	</div>
</div>	
@endsection
@push('js') 
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> 
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
	
	var dataTable = $('#users-datatable').DataTable({
		processing:true,
		"language": {
			'loadingRecords': '&nbsp;',
			'processing': 'Loading...'
		},
		dom: 'Bflitp', // Include the 'B' button container in the layout
            buttons: [
                {
                    extend: 'copy',
                    className: 'btn btn-outline-primary'
                },
                {
                    extend: 'csv',
                    className: 'btn btn-outline-primary'
                },
                {
                    extend: 'excel',
                    className: 'btn btn-outline-success'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-outline-danger'
                },
                {
                    extend: 'print',
                    className: 'btn btn-outline-secondary'
                }
            ],
		serverSide:true,
		bLengthChange: true,
		searching: true,
		bFilter: true,
		bInfo: true,
		iDisplayLength: 25,
		order: [[0, 'desc'] ],
		bAutoWidth: false,			 
		"ajax":{
			"url": "{{ route('newuser.ajaxnewUser') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}";
				d.search   = $('input[type="search"]').val();
				d.staff_id   = $('#staff_id').val();  
				d.user_id   = $('#user_idsss').val();
				d.fromdate   = $('#fromdate').val();  
				d.todate   = $('#todate').val();
			}
		},
		"columns": [
		{ "data": "id" }, 
		{ "data": "company_name" }, 
		{ "data": "name" }, 
		{ "data": "mobile" }, 
		{ "data": "email" }, 
		{ "data": "wallet_amount" }, 
		{ "data": "kyc_status" }, 
		{ "data": "status" },  
		{ "data": "staff_member" },  
		{ "data": "created_at" },   
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
	$('.search_user').click(function (){
			dataTable.draw();	
	});
	function rechargeUser(obj,evt)
	{
		evt.preventDefault();
		var id = $(obj).attr('data-id');
		var amount = $(obj).attr('data-amount');
		$('#user_id').val(id);
		$('#current_balance').text(amount);
		$('.rechargeUser').modal('show');
	}
	
	function setRechargeAmount(obj,amount)
	{
		$('button.re-btn').removeClass('active');
		$('#recharge_user_amount').val(amount);
		$('.userpayableamount').text(amount);
		$(obj).addClass('active');
	}
	
	function userEdit(obj,event)
    {
        event.preventDefault();
        window.location.href = obj;
    }
</script>
@endpush