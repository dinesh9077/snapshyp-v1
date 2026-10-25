<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="varyingModalLabel" aria-hidden="true"
	data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="varyingModalLabel">create New staff</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="mdi mdi-close"></i>
				</button>
			</div>
			<form id="addStaffForm" action="{{ route('staff.store') }}" method="post" enctype="multipart/form-data">
				<div class="modal-body row">
					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Company Name<span
								class="text-danger">*</span></label>
						<input type="text" class="form-control  new-border-popups" id="company_name" name="company_name"
							required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Name<span class="text-danger">*</span></label>
						<input type="text" class="form-control  new-border-popups" id="name" name="name" required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Mobile<span class="text-danger">*</span></label>
						<input type="text" class="form-control  new-border-popups" id="mobile" name="mobile"
							oninput="$(this).val($(this).val().replace(/[^0-9.]/g, ''));" required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Email<span class="text-danger">*</span></label>
						<input type="text" class="form-control  new-border-popups" id="email" name="email" required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Password<span
								class="text-danger">*</span></label>
						<input type="password" class="form-control  new-border-popups" id="password" name="password"
							required>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Geneder<span class="text-danger">*</span></label>
						<select autocomplete="off" class="form-control  new-border-popups" name="gender" id="gender"
							Required>
							<option value=""> Select Gender </option>
							<option value="Male"> Male </option>
							<option value="Female"> Female</option>
							<option value="Other"> Other</option>
						</select>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Roles<span class="text-danger">*</span></label>
						<select class="form-control  new-border-popups" id="role" name="role" required>
							<option value=""> Select Roles </option>
							@foreach($roles as $role)
								@if($role->name != 'user')
									<option value="{{ $role->name }}" data-role-id="{{ $role->id }}">
										{{ ucwords(str_replace('_', ' ', $role->name)) }} </option>
								@endif
							@endforeach
						</select>
					</div>

					<div class="mb-3 col-md-6">
						<label for="recipient-name" class="form-label">Status<span class="text-danger">*</span></label>
						<select class="form-control  new-border-popups" id="status" name="status">
							<option value="1"> Active </option>
							<option value="0"> In-Active </option>
						</select>
					</div>

					<div class="mb-3 col-md-6 shipping-api-provider-div d-none">
						<label for="shipping_api_provider" class="form-label">API Provider</label>
						<select class="form-control new-border-popups" id="shipping_api_provider"
							name="shipping_api_provider">
							<option value="">No API</option>
							@foreach(($shippingApiProviders ?? []) as $providerKey => $providerLabel)
								<option value="{{ $providerKey }}">{{ $providerLabel }}</option>
							@endforeach
						</select>
					</div>

					<div class="mb-3 col-md-6 rate-card-div">
						<label for="b2c_rate_card_id" class="form-label">B2C Rate Card<span
								class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="b2c_rate_card_id" name="b2c_rate_card_id">
							<option value="0">All</option>
							@foreach ($b2cRateCards as $rateCard)
								<option value="{{ $rateCard->id }}">{{ $rateCard->group_name }}</option>
							@endforeach
						</select>
					</div>

					{{-- <div class="mb-3 col-md-6 rate-card-div">
						<label for="b2b_rate_card_id" class="form-label">B2B Rate Card<span
								class="text-danger">*</span></label>
						<select class="form-control new-border-popups" id="b2b_rate_card_id" name="b2b_rate_card_id">
							<option value="0">All</option>
							@foreach ($b2bRateCards as $rateCard)
							<option value="{{ $rateCard->id }}">{{ $rateCard->group_name }}</option>
							@endforeach
						</select>
					</div> --}}

					<div class="mb-3 col-md-6 staff-franchise-charge-div d-none">
						<label for="franchise_charge_type" class="form-label">Franchise Charge Type</label>
						<select class="form-control new-border-popups" id="franchise_charge_type"
							name="franchise_charge_type">
							<option value="1">Fix</option>
							<option value="2">Percentage</option>
						</select>
					</div>

					<div class="mb-3 col-md-6 staff-franchise-charge-div d-none">
						<label for="franchise_charge" class="form-label">Franchise Charge</label>
						<input type="number" step="0.01" min="0" class="form-control new-border-popups"
							id="franchise_charge" name="franchise_charge" value="0">
						<small class="text-muted">If this staff ships an order, this amount will be credited to the
							franchise.</small>
					</div>

				</div>
				<div class="roles-table-main permission_show row col-md-12 m-0"> </div>


				<div class="modal-footer" style="border-top: 0px; padding-top : 0px;">
					<button type="submit" class="new-submit-btn">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	function toggleStaffApiProviderField() {
		const role = ($('#addStaffForm #role').val() || '');
		const isFranchise = role === 'franchise';
		const showChargeInputs = role === 'staff' || role === 'franchise';
		const wrapper = $('#addStaffForm .shipping-api-provider-div');
		const input = $('#addStaffForm #shipping_api_provider');
		const chargeWrapper = $('#addStaffForm .staff-franchise-charge-div');
		const chargeType = $('#addStaffForm #franchise_charge_type');
		const chargeValue = $('#addStaffForm #franchise_charge');

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

	$('#addStaffForm').submit(function (event) {
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
				$('#addStaffForm').find('button').prop('disabled', false);
				$('.error_msg').remove();

				if (res.status === "success") {
					dataTable.draw();
					toastrMsg(res.status, res.msg);
					$('#addStaffModal').modal('hide');
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

	$('#role').change(function () {
		toggleStaffApiProviderField();
		$('.permission_show').html('');
		const role = $(this).val();
		const roleId = $(this).find(':selected').attr('data-role-id');

		if (role != "master_admin") {
			$.get("{{ url('roles/groups') }}/" + roleId, function (res) {
				$('.permission_show').html(res.view);
			}, 'Json');
		}
	})

	$('.selectAllModule').on('click', function () {
		if (this.checked) {
			$('tbody').find('input[type="checkbox"]').prop('checked', true);
		} else {
			$('tbody').find('input[type="checkbox"]').prop('checked', false);
		}
	});

	$('.selectAll').on('click', function () {
		var num = $(this).val();
		if (this.checked) {
			$('.checkbox_' + num).each(function () {
				this.checked = true;
			});
		}
		else {
			$('.checkbox_' + num).each(function () {
				this.checked = false;
			});
		}
	});

	toggleStaffApiProviderField();
</script>