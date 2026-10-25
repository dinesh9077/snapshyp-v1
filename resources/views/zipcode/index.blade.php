@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Zipcodes')
@section('header_title', 'Zipcodes')
@section('content')
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-create-order">
                            <div class="main-disolay-felx order-filter" style="margin-top: 0 !important;">
                                  <!-- Filter Section -->
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <div class="from-group">
                                        <label for="filter_city">Filter by City</label>
                                        <select name="filter_city" id="filter_city" class="form-control select2">
                                            <option value="">All Cities</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <div class="from-group">
                                        <label for="filter_state">Filter by State</label>
                                        <select name="filter_state" id="filter_state" class="form-control select2">
                                            <option value="">All States</option>
                                        </select>
                                    </div>
                                </div>
                                @if (config('permission.zipcode.add'))
                                    <div class="main-btn0main-1 ml-auto">
                                        <button class="btn-main-1" id="addZipcodeBtn"><span class="mdi mdi-plus"></span> Add
                                            Zipcode </button>
                                    </div>
                                @endif
                                
                            </div>
                            <div class="main-data-teble-1 table-responsive">
                                <table id="zipcode-datatable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> SR.No </th>
                                            <th> Pincode </th>
                                            <th> City </th>
                                            <th> State </th>
                                            <th> Action </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Zipcode Modal -->
    <div class="modal fade" id="zipcodeModal" tabindex="-1" role="dialog" aria-labelledby="zipcodeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="zipcodeModalLabel">Add Zipcode</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <form id="zipcodeForm">
                    @csrf
                    <input type="hidden" id="zipcode_id" name="zipcode_id">
                    <div class="modal-body">
                        <div class="from-group my-2">
                            <label for="pincode">Pincode <span class="text-danger">*</span></label>
                            <input type="text" autocomplete="off" name="pincode" id="pincode" class="form-control"
                                placeholder="Pincode" required maxlength="10">
                        </div>
                        <div class="from-group my-2">
                            <label for="city">City <span class="text-danger">*</span></label>
                            <input type="text" autocomplete="off" name="city" id="city" class="form-control"
                                placeholder="City" required>
                        </div>
                        <div class="from-group my-2">
                            <label for="state">State <span class="text-danger">*</span></label>
                            <input type="text" autocomplete="off" name="state" id="state" class="form-control"
                                placeholder="State" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-reset" data-dismiss="modal">Close</button>
                        <button type="submit" class="new-submit-btn" id="saveZipcodeBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        var dataTable = $('#zipcode-datatable').DataTable({
            processing: true,
            "language": {
                'loadingRecords': '&nbsp;',
                'processing': 'Loading...'
            },
            serverSide: true,
            bLengthChange: true,
            searching: true,
            bFilter: true,
            bInfo: true,
            iDisplayLength: 25,
            order: [
                [1, 'asc']
            ],
            bAutoWidth: false,
            "ajax": {
                "url": "{{ route('zipcode.ajax') }}",
                "dataType": "json",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search = $('input[type="search"]').val();
                    d.filter_city = $('#filter_city').val();
                    d.filter_state = $('#filter_state').val();
                }
            },
            "columns": [{
                    "data": "id",
                    "orderable": false
                },
                {
                    "data": "pincode"
                },
                {
                    "data": "city"
                },
                {
                    "data": "state"
                },
                {
                    "data": "action",
                    "orderable": false
                },
            ]
        });

        // Load filter options
        function loadFilterOptions() {
            $.ajax({
                url: "{{ route('zipcode.filter-options') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    var cities = Array.isArray(response && response.cities) ? response.cities : [];
                    var states = Array.isArray(response && response.states) ? response.states : [];

                    // Populate cities
                    $('#filter_city').empty().append('<option value="">All Cities</option>');
                    cities.forEach(function(city) {
                        $('#filter_city').append('<option value="' + city + '">' + city + '</option>');
                    });

                    // Populate states
                    $('#filter_state').empty().append('<option value="">All States</option>');
                    states.forEach(function(state) {
                        $('#filter_state').append('<option value="' + state + '">' + state +
                            '</option>');
                    });
                },
                error: function(xhr) {
                    console.error('Filter options failed:', xhr.status, xhr.responseText);
                }
            });
        }

        // Reload table on filter change
        $('#filter_city, #filter_state').on('change', function() {
            dataTable.ajax.reload();
        });

        // Open Add Modal
        $('#addZipcodeBtn').on('click', function() {
            $('#zipcodeModalLabel').text('Add Zipcode');
            $('#zipcodeForm')[0].reset();
            $('#zipcode_id').val('');
            $('#zipcodeModal').modal('show');
        });

        // Edit Zipcode
        $(document).on('click', '.edit-zipcode', function(e) {
            e.preventDefault();
            var id = $(this).data('id');

            $.ajax({
                url: "{{ url('zipcode/edit') }}/" + id,
                type: "GET",
                success: function(response) {
                    if (response.status === 'success') {
                        $('#zipcodeModalLabel').text('Edit Zipcode');
                        $('#zipcode_id').val(response.data.id);
                        $('#pincode').val(response.data.pincode);
                        $('#city').val(response.data.city);
                        $('#state').val(response.data.state);
                        $('#zipcodeModal').modal('show');
                    }
                }
            });
        });

        // Save Zipcode (Add/Edit)
        $('#zipcodeForm').on('submit', function(e) {
            e.preventDefault();
            var $submitButton = $('#saveZipcodeBtn');
            $submitButton.prop('disabled', true);

            var id = $('#zipcode_id').val();
            var url = id ? "{{ url('zipcode/update') }}/" + id : "{{ route('zipcode.store') }}";
            var formData = new FormData(this);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    $submitButton.prop('disabled', false);
                    if (res.status === 'success') {
                        toastrMsg(res.status, res.msg);
                        $('#zipcodeModal').modal('hide');
                        dataTable.ajax.reload();
                        loadFilterOptions(); // Reload filter options
                    } else {
                        toastrMsg(res.status, res.msg);
                    }
                },
                error: function(xhr) {
                    toastrMsg('error', 'Something went wrong. Please try again.');
                    $submitButton.prop('disabled', false);
                }
            });
        });

        // Delete Zipcode
        function deleteRecord(obj, event) {
            event.preventDefault();
            var url = $(obj).attr('href');
            if (confirm('Are you sure you want to delete this record?')) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            toastrMsg('success', response.msg);
                            dataTable.ajax.reload();
                            loadFilterOptions(); // Reload filter options
                        } else {
                            toastrMsg('error', response.msg);
                        }
                    },
                    error: function() {
                        toastrMsg('error', 'Something went wrong.');
                    }
                });
            }
        }

        // Load filter options on page load
        $(document).ready(function() {
            loadFilterOptions();
        });
    </script>
@endpush
