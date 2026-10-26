{{-- Last row of a shipping label page: page number (multi-page labels) and "Powered by" --}}
<tr>
    <td colspan="2" style="padding: 3px 6px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: none; padding: 0; font-size: 8px; vertical-align: middle;">
                    @if ($totalPages > 1)
                        <span class="b">Page {{ $pageNo }} of {{ $totalPages }}</span>
                    @endif
                </td>
                <td style="border: none; padding: 0; text-align: right; vertical-align: middle;">
                    <span class="b" style="font-size: 7px; vertical-align: middle;">Powered by</span>
                    @if ($platformLogo)
                        <img src="{{ $platformLogo }}" style="height: 13px; vertical-align: middle;" alt="">
                    @else
                        <span class="b" style="font-size: 8px;">{{ config('setting.company_name') }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
