@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Edit Franchise Partner')
@section('header_title', 'Edit Client')
@section('content')
    <style>
        button.btn-light-2 {
            padding: 7px 12px;
            font-size: 14px;
            border: none;
            background: linear-gradient(180deg, #ffc8c8 0%, #ff6e6e45 100%);
            border-radius: 5px;
        }

        .main-row.main-data-teble-1 .row {
            row-gap: 15px;
        }

        .main-heading-1.pt-lg-0.pb-lg-2.px-lg-0 h4 {
            color: #000;
            font-size: 20px;
        }

        .api-section {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            margin-top: 25px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
        }

        .webhook-card {
            border: 1px solid #e3e6f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            background: #fafbff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .wh-left {
            flex: 1;
            min-width: 0;
        }

        .webhook-url {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            word-break: break-all;
            margin-bottom: 8px;
        }

        .ev-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .ev-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .ev-created {
            background: #d4edda;
            color: #155724;
        }

        .ev-updated {
            background: #cce5ff;
            color: #004085;
        }

        .ev-status {
            background: #fff3cd;
            color: #856404;
        }

        .ev-deleted {
            background: #f8d7da;
            color: #721c24;
        }

        .wh-actions {
            display: flex;
            gap: 2px;
            flex-shrink: 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .add-btn {
            background: #3b5bdb;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .no-webhooks {
            text-align: center;
            padding: 30px;
            color: #bbb;
            font-size: 14px;
        }

        .wh-modal-bg {
            display: none;
            position: fixed;
            inset: 0;
        }

        .wh-modal-bg.open {
            display: block;
            background: rgba(0, 0, 0, .45);
            z-index: 1040;
        }

        .wh-modal-box {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            width: 480px;
            max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .18);
        }

        .wh-modal-box.open {
            display: block;
        }

        .wh-form-grp {
            margin-bottom: 18px;
        }

        .wh-form-grp label {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 6px;
            color: #444;
        }

        .wh-form-grp input[type=url] {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid #dedede;
            border-radius: 9px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .wh-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .wh-modal-header .close {
            background-color: #3984FB;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            border: 2px solid #fff;
            text-shadow: none;
            color: #fff;
            border-radius: 50%;
            padding: 4px;
        }

        .event-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 6px;
        }

        .event-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border: 1px solid #dedede;
            border-radius: 10px;
            cursor: pointer;
            transition: all .2s;
            user-select: none;
        }

        .event-option:has(input:checked) {
            border-color: #3b5bdb;
            background: #eef1ff;
        }

        .event-option input {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 768px) {
            .webhook-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .webhook-card {
                padding: 12px 12px;
            }
        }
    </style>
    <div class="content-page">
        <div class="content">
            <form id="userForm" method="post" class="customer_form" action="{{ route('users.update', $user->id) }}"
                enctype="multipart/form-data">
                @csrf
                <div class="container-fluid">
                    <div class="main-rowx-1">
                        <div class="main-order-001">
                            <div class="main-row main-data-teble-1">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="username"> Company Name </label>
                                            <input type="text" autocomplete="off" name="company_name" id="company_name"
                                                placeholder="Company Name" value="{{ $user->company_name }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="username"> Full Name </label>
                                            <input type="text" autocomplete="off" name="name" id="name"
                                                placeholder="Full Name" value="{{ $user->name }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Mobile </label>
                                            <input type="text" autocomplete="off" name="mobile" id="mobile"
                                                placeholder="Mobile" maxlength="10" pattern="\d{10}"
                                                title="Please enter exactly 10 digits" oninput="allowOnlyNumbers(this)"
                                                value="{{ $user->mobile }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Email </label>
                                            <input type="email" autocomplete="off" name="email" id="email"
                                                placeholder="Email" value="{{ $user->email }}" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Password </label>
                                            {{-- xpass is stored encrypted; the model decrypts it for this admin screen --}}
                                            <input type="text" autocomplete="off" name="password" id="password"
                                                placeholder="Password" value="{{ $user->xpass }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Gender </label>
                                            <select autocomplete="off" name="gender" id="gender" Required>
                                                <option value=""> Select Gender </option>
                                                <option value="Male" {{ $user->gender == 'Male' ? 'selected' : '' }}> Male
                                                </option>
                                                <option value="Female" {{ $user->gender == 'Female' ? 'selected' : '' }}>
                                                    Female</option>
                                                <option value="Other" {{ $user->gender == 'Other' ? 'selected' : '' }}>
                                                    Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Status </label>
                                            <select autocomplete="off" name="status" id="status">
                                                <option value="1" {{ $user->status == 1 ? 'selected' : '' }}> Active
                                                </option>
                                                <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>
                                                    In-Active
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="from-group">
                                            <label for="first-name"> Is KYC </label>
                                            <select autocomplete="off" name="kyc_status" id="kyc_status">
                                                <option value="0" {{ $user->kyc_status == 0 ? 'selected' : '' }}> No
                                                </option>
                                                <option value="1" {{ $user->kyc_status == 1 ? 'selected' : '' }}> Yes
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    @if(auth()->user()->role !== 'franchise')
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="first-name"> B2C Rate Card </label>
                                                <select class="form-control" name="b2c_rate_card_id">
                                                    <option value="0" {{ (($user->b2c_rate_card_id ?? $user->rate_card_id ?? 0) == 0) ? 'selected' : '' }}>All</option>
                                                    @foreach ($b2cRateCards as $rateCard)
                                                        <option value="{{ $rateCard->id }}" {{ (($user->b2c_rate_card_id ?? $user->rate_card_id) == $rateCard->id) ? 'selected' : '' }}>
                                                            {{ $rateCard->group_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="assigned_shipping_company_id"> Shadowfax Account </label>
                                                <select class="form-control" name="assigned_shipping_company_id" id="assigned_shipping_company_id">
                                                    <option value="">Default ({{ optional($courierAccounts->firstWhere('is_default', true))->name ?? 'none set' }})</option>
                                                    @foreach ($courierAccounts as $account)
                                                        <option value="{{ $account->id }}" {{ (int) $user->assigned_shipping_company_id === (int) $account->id ? 'selected' : '' }}>
                                                            {{ $account->name }}{{ $account->is_default ? ' (default)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        {{-- <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="first-name"> B2B Rate Card </label>
                                                <select class="form-control" name="b2b_rate_card_id">
                                                    <option value="0" {{ (($user->b2b_rate_card_id ?? $user->rate_card_id ?? 0) == 0) ? 'selected' : '' }}>All</option>
                                                    @foreach ($b2bRateCards as $rateCard)
                                                        <option value="{{ $rateCard->id }}" {{ (($user->b2b_rate_card_id ?? $user->rate_card_id) == $rateCard->id) ? 'selected' : '' }}>
                                                            {{ $rateCard->group_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div> --}}
                                    @endif
                                    @if($canManageCharges ?? false)
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="b2c_charge_type">B2C Charges Type</label>
                                                <select class="form-control" name="b2c_charge_type" id="b2c_charge_type">
                                                    <option value="1" {{ (int) ($user->b2c_charge_type ?? $user->charge_type ?? 1) === 1 ? 'selected' : '' }}>Fix</option>
                                                    <option value="2" {{ (int) ($user->b2c_charge_type ?? $user->charge_type ?? 1) === 2 ? 'selected' : '' }}>Percentage</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="b2c_charge">B2C Charges</label>
                                                <input type="number" step="0.01" min="0" autocomplete="off" name="b2c_charge"
                                                    id="b2c_charge" placeholder="Enter B2C charges"
                                                    value="{{ $user->b2c_charge ?? $user->charge ?? 0 }}">
                                            </div>
                                        </div>
                                        {{-- <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="b2b_charge_type">B2B Charges Type</label>
                                                <select class="form-control" name="b2b_charge_type" id="b2b_charge_type">
                                                    <option value="1" {{ (int) ($user->b2b_charge_type ?? $user->charge_type ?? 1) === 1 ? 'selected' : '' }}>Fix</option>
                                                    <option value="2" {{ (int) ($user->b2b_charge_type ?? $user->charge_type ?? 1) === 2 ? 'selected' : '' }}>Percentage</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="from-group">
                                                <label for="b2b_charge">B2B Charges</label>
                                                <input type="number" step="0.01" min="0" autocomplete="off" name="b2b_charge"
                                                    id="b2b_charge" placeholder="Enter B2B charges"
                                                    value="{{ $user->b2b_charge ?? $user->charge ?? 0 }}">
                                            </div>
                                        </div> --}}
                                    @endif
                                </div>

                                <div class="address_block mt-4">
                                    <div class="main-heading-1 pt-lg-0 pb-lg-2 px-lg-0">
                                        <h4 class="mb-0 card-title"> Address </h4>
                                    </div>
                                    <div class="append_address">
                                        <div class="row align-items-end">
                                            <div class="col-lg-12 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> Address </label>
                                                    <textarea class="default" autocomplete="off" name="address" id="address"
                                                        placeholder="Address" required>{{ $user->address }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="first-name"> Zip code </label>
                                                    <input class="default" type="text" autocomplete="off" name="zip_code"
                                                        id="zip_code" placeholder="Zip code"
                                                        onKeyup="autoFetchCountry(this)" value="{{ $user->zip_code }}"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> Country </label>
                                                    <input class="default" type="text" autocomplete="off" name="country"
                                                        id="country" placeholder="Country" value="{{ $user->country }}"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> State </label>
                                                    <input class="default" type="text" autocomplete="off" name="state"
                                                        id="state" placeholder="State" value="{{ $user->state }}" required>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6">
                                                <div class="from-group">
                                                    <label for="username"> City </label>
                                                    <input class="default" type="text" autocomplete="off" name="city"
                                                        id="city" placeholder="City" value="{{ $user->state }}" required>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-3 d-flex justify-content-end align-items-center" style="gap:10px;">
                                <button class="new-submit-btn " id="customer_submit"> Submit </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="api-section">
                    <div class="section-header">
                        <div>
                            <h4 style="margin:0" class="mb-0 card-title"><i class="mdi mdi-bell-ring-outline"></i> Webhooks
                            </h4>
                            <p style="margin:4px 0 0;color:#999;font-size:13px;">Configure endpoint to receive notifications
                                for this user.</p>
                        </div>
                        <button type="button" class="add-btn" onclick="openWhAdd()">+ Add Webhook</button>
                    </div>

                    <div id="webhookList">
                        <div id="noWh" class="no-webhooks" style="display:none;">No webhooks configured for this user.</div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="wh-modal-bg" id="whModalBg" onclick="closeWhModal()"></div>
    <div class="wh-modal-box" id="whModalBox">
        <div class="wh-modal-header  pb-3">
            <h4 class="modal-title" id="whModalTitle">Add Webhook</h4>
            <input type="hidden" id="editWhId">
            <button type="button" class="close" onclick="closeWhModal()">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        <div class="wh-form-grp">
            <label>Webhook URL <span style="color:red">*</span></label>
            <input type="url" id="whUrl" placeholder="https://example.com/webhook">
        </div>

        <div class="wh-form-grp">
            <label>Select Events <span style="color:red">*</span></label>
            <div class="event-grid">
                <label class="event-option">
                    <input type="checkbox" value="order.created" class="wh-ev">
                    <div><strong>order.created</strong></div>
                </label>
                <label class="event-option">
                    <input type="checkbox" value="order.updated" class="wh-ev">
                    <div><strong>order.updated</strong></div>
                </label>
                <label class="event-option">
                    <input type="checkbox" value="order.status_changed" class="wh-ev">
                    <div><strong>order.status_changed</strong></div>
                </label>
                <label class="event-option">
                    <input type="checkbox" value="order.deleted" class="wh-ev">
                    <div><strong>order.deleted</strong></div>
                </label>
            </div>
        </div>

        <div class="d-flex justify-content-end" style="gap:10px; margin-top:20px;">
            <button type="button" class="btn btn-light btn-reset" onclick="closeWhModal()">Cancel</button>
            <button type="button" class="btn btn-apply" onclick="saveWebhook()">Save</button>
        </div>
    </div>
@endsection
@push('js')
    <script>
        function allowOnlyNumbers(input) {
            input.value = input.value.replace(/\D/g, '');
        }

        // Initialize tooltips
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });

        $('#userForm').submit(function (event) {
            event.preventDefault();
            $(this).find('button').prop('disabled', true);
            var formData = new FormData(this);
            $.ajax({
                async: true,
                type: $(this).attr('method'),
                url: $(this).attr('action'),
                data: formData,
                cache: false,
                processData: false,
                contentType: false,
                dataType: 'Json',
                success: function (res) {
                    $('button').prop('disabled', false);
                    if (res.status == "error") {
                        toastrMsg(res.status, res.msg);
                    } else {
                        toastrMsg(res.status, res.msg);
                        window.location.href = "{{ route('users') }}?kyc_status=" + res.kyc_status;
                    }
                }
            });
        });

        let zipTimeout;

        function autoFetchCountry(obj) {
            obj.value = obj.value.replace(/\D/g, '');

            clearTimeout(zipTimeout); // Clear previous timeout

            const zip_code = $(obj).val().trim();

            if (/^\d{6}$/.test(zip_code)) { // Validate: Exactly 6 digits
                zipTimeout = setTimeout(() => {
                    $.ajax({
                        type: 'GET',
                        url: `https://api.postalpincode.in/pincode/${zip_code}`, // Using Template Literal
                        success: function (response) {
                            if (response[0]?.Status === "Success" && response[0].PostOffice?.length) {
                                const {
                                    District: city,
                                    State: state,
                                    Country: country
                                } = response[0].PostOffice[0];

                                $(`#city`).val(city);
                                $(`#state`).val(state);
                                $(`#country`).val(country);
                            } else {
                                console.warn("Invalid or missing pincode data.");
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("API Error:", error);
                        }
                    });
                }, 500); // Debounce API request by 500ms
            }
        }

        const WH_R = {
            list: '{{ route("users.webhooks.admin.index", $user->id) }}',
            store: '{{ route("users.webhooks.admin.store", $user->id) }}',
            update: id => `{{ url("users/webhooks/update") }}/${id}`,
            del: id => `{{ url("users/webhooks/delete") }}/${id}`,
            csrf: '{{ csrf_token() }}',
        };

        const evBadge = {
            'order.created': '<span class="ev-badge ev-created">order.created</span>',
            'order.updated': '<span class="ev-badge ev-updated">order.updated</span>',
            'order.status_changed': '<span class="ev-badge ev-status">order.status_changed</span>',
            'order.deleted': '<span class="ev-badge ev-deleted">order.deleted</span>',
        };

        async function loadWebhooks() {
            try {
                const res = await fetch(WH_R.list);
                if (!res.ok) throw new Error("Fetch failed");
                const json = await res.json();
                const list = json.data || [];
                const cont = document.getElementById('webhookList');
                if (!cont) return;
                cont.querySelectorAll('.webhook-card').forEach(el => el.remove());
                const noWh = document.getElementById('noWh');
                if (!list.length) {
                    if (noWh) noWh.style.display = 'block';
                    return;
                }
                if (noWh) noWh.style.display = 'none';
                list.forEach(wh => cont.appendChild(buildWhCard(wh)));
            } catch (e) {
                console.error("Webhook load error:", e);
            }
        }

        function buildWhCard(wh) {
            const card = document.createElement('div');
            card.className = 'webhook-card';
            const badges = (wh.events || []).map(e => evBadge[e] || e).join(' ');

            card.innerHTML = `
                    <div class="wh-left">
                        <div class="webhook-url">${wh.url}</div>
                        <div class="ev-badges">${badges}</div>
                    </div>
                    <div class="wh-actions">
                        <button type="button" class="btn btn-icon waves-effect waves-light action-icon mr-1 btn-edit-wh"><i class="mdi mdi-pencil"></i></button>
                        <button type="button" class="btn btn-icon waves-effect waves-light action-icon btn-del-wh"><i class="mdi mdi-trash-can-outline"></i></button>
                    </div>
                `;

            card.querySelector('.btn-edit-wh')?.addEventListener('click', () => openWhEdit(wh));
            card.querySelector('.btn-del-wh')?.addEventListener('click', () => delWh(wh.id));
            return card;
        }

        function openWhAdd() {
            const title = document.getElementById('whModalTitle');
            if (title) title.textContent = 'Add Webhook';
            const weid = document.getElementById('editWhId');
            if (weid) weid.value = '';
            const wurl = document.getElementById('whUrl');
            if (wurl) wurl.value = '';
            document.querySelectorAll('.wh-ev').forEach(cb => cb.checked = false);
            document.getElementById('whModalBox')?.classList.add('open');
            document.getElementById('whModalBg')?.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function openWhEdit(wh) {
            const title = document.getElementById('whModalTitle');
            if (title) title.textContent = 'Edit Webhook';
            const weid = document.getElementById('editWhId');
            if (weid) weid.value = wh.id;
            const wurl = document.getElementById('whUrl');
            if (wurl) wurl.value = wh.url;
            document.querySelectorAll('.wh-ev').forEach(cb => {
                cb.checked = Array.isArray(wh.events) && wh.events.includes(cb.value);
            });
            document.getElementById('whModalBox')?.classList.add('open');
            document.getElementById('whModalBg')?.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeWhModal() {
            document.getElementById('whModalBox')?.classList.remove('open');
            document.getElementById('whModalBg')?.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        async function saveWebhook() {
            const id = document.getElementById('editWhId')?.value;
            const url = document.getElementById('whUrl')?.value.trim();
            const events = [...document.querySelectorAll('.wh-ev:checked')].map(el => el.value);

            if (!url || !events.length) {
                toastrMsg('error', 'URL and at least one event required.');
                return;
            }

            const ep = id ? WH_R.update(id) : WH_R.store;
            try {
                const res = await fetch(ep, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WH_R.csrf },
                    body: JSON.stringify({ url, events }),
                });
                const json = await res.json();

                if (json.status === 'success') {
                    closeWhModal();
                    loadWebhooks();
                    toastrMsg('success', json.msg);
                } else {
                    toastrMsg('error', json.msg || 'Save failed.');
                }
            } catch (e) {
                toastrMsg('error', 'Connection error.');
            }
        }

        async function delWh(id) {
            if (!confirm('Delete this webhook?')) return;
            try {
                const res = await fetch(WH_R.del(id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': WH_R.csrf }
                });
                const json = await res.json();
                if (json.status === 'success') {
                    loadWebhooks();
                    toastrMsg('success', json.msg);
                } else {
                    toastrMsg('error', 'Delete failed.');
                }
            } catch (e) {
                toastrMsg('error', 'Connection error.');
            }
        }

        // Safety Cleanup & Escape Helper logic (Fix for stuck black overlays)
        function forceCleanup() {
            // Remove stuck Bootstrap backdrops
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            // Close custom modals and backdrops
            document.querySelectorAll('.modal-bg, .modal-box, .wh-modal-bg, .wh-modal-box').forEach(el => {
                el.classList.remove('open');
            });
            // Re-enable page scrolling
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        }

        window.onerror = function () {
            // Silently handle errors to prevent "Paused in debugger"
            return true;
        };

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                // Sanity check when user returns: if no modal is open, ensure no black layer
                const modal = document.getElementById('modalBox') || document.getElementById('whModalBox');
                if (modal && !modal.classList.contains('open')) {
                    forceCleanup();
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            forceCleanup();
            loadWebhooks();
        });
    </script>
@endpush