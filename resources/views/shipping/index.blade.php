@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipping Companies')
@section('header_title', 'Shipping Companies')
@section('content')
	@php
		$shippingSelectionOptions = $shippingSelectionOptions ?? [
			['value' => 'smart_select', 'label' => 'Smart Select'],
			['value' => 'carriers', 'label' => 'Carriers'],
		];
		$shippingSelectionMap = $shippingSelectionMap ?? collect($shippingSelectionOptions)
			->pluck('label', 'value')
			->all();
	@endphp

	<style>
		:root {
			--new-color-prime: #3984FB;
		}

		.manage-pincode-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 34px;
			height: 34px;
			border-radius: 50%;
			color: var(--new-color-prime) !important;
			transition: all 0.2s ease;
		}

		.manage-pincode-icon:hover {
			background: rgba(31, 75, 153, 0.12);
			color: var(--new-color-prime) !important;
			text-decoration: none;
		}

		.manage-pincode-icon i {
			font-size: 18px;
		}

		.company-workspace-icon {
			color: #fb5c08 !important;
		}

		.company-workspace-icon:hover {
			background: rgba(251, 92, 8, 0.12);
			color: #d94800 !important;
		}
	</style>

	<div class="content-page">
		<div class="content">

			<!-- Start Content-->
			<div class="container-fluid">
				<div class="main-order-page-1">
					<div class="main-order-001">
						<div class="main-create-order">
							<div class="main-disolay-felx" style="margin-top: 0 !important;">
								@if(config('permission.shipping_company.add'))
									<div class="main-btn0main-1 ml-auto">
										<button class="btn-main-1" onclick="createShipping(event)">
											<span class="mdi mdi-plus"></span> Create Shipping Company </button>
									</div>
								@endif
							</div>

							<div class="main-data-teble-1 table-responsive">
								<table id="shipping-datatable" class="" style="width:100%">
									<thead>
										<tr>
											<th>Sr.No.</th>
											<th>Company Logo</th>
											<th>Company Name</th>
											<th>Provider</th>
											<th>Url</th>
											<th>Environment</th>
											<th>Status</th>
											<th>Created At</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										@foreach($shippings as $key => $shipping)
											<tr>
												<td>{{ $key + 1 }}</td>
												<td>
													@if($shipping->logo)
														<img class="pan1"
															src="{{ url('storage/shipping-logo/' . $shipping->logo) }}"
															alt="{{ $shipping->name }} Logo">
													@else
														<span>No Logo</span>
													@endif
												</td>
												<td>
													{{ $shipping->name }}
													@if($shipping->is_default)
														<span class="badge badge-primary ml-1" title="Sellers without an assigned account use this one">Default</span>
													@endif
												</td>
												<td>{{ $shipping->providerLabel() }}</td>
												<td>{{ $shipping->url }}</td>
												<td>
													<span
														class="badge {{ $shipping->mode == 1 ? 'badge-success' : 'badge-warning' }}">
														{{ $shipping->mode == 1 ? 'Live' : 'TEST' }}
													</span>
												</td>
												<td>
													<span
														class="badge {{ $shipping->status == 1 ? 'badge-success' : 'badge-danger' }}">
														{{ $shipping->status == 1 ? 'Active' : 'In-Active' }}
													</span>
												</td>
												<td>{{ $shipping->created_at->format('d M Y') }}</td>
												<td>
													@if(config('permission.shipping_company.edit'))
														<a href="javascript:;" onclick="editShipping(this,event)"
															data-id="{{ $shipping->id }}" data-name="{{ $shipping->name }}"
															data-status="{{ $shipping->status }}"
															@foreach (\App\Models\ShippingCompany::credentialFields() as $field => $label) data-{{ $field }}="{{ $shipping->$field }}" @endforeach
															data-mode="{{ $shipping->mode }}"
															data-provider="{{ $shipping->provider }}"
															data-is_default="{{ $shipping->is_default ? 1 : 0 }}" class="class-pencil mr-1">
															<i class="mdi mdi-pencil "></i>
														</a>
														<a href="{{ route('shipping.company.manage-pincode', $shipping->id) }}"
															class="btn btn-icon waves-effect waves-light action-icon manage-pincode-icon"
															data-toggle="tooltip" title="Manage Pincode">
															<i class="mdi mdi-map-marker-radius"></i>
														</a>
														{{-- <a href="{{ route('shipping.company.workspace', $shipping->id) }}"
															class="btn btn-icon waves-effect waves-light action-icon manage-pincode-icon company-workspace-icon"
															data-toggle="tooltip" title="Company Workspace">
															<i class="mdi mdi-truck-delivery"></i>
														</a> --}}
													@endif
												</td>
											</tr>
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

	<div class="modal fade bd-example-modal-lg" id="createShippingModal" tabindex="-1" role="dialog"
		aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">
						<span class="mdi mdi-plus"></span> Create Shipping Company
					</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="mdi mdi-close"></i>
					</button>
				</div>
				<form id="shippingStoreForm" method="post" action="{{ route('shipping.company.store') }}"
					enctype="multipart/form-data">
					@csrf
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id">Shipping Company Logo </label>
									<input type="file" class="form-control" name="logo"
										accept="image/png, image/jpe, image/jpeg">
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id">Shipping Company Name </label>
									<input type="text" class="form-control" name="name" placeholder="Shipping Company Name"
										required>
								</div>
							</div>
							@include('shipping.partials.provider-fields', ['prefix' => 'create_', 'editing' => false])
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id"> Environment Mode</label>
									<select class="form-control" name="mode" id="mode" required>
										<option value="1"> Live </option>
										<option value="2"> TEST </option>
									</select>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id"> Status </label>
									<select class="form-control" name="status" id="status" required>
										<option value="1"> Active </option>
										<option value="2"> In-Active </option>
									</select>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="is_default"> Default Account </label>
									<select class="form-control" name="is_default" id="is_default">
										<option value="0"> No </option>
										<option value="1"> Yes (sellers without an assigned account use this) </option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer" style="border-top: none; padding-top : 0px;">
						<button type="submit" class="new-submit-btn"> Save </button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="modal fade" id="editShippingModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
		aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel"> Update Shipping Company </h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<i class="mdi mdi-close"></i>
					</button>
				</div>

				<form id="shippingEditForm" method="post" action="{{ route('shipping.company.update') }}"
					enctype="multipart/form-data">
					@csrf
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id">Shipping Company Logo </label>
									<input type="file" class="form-control" name="logo"
										accept="image/png, image/jpe, image/jpeg">
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id">Shipping Company Name </label>
									<input type="text" class="form-control" name="name" id="edit_name"
										placeholder="Shipping Company Name" required>
								</div>
								<input type="hidden" id="edit_id" name="id">
							</div>
							@include('shipping.partials.provider-fields', ['prefix' => 'edit_', 'editing' => true])
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id"> Environment Mode</label>
									<select class="form-control" name="mode" id="edit_mode" required>
										<option value="1"> Live </option>
										<option value="2"> TEST </option>
									</select>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="order-id"> Status </label>
									<select class="form-control" name="status" id="edit_status" required>
										<option value="1"> Active </option>
										<option value="2"> In-Active </option>
									</select>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="from-group my-2">
									<label for="edit_is_default"> Default Account </label>
									<select class="form-control" name="is_default" id="edit_is_default">
										<option value="0"> No </option>
										<option value="1"> Yes (sellers without an assigned account use this) </option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer " style="border-top: none; padding-top: 0px;">
						<button type="submit" class="new-submit-btn"> Save </button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection
