@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Api Developer Section')
@section('header_title', 'Api Developer Section')
@section('content')
<style>
.api-section {
    background: #fff;
    border-radius: 14px;
    padding: 28px 32px;
    margin-bottom: 28px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07);
}
.api-section h2 { color: #1a1a2e; margin-bottom: 14px; font-size: 22px; }
.api-section h3 { color: #333; font-size: 17px; margin: 22px 0 10px; }

/* Token */
.token-box {
    background: #f5f6fa;
    border: 1px solid #e3e6f0;
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    word-break: break-all;
    font-size: 14px;
    font-family: monospace;
}

/* Webhook Card */
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
.wh-left { flex: 1; min-width: 0; }
.webhook-url {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    word-break: break-all;
    margin-bottom: 8px;
}
.ev-badges { display: flex; flex-wrap: wrap; gap: 6px; }
.ev-badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
}
.ev-created { background: #d4edda; color: #155724; }
.ev-updated { background: #cce5ff; color: #004085; }
.ev-status { background: #fff3cd; color: #856404; }
.ev-deleted { background: #f8d7da; color: #721c24; }

/* Buttons */
.wh-actions { display: flex; gap: 8px; flex-shrink: 0; }
.btn-edit {
    background: #ffc107; color: #212529;
    border: none; padding: 6px 16px;
    border-radius: 7px; font-size: 13px;
    font-weight: 600; cursor: pointer;
}
.btn-edit:hover { background: #e0a800; }
.btn-del {
    background: #dc3545; color: #fff;
    border: none; padding: 6px 16px;
    border-radius: 7px; font-size: 13px;
    font-weight: 600; cursor: pointer;
}
.btn-del:hover { background: #bd2130; }

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.add-btn {
    background: #3b5bdb; color: #fff;
    border: none; padding: 10px 20px;
    border-radius: 9px; font-size: 14px;
    font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
}
.add-btn:hover { background: #2f4ac7; }

.no-webhooks {
    text-align: center;
    padding: 40px 20px;
    color: #bbb;
    font-size: 15px;
}

/* Modal */
.modal-bg {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 1040;
}
.modal-bg.open { display: block; }
.modal-box {
    display: none; position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    z-index: 1050;
    background: #fff; border-radius: 16px;
    padding: 30px 32px;
    width: 480px; max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
}
.modal-box.open { display: block; }
.modal-box h4 { font-size: 20px; margin-bottom: 22px; color: #1a1a2e; }

.form-grp { margin-bottom: 18px; }
.form-grp label { font-weight: 700; font-size: 13px; display: block; margin-bottom: 6px; color: #444; }
.form-grp input[type=url] {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid #d0d5e8;
    border-radius: 9px; font-size: 14px;
    box-sizing: border-box;
}
.form-grp input[type=url]:focus {
    border-color: #3b5bdb; outline: none;
    box-shadow: 0 0 0 3px rgba(59,91,219,.12);
}

.event-grid { display: flex; flex-direction: column; gap: 10px; margin-top: 6px; }
.event-option {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px;
    border: 2px solid #e3e6f0;
    border-radius: 10px; cursor: pointer;
    transition: all .2s;
    user-select: none;
}
.event-option input { width: 16px; height: 16px; cursor: pointer; }
.event-option strong { font-size: 13px; display: block; }
.event-option span { font-size: 11px; color: #777; }
.event-option:has(input:checked) { border-color: #3b5bdb; background: #eef1ff; }

.modal-ftr { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
.btn-save {
    background: #3b5bdb; color: #fff; border: none;
    padding: 10px 22px; border-radius: 9px;
    font-size: 14px; font-weight: 600; cursor: pointer;
}
.btn-save:hover { background: #2f4ac7; }
.btn-cls {
    background: #f0f2fa; color: #444; border: none;
    padding: 10px 22px; border-radius: 9px;
    font-size: 14px; font-weight: 600; cursor: pointer;
}

/* Code */
pre {
    background: #1e1e2e; color: #cdd6f4;
    padding: 16px 18px; border-radius: 10px;
    overflow-x: auto; font-size: 13px;
    line-height: 1.7; margin-bottom: 10px;
}
code { font-family: 'Courier New', monospace; }
.copy-btn {
    background: #3b5bdb; color: #fff; border: none;
    padding: 6px 14px; border-radius: 7px;
    font-size: 13px; font-weight: 600; cursor: pointer;
}
.copy-btn:hover { background: #2f4ac7; }

@media (max-width: 768px) {
    .webhook-card{
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="main-order-page-1">

    {{-- API Token --}}
    <div class="api-section">
        <h2>🔑 Your API Token</h2>
        <div class="token-box">
            <span id="apiToken" style="flex:1">{{ $apiToken }}</span>
            <button class="copy-btn" onclick="cpEl('apiToken', this)">Copy</button>
        </div>
        <p style="margin-top:10px;color:#999;font-size:13px;">
            Preferred: <code>Authorization: Bearer &lt;token&gt;</code>. 
            Fallback (recommended for local/XAMPP): <code>X-API-Token: &lt;token&gt;</code>.
        </p>
    </div>

    {{-- Webhook --}}
    <div class="api-section">
        <div class="section-header">
            <div>
                <h2 style="margin:0">🔔 Webhook</h2>
                <p style="margin:4px 0 0;color:#999;font-size:13px;">
                    One webhook URL per account. We POST a JSON payload the moment an order is created or its shipment
                    status changes. Failed deliveries are retried automatically.
                </p>
            </div>
            <button class="add-btn" id="whAddBtn" onclick="whOpenModal(false)" style="display:none;">
                <i class="mdi mdi-plus-circle"></i> Add Webhook
            </button>
        </div>

        {{-- Empty state --}}
        <div id="whEmpty" class="no-webhooks" style="display:none;">
            No webhook registered yet. Click <b>Add Webhook</b> to start receiving notifications.
        </div>

        {{-- The single webhook card --}}
        <div id="whCard" style="display:none;flex-direction:column;gap:14px;border:1px solid #eee;border-radius:12px;padding:18px;background:#fafbff;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;align-items:flex-start;">
                <div style="flex:1;min-width:260px;">
                    <div id="whUrl" class="webhook-url" style="word-break:break-all;font-weight:600;font-size:15px;"></div>
                    <div id="whEvents" class="ev-badges" style="margin-top:8px;"></div>
                </div>
                <div class="wh-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button id="whToggleBtn" onclick="whToggle()" style="border:0;border-radius:7px;padding:8px 16px;font-weight:600;cursor:pointer;color:#fff;background:#6c757d;">Disable</button>
                    <button onclick="whOpenModal(true)" style="border:0;border-radius:7px;padding:8px 16px;font-weight:600;cursor:pointer;color:#fff;background:#f0ad2e;">Edit</button>
                    <button onclick="whTest()" style="border:0;border-radius:7px;padding:8px 16px;font-weight:600;cursor:pointer;color:#fff;background:#20a464;">Send Test</button>
                    <button onclick="whDelete()" style="border:0;border-radius:7px;padding:8px 16px;font-weight:600;cursor:pointer;color:#fff;background:#e74c3c;">Delete</button>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:#666;font-size:13px;">
                <span>Secret:</span>
                <code id="whSecret" style="word-break:break-all;">—</code>
                <button onclick="whRegenerate()" style="border:0;border-radius:6px;padding:5px 12px;font-weight:600;cursor:pointer;color:#fff;background:#f0ad2e;">Regenerate</button>
            </div>
        </div>

        {{-- Delivery logs --}}
        <div style="margin-top:26px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h3 style="margin:0;">Delivery Logs</h3>
                <button class="copy-btn" onclick="whLoadLogs()">↻ Refresh</button>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="text-align:left;color:#888;border-bottom:2px solid #eee;">
                            <th style="padding:10px 8px;">Time</th>
                            <th style="padding:10px 8px;">Event</th>
                            <th style="padding:10px 8px;">Status</th>
                            <th style="padding:10px 8px;">HTTP</th>
                            <th style="padding:10px 8px;">Attempt</th>
                            <th style="padding:10px 8px;">Duration</th>
                            <th style="padding:10px 8px;">Response / Error</th>
                            <th style="padding:10px 8px;">Payload</th>
                        </tr>
                    </thead>
                    <tbody id="whLogs">
                        <tr><td colspan="8" style="color:#bbb;padding:18px 0;text-align:center;">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- API Docs --}}
    <div class="api-section">
        <h2><i class="mdi mdi-api"></i> API Documentation</h2>

        <h3>1. Get Warehouse List</h3>
        <pre id="curl1"><code>curl --location '{{ url('/api-service/order/warehouse/list') }}' \
  --header 'X-API-Token: {{ $apiToken }}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl1',this)">Copy</button>

        <h3>2. Create Order</h3>
        <pre id="curl2"><code>curl --location '{{ url('/api-service/order/store') }}' \
  --header 'X-API-Token: {{ $apiToken }}' \
  --form 'shipping_mode="Surface"' \
  --form 'weight_order="1"' \
  --form 'order_date="{{ date('Y-m-d') }}"' \
  --form 'order_type="cod"' \
  --form 'cod_amount="651"' \
  --form 'warehouse_id="7"' \
  --form 'first_name="Rahul"' \
  --form 'last_name="Kumar"' \
  --form 'email="rahul@example.com"' \
  --form 'mobile="9876543210"' \
  --form 'address="221B Baker Street"' \
  --form 'country="India"' \
  --form 'state="Maharashtra"' \
  --form 'city="Mumbai"' \
  --form 'zip_code="400001"' \
  --form 'product_category[0]="cloth"' \
  --form 'product_name[0]="saree"' \
  --form 'amount[0]="651"' \
  --form 'quantity[0]="1"' \
  --form 'weight="1"' \
  --form 'length="10"' \
  --form 'width="10"' \
  --form 'height="2"' \
  --form 'invoice_no="INV-123"' \
  --form 'invoice_amount="651"' \
  --form 'total_weight="1"'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl2',this)">Copy</button>

        <h3>3. Update Order <small style="color:#999;font-weight:normal;">(only while status is <code>New</code>)</small></h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Partial update — send only the fields you want to change. Once the order is shipped it is frozen and cannot be updated.
        </p>
        <pre id="curl3"><code>curl --location '{{ url('/api-service/order/update/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}' \
  --form 'order_type="prepaid"' \
  --form 'weight="2"' \
  --form 'length="12"' \
  --form 'width="10"' \
  --form 'height="4"' \
  --form 'total_weight="2"' \
  --form 'invoice_amount="899"' \
  --form 'address="New Address Line, Andheri"' \
  --form 'city="Mumbai"' \
  --form 'state="Maharashtra"' \
  --form 'zip_code="400053"'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl3',this)">Copy</button>

        <h3>4. Courier List / Rate Check</h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Returns every serviceable courier for the order with its chargeable weight and total charge. Pick one and post it back to <code>/order/ship</code>.
            Package dimensions and warehouse must be set first. Note: <code>courier_id</code> is <code>null</code> for
            direct couriers (Shadowfax) — send it back exactly as returned.
        </p>
        <pre id="curl4"><code>curl --location '{{ url('/api-service/order/courier-list/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl4',this)">Copy</button>

        <h3>5. Ship Order</h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Books the shipment and assigns an AWB. Just post back the courier you picked from
            <code>/order/courier-list</code> — <code>shipping_company_id</code>, <code>courier_id</code>,
            <code>courier_name</code>, <code>total_charges</code> and <code>applicable_weight</code>. The freight /
            COD / tax breakup is resolved on the server, so you don't need to send it.
        </p>
        <pre id="curl5"><code>curl --location '{{ url('/api-service/order/ship') }}' \
  --header 'X-API-Token: {{ $apiToken }}' \
  --header 'Content-Type: application/json' \
  --data '{
    "order_id": 123,
    "shipping_company_id": 1,
    "courier_id": null,
    "courier_name": "Shadowfax",
    "total_charges": 213.46,
    "applicable_weight": 5
  }'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl5',this)">Copy</button>

        <h3>6. Get Order Details</h3>
        <pre id="curl6"><code>curl --location '{{ url('/api-service/order/details/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl6',this)">Copy</button>

        <h3>7. Order Status <small style="color:#999;font-weight:normal;">(lightweight, no courier call)</small></h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Current status + full status history without hitting the courier's tracking API.
        </p>
        <pre id="curl7"><code>curl --location '{{ url('/api-service/order/status/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl7',this)">Copy</button>

        <h3>8. Order Tracking <small style="color:#999;font-weight:normal;">(live courier scans)</small></h3>
        <pre id="curl8"><code>curl --location '{{ url('/api-service/order/tracking') }}' \
  --header 'X-API-Token: {{ $apiToken }}' \
  --header 'Content-Type: application/json' \
  --data '{"awb_number": "14355940174220"}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl8',this)">Copy</button>
        <p style="margin:6px 0 0;color:#999;font-size:13px;">
            Track by AWB or by order id: <code>{"order_id": "123"}</code> (numeric id or order prefix).
        </p>

        <h3>9. Download Shipping Label <small style="color:#999;font-weight:normal;">(PDF)</small></h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Streams the label PDF. Available only after the order is shipped (has an AWB). Add <code>--output label.pdf</code> to save it.
        </p>
        <pre id="curl9"><code>curl --location '{{ url('/api-service/order/label/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}' \
  --output label.pdf</code></pre>
        <button class="copy-btn" onclick="cpEl('curl9',this)">Copy</button>

        <h3>10. Cancel Order</h3>
        <p style="margin:2px 0 8px;color:#999;font-size:13px;">
            Closes an unshipped order, or cancels the booked shipment with the courier and refunds the shipping charge to your wallet.
        </p>
        <pre id="curl10"><code>curl --location '{{ url('/api-service/order/cancel/123') }}' \
  --header 'X-API-Token: {{ $apiToken }}'</code></pre>
        <button class="copy-btn" onclick="cpEl('curl10',this)">Copy</button>
    </div>

</div>
</div>
</div>
</div>

{{-- Modal --}}
<div class="modal-bg" id="whModalBg" onclick="whCloseModal()"></div>
<div class="modal-box" id="whModalBox">
    <h4 id="whModalTitle">Add Webhook</h4>

    <div class="form-grp">
        <label>Webhook URL <span style="color:red">*</span></label>
        <input type="url" id="whInputUrl" placeholder="https://yourdomain.com/webhook">
    </div>

    <div class="form-grp">
        <label>Select Events <span style="color:red">*</span></label>
        <div class="event-grid" id="whEventGrid"></div>
    </div>

    <div class="modal-ftr">
        <button class="btn-cls" onclick="whCloseModal()">Cancel</button>
        <button class="btn-save" onclick="whSave()">Save Webhook</button>
    </div>
</div>

@endsection
@push('js')
<script>
/* ---- Webhook (single per merchant) ---- */
const WH = {
    index:  "{{ route('webhooks.index') }}",
    logs:   "{{ route('webhooks.logs') }}",
    store:  "{{ route('webhooks.store') }}",
    del:    "{{ route('webhooks.destroy') }}",
    toggle: "{{ route('webhooks.toggle') }}",
    secret: "{{ route('webhooks.regenerate-secret') }}",
    test:   "{{ route('webhooks.test') }}",
    csrf:   "{{ csrf_token() }}",
};
const EV_CLASS = {
    'order.created': 'ev-created',
    'order.updated': 'ev-updated',
    'order.status_changed': 'ev-status',
    'order.deleted': 'ev-deleted',
};
let whCurrent = null;
let whEventLabels = {};

function whReq(url, method = 'GET', body = null) {
    return fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': WH.csrf,
            'Accept': 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : null,
    }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));
}

function whEsc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

function whRender() {
    const card = document.getElementById('whCard');
    const empty = document.getElementById('whEmpty');
    const addBtn = document.getElementById('whAddBtn');

    if (!whCurrent) {
        card.style.display = 'none';
        empty.style.display = 'block';
        addBtn.style.display = 'flex';
        return;
    }

    // Only one webhook is allowed, so hide "Add" once it exists.
    addBtn.style.display = 'none';
    empty.style.display = 'none';
    card.style.display = 'flex';

    document.getElementById('whUrl').textContent = whCurrent.url;
    document.getElementById('whSecret').textContent = whCurrent.secret_key || '—';
    document.getElementById('whEvents').innerHTML = (whCurrent.events || [])
        .map(e => `<span class="ev-badge ${EV_CLASS[e] || ''}">${whEsc(e)}</span>`).join(' ');

    const enabled = Number(whCurrent.status) === 1;
    const btn = document.getElementById('whToggleBtn');
    btn.textContent = enabled ? 'Disable' : 'Enable';
    btn.style.background = enabled ? '#6c757d' : '#20a464';
}

function whLoad() {
    whReq(WH.index).then(({ body }) => {
        whCurrent = body.data || null;
        whEventLabels = body.events || {};
        whRender();
    });
    whLoadLogs();
}

function whLoadLogs() {
    whReq(WH.logs).then(({ body }) => {
        const rows = (body.data && body.data.data) || [];
        const tbody = document.getElementById('whLogs');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="color:#bbb;padding:18px 0;text-align:center;">No deliveries yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(l => {
            const ok = l.status === 'success';
            const detail = l.error_message || l.response_body || '—';
            const payload = JSON.stringify(l.payload || {}, null, 2);
            return `<tr style="border-bottom:1px solid #f1f1f1;">
                <td style="padding:10px 8px;white-space:nowrap;">${whEsc(l.created_at)}</td>
                <td style="padding:10px 8px;"><code>${whEsc(l.event)}</code></td>
                <td style="padding:10px 8px;"><span class="ev-badge ${ok ? 'ev-created' : 'ev-deleted'}">${whEsc(l.status)}</span></td>
                <td style="padding:10px 8px;">${whEsc(l.http_status ?? '—')}</td>
                <td style="padding:10px 8px;">${whEsc(l.attempt)}/${whEsc(l.max_attempts)}</td>
                <td style="padding:10px 8px;">${l.duration_ms != null ? whEsc(l.duration_ms) + ' ms' : '—'}</td>
                <td style="padding:10px 8px;max-width:260px;word-break:break-word;">${whEsc(String(detail).slice(0, 160))}</td>
                <td style="padding:10px 8px;"><details><summary style="cursor:pointer;color:#3b5bdb;">View</summary>
                    <pre style="max-width:420px;font-size:11px;white-space:pre-wrap;">${whEsc(payload)}</pre></details></td>
            </tr>`;
        }).join('');
    });
}

function whOpenModal(isEdit = false) {
    document.getElementById('whModalTitle').textContent = isEdit ? 'Edit Webhook' : 'Add Webhook';
    document.getElementById('whInputUrl').value = isEdit && whCurrent ? whCurrent.url : '';

    const selected = isEdit && whCurrent ? (whCurrent.events || []) : Object.keys(whEventLabels);
    document.getElementById('whEventGrid').innerHTML = Object.entries(whEventLabels).map(([key, label]) => `
        <label class="event-option">
            <input type="checkbox" value="${whEsc(key)}" ${selected.includes(key) ? 'checked' : ''}>
            <div><strong>${whEsc(label)}</strong><span>${whEsc(key)}</span></div>
        </label>`).join('');

    document.getElementById('whModalBg').classList.add('open');
    document.getElementById('whModalBox').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function whCloseModal() {
    document.getElementById('whModalBg').classList.remove('open');
    document.getElementById('whModalBox').classList.remove('open');
    document.body.style.overflow = 'auto';
}

function whSave() {
    const url = document.getElementById('whInputUrl').value.trim();
    const events = [...document.querySelectorAll('#whEventGrid input:checked')].map(i => i.value);

    if (!url) { toast('Webhook URL is required.', 'warn'); return; }
    if (!events.length) { toast('Select at least one event.', 'warn'); return; }

    // store() creates on first save and edits thereafter, so one endpoint covers both.
    whReq(WH.store, 'POST', { url, events }).then(({ ok, body }) => {
        if (!ok || body.status !== 'success') {
            toast(body.msg || (body.errors ? Object.values(body.errors)[0][0] : 'Could not save webhook.'), 'err');
            return;
        }
        whCloseModal();
        whLoad();
        toast(body.msg, 'ok');
    });
}

function whToggle() {
    whReq(WH.toggle, 'POST').then(({ body }) => {
        if (body.status === 'success') { whCurrent.status = body.new_status; whRender(); toast(body.msg, 'ok'); }
        else toast(body.msg || 'Could not change status.', 'err');
    });
}

function whDelete() {
    if (!confirm('Delete this webhook? Its delivery logs will be removed too.')) return;
    whReq(WH.del, 'DELETE').then(({ body }) => {
        if (body.status === 'success') { whCurrent = null; whRender(); whLoadLogs(); toast(body.msg, 'ok'); }
        else toast(body.msg || 'Could not delete webhook.', 'err');
    });
}

function whRegenerate() {
    if (!confirm('Regenerate the secret? Your endpoint must use the new one to verify signatures.')) return;
    whReq(WH.secret, 'POST').then(({ body }) => {
        if (body.status === 'success') { whCurrent.secret_key = body.secret_key; whRender(); toast(body.msg, 'ok'); }
        else toast(body.msg || 'Could not regenerate secret.', 'err');
    });
}

function whTest() {
    whReq(WH.test, 'POST').then(({ body }) => {
        toast(body.msg || 'Test sent.', body.status === 'success' ? 'ok' : 'err');
        whLoadLogs();
    });
}

/* ---- Copy ---- */

function cpEl(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}

/* ---- Toast ---- */
function toast(msg, type = 'info') {
    const col = { ok: '#28a745', err: '#dc3545', info: '#17a2b8', warn: '#e67e22' };
    const el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = `
        position:fixed; bottom:26px; right:26px; z-index:9999;
        background:${col[type] || col.info}; color:#fff;
        padding:12px 22px; border-radius:9px;
        font-size:14px; font-weight:600;
        box-shadow:0 6px 20px rgba(0,0,0,.18);
        transition:opacity .3s;
    `;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = 0; setTimeout(() => el.remove(), 400); }, 3000);
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

window.onerror = function() {
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
    whLoad();
});
</script>
@endpush
