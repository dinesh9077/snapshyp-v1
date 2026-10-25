<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .status-table {
            width: 100%;
            border-collapse: collapse;
        }
        .status-table th, .status-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .status-table th {
            background-color: #f2f2f2;
        }
        .status-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-table tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <h1>Tracking Information</h1>

    @if(isset($data['object']['field']) && is_array($data['object']['field']))
        <?php
        // Define custom keys
        $keys = [
            "awb_number",
            "orderid",
            "actual_weight",
            "origin",
            "destination",
            "current_location_name",
            "current_location_code",
            "customer",
            "consignee",
            "pickupdate",
            "status",
            "tracking_status",
        ];
        ?>

        <h2>Shipment Details</h2>
        <table class="status-table">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($data['object']['field'], 0, 12) as $index => $value)
                    <tr>
                        <td>{{ $keys[$index] ?? 'Unknown Field' }}</td>
                         <td>
                            @if($keys[$index] == 'pickupdate' && is_array($value))
                            
                                @foreach($value as $date)
                                
                                    {{ $date['name'] }}<br>
                                @endforeach
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Scan Stages</h2>
        @if(isset($data['object']['field'][36]['object']) && is_array($data['object']['field'][36]['object']))
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
                    
                    @foreach($data['object']['field'][36]['object'] as $scan)
                    
                        @if(isset($scan['field']) && is_array($scan['field']))
                            <tr>
                                <td>{{ $scan['field'][0] ?? 'N/A' }}</td> <!-- Date & Time -->
                                <td>{{ $scan['field'][1] ?? 'N/A' }}</td> <!-- Event -->
                            </tr>
                        @else
                            <tr>
                                <td>{{ $data['object']['field'][36]['object']['field'][0] ?? 'N/A' }}</td> <!-- Date & Time -->
                                <td>{{ $data['object']['field'][36]['object']['field'][1] ?? 'N/A' }}</td> <!-- Event -->
                            </tr>
                        @endif
                    @endforeach
                    
                </tbody>
            </table>
        @else
            <p>No scan data available.</p>
        @endif
    @else
        <p>No tracking information available.</p>
    @endif
</body>
</html>
