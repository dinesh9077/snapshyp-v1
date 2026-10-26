{{-- "Copy rates from" picker for one courier block; filled in by copy-rates-js --}}
@if ($zoneShippingSettings->count() > 1)
	<div class="d-flex align-items-center copy-rates-control ml-3" style="gap: 8px;">
		<select class="form-control form-control-sm copy-rates-source" style="width: 200px;"
			title="Copy all rates and COD charges from another courier into this one">
			<option value="">Copy rates from...</option>
			@foreach ($zoneShippingSettings as $sourceIndex => $sourceZones)
				@if ((string) $sourceIndex !== (string) $index)
					<option value="{{ $sourceIndex }}">{{ $sourceZones->first()->shippingCompany->name }}</option>
				@endif
			@endforeach
		</select>
		<button type="button" class="btn btn-sm btn-outline-primary copy-rates-apply" data-target="{{ $index }}">
			<i class="mdi mdi-content-copy"></i> Copy
		</button>
	</div>
@endif
