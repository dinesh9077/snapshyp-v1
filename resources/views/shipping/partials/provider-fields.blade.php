{{-- Provider picker plus every credential field; the JS shows only the ones the chosen provider needs --}}
<div class="col-lg-6">
	<div class="from-group my-2">
		<label for="{{ $prefix }}provider"> Provider </label>
		<select class="form-control provider-select" name="provider" id="{{ $prefix }}provider"
			{{ $editing ? 'disabled' : 'required' }}>
			@foreach (\App\Models\ShippingCompany::PROVIDERS as $providerKey => $provider)
				<option value="{{ $providerKey }}">{{ $provider['label'] }}</option>
			@endforeach
			@if ($editing)
				<option value="other">Other (not integrated)</option>
			@endif
		</select>
	</div>
</div>
@foreach (\App\Models\ShippingCompany::credentialFields() as $field => $label)
	<div class="col-lg-6 provider-field" data-field="{{ $field }}">
		<div class="from-group my-2">
			<label for="{{ $prefix }}{{ $field }}">{{ $label }}</label>
			<input type="{{ $field === 'url' ? 'url' : 'text' }}" class="form-control" id="{{ $prefix }}{{ $field }}"
				name="{{ $field }}" placeholder="{{ $label }}">
		</div>
	</div>
@endforeach