@push('js')
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script>
		const defaultSelectionMode = 'smart_select';

		$('#shipping-datatable').DataTable();

		const courierProviders = @json(\App\Models\ShippingCompany::PROVIDERS);

		// Show only the credential fields the selected provider needs; hidden ones are disabled so they are not posted
		function applyProviderFields($form) {
			var provider = courierProviders[$form.find('.provider-select').val()];
			$form.find('.provider-field').each(function() {
				var field = $(this).data('field');
				var label = provider ? provider.fields[field] : null;
				var $input = $(this).find('input');
				// Unknown (legacy) provider: keep every field editable, none required
				$(this).toggle(!provider || !!label);
				$input.prop('disabled', !!provider && !label).prop('required', !!label);
				if (label) {
					$(this).find('label').text(label);
					$input.attr('placeholder', label);
				}
			});
		}

		$('#create_provider').on('change', function() {
			var $form = $('#shippingStoreForm');
			var provider = courierProviders[$(this).val()];
			$form.find('[name="url"]').val(provider && provider.default_url ? provider.default_url : '');
			applyProviderFields($form);
		});

		function createShipping(event) {
			event.preventDefault();
			$('#shippingStoreForm')[0].reset();
			$('#create_provider').trigger('change');
			$('#createShippingModal').modal('show');
		}

		function editShipping(obj, event) {
			event.preventDefault();

			var $obj = $(obj);

			var id = $obj.data('id');
			var name = $obj.data('name');
			var status = $obj.data('status');
			var mode = $obj.data('mode');

			// Set the values in the form
			$('#edit_id').val(id);
			$('#edit_name').val(name);
			$('#shippingEditForm .provider-field').each(function() {
				var field = $(this).data('field');
				$(this).find('input').val($obj.attr('data-' + field) || '');
			});
			$('#edit_mode').val(mode);
			$('#edit_is_default').val($obj.data('is_default') ? 1 : 0);
			$('#edit_status').val(status);
			$('#edit_provider').val($obj.data('provider') in courierProviders ? $obj.data('provider') : 'other');
			applyProviderFields($('#shippingEditForm'));

			// Show the modal
			$('#editShippingModal').modal('show');
		}

	</script>
@endpush
