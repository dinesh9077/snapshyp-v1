@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - WhatsApp Settings')
@section('header_title','WhatsApp Settings')
@section('content')

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
                <!-- WhatsApp API Settings -->
                <div class="main-order-001 create-ship wpi-conf mb-3">
                    <h4 class="mb-3 new-CSM-title">WhatsApp API Configuration</h4>
                    <form id="whatsappSettingsForm">
                        @csrf
                        <div class="main-rowx-1">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="api_key">API Key <span class="text-danger">*</span></label>
                                            <input type="text" name="api_key" id="api_key" class="form-control" 
                                                   value="{{ $settings->api_key }}" 
                                                   placeholder="Enter your WhatsApp API Key" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="api_url">API URL <span class="text-danger">*</span></label>
                                            <input type="url" name="api_url" id="api_url" class="form-control" 
                                                   value="{{ $settings->api_url }}" 
                                                   placeholder="https://api.whatsapp.com/send" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="api_phone_number">WhatsApp Phone Number</label>
                                            <input type="text" name="api_phone_number" id="api_phone_number" class="form-control" 
                                                   value="{{ $settings->api_phone_number }}" 
                                                   placeholder="+91XXXXXXXXXX" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="from-group my-2">
                                            <label for="is_enabled">Enable WhatsApp Notifications</label>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="is_enabled" name="is_enabled" value="1" 
                                                       {{ $settings->is_enabled ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="is_enabled">
                                                    {{ $settings->is_enabled ? 'Enabled' : 'Disabled' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(config('permission.whatsapp_setting.edit'))
                            <div class="mt-1">
                                <button type="submit" class="new-submit-btn">Save Settings</button>
                                {{-- <button type="button" class="btn btn-secondary new-submit-btn" onclick="testConnection()">Test Connection</button> --}}
                            </div>
                        @endif
                    </form>
                </div>

                <!-- Forward Shipments Notification Types -->
                <div class="main-order-001 create-ship mb-3">
                    <h4 class="mb-3">
                        <span class="forward-ship new-CSM-title">Forward Shipment Services</span>
                    </h4>
                    
                    <div class="notification-types ">
                        @foreach($notificationTypes as $type)
                        <div class="notification-item mb-3 new-borders-ppt">
                            <div class="row align-items-center">
                                <div class="col-4" style="cursor:pointer;" data-name="{{ $type->type  }}" onclick="previewTemplate(this, event)">
                                    <div class="notification-header">
                                        <h5>
                                            {{ $type->name }}
                                            @if($type->is_recommended)
                                            <span class="badge">Recommended</span>
                                            @endif
                                        </h5>
                                        <p class="text-muted mb-0">{{ $type->message_template }}</p>
                                    </div>
                                </div>
                                
                                <div class="col-3 text-center">
                                    @if($type->is_free)
                                    <span class="badge badge-success" style="font-size: 16px;">Free</span>
                                    @else
                                    <span class="price">
                                        ₹{{ number_format($type->price, 2) }}
                                    </span>
                                    @endif
                                </div>
                                @if(config('permission.whatsapp_setting.edit'))
                                    <div class="col-3 text-center">
                                        <button type="button" class="btn-sm f-ship-edit" 
                                                onclick="editNotificationType({{ $type->id }})">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                @endif
                                @if(config('permission.whatsapp_setting.edit'))
                                    <div class="col-2 text-center">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input notification-toggle" 
                                                id="toggle_{{ $type->id }}" 
                                                data-id="{{ $type->id }}"
                                                {{ $type->is_enabled ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="toggle_{{ $type->id }}">
                                                {{ $type->is_enabled ? 'ON' : 'OFF' }}
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Notification Type Modal -->
<div class="modal fade nt-edit" id="editNotificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Notification Type</h5>
                <button type="button" class="close" data-dismiss="modal">
                   <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="editNotificationForm">
                @csrf
                <input type="hidden" id="edit_notification_id" name="notification_id">
                <div class="modal-body">
                    <div class="row">
                        {{-- <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_type">Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_type" style="height:43px;" name="type" required placeholder="e.g., order_confirmation" readonly>
                                <small class="form-text text-muted">Unique identifier (use underscore format)</small>
                            </div>
                        </div> --}}
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_name">Notification Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" style="height:43px;" id="edit_name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_message_template">Message Preview</label>
                        <textarea class="form-control" id="edit_message_template" name="message_template" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_pricing_type">Pricing Type <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="edit_pricing_type"  style="height:43px;"name="pricing_type" required>
                                    <option value="">Select Pricing Type</option>
                                    <option value="paid">Paid</option>
                                    <option value="free">Free</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="price_field_container" style="display: none;">
                                <label for="edit_price">Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="edit_is_enabled" name="is_enabled" value="1">
                                    <label class="custom-control-label" for="edit_is_enabled">Enable this notification</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="edit_is_recommended" name="is_recommended" value="1">
                                    <label class="custom-control-label" for="edit_is_recommended">Mark as Recommended</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-right border-0">
                    <button type="button" class="btn btn-light btn-reset" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-apply">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notification Type Modal -->
<div class="modal fade nt-edit" id="templatePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
             <!-- Header -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title mb-1" id="templatePreviewLabel">
                        Template Preview
                    </h5>
                    <small class="text-muted d-block">
                        Template preview for WhatsApp Notification
                    </small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="editNotificationForm">
                @csrf
                <input type="hidden" id="edit_notification_id" name="notification_id">
                <div class="modal-body">
                    <img id="templateImagePreview" src="" 
                    alt="Preview Image" 
                    style="width:100%; height:auto;">
                </div>
                <div class="modal-footer justify-content-end border-0">
                    <button type="button" class="btn btn-main-1" data-dismiss="modal">Close</button> 
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>

function previewTemplate(obj, event)
{
    event.preventDefault();
    const templateName = $(obj).attr('data-name');

    const imageUrl = "{{ url('assets/whatsapp-preview') }}/"+templateName+".png";
    $('#templateImagePreview').attr('src', imageUrl);
    $('#templatePreviewModal').modal('show');
}

$(document).ready(function() {

    window.baseUrl = "{{ url('/') }}";

    // Handle pricing type dropdown change
    $('#edit_pricing_type').on('change', function() {
        var pricingType = $(this).val();
        if(pricingType === 'paid') {
            $('#price_field_container').show();
            $('#edit_price').prop('required', true);
        } else if(pricingType === 'free') {
            $('#price_field_container').hide();
            $('#edit_price').prop('required', false);
            $('#edit_price').val('0');
        } else {
            $('#price_field_container').hide();
            $('#edit_price').prop('required', false);
        }
    });

    // Reset modal when closed
    $('#editNotificationModal').on('hidden.bs.modal', function() {
        $('#editNotificationForm')[0].reset();
        $('#price_field_container').hide();
        $('#edit_price').prop('required', false);
    });

    // Save WhatsApp Settings
    $('#whatsappSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        var data = {};
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        // Handle checkbox
        data['is_enabled'] = $('#is_enabled').is(':checked') ? 1 : 0;
        
        $.ajax({
            url: '{{ route("whatsapp.settings.update") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if(response.status === 'success') {
                    toastrMsg('success', response.message);
                } else {
                    toastrMsg('error', response.message);
                }
            },
            error: function(xhr) {
                toastrMsg('error', 'Failed to update settings');
            }
        });
    });

    // Toggle notification type
    $('.notification-toggle').on('change', function() {
        const id = $(this).data('id');
        const isChecked = $(this).is(':checked');
        
        $.ajax({
            url: window.baseUrl + '/whatsapp-settings/notification-type/' + id + '/toggle',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.status === 'success') {
                    toastrMsg('success', response.message);
                    $('label[for="toggle_' + id + '"]').text(response.is_enabled ? 'ON' : 'OFF');
                } else {
                    toastrMsg('error', response.message);
                }
            },
            error: function(xhr) {
                toastrMsg('error', 'Failed to toggle notification');
            }
        });
    });

    // Edit notification type form submit
    $('#editNotificationForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#edit_notification_id').val();
        
        var formData = $(this).serializeArray();
        var data = {};
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        // Handle pricing type
        var pricingType = $('#edit_pricing_type').val();
        data['is_free'] = (pricingType === 'free') ? 1 : 0;
        data['is_enabled'] = $('#edit_is_enabled').is(':checked') ? 1 : 0;
        data['is_recommended'] = $('#edit_is_recommended').is(':checked') ? 1 : 0;
        
        // Set price to 0 if free
        if(pricingType === 'free') {
            data['price'] = 0;
        }
        
        $.ajax({
            url: window.baseUrl + '/whatsapp-settings/notification-type/' + id,
            method: 'POST',
            data: data,
            success: function(response) {
                if(response.status === 'success') {
                    toastrMsg('success', response.message);
                    $('#editNotificationModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastrMsg('error', response.message);
                }
            },
            error: function(xhr) {
                var errorMsg = 'Failed to update notification type';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastrMsg('error', errorMsg);
            }
        });
    });
});

