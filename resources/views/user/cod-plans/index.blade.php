@extends('layouts.backend.app')
@section('title', config('setting.company_name').' - Early COD Remittance Plan')
@section('header_title', 'Early COD Remittance Plan')
@section('content')

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="main-order-page-1 cod-remittance-plan">
                <div >
                    <div class="main-filter-weight">
                        <div class="inner-page-heading w-100">
                            <div class="row row-re justify-content-between w-100 mb-4" style="margin:0;">
                                <div class="col-xl-9 col-lg-8 col-sm-6">
                                    <h4 class="new-CSM-title">Early COD Remittance Plan</h4>
                                    <p>Choose the best payment plan for your business</p>
                                </div>
                                <div class="col-xl-3 col-lg-4 col-sm-6 pr-0 text-right">
                                    <div class="current-plan-info block-bg">
                                        <h6 class="mt-0">Current Plan</h6>
                                        <div id="currentPlanDisplay">
                                            @if($currentPlan && $user->cod_plan_active)
                                                @php
                                                    $activePlan = $codPlans->where('id', $currentPlan)->first();
                                                @endphp
                                                @if($activePlan)
                                                    <span class="badge badge-success">{{ $activePlan->plan_name }}</span>
                                                    <small class="d-block text-dark">{{ $activePlan->percentage }}% COD Charges</small>
                                                @else
                                                    <span class="badge badge-secondary">No Plan Selected</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">No Plan Selected</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cod-plans-container">
                        <div class="cod-plan-grid">
                            @foreach($codPlans as $plan)
                                <div class="cod-plan-grid-item">
                                    <div class="block-bg h-100 cod-plan-card {{ $plan->is_default ? 'default-plan' : '' }} {{ $currentPlan == $plan->id ? 'active-plan' : '' }}" 
                                         data-plan-id="{{ $plan->id }}">
                                        <div class="mb-3">
                                            <div class="plan-header">
                                            <h5 class="plan-title">{{ $plan->plan_name }}</h5>
                                            <!-- @if($plan->is_default)
                                                <span class="default-badge">(Default)</span>
                                            @endif -->
                                        </div>
                                        
                                        <div class="plan-details">
                                            <div class="plan-percentage mb-2">
                                                <span class="percentage-value">{{ $plan->percentage }}%</span>
                                                <span class="percentage-label">of COD Amount</span>
                                            </div>
                                            
                                            <div class="plan-info">
                                                <div class="info-item mb-2">
                                                    <i class="mdi mdi-information-outline"></i>
                                                    <span>The Applicable transaction charges is {{ $plan->percentage }}% of the COD amount (Exclusive of GST)</span>
                                                </div>
                                                
                                                <div class="info-item mb-2">
                                                    <i class="mdi mdi-calendar-clock"></i>
                                                    <span>Remittance frequency: All Working Days (MON-FRI)</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="plan-terms">
                                            <div class="form-check">
                                                <input class="form-check-input terms-checkbox" type="checkbox" 
                                                       id="terms_{{ $plan->id }}" data-plan-id="{{ $plan->id }}"
                                                       {{ $currentPlan == $plan->id ? 'checked' : '' }}>
                                                <label class="form-check-label" style="cursor: pointer;"  for="terms_{{ $plan->id }}">
                                                    I have carefully read and agree to the <span style="color: black;">Terms & Conditions </span> 
                                                </label>
                                            </div>
                                        </div>
                                        </div>
                                        
                                        <div class="plan-action">
                                            @if($currentPlan == $plan->id)
                                                <button class="btn btn-main-1 add-btn btn-block activated-btn active-button-new-card" disabled>
                                                    <i class="mdi mdi-check-circle"></i> Activated
                                                </button>
                                            @else
                                                <button class="btn btn-main-1 add-btn btn-block activate-btn active-button-new-card" 
                                                        data-plan-id="{{ $plan->id }}" >
                                                    Activate
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- @if($currentPlan && $user->cod_plan_active)
                        <div class="current-plan-actions text-center mt-4">
                            <button class="btn btn-danger" id="deactivatePlan">
                                <i class="mdi mdi-close"></i> Deactivate Current Plan
                            </button>
                        </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="terms-content">
                    {!! nl2br(e($codTerms)) !!}
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-main-1 submite" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
    .inner-page-heading{
        width: 100% !important;
    }
.cod-plans-container {
    margin-top: 30px;
}

.cod-plan-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 22px;
    align-items: stretch;
}

.cod-plan-grid-item {
    min-width: 0;
}

