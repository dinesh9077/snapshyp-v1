@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - Product category')
@section('header_title','Product category')
@section('content') 

<div class="content-page">
    <div class="content">
		
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">                    
               <div class="main-order-001"> 
                    <div class="main-create-order">
                        <div class="main-disolay-felx" style="margin-top: 0 !important;">
                            <div class="main-btn0main-1">
        						<button  class="btn btn-primary btn-main-1" data-toggle="modal" data-target=".bd-example-modal-lg"> Add Product Category </button>
        					</div>
        				</div>
        				
                        <div class="main-data-teble-1 table-responsive">
                            <table id="shipping-datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Sr.no</th>
                                        <th>Product Category Name</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
        							</tr>
        						</thead>
                                <tbody>
        							@foreach($products as $key=>$product)
        							<td> {{( $key + 1 )}}</td>
        							<td>{{$product->name}}</td>
        							<td>
        								@if($product->status == "1")
        								<span class="badge badge-success">Active</span>
        								@else
        								<span class="badge badge-danger">In-Active</span>	
        								@endif 
        							</td>
        							<td>{{date('d M Y',strtotime($product->created_at))}}</td>
        							<td>
        								<a href="javascript:;" onclick="editProductCategory(this,event)" data-id="{{$product->id}}" data-name="{{$product->name}}" data-status="{{$product->status}}" class="btn btn-icon waves-effect waves-light action-icon mr-1"> <i class="mdi mdi-pencil"></i> </a>
        								
        								<a href="{{url('product-category/delete',$product->id)}}" class="btn btn-icon waves-effect waves-light action-icon mr-1"onClick="deleteRecord(this,event);"> <i class="mdi mdi-trash-can-outline"></i> </a></td> 
        								
        							</td> 
        							@endforeach
        						</tbody>
        					</table>
        				</div>
        			</div>
    			</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Add Product Category </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
				</button>
			</div>
			
			<form method="post" action="{{route('product-category.store')}} ">
				@csrf
				<div class="modal-body">
                    <div class="from-group my-2">
                        <label for="order-id"> Product Category </label>
                        <input type="text" class="form-control" name="name" placeholder="Product Category" required>
					</div> 
					<div class="from-group my-2">
                        <label for="order-id"> Status </label>
						<select class="form-control" name="status" id="status" required>
							<option value="1"> Active </option>
							<option value="2"> In-Active </option>
						</select>
					</div> 
				</div>
				<div class="modal-footer"> 
					<button type="submit" class="btn btn-primary btn-main-1"> Save </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade editShipping" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Update Product Category </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
				</button>
			</div>
			
			<form method="post" action="{{route('product-category.update')}} ">
				@csrf
				<div class="modal-body">
                    <div class="from-group my-2">
                        <label for="order-id"> Product Category </label>
                        <input type="text" class="form-control" name="name" id="edit_name" placeholder="Product Category" required>
					</div> 
					<input type="hidden" id="edit_id" name="id" >
					<div class="from-group my-2">
                        <label for="order-id"> Status </label>
						<select class="form-control" name="status" id="edit_status" required>
							<option value="1"> Active </option>
							<option value="2"> In-Active </option>
						</select>
					</div> 
				</div>
				<div class="modal-footer"> 
					<button type="submit" class="btn btn-primary btn-main-1"> Save </button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection
@push('js') 
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> 
<script> 
	$('#shipping-datatable').DataTable(); 
	
	function editProductCategory(obj,event)
	{
		event.preventDefault();	
		var id = $(obj).attr('data-id');
		var name = $(obj).attr('data-name');
		var status = $(obj).attr('data-status');
		
		$('#edit_id').val(id);
		$('#edit_name').val(name);
		$('#edit_status').val(status);
		$('.editShipping').modal('show');
	}
</script>
@endpush