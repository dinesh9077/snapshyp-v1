@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Courier Commission')
@section('header_title', 'Courier Commission')

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

                                <form method="POST" action="{{ route('courier.commission.store') }}" id="commissionForm">
                                    @csrf
                                    <div id="removed-commission-ids"></div>
                                    <div id="courier-commission-list">
                                        @php
                                            $commissions = old('commissions', $commissions->toArray() ?? []);
                                            if (empty($commissions)) {
                                                $commissions = [
                                                    [
                                                        'id' => '',
                                                        'shipping_company' => '',
                                                        'courier_id' => '',
                                                        'courier_name' => '',
                                                        'type' => '',
                                                        'value' => '',
                                                    ],
                                                ];
                                            }
                                        @endphp

                                        @foreach ($commissions as $i => $commission)
                                            <div class="row mb-3 courier-commission-item">
                                                <div class="col">
                                                    <select name="commissions[{{ $i }}][shipping_company]"
                                                        class="form-control" required>
                                                        <option value="">Select Shipping Company</option>
                                                        @foreach ($shippingCompanies as $shippingCompany)
                                                            <option value="{{ $shippingCompany->id }}"
                                                                {{ ($commission['shipping_company'] ?? '') == $shippingCompany->id ? 'selected' : '' }}>
                                                                {{ $shippingCompany->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <input type="hidden" name="commissions[{{ $i }}][id]"
                                                    value="{{ $commission['id'] ?? '' }}">
                                                <div class="col">
                                                    <input type="text"
                                                        name="commissions[{{ $i }}][courier_id]"
                                                        class="form-control" placeholder="Courier ID"
                                                        value="{{ $commission['courier_id'] ?? '' }}">
                                                </div>
                                                <div class="col">
                                                    <input type="text"
                                                        name="commissions[{{ $i }}][courier_name]"
                                                        class="form-control" placeholder="Courier Name"
                                                        value="{{ $commission['courier_name'] ?? '' }}" required>
                                                </div>
                                                <div class="col">
                                                    <select name="commissions[{{ $i }}][type]"
                                                        class="form-control" required>
                                                        <option value="fix"
                                                            {{ ($commission['type'] ?? '') == 'fix' ? 'selected' : '' }}>Fix
                                                        </option>
                                                        <option value="percentage"
                                                            {{ ($commission['type'] ?? '') == 'percentage' ? 'selected' : '' }}>
                                                            Percentage</option>
                                                    </select>
                                                </div>
                                                <div class="col">
                                                    <input type="number" step="0.01"
                                                        name="commissions[{{ $i }}][value]" class="form-control"
                                                        placeholder="Value" value="{{ $commission['value'] ?? '' }}"
                                                        required>
                                                </div>
                                                <div class="col-">
                                                    @if ($loop->last)
                                                        <!-- Only last row gets Add button -->
                                                        <button type="button" class="btn  new-height-btn-plus add-row"
                                                            style="height: 43px; width : 43px;">+</button>
                                                    @else
                                                        <!-- All other rows get only Remove button -->
                                                        <button type="button" class="btn  new-height-btn-plus remove-row"
                                                            style="height: 43px; width : 43px; background: #FFE2E2;    color: #FF0000;">-</button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="text-right">
                                        {{-- <button type="submit" class="new-submit-btn">Save</button> --}}
                                        <button type="button" class="new-submit-btn" id="submitWithPasskey">Save</button>
                                    </div> 
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Passkey Modal -->
  <div class="modal fade" id="passkeyModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Enter Passkey</h5>
          <button type="button" class="close" data-dismiss="modal">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <div class="modal-body">
          <input type="password" id="passkeyInput" class="form-control" placeholder="Enter your passkey">
          <small id="passkeyError" class="text-danger mt-1 d-block" style="display:none;"></small>
        </div>

        <div class="modal-footer"> 
          <button type="button" class="new-submit-btn" id="verifyPasskeyBtn">Verify & Submit</button>
        </div> 
      </div>
    </div>
  </div>

@endsection
@push('js')
    <script>

      $(document).ready(function() {

          // When click Save => show passkey modal
          $('#submitWithPasskey').on('click', function() {
              $('#passkeyInput').val('');
              $('#passkeyError').hide();
              $('#passkeyModal').modal('show');
          });

          // When verify button clicked
          $('#verifyPasskeyBtn').on('click', function() {
              let passkey = $('#passkeyInput').val();

              if (passkey.trim() === '') {
                  $('#passkeyError').text('Passkey is required.').show();
                  return;
              }

              $.ajax({
                  url: "{{ route('verify.passkey') }}",
                  method: 'POST',
                  data: { passkey: passkey, _token: "{{ csrf_token() }}" },
                  success: function(res) {
                      if (res.status === 'success') {
                          $('#passkeyModal').modal('hide');
                          $('#commissionForm').submit(); // final submit
                      } else {
                          $('#passkeyError').text('Invalid passkey.').show();
                      }
                  },
                  error: function() {
                      $('#passkeyError').text('Something went wrong.').show();
                  }
              });

          });
      });

        document.addEventListener('DOMContentLoaded', function() {
            const list = document.getElementById('courier-commission-list');
            const removedIdsWrap = document.getElementById('removed-commission-ids');

            function getRowCount() {
                return list.querySelectorAll('.courier-commission-item').length;
            }

            // Rebuild input/select names to be commissions[0][field], commissions[1][field], ...
            function reindexRows() {
                list.querySelectorAll('.courier-commission-item').forEach((row, idx) => {
                    row.querySelectorAll('input, select, textarea').forEach(el => {
                        const name = el.getAttribute('name');
                        if (!name) return;
                        // replace commissions[<num>][field] => commissions[idx][field]
                        const newName = name.replace(/^commissions\[\d+\]\[(.+)\]$/,
                            `commissions[${idx}][$1]`);
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
                      <div class="col">
                        <select name="commissions[${idx}][shipping_company]" class="form-control" required>
                          <option value="">Select Shipping Company</option>
                              @foreach ($shippingCompanies as $shippingCompany)
                                  <option value="{{ $shippingCompany->id }}" >{{ $shippingCompany->name }}</option>
                              @endforeach 
                        </select>
                      </div>
                      <input type="hidden" name="commissions[${idx}][id]" value="">
                      <div class="col">
                        <input type="text" name="commissions[${idx}][courier_id]" class="form-control" placeholder="Courier ID" >
                      </div>
                      <div class="col">
                        <input type="text" name="commissions[${idx}][courier_name]" class="form-control" placeholder="Courier Name" required>
                      </div>
                      <div class="col">
                        <select name="commissions[${idx}][type]" class="form-control" required>
                          <option value="fix">Fix</option>
                          <option value="percentage">Percentage</option>
                        </select>
                      </div>
                      <div class="col">
                        <input type="number" step="0.01" name="commissions[${idx}][value]" class="form-control" placeholder="Value" value="0" required>
                      </div>
                      <div class="col-">
                        <button type="button" class="btn new-height-btn-plus remove-row" style="height: 43px; width : 43px; background: #FFE2E2;    color: #FF0000;">-</button>
                      </div>
                    `;
                    list.appendChild(newRow);
                    rowIdx++;
                    return;
                }

                // Remove row
                if (removeBtn) 
                {
                    const item = removeBtn.closest('.courier-commission-item');
                    if (!item) return;

                    // optional: prevent removing the last remaining row
                    if (getRowCount() === 1) {
                        alert('At least one commission row is required.');
                        return;
                    }

                    // 🔥 confirmation before delete
                    const sure = confirm("Are you sure you want to remove this row?");
                    if (!sure) {
                        return; // stop if user clicks Cancel
                    }

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
