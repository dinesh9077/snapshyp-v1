<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Shipping Label</title>
</head>
@php
    // One template for both label sizes (desktop-label includes it). Every "Label Settings"
    // switch is honoured here; a missing setting row falls back to the defaults below.
    $defaults = [
        'label_size' => 'thermal',
        'label_company_logo' => null,
        'label_show_invoice_value' => true,
        'label_show_invoice_number' => true,
        'label_show_weight' => true,
        'label_show_cod_amount' => true,
        'label_show_seller_name' => true,
        'label_show_company_name' => true,
        'label_show_seller_address' => true,
        'label_show_seller_mobile' => true,
        'label_show_product_name' => true,
        'label_show_sku' => true,
        'label_show_quantity' => true,
        'label_show_item_price' => true,
        'label_show_taxes' => false,
        'label_product_name_limit' => 40,
        'label_sku_limit' => 24,
        'label_print_multiple' => false,
        'label_print_limit' => 3,
    ];
    $pref = (object) array_merge($defaults, $preference ? array_filter($preference->only(array_keys($defaults)), fn ($v) => $v !== null) : []);

    $isThermal = ($pref->label_size ?? 'thermal') !== 'desktop';

    // Images are embedded as data URIs so the PDF never shows "image not found"
    $embed = function (array $paths) {
        foreach ($paths as $path) {
            if ($path && is_file($path)) {
                $type = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpeg';
                return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return null;
    };
    $sellerLogo = $pref->label_company_logo ? $embed([
        storage_path('app/public/settings/label/' . $pref->label_company_logo),
        public_path('storage/settings/label/' . $pref->label_company_logo),
    ]) : null;
    $courierLogo = !empty($shipping->logo) ? $embed([
        storage_path('app/public/shipping-logo/' . $shipping->logo),
        public_path('storage/shipping-logo/' . $shipping->logo),
    ]) : null;
    $platformLogo = config('setting.header_logo') ? $embed([
        storage_path('app/public/settings/' . config('setting.header_logo')),
        public_path('storage/settings/' . config('setting.header_logo')),
    ]) : null;

    $warehouse = $order->warehouse;
    $sellerBrand = $warehouse->company_name ?? (optional($order->user)->company_name ?? '');
    $pickupAddress = $warehouse
        ? trim(implode(', ', array_filter([$warehouse->address, $warehouse->city, $warehouse->state, $warehouse->zip_code])))
        : '';
    $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    $customerAddress = trim(implode(', ', array_filter([
        $customerAddr->address ?? null,
        $customerAddr->city ?? null,
        $customerAddr->state ?? null,
    ])));

    $isCod = strtolower((string) $order->order_type) === 'cod';
    $money = fn ($v) => 'Rs. ' . number_format((float) $v, 2);

    // Product columns that are switched on; the Total label spans the text columns
    $showName = (bool) $pref->label_show_product_name;
    $showSku = (bool) $pref->label_show_sku;
    $showQty = (bool) $pref->label_show_quantity;
    $showPrice = (bool) $pref->label_show_item_price;
    $showItems = $showName || $showSku || $showQty || $showPrice;
    $itemCols = (int) $showName + (int) $showSku + (int) $showQty + (int) $showPrice;
    $textCols = (int) $showName + (int) $showSku;

    // Items: the label shows up to "print limit" items. With "Print multiple" on, the rest
    // continue on the next page(s) (items only, no second barcode); otherwise "+N more".
    $perPage = max(1, (int) $pref->label_print_limit);
    $firstItems = $products->take($perPage);
    $moreItems = $products->slice($perPage)->values();
    $continuation = (bool) $pref->label_print_multiple && $moreItems->count() ? $moreItems->chunk($perPage)->values() : collect();
    $hiddenItems = $continuation->count() ? 0 : $moreItems->count();
    $totalPages = 1 + $continuation->count();

    $showReturn = $pref->label_show_seller_address || $pref->label_show_seller_name
        || $pref->label_show_company_name || $pref->label_show_seller_mobile;

    // Thermal (4x6 in) should fill its page but never spill onto a second one, so the size tier
    // follows a rough line count of what is printed. Desktop keeps a compact half-page label.
    $nameLimit = $isThermal ? min((int) $pref->label_product_name_limit, 50) : (int) $pref->label_product_name_limit;
    $itemLines = 2; // header + total
    foreach ($firstItems as $product) {
        $itemLines += max(1,
            $showName ? (int) ceil(min(mb_strlen((string) $product->product_name), $nameLimit) / 22) : 1,
            $showSku ? (int) ceil(min(mb_strlen((string) $product->sku_number), (int) $pref->label_sku_limit) / 14) : 1);
    }
    $itemLines += ($hiddenItems || $continuation->count()) ? 1 : 0;
    $leftLines = 4 + (int) ceil(min(mb_strlen($customerAddress), 150) / 38);
    $infoLines = 2 + (int) (bool) $order->platform_order_id + (int) ($pref->label_show_invoice_number && $order->invoice_no)
        + (int) $pref->label_show_invoice_value + (int) ($pref->label_show_taxes && (float) $order->total_gst_amount > 0)
        + ($pref->label_show_weight ? 2 : 0);
    $returnLines = ($showReturn && $warehouse)
        ? 1 + (int) $pref->label_show_company_name + ($pref->label_show_seller_address ? (int) ceil(mb_strlen($pickupAddress) / 50) : 0)
            + (int) ($pref->label_show_seller_name || $pref->label_show_seller_mobile)
        : 0;
    $density = max($leftLines, $infoLines) + ($showItems ? $itemLines : 0) + $returnLines;

    $tiers = [
        'large' => ['fs' => 10.5, 'pad' => '5px 6px', 'head' => 54, 'logo' => 46, 'courier' => 30, 'barcode' => 80, 'awb' => 17, 'pin' => 15, 'brand' => 17, 'item' => '4px 5px', 'gap' => 3],
        'medium' => ['fs' => 9.5, 'pad' => '4px 5px', 'head' => 44, 'logo' => 38, 'courier' => 26, 'barcode' => 60, 'awb' => 15, 'pin' => 13, 'brand' => 15, 'item' => '3px 4px', 'gap' => 2],
        'compact' => ['fs' => 8.5, 'pad' => '3px 5px', 'head' => 36, 'logo' => 32, 'courier' => 22, 'barcode' => 44, 'awb' => 13, 'pin' => 12, 'brand' => 13, 'item' => '2px 4px', 'gap' => 2],
    ];
    $tier = !$isThermal ? 'desktop' : ($density <= 19 ? 'large' : ($density <= 24 ? 'medium' : 'compact'));
    $s = $tiers[$tier] ?? ['fs' => 10, 'pad' => '3px 5px', 'head' => 44, 'logo' => 42, 'courier' => 26, 'barcode' => 50, 'awb' => 16, 'pin' => 12, 'brand' => 14, 'item' => '2px 4px', 'gap' => 2];
@endphp
<style>
    @page { margin: {{ $isThermal ? '6px' : '24px' }}; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; font-size: {{ $s['fs'] }}px; color: #000; }
    .label { width: {{ $isThermal ? '100%' : '62%' }}; border-collapse: collapse; table-layout: fixed; }
    .label > tbody > tr > td, .label > tr > td { border: 1px solid #000; padding: {{ $s['pad'] }}; vertical-align: top; }
    p { margin: 0 0 {{ $s['gap'] }}px; line-height: 1.3; }
    .b { font-weight: bold; }
    .muted { color: #333; }
    .brand { font-size: {{ $s['brand'] }}px; font-weight: bold; }
    .center { text-align: center; }
    .items { width: 100%; border-collapse: collapse; }
    .items th, .items td { padding: {{ $s['item'] }}; border-bottom: 1px solid #000; border-right: 1px solid #000; text-align: left; vertical-align: top; }
    .items th:last-child, .items td:last-child { border-right: none; }
    .items tr:last-child td { border-bottom: none; }
    .awb { font-size: {{ $s['awb'] }}px; font-weight: bold; letter-spacing: 1px; margin: 3px 0 0; }
    .page-break { page-break-after: always; }
</style>

<body>
    {{-- ================= Page 1: the shipping label ================= --}}
    <table class="label">
        <tr>
            <td style="width: 66%; vertical-align: middle; height: {{ $s['head'] }}px;">
                @if ($sellerLogo)
                    <img src="{{ $sellerLogo }}" style="max-height: {{ $s['logo'] }}px; max-width: 160px;" alt="">
                @else
                    <span class="brand">{{ $sellerBrand }}</span>
                @endif
            </td>
            <td class="center" style="width: 34%; vertical-align: middle;">
                @if ($courierLogo)
                    <img src="{{ $courierLogo }}" style="max-height: {{ $s['courier'] }}px; max-width: 90px;" alt="">
                @else
                    <p class="b">{{ $shipping->name ?? '' }}</p>
                @endif
                <p class="b" style="margin: 3px 0 0;">{{ $order->shipping_mode }}</p>
            </td>
        </tr>

        <tr>
            <td>
                <p><span class="b">Deliver To:</span> {{ $customerName }}</p>
                <p><span class="b">Contact:</span> {{ $customer->mobile ?? '' }}</p>
                <p><span class="b">Address:</span> {{ \Illuminate\Support\Str::limit($customerAddress, $isThermal ? 150 : 260) }}</p>
                <p><span class="b">PIN:</span> <span class="b" style="font-size: {{ $s['pin'] }}px;">{{ $customerAddr->zip_code ?? '' }}</span></p>
                <p>
                    <span class="b">Payment:</span>
                    @if ($isCod)
                        <span class="b">COD</span>@if ($pref->label_show_cod_amount) - <span class="b">{{ $money($order->cod_amount) }}</span>@endif
                    @else
                        Prepaid
                    @endif
                </p>
            </td>
            <td>
                <p><span class="b">Order:</span> {{ $order->order_prefix }}</p>
                @if ($order->platform_order_id)
                    <p><span class="b">{{ ucfirst($order->platform_source ?: 'Channel') }}:</span> {{ $order->platform_order_name ?: $order->platform_order_id }}</p>
                @endif
                <p><span class="b">Date:</span> {{ $order->order_date }}</p>
                @if ($pref->label_show_invoice_number && $order->invoice_no)
                    <p><span class="b">Inv No:</span> {{ $order->invoice_no }}</p>
                @endif
                @if ($pref->label_show_invoice_value)
                    <p><span class="b">Inv Value:</span> {{ $money($order->total_amount) }}</p>
                @endif
                @if ($pref->label_show_taxes && (float) $order->total_gst_amount > 0)
                    <p><span class="b">GST:</span> {{ $money($order->total_gst_amount) }}</p>
                @endif
                @if ($pref->label_show_weight)
                    <p><span class="b">Weight:</span> {{ (float) $order->weight }} KG</p>
                    @if ((float) $order->length > 0)
                        <p class="muted">{{ (float) $order->length }}x{{ (float) $order->width }}x{{ (float) $order->height }} cm</p>
                    @endif
                @endif
            </td>
        </tr>

        @if ($showItems && $firstItems->count())
            <tr>
                <td colspan="2" style="padding: 0;">
                    @include('order.partials.label-items', ['rows' => $firstItems, 'withTotal' => true])
                </td>
            </tr>
        @endif

        <tr>
            <td colspan="2" class="center" style="padding: 6px 5px;">
                @if (!empty($orderIdBarcodePng))
                    <img src="data:image/png;base64,{{ $orderIdBarcodePng }}" alt="Barcode"
                        style="height: {{ $s['barcode'] }}px; width: 86%;">
                @endif
                <p class="awb">{{ $order->awb_number }}</p>
            </td>
        </tr>

        @if ($showReturn && $warehouse)
            <tr>
                <td colspan="2">
                    <p class="b">If not delivered, return to:</p>
                    @if ($pref->label_show_company_name && $warehouse->company_name)
                        <p class="b">{{ $warehouse->company_name }}</p>
                    @endif
                    @if ($pref->label_show_seller_address && $pickupAddress)
                        <p>{{ \Illuminate\Support\Str::limit($pickupAddress, $isThermal ? 140 : 240) }}</p>
                    @endif
                    @if ($pref->label_show_seller_name || $pref->label_show_seller_mobile)
                        <p>
                            @if ($pref->label_show_seller_name && $warehouse->contact_name)
                                <span class="b">Contact:</span> {{ $warehouse->contact_name }}
                            @endif
                            @if ($pref->label_show_seller_mobile && $warehouse->contact_number)
                                &nbsp; <span class="b">Ph:</span> {{ $warehouse->contact_number }}
                            @endif
                        </p>
                    @endif
                </td>
            </tr>
        @endif

        @include('order.partials.label-footer', ['pageNo' => 1])
    </table>

    {{-- ============ "Print multiple": remaining items continue on the next pages ============ --}}
    @foreach ($continuation as $index => $pageItems)
        <div class="page-break"></div>
        <table class="label">
            <tr>
                <td style="width: 66%; vertical-align: middle;">
                    <p class="b" style="font-size: {{ $s['fs'] + 2 }}px;">Order {{ $order->order_prefix }} - items (continued)</p>
                    <p><span class="b">AWB:</span> {{ $order->awb_number }}</p>
                    <p><span class="b">Deliver To:</span> {{ $customerName }}, {{ $customerAddr->zip_code ?? '' }}</p>
                </td>
                <td class="center" style="width: 34%; vertical-align: middle;">
                    <p class="b" style="font-size: {{ $s['fs'] + 3 }}px;">Page {{ $index + 2 }} / {{ $totalPages }}</p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0;">
                    {{-- Order totals are on page 1 --}}
                    @include('order.partials.label-items', ['rows' => $pageItems, 'withTotal' => false, 'firstPage' => false])
                </td>
            </tr>
            @include('order.partials.label-footer', ['pageNo' => $index + 2])
        </table>
    @endforeach
</body>

</html>
