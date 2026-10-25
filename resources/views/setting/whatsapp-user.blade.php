@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - WhatsApp Services')
@section('header_title','WhatsApp Services')
@section('content')

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            <div class="main-order-page-1">
                
                <!-- User Services Summary -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body wp-card new-CSM-title">
                                <h4 class="mb-3">
                                    <i class="mdi mdi-chart-box-outline "></i> Your WhatsApp Services
                                </h4>
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="p-3 bg-1 rounded">
                                            <h2 class="mb-2">{{ $totalPurchased }}</h2>
                                            <p class="mb-0">Total Services Purchased</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-2 rounded">
                                            <h2 class="mb-2">{{ $activePurchased }}</h2>
                                            <p class="mb-0">Active Services</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-3 rounded">
                                            <h2 class="mb-2">{{ $availableServices }}</h2>
                                            <p class="mb-0">Available to Purchase</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Forward Shipments Notification Types -->
                <div class="main-order-001 create-ship forward-ship">
                    <h4 class="mb-3">
                        <span class="new-CSM-title">FORWARD SHIPMENT SERVICES</span>
                    </h4>
                    
                    <div class="notification-types">
                        @foreach($notificationTypes as $type)
                        <div class="notification-item mb-3 p-3 border rounded {{ $type->isPurchased ? 'bg-light' : '' }}">
                            <div class="row align-items-center">
                                <div class="col-lg-6 col-md-6" style="cursor:pointer;" data-name="{{ $type->type  }}" onclick="previewTemplate(this, event)">
                                    <div class="notification-header">
                                        <h5 class="mb-1">
                                            {{ $type->name }}
                                            @if($type->is_recommended)
                                            <span class="badge badge-info">Recommended</span>
                                            @endif
                                            @if($type->isPurchased)
                                            <span class="badge badge-success">Purchased</span>
                                            @endif
                                        </h5>
                                        <p class="text-muted mb-0 small">{{ $type->message_template }}</p>
                                    </div>
                                </div>
                                
                                <div class="col-lg-3 col-md-3 text-center">
                                    @if($type->is_free)
                                    <span class="badge badge-success" style="font-size: 16px;">Free</span>
                                    @else
                                    <span class="text-dark" style="font-size: 18px; font-weight: 500;">
                                        ₹{{ number_format($type->price, 2) }}
                                    </span>
                                    @endif
                                </div>
                                
                                <div class="col-lg-3 col-md-3 text-center">
                                    @if($type->isPurchased)
                                        <button type="button" class="btn btn-sm check-service-btn" disabled>
                                            <i class="mdi mdi-check-circle"></i> Added
                                        </button>
                                        <button type="button" class="btn btn-sm delete-service-btn" 
                                                onclick="removeService({{ $type->id }})">
                                            <i class="mdi mdi-trash-can-outline"></i> Remove
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm add-service-btn" 
                                                onclick="addService({{ $type->id }})">
                                            <i class="mdi mdi-plus-circle"></i> Add Service
                                        </button>
                                    @endif
                                </div>
                                
                                {{-- <div class="col-lg-2 col-md-2 text-center">
                                    @if($type->isPurchased)
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input service-toggle" 
                                               id="toggle_{{ $type->id }}" 
                                               data-id="{{ $type->id }}"
                                               {{ $type->isActive ? 'checked' : '' }}>
                                        <label class="custom-control-label text-secondary" for="toggle_{{ $type->id }}">
                                            {{ $type->isActive ? 'ON' : 'OFF' }}
                                        </label>
                                    </div>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </div> --}}
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
                    <span>&times;</span>
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

    // Toggle service active status
    $('.service-toggle').on('change', function() {
        const id = $(this).data('id');
        const isChecked = $(this).is(':checked');
        
        $.ajax({
            url: window.baseUrl + '/user/whatsapp-services/' + id + '/toggle',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.status === 'success') {
                    toastrMsg('success', response.message);
                    $('label[for="toggle_' + id + '"]').text(response.is_active ? 'ON' : 'OFF');
                } else {
                    toastrMsg('error', response.message);
                }
            },
            error: function(xhr) {
                toastrMsg('error', 'Failed to toggle service');
            }
        });
    });
});

// Add service
function addService(id) {
    if(!confirm('Are you sure you want to add this WhatsApp service?')) {
        return;
    }
    
    $.ajax({
        url: window.baseUrl + '/user/whatsapp-services/add',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            notification_type_id: id
        },
        success: function(response) {
            if(response.status === 'success') {
                toastrMsg('success', response.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastrMsg('error', response.message);
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to add service';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastrMsg('error', errorMsg);
        }
    });
}

// Remove service
function removeService(id) {
    if(!confirm('Are you sure you want to remove this WhatsApp service?')) {
        return;
    }
    
    $.ajax({
        url: window.baseUrl + '/user/whatsapp-services/' + id + '/remove',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if(response.status === 'success') {
                toastrMsg('success', response.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastrMsg('error', response.message);
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to remove service';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastrMsg('error', errorMsg);
        }
    });
}
</script>
@endpush

