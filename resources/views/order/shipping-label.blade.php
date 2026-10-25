@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipments Label')
@section('header_title', 'Shipments Label')
@section('content')
<div class="content-page">
    <div class="content"> 
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="main-order-001">
					<div class="row">
						@foreach($labels ?? [] as $key => $base64Image)
							<div class="col-lg-6 col-md-6 col-sm-12">
								<div class="card shadow-sm p-3 mb-4">
									<h5 class="card-title">Label {{ $key + 1 }}</h5>
									@if($base64Image)
										<img src="{{ $base64Image }}" class="img-fluid border rounded">
									@else
										<p class="text-danger">No Label Found</p>
									@endif
									
									@if($base64Image)
										<a href="{{ $base64Image }}" class="btn btn-primary mt-2" download> Download </a>
									@endif
								</div>
							</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection