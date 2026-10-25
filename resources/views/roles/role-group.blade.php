<div class="table-responsive ">
	<table id="datatable" class="table table-bordered  dt-responsive nowrap extra" style="border-collapse: collapse; border-spacing: 0; width: 100%;"> 
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
						<input id="view_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.view]" value="view" <?php if(in_array($permissions[$i]->name.'.view', $roleper)){ echo 'checked'; } ?>>
					</td> 
					<td>
						<input id="add_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.add]" value="add" <?php if(in_array($permissions[$i]->name.'.add', $roleper)){ echo 'checked'; } ?>>
					</td> 
					<td>
						<input id="edit_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.edit]" value="edit" <?php if(in_array($permissions[$i]->name.'.edit', $roleper)){ echo 'checked'; } ?>>
					</td> 
					<td>
						<input id="delete_{{$headkey}}" class="checkbox_{{$headkey}}" type="checkbox" name="permission[{{$permissions[$i]->name}}.delete]" value="delete" <?php if(in_array($permissions[$i]->name.'.delete', $roleper)){ echo 'checked'; } ?>>
					</td>  
				</tr> 
			<?php } ?>
		</tbody>
	</table>
</div> 

<script> 
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
		$headkey = strtolower(str_replace(' ','_', $permissions[$i]->name)).$i;
	?>
	<script>
		$(document).ready(function()
		{ 
			$('.checkbox_{{$headkey}}').on('click',function()
			{
				if($('.checkbox_{{$headkey}}:checked').length == $('.checkbox_{{$headkey}}').length)
				{
					$('#all_{{$headkey}}').prop('checked',true);
				}
				else
				{
					$('#all_{{$headkey}}').prop('checked',false);
				}
			});
			
			if($('.checkbox_{{$headkey}}:checked').length == $('.checkbox_{{$headkey}}').length)
			{
				$('#all_{{$headkey}}').prop('checked',true);
			}
			else
			{
				$('#all_{{$headkey}}').prop('checked',false);
			}
		}); 
	</script>
	<?php
	}
?> 