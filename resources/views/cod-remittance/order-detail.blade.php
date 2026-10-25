@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Order Detail')
@section('header_title', 'Order Detail')
@section('content')

    <style>
        .order-detail-container {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .order-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .order-id-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .transactions-section {
            margin-top: 30px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            border-radius: 10px;
            overflow: hidden;
        }

        .transactions-table thead th {
            background: #ececec;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .transactions-table tbody td {
            padding: 14px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .transactions-table tbody tr:hover {
            background: #E8EEFF47;
        }
        .transactions-table tbody tr:last-child {
             border-bottom: none !important;
        }
        .transactions-table tbody tr:last-child td{
            border-bottom: none;
        }
        .trans-type-revenue {
            color: #28a745;
            font-weight: 600;
        }

        .trans-type-spend {
            color: #dc3545;
            font-weight: 600;
        }

        .amount-positive {
            color: #28a745;
            font-weight: 600;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: 600;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <!-- Order Header -->
                <div class="main-order-001 mb-3">
                    <div class="order-header">
                        <div class="order-id-title">Order ID: {{ $order->order_prefix ?? $order->id }}</div>

                        <div class="order-info-grid">
                            <div class="info-item">
                                <div class="info-label">Payment Mode</div>
                                <div class="info-value">{{ strtoupper($order->order_type) }}</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Total Selling Price</div>
                                <div class="info-value">
                                    {{ config('setting.currency') }}{{ number_format($order->invoice_amount ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Order Created</div>
                                <div class="info-value">
                                    {{ $order->created_at ? $order->created_at->format('Y-m-d') : '-' }}</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Revenue</div>
                                <div class="info-value">
                                    {{ config('setting.currency') }}{{ number_format($order->invoice_amount ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Spend</div>
                                <div class="info-value">
                                    -{{ config('setting.currency') }}{{ number_format($order->remittance_amount ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Current Status</div>
                                <div class="info-value">
                                    <span
                                        class="status-badge status-delivered">{{ strtoupper($order->status_courier ?? 'PENDING') }}</span>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">P&L Per Order</div>
                                <div class="info-value" style="color: #28a745;">
                                    {{ config('setting.currency') }}{{ number_format(($order->invoice_amount ?? 0) - ($order->remittance_amount ?? 0), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Section -->
                    <div class="transactions-section">
                        <h6 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Transactions</h6>

                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>AWB Number</th>
                                    <th>Trans. Type</th>
                                    <th>Transaction Details</th>
                                    <th>Amount</th>
                                    <th>Trigger State Status</th>
                                    <th>Remittance Cycle (F & D)</th>
                                    <th>Settlement Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction['date'] }}</td>
                                        <td>{{ $transaction['awb_number'] }}</td>
                                        <td>
                                            <span
                                                class="{{ $transaction['trans_type'] === 'Revenue' ? 'trans-type-revenue' : 'trans-type-spend' }}">
                                                {{ $transaction['trans_type'] }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction['transaction_details'] }}</td>
                                        <td>
                                            <span
                                                class="{{ $transaction['amount'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                                {{ $transaction['amount'] >= 0 ? '+' : '' }}{{ config('setting.currency') }}{{ number_format(abs($transaction['amount']), 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge status-delivered">{{ $transaction['trigger_state'] }}</span>
                                        </td>
                                        <td>{{ $transaction['remittance_cycle'] }}</td>
                                        <td>
                                            <span
                                                class="status-badge {{ $transaction['settlement_status'] === 'COMPLETED' ? 'status-completed' : 'status-pending' }}">
                                                {{ $transaction['settlement_status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                            No transactions found for this order
                                        </td>
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
