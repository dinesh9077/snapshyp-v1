@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Add Cluster')
@section('header_title', 'Add Cluster')
@section('content')  
<div class="content-page">
	<div class="content">
		<!-- Start Content-->
		<div class="container-fluid">
			<div class="main-order-page-1">
				<div class="main-order-001">
					
					<div class="inner-page-heading d-flex justify-content-end"></div>
					
					@if(session('error'))
						<div class="alert alert-danger">{{ session('error') }}</div>
					@endif
					
					<!--@if($errors->any())
						<div class="alert alert-danger">
							<ul class="mb-0">
								@foreach($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif-->
					
					<div class="ordr-main-001">
						<form action="{{ route('rate.dp-world.cluster-store') }}" method="POST">
							@csrf
							
							<div class="mb-3">
								<label>Code Name</label>
								<input type="text" name="code_name" class="form-control" value="{{ old('code_name') }}">
								@error('code_name')
									<small class="text-danger">{{ $message }}</small>
								@enderror
							</div>
							
							<div class="mb-3">
								<label>Zone Name</label>
								<input type="text" name="zone_name" class="form-control" value="{{ old('zone_name') }}">
								@error('zone_name')
									<small class="text-danger">{{ $message }}</small>
								@enderror
							</div>
							
							<div class="mb-3">   
								<label>States</label>
								<select name="states[]" class="control-form select2" multiple>
									@foreach($states as $state)
										<option value="{{ $state }}" {{ collect(old('states'))->contains($state) ? 'selected' : '' }}>{{ $state }}</option>
									@endforeach
								</select>
								@error('states')
									<small class="text-danger">{{ $message }}</small>
								@enderror 
							</div>
							
							<div class="mb-3">
								<label>Cities</label>
								<select name="cities[]" class="form-select select2" multiple>
									@foreach($cities as $city)
										<option value="{{ $city }}" {{ collect(old('cities'))->contains($city) ? 'selected' : '' }}>{{ $city }}</option>
									@endforeach
								</select>
								@error('cities')
									<small class="text-danger">{{ $message }}</small>
								@enderror
							</div>
							
							<button class="btn btn-success">Save</button> 
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div> 
@endsection 