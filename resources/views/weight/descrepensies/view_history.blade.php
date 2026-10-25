<style>
</style>
<div class="table-responsive">
	<table class="dataTable no-footer" style="width:100%; !important">
		<thead>
			<tr>
				<th>Date</th>
				<th>Activity</th>
				<th>Action By</th>
				<th>Remark</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			@foreach($weight_history as $row)
			<tr>
				<td>{{date('M d Y h:i a',strtotime($row->created_at))}}</td>
				<td>{{$row->status_descrepency}}</td>
				<td>{{$row->action_by}}</td>
				<td>{{$row->remarks}}</td>

				<td>
					@if($row->status_descrepency == "Rejected")
					<?php $weight_descrepencies = App\Models\weightDescrepency::whereOrder_id($row->order_id)->first();  ?>
					@if(!empty($weight_descrepencies))
					<a href="javascript:;" data-id="{{$weight_descrepencies->id}}" onclick="viewImage(this,event)">View Image</a>
					@endif
					@endif
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>