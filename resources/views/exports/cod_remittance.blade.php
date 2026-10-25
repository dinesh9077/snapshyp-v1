<table>
    <thead>
        <tr> 
            <th>Order ID</th>
            <th>Shipment Date</th>
            <th>Order No</th>
            <th>Carrier Name</th>
            <th>AWB</th>
            <th>Collectable Amount</th>
            <th>Order Amount</th>
            <th>Delivery Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($orders as $order)
        <tr> 
            <td>{{ $order->id }}</td>
            <td>{{ $order->created_at->format('Y-m-d') }}</td>
            <td>{{ $order->order_prefix }}</td>
            <td>{{ $order->courier_name }}</td>
            <td>{{ $order->awb_number }}</td>
            <td>{{ $order->cod_amount }}</td>
            <td>{{ $order->total_amount }}</td>
            <td>{{ $order->delivery_date }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
