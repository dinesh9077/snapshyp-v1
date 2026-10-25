<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Shipping Label</title>
</head>
@php
    if (!$preference) {
        $preference = (object)[
            'label_size' => 'thermal',
            'label_sku_limit' => 32,
            'label_print_limit' => 3,
            'label_print_multiple' => false,
            'label_company_logo' => null,
            'label_show_invoice_value' => false,
            'label_show_invoice_number' => false,
            'label_show_weight' => false,
            'label_show_product_name' => true,
            'label_show_sku' => true,
            'label_show_quantity' => true,
            'label_show_item_price' => true,
            'label_show_seller_address' => false,
            'label_show_seller_name' => false,
            'label_show_seller_mobile' => false,
            'label_show_company_name' => false,
        ];
    }
    $isThermal = ($preference->label_size ?? 'thermal') == 'thermal';
    $fontSize = $isThermal ? '10px' : '11px';
    $padding = $isThermal ? '2px 5px' : '5px';
    $headerPadding = $isThermal ? '5px' : '10px 10px';
    $barcodeHeight = $isThermal ? '40px' : '50px';
    $qrSize = $isThermal ? '50px' : '60px';
    $prodNameLimit = 30;
    $skuLimit = $isThermal ? 24 : optional($preference)->label_sku_limit ?? 32;
    $tablePadding = $isThermal ? '1px 5px' : '5px';

    // Pagination Logic
    $printLimit = optional($preference)->label_print_limit ?? 3;
    $doPagination = (optional($preference)->label_print_multiple ?? false) && $products->count() > $printLimit;

    if ($doPagination) {
        $chunks = $products->chunk($printLimit);
    } else {
        $chunks = [$products];
    }

    // Pre-calculate addresses to avoid repetition
    $pickupAddress = '';
    if ($order->warehouse) {
        $pickupAddress = trim(
            $order->warehouse->address .
                ' ' .
                $order->warehouse->city .
                ' ' .
                $order->warehouse->state .
                ' ' .
                $order->warehouse->zip_code .
                ', ' .
                $order->warehouse->country,
        );
    }
    $customerAddress =
        $customerAddr->address .
        ' ' .
        $customerAddr->city .
        ' ' .
        $customerAddr->state .
        ' ' .
        $customerAddr->zip_code .
        ',' .
        $customerAddr->country;
@endphp
<style>
    @page {
        margin: {{ ($preference->label_size ?? 'thermal') == 'thermal' ? '5px' : '20px' }} !important;
    }
</style>

<body>
    @foreach ($chunks as $chunk)
        <table
            style="width: {{ $isThermal ? '100%' : '50%' }}; margin: ; border-collapse: collapse; font-family: Arial, sans-serif; font-size: {{ $fontSize }}; border: 1px solid black;">
            <tr>
                <td colspan="4"
                    style="padding: {{ $headerPadding }}; width: 80%; vertical-align: middle; font-size: 25px; font-weight: bold; border: 1px solid black;">
                @if ($preference->label_company_logo)
                    <img src="{{ public_path('storage/settings/label/' . $preference->label_company_logo) }}"
                        width="{{ $isThermal ? '120' : '150' }}" alt="">
                @endif
                </td>
                <td colspan="1"
                    style="padding: 0; text-align: center ; width: 20%; center; border: 1px solid black; padding: 0px 5px;">
                    <div style="height: {{ $barcodeHeight }}; width: 100%;">
                        @php
                            $logoPath = public_path('storage/shipping-logo/' . $shipping->logo);
                            if (!empty($order->courier_logo)) {
                                if (filter_var($order->courier_logo, FILTER_VALIDATE_URL)) {
                                    $logoPath = $order->courier_logo; 
                                } elseif (file_exists(public_path('storage/courier-logo/' . $order->courier_logo))) {
                                    $logoPath = public_path('storage/courier-logo/' . $order->courier_logo);
                                }
                            }
                        @endphp
                        <img src="{{ $logoPath }}" alt="LOGO"
                            style="max-height: 100%; max-width: 100%; width: auto; height: auto; display: block; margin-left: auto; margin-right: auto;">
                    </div>
                    <p style="font-weight: bold; margin-top: 5px; font-size: {{ $fontSize }};">{{ $order->shipping_mode }}</p>
                </td>
            </tr>

            <tr>
                <td colspan="3"
                    style="padding: {{ $padding }}; font-size: {{ $fontSize }}; border: 1px solid black; vertical-align: top;">
                    <p style="margin-top: 5px;"> <span style="font-weight: bold;">Deliver To :</span>
                        {{ $customer->first_name . ' ' . $customer->last_name }}</p>

                    <p style="margin-top: 5px;"><span style="font-weight: bold;">Contact :</span>
                        {{ $customer->mobile }}</p>

                    <p style="margin-top: 5px;"><span style="font-weight: bold;">Address :</span>
                        {{ $customerAddress }}
                    </p>
                    <p style="margin-top: 5px;"><span style="font-weight: bold;">Pin :</span>
                        {{ $customerAddr->zip_code }}
                    </p>
                    @if ($order->order_type == 'cod')
                        <p style="margin-top: 5px;"><span style="font-weight: bold;">Payment Type :</span> COD (Rs. {{ $order->cod_amount }})</p>
                    @else
                        <p style="margin-top: 5px;"><span style="font-weight: bold;">Payment Type :</span> Prepaid</p>
                    @endif
                </td>

                <td colspan="2"
                    style="padding: {{ $padding }}; font-size: {{ $fontSize }}; border: 1px solid black; vertical-align: top;">
                    <p style="margin-top: 7px;"><span style="font-weight: bold;">Order Id :</span>
                        {{ $order->order_prefix }}</p>
                    @if ($order->invoice_no)
                        <p style="margin-top: 7px;"><span style="font-weight: bold;">Ref./Invoice# :</span>
                            {{ $order->invoice_no }}</p>
                    @endif
                    <p style="margin-top: 7px;"><span style="font-weight: bold;">Date :</span> {{ $order->order_date }}
                    </p>
                    @if ($preference->label_show_invoice_value)
                        <p style="margin-top: 7px;"><span style="font-weight: bold;">Invoice Value :</span> Rs.
                            {{ $order->total_amount }}</p>
                    @endif
                    @if ($preference->label_show_invoice_number && $order->invoice_no)
                        <p style="margin-top: 7px;"><span style="font-weight: bold;">Inv No :</span>
                            {{ $order->invoice_no }}</p>
                    @endif
                    @if ($preference->label_show_weight)
                        <p style="margin-top: 7px;">
                            <span style="font-weight: bold;">Weights :</span> {{ $order->weight }} KG
                        </p>
                    @endif

                    @if ($order->platform_order_id)
                        <p style="margin-top: 7px;">
                            <span style="font-weight: bold;">{{ $order->platform_name }} OrderId :</span>
                            {{ $order->platform_order_id }}
                        </p>
                    @endif
                </td>
            </tr>
            @if (
                $preference->label_show_product_name ||
                    $preference->label_show_sku ||
                    $preference->label_show_quantity ||
                    $preference->label_show_item_price)
                <tr>
                    <td colspan="5" style="padding: 0; border: 1px solid black; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse; border: none; font-size: 11px;">
                            <thead>
                                <tr>
                                    {{-- <th
                                    style="padding: 5px; border-bottom: 1px solid black; border-right: 1px solid black; text-align: left;">
                                    Product category</th> --}}
                                    @if (!$preference || $preference->label_show_product_name)
                                        <th
                                            style="padding: {{ $padding }}; border-bottom: 1px solid black; border-right: 1px solid black; text-align: left;">
                                            Product Name</th>
                                    @endif
                                    @if (!$preference || $preference->label_show_sku)
                                        <th
                                            style="padding: {{ $padding }}; border-bottom: 1px solid black; border-right: 1px solid black; text-align: left;">
                                            Sku</th>
                                    @endif
                                    @if (!$preference || $preference->label_show_quantity)
                                        <th
                                            style="padding: {{ $padding }}; width: 70px; border-bottom: 1px solid black; border-right: 1px solid black; text-align: left;">
                                            Qty</th>
                                    @endif
                                    @if (!$preference || $preference->label_show_item_price)
                                        <th
                                            style="padding: {{ $padding }}; width: 100px; border-bottom: 1px solid black; text-align: left;">
                                            Price</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Total for calculation inside loop should be full order total as requested
                                    // But sum logic in previous code was summing loop products if no pre-calc.
                                    // We will calculate TOTALS for the Footer once based on ALL products.
                                    $grandTotalQuntity = $products->sum('quantity');
                                    $grandTotalAmount = $order->total_amount;
                                @endphp
                                @foreach ($chunk as $product)
                                    <tr>
                                        {{-- <td
                                        style="padding: 5px; border-bottom: 1px solid black; border-right: 1px solid black; vertical-align: top;">
                                        {{ $product->product_category }}</td> --}}
                                        @if (!$preference || $preference->label_show_product_name)
                                            <td
                                                style="padding: {{ $tablePadding }}; border-bottom: 1px solid black; border-right: 1px solid black; vertical-align: top;">
                                                {{ \Illuminate\Support\Str::limit($product->product_name, $prodNameLimit) }}
                                            </td>
                                        @endif
                                        @if (!$preference || $preference->label_show_sku)
                                            <td
                                                style="padding: {{ $tablePadding }}; border-bottom: 1px solid black; border-right: 1px solid black; vertical-align: top;">
                                                {{ \Illuminate\Support\Str::limit($product->sku_number, $skuLimit) }}
                                            </td>
                                        @endif
                                        @if (!$preference || $preference->label_show_quantity)
                                            <td
                                                style="padding: {{ $tablePadding }}; border-bottom: 1px solid black; border-right: 1px solid black; vertical-align: top;">
                                                {{ $product->quantity }}
                                            </td>
                                        @endif
                                        @if (!$preference || $preference->label_show_item_price)
                                            <td
                                                style="padding: {{ $tablePadding }}; border-bottom: 1px solid black; vertical-align: top;">
                                                {{ $product->amount }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                                <tr>
                                    @if (
                                        (!$preference->label_show_product_name && !$preference->label_show_sku) ||
                                            ($preference->label_show_quantity || $preference->label_show_item_price))
                                        <td colspan="2"
                                            style="text-align: right; font-weight: bold; padding: {{ $tablePadding }}; border-right: 1px solid black; vertical-align: top;">
                                            Total
                                        </td>
                                    @endif
                                    @if (!$preference || $preference->label_show_quantity)
                                        <td
                                            style="padding: {{ $tablePadding }}; border-right: 1px solid black; vertical-align: top;">
                                            {{ $grandTotalQuntity }}</td>
                                    @endif
                                    @if (!$preference || $preference->label_show_item_price)
                                        <td style="padding: {{ $tablePadding }}; vertical-align: top;">Rs.
                                            {{ $grandTotalAmount }}</td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endif

            <tr>
                <td colspan="5"
                    style="padding: {{ $isThermal ? '5px 0' : '10px 0' }}; text-align: center; border: 1px solid black;">
                    <div style="height: {{ $barcodeHeight }}; width: 100%;">
                        @if (!empty($order->shipment_id))
                            <img src="data:image/png;base64,{{ $orderIdBarcodePng }}" alt="Barcode"
                                style="display: block; height:{{ $barcodeHeight }}; width:70%; margin: 0 auto;" />
                        @endif
                    </div>
                    <p style="font-size: 18px;margin:0">{{ $order->awb_number }}</p>
                </td>
            </tr>
            @if (
                $preference->label_show_seller_address ||
                    $preference->label_show_seller_name ||
                    $preference->label_show_seller_mobile ||
                    $preference->label_show_company_name)
                <tr>
                    <td colspan="5"
                        style="padding: {{ $padding }}; border: 1px solid black; vertical-align: top;">
                        <p style="font-weight: bold; ">If not delivered, Return to:</p>
                        @if ($preference->label_show_seller_address)
                            <p>{{ $pickupAddress }}</p>
                        @endif
                        @if ($preference->label_show_seller_name)
                            <p>Contact Name: <span>{{ $order->warehouse->contact_name ?? '' }}</span></p>
                        @endif
                        @if ($preference->label_show_company_name)
                            <p>Company Name: <span>{{ $order->warehouse->company_name ?? '' }}</span></p>
                        @endif
                        @if ($preference->label_show_seller_mobile)
                            <p>Phone: {{ $order->warehouse->contact_number ?? '' }}</p>
                        @endif
                    </td>
                </tr>
            @endif
            <tr>
                <td colspan="5" style="border: 1px solid black; padding: 5px; text-align: right; vertical-align: middle;">
                    <span style="font-size: 10px; font-weight: bold; vertical-align: middle; font-family: Arial, sans-serif;">Powered by </span>
                    <img src="{{ public_path('storage/settings/' . config('setting.header_logo')) }}" width="80" alt="ShipFiGO" style="vertical-align: middle;">
                </td>
            </tr>
        </table>

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
