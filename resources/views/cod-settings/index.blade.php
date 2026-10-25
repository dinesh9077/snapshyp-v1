@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - COD Settings')
@section('header_title','COD Settings')
@section('content')
<style>
    .dataTable.no-footer tr th {
  font-weight: 600;
}

.dataTable.no-footer tr td:first-child{
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

.dataTable.no-footer tr td:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}
.table-striped tbody tr:nth-of-type(odd){
        background: #f9f9f9;
}
</style>
<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1 cod-settings-page">
                <div class="main-order-001">
                    <div class="main-filter-weight">
                        <div class="inner-page-heading">
                           <div class="row row-re w-100 mb-2">
                                <div class="col-lg-6">
                                    <h4 class="new-CSM-title">COD Settings Management</h4>
                                    <p class="mb-0">Configure T+1, T+2, T+3, T+4 day settings with percentages and GST</p>
                                </div>
                                {{-- <div class="col-lg-3 col-sm-6">
                                    <form method="GET" action="{{ route('cod-settings.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search settings...">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="submit">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div> --}} 
                                @if(config('permission.cod_setting.add'))
                                    <div class="col-lg-6 text-right p-0">
                                        <button type="button" class="btn-main-1 mr-sm-2 mb-2 mb-sm-0" data-toggle="modal" data-target="#holidayModal">
                                            <i class="mdi mdi-calendar"></i> Manage Holidays
                                        </button>
                                        <button type="button" class="btn-main-1 mb-2 mb-sm-0" data-toggle="modal" data-target="#termModal">
                                            <i class="mdi mdi-cog"></i> Terms And Condition
                                        </button>
                                        {{-- <button type="button" class="btn btn-primary" id="addNewSetting">
                                            <i class="mdi mdi-plus"></i> Add New Setting
                                        </button> --}}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    
                    <div class="ordr-main-001">
                        <div class="main-data-teble-1 table-responsive">
                            <table class="table table-striped dataTable no-footer new-table-data" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Plan Name</th>
                                        <th>Days</th>
                                        <th>COD %</th>
                                        <th>GST %</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        {{-- <th>Actions</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($codSettings as $index => $setting)
                                        <tr>
                                            <td>{{ $codSettings->firstItem() + $index }}</td>
                                            <td>{{ $setting->plan_name }}</td>
                                            <td>{{ $setting->days }}</td>
                                            <td>{{ $setting->percentage }}%</td>
                                            <td>{{ $setting->gst_percentage }}%</td>
                                            <td>
                                                @if($setting->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                                @if($setting->is_default)
                                                    <span class="badge badge-primary">Default</span>
                                                @endif
                                            </td>
                                            <td>{{ $setting->created_at->format('d M Y') }}</td>
                                            {{-- <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                                            data-id="{{ $setting->id }}" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    @if($setting->id > 4)
                                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                                data-id="{{ $setting->id }}" title="Delete">
                                                            <i class="mdi mdi-trash-can-outline"></i>
                                                        </button>
                                                        @endif
                                                </div>
                                            </td> --}}
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No COD settings found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $codSettings->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="codSettingModal" tabindex="-1" role="dialog" aria-labelledby="codSettingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="codSettingModalLabel">Add COD Setting</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="codSettingForm">
                @csrf
                <input type="hidden" id="setting_id" name="setting_id">
                <input type="hidden" id="form_method" name="_method" value="POST">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="plan_name">Plan Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="plan_name" name="plan_name" placeholder="e.g., T+1 Day" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="days">Days <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="days" name="days" min="1" max="30" placeholder="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="percentage">COD Percentage <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="percentage" name="percentage" step="0.01" min="0" max="100" placeholder="2.50" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gst_percentage">GST Percentage <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="gst_percentage" name="gst_percentage" step="0.01" min="0" max="100" value="18.00" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                    <label class="form-check-label" for="is_default">
                                        Set as Default
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description for this COD setting..."></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>
  
<!-- Add/Edit Term Modal -->
<div class="modal fade" id="termModal" tabindex="-1" role="dialog" aria-labelledby="termModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="termModalLabel">Terms And Conditions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="termForm">
                @csrf 
                <input type="hidden" id="term_method" name="_method" value="POST"> 
                <div class="modal-body"> 
                    <div class="form-group">
                        <label for="term_description">Terms And Condition <span class="text-danger">*</span></label>
                        <textarea class="form-control tems-des-list" id="cod_term" name="cod_term" rows="8" placeholder="Enter Terms And Condition" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div> 
                </div>
                
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-reset" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-apply" id="saveTermBtn" style="font-size : 14px;">Save Term</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" role="dialog" aria-labelledby="holidayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="holidayModalLabel">COD Remittance Holidays</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Add Holiday Form -->
                <form id="holidayForm" class="mb-4">
                    @csrf
                    <div class="row">
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" name="description" placeholder="Description">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4">
                           <label class="d-lg-block d-none">&nbsp;</label>
                           <button type="submit" class="new-submit-btn btn-block">Add</button>
                        </div>
                    </div>
                </form>

                <!-- Holidays List -->
                <div class="table-responsive">
                    <table class="dataTable no-footer">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="holidayTableBody">
                            @forelse($holidays as $holiday)
                                <tr id="holiday-row-{{ $holiday->id }}">
                                    <td>{{ $holiday->date ? \Carbon\Carbon::parse($holiday->date)->format('d M Y') : '' }}</td>
                                    <td>{{ $holiday->description }}</td>
                                    <td>
                                        <button type="button" class="btn btn-icon waves-effect waves-light action-icon delete-holiday" data-id="{{ $holiday->id }}">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="no-holiday-row">
                                    <td colspan="3" class="text-center">No holidays found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
$(document).ready(function() {
    // Load existing terms when modal opens
    $('#termModal').on('show.bs.modal', function() {
        loadExistingTerms();
    });
    
    // Save Terms Form
    $('#termForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('cod-settings.terms.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === "success") {
                    $('#termModal').modal('hide');
                    toastrMsg(response.status, response.msg);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $('.form-control').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                    
                    $.each(errors, function(key, value) {
                        $('#' + key).addClass('is-invalid');
                        $('#' + key).siblings('.invalid-feedback').text(value[0]);
                    });
                } else {
                    toastrMsg('error', 'Failed to save terms and conditions'); 
                }
            }
        });
    });
    
    // Load existing terms
    function loadExistingTerms() {
        $.get("{{ route('cod-settings.terms.get') }}", function(response) {
            if (response.status === "success") {
                $('#cod_term').val(response.data);
            }
        }).fail(function() { 
            console.log('Failed to load existing terms');
        });
    }

    // Add New Setting
    $('#addNewSetting').click(function() {
        resetForm();
        $('#codSettingModalLabel').text('Add COD Setting');
        $('#saveBtn').text('Save Setting');
        $('#form_method').val('POST');
        $('#codSettingModal').modal('show');
    });

    // Edit Setting
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        
        $.get("{{ url('cod-settings') }}/" + id + "/edit", function(data) {
            resetForm();
            $('#codSettingModalLabel').text('Edit COD Setting');
            $('#saveBtn').text('Update Setting');
            $('#form_method').val('PUT');
            $('#setting_id').val(data.id);
            $('#plan_name').val(data.plan_name);
            $('#days').val(data.days);
            $('#percentage').val(data.percentage);
            $('#gst_percentage').val(data.gst_percentage);
            $('#description').val(data.description);
            $('#is_active').prop('checked', data.is_active);
            $('#is_default').prop('checked', data.is_default);
            $('#codSettingModal').modal('show');
        }).fail(function() { 
            toastrMsg('error', 'Failed to load setting data');  
        });
    });

    // Save Form
    $('#codSettingForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var url = $('#form_method').val() === 'PUT' ? 
            "{{ url('cod-settings') }}/" + $('#setting_id').val() : 
            "{{ route('cod-settings.store') }}";
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                 if (response.status === "success") {
                    $('#codSettingModal').modal('hide');
                    location.reload(); // Reload page to show updated data
                    toastrMsg(response.status, response.msg);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $('.form-control').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                    
                    $.each(errors, function(key, value) {
                        $('#' + key).addClass('is-invalid');
                        $('#' + key).siblings('.invalid-feedback').text(value[0]);
                    });
                } else { 
                    toastrMsg('error', 'Something went wrong!');
                }
            }
        });
    });

    // Delete Setting
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this COD setting?')) {
            $.ajax({
                url: "{{ url('cod-settings') }}/" + id,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status === "success") {
                        location.reload(); 
                    }
                },
                error: function() {
                    toastrMsg('error', 'Failed to delete setting'); 
                }
            });
        }
    });

    // Reset Form
    function resetForm() {
        $('#codSettingForm')[0].reset();
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#setting_id').val('');
        $('#is_active').prop('checked', true);
        $('#is_default').prop('checked', false);
        $('#gst_percentage').val('18.00');
    }
    // Holiday Management
    $('#holidayForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('cod-settings.holiday.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === "success") {
                    toastrMsg('success', response.msg);
                    $('#holidayForm')[0].reset();
                    
                    // Add row to table (reload page is easiest, but let's append for smoothness)
                    var date = new Date(response.data.date);
                    var formattedDate = date.getDate() + ' ' + date.toLocaleString('default', { month: 'short' }) + ' ' + date.getFullYear();
                    
                    var newRow = `
                        <tr id="holiday-row-${response.data.id}">
                            <td>${formattedDate}</td>
                            <td>${response.data.description || ''}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger delete-holiday" data-id="${response.data.id}">
                                    <i class="mdi mdi-trash-can-outline"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    if($('#no-holiday-row').length) {
                        $('#no-holiday-row').remove();
                    }
                    
                    $('#holidayTableBody').prepend(newRow);
                }
            },
            error: function(xhr) {
               toastrMsg('error', xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : 'Failed to add holiday');
            }
        });
    });

    $(document).on('click', '.delete-holiday', function() {
        if(!confirm('Are you sure?')) return;
        
        var id = $(this).data('id');
        var row = $('#holiday-row-' + id);
        
        $.ajax({
            url: "{{ url('cod-settings/holiday') }}/" + id,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                if(response.status === 'success') {
                    row.remove();
                    toastrMsg('success', response.msg);
                }
            }
        });
    });
});
</script>
@endpush

