@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - General Setting')
@section('header_title','General Setting')
@section('content')

<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="label-settings">
				<form class="generalForm" action="{{url('setting/update-preferance')}}" method="post" enctype="multipart/form-data">
					@csrf
					<div class="main-order-page-1">
				<div class="main-order-001">
					<div class="main-data-teble-1">
						<div class="main-hed-1">
							<h6> Let us know which label format and size works best for your business and we'll apply it to all of your new labels. </h6>
						</div>
						
						<div class="main-001-check">
							<div class="checkbox checkbox-primary">
								<input id="checkbox5" type="checkbox" name="order_value" value="1" <?php echo (config('setting.order_value') == 1)?'checked':''; ?>>
								<label for="checkbox5"> Display Order value in COD and Prepaid label </label><br> 
							</div>
						</div>
						
						<div class="main-001-check">
							<div class="checkbox checkbox-primary">
								<input id="checkbox6" type="checkbox" name="shipper_mobile" value="1" <?php echo (config('setting.shipper_mobile') == 1)?'checked':''; ?>>
								<label for="checkbox6"> Display Shipper's Mobile No. and Alternate Mobile No. in Label </label><br> 
							</div>
						</div>
						
						<div class="main-001-check">
							<div class="checkbox checkbox-primary">
								<input id="checkbox7" type="checkbox" name="shipper_address" value="1" <?php echo (config('setting.shipper_address') == 1)?'checked':''; ?>>
								<label for="checkbox7"> Display Shipper's Address in Label </label><br> 
							</div>
						</div>
						
						<div class="main-001-check">
							<div class="checkbox checkbox-primary">
								<input id="checkbox10" type="checkbox" name="consignee_contact" value="1" <?php echo (config('setting.consignee_contact') == 1)?'checked':''; ?>>
								<label for="checkbox10">  Display Consignee’s Contact Number in Label </label><br> 
							</div>
						</div>
						<div class="text-align-left">
							<button class="btn-main-1"> Submit </button>
						</div>
					</div>
					</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection