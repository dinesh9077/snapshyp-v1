<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="varyingModalLabel" aria-hidden="true"
	data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="varyingModalLabel">Update Staff</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form id="editStaffForm" action="{{ route('staff.update', ['id' => $staff->id]) }}" method="post"
				enctype="multipart/form-data">
				<div class="modal-body row">
					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Company Name<span
								class="text-danger">*</span></label>
						<input type="text" class="form-control new-border-popups" id="company_name" name="company_name"
							value="{{ $staff->company_name }}" required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Name<span class="text-danger">*</span></label>
						<input type="text" class="form-control new-border-popups" id="name" name="name"
							value="{{ $staff->name }}">
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Mobile<span class="text-danger">*</span></label>
						<input type="text" class="form-control new-border-popups" id="mobile" name="mobile"
							value="{{ $staff->mobile }}" oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));">
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Email<span class="text-danger">*</span></label>
						<input type="text" class="form-control new-border-popups" id="email" name="email"
							value="{{ $staff->email }}">
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Password<span
								class="text-danger">*</span></label>
						{{-- xpass is stored encrypted; the model decrypts it for this admin screen --}}
						<input type="text" class="form-control new-border-popups" id="password" name="password"
							value="{{ $staff->xpass }}" autocomplete="off">
					</div>
					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Geneder<span class="text-danger">*</span></label>
						<select autocomplete="off" class="form-control new-border-popups" name="gender" id="gender"
							Required>
							<option value=""> Select Gender </option>
							<option value="Male" {{ $staff->gender == 'Male' ? 'selected' : '' }}> Male </option>
							<option value="Female" {{ $staff->gender == 'Female' ? 'selected' : '' }}> Female</option>
							<option value="Other" {{ $staff->gender == 'Other' ? 'selected' : '' }}> Other</option>
						</select>
					</div>
					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Roles<span class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="role" name="role" required>
							<option value=""> Select Roles </option>
							@if(isset($roles))
								@foreach($roles as $role)
									@if($role->name != 'user')
										<option value="{{ $role->name }}" data-role-id="{{ $role->id }}" {{ strtolower(str_replace(' ', '_', $staff->role)) == strtolower(str_replace(' ', '_', $role->name)) ? 'selected' : '' }}> {{ ucwords(str_replace('_', ' ', $role->name)) }} </option>
									@endif
								@endforeach
							@endif
						</select>
					</div>
					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Status<span class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="status" name="status">
							<option value="1" {{ $staff->status == 1 ? 'selected' : '' }}> Active </option>
							<option value="0" {{ $staff->status == 0 ? 'selected' : '' }}> In-Active </option>
						</select>
					</div>

					<div
						class="mb-3 col-md-6 shipping-api-provider-div {{ strtolower((string) ($staff->role ?? '')) === 'franchise' ? '' : 'd-none' }}">
						<label for="shipping_api_provider" class="form-label">API Provider</label>
						<select class="form-control new-border-popups" id="shipping_api_provider"
							name="shipping_api_provider">
							<option value="">No API</option>
							@foreach(($shippingApiProviders ?? []) as $providerKey => $providerLabel)
								<option value="{{ $providerKey }}" {{ strtolower((string) ($staff->shipping_api_provider ?? '')) === $providerKey ? 'selected' : '' }}>
									{{ $providerLabel }}
								</option>
							@endforeach
						</select>
					</div>

					<div class="mb-3 col-md-6 rate-card-div">
						<label for="b2c_rate_card_id" class="form-label">B2C Rate Card<span
								class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="b2c_rate_card_id" name="b2c_rate_card_id">
							<option value="0" {{ (($staff->b2c_rate_card_id ?? $staff->rate_card_id ?? 0) == 0) ? 'selected' : '' }}>All</option>
							@if(isset($b2cRateCards))
								@foreach ($b2cRateCards as $rateCard)
									<option value="{{ $rateCard->id }}" {{ (($staff->b2c_rate_card_id ?? $staff->rate_card_id) == $rateCard->id) ? 'selected' : '' }}>
										{{ $rateCard->group_name }}
									</option>
								@endforeach
							@endif
						</select>
					</div>

					{{-- <div class="mb-3 col-md-6 rate-card-div">
						<label for="b2b_rate_card_id" class="form-label">B2B Rate Card<span
								class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="b2b_rate_card_id" name="b2b_rate_card_id">
							<option value="0" {{ (($staff->b2b_rate_card_id ?? $staff->rate_card_id ?? 0) == 0) ?
								'selected' : '' }}>All</option>
							@if(isset($b2bRateCards))
							@foreach ($b2bRateCards as $rateCard)
							<option value="{{ $rateCard->id }}" {{ (($staff->b2b_rate_card_id ?? $staff->rate_card_id)
								== $rateCard->id) ? 'selected' : '' }}>
								{{ $rateCard->group_name }}
							</option>
							@endforeach
							@endif
						</select>
					</div> --}}

					<div
						class="mb-3 col-md-6 staff-franchise-charge-div {{ strtolower((string) ($staff->role ?? '')) === 'staff' ? '' : 'd-none' }}">
						<label for="franchise_charge_type" class="form-label">Franchise Charge Type</label>
						<select class="form-control new-border-popups" id="franchise_charge_type"
							name="franchise_charge_type">
							<option value="1" {{ (int) ($staff->franchise_charge_type ?? 1) === 1 ? 'selected' : '' }}>Fix
							</option>
							<option value="2" {{ (int) ($staff->franchise_charge_type ?? 1) === 2 ? 'selected' : '' }}>
								Percentage</option>
						</select>
					</div>

					<div
						class="mb-3 col-md-6 staff-franchise-charge-div {{ strtolower((string) ($staff->role ?? '')) === 'staff' ? '' : 'd-none' }}">
						<label for="franchise_charge" class="form-label">Franchise Charge</label>
						<input type="number" step="0.01" min="0" class="form-control new-border-popups"
							id="franchise_charge" name="franchise_charge" value="{{ $staff->franchise_charge ?? 0 }}">
						<small class="text-muted">If this staff ships an order, this amount will be credited to the
							franchise.</small>
					</div>

					<div class="modal-footer w-100 text-right" style="padding: 0px 10px; border-top : 0px;">
						<button type="submit" class="new-submit-btn">Submit</button>
					</div>
			</form>
		</div>
	</div>
