<div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
    <h4 class="mb-0 card-title">COD Remittance Settings</h4>
</div>
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="from-group">
            <label for="cod_remittance_cycle_days">COD Remittance Cycle (Days)</label>
            <input type="number" autocomplete="off" name="cod_remittance_cycle_days" id="cod_remittance_cycle_days"
                placeholder="Enter days (e.g. 1 for T+1)" min="0" value="{{ $user->cod_remittance_cycle_days ?? '' }}">
            <small class="text-muted">T+1, T+2, T+3 etc. (Leave empty for global)</small>
        </div>
    </div>
    {{-- <div class="col-lg-3 col-sm-6">
        <div class="from-group multi-selecter second">
            <label for="payout_days">
                Payout Days
                <i class="mdi mdi-information-outline" data-toggle="tooltip" data-placement="top"
                    title="Select days when remittance payouts should be processed"
                    style="cursor: help; color: #6c757d;"></i>
            </label>
            <select class="form-control select2" id="user_payout_days" name="payout_days[]" multiple>
                @php
                    $userSelectedDays = [];
                    if (isset($user) && !empty($user->payout_days)) {
                        $userSelectedDays = is_array($user->payout_days)
                            ? $user->payout_days
                            : explode(',', $user->payout_days);
                    }
                @endphp
                <option value="Monday" {{ in_array('Monday', $userSelectedDays) ? 'selected' : '' }}>Monday</option>
                <option value="Tuesday" {{ in_array('Tuesday', $userSelectedDays) ? 'selected' : '' }}>Tuesday</option>
                <option value="Wednesday" {{ in_array('Wednesday', $userSelectedDays) ? 'selected' : '' }}>Wednesday
                </option>
                <option value="Thursday" {{ in_array('Thursday', $userSelectedDays) ? 'selected' : '' }}>Thursday
                </option>
                <option value="Friday" {{ in_array('Friday', $userSelectedDays) ? 'selected' : '' }}>Friday</option>
                <option value="Saturday" {{ in_array('Saturday', $userSelectedDays) ? 'selected' : '' }}>Saturday
                </option>
                <option value="Sunday" {{ in_array('Sunday', $userSelectedDays) ? 'selected' : '' }}>Sunday</option>
            </select>
            <small class="text-muted">You can select a maximum of 2 payout days. Leave empty to use global
                settings</small>
        </div>
    </div> --}}
    <div class="col-lg-3 col-sm-6">
        <div class="from-group">
            <label for="remittance_service_fee_type">Remittance Service Fee Type</label>
            <select autocomplete="off" name="remittance_service_fee_type" id="remittance_service_fee_type">
                <option value="fixed" {{ ($user->remittance_service_fee_type ?? '') == 'fixed' ? 'selected' : '' }}>
                    Fixed Amount (₹)</option>
                <option value="percentage"
                    {{ ($user->remittance_service_fee_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage (%)
                </option>

            </select>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="from-group">
            <label for="remittance_service_fee_value">Remittance Service Fee Value</label>
            <input type="number" step="0.01" autocomplete="off" name="remittance_service_fee_value"
                id="remittance_service_fee_value" placeholder="Enter fee" min="0"
                value="{{ $user->remittance_service_fee_value ?? '' }}">
            <small class="text-muted">Leave empty to use global settings</small>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="from-group">
            <label for="cod_wallet_transfer_value">COD to Wallet Transfer (%)</label>
            <input type="number" step="0.01" autocomplete="off" name="cod_wallet_transfer_value"
                id="cod_wallet_transfer_value" placeholder="Enter percentage" min="0" max="100"
                value="{{ $user->cod_wallet_transfer_value ?? '' }}">
            <small class="text-muted">Leave empty to use global settings</small>
        </div>
    </div>
</div>
