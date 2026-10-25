@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Shipping Weight')
@section('header_title', 'Shipping Weight')

@section('content')
    <style>
        button.d-002 {
            padding: 6px 15px;
            border-radius: 4px;
            font-size: 20px;
            font-weight: 700;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px !important;
            font-size: 14px !important;
            padding: 7px 15px;
        }

        @media(max-width:1600px) {
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 1 !important;
                padding: 11px 15px !important;
                font-size: 12px !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 35px !important;
            }
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">

                        <!-- Header -->
                        <div
                            class="mb-3 d-flex justify-content-between align-items-start flex-wrap r-gap order-filter">
                            <div class="">
                                <label for="filterShippingCompany">Shipping Company</label>
                                <select id="filterShippingCompany" class="form-select form-select-sm"
                                    style="min-width: 240px;">
                                    <option value="">All Companies</option>
                                    @foreach ($shippingCompanies as $company)
                                        <option value="{{ $company->id }}" {{ (string) request('shipping_company_id') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <a href="javascript:void(0);" class="btn btn-main-1" id="btnAddWeight">
                                    <i class="mdi mdi-plus"></i> Add Shipping Weight
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="main-data-teble-1 table-responsive">
                            <table id="shipping-weight-datatable" class="" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Sr.no</th>
                                        <th>Shipping Company</th>
                                        <th>Weight</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Modal: Create / Edit -->
    <div class="modal fade" id="weightModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="weightForm">@csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="weightModalTitle">Add Shipping Weight</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="weight_id" name="weight_id">

                        <div class="mb-3">
                            <label>Shipping Company <span class="text-danger">*</span></label>
                            <select id="shipping_company_id" name="shipping_company_id" class="form-control select2"
                                required>
                                <option value="">Select Shipping Company</option>
                                @foreach ($shippingCompanies as $shippingCompany)
                                    <option value="{{ $shippingCompany->id }}" {{ (string) request('shipping_company_id') === (string) $shippingCompany->id ? 'selected' : '' }}>{{ $shippingCompany->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger error-text shipping_company_id_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Weight (kg) <span class="text-danger">*</span></label>
                            <input type="text" name="weight" id="weight" class="form-control"
                                placeholder="Enter weight in kg (e.g., 0.5, 1.25, 5)" required
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
                            <span class="text-danger error-text weight_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="text-danger error-text status_error"></span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-reset" data-dismiss="modal">Cancel</button>
                        <button type="submit" id="weightSaveBtn" class="new-submit-btn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection


@push('js')
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {

            // --- Initialize Select2 
            $('.select2').select2({
                dropdownParent: $('#weightModal')
            });

            // --- DataTable
            var table = $('#shipping-weight-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('rate.shipping-weight.ajax') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.shipping_company_id = $('#filterShippingCompany').val();
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'shipping_company'
                    },
                    {
                        data: 'weight'
                    },
                    {
                        data: 'status',
                        orderable: false
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                pageLength: 25,
                order: [
                    [1, 'asc'],
                    [2, 'asc']
                ]
            });

            // --- Filter by shipping company
            $('#filterShippingCompany').on('change', function() {
                table.ajax.reload();
            });

            // --- Add new
            $('#btnAddWeight').on('click', function() {
                $('#weightModalTitle').text('Add Shipping Weight');
                $('#weightForm')[0].reset();
                $('#weight_id').val('');
                $('#shipping_company_id').val($('#filterShippingCompany').val() || null).trigger('change');
                $('.error-text').text('');
                $('#weightModal').modal('show');
            });

            // --- Edit Weight
            $(document).on('click', '.btn-edit-weight', function() {
                let id = $(this).data('id');
                $.get("{{ url('rate/shipping-weight/edit') }}/" + id, function(res) {
                    if (res.status === 'success') {
                        let w = res.object;
                        $('#weight_id').val(w.id);
                        $('#shipping_company_id').val(w.shipping_company_id).trigger('change');
                        $('#weight').val(w.weight);
                        $('#status').val(w.status);
                        $('.error-text').text('');
                        $('#weightModalTitle').text('Edit Shipping Weight');
                        $('#weightModal').modal('show');
                    } else {
                        Swal.fire('Error', 'Failed to fetch weight data', 'error');
                    }
                });
            });

            // --- Save Weight
            $('#weightForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#weight_id').val();
                let url = id ? "{{ url('rate/shipping-weight/update') }}/" + id :
                    "{{ route('rate.shipping-weight.store') }}";

                $('.error-text').text('');
                $('#weightSaveBtn').prop('disabled', true);

                $.post(url, $(this).serialize(), function(res) {
                    if (res.status === 'success') {
                        $('#weightModal').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Success', res.msg, 'success');
                    } else if (res.status === 'validation') {
                        $.each(res.errors, function(key, value) {
                            $('.' + key + '_error').text(value[0]);
                        });
                    } else {
                        let msg = res.msg || 'Something went wrong';
                        Swal.fire('Error', msg, 'error');
                    }
                }).fail(function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        if (errors) {
                            $.each(errors, function(key, value) {
                                $('.' + key + '_error').text(value[0]);
                            });
                        } else {
                            Swal.fire('Error', xhr.responseJSON.msg || 'Validation error', 'error');
                        }
                    } else {
                        Swal.fire('Error', 'Something went wrong', 'error');
                    }
                }).always(() => $('#weightSaveBtn').prop('disabled', false));
            });

// --- Delete Weight
            $(document).on('click', '.btn-delete-weight', function() {
                let id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this shipping weight?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ url('rate/shipping-weight/delete') }}/" + id, {
                            _token: "{{ csrf_token() }}"
                        }, function(res) {
                            if (res.status === 'success') {
                                table.ajax.reload(null, false);
                                Swal.fire('Deleted!', res.msg, 'success');
                            } else {
                                Swal.fire('Error', res.msg || 'Failed to delete', 'error');
                            }
                        }).fail(function(xhr) {
                            let msg = 'Failed to delete';
                            if (xhr.responseJSON && xhr.responseJSON.msg) {
                                msg = xhr.responseJSON.msg;
                            }
                            Swal.fire('Error', msg, 'error');
                        });
                    }
                });
            });
        });
    </script>
@endpush
