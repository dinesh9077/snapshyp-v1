@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Client')
@section('header_title','Client')
@section('content') 
 
<div class="content-page">
    <div class="content"> 
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-create-order">
                
				<div class="main-order-page-1">                    
					<div class="main-order-001">  
						<div class="main-disolay-felx justify-content-end">
					@if(config('permission.clients.add'))
						<div class="main-btn0main-1">
							<a href="{{ route('users.create') }}"> <button class="btn-main-1"><span class="mdi mdi-plus"></span> Create Client </button> </a>
						</div>
					@endif
				</div>   
						<div class="main-data-teble-1 table-responsive">
							<table id="client-datatable" class="" style="width:100%">
								<thead>
									<tr>
										<th> SR.No </th> 
										<th> Seller ID </th>
										<th> Company Name</th>
										<th> User Name </th>
										<th> Mobile </th>
										<th> Email </th>
										<th> Wallet Amount </th>
										<th> KYC status </th>
										<th> status </th>
										<th> Created By </th>
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

<div class="modal fade wallet-model" id="rechargeUserModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg model-width-1">
		<div class="modal-content">
			<div class="modal-header head-00re pb-0" style="border: none;">
				<h5 class="modal-title" id="exampleModalLabel"> Recharge Your Wallet </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form id="rechargeOfflineForm" method="post" action="{{ url('users/recharge/offline') }}" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="transaction_type" value="Offline">
				<input type="hidden" name="user_id" id="user_id" value="">
				
				<div class="modal-body pt-0">
					<div class="main-01">
						<h5>Current Wallet Amount <span> {{ config('setting.currency') }}<span id="current_balance"> </span></span></h5>
					</div>
					<div class="man-01rech"> 
						<h5> Enter Amount </h5>
						
						<div class="from-group rech-re-form">
							<span class="position-absolute custom-rupee-position"> {{ config('setting.currency') }} </span>
							<input type="number" placeholder="500" value="500" name="amount" id="recharge_user_amount" required style="margin-left : 5px;"> 
							<p class="mt-1"> Min value: {{ config('setting.currency') }} 500 </p>
						</div>
						
						<h6> Or Select From Below </h6>
						<div class="main-21-33">
							@foreach([500, 1000, 2500, 5000, 10000] as $amount)
								<button type="button" class="re-btn" onclick="setRechargeAmount(this, {{ $amount }})">
									{{ config('setting.currency') }} {{ $amount }}
								</button>
							@endforeach
						</div>

						<div class="offline_param"> 
							<h5> Note </h5>
							<div class="from-group rech-re-form"> 
								<textarea name="note" id="note"></textarea>
							</div>
						</div>

						<div class="offline_param"> 
							<h5> Pass Key </h5>
							<div class="from-group rech-re-form"> 
								<input name="passkey" id="passkey" required>
							</div>
						</div> 
					</div> 
					<div class="class-main-count">
						<div class="main-justify-space">
							<div class="left-rech">
								<h5> Recharge Amount </h5>
							</div> 
							<div class="right-rech">
								<h5> {{ config('setting.currency') }}<span class="userpayableamount">500</span> </h5>
							</div>
						</div> 
						<div class="main-justify-space">
							<div class="left-rech main-recha">
								<h5> Payable Amount </h5>
							</div> 
							<div class="right-rech main-recha">
								<h5> {{ config('setting.currency') }}<span class="userpayableamount">500</span> </h5>
							</div>
						</div> 
					</div>
				</div>
				<div class="modal-footer" style="padding-top: 0px ; border-top : 0px;"> 
					<button type="submit" id="txnSubmitBtn" class="new-submit-btn"> Continue to Payment </button>
				</div>
			</form>
		</div>
	</div>
</div>	
@endsection

@push('js') 
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>  
<script> 
	const kycStatus = @json($kycStatus);
	var dataTable = $('#client-datatable').DataTable({
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
			"url": "{{ route('users.ajax') }}",
			"dataType": "json",
			"type": "POST",
			"data": function (d) {
				d._token   = "{{csrf_token()}}";
				d.search = $('input[name="search"]').val(); 
				d.kyc_status   = kycStatus; 
			}
		},
		"columns": [
			{ "data": "id" }, 
			{
				"data": "seller_id"
			},
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
	
	function creditUser(obj, event)
	{
		event.preventDefault();
		
		const id = $(obj).data('id'); // Use jQuery's .data() for cleaner attribute access
		const amount = $(obj).data('amount'); // Same for amount
		const userIdField = $('#rechargeOfflineForm #user_id');
		const currentBalanceField = $('#rechargeOfflineForm #current_balance');
		
		userIdField.val(id); // Set user ID
		currentBalanceField.text(amount); // Update current balance
		setTxnUi('credit');
		$('#rechargeUserModal').modal('show'); // Show modal
	}
	
	function setTxnUi(type) {
		const titleEl  = $('#exampleModalLabel'); 
		const submitEl = $('#txnSubmitBtn');
		const form = $('#rechargeOfflineForm');

		// 🔹 Set or update hidden input (avoid duplicates)
		let txnInput = form.find('input[name="transaction_type"]');
		if (txnInput.length) {
			txnInput.val(type);
		} else {
			form.append(`<input type="hidden" name="transaction_type" value="${type}">`);
		}

		if (type === 'credit') {
			titleEl.text('Manual Credit');
			submitEl.text('Credit Now')

		} else {
			titleEl.text('Manual Debit'); 
			submitEl.text('Debit Now')
		}
	}

	function debitUser(obj, event)
	{
		event.preventDefault();
		
		const id = $(obj).data('id'); // Use jQuery's .data() for cleaner attribute access
		const amount = $(obj).data('amount'); // Same for amount
		const userIdField = $('#rechargeOfflineForm #user_id');
		const currentBalanceField = $('#rechargeOfflineForm #current_balance');
		
		userIdField.val(id); // Set user ID
		currentBalanceField.text(amount); // Update current balance 
		setTxnUi('debit');                  

		$('#rechargeUserModal').modal('show'); // Show modal
	}

	function setRechargeAmount(obj, amount) {
		const button = $(obj); // Cache the button element
		const inputAmountField = $('#rechargeOfflineForm #recharge_user_amount');
		const payableAmountField = $('#rechargeOfflineForm .userpayableamount');
		
		// Remove active class from all buttons and add it to the clicked one
		$('#rechargeOfflineForm button.re-btn').removeClass('active');
		button.addClass('active');
		
		// Update the amount in the input and displayed value
		inputAmountField.val(amount);
		payableAmountField.text(amount);
	}

</script>
@endpush