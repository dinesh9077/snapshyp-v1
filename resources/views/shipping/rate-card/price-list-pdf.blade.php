<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Price List - {{ $rateCard->group_name }}</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        .page-break { page-break-after: always; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        .company-name { font-size: 18px; font-weight: bold; color: #FB5C08; margin-bottom: 5px; }
        .badge { background: #00c853; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .per-kg { text-align: right; color: #64748b; font-style: italic; font-size: 10px; margin-bottom: 5px; }
        
        .matrix-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .matrix-table th { background: #fff1ec; color: #FB5C08; border: 1px solid #e2e8f0; padding: 6px 4px; font-size: 9px; }
        .matrix-table td { border: 1px solid #e2e8f0; padding: 6px 4px; text-align: center; }
        .zone-label { font-weight: bold; background: #fff; text-align: left !important; color: #0f172a; border-left: 2px solid #FB5C08 !important; }

        .section-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 25px 0 15px 0; text-transform: uppercase; border-left: 4px solid #FB5C08; padding-left: 10px; }
        
        .other-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .other-table tr:nth-child(even) { background: #f8fafc; }
        .other-table td { padding: 10px 15px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .charge-name { width: 35%; font-weight: bold; color: #334155; }
        .charge-desc { color: #475569; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; color: #94a3b8; font-size: 9px; border-top: 1px solid #f1f5f9; padding-top: 10px; }
        
        .logo { max-height: 45px; width: auto; }
    </style>
</head>
<body>
    @foreach($data as $index => $item)
        <div class="@if(!$loop->last) page-break @endif">
            <div class="header">
                <table style="width: 100%;">
                    <tr>
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ $item['company']->name }}</div>
                            <span class="badge">FORWARD RATE CARD</span>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                             @if(!empty($item['company']->logo))
                                @php
                                    $logoPath = public_path('storage/'.$item['company']->logo);
                                    if(!file_exists($logoPath)) {
                                        $logoPath = base_path('public/storage/'.$item['company']->logo);
                                    }
                                @endphp
                                @if(file_exists($logoPath))
                                    <img src="{{ $logoPath }}" class="logo">
                                @else
                                    <div style="font-weight: bold; color: #cbd5e1; font-size: 16px;">{{ strtoupper($item['company']->name) }}</div>
                                @endif
                             @else
                                <div style="font-weight: bold; color: #cbd5e1; font-size: 16px;">{{ strtoupper($item['company']->name) }}</div>
                             @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if($type === 'b2b')
                <div class="per-kg">Pricing: Per 1 Kg</div>
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Origin \ Dest</th>
                            @foreach($item['b2bZones'] as $toZone)
                                <th>{{ $toZone->heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item['b2bZones'] as $fromZone)
                            <tr>
                                <td class="zone-label">{{ $fromZone->heading }}</td>
                                @foreach($item['b2bZones'] as $toZone)
                                    @php
                                        $rate = $item['matrixData']->get($fromZone->id)?->where('to_zone_id', $toZone->id)->first();
                                    @endphp
                                    <td>{{ $rate ? number_format($rate->rate_per_kg, 2) : '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="b2c-container">
                    @foreach($item['b2cRows'] as $weight => $rows)
                        <div class="section-title">Weight: {{ $weight }} Kg</div>
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Zone Heading</th>
                                    <th>Base Rate</th>
                                    <th>Extra Rate</th>
                                    <th>COD (Flat)</th>
                                    <th>COD (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td class="zone-label" style="text-align: left !important;">{{ $row->zoneSetting->heading ?? 'N/A' }}</td>
                                        <td>{{ number_format($row->rate, 2) }}</td>
                                        <td>{{ number_format($row->extra_rate, 2) }}</td>
                                        <td>{{ number_format($row->cod_charges, 2) }}</td>
                                        <td>{{ number_format($row->cod_percentage, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endif

            <div class="section-title">{{ strtoupper($type) }} Other Charges</div>
            <table class="other-table">
                <tbody>
                    @foreach($item['activeCharges'] as $charge)
                        @php
                            $config = $charge['config'];
                            $master = $charge['master'];
                            $desc = '';
                            
                            switch($master->calc_type) {
                                case 'per_kg_or_min':
                                    $desc = "₹" . ($config['per_kg'] ?? 0) . " Per Kg OR ₹" . ($config['min'] ?? 0) . " Which is Higher";
                                    break;
                                case 'percent_or_min':
                                    $desc = ($config['percent'] ?? 0) . "% OR ₹" . ($config['min'] ?? 0) . " Which is Higher";
                                    break;
                                case 'per_kg_or_flat':
                                    $desc = "₹" . ($config['per_kg'] ?? 0) . " Per Kg OR ₹" . ($config['flat'] ?? 0) . " Which is Higher";
                                    break;
                                case 'flat':
                                    $desc = "₹" . ($config['flat'] ?? 0);
                                    break;
                                case 'divisor':
                                    $desc = "(Length*Width*Height)/" . ($config['divisor'] ?? 5000);
                                    break;
                                case 'min_charge':
                                    $desc = "₹" . ($config['min'] ?? 0);
                                    break;
                                case 'gst_percent':
                                    $desc = ($config['percent'] ?? 0) . "% (As per Govt. rule and regulations)";
                                    break;
                                case 'handling_tier':
                                    $desc = "Weight Tier: " . ($config['threshold'] ?? 0) . " Kg (Upto: ₹" . ($config['rate_upto'] ?? 0) . ", Above: ₹" . ($config['rate_above'] ?? 0) .")";
                                    break;
                                case 'max_single_box_weight':
                                    $desc = ($config['max_single_box_weight'] ?? 0) . " Kg Max Weight per Box";
                                    break;
                                default:
                                    $parts = [];
                                    foreach($config as $k => $v) if($v !== '' && $v !== null) $parts[] = ucfirst($k) . ": " . $v;
                                    $desc = implode(', ', $parts);
                            }
                        @endphp
                        <tr>
                            <td class="charge-name">{{ $master->name }}</td>
                            <td class="charge-desc">{{ $desc }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ date('d M Y h:i A') }} | {{ config('setting.company_name') }} Price List
    </div>
</body>
</html>
