<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="varyingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="varyingModalLabel">Add New Role</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form id="addRoleForm" action="{{ route('roles.store') }}" method="post" enctype="multipart/form-data">
				<div class="modal-body row">
					<div class="mb-2 col-md-6">
						<label for="recipient-name" class="form-label ">Role Name   <span class="text-danger">*</span></label>
						<input type="text" class="form-control new-border-popups" id="name" name="name" required>
					</div>  
					<div class="mb-2 col-md-6">
						<label for="recipient-name" class="form-label ">Status <span class="text-danger">*</span> </label>
						<select class="form-control new-border-popups" id="status" name="status" required>
							<option value="1"> Active </option>
							<option value="0"> In-Active </option>
						</select>
					</div>  
				</div>
				<div class="row m-0"> 
					<div class="col-md-12"> 
						<div class="table-responsive ">
							<table id="datatable" class="table table-bordered  dt-responsive nowrap extra role" style="border-collapse: collapse; border-spacing: 0; width: 100%;"> 
								<thead>
									<tr>
										<th>Module Permission</th>
										<th>all</th>
										<th>view</th>
										<th>add</th>
										<th>edit</th>
										<th>delete</th> 
									</tr>
								</thead>
								<tbody>
									<?php
										$length = count($permissions); 
										for($i = 0; $i < $length; $i++) 
										{
											$headkey = strtolower(str_replace(' ','_',$permissions[$i]->name)).$i;
										?>
										<tr>
											<td>{{ucwords(str_replace('_',' ',$permissions[$i]->name))}}</td>
											<td>
												<input id="all_{{$headkey}}" class="selectAll" value="{{$headkey}}" type="checkbox">
											</td>
											<td>
												<input id="view_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox"  name="permission[{{$permissions[$i]->name}}.view]" value="view" value="view">
											</td> 
											<td>
												<input id="add_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.add]" value="add">
											</td> 
											<td>
												<input id="edit_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.edit]" value="edit">
											</td> 
											<td>
												<input id="delete_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.delete]" value="delete">
											</td>  
										</tr> 
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>  
				</div>
				<div class="modal-footer" style="border-top: 0px; padding-top : 0px;">
					<button type="submit" class="new-submit-btn">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$('#addRoleForm').submit(function(event) 
	{
		event.preventDefault();   
		
		$(this).find('button').prop('disabled',true);   
		 
		var formData = new FormData(this);  
		formData.append('_token', "{{ csrf_token() }}"); 
		  
		$.ajax({ 
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: formData,
			processData: false, 
			contentType: false,  
			cache: false, 
			dataType: 'Json', 
			success: function (res) 
			{ 
				$('#addRoleForm').find('button').prop('disabled',false);	 
				$('.error_msg').remove(); 
				
				if(res.status === "success")
				{ 
					dataTable.draw();
					toastrMsg(res.status,res.msg);  
					$('#addRoleModal').modal('hide');
				}
				else if(res.status == "validation")
				{  
					$.each(res.errors, function(key, value) {
						var inputField = $('#' + key);
						var errorSpan = $('<span>')
						.addClass('error_msg text-danger') 
						.attr('id', key + 'Error')
						.text(value[0]);  
						inputField.parent().append(errorSpan);
					});
				}
				else
				{  
					toastrMsg(res.status,res.msg); 
				}
			} 
		});
	});	
	
	$('.selectAllModule').on('click', function()
	{
		if (this.checked) {
			$('tbody').find('input[type="checkbox"]').prop('checked', true);
		} else {
			$('tbody').find('input[type="checkbox"]').prop('checked', false);
		}
	});
	
	$('.selectAll').on('click',function()
	{ 
		var num = $(this).val();   
		if(this.checked)
		{
			$('.checkbox_'+num).each(function(){
				this.checked = true;
			});
		}
		else
		{
			$('.checkbox_'+num).each(function(){
				this.checked = false;
			});
		}
	});
</script> 
<?php
	$length = count($permissions); 
	for($i = 0; $i < $length; $i++) 
	{
		$headkey = strtolower(str_replace(' ','_',$permissions[$i]->name)).$i;
	?>
	<script>
		$(document).ready(function()
		{ 
			$('.checkbox_{{$headkey}}').on('click',function(){
				if($('.checkbox_{{$headkey}}:checked').length == $('.checkbox_{{$headkey}}').length){
					$('#all_{{$headkey}}').prop('checked',true);
					}else{
					$('#all_{{$headkey}}').prop('checked',false);
				}
			});
		});
	</script>
	<?php
	}
?> 