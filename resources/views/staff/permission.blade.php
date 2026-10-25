<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="varyingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="varyingModalLabel">Update staff Permission</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form id="editPermissionForm" action="{{ route('staff.permission-update', ['id' => $staff->id]) }}" method="post" enctype="multipart/form-data">
				<div class="modal-body row"> 
					<div class="mb-2 col-md-12">
						<label for="recipient-name" class="form-label">Roles <span class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="role" name="role">
							<option value=""> Select Roles </option> 
							@foreach($roles as $role)
								@if($role->name != "user")
									<option value="{{ $role->name }}" data-role-id="{{ $role->id }}" {{ strtolower(str_replace(' ', '_', $staff->role)) == strtolower(str_replace(' ', '_', $role->name)) ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $role->name)) }} </option> 
								@endif
							@endforeach
						</select>
					</div>   
				</div> 
				<div class="row permission_show m-0">
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
												<input id="view_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.view]" value="view" <?php if(in_array($permissions[$i]->name.'.view',$roleper)){ echo 'checked'; } ?>>
											</td> 
											<td>
												<input id="add_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.add]" value="add" <?php if(in_array($permissions[$i]->name.'.add',$roleper)){ echo 'checked'; } ?>>
											</td> 
											<td>
												<input id="edit_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.edit]" value="edit" <?php if(in_array($permissions[$i]->name.'.edit',$roleper)){ echo 'checked'; } ?>>
											</td> 
											<td>
												<input id="delete_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.delete]" value="delete" <?php if(in_array($permissions[$i]->name.'.delete',$roleper)){ echo 'checked'; } ?>>
											</td>  
										</tr> 
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>  	
				</div>
				<div class="modal-footer" style="padding-top: 0px; border-top : 0px;">
					<button type="submit"  class="new-submit-btn">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$('#editPermissionForm').submit(function(event) 
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
				$('#editPermissionForm').find('button').prop('disabled',false);	 
				$('.error_msg').remove(); 
				
				if(res.status === "success")
				{ 
					dataTable.draw();
					toastrMsg(res.status, res.msg);  
					$('#editPermissionModal').modal('hide');
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
					toastrMsg(res.status, res.msg); 
				}
			} 
		});
	});	
	
	$('#role').change(function ()
	{ 
		// Clear previous permissions
		$('.permission_show').html('');

		const role = $(this).val();
		const roleId = $(this).find(':selected').data('role-id');
 
		if(role != "master_admin")
		{
			$.get("{{url('roles/groups')}}/"+roleId, function(res)
			{  
				$('.permission_show').html(res.view);  
			},'Json');
		}  
	})
		
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
				$('.checkbox_{{$headkey}}').on('click',function()
				{
					if($('.checkbox_{{$headkey}}:checked').length == $('.checkbox_{{$headkey}}').length){
						
						$('#all_{{$headkey}}').prop('checked',true);
						}else{
						
						$('#all_{{$headkey}}').prop('checked',false);
					}
				});
				
				if($('.checkbox_{{$headkey}}:checked').length == $('.checkbox_{{$headkey}}').length){
					
					$('#all_{{$headkey}}').prop('checked',true);
					}else{
					
					$('#all_{{$headkey}}').prop('checked',false);
				}
			});
		</script>
		<?php
		}
	?> 
