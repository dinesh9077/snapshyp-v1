@extends('layouts.backend.app')

@section('title', 'Wallet Recharge')

@section('header_title')
    Wallet Recharge
@endsection

@section('content')
    <style>
:root {
    --prime: #3984FB;
    --second: #3984FB;
    --light-bg: #f2f6fab0;
    --wallet-border: #d9e3f3;
    --wallet-shadow: 0 18px 40px rgba(10, 34, 74, 0.08);
}

/* Container */
.wallet-container {
    max-width: 920px;
    margin: auto;
}

/* Header */
.wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.wallet-header-copy h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.wallet-header-copy p {
    display: none;
}

.transaction-history-btn {
    background: var(--prime);
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    text-decoration: none;
}

.transaction-history-btn:hover {
    background: #0f2f6f;
}

/* Balance cards */
.balance-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.balance-cards > div[style*="justify-content: flex-end"] {
    display: none !important;
}

.balance-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #103b7f 0%, #1d4ea1 55%, #4d76d6 100%);
    color: #fff;
    padding: 20px;
    border-radius: 20px;
    box-shadow: var(--wallet-shadow);
    min-height: 130px;
}

.balance-card::after {
    content: "";
    position: absolute;
    inset: auto -40px -60px auto;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
}

.balance-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.balance-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.18);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.balance-icon img {
    width: 20px;
    filter: brightness(0) invert(1);
}

.balance-label {
    font-size: 14px;
    opacity: 0.92;
}

.balance-amount {
    position: relative;
    z-index: 1;
    margin-top: 18px;
    margin-bottom: 0;
    font-size: 32px;
    font-weight: 700;
}

.balance-note {
    position: relative;
    z-index: 1;
    margin-top: 12px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.84);
}

/* Recharge card */
.recharge-section {
    background: #fff;
    border: 1px solid var(--wallet-border);
    border-radius: 24px;
    padding: 24px;
    box-shadow: var(--wallet-shadow);
}

.recharge-section h2 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #111827;
    display: flex;
    align-items: center;
}

.recharge-subtitle {
    margin-bottom: 24px;
    color: #64748b;
    font-size: 14px;
}

/* Amount input */
.amount-input-group label {
    font-size: 14px;
    color: #444;
}

.amount-input-wrapper {
    display: flex;
    align-items: center;
    border: 1px solid var(--wallet-border);
    background: #f8fbff;
    border-radius: 12px;
    padding: 10px 14px;
    margin-top: 6px;
}

.currency-symbol {
    font-size: 18px;
    font-weight: 600;
    color: #3984FB;
    margin-right: 6px;
}

.amount-input {
    border: none;
    outline: none;
    font-size: 16px;
    width: 100%;
}

/* Quick buttons */
.quick-amounts {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 18px 0;
}

.quick-amount-btn {
    border: 1px solid #3984FB;
    background: #f8fbff;
    color: #3984FB;
    padding: 10px 0;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.quick-amount-btn.active,
.quick-amount-btn:hover {
    background: #3984FB;
    color: #fff;
}

/* Payment methods */
.payment-methods h3 {
    font-size: 15px;
    margin: 20px 0 10px;
    color: #333;
}

.payment-method-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--wallet-border);
    background: #f8fbff;
    border-radius: 16px;
    padding: 14px 16px;
    cursor: pointer;
}

.payment-method-option input {
    accent-color: #3984FB;
}

.cod-method-option {
    justify-content: flex-start;
    gap: 12px;
}

.cod-method-option .payment-method-info {
    text-align: left;
}

.payment-method-desc {
    font-size: 14px;
}