</div>
<script>
	function toggleEditStaffApiProviderField() {
		const role = ($('#editStaffForm #role').val() || '');
		const isFranchise = role === 'franchise';
		const showChargeInputs = role === 'staff' || role === 'franchise';
		const wrapper = $('#editStaffForm .shipping-api-provider-div');
		const input = $('#editStaffForm #shipping_api_provider');
		const chargeWrapper = $('#editStaffForm .staff-franchise-charge-div');
		const chargeType = $('#editStaffForm #franchise_charge_type');
		const chargeValue = $('#editStaffForm #franchise_charge');

		wrapper.toggleClass('d-none', !isFranchise);
		if (!isFranchise) {
			input.val('');
		}

		chargeWrapper.toggleClass('d-none', !showChargeInputs);
		if (!showChargeInputs) {
			chargeType.val('1');
			chargeValue.val('0');
		}
	}

	$('#editStaffForm').submit(function (event) {
		event.preventDefault();

		$(this).find('button').prop('disabled', true);
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
			success: function (res) {
				$('#editStaffForm').find('button').prop('disabled', false);
				$('.error_msg').remove();

				if (res.status === "success") {
					dataTable.draw();
					toastrMsg(res.status, res.msg);
					$('#editStaffModal').modal('hide');
				}
				else if (res.status == "validation") {
					$.each(res.errors, function (key, value) {
						var inputField = $('#' + key);
						var errorSpan = $('<span>')
							.addClass('error_msg text-danger')
							.attr('id', key + 'Error')
							.text(value[0]);
						inputField.parent().append(errorSpan);
					});
				}
				else {
					toastrMsg(res.status, res.msg);
				}
			}
		});
	});

	$('#editStaffForm #role').on('change', function () {
		toggleEditStaffApiProviderField();
	});

	toggleEditStaffApiProviderField();

</script>