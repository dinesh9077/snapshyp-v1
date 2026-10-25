@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Client Courier Commission')
@section('header_title', 'Client Courier Commission')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card"> 
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            <form method="POST" action="{{ route('users.courier.commission.store') }}">
                                @csrf
								<input type="hidden" name="user_id" value="{{ $userId }}">
                                <div id="removed-commission-ids"></div>
                                <div id="courier-commission-list"> 
                                    @foreach($commissions as $i => $commission) 
										@php
											$userCommision = $commission->userCommissions->first();
										@endphp
										<div class="row mb-3 courier-commission-item">
											<div class="col">
												<select name="commissions[{{ $i }}][shipping_company]" class="form-control" required>
													<option value="">Select Shipping Company</option>
													@foreach($shippingCompanies as $shippingCompany)
													  <option value="{{ $shippingCompany->id }}" {{ ($commission->shipping_company ?? '') == $shippingCompany->id ? 'selected' : '' }}>{{ $shippingCompany->name }}</option>
													@endforeach 
												</select>
											</div>
											<input type="hidden" name="commissions[{{ $i }}][id]" value="{{ $commission->id ?? '' }}">
											<div class="col">
												<input type="text" name="commissions[{{ $i }}][courier_id]" class="form-control" placeholder="Courier ID" value="{{ $commission->courier_id ?? '' }}">
											</div>
											<div class="col">
												<input type="text" name="commissions[{{ $i }}][courier_name]" class="form-control" placeholder="Courier Name" value="{{ $commission->courier_name ?? '' }}" required>
											</div>
											<div class="col">
												<select name="commissions[{{ $i }}][type]" class="form-control" required>
													<option value="fix" {{ (($userCommision->type ?? $commission->type ?? '') == 'fix') ? 'selected' : '' }}>Fix</option>
													<option value="percentage" {{ (($userCommision->type ?? $commission->type ?? '') == 'percentage') ? 'selected' : '' }}>Percentage</option>
												</select> 
											</div>
											<div class="col">
												<input type="number" step="0.01" name="commissions[{{ $i }}][value]" class="form-control" placeholder="Value" value="{{ $userCommision->value ?? $commission->value ?? '' }}" required>
											</div>
                                            <div class="col-auto d-flex align-items-center">
                                                <button type="button" class="btn new-height-btn-plus remove-row"
                                                    style="height: 43px; width: 43px; background: #FFE2E2; color: #FF0000;">-</button>
                                            </div>
										</div>
                                    @endforeach
                                </div>

                                <button type="submit" style="float: right;" class="new-submit-btn mt-3">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const list = document.getElementById('courier-commission-list');
  const removedIdsWrap = document.getElementById('removed-commission-ids');

  function getRowCount(){
    return list.querySelectorAll('.courier-commission-item').length;
  }

  // Rebuild input/select names to be commissions[0][field], commissions[1][field], ...
  function reindexRows(){
    list.querySelectorAll('.courier-commission-item').forEach((row, idx) => {
      row.querySelectorAll('input, select, textarea').forEach(el => {
        const name = el.getAttribute('name');
        if (!name) return;
        // replace commissions[<num>][field] => commissions[idx][field]
        const newName = name.replace(/^commissions\[\d+\]\[(.+)\]$/, `commissions[${idx}][$1]`);
        el.setAttribute('name', newName);
      });
    });
  }

  // initial row count
  let rowIdx = getRowCount();

  // delegated click handler (works for dynamically added elements too)
  document.addEventListener('click', function(e) {
    const addBtn = e.target.closest('.add-row');
    const removeBtn = e.target.closest('.remove-row');

    // Add new row
    if (addBtn) {
      const idx = getRowCount();
      const newRow = document.createElement('div');
      newRow.className = 'row mb-3 courier-commission-item';
      newRow.innerHTML = `
        <div class="col-md-2">
          <select name="commissions[${idx}][shipping_company]" class="form-control" required>
            <option value="">Select Shipping Company</option>
                @foreach($shippingCompanies as $shippingCompany)
                    <option value="{{ $shippingCompany->id }}" >{{ $shippingCompany->name }}</option>
                @endforeach 
          </select>
        </div>
        <input type="hidden" name="commissions[${idx}][id]" value="">
        <div class="col-md-2">
          <input type="text" name="commissions[${idx}][courier_id]" class="form-control" placeholder="Courier ID" >
        </div>
        <div class="col-md-2">
          <input type="text" name="commissions[${idx}][courier_name]" class="form-control" placeholder="Courier Name" required>
        </div>
        <div class="col-md-2">
          <select name="commissions[${idx}][type]" class="form-control" required>
            <option value="fix">Fix</option>
            <option value="percentage">Percentage</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="number" step="0.01" name="commissions[${idx}][value]" class="form-control" placeholder="Value" value="0" required>
        </div>
        <div class="col-md-2 d-flex align-items-center">
          <button type="button" class="btn new-height-btn-plus remove-row" style="height: 43px; width: 43px; background: #FFE2E2; color: #FF0000;">-</button>
        </div>
      `;
      list.appendChild(newRow);
      rowIdx++;
      return;
    }

    // Remove row
    if (removeBtn) {
      const item = removeBtn.closest('.courier-commission-item');
      if (!item) return;

      const idInput = item.querySelector('input[name$="[id]"]');
      if (idInput && idInput.value) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'removed_commission_ids[]';
        hidden.value = idInput.value;
        removedIdsWrap.appendChild(hidden);
      }

      item.remove();
      reindexRows();
      rowIdx = getRowCount();
      return;
    }
  });
});
</script>
@endpush