/* Summary */
.payment-summary {
    background: linear-gradient(180deg, #f9fbff 0%, #eef4fb 100%);
    border: 1px solid var(--wallet-border);
    border-radius: 18px;
    padding: 16px;
    margin-top: 22px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 8px;
}

.summary-total {
    font-size: 16px;
    font-weight: 700;
    color: #3984FB;
}

/* Pay button */
.pay-now-btn {
    width: 260px;
    margin-top: 22px;
    margin-left: auto;
    background: linear-gradient(135deg, #103b7f 0%, #2154ad 100%);
    color: #fff;
    border: none;
    padding: 14px 18px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(21, 63, 147, 0.3);
    display: flex;
    justify-content: center;
}

.pay-now-btn:hover {
    background: #3984FB;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 38, 77, 0.35);
}

.history-panel {
    background: #fff;
    border: 1px solid var(--wallet-border);
    border-radius: 24px;
    padding: 24px;
    box-shadow: var(--wallet-shadow);
}

.history-panel h2 {
    font-size: 18px;
    margin-bottom: 18px;
    color: #111827;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th,
.history-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #e8eef8;
    font-size: 14px;
    text-align: left;
}

.history-table th {
    color: #64748b;
    font-weight: 600;
}

.history-credit {
    color: #15803d;
    font-weight: 700;
}

.history-empty {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.franchise-only-balance {
    grid-template-columns: minmax(0, 1fr);
    max-width: 460px;
}

.franchise-view .balance-cards > .balance-card:nth-child(2),
.franchise-view .balance-cards > div[style*="justify-content: flex-end"] {
    display: none !important;
}
.history-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #3984FB;
    background: #fff;
    border-radius: 999px;
    text-decoration: none;
    border: 1px solid #c7d4ff;
    transition: all 0.3s ease;
}

.history-pill:hover {
    background: #3984FB;
    color: #fff;
}
/* Mobile */
@media (max-width: 576px) {
    .wallet-header {
        flex-direction: column;
        align-items: stretch;
    }

    .wallet-header-copy h1 {
        font-size: 24px;
    }

    .balance-cards {
        grid-template-columns: 1fr;
    }

    .quick-amounts {
        grid-template-columns: repeat(2, 1fr);
    }
    .pay-now-btn {
        width: 100%;
    }

}

    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="wallet-container {{ $user->role === 'franchise' ? 'franchise-view' : '' }}">
                    <!-- Header -->
                    <div class="wallet-header">
                        <div class="wallet-header-copy">
                            <h1>Wallet Recharge</h1>
                            <p>Recharge your wallet and review current wallet and COD balances.</p>
                        </div>
                        <a href="{{ route('recharge.list') }}" class="history-pill">
                             <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Transaction History
                        </a>
                    </div>

                    <!-- Balance Cards -->
                    <div class="balance-cards {{ $user->role === 'franchise' ? 'franchise-only-balance' : '' }}">
                        <div class="balance-card">
                            <div class="balance-card-header">
                                <div class="balance-icon wallet">
                                    <img src="{{ asset('assets/images/wallet-solid.svg') }}" alt="Wallet">
                                </div>
                                <div class="balance-label">Wallet Balance</div>
                            </div>
                            <h2 class="balance-amount">₹ {{ number_format($user->wallet_amount ?? 0, 2) }}</h2>
                        </div>

                        <div class="balance-card">
                            <div class="balance-card-header">
                                <div class="balance-icon cod">
                                    <img src="{{ asset('assets/images/money-bill-1-regular.svg') }}" alt="COD">
                                </div>
                                <div class="balance-label">COD Balance</div>
                            </div>
                            <h2 class="balance-amount">₹ {{ number_format($codBalance + ($codWalletBalance ?? 0), 2) }}</h2>
                        </div>
                        <div class="" style="text-align: right; color: white; display:flex; justify-content: flex-end; margin-top:5px;">
                            <span>COD Wallet: ₹{{ number_format($codWalletBalance ?? 0, 2) }}</span>
                        </div>
                    </div>

                    @if ($user->role === 'franchise')
                    <div class="history-panel">
                        <h2>Transaction History</h2>
                        @if(($recentTransactions ?? collect())->isEmpty())
                            <p class="history-empty">No transaction history found.</p>
                        @else
                            <div class="table-responsive">
                                <table class="history-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTransactions as $transaction)
                                            <tr>
                                                <td>{{ optional($transaction->created_at)->format('d M Y, h:i A') }}</td>
                                                <td>{{ $transaction->transaction_type ?? '-' }}</td>
                                                <td>{{ $transaction->transaction_status ?? '-' }}</td>
                                                <td class="{{ ($transaction->amount_type ?? '') === 'credit' ? 'history-credit' : '' }}">₹ {{ number_format((float) ($transaction->amount ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    @else
                    <!-- Recharge Section -->
                    <div class="recharge-section">
                        <h2><img src="{{ asset('assets/images/credit-card-regular.svg') }}"
                                style="width: 25px;margin-right: 5px;" alt="Credit Card"> Recharge Your
                            Wallet</h2>

                        <form id="rechargeForm">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">

                            <!-- Amount Input -->
                            <div class="amount-input-group">
                                <label for="recharge_amount">Enter The Amount</label>
                                <div class="amount-input-wrapper">
                                    <span class="currency-symbol">₹</span>
                                    <input type="number" id="recharge_amount" name="amount" class="amount-input"
                                        placeholder="{{ config('setting.recharge_min_amount', 200) }}"
                                        value="{{ config('setting.recharge_min_amount', 200) }}"
                                        min="{{ config('setting.recharge_min_amount', 200) }}"
                                        max="{{ config('setting.recharge_max_amount', 10000) }}" step="1">
                                </div>
                            </div>

                            <!-- Quick Amount Buttons -->
                            @php
                                $rechargeMin = (int) config('setting.recharge_min_amount', 200);
                                $rechargeMax = (int) config('setting.recharge_max_amount', 10000);
                                if ($rechargeMax < $rechargeMin) {
                                    $rechargeMax = $rechargeMin;
                                }
                                $quickAmounts = array_values(array_unique([
                                    $rechargeMin,
                                    min($rechargeMax, max($rechargeMin, $rechargeMin * 2)),
                                    min($rechargeMax, max($rechargeMin, $rechargeMin * 4)),
                                    $rechargeMax,
                                ]));
                            @endphp
                            <div class="quick-amounts">
                                @foreach ($quickAmounts as $index => $quickAmount)
                                    <button type="button" class="quick-amount-btn {{ $index === 0 ? 'active' : '' }}"
                                        data-amount="{{ $quickAmount }}">
                                        &#8377;{{ $quickAmount }}
                                    </button>
                                @endforeach
                            </div>

                            <!-- Payment Methods -->
                            <div class="payment-methods">
                                <h3>Recharge using COD Balance / Wallet</h3>
                                <div class="payment-method-option cod-method-option"
                                    style="pointer-events: {{ ($codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0))) <= 0 ? 'none' : '' }}; cursor: {{ ($codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0))) <= 0 ? 'not-allowed' : 'pointer' }}; background-color: {{ ($codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0))) <= 0 ? '#8080801f' : '' }}"
                                    onclick="toggleCodUtilization(true)">
                                    <input type="radio" name="payment_method" value="cod" id="cod_balance"
                                        {{ ($codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0))) <= 0 ? 'disabled' : '' }}>
                                    <div class="payment-method-info">
                                        <div class="payment-method-name">COD Utilisation</div>
                                    </div>
                                </div>
                                <div id="codUtilizationBox" style="display: none;">
                                    <div class="from-group mt-2">
                                        <label for="cod_utilization_amount">
                                            Enter COD Utilization Amount
                                        </label>
                                        <input type="number" class="form-control" id="cod_utilization_amount"
                                            placeholder="Enter amount" value="{{ $codRechargeAvailable ?? ($codTransferBalance + ($codWalletBalance ?? 0)) }}"
                                            data-max="{{ $codRechargeAvailable ?? ($codTransferBalance + ($codWalletBalance ?? 0)) }}" data-cod_amount="{{ $codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0)) }}">
                                        <small class="text-muted">Total Available: ₹{{ number_format(($codRechargeAvailable ?? ($codBalance + ($codWalletBalance ?? 0))), 2) }}</small>
                                    </div>
                                </div>
                                <input type="hidden" id="cod_balance_amount" value="{{ $codBalance }}">
                                <input type="hidden" id="cod_wallet_balance_amount" value="{{ $codWalletBalance ?? 0 }}">
                                <h3 style="margin-top: 24px;">Online (UPI/Razorpay)</h3>
                                @if (config('setting.razorpay_status') == 1)
                                    <div class="payment-method-option" onclick="toggleCodUtilization(false)">
                                        <input type="radio" name="payment_method" value="razorpay" id="razorpay_payment"
                                            checked>
                                        <div class="payment-method-info">
                                            <div class="payment-method-desc">Razorpay Payment Gateway</div>
                                        </div>
                                        <div class="payment-icons">
                                            <div class="payment-icon">📱</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Zoho Payment Option (commented/disabled)
                                @if (config('setting.zoho_status') == 1)
                                    <div class="payment-method-option" onclick="toggleCodUtilization(false)">
                                        <input type="radio" name="payment_method" value="zoho" id="zoho_payment">
                                        <div class="payment-method-info">
                                            <div class="payment-method-desc">Zoho Payment Gateway</div>
                                        </div>
                                        <div class="payment-icons">
                                            <div class="payment-icon">💳</div>
                                        </div>
                                    </div>
                                @endif
                                --}}
                            </div>

                            <!-- Payment Summary -->
                            <div class="payment-summary">
                                <div class="summary-row">
                                    <span class="summary-label">You're paying:</span>
                                    <span class="summary-value">₹ <span id="paying_amount">500</span></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Gateway Amount</span>
                                    <span class="summary-value"><span id="razorpay_amount">0</span></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">COD Utilisation</span>
                                    <span class="summary-value">₹ <span id="cod_amount">0</span></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label summary-total">Total</span>
                                    <span class="summary-value summary-total"> <span id="total_amount">500</span></span>
                                </div>
                            </div>

                            <!-- Pay Now Button -->
                            <button type="submit" class="pay-now-btn">
                                Pay Now: ₹<span id="pay_button_amount">500</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($user->role !== 'franchise')
    @push('js')
            @if(config('setting.razorpay_status') == 1)
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            @endif
            <script>
            $(document).ready(function() {
                const rechargeMinAmount = parseFloat(@json((float) config('setting.recharge_min_amount', 200))) || 200;
                const rechargeMaxAmount = parseFloat(@json((float) config('setting.recharge_max_amount', 10000))) || 10000;

                const els = {
                    rechargeInput: $('#recharge_amount'),
                    codInput: $('#cod_utilization_amount'),
                    codBox: $('#codUtilizationBox'),
                    paying: $('#paying_amount'),
                    total: $('#total_amount'),
                    codSummary: $('#cod_amount'),
                    razorpaySummary: $('#razorpay_amount'),
                    payBtnAmount: $('#pay_button_amount'),
                    quickBtns: $('.quick-amount-btn'),
                    paymentOptions: $('.payment-method-option'),
                };

                document.getElementById('cod_utilization_amount').addEventListener('input', function() {
                    let enteredValue = parseFloat(this.value) || 0;
                    const maxTransfer = parseFloat(this.dataset.max) || 0;
                    if (enteredValue > maxTransfer) {
                        enteredValue = maxTransfer;
                        this.value = maxTransfer;
                    }
                    if (enteredValue < 0) {
                        enteredValue = 0;
                        this.value = 0;
                    }
                });

                /* ---------------------------
                 * CORE UPDATE FUNCTION
                 * --------------------------- */
                function updateUI({
                    total = 0,
                    cod = 0,
                    razorpay = 0
                }) {
                    total = parseFloat(total) || 0;
                    cod = parseFloat(cod) || 0;
                    razorpay = parseFloat(razorpay) || 0;

                    els.paying.text(total.toLocaleString('en-IN'));
                    els.total.text(total.toLocaleString('en-IN'));
                    els.codSummary.text(cod.toLocaleString('en-IN'));
                    els.razorpaySummary.text(razorpay.toLocaleString('en-IN'));
                    els.payBtnAmount.text(total.toLocaleString('en-IN'));
                }

                /* ---------------------------
                 * QUICK AMOUNT BUTTONS
                 * --------------------------- */
                els.quickBtns.on('click', function() {
                    let amount = parseFloat($(this).data('amount')) || 0;
                    amount = Math.max(amount, rechargeMinAmount);
                    amount = Math.min(amount, rechargeMaxAmount);

                    els.quickBtns.removeClass('active');
                    $(this).addClass('active');

                    els.rechargeInput.val(amount);
                    updateUI({
                        total: amount,
                        razorpay: amount,
                        cod: 0
                    });
                });

                /* ---------------------------
                 * MANUAL AMOUNT INPUT
                 * --------------------------- */
                els.rechargeInput.on('input', function() {
                    const amount = $(this).val() || 0;

                    els.quickBtns.removeClass('active');
                    updateUI({
                        total: amount,
                        razorpay: amount,
                        cod: 0
                    });
                });

                /* ---------------------------
                 * COD UTILIZATION TOGGLE
                 * --------------------------- */
                window.toggleCodUtilization = function(show) {
                    if (show) {
                        els.codBox.show();
                        els.rechargeInput.val('');
                        els.quickBtns.prop('disabled', true);
                        els.rechargeInput.prop('disabled', true);
                        const codAmount = els.codInput.val() || 0;
                        updateUI({
                            total: codAmount,
                            cod: codAmount,
                            razorpay: 0
                        });

                    } else {
                        els.codBox.hide();
                        els.quickBtns.prop('disabled', false);
                        els.rechargeInput.prop('disabled', false);
                        const amount = els.rechargeInput.val() || getActiveAmount() || 0;
                        els.rechargeInput.val(amount);
                        updateUI({
                            total: amount,
                            razorpay: amount,
                            cod: 0
                        });
                    }
                };

                /* ---------------------------
                 * COD INPUT CHANGE
                 * --------------------------- */
                els.codInput.on('input', function() {
                    const value = $(this).val() || 0;
                    updateUI({
                        total: value,
                        cod: value,
                        razorpay: 0
                    });
                });

                /* ---------------------------
                 * PAYMENT METHOD SELECTION
                 * --------------------------- */
                els.paymentOptions.on('click', function() {
                    els.paymentOptions.removeClass('active');
                    $(this).addClass('active');
                    $(this).find('input[type="radio"]').prop('checked', true);
                });

                /* ---------------------------
                 * ACTIVE QUICK AMOUNT
                 * --------------------------- */
                function getActiveAmount() {
                    const btn = $('.quick-amount-btn.active');
                    return btn.length ? btn.data('amount') : 0;
                }

                /* ---------------------------
                 * INITIAL STATE
                 * --------------------------- */
                if (rechargeMaxAmount < rechargeMinAmount) {
                    toastrMsg('error', 'Recharge settings are invalid. Please contact admin.');
                }

                const initialAmount = Math.min(Math.max(500, rechargeMinAmount), rechargeMaxAmount);
                els.rechargeInput.val(initialAmount);
                updateUI({
                    total: initialAmount,
                    razorpay: initialAmount,
                    cod: 0
                });

                /* ---------------------------
                 * FORM SUBMISSION
                 * --------------------------- */
                $('#rechargeForm').on('submit', function(e) {
                    e.preventDefault();

                    const paymentMethod = $('input[name="payment_method"]:checked').val();
                    const userId = $('input[name="user_id"]').val();

                    if (paymentMethod === 'cod') {
                        // Handle COD Utilization
                        const codAmount = parseFloat($('#cod_utilization_amount').val()) || 0;
                        const codBalancePercent = @json($walletTransferValue);
                        if (codAmount < 200) {
                            toastrMsg('error', 'Minimum COD Utilization Amount is ₹200');
                            $('.pay-now-btn').prop('disabled', false);
                            return;
                        }

                        const codCalanceAmount = $('#cod_balance_amount').val() || 0;
                        const codWalletBalanceAmount = $('#cod_wallet_balance_amount').val() || 0;

                        // Show loading
                        run_waitMe($('body'), 1, 'win8');

                        $.ajax({
                            url: "{{ route('recharge.wallet.cod-expense') }}",
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                user_id: userId,
                                cod_amount: codAmount,
                                cod_balance_amount: codCalanceAmount,
                                cod_wallet_amount: codWalletBalanceAmount,
                                cod_balance_percent: codBalancePercent,
                                payment_method: 'cod'
                            },
                            success: function(response) {
                                $('body').waitMe('hide');

                                if (response.status === 'success') {
                                    toastrMsg('success', response.msg ||
                                        'COD balance transferred successfully!');

                                    // Show transaction details
                                    if (response.data) {
                                        setTimeout(() => {
                                            toastrMsg('info',
                                                `Reference ID: ${response.data.reference_id}`
                                            );
                                        }, 1000);
                                    }

                                    // Redirect to transaction history after 2 seconds
                                    setTimeout(() => {
                                        window.location.href =
                                            "{{ route('recharge.thankyou') }}" + "?transaction_id=" + (response.data?.reference_id || '');
                                    }, 2000);
                                } else {
                                    toastrMsg('error', response.msg ||
                                        'Failed to process COD transfer');
                                }
                            },
                            error: function(xhr) {
                                $('body').waitMe('hide');
                                const errorMsg = xhr.responseJSON?.msg ||
                                    'Failed to process COD transfer. Please try again.';
                                toastrMsg('error', errorMsg);
                            }
                        });

                    } else if (paymentMethod === 'razorpay') {
                        // Handle Razorpay Payment
                        const amount = parseFloat($('#recharge_amount').val()) || 0;

                        if (amount < rechargeMinAmount) {
                            toastrMsg('error', `Minimum recharge amount is ₹${rechargeMinAmount}`);
                            return;
                        }

                        if (amount > rechargeMaxAmount) {
                            toastrMsg('error', `Maximum recharge amount is ₹${rechargeMaxAmount}`);
                            return;
                        }

                        // Show loading
                        run_waitMe($('body'), 1, 'win8');

                        // Create Razorpay order
                        $.ajax({
                            url: "{{ route('recharge.wallet.create-razorpay-order') }}",
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                user_id: userId,
                                amount: amount
                            },
                            success: function(response) {
                                $('body').waitMe('hide');

                                if (response.status === 'success' && response.order) {
                                    // Initialize Razorpay payment
                                    initiateRazorpayPayment(response.order, amount, userId);
                                } else {
                                    toastrMsg('error', response.msg ||
                                        'Failed to create payment order');
                                }
                            },
                            error: function(xhr) {
                                $('body').waitMe('hide');
                                const errorMsg = xhr.responseJSON?.msg ||
                                    'Failed to create payment order. Please try again.';
                                toastrMsg('error', errorMsg);
                            }
                        });
                        {{--
                        } else if (paymentMethod === 'zoho') {
                             const amount = parseFloat($('#recharge_amount').val()) || 0;
                             if (amount < rechargeMinAmount) {
                                toastrMsg('error', `Minimum recharge amount is ₹${rechargeMinAmount}`);
                                return;
                            }

                            if (amount > rechargeMaxAmount) {
                                toastrMsg('error', `Maximum recharge amount is ₹${rechargeMaxAmount}`);
                                return;
                            }
                             run_waitMe($('body'), 1, 'win8');

                             $.ajax({
                                url: "{{ route('recharge.wallet.create-zoho-payment-link') }}",
                                method: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    amount: amount
                                },
                                success: function(response) {
                                    $('body').waitMe('hide');
                                    if (response.status === 'success') {
                                         window.location.href = response.payment_url;
                                    } else {
                                        toastrMsg('error', response.msg);
                                    }
                                },
                                error: function(xhr) {
                                    $('body').waitMe('hide');
                                    toastrMsg('error', 'Something went wrong.');
                                }
                                 });
                        }
                        --}}
                    } else {
                        toastrMsg('error', 'Please select a payment method');
                    }
                });

                /* ---------------------------
                 * RAZORPAY PAYMENT INTEGRATION
                 * --------------------------- */
                function initiateRazorpayPayment(order, amount, userId) {
                    const options = {
                        key: "{{ config('setting.razorpay_key_id') }}", // Razorpay Key ID from settings
                        amount: order.amount, // Amount in paise
                        currency: order.currency,
                        name: "{{ config('setting.company_name') }}",
                        description: "Wallet Recharge",
                        image: "{{ asset('storage/settings/' . config('setting.login_logo')) }}",
                        order_id: order.id,
                        handler: function(response) {
                            // Payment successful - verify payment
                            verifyRazorpayPayment(response, amount, userId);
                        },
                        prefill: {
                            name: "{{ auth()->user()->name }}",
                            email: "{{ auth()->user()->email }}",
                            contact: "{{ auth()->user()->mobile }}"
                        },
                        notes: {
                            user_id: userId,
                            purpose: "Wallet Recharge"
                        },
                        theme: {
                            color: "#000000"
                        },
                        modal: {
                            ondismiss: function() {
                                toastrMsg('warning', 'Payment cancelled');
                            }
                        }
                    };

                    const rzp = new Razorpay(options);

                    rzp.on('payment.failed', function(response) {
                        toastrMsg('error', 'Payment failed: ' + response.error.description);

                        // Log failed payment
                        $.ajax({
                            url: "{{ route('recharge.wallet.payment-failed') }}",
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                user_id: userId,
                                order_id: response.error.metadata.order_id,
                                payment_id: response.error.metadata.payment_id,
                                reason: response.error.description
                            }
                        });
                    });

                    rzp.open();
                }

                /* ---------------------------
                 * VERIFY RAZORPAY PAYMENT
                 * --------------------------- */
                function verifyRazorpayPayment(response, amount, userId) {
                    run_waitMe($('body'), 1, 'win8');

                    $.ajax({
                        url: "{{ route('recharge.wallet.verify-razorpay-payment') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            user_id: userId,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature,
                            amount: amount
                        },
                        success: function(verifyResponse) {
                            $('body').waitMe('hide');

                            if (verifyResponse.status === 'success') {
                                toastrMsg('success', verifyResponse.msg ||
                                    'Payment successful! Wallet recharged.');

                                // Show transaction details
                                if (verifyResponse.data) {
                                    setTimeout(() => {
                                        toastrMsg('info',
                                            `Transaction ID: ${verifyResponse.data.transaction_id}`
                                        );
                                    }, 1000);
                                }

                                // Redirect to transaction history
                                setTimeout(() => {
                                    window.location.href = "{{ route('recharge.thankyou') }}" + "?transaction_id=" + (verifyResponse.data?.transaction_id || '');
                                }, 2000);
                            } else {
                                toastrMsg('error', verifyResponse.msg || 'Payment verification failed');
                            }
                        },
                        error: function(xhr) {
                            $('body').waitMe('hide');
                            const errorMsg = xhr.responseJSON?.msg ||
                                'Payment verification failed. Please contact support.';
                            toastrMsg('error', errorMsg);
                        }
                    });
                }
            });
        </script>
@endpush
    @endif
@endsection
