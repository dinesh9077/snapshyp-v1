{{-- Item rows of a shipping label page; uses the column switches of order.thermal-label --}}
<table class="items">
    <tr>
        @if ($showName)<th>Product</th>@endif
        @if ($showSku)<th style="width: 28%;">SKU</th>@endif
        @if ($showQty)<th style="width: 12%;">Qty</th>@endif
        @if ($showPrice)<th style="width: 22%;">Price</th>@endif
    </tr>
    @foreach ($rows as $product)
        <tr>
            @if ($showName)<td>{{ \Illuminate\Support\Str::limit($product->product_name, $nameLimit) }}</td>@endif
            @if ($showSku)<td>{{ \Illuminate\Support\Str::limit($product->sku_number, (int) $pref->label_sku_limit) }}</td>@endif
            @if ($showQty)<td>{{ $product->quantity }}</td>@endif
            @if ($showPrice)<td>{{ number_format((float) $product->amount, 2) }}</td>@endif
        </tr>
    @endforeach
    @if (($firstPage ?? true) && $hiddenItems > 0)
        <tr><td colspan="{{ $itemCols }}" class="muted">+ {{ $hiddenItems }} more item(s)</td></tr>
    @endif
    @if (($firstPage ?? true) && $continuation->count())
        <tr><td colspan="{{ $itemCols }}" class="b">{{ $moreItems->count() }} more item(s) continued on the next page</td></tr>
    @endif
    @if ($withTotal && ($showQty || $showPrice))
        <tr>
            @if ($textCols > 0)
                <td colspan="{{ $textCols }}" class="b" style="text-align: right;">Total ({{ $products->count() }} items)</td>
            @endif
            @if ($showQty)<td class="b">{{ $products->sum('quantity') }}</td>@endif
            @if ($showPrice)<td class="b">{{ $money($order->total_amount) }}</td>@endif
        </tr>
    @endif
</table>
