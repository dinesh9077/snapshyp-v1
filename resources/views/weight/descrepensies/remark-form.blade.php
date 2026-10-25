@extends('layouts.backend.app')

@section('title', config('setting.company_name') . ' - Weight Discrepancies Remark')
@section('header_title', 'Weight Discrepancies Remark')

@section('content')
	<style>
		/* .chat-container { 
			margin: auto;
			background: white;
			padding: 20px;
			border-radius: 10px;
			box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
		} */

		.chat-container h4 {
			color: black;
			margin-bottom: 24px;
		}

		.chat-box {
			height: 300px;
			overflow-y: auto;
			border-radius: 5px;
			padding: 15px;
			background: #fff;
			border: 1px solid #ddd;
		}

		.chat-input {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-top: 10px;
		}

		.chat-input input[type="text"],
		.chat-input input[type="file"] {
			flex: 1;
			padding: 8px;
			border-radius: 10px;
			border: 1px solid #ccc;
		}

		.chat-input button {
			background: #3984FB;
			color: white;
			border: none;
			padding: 14px 38px;
			border-radius: 10px;
			cursor: pointer;
		}

		.image-preview {
			gap: 10px;
			flex-wrap: wrap;
			margin-top: 10px;
		}

		.image-preview img {
			width: 50px;
			height: 50px;
			border-radius: 5px;
			object-fit: cover;
			border: 1px solid #ddd;
		}

		.message {
			padding: 10px;
			border-radius: 10px;
			word-wrap: break-word;
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
		}

		.admin-message {
			background-color: #F3F3F3;
			color: #000000;
		}

		.user-message {
			background-color: #F3F3F3;
			color: #000000;
		}

		/* Align User Messages to the Right */
		.user-right {
			align-self: flex-end;
			text-align: right;
		}

		/* Align Admin Messages to the Left */
		.admin-left {
			align-self: flex-start;
			text-align: left;
		}

		/* Admin's Own Messages (if they send from admin panel) */
		.admin-right {
			align-self: flex-end;
			text-align: right;
		}

		/* User Messages (if they are sending) */
		.user-left {
			align-self: flex-start;
			text-align: left;
		}
	</style>

	<div class="content-page">
		<div class="content">
			<div class="container-fluid mt-3">
				<div class="chat-container main-order-001">
					<h4>AWB Number: {{ $order->awb_number }}</h4>
					<div class="chat-box" id="chatBox">
						@foreach($remarks as $remark)
							@php
								$images = $remark->images ?? [];
							@endphp
							@if(auth()->user()->role == "admin")
								@if($remark->role == "admin")
									<!-- Admin's own messages -->
									<div class="message admin-message  mt-2" style="font-size: 16px;">
										<span
											style="font-size: 13px;border-bottom: 1px dashed black;">{{ $remark->created_at->format('Y-m-d h:i a') }}
										</span> <span style="float: right;margin-left: auto;"> <strong>Me:</strong>
											{{ $remark->remark }} </span>
										@if(!empty($images))
											<div class="image-preview">
												@foreach($images as $image)
													<a href="{{ asset('storage/' . $image) }}" target="_blank"><img
															src="{{ asset('storage/' . $image) }}" alt="Remark Image"></a>
												@endforeach
											</div>
										@endif
									</div>
								@else
									<!-- User's messages for Admin -->
									<div class="message user-message user-left mt-2" style="font-size: 16px;">
										<strong>{{ $remark->user->name }}:</strong> {{ $remark->remark }} <span
											style="font-size: 13px;float: right;border-bottom: 1px dashed black;">{{ $remark->created_at->format('Y-m-d h:i a') }}
										</span>
										@if(!empty($images))
											<div class="image-preview">
												@foreach($images as $image)
													<a href="{{ asset('storage/' . $image) }}" target="_blank"><img
															src="{{ asset('storage/' . $image) }}" alt="Remark Image"></a>
												@endforeach
											</div>
										@endif
									</div>
								@endif
							@else
								@if($remark->role == "admin")
									<!-- Admin messages for User (left side) -->
									<div class="message admin-message admin-left mt-2" style="font-size: 16px;">
										<strong>Admin:</strong> {{ $remark->remark }}<span
											style="font-size: 13px;float: right;border-bottom: 1px dashed black;">{{ $remark->created_at->format('Y-m-d h:i a') }}
										</span>
										@if(!empty($images))
											<div class="image-preview">
												@foreach($images as $image)
													<a href="{{ asset('storage/' . $image) }}" target="_blank"><img
															src="{{ asset('storage/' . $image) }}" alt="Remark Image"></a>
												@endforeach
											</div>
										@endif
									</div>
								@else
									<!-- User's own messages (right side) -->
									<div class="message user-message mt-2" style="font-size: 16px;">
										<span
											style="font-size: 13px;border-bottom: 1px dashed black;">{{ $remark->created_at->format('Y-m-d h:i a') }}
										</span><span style="float: right;margin-left: auto;"><strong>Me:</strong>
											{{ $remark->remark }}</span>
										@if(!empty($images))
											<div class="image-preview">
												@foreach($images as $image)
													<a href="{{ asset('storage/' . $image) }}" target="_blank"><img
															src="{{ asset('storage/' . $image) }}" alt="Remark Image"></a>
												@endforeach
											</div>
										@endif
									</div>
								@endif
							@endif
						@endforeach
					</div>


					<form id="remarkForm" action="{{ route('weight.remark.store') }}" method="POST"
						enctype="multipart/form-data" class="mt-3">
						@csrf
						<input type="hidden" name="order_id" value="{{ $order->id }}">
						<div class="chat-input">
							<input type="text" name="remark" id="remarkInput" style="height: 49px;"
								placeholder="Type your remark..." required>
							<input type="file" id="imageUpload" name="images[]" style="height: 49px;" accept="image/*"
								multiple onchange="previewImages()">
							<button type="submit">Send</button>
						</div>
					</form>
					<div class="image-preview" id="imagePreview"></div>
				</div>
			</div>
		</div>
	</div>

	<script>
		function sendMessage() {
			let input = document.getElementById("remarkInput");
			let chatBox = document.getElementById("chatBox");

			if (input.value.trim() === "") return;

			let messageDiv = document.createElement("div");
			messageDiv.classList.add("message", "user-message");
			messageDiv.innerHTML = "<strong>You:</strong> " + input.value;

			chatBox.appendChild(messageDiv);
			chatBox.scrollTop = chatBox.scrollHeight;

			input.value = "";
		}

		function previewImages() {
			let preview = document.getElementById("imagePreview");
			let files = document.getElementById("imageUpload").files;

			preview.innerHTML = "";

			Array.from(files).forEach(file => {
				let img = document.createElement("img");
				img.src = URL.createObjectURL(file);
				preview.appendChild(img);
			});
		}


	</script>
@endsection