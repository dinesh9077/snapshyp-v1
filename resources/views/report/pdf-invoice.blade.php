<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header,
        .section {
            padding: 10px 20px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .section td {
            vertical-align: top;
        }

        .label {
            font-weight: bold;
        }

        .invoice-details {
            float: right;
            text-align: right;
        }

        .amount-table th,
        .amount-table td {
            border: 1px solid #000;
            padding: 8px;
        }

        .amount-table th {
            background-color: #f2f2f2;
        }

        .note {
            margin-top: 20px;
            font-size: 11px;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
            font-size: 11px;
        }

        .stamp {
            height: 60px;
        }

        .page-border {
            border: 1px solid #000;
            padding: 20px;
            margin: 20px;
            box-sizing: border-box;
        }

        @page {
            margin: 20px;
        }
    </style>
</head>

<body>
    <div class="page-border">
        <div class="header">
            <div class="title">Tax Invoice</div>
            <table>
                <tr>
                    <td>
                        @if (!empty($logoPath))
                            <img src="{{ $logoPath }}" height="30" alt="logo">
                        @endif
                    </td>
                    <td class="invoice-details">
                        <div><strong>Invoice #:</strong> {{ $invoice['number'] }}</div>
                        <div><strong>Invoice Date:</strong> {{ $invoice['date'] }}</div>
                        <div><strong>Invoice Period:</strong> {{ $invoice['period'] }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div><strong>Bill From:</strong></div>
                        <div>{{ $invoice['from']['name'] }}</div>
                        <div>{{ $invoice['from']['address'] }}</div>
                        <div><strong>GSTIN:</strong> {{ $invoice['from']['gst'] }}</div>
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <div><strong>Bill To:</strong></div>
                        <div>{{ $invoice['to']['name'] }}</div>
                        <div>{{ $invoice['to']['address'] }}</div>
                        <div><strong>Mobile:</strong> {{ $invoice['to']['mobile'] ?: 'N/A' }}</div>
                        <div><strong>GSTIN:</strong> {{ $invoice['to']['gstNumber'] }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="amount-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width: 150px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Orders</td>
                        <td>{{ number_format($invoice['summary']['order_count'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Forward Charges</td>
                        <td>Rs. {{ number_format($invoice['summary']['forward'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>COD Charges</td>
                        <td>Rs. {{ number_format($invoice['summary']['cod'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>RTO Charges</td>
                        <td>Rs. {{ number_format($invoice['summary']['rto'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Taxable Shipping Charges (HSN Code - 996812)</td>
                        <td>Rs. {{ number_format($invoice['charges']['shipping'], 2) }}</td>
                    </tr>
                    @if($invoice['charges']['igst'] > 0)
                    <tr>
                        <td>IGST @ 18%</td>
                        <td>Rs. {{ number_format($invoice['charges']['igst'], 2) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td>SGST @ 9%</td>
                        <td>Rs. {{ number_format($invoice['charges']['sgst'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>CGST @ 9%</td>
                        <td>Rs. {{ number_format($invoice['charges']['cgst'], 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Net Payable:</th>
                        <th>Rs. {{ number_format($total, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="note">
                Kindly remit the net payable amount to the below mentioned account (Ignore if already paid):<br><br>
                <strong>Kindly Login to {{ url('/') }} using your registered user ID and password and
                    recharge your wallet with net payable amount.</strong>
            </div>
            <div class="signature">
                <div>For, {{ $invoice['from']['name'] }}</div><br><br>
                <!--<img src="{{ public_path('images/stamp.png') }}" class="stamp" alt="stamp">-->
                <div>Authorized Signatory</div><br>
            </div>
        </div>

        <div class="section" style="font-size: 10px; text-align: center;">
            <strong>Regd. Office:</strong> {{ $invoice['from']['address'] }} <br>
            This is a computer generated invoice no signature is required
        </div>
    </div>
</body>

</html>