// Edit notification type
function editNotificationType(id) {
    $.ajax({
        url: window.baseUrl + '/whatsapp-settings/notification-type/' + id,
        method: 'GET',
        success: function(data) {
            // Reset form first
            $('#editNotificationForm')[0].reset();
            
            // Set form values
            $('#edit_notification_id').val(data.id);
            $('#edit_type').val(data.type);
            $('#edit_name').val(data.name);
            $('#edit_message_template').val(data.message_template);
            $('#edit_is_enabled').prop('checked', data.is_enabled == 1);
            $('#edit_is_recommended').prop('checked', data.is_recommended == 1);
            
            // Set pricing type and trigger change event
            if(data.is_free == 1) {
                $('#edit_pricing_type').val('free').trigger('change');
            } else {
                $('#edit_price').val(data.price);
                $('#edit_pricing_type').val('paid').trigger('change');
            }
            
            // Show modal
            $('#editNotificationModal').modal('show');
        },
        error: function(xhr) {
            toastrMsg('error', 'Failed to load notification details');
        }
    });
}

// Test WhatsApp connection
function testConnection() {
    const phone = prompt('Enter phone number to test (with country code):');
    if(!phone) return;
    
    $.ajax({
        url: '{{ route("whatsapp.test-connection") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            phone_number: phone
        },
        success: function(response) {
            if(response.status === 'success') {
                toastrMsg('success', response.message);
            } else {
                toastrMsg('error', response.message);
            }
        },
        error: function(xhr) {
            toastrMsg('error', 'Failed to test connection');
        }
    });
}
</script>
@endpush