.cod-plan-card {
    background: #fff;
    border: 2px solid #e3e6f0;
    border-radius: 14px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s ease;
    position: relative;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.cod-plan-card:hover {
    border-color: #4e73df;
    box-shadow: 0 0 20px rgba(78, 115, 223, 0.1);
}

.cod-plan-card.default-plan {
    background: linear-gradient(135deg, #f6d55c 0%, #f39c12 100%);
    border-color: #f39c12;
}

.cod-plan-card.active-plan {
    border-color: #28a745;
    background: #f8fff9;
    box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
}

.cod-plan-card.active-plan .plan-title {
    color: #28a745;
}

.plan-header {
    text-align: center;
    margin-bottom: 20px;
    position: relative;
}

.plan-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.default-badge {
    background: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 5px;
}

.plan-percentage {
    text-align: center;
    margin-bottom: 20px;
}

.percentage-value {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: #4e73df;
}

/* .default-plan .percentage-value {
    color: #fff;
} */

.percentage-label {
    font-size: 0.9rem;
    color: #6c757d;
}
/* 
.default-plan .percentage-label {
    color: rgba(255, 255, 255, 0.9);
} */

.plan-info {
    margin-bottom: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.info-item i {
    margin-right: 8px;
    margin-top: 2px;
    color: #4e73df;
}

/* .default-plan .info-item i {
    color: #000;
} */

.plan-terms {
    margin-bottom: 20px;
    min-height: 64px;
}

.plan-terms .form-check-label {
    font-size: 0.85rem;
    cursor: pointer;
}

.plan-action .btn {
    font-weight: 600;
    padding: 10px;
    min-height: 46px;
}

.activated-btn {
    background: #e91e63 !important;
    border-color: #e91e63 !important;
}

.current-plan-info {
    text-align: right;
    border-radius: 14px;
    padding: 18px 22px;
}

.current-plan-info h6 {
    font-size: 10px !important;
    margin-bottom: 5px;
    color: #000000ff !important;
}

.current-plan-info .badge {
    font-size: 22px;
    font-weight: 600;
    border-radius: 999px;
    padding: 6px 16px;
    margin: 6px 0 10px;
}

.cod-plan-card .form-check {
    margin-bottom: 0;
}

.cod-plan-card .form-check-input {
    margin-top: 0.2rem;
}

.cod-plan-card .form-check-label {
    line-height: 1.5;
}

.current-plan-actions {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-top: 30px;
}

@media (max-width: 1399px) {
    .cod-plan-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 991px) {
    .cod-plan-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .current-plan-info {
        text-align: left;
        margin-top: 15px;
    }
    
    .cod-plan-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Load current plan on page load
    loadCurrentPlan();
    
    // Initialize terms checkboxes for non-active plans
    $('.terms-checkbox').each(function() {
        const planId = $(this).data('plan-id');
        const activateBtn = $(`.activate-btn[data-plan-id="${planId}"]`);
        const isCurrentPlan = $(this).is(':checked');
        
        if (!isCurrentPlan) {
            //activateBtn.prop('disabled', true);
        }
    });
    
    // Terms checkbox change handler
    $('.terms-checkbox').change(function() {
        const planId = $(this).data('plan-id');
        const isChecked = $(this).is(':checked');
        const activateBtn = $(`.activate-btn[data-plan-id="${planId}"]`);
        
        // Only enable/disable if this is not the current active plan
        if (!$(this).closest('.cod-plan-card').hasClass('active-plan')) {
            activateBtn.prop('disabled', !isChecked);
        }
    });
    
    // Show terms modal when checkbox label is clicked
    $('.form-check-label').click(function(e) {
        e.preventDefault();
        $('#termsModal').modal('show');
    });
    
    // Activate plan
    $('.activate-btn').click(function() {
        const planId = $(this).data('plan-id');
        const termsAccepted = $(`#terms_${planId}`).is(':checked');
        const $button = $(this);
        
        console.log('Activate button clicked:', {
            planId: planId,
            termsAccepted: termsAccepted,
            url: "{{ route('user.cod-plans.activate') }}"
        });
        
        if (!termsAccepted) {
            toastrMsg('error', 'Please accept the terms and conditions first');
            return;
        }
        
        // Disable button during request
        $button.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Activating...');
        
        const requestData = {
            _token: "{{ csrf_token() }}",
            plan_id: planId,
            terms_accepted: 1
        };
        
        console.log('Sending request data:', requestData);
        
        $.ajax({
            url: "{{ route('user.cod-plans.activate') }}",
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                console.log('Activation response:', response);
                if (response.status === 'success') {
                    toastrMsg(response.status, response.msg);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    toastrMsg('error', response.msg || 'Failed to activate plan');
                    $button.prop('disabled', false).html('Activate');
                }
            },
            error: function(xhr, status, error) {
                console.log('Activation error:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                let errorMsg = 'Failed to activate plan';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.msg) {
                        errorMsg = xhr.responseJSON.msg;
                    } else if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMsg = 'Validation failed: ';
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + ' ';
                        });
                    }
                } else if (xhr.responseText) {
                    errorMsg = 'Server error: ' + xhr.responseText.substring(0, 100);
                }
                
                toastrMsg('error', errorMsg);
                $button.prop('disabled', false).html('Activate');
            }
        });
    });
    
    // Deactivate plan
    $('#deactivatePlan').click(function() {
        if (confirm('Are you sure you want to deactivate your current COD plan?')) {
            $.ajax({
                url: "{{ route('user.cod-plans.deactivate') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status === 'success') {
                        toastrMsg(response.status, response.msg);
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function() {
                    toastrMsg('error', 'Failed to deactivate plan');
                }
            });
        }
    });
    
    // Load current plan details
    function loadCurrentPlan() {
        $.get("{{ route('user.cod-plans.current') }}", function(response) {
            if (response.status === 'success' && response.active && response.data) {
                $('#currentPlanDisplay').html(`
                    <span class="badge badge-success">${response.data.plan_name}</span>
                    <small class="d-block text-dark mt-2">${response.data.percentage}% COD Charges</small>
                `);
            } else {
                $('#currentPlanDisplay').html(`
                    <span class="badge badge-secondary">No Plan Selected</span>
                `);
            }
        }).fail(function() {
            $('#currentPlanDisplay').html(`
                <span class="badge badge-secondary">No Plan Selected</span>
            `);
        });
    }
});
</script>
@endpush
